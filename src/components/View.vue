<template>
  <k-view class="k-simplestats-view">
    <p><br/></p>
<!--
    <k-header>
      Simple Stats
    </k-header>
-->
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
  methods: {
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
</style>
