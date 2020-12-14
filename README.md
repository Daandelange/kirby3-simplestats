# SimpleStats

Track pageviews, referrers and devices on your Kirby 3 website.
This plugin provides simple solution for **self-hosted**, **minimal** and **non-intrusive** visitor analytics.

It embeds a php tracking solution that creates and populates a single database file when your Kirby pages are served.
A panel allows connected users to visualise the computed stats.

- A crypted user-unique fingerprint is stored in order to track unique page views. `md5(IP+Browser+Salt)`
- After 24H, it's processed and dissociated from any user identifying data.
- All data is summed up on a monthly basis. *There is no weekly or daily precision.*


### Current state
This is very alpha, and has not been tested. It is an initial codebase to be improved.
Discussions and contributions are welcome, as long as the stats stay minimal.

****


## Installation

### Option 1 : Download

Download and copy this repository to `/site/plugins/simplestats`.

### Option 2 : Git submodule

```
git submodule add https://github.com/daandelange/kirby3-simplestats.git site/plugins/simplestats
```
Eventually add `--depth 1` to discard the repo commit history. (saves disk space)

****

## Configuration

*Soon...*

For now, check the comments in `options.php`, not recommended to change the default behaviour yet.

### Legal configuration
Depending on your local laws, you might need to sit down and define how personal visitor data is handled.
You might want to inspect the source code to know what's going on in details.
As the license states, there's no guarantee whatsoever.

## Options

*Soon...*

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
- While developping   : `npm run dev`
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

- Copyright 2020 [Daan de Lange](https://github.com/daandelange)
