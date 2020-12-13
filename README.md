# SimpleStats

Track pageviews, referrers and devices on your Kirby 3 website.
This plugin provides simple solution for self-hosting **minimal** and **non-instrusive** visitor analytics.

It embeds a php tracking solution that creates and populates a single database file.
A panel view lets authenticated users visualise them.

- A crypted user-unique fingerprint is stored in order to track unique page views.
- After 24H, it's processed and dissociated from any user identifiying data.
- All data is summed up on a monthly basis. *There is no weekly or dayly precision.*


### Current state
This is very alpha, and has not been tested. It is an initial codebase to be improved.
Discussions and contributions are welcome, as long as the stats stay minimal.

****


## Installation

### Download

Download and copy this repository to `/site/plugins/simplestats`.

### Git submodule

```
git submodule add https://github.com/daandelange/kirby3-simplestats.git site/plugins/simplestats
```


## Configuration
*Soon...*
For now, check the comments in `options.php`, not recommended to change the default behaviour.

## Options

*Soon...*

## Development

Development was started from [a standard Kirby PluginKit](https://github.com/getkirby/pluginkit/tree/4-panel), see [their plugin guide](https://getkirby.com/docs/guide/plugins/plugin-setup-basic) for more details.

Npm requirements  : `npm install -g parcel-bundler`
Setup             : `cd /path/to/website/site/plugins/simplestats && npm install`
While developping : `npm run dev`
Publish changes   : `npm run build`


****


## License

MIT - Free to use, free to improve.

- Copyright 2020 [Daan de Lange](https://github.com/daandelange)
