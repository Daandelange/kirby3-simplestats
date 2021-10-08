<template>
  <div class="configuration">
    <k-headline size="large">Configuration</k-headline>

    <k-headline class="rightColumnAlign">Tracking</k-headline>
    <k-text-field name="" :counter="false" :disabled="true" label="Tracking period" :value="trackingPeriodName" icon="clock"/>
    <k-text-field name="" :counter="false" :disabled="true" label="Track unique users every" :value="uniqueSeconds" after="seconds" icon="clock"/>
    <k-toggle-field name="" :disabled="true" label="Using custom salt" :value="saltIsSet" icon="key" />
    <k-tags-field name="" :counter="false" :disabled="true" label="Tracking Features Enabled" :value="trackingFeatures" icon="globe" />
    <!--<k-toggle-field name="" :disabled="true" label="Track referrers" :value="enableReferers" icon="chart" />
    <k-toggle-field name="" :disabled="true" label="Track devices" :value="enableDevices" icon="chart" />
    <k-toggle-field name="" :disabled="true" label="Track page visits" :value="enableVisits" icon="chart" />
    <k-toggle-field name="" :disabled="true" label="Track languages" :value="enableVisitLanguages" icon="globe" />-->
    <k-tags-field name="" :counter="false" :disabled="true" label="Ignored user roles (not tracked)" :value="ignoredRoles" icon="users" />
    <k-tags-field name="" :counter="false" :disabled="true" label="Ignored page IDs (not tracked)" :value="ignoredPages" icon="page" />
    <k-tags-field name="" :counter="false" :disabled="true" label="Ignored page templatess (not tracked)" :value="ignoredTemplates" icon="page" />
    <br/>
    <k-headline class="rightColumnAlign">Logging</k-headline>
    <k-text-field name="" :counter="false" :disabled="true" label="Log File" :value="logFile" icon="file-code" />
    <k-tags-field name="" :counter="false" :disabled="true" label="Log Level" :value="logLevels" icon="bug"  />
    <br/>
    <k-info-field v-if="!isLoading && !saltIsSet" label="UNSALTED!" theme="negative" text="You are using SimpleStats unsalted, which is less secure. You can set it in your config.php." />
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
    trackingFeatures(){
      var features = [];
      if(this.enableReferers) features.push('Referrers');
      if(this.enableDevices) features.push('Devices');
      if(this.enableVisits) features.push('Page Visits');
      if(this.enableVisitLanguages) features.push('Language Visits');
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
