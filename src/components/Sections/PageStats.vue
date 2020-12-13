<template>
  <div>
    <br />

    <k-grid style="margin: 0 1em;">
      <k-column>
        <k-headline size="medium">Visits over time</k-headline>
        <br/>
<!--      <histogram :dataset="[visitsOverTimeData]" :labels="visitsOverTimeLabels" /> -->
        <area-chart
          :data="visitsOverTimeData"
          :download="true"
          download="Site_PageVisits.png"
          :diiiiscrete="true"
          label="Unique visits"
          xtitle="Time"
          ytitle="Visits"
          height="150px"
        ></area-chart>
      </k-column>


      <k-column>
        <k-headline size="medium">Page visits over time</k-headline>
        <br/>
        <column-chart
          :data="pageVisitsOverTimeData"
          :download="true"
          download="Site_PageVisits.png"
          :diiiscrete="true"
          label="Unique visits"
          xtitle="Time"
          ytitle="Visits"
          heeight="300px"
          :stacked="true"
          :library="chartOptions"
          v-if="pageVisitsOverTimeData.length > 0"
        ></column-chart>
        <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
      </k-column>

      <k-column>
        <tbl
          :rows="rows"
          :columns="columns"
          :store="false"
          :search="true"
          :sort="true"
          :pagination="{}"
          :isLoading="isLoading"
          :options="{add:false,reset:false}"
          headline="Visited pages"
          v-if="rows.length > 0"
        >
          <!-- Default entryslot -->
          <template slot="column-$default" slot-scope="props">
            <p>
              {{ props.value }}
            </p>
          </template>
          <!-- percentage entryslot -->
            <template slot="column-hitspercent" slot-scope="props">
              <p v-bind:style="{ width: props.value + '%' }"></p>
            </template>
        </tbl>
        <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
      </k-column>

    </k-grid>
  </div>
</template>

<script>
// import Vue from 'vue'

import Tbl from 'tbl-for-kirby';

export default {
  extends: 'k-pages-section',
  data() {
    return {
      rows: [],
      columns: [],

      visitsOverTimeData:   [],
      pageVisitsOverTimeData:   [],

      chartOptions: {
        scales: {
          xAxes: [{
            //display: false,
            type: 'time',
            time: {
              unit: 'month',
              displayFormats: {
                  month: 'MMM YYYY'
              }
            }
          }],
          yAxes: [{
            stacked: true
          }]
        }
      },

      isLoading: false,
      error: "",
    }
  },
  components: {
    Tbl,
    //Chartkick.use(Chart)
  },
  use() {
    //Chartkick.use(Chart),
    //Chartkick
  },
  // see: https://forum.getkirby.com/t/can-i-use-vue-use-inside-my-plugin/17822
  // Also see https://github.com/getkirby/ideas/issues/219
  beforeCreate() {
  	// Instead of: Vue.use(PluginName)
  	//window.panel.app.$root.constructor.use(Chartkick.use(Chart), {});
  	//app.$root.constructor.use(Chartkick.use(Chart), {});
  },
  created() {
    this.load();
  },
  mounted() {
    //debugger;

  },
  methods: {
    load(reload) {
      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/pagestats")
        .then(response => {
          this.isLoading = false
          this.columns = response.pagestatslabels
          this.rows    = response.pagestatsdata

          this.visitsOverTimeData   = response.visitsovertimedata
          //this.visitsOverTimeLabels = response.visitsovertimelabels

          this.pageVisitsOverTimeData = response.pagevisitsovertimedata

          //console.warn(response);
          //console.log(response.data.rows);
          // replace default translations if needed
//           let translations = response.translations
//           Object.keys(translations).forEach(k => {
//               if (translations[k] == null) delete translations[k]
//           })
//           this.translations = Object.assign({}, this.translations, translations)
        })
        .catch(error => {
          this.isLoading = false
          this.error = error.message
        })
    },
  }
};
</script>

<style lang="scss">
.emptyChart {
  min-height: 250px;
  margin-top: 1em;
  padding: 1em;
}
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
</style>
