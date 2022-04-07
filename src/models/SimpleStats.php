<?php

declare(strict_types=1);

namespace daandelange\SimpleStats;

//use Kirby\Http\Header;
@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;
use Kirby\Cms\Page;
use Kirby\Cms\Response;

use Snowplow\RefererParser\Parser as RefererParser;
use Snowplow\RefererParser\Medium;

use WhichBrowser\Parser as BrowserParser;
use WhichBrowser\Constants\DeviceType;
use WhichBrowser\Constants\BrowserType;

class SimpleStats extends SimpleStatsDb {

    // track() without exceptions
    public static function safeTrack(string $page_uri = ''){
        try {
            return SimpleStats::track($page_uri);
        } catch (Throwable $e) {
            // If logging enable, initialize model and add record
            if (option('daandelange.simplestats.log.tracking') === true) {
                Logger::logTracking('Error tracking page: '.$page.'. Error='.$e->getMessage().'(file: '.$e->getFile().'#L'.$e->getLine().')');
            }
        }
        return false;
    }

    // Generates a router response for serving the tracker image
    public static function trackPageAndServeImageResponse(Page $page){
        // Correct tracking method ?
        if( SimpleStatsTrackingMode::OnImage === option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnLoad) ){
            // Any tracking feature is enabled ?
            if(
                true===option('daandelange.simplestats.tracking.enableDevices' , true) ||
                true===option('daandelange.simplestats.tracking.enableVisits'  , true) ||
                true===option('daandelange.simplestats.tracking.enableReferers', true) ||
                true===option('daandelange.simplestats.tracking.enableVisitLanguages', true)
            ){
                // Does the page exist ?
                if( $page && $page->exists() && $page->isPublished() ){
                    SimpleStats::safeTrack( $page->id() );
                    //return var_dump(SimpleStats::safeTrack( $page->id() ));

                    return new Response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII='), 'image/png', 200);

                    //header('Content-Type: image/png');
                    //header("Content-type: image/png");
                    //echo  base64_decode('image/png;base64,'); // Smallest transparent PNG
                    //echo  base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII='); // Smallest transparent PNG

                    //header("Content-type: image/gif");
                    //echo  base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='); // Smallest transparent GIF
                    //echo  base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); // Smallest transparent GIF
                    //exit;
                }
            }
            // Unknown kirby page  or nothing to track, return 404
            return new \Kirby\Exception\ErrorPageException(['httpCode'=>410]); // Gone / Removed (with error page)
        }
        return new \Kirby\Exception\ErrorPageException(['httpCode'=>404]); // Not found (with error page)
        //return new Response(null, null, 410); // Gone / Removed (no page is served)
        //return false; // generates error page
    }

    // Trigger track function
    // Note : the uri should be $page->id(), the Kirby uri is translateable.
    // Additional params are not recommended to use; mainly for testing purposes.
    // Return value : Needs to be unified. Sometimes it returns trackin status (tracked/not tracked), sometimes it indicates errors vs correct tracking behaviour.
    public static function track( string $page_uri = '', int $time = null, \Kirby\Cms\User $user = null, string $forceLang = null  ): bool {

        // Dont allow tracking in disabled mode
        if( SimpleStatsTrackingMode::Disabled === option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnLoad) ){
            return true;
        }

        // Localhost protection #18
        if( true === option('daandelange.simplestats.tracking.ignore.localhost' , false) && in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) ){
            return true;
        }

        // skip ignored paths
        if( empty($page_uri) || in_array($page_uri, option('daandelange.simplestats.tracking.ignore.pages')) === true) {
            return true;
        }

        // Format time
        if(!$time) $time = time();

        // tmp : Sync daystats
        Stats::syncDayStats($time);

        // Skip if any tracking feature is disabled
        if(
            false===option('daandelange.simplestats.tracking.enableDevices' , true) &&
            false===option('daandelange.simplestats.tracking.enableVisits'  , true) &&
            false===option('daandelange.simplestats.tracking.enableReferers', true) &&
            false===option('daandelange.simplestats.tracking.enableVisitLanguages', true)
        ){
            return true;
        }

        // Skip ignored roles
        if( count(option('daandelange.simplestats.tracking.ignore.roles')) > 0){
            // Fetch user
            if(!$user) $user = kirby()->user();
            $ignores = option('daandelange.simplestats.tracking.ignore.roles');
            if($user && $user->isLoggedIn()){
                foreach($user->roles() as $role){
                    if( in_array($role, $ignores)) return false;
                }
            }
        }

        // Verify template exclusions
        $ignoredTemplates = option('daandelange.simplestats.tracking.ignore.templates');
        if( is_array($ignoredTemplates) && count($ignoredTemplates) > 0 ){
            // Try parse page object
            $page = page($page_uri); // Slow... could be faster passing the page object instead of $page->id()
            if($page){
                if( in_array($page->intendedTemplate()->name(), $ignoredTemplates) === true ) return false;
                if( in_array(        $page->template()->name(), $ignoredTemplates) === true ) return false;
            }
            else {
                // continue (?), unknown page = unknown template, cannot verify template exclusion
            }
        }

        // Todo: verify page uri ?

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
        //var_dump('$userEntry', $userEntry);

        // Bot detection / ignore
        $userIsBot = false;

        // New user ?
        if($userEntry===null){
            // Default values
            $timestamp = $time;
            $osfamily = $devicetype = $browserengine = '';
            $visitedpages = '';

            // Get device info
            $info = SimpleStats::detectSystemFromUA();
            $userIsBot = ($info['system'] == 'bot');

            // Ignore bots globally ?
            if( $userIsBot && true === option('daandelange.simplestats.tracking.ignore.bots' , false)){
                return true;
            }

            // Track Devices and/or Visits
            if ( option('daandelange.simplestats.tracking.enableVisits') === true || option('daandelange.simplestats.tracking.enableDevices')===true ){

                // Populate visited pages
                if( option('daandelange.simplestats.tracking.enableVisits') === true ){
                    if( !$userIsBot || false === option('daandelange.simplestats.tracking.ignore.botVisits' , false) ){
                        $visitedpages = self::getPageIDWithLang($page_uri, $forceLang);
                    }
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
                    Logger::LogWarning("Could not insert new visitor : ${userID}. Error=".$db->lastError()->getMessage());
                }
                //echo $db->lastError();
                //echo "User created !";
            }
        }
        // Update current user
        else {
            $userIsBot = ($userEntry->osfamily == 'bot');

            // Ignore bots globally ?
            if( $userIsBot && true === option('daandelange.simplestats.tracking.ignore.bots' , false)){
                return true;
            }

            // Append  visited pages
            if( option('daandelange.simplestats.tracking.enableVisits') === true ){
                // Bot visits are not tracked according to user setting
                if( !$userIsBot || false === option('daandelange.simplestats.tracking.ignore.botVisits' , false) ){

                    $page_uri = self::getPageIDWithLang($page_uri, $forceLang);

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
        }

        // Ignore bots from here (they only participate to device stats)
        if( $userIsBot && true === option('daandelange.simplestats.tracking.ignore.botReferers' , true) ){
            //echo "IgnoredBot!";
            return true;
        }

        // Track referer
        if ( option('daandelange.simplestats.tracking.enableReferers') === true ){
            $refererInfo = SimpleStats::getRefererInfo();

            //$referer = '';
            if($refererInfo){
                $refererUrl = $refererInfo['url'];
                $referrerPeriod = getPeriodFromTime();

                // Retrieve referer from db
                $refererEntry = null;
                $refererEntry = $db->query("SELECT `id` from `referers` WHERE `referer`='${refererUrl}' AND `monthyear`=${referrerPeriod} LIMIT 1");
                // Referer already exists. Increment hits.
                if( $refererEntry ){
                    if( $refererEntry->isNotEmpty() ){
                        $id = intval( $refererEntry->first()->id );

                        if( !$db->query("UPDATE `referers` SET `hits` = `hits`+1 WHERE `id`='${id}'; ") ){
                            Logger::LogWarning("Failed to update referrer : ${refererUrl}. Error=".$db->lastError()->getMessage());
                        }
                    }
                    // Insert new referer
                    else {//if( !$refererEntry || $refererEntry == null ){
                        // known medium hold name instead of domain
                        $domain = (!empty($refererInfo['source']))?$refererInfo['source']:$refererInfo['host'];
                        $medium = $refererInfo['medium'];
                        //echo "INSERT INTO `referers` (referer, domain, monthyear, hits) VALUES ('${refererUrl}', '${domain}', ${referrerPeriod}, 1)";
                        if(!$db->query("INSERT INTO `referers` (referer, domain, monthyear, hits, medium) VALUES ('${refererUrl}', '${domain}', ${referrerPeriod}, 1, '${medium}')")){
                            Logger::LogWarning("Failed to insert new referrer : ${refererUrl}. Error=".$db->lastError()->getMessage());
                        }
                    }
                }
                else {
                    Logger::LogWarning("Failed to retrieve this month's referrers from db. Referrer : ${refererUrl}. Error=".$db->lastError()->getMessage());
                }
            }
            // Unable to parse referer ?
            else {
                // Internal, Empty or incorrect referrer, don't track anything.
                if( isset($_SERVER['HTTP_REFERER']) ){
                    $selfHostPos = stripos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']);
                    // check for pos 8 (after https://), or pos 7 (after http://)
                    if( $selfHostPos !== 7 && $selfHostPos !== 8 ){
                        Logger::LogVerbose("Referrer is set, but could not parse it : ".$_SERVER['HTTP_REFERER'].'. / '.((isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST']));
                    }
                }
                return true;
            }

        }

        return true;
    }


    public static function getPageIDWithLang($page_uri, string $forceLang = null): string {

        // With language ?
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ) {
            $curLang = kirby()->language($forceLang)??kirby()->language();
            if(!$curLang) $curLang = 'none';
            else $curLang = $curLang->code();
            return $page_uri .'::'. $curLang;
        }
        else {
            return $page_uri;
        }

    }


    /**
     * Anonymizes given IP address
     *
     * See https://github.com/geertw/php-ip-anonymizer
     *
     * @param string $address IP address
     * @return string Anonymized IP address or null if no valid IP.
     */
    public static function anonymize(string $address): ?string {
        $addressPacked = inet_pton($address);
        if(!$addressPacked) return null; // return early when IP address has wrong format
        // Note: sometimes, on localhost, ip can be `::1` which is still valid and gets stripped to `::`

        $bitsToAnonymize = option('daandelange.simplestats.tracking.anonimizeIpBits', 1);
        if($bitsToAnonymize < 1) return inet_ntop($addressPacked); // Return early when no-anonymization

        $ipBits = strlen($addressPacked);

        if ($ipBits == 4 || $ipBits == 16) {
            $isIpv6 = ($ipBits == 16);
            if($isIpv6){
                $bitsToAnonymize *= 2; // note: ipv6 has double anonimized bits
                $ipBits = 8;
            }

            $maskMax = array_fill(0, $ipBits, '');
            $maskMax = array_map(function($k, $v) use($ipBits, $bitsToAnonymize, $isIpv6) {
                return ($k >= $ipBits-$bitsToAnonymize )?(($isIpv6?'0000':'0')):($isIpv6?'ffff':'255');
            }, array_keys($maskMax), $maskMax);
implode($isIpv6?':':'.', $maskMax);

            return inet_ntop($addressPacked & inet_pton(implode($isIpv6?':':'.', $maskMax)));
        }

        return null;
    }


    // Combines the ip + user_agent to get a unique user string
    public static function getUserUniqueString(string $ua = ''): string {
        // Anonymize IP beforehand (if enabled)
        $ip = static::anonymize($_SERVER['REMOTE_ADDR']); // $kirby->visitor()->ip()

        // Replace `.:` by `_`
        $ip = preg_replace("/[\.\:]+/", '_', $ip);
        $ua = preg_replace("/[^a-zA-Z0-9]+/", '', substr(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'UserAgentNotSet', 0, 256) ); // $kirby->visitor()->ip()->userAgent()
        $salt = option('daandelange.simplestats.tracking.salt');

        // Compute final string mixing the 3 previous ones
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
        return hash('sha1', base64_encode($final));
    }

    // Returns an array with detected user hardware setup
    public static function detectSystemFromUA( $ua = null ): array {
        // Kirby method : $kirby->visitor()->ip()->userAgent()
        if($ua===null) $ua = substr($_SERVER['HTTP_USER_AGENT'], 0, 256);

        $data = [
            'engine' => 'undefined',
            'device' => 'undefined',
            'system' => 'undefined'
        ];

        // Respect DNT requests
        // Todo: Don't respect DNT bots !
        if (array_key_exists('HTTP_DNT', $_SERVER) && (1 === (int) $_SERVER['HTTP_DNT'])){
            // Don't collect private fingerprintable user data
            //$data['engine']=$data['device']=$data['system']='Anonymous';
            //return $data;
        }

        // Todo : Handle opt.out ?

        // Get Headers with replaced ua
        $headers = getallheaders();
        if(isset($headers['HTTP_USER_AGENT'])) $headers['HTTP_USER_AGENT']=$ua;
        $headers['User-Agent']=$ua;
        unset($headers['x-requested-with']); // $headers['x-requested-with'] = 'xmlhttprequest' interferes and makes all requests mobile devices

        // Parser
        $clientData = new BrowserParser();//$headers, [ 'detectBots' => true, 'useragent'=>false, 'engine'=>false,'features'=>false ]);
        $clientData->analyse($headers, [ 'detectBots' => true, 'useragent'=>false, 'engine'=>true,'features'=>false ]); // Note: Useragent must be false for detection to work
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
    public static function getRefererInfo( string $refHeader = null): ?array {
        if(empty($refHeader) && isset($_SERVER['HTTP_REFERER'])) $refHeader = substr($_SERVER['HTTP_REFERER'], 0, 256);
        $returnData = [
            'url'       => '', // Full url
            'medium'    => '', // ex: social / search / website / other (unknown)
            'source'    => '', // ex: Google, Microsoft, [empty]
            'host'      => '', // domain name (with subdomain)
        ];

        if( !empty($refHeader) ){
                $parser = new RefererParser(/* null, $_SERVER['HTTP_HOST'] */);
                $referer = $parser->parse($refHeader, (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                //echo "Got referer! == ".$refHeader."\n";
                //echo " --- ".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                if( $referer->isValid() ){
                    if ($referer->isKnown()) {
                        $returnData['medium']=$referer->getMedium();
                        $returnData['source']=$referer->getSource();

                        $urlParts = parse_url($refHeader);
                        if( $urlParts && isset($urlParts['host'])){
                            //var_dump($urlParts);
                            // Sanitize yahoo urls specifically ?
                            if( strpos($urlParts['host'], 'yahoo.com')!==false && isset($urlParts['path']) && ($cut=strpos($urlParts['path'], '_ylt')) && $cut !== false) $urlParts['path'] = substr($urlParts['path'], 0, $cut);
                            // Note: protocol and query strings are stripped
                            $returnData['url']=$urlParts['host'].(isset($urlParts['path'])?$urlParts['path']:'');//str_replace('www.','', $urlParts['host'].$urlParts['path'];
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
                        $returnData['medium']='website';//$referer->getMedium(); // Note: All unknown referrers are considered websites.
                        $returnData['source']=''; // other ?
                        $urlParts = parse_url($refHeader);
                        if( $urlParts && isset($urlParts['host']) ){
                            // Note: protocol and query strings are stripped
                            $returnData['url'] =$urlParts['host'].(isset($urlParts['path'])?$urlParts['path']:'');
                            $returnData['host']=$urlParts['host'];

                            // Todo: protect url against sql injections via url ?

                            return $returnData;
                        }
                        else {
                            //echo 'Invalid URL!  ....';//var_dump(parse_url($refHeader));
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
