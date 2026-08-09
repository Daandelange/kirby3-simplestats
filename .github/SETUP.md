# Setup

Here are some instructions for getting started with SimpleStats.

****

## Requirements

 - Kirby 5.0.0+. _(Kirby 4 is known to work but unsupported)._
 - PHP 8.2+ with support for SQLite3.
 - There's an automatic requirements checker in the `Information` tab of the plugin's panel view, feel free to use that for double-checking requirements.

> [!NOTE]
> **Retro-compatibility :**
> - Kirby 3 : Use [0.6.6-beta](https://github.com/Daandelange/kirby-simplestats/tree/0.6.6-beta).
> - Kirby 4 : Use latest version (unsupported) or [0.7.7-beta](https://github.com/Daandelange/kirby-simplestats/tree/0.7.7-beta).


****


## Installation

- **Option 1 : Download**  
  Download and copy this repository to `/site/plugins/simplestats`.

- **Option 2 : Git submodule**  
  ```
  git submodule add https://github.com/daandelange/kirby-simplestats.git site/plugins/simplestats
  ```
  Eventually add `--depth 1` to discard the repo commit history. (saves disk space)

- **Option 3 : Composer** (*update the command to match the latest version*)  
  `composer require daandelange/simplestats:~1.0.0` (update with `composer update`)   
  _Note: I don't make a release for every small change. If you want to use the latest version with composer, you can manually enter a commit :_  
  `composer require daandelange/simplestats:dev-master#dbdb4a2 --with-dependencies`.

> [!IMPORTANT]
> **Read before you proceed !**  
> After installing the plugin and before starting your webserver, you have to configure the plugin by carefully selecting [a tracking resolution](#tracking-resolution) and [your website's languages setup](#language-setup).


### Learn about data processing

Now that you prepare to collect data, it's a good moment to learn about how the data is processed

- Tracking happens when the page is served by Kirby.
- A crypted and anonymized user-unique fingerprint is stored in order to track unique page views.  
  - _[The formula](https://github.com/Daandelange/kirby-simplestats/search?q=getUserUniqueString) is more or less `sha1( base64_encode( mix( anonimize(IP, 0.0.0.x) + trunc(UserAgent) + Salt)) )`._
  - It's stored together with a list of visited pages, the device category (bot/mobile/desktop/tablet/other), the browser's engine (Gecko/Webkit/Blink/Other) and OS Family.
  - This data is kept for a very short amount of time to ensure only counting unique hits.
- After 24H, the collected data is processed and any user identifying data is deleted :
  - The visited pages' hit counts are incremented, globally and per language.
  - Device, Engine and OS Family are separately incremented.
- Referrers are processed immediately and are not bound to any user-related identifier.
- All data is summed up on a monthly basis by default. You can easily change this "timeframe" precision.
- If the database architecture ever evolves, an update meccanism is ready for use to migrate your data.


### Updating

Before updating, make sure to **backup your database file**. If something goes wrong, you'll be able to retrieve your stats by replacing the database file later.

Depending on the installation method you used, proceed to the logical steps to update.

After updating:
- Review new options and configure as wished.
- Sometimes, a database upgrade is needed. If so, head to the panel's `Information` tab and follow instructions in the upgrade section.
- It's also a good idea to check the log file for any errors : `site/logs/simplestats_errors.txt`.


****


## Configuration


### Language Setup

Multi-language websites are supported (as well as single-language ones).  
For each page, there's a global counter together with a separate counter for each language (if not disabled).

> [!WARNING]
> Do not add or remove languages to your Kirby installation without starting with a [fresh database file]((#database-configuration)) !

> [!NOTE]
> The plugin provides panel translations for the following languages : _(other translations are welcome)_
> - English
> - Hungarian


### Tracking Resolution

The tracking resolution is the most important setting to explicitly define.  
It determines the time resolution of the collected data, sampling it on a daily, weekly or monthly basis.

> [!WARNING]
> Do not change the tracking resolution without starting with a [fresh database file](#database-configuration) !


### Database Configuration

The database file is a simple but versatile `.sqlite` file that holds all tracking data.  
You can visualise the data in the panel or easily grab the raw data using the `Stats` helper class, or import it directly in other Sqlite software.

It's recommended to occasionally backup your stats database file, specially before updates.

> [!IMPORTANT]
> *The database is tightly bound to the tracking resolution* option and language setup : *it can not* be changed afterwards.  
>
> Changing these while keeping the same database file results in undefined behaviour.  
> If changing, prefer starting with a fresh database.  
> _There's currently no database migration script for keeping data in such case._
>
> Note: *If you change your multilanguage settings*, you can try to manually edit the previous database file, adding the necessary language columns.
> This [could be automated with update scripts](https://github.com/Daandelange/kirby-simplestats/issues/14).

#### Generating Stats

> [!TIP]
> If you'd like to populate the database with some fake stats (useful for testing or developing SimpleStats), you can use the panel interface to generate some in the "Information" tab.


### Legal Configuration

Depending on your local laws, you might need to sit down and define how personal visitor data is handled.  
You might want to inspect the source code to know [what's going on](#learn-about-data-processing) in details.  
As the license states, there's no guarantee whatsoever.  

### Config Example

Above are the essential configuration options, but like any Kirby plugin, many options can be set in your `site/config/config.php`.  
All available options are listed and explained in `src/config/options.php`; don't hesitate to copy & paste from there !

Example :
````PHP
// file: site/config/config.php

// Add this include if you change the tracking mode or use a SimpleStatsTimeFrameUtility class
require_once(__DIR__ . '/../plugins/d-simplestats/src/models/SimpleStatsTrackingMode.php');
require_once(__DIR__ . '/../plugins/d-simplestats/src/models/SimpleStatsTimeFrameUtility.php');

// The custom variable types (SimpleStatsTimeFrameUtility, SimplestatsTrackingMode) are namespaced, you can shorten their invocations with the line below:
// use daandelange\SimpleStats\SimpleStatsTrackingMode;
// use daandelange\SimpleStats\SimpleStatsTimeFrameUtility;

return [
  // [...] your options ...

  // Getting started
  'daandelange.simplestats.panel.dismissDisclaimer' => true, // I have read & understand the rules
  'daandelange.simplestats.tracking.timeFrameUtility' => 'weekly', // Weekly timespan precision
  'daandelange.simplestats.tracking.salt' => 'SOMETHINGDIFFERENT', // Private, to secure your database & visitors
  
  // Random
  'daandelange.simplestats.panel.enable' => false, // Disable the panel view completely
  'daandelange.simplestats.tracking.enableReferers' => false, // Disable referer tracking

  // Advanced
  'daandelange.simplestats.tracking.timeFrameUtility' => new \daandelange\SimpleStats\SimpleStatsTimeFrameUtilityWeekly(), // Set it as a (custom) class
  'daandelange.simplestats.tracking.method' => \daandelange\SimpleStats\SimpleStatsTrackingMode::OnImage, // Use tracking image
];
````

### All Config Options

Here's a list with options. *(the `daandelange.simplestats` part has been stripped)*  
You might also find some more detailed information in the comments of `options.php`.

| Option                          | Type                                  | Default         | Description                                                                | Comment                                                                           |
|---------------------------------|---------------------------------------|-----------------|----------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| **TRACKING**                    |                                       |                 |                                                                            |                                                                                   |
| `tracking.timeFrameUtility`     | String \| SimpleStatsTimeFrameUtility | `'monthly'`     | Set the tracking resolution class that handles time conversions to periods. **(change with caution!)** | Possible string values: `daily`, `weekly`, `monthly`. |
| `tracking.enableReferers`       | Bool                                  | true            | Enables tracking referrers. Gives an insight of who links to your website. |                                                                                   |
| `tracking.enableDevices`        | Bool                                  | true            | Enables tracking of minimal hardware configurations (device information)   |                                                                                   |
| `tracking.enableVisits`         | Bool                                  | true            | Enables tracking page visits (frequentation)                               |                                                                                   |
| `tracking.enableVisitLanguages` | Bool                                  | true            | Enables a counter per language per page.                                   | Only effective in multi-language Kirby installations and `enableVisits` enabled.  |
| `tracking.ignore.roles`         | Array                                 | `['admin']`     | Ignore any tracking for connected users with these roles.                  |                                                                                   |
| `tracking.ignore.pages`         | Array                                 | `[]`            | Ignore tracking for these page ids.                                        | Make sure to use the full id, not the slug.                                       |
| `tracking.ignore.templates`     | Array                                 | `['error']`     | Ignore tracking for pages with these templates.                            | Checked against `template()` and `intendedTemplate()`                             |
| `tracking.ignore.localhost`     | Array                                 | `false`         | Ignore tracking visits from localhost.                                     | Checked against `template()` and `intendedTemplate()`                             |
| `tracking.ignore.bots`          | Bool                                  | false           | Ignore tracking any bots.                                                  |                                                                                   |
| `tracking.ignore.botVisits`     | Bool                                  | true            | Ignore counting bot page views.                                            |                                                                                   |
| `tracking.ignore.botReferers`   | Bool                                  | true            | Ignore tracking referrers sent by bots.                                    |                                                                                   |
| `tracking.salt`                 | String                                | `'CHANGEME'`    | A unique hash, used to generate a unique user id from visitor data.        | Recommended to change! Ensures that user identifying information is hard to retrieve if your database leaks. |
| `tracking.anonimizeIpBits`      | Number                                | `1`             | Anonymise the IP address of X bits.                                     | Use `0` for no anonymisation, `4` for full anonymisation. (influences unique visits) |
| `tracking.method`               | SimplestatsTrackingMode               | `'onLoad'`      | Tracking mode. See `SimplestatsTrackingMode` for more information. | `onLoad` is the only fully automatic mode, others [need manual attention](#tracking-method). |
| `tracking.imageStyle`           | String                                | See options.php | CSS style for the served tracking image. (to customize)                    |                                                                                   |
| **PANEL**                       |                                       |                 |                                                                            |                                                                                   |
| `panel.dismissDisclaimer`       | Bool                                  | false           | Dismisses the panel disclaimer message.                                    |                                                                                   |
| `panel.enable`                  | Bool                                  | true            | Enable/Disable viewing stats in the panel.                                 |                                                                                   |
| `panel.breadcrumbLabel`         | String                                | `'SimpleStats'` | Breadcrumb shown in the panel.                                             |                                                                                   |
| `panel.hideBots`                | Bool                                  | false           | To hide bot information from the devices tab.                              |                                                                                   |
| `panel.defaultTimeSpan`         | Integer                               | -1              | To set the range (in periods) for the default panel view.                  | Use `-1` for viewing the whole available range. Use `4` for 4 periods (days/weeks/months).  |


****


## Website Integration

While simplestats is now ready to run, you can integrate it further with your website.

### Tracking Method

If you want to use the default `onLoad` tracking method, no further action is required, the plugin automatically hooks to Kirby route events.  
If you choose any other tracking method, you'll need to integrate the method in your code.

> [!NOTE]
> **Possible Tracking Methods:** (Config value: `tracking.method`)
>
>   - #### `SimpleStatsTrackingMode::OnLoad` : Uses kirby's route hooks to track content when it's served.  
>     *Pros*: Ensures that every request is tracked.  
>     *Cons*: Slows down the page serve time.  
>     *Setup steps*: None.
>   - #### `SimpleStatsTrackingMode::OnImage` : Generate a simple image tag within your HTML.  
>     *Pros*: Doesn't slow down page serve time.  
>     *Cons*: You trust the user to load the image.  
>     *Setup steps*: You need to call `$page->simpleStatsImage()` in your template code. You probably want to do this once in `site/snippets/footer.php` for example.
>   - #### `SimpleStatsTrackingMode::Disabled` : Disables tracking, no further action is needed.
>     *Pros*: Tracking is disabled.  
>     *Cons*: Tracking is disabled.  
>     *Setup steps*: None.
>   - #### `SimpleStatsTrackingMode::Manual` : Manually call the tracking function.  
>     *Pros*: Very flexible, might solve edge-case-usage.  
>     *Cons*: See below.  
>     *Setup steps*: You have to call `SimpleStats::track()` manually. Additionally, you need to populate the http headers argument accordingly to track referers and device information.

#### User permissions

The plugin provides permissions for controlling user access.

| Permission                        | Default | Description                                |
|-----------------------------------|---------|--------------------------------------------|
| daandelange.simplestats.access    | true    | User can view stats from the panel.        |
| daandelange.simplestats.configure | false   | User can view & use the "information" tab. |

Example user role configuration :

```yml
# site/blueprints/users/admin.yml
title: Admin

permissions:
  daandelange.simplestats:
    access: true
    configure: true
```


### Panel menu

If you're using a custom panel menu, you can add the simplestats area to it.

````php
<?php 
// File: site/config.config.php
return [
  'panel' => [
    'menu' => [
        // ... your other menu items
        'simplestats',
    ],
  ],
];
````

### Page blueprints

If you wish to display individual page stats in the panel, you may add a `pagestats` section to your page's blueprint.

````yml
sections:
  pagestats:
    type: pagestats
    label: Analytics
    size: small           # affects chart height : tiny, small, medium, large
    showTotals: true      # Show total and average visits
    showTimeline: true    # Show visits over time (graph)
    showLanguages: true   # Show languages (popularity per language)
    showFullInfo: false   # Add extra verbose stats (data time span and # samples)
````

### API

Below are some useful methods exposed by SimpleStats.

#### Singletons

- `SimpleStats::safeTrack($id)`  
  Throw-safe alternative of `track()`. `$id` is a `$page->id()` to be tracked.
- `SimpleStats::track($id)`  
  Function called to track user data. `$id` is a `$page->id()` to be tracked.
- `Stats::*()`  
  Class to help retrieve raw array data from the database file.
- `Stats::syncDayStats()`  
  Syncs the 24H visitor data to the DB. Cron-able.
- `Stats::getVisitors()`  
  Returns the current visitors table data as visible in the panel.
- `Stats::getDbTimeSpan()`  
  Returns the time range covered by the database.
- `Stats::pageStats($from=null, $to=null)`  
  Returns the page visits table data as visible in the panel.
- `Stats::deviceStats($from=null, $to=null)`  
  Returns the devices data as visible in the panel.
- `Stats::refererStats($from=null, $to=null)`  
  Returns the referrer data as visible in the panel.

#### Page Methods

- `$page->simpleStatsImage()`  
  HTML code for the tracking image, when using OnImage tracking method.
- `$page->getPageStats()`  
  Returns an array with useful tracking information about the page.
- `$page->getSimplestatsCount()`  
  Returns the total amount of visits on the page.

#### User Methods

- `$user->hasSimpleStatsPanelAccess($forSpecialAdminAccess=false)`  
  Returns true if the user is authorized to access the SimpleStats panel area, with or without admin rights.


****


## Panel User Interface

The panel interface shows your website analytics data and tracking setup.  
You can access the area from your main menu while it's also accessible under this url : `/panel/simplestats`.  

### Time Span Selector

A range selector allows you to select a timespan for displaying analytics, in the top right or the SimpleStats area.

### Charts

Charts are interactive, you can hover them to have details, and click labels to toggle filtering. You can even download timelines as PNGs.

### Tables

Tables are interactive and paginated. You can search data within and sort them by clicking on the column headers.  
Click on a row to open page-specific stats.


****


## Development

If you wish to build simplestats by yourself or start contributing to its code, here are some instructions.  
*These steps are optional, for building the plugin from source.*

### Environment

Development is usually done on [a standard Kirby PluginKit](https://github.com/getkirby/pluginkit/tree/4-panel) so that you can test it directly.  
If so, simply follow their instructions, eventually make it multilanguage, then [install simplestats to it](#installation).


### Setup & compile

- Go to the simplestats root folder : `cd site/plugins/simplestats`
- First time, install dependencies  : `pnpm install`
- While developing                  : `pnpm run dev`
- Compile a production build        : `pnpm run build`
- Update dependencies               : `pnpm update`

Note that we use `pnpm` but it also works with `npm`.
See [Kirby's plugin guide](https://getkirby.com/docs/guide/plugins/plugin-setup-basic) for more details on developping plugins.

### Current state

SimpleStats is now mature after 6 years of making in my spare time, during which it has been tested and deployed on various websites, proving its robustness.  
Thanks to @bogdancondorachi, the panel components are now using more native Fiber UI components, reducing the footprint and hopefully facilitating maintenance regarding Kirby updates.
_Time of writing:August 2026._

### Contributing

I guess a lot of functionality could be added to suit the plugin for a wider variety of website setups.  
The panel interface could also be improved and translated. The documentation could be clarified too.

Any contributions (discussions, reports, feedback, translations) are welcome using pull requests or issues, as long as the collected stats stay minimal and reasonably non-intrusive.  
You may also have a look at the [open issues](https://github.com/Daandelange/kirby-simplestats/issues/) for contribution ideas.
