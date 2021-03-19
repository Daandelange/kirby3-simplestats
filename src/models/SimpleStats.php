<?php

declare(strict_types=1);

namespace daandelange\SimpleStats;

//use Kirby\Http\Header;
@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;

use Snowplow\RefererParser\Parser as RefererParser;
use Snowplow\RefererParser\Medium;

use WhichBrowser\Parser as BrowserParser;
use WhichBrowser\Constants\DeviceType;
use WhichBrowser\Constants\BrowserType;

class SimpleStats extends SimpleStatsDb {

    // Trigger track function
    // Note : the uri should be an id, the Kirby uri is translateable.
    public static function track( string $page_uri = '' ): bool {

        // skip ignored paths
        if( empty($page_uri) || in_array($page_uri, option('daandelange.simplestats.tracking.ignore.pages')) === true) {
            return false;
        }

        // tmp : Sync daystats
        Stats::syncDayStats();

        // Skip ignored roles
        if( count(option('daandelange.simplestats.tracking.ignore.roles')) > 0){
            $curUser = kirby()->user();
            $ignores = option('daandelange.simplestats.tracking.ignore.roles');
            if($curUser && $curUser->isLoggedIn()){
                foreach($curUser->roles() as $role){
                    if( in_array($role, $ignores)) return false;
                }
            }
        }

        // Todo: Kirby ignore user roles ?
        // Todo: verify page uri

        // Get unique visitor id
        $userID = SimpleStats::getUserUniqueString();
        $db = self::database();

        // Retrieve user from db
        $userEntry = null;
        //var_dump($db->query("SELECT `visitedpages`, `osfamily` from `pagevisitors` WHERE `userunique`='${userID}' LIMIT 1")->first());
        $userResult = $db->query("SELECT `visitedpages`, `osfamily` from `pagevisitors` WHERE `userunique`='${userID}' LIMIT 1");
        if(!$userResult){
            Logger::LogWarning("Could not select existing visitor. Aborting tracking. Error=".$db->lastError()->getMessage());
            return false;
        }

        $userEntry = $userResult->first();

        // Bot detection / ignore
        $userIsBot = false;

        // New user ?
        if($userEntry===null){
            // Default values
            $timestamp = time();
            $osfamily = $devicetype = $browserengine = '';
            $visitedpages = '';

            // Get device info
            // Note: Bot devices are always tracked, user devices as per settings
            $info = SimpleStats::detectSystemFromUA();
            $userIsBot = ($info['system'] == 'bot');

            // Track Devices and/or Visits
            if ( option('daandelange.simplestats.tracking.enableVisits') === true || option('daandelange.simplestats.tracking.enableDevices')===true ){

                // Populate visited pages
                if( option('daandelange.simplestats.tracking.enableVisits') === true ){
                    $visitedpages = self::getPageIDWithLang($page_uri);
                }

                // Populate device info ?
                // Note: Don't respect bot privacy, track them as much as you can !
                if( $userIsBot || option('daandelange.simplestats.tracking.enableDevices')===true ){
                    $osfamily = $info['system'];
                    $devicetype = $info['device'];
                    $browserengine = $info['engine'];
                }

                //echo "INSERT INTO `pagevisitors` (userunique, timeregistered, osfamily, devicetype, browserengine, visitedpages) VALUES ('${userID}', ${timestamp}, '${osfamily}', '${devicetype}', '${browserengine}', '${visitedpages}')";
                if( !$db->query("INSERT INTO `pagevisitors` (userunique, timeregistered, osfamily, devicetype, browserengine, visitedpages) VALUES ('${userID}', ${timestamp}, '${osfamily}', '${devicetype}', '${browserengine}', '${visitedpages}')") ){
                    Logger::LogWarning("Could not insert new visitor. Error=".$db->lastError()->getMessage());
                }
                //echo $db->lastError();
                //echo "User created !";
            }
        }
        // Update current user
        else {
            $userIsBot = ($userEntry->osfamily == 'bot');

            // Append  visited pages (except bots)
            // Note: Bot visits are not tracked. Todo: Make this an option
            if( !$userIsBot && option('daandelange.simplestats.tracking.enableVisits') === true ){
                $page_uri = self::getPageIDWithLang($page_uri);

                // Check if the page was already visited.
                if( !in_array($page_uri, explode(',', $userEntry->visitedpages) )){
                    // Add page visit
                    $newPages = (!empty($userEntry->visitedpages)?$userEntry->visitedpages.',':'').$page_uri;
                    if( !$db->query("UPDATE `pagevisitors` SET `visitedpages` = '${newPages}' WHERE `userunique`='${userID}'; ") ){
                        Logger::LogWarning("Failed to update page visitors : ${userID}. Error=".$db->lastError()->getMessage());
                    }
                }
            }
        }

        // Ignore bots from here (they only participate to device stats)
        if( $userIsBot ){
            //echo "IgnoredBot!";
            return true;
        }

        // Track referer
        if ( option('daandelange.simplestats.tracking.enableReferers') === true ){
            $refererInfo = SimpleStats::getRefererInfo();

            //$referer = '';
            if($refererInfo){
                $refererUrl = $refererInfo['url'];
                $yearmonth = date('Ym');

                // Retrieve referer from db
                $refererEntry = null;
                $refererEntry = $db->query("SELECT `id` from `referers` WHERE `referer`='${refererUrl}' AND `monthyear`=${yearmonth} LIMIT 1");
                // Referer already exists. Increment hits.
                if( $refererEntry ){
                    if( $refererEntry->isNotEmpty() ){
                        $id = intval( $refererEntry->first()->id );

                        if( !$db->query("UPDATE `referers` SET `hits` = `hits`+1 WHERE `id`='${id}'; ") ){
                            Logger::LogWarning("Failed to update referer : ${refererUrl}. Error=".$db->lastError()->getMessage());
                        }
                    }
                    // Insert new referer
                    else {//if( !$refererEntry || $refererEntry == null ){
                        // known medium hold name instead of domain
                        $domain = (!empty($refererInfo['source']))?$refererInfo['source']:$refererInfo['host'];
                        $medium = $refererInfo['medium'];
                        //echo "INSERT INTO `referers` (referer, domain, monthyear, hits) VALUES ('${refererUrl}', '${domain}', ${yearmonth}, 1)";
                        if(!$db->query("INSERT INTO `referers` (referer, domain, monthyear, hits, medium) VALUES ('${refererUrl}', '${domain}', ${yearmonth}, 1, '${medium}')")){
                            Logger::LogWarning("Failed to insert new referer : ${refererUrl}. Error=".$db->lastError()->getMessage());
                        }
                    }
                }
                else {
                    Logger::LogWarning("Failed to retrieve this month's referrers from db. Referrer : ${refererUrl}. Error=".$db->lastError()->getMessage());
                }
            }
            // Unable to parse referer ?
            else {
                // Internal, Empty or incorrect referer, don't track anything.
                if( isset($_SERVER['HTTP_REFERER']) && (stripos((isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'], $_SERVER['HTTP_REFERER']) === 0) ){
                    Logger::LogVerbose("Referrer is set, but could not parse it : ".$_SERVER['HTTP_REFERER'].'.');
                }

                return true;
            }

        }

        return true;
    }

    public static function getPageIDWithLang($page_uri): string {

        // With language ?
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ) {
            $curLang = kirby()->language();
            if(!$curLang) $curLang = 'none';
            else $curLang = $curLang->code();
            return $page_uri .'::'. $curLang;
        }
        else {
            return $page_uri;
        }

    }

    // Combines the ip + user_agent to get a unique user string
    public static function getUserUniqueString(string $ua = ''): string {
        $ip = preg_replace("/[\.\:]+/", '_', preg_replace("/[^a-zA-Z0-9\.\:]+/", '', substr($_SERVER['REMOTE_ADDR'],0,128))); // $kirby->visitor()->ip()
        $ua = preg_replace("/[^a-zA-Z0-9]+/", '', substr($_SERVER['HTTP_USER_AGENT'], 0, 256) ); // $kirby->visitor()->ip()->userAgent()
        $salt = option('daandelange.simplestats.tracking.salt');
        // Compute final string
    	$final = '';
    	$iplen=strlen($ip);
    	$ualen=strlen($ua);
    	$saltlen=strlen($salt);
    	$max=max($iplen, $ualen, $saltlen);
    	for($i=0; $i<$max; $i++){
    		if( $i < $iplen ) $final.=$ip[$i];
    		if( $i < $ualen ) $final.=$ua[$i];
    		if( $i < $saltlen ) $final.=$salt[$i];
    	}
    	//echo '----'.($ip.$salt.$ua).'----';
    	return hash("sha1", base64_encode($final));
    }

    // Returns an array with detected user hardware setup
    public static function detectSystemFromUA( $ua = '' ): array {
        // Kirby method : $kirby->visitor()->ip()->userAgent()
    	if(empty($ua)) $ua = substr($_SERVER['HTTP_USER_AGENT'], 0, 256);

        $data = [
            'engine' 	=> 'undefined',
            'device'	=> 'undefined',
            'system'	=> 'undefined'
        ];

        // Respect DNT requests
        // Todo: Don't respect DNT bots !
    	if (array_key_exists('HTTP_DNT', $_SERVER) && (1 === (int) $_SERVER['HTTP_DNT'])){
    		// Don't collect private fingerprintable user data
    		//$data['engine']=$data['device']=$data['system']='Anonymous';
    		//return $data;
    	}

    	// Todo : Handle opt.out ?

        // Parser
        $headers = getallheaders();
        $headers['HTTP_USER_AGENT']=$ua;
        $clientData = new BrowserParser();//$headers, [ 'detectBots' => true, 'useragent'=>false, 'engine'=>false,'features'=>false ]);
    	$clientData->analyse($headers, [ 'detectBots' => true, 'useragent'=>false, 'engine'=>false,'features'=>false ]);
    	// Todo: set engine to true above ???
        //echo $clientData->os->name.' :: '.$clientData->engine->name.' :: '.$clientData->device->type."<br>\n";

    	// Detected something ?
    	if( $clientData->isDetected() ){

    		// Got a bot ?
    		if( $clientData->isType(DeviceType::BOT)){//device->type == "bot" ){
    			$data['engine']=$data['system']='bot';
    			$data['device']='server';
    			return $data;
    		}
    		// No bot
    		else {
    			// Save Device info
    			// todo: Save only desktop / tablet / mobile / other
    			// $data['device']=$clientData->device->type;
    			if( $clientData->isType(DeviceType::DESKTOP) ){
    				$data['device']='desktop';
    			}
    			elseif( $clientData->isType(DeviceType::MOBILE) ){
    				$data['device']='mobile';
    			}
    			elseif( $clientData->isType(DeviceType::TABLET) ){
    				$data['device']='tablet';
    			}
    			else {
    				// todo: set to desktop ? Or other ?
    				if( $clientData->isMobile() ){
    					$data['device']='mobile';
    				}
    				else if( !empty($clientData->device) && !empty($clientData->device->type) ){
    					//var_dump($clientData->device);
    					$data['device']='other';
    				}
    				//elseif() // check browser here for fallback ?
    				else {
    					$data['device']='undefined';
    				}
    			}

    			// Save OS info
    /*
    			$data['system']=$clientData->os->name;
    			if( $clientData->isOs('desktop') ){
    				$data['system']=$clientData->os->name;
    			}
    */
    			if( isset($clientData->os->family) && !empty($clientData->os->family->name) ){
    				$data['system']=$clientData->os->family->name;// .'/'.$clientData->os->name;
    			}
    			elseif( isset($clientData->os->name) && !empty($clientData->os->name) ){
    				$data['system']=$clientData->os->name;
    			}
    			elseif( $clientData->os->isDetected() ){
    				$data['system']='other';
    			}
    			else{
    				//var_dump($clientData->os);
    				//echo WhichBrowser\Model\
    				$data['system']='unknown';
    			}

    			// Engine info
    			//$data['engine']=$clientData->engine->name;
    			// Engine detected ?
    			if( !empty($clientData->engine) && isset($clientData->engine->family->name) && !empty($clientData->engine->family->name) ){
    				$data['engine']=$clientData->engine->family->name;
    			}
    			elseif( !empty($clientData->engine) && isset($clientData->engine->name) && !empty($clientData->engine->name) ){
    				$data['engine']=$clientData->engine->name;
    			}
    			// Look for engine fallback in browser type
    			elseif( isset($clientData->browser->type) ) {
    				//if( $clientData->browser->type ^= 'browser'
    				if( $clientData->browser->type === BrowserType::BROWSER_TEXT ){//'browser:text'){
    					$data['engine']='textbased';
    				}
    				elseif( stripos( $clientData->browser->type, BrowserType::BROWSER) === 0 ){
    					$data['engine']='other';
    				}
    				elseif( stripos( $clientData->browser->type, BrowserType::APP) === 0 ){
    					$data['engine']='application';
    				}
    				else {
    					$data['engine']='unknown';
    				}
    			}
    			else {
    				$data['engine']='unknown';
    			}
    		}
    	}
    	// No client data detected
    	else {
    		$data['engine']=$data['device']=$data['system']='other';
    		return $data;
    	}
    	return $data;
    }

    // Referer retrieval
    public static function getRefererInfo(): ?array {
        $returnData = [
            'url'       => '', // Full url
            'medium'    => '', // ex: social / search / website / other (unknown)
            'source'    => '', // ex: Google, Microsoft, [empty]
            'host'      => '', // domain name (with subdomain)
        ];

        if( isset($_SERVER['HTTP_REFERER']) ){
            $refHeader = $_SERVER['HTTP_REFERER'];
            	$parser = new RefererParser(/* null, $_SERVER['HTTP_HOST'] */);
            	$referer = $parser->parse($refHeader, (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            	//echo "Got referer! == ".$_SERVER['HTTP_REFERER']."\n";
            	//echo " --- ".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            	if( $referer->isValid() ){
            		if ($referer->isKnown()) {
            			$returnData['medium']=$referer->getMedium();
                        $returnData['source']=$referer->getSource();

                        if( $urlParts = parse_url($refHeader) ){
                            //var_dump($urlParts);
                            // Note: protocol and query strings are stripped
                            $returnData['url']=$urlParts['host'].$urlParts['path'];//str_replace('www.','', $urlParts['host'].$urlParts['path'];
                            $returnData['host']=$urlParts['host'];

                            // Todo: protect url against sql injections via url ?
                        }
                        //var_dump($returnData);
                        return $returnData;
            		}
            		else {
            			if( $referer->getMedium() == Medium::INTERNAL ){
            				// IGNORE internals
            				//echo "Got INTERNAL referer !";
            				return null;
            			}
            			// Referer is valid but unknown (other)
            			else {
            				//echo "Got UNKNOWN referer!";
            				//echo $referer->getMedium(); // "Search"
                			//echo ' - ';
                			//echo $referer->getSource(); // "Google"
                            $returnData['medium']='website';$referer->getMedium(); // unknown
                            $returnData['source']=''; // other ?
                            if( $urlParts = parse_url($refHeader) ){
                                //var_dump($urlParts);
                                // Note: protocol and query strings are stripped
                                $returnData['url']=$urlParts['host'].$urlParts['path'];
                                $returnData['host']=$urlParts['host'];

                                // Todo: protect url against sql injections via url ?

                                //var_dump($returnData);
                                return $returnData;
                            }
                            else {
                                // No valid URL
                                return null;
                            }

                            //var_dump($returnData);
            			}
            			//var_dump($referer);
            			//echo $referer->getMedium();
            		}
            	}
            	// Referer is an invalid URL (or empty)
            	else {
            		// IGNORE
            		//echo "Got INVALID referer !\n";
            		return null;
            	}
        }
        // default return value (no referrer)
        return null;
    }
}
