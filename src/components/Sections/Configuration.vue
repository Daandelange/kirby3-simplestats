<template>
  <div class="configuration">
    <k-headline size="large">{{ $t('simplestats.info.config.title') }}</k-headline>

    <k-headline class="rightColumnAlign">{{ $t('simplestats.info.config.tracking') }}</k-headline>
    <k-text-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.tracking.periodname')" :value="trackingPeriodName" icon="clock"/>
    <k-number-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.tracking.periodsecs')" :value="uniqueSeconds" :after="$t('simplestats.charts.seconds')" icon="clock"/>
    <k-toggle-field name="" :disabled="true" :label="$t('simplestats.info.config.tracking.salted')" :value="saltIsSet" icon="key" />
    <k-tags-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.tracking.features')" :value="trackingFeatures" icon="globe" />
    <k-tags-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.tracking.ignore.roles')" :value="ignoredRoles" icon="users" />
    <k-tags-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.tracking.ignore.ids')" :value="ignoredPages" icon="page" />
    <k-tags-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.tracking.ignore.templates')" :value="ignoredTemplates" icon="page" />
    <br/>
    <k-headline class="rightColumnAlign">{{ $t('simplestats.info.config.log.title') }}</k-headline>
    <k-text-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.log.file')" :value="logFile" icon="file-code" />
    <k-tags-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.config.log.level')" :value="logLevels" icon="bug"  />
    <br/>
    <k-info-field v-if="!isLoading && !saltIsSet" :label="$t('simplestats.info.config.tracking.unsalted')" theme="negative" :text="$t('simplestats.info.config.tracking.unsaltedwarn')" />
  </div>
</template>

<script>

export default {
  extends: 'k-pages-section',
  data() {
    return {
      isLoading: true,
      error: "",
      saltIsSet : false,
      trackingPeriodName : '',
      trackingSince : '',
      uniqueSeconds : '',
      enableReferers : false,
      enableDevices : false,
      enableVisits : false,
      enableVisitLanguages : false,
      ignoredRoles : [],
      ignoredPages : [],
      ignoredTemplates : [],
      logFile : '',
      logLevels : [],
    }
  },
  created() {
    this.load();
  },
  computed: {
    trackingFeatures() {
      var features = [];
      if(this.enableReferers)       features.push( this.$t('simplestats.info.config.tracking.referrers', 'Referers') );
      if(this.enableDevices)        features.push( this.$t('simplestats.info.config.tracking.devices',   'Devices') );
      if(this.enableVisits)         features.push( this.$t('simplestats.info.config.tracking.visits',    'Page Visits') );
      if(this.enableVisitLanguages) features.push( this.$t('simplestats.info.config.tracking.languages', 'Page Visitors') );
      return features;
    },
  },
  methods: {
    load() {

      // Load configuration
      this.$api
        .get("simplestats/configinfo")
        .then(response => {
          this.isLoading = false
          this.saltIsSet = response.saltIsSet
          this.trackingPeriodName = response.trackingPeriodName
          this.trackingSince = response.trackingSince
          this.uniqueSeconds = response.uniqueSeconds
          this.enableReferers = response.enableReferers
          this.enableDevices = response.enableDevices
          this.enableVisits = response.enableVisits
          this.enableVisitLanguages = response.enableVisitLanguages
          this.ignoredRoles = response.ignoredRoles
          this.ignoredPages = response.ignoredPages
          this.logFile = response.logFile
          this.logLevels = response.logLevels
          this.ignoredTemplates = response.ignoredTemplates
        })
        .catch(error => {
          this.isLoading = false
          this.error = error.message
          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        });
    },
  }
};
</script>

<style lang="scss">
.configuration {
  .k-field:not(.k-info-field) {
    display: flex;

    .k-field-label {
      font-weight: normal;
      font-size: .9em;
      line-height: 1em;
      align-items: flex-end;
      border-bottom: 1px rgba(0,0,0,.1) dashed;
    }
    .k-field-header {
      //display: inline-block;
      width: 40%;
      float: left;
      align-items: flex-end;
    }
    .k-input {
      //display: inline-block;
      width: 60%;
      float: left;

      &[data-disabled] {
        background-color: white;
      }
    }
    &[data-disabled] {
      cursor: inherit;
    }
  }
  .rightColumnAlign {
    margin-left: 40%;
    display: block;
  }
}
</style>
