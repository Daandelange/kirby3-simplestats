<template>
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
        <k-headline size="medium" align="center">Disclaimer - Collecting Data</k-headline>
        <k-text size="small" align="center">
          You are actively collecting stats : you have to know what you are doing, and be responsible.<br>
          You should know your legal obligations regarding personal data storage of your visitors, and their rights.<br>
          SimpleStats is free to use, and [according to its license] it doesn't come with any guarantees or legal advice whatsoever.<br>
          By using SimpleStats, you agree to .
          <span class="hover-to-help">
            <k-icon type="question" />
            <div class="help"><k-text theme="help" size="small" align="center">To dismiss this message : refer to the readme.</k-text></div>
          </span>
          <br>
        </k-text>

      </k-column>
    </k-grid>

    <k-tabs
      ref="tabs-ref"
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
      <information />

      <visitors />
    </div>

    <div v-else>
      <k-empty>Something is wrong with tab handling...</k-empty>
    </div>

  </k-view>
</template>

<script>


import Visitors from "./Sections/Visitors.vue";
import PageStats from "./Sections/PageStats.vue";
import Devices from "./Sections/Devices.vue";
import Referers from "./Sections/Referers.vue";
import Information from "./Sections/Information.vue";

export default {
  components: {
    Visitors,
    PageStats,
    Devices,
    Referers,
    Information,
  },
  data() {
    return {
      // Set initial tab and load it
      tab: this.$route.hash.replace('#', '')??'simplestats-tabs-visitedpages',
      tabs: [
        {name:'simplestats-tabs-visitedpages', label:'Page visits', icon:'layers', columns: []},
        {name:'simplestats-tabs-visitordevices', label:'Visitor Devices', icon:'users', columns: []},
        {name:'simplestats-tabs-referers', label:'Referers', icon:'chart', columns: []},
        {name:'simplestats-tabs-info', label:'Information', icon:'map', columns: []},
      ],
      dismissDisclaimer : false,
      isLoading : true,
    };
  },
  watch: {
/*
    tab(incoming){
      console.warn(incoming);
    },
*/
    '$route'() {
      //console.log('$route', this.tab);
      this.tab = this.$route.hash.replace('#', '');
    },
  },
  computed: {
    tabsKey() {
      //return "simplestats-tabs-"+this.tab;
      //console.log('tabsKey()', this.tab);
      if(this.tab=='') this.tab = 'simplestats-tabs-visitedpages';
      return this.tab;
    },
  },
  created() {
    this.load();
  },

  methods: {
    load(reload) {
      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/mainview")
        .then(response => {
          this.isLoading = false
          this.dismissDisclaimer   = response.dismissDisclaimer
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
      //console.log('onTab()', this.tab)
      this.tab = tab.name;
    },
  },
};
</script>

<style lang="scss">
.row-percent {
  //background-color: #bbb;
}
.row-percent p {
  background-color: rgba(46, 64, 87,1);
  color: white;
  display: inline-block;
  height: 4px;
  position: relative;
}
.row-percent p {
  background-color: rgba(46, 64, 87,.8);
  color: white;
  //display: inline-block;
  height: 20%;
  position: relative;
  width: 0%; /* Default for unvalid values */
  padding: 0em 0!important; /* overrides tbl default styling */
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
</style>
