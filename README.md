# SimpleStats

Track page views, referrers and devices on your Kirby 3 website.
This plugin provides a simple solution for **self-hosted**, **minimal** and **non-intrusive** visitor analytics.

![Simplestats Screenshot](k3-simplestats.png)

- It tracks **referrer URLs** to keep track of who links to your website, categorised as either `search`, `social` or `website`.
- It tracks **device information** (device types, browser engine, OS family; all version-less) for keeping track of how your website is visited.
- It tracks **page visits**, counting 1 hit per page per unique user per language, every 24H.
- The collected data is stored in a **.sqlite database** (raw data) and can be visualised using Kirby's admin panel (computed).

### How it works
- Tracking happens when the page is served by Kirby.
- A crypted user-unique fingerprint is stored in order to track unique page views.
_The formula is `md5( trunc(IP) + trunc(UserAgent) + Salt )`, which still, according to GDPR, seems to be considered as personal data._
It's stored together with a list of visited pages, the device category (bot/mobile/desktop/tablet/other), the browser's engine (Gecko/Webkit/Blink/Other) and OS Family.
This data is kept for a very short amount of time to ensure only counting unique hits.
- After 24H, the collected data is processed and any user identifying data is deleted :
  - The visited pages' hit counts are incremented, globally and per language.
  - Device, Engine and OS Family are separately incremented.
- Referrers are processed immediately and not bound to any user-related identifier.
- For now, all data is summed up on a monthly basis; it only tracks the amount of visits per month. *Future releases might let you set a custom time span.*


### Current state
This is very alpha state. I've tested it on a few online and online configurations. It's been reported to work on other websites too, while there are also reports of this plugin not working.
Please note that the database structure might evolve over time, until a more stable release is available.


### Contributing
I guess a lot of options could be added to suit the plugin for a wider variety of website setups. The panel interface could also be improved and translated.
Any contributions (discussions, reports, feedback and pull requests) are welcome, as long as the collected stats stay minimal and reasonably non-intrusive.

****


## Installation

#### Option 1 : Download

Download and copy this repository to `/site/plugins/simplestats`.

#### Option 2 : Git submodule

```
git submodule add https://github.com/daandelange/kirby3-simplestats.git site/plugins/simplestats
```
Eventually add `--depth 1` to discard the repo commit history. (saves disk space)

****

### Configuration

*Soon...*

For now, check the comments in `options.php`, while it's not recommended to change the default behaviour yet.

#### Language setup
Multilanguage websites are supported. For each page, there's a global counter, with an optional counter for each language.
Also, the panel view has not (yet?) been translated.

#### Legal configuration
Depending on your local laws, you might need to sit down and define how personal visitor data is handled.
You might want to inspect the source code to know what's going on in details.
As the license states, there's no guarantee whatsoever.

#### Options
Like any Kirby plugin, options can be set in your `site/config/config.php`.
Available options are listed and explained in `src/config/options.php`.
Example :
````PHP
// site/config/config.php
return [
  // [...] your options ...

  // Simplestats
  'daandelange.simplestats.panel.enable' => false, // Disable the panel view completely
  'daandelange.simplestats.tracking.enableReferers' => false, // Disable referer tracking
];
````

### Updating
Before updating, make sure to **backup your database file**. If something goes wrong, you'll be able to retrieve your stats.

Depending on the installation method you used, proceed to the logical steps to update.

After updating, review new options and configure as wished.
Sometimes, a database upgrade is needed. If so, head to the panel's `Information` tab and follow instructions in the upgrade section.


****

## Panel Interface

*WIP...*

### Charts
Charts are interactive, you can hover them to have details, and click labels to toggle filtering. You can even download a chart as png.

### Tables
Tables are interactive and paginated. You can search data within and sort them by clicking on the column headers.


****

## Development

Development was started from [a standard Kirby PluginKit](https://github.com/getkirby/pluginkit/tree/4-panel), see [their plugin guide](https://getkirby.com/docs/guide/plugins/plugin-setup-basic) for more details on using it.
*These steps are optional, for building development versions.*

- Npm requirements    : `npm install -g parcel-bundler`
- Setup               : `cd /path/to/website/site/plugins/simplestats && npm install`
- While developing    : `npm run dev`
- Publish changes     : `npm run build`
- Update dependencies : `npm update`


****

## Powered by

- [DistantNative/tbl-for-kirby](https://github.com/distantnative/tbl-for-kirby) : Table layout for the SimpleStats panel interface. [*MIT*]
- [ChartKick](https://chartkick.com) using [Chart.js]() for displaying interactive charts. [*MIT*]
- Package managers and packers : NPM, Parcel, Composer, Yarn.
- [Kirby CMS](https://getkirby.com) : Providing the plugin interface [[*licensed software*](https://getkirby.com/license)]
- [WhichBrowser/Parser-PHP](https://github.com/WhichBrowser/Parser-PHP) : an accurate and performant php user-agent parser.  [*MIT*]
- [Snowplow/referer-parser](https://github.com/snowplow-referer-parser/referer-parser) : a performant php referer parser. [*GNU GPL 3.0*]

## Alternatives / Similar

- [DistantNative/retour-for-kirby](https://github.com/distantnative/retour-for-kirby) : Manage redirects and track 404s right from the Panel.
- [Bnomei/Pagecounter](https://github.com/bnomei/kirby3-pageviewcounter) : Count page hits and last visited date on your Kirby pages.
- [SylvainJulé/kirby-matomo](https://github.com/sylvainjule/kirby-matomo) : A Matomo wrapper for Kirby.


## License

- [MIT](./LICENSE.md) - Free to use, free to improve.

- Copyright 2020-2021 [Daan de Lange](https://github.com/daandelange)
