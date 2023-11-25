<template>
  <k-inside>
  <k-view class="k-simplestats-view">
    <!-- DISCLAIMER -->
    <k-grid v-if="!isLoading && !dismissDisclaimer">
      <k-column>
        <k-headline size="medium" align="center">{{ $t('simplestats.disclaimer.title') }}</k-headline>
        <k-text size="small" align="center">
          <span v-html="$t('simplestats.disclaimer.text')"></span>
          <span class="hover-to-help">
            <k-icon type="question" />
            <div class="help"><k-text theme="help" size="small" align="center">{{ $t('simplestats.disclaimer.dismiss') }}</k-text></div>
          </span>
          <br>
        </k-text>
        <br/><br/>
      </k-column>
    </k-grid>

    <k-header :tabs="tabs" :tab="tab" @tabChange="onTab">
      <template slot="default">
        {{ label }}
      </template>
      <!-- <template slot="left">
        Left
      </template> -->
      <template slot="right">
          <time-frame-input :dateChoices="timeframes" ref="timeFrame" />
      </template>
    </k-header>

    <div v-if="tab == tabs[0].name">
      <page-stats :dateFrom="$refs.timeFrame.dateFrom" :dateTo="$refs.timeFrame.dateTo" section-name="pagestats" />
    </div>

    <div v-else-if="tab == tabs[1].name">
      <devices :dateFrom="$refs.timeFrame.dateFrom" :dateTo="$refs.timeFrame.dateTo" section-name="devicestats" />
    </div>

    <div v-else-if="tab == tabs[2].name">
      <referers :dateFrom="$refs.timeFrame.dateFrom" :dateTo="$refs.timeFrame.dateTo" section-name="refererstats" />
    </div>

    <div v-else-if="tab == tabs[3].name">
      <k-grid gutter="large">
        <!-- CONFIGURATION -->
        <k-column width="1/2">
          <configuration section-name="configinfo" />
        </k-column>

        <!-- DB INFORMATION -->
        <k-column width="1/2">
          <DbInformation section-name="listdbinfo" />
        </k-column>
          
        <!-- TRACKING TESTER -->
        <k-column width="1/1">
          <k-line-field />
          <tracking-tester />
        </k-column>

        <!-- VISITORS TABLE -->
        <k-column width="1/1">
          <k-line-field />
          <visitors section-name="listvisitors" />
        </k-column>
      </k-grid>
    </div>

    <div v-else>
      <k-empty>{{ $t('simplestats.taberror') }}</k-empty>
    </div>

  </k-view>
  </k-inside>
</template>



<script>

import Visitors from "./Sections/Visitors.vue";
import PageStats from "./Sections/PageStats.vue";
import Devices from "./Sections/Devices.vue";
import Referers from "./Sections/Referers.vue";
import DbInformation from "./Sections/DbInformation.vue";
import Configuration from "./Sections/Configuration.vue";
import TrackingTester from "./Sections/TrackingTester.vue";
import TimeFrameInput from "./Ui/TimeFrameInput.vue";

export default {
  components: {
    Visitors,
    PageStats,
    Devices,
    Referers,
    DbInformation,
    Configuration,
    TrackingTester,
    TimeFrameInput,
  },
  data() {
    return {
      // Set initial tab and load it
      tab: '',
      dismissDisclaimer : false,
      isLoading : true,
    };
  },
  props: {
    label: {
      type: String,
      default: "Simple Stats",
    },
    initialtab: {
      type: String,
      default: "pagevisits",
    },
    tabs: {
      type: Array,
      default: [],
    },
    globaltimespan: {
      type: Array,
      default: [],
    },
    timeframes: {
      type: Array,
      default: [],
    },
  },
  watch: {
    initialtab(newValue){
      //console.log('initialTab.watch', newValue);
      if(newValue) this.tab = newValue;
    }
  },
  // computed: {

  // },
  // created() {

  // },
  mounted(){
    this.onTab();
  },
  methods: {
    getTabFromLocalStorage(){
      try {
        return window.localStorage.getItem('ss-tabs-menu');
      } catch (error) {
        // ignore
      }
      return null;
    },
    writeTabToLocalStorage(){
      // Don't store crap
      if(!this.tab || this.length<1) return false;
      try {
        window.localStorage.setItem('ss-tabs-menu', this.tab);
        return true;
      } catch (error) {
        // ignore
      }
      return false;
    },

    // Bind tab changing
    onTab(tab) {
      let mTab = tab;
      // Set initial tab position
      if( !tab || tab.length<1 ){
        // set default
        mTab = this.getTabFromLocalStorage();
      }
      if(!mTab || mTab.length<1 && this.initialtab ) mTab = this.initialtab;

      // Sanitize
      if(!this.tabs.some(aTab=>aTab.name===mTab)){
        mTab = this.tabs[0].name;
      }

      // Grab tab key and set tab acordingly
      // This is hacky and subject to break !
      this.tab = mTab;
      this.$root.$view.breadcrumb[0].label = (this.tabs.find((t)=>t.name===tab)?.label) ?? mTab;
      this.$root.$view.breadcrumb[0].link = null;
      this.writeTabToLocalStorage();
    },
  },
};
</script>

<style lang="less">

.k-simplestats-view {

// Todo: Are these styles still used ?
.row-percent p, .row-percent span.visualiser {
  float: left;
  background-color: rgba(46, 64, 87,.4);// .8);
  color: white;
  display: inline-block;
  height: 1em;
  width: 0%; /* Default for unvalid values */
}

.row-percent span.number {
  display: inline-block;
  margin-left: -100%;
}

.hover-to-help {
  position: relative;

  .k-icon {
    display: inline;
  }

  .help {
    display: inline-block;
    visibility: hidden;
    z-index: 1;
    position: relative;
    left: 10px;
    top: 0;
    overflow: visible;
    width: 0;
    height: 0;

    .k-text {
      width: 350px;
      background-color: #efefef;
      border: 1px solid black;
      padding: 8px 10px;
      color: black;
    }
  }
  &:hover .help {
    //display: inline-block;
    visibility: visible;
  }
}
}
</style>
