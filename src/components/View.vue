<template>
  <k-inside>
  <k-view class="k-simplestats-view">
    <p><br/></p>
<!--
    <k-header>
      Simple Stats
    </k-header>
-->

    <k-grid v-if="!isLoading && !dismissDisclaimer">
      <k-column>
        <!-- DISCLAIMER -->
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

    <k-tabs
      ref="tabsref"
      :tabs="tabs"
      :tab="tab"
      :key="tabsKey"
      @tab="onTab"
    />
    <p><br/></p>

    <div v-if="tab == 'simplestats-tabs-visitedpages'">
      <page-stats />
    </div>

    <div v-else-if="tab == 'simplestats-tabs-visitordevices'">
      <devices />
    </div>

    <div v-else-if="tab == 'simplestats-tabs-referers'">
      <referers />
    </div>

    <div v-else-if="tab == 'simplestats-tabs-info'">
      <k-grid gutter="large">
        <!-- CONFIGURATION -->
        <k-column width="1/2">
          <configuration />
          <k-line-field/>
          <tracking-tester />
        </k-column>

        <k-column width="1/2">
          <!-- DB INFORMATION -->
          <DbInformation />
        </k-column>

        <k-column>
          <visitors />
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


//import 'vue-good-table/dist/vue-good-table.css';

export default {
  components: {
    Visitors,
    PageStats,
    Devices,
    Referers,
    DbInformation,
    Configuration,
    TrackingTester
  },
  data() {
    return {
      // Set initial tab and load it
      tab: this.tabsKey,
      tabs: [
        // Note: columns are needed for the panel not to throw an error...
        { name:'simplestats-tabs-visitedpages',   label:'Page visits',      icon:'layers',  columns: [], 'link':'simplestats?tab=simplestats-tabs-visitedpages'},
        { name:'simplestats-tabs-visitordevices', label:'Visitor Devices',  icon:'users',   columns: [] , 'link':'simplestats?tab=simplestats-tabs-visitordevices' },
        { name:'simplestats-tabs-referers',       label:'Referers',         icon:'chart',   columns: [], 'link':'simplestats?tab=simplestats-tabs-referers'},
        { name:'simplestats-tabs-info',           label:'Information',      icon:'map',     columns: [], 'link':'simplestats?tab=simplestats-tabs-info'},
      ],
      dismissDisclaimer : false,
      isLoading : true,
    };
  },
  watch: {
    tab(incoming){
      //console.warn('watch.tab =',incoming);
      // changes tab value when dynamically loading page
      this.tab = incoming;
    },
    // Not used anymore, maybe needed for Kirby installations before 3.6 ?
//     '$route'() {
//       console.log('$route', this.tab, this.$store.state.system.info.version);
//       this.tab = this.$route.hash.replace('#', '');
//     },
  },
  computed: {
    tabsKey() {
      // K3.5 (has version in $store globally)
      if(this.$store.state && this.$store.state.system && this.$store.state.system.info && this.$store.state.system.info.version && parseInt(this.$store.state.system.info.version.at(0) + this.$store.state.system.info.version.at(2)) < 36 ){
        this.tab = this.$route.hash.replace('#', '');
      }
      // K3.6 (needs API query = system.info.version, or assume its 3.6+)
      else {
        const urlParams = new URLSearchParams(window.location.search);
        const myParam = urlParams.get('tab');
        if(myParam) this.tab=myParam;
      }
      if(!this.tab || this.tab=='') this.tab = 'simplestats-tabs-visitedpages';
      return this.tab;
    },
  },
  created() {
    this.load();
    //console.log('created()', this.tab, this.tabsKey  );
  },

  methods: {
    load(reload) {
      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/mainview")
        .then(response => {
          this.isLoading = false
          this.dismissDisclaimer   = response.dismissDisclaimer
          //this.translations = response.translations

          this.tabs[0].label = response.translations.tabs.pagevisits;
          this.tabs[1].label = response.translations.tabs.visitordevices;
          this.tabs[2].label = response.translations.tabs.referers;
          this.tabs[3].label = response.translations.tabs.information;
        })
        .catch(error => {
          this.isLoading = false
          this.error = error.message

          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        })
    },

    // Bind tab changing
    onTab(tab) {
      //console.log('onTab()', this.tab);
      this.tab = tab.name;
    },
  },
};
</script>

<style lang="less">

/* @import 'vue-good-table/dist/vue-good-table.css'; //*/
.k-simplestats-view {
  /* Scoped import not to break k3-pagetable. See https://github.com/Daandelange/kirby3-simplestats/issues/20 */
  @import (less) 'vue-good-table/dist/vue-good-table.css';

.row-percent {
  //background-color: #bbb;
}
.row-percent p, .row-percent span.visualiser {
  float: left;
  background-color: rgba(46, 64, 87,.4);// .8);
  color: white;
  display: inline-block;
  height: 1em;
  //position: relative;
  width: 0%; /* Default for unvalid values */
  //padding: 0em 0!important; /* overrides tbl default styling */
}

.row-percent span.number {
  display: inline-block;
  //float: right;
  //position: relative;
  margin-left: -100%;
}

// Custom vue-good-table syles
.vgt-global-search {
  width: 50%;
  float: right;
  //margin-top: -100%;
  padding: 5px 0 5px 5px;
  background-color: transparent;
  background-image: none;
}
.vgt-inner-wrap {
  margin-top: -2em; // To place search box next-to headline. Will break layout without search box.
  box-shadow: none;
}
table.vgt-table.nosearch {
  margin-top: 2.5em; // to cancel the nagative margin for search box positioning
}
.vgt-input[type=text]{
  background-color: transparent;
  color: black;

  &:active, &:focus {
    background-color: white;
  }
}
.vgt-wrap__footer {
  border: none;
  background: transparent;
  font-size: 0.975rem;

  .footer__navigation {
    font-size: 0.975rem;
  }

  .footer__navigation__page-btn {
    font-weight: normal;
    span {
      font-size: 0.975rem;
    }
  }
}

table.vgt-table {
  background: transparent;
  font-size: .875rem;
  color: black;

  thead th {
    background: #d6d6d6;
    color: #666;

    & {
      font-weight: normal;
    }
  }

  tbody {
    background-color: white;
  }

  td {
    color: black;
    word-break: break-word;
  }

  .k-icon {
    display: inline-block;
    margin-right: .5rem;
    vertical-align: middle;
  }
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
