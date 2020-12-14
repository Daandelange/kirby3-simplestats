<template>
  <div>

      <k-grid gutter="medium">
        <k-column width="1/3">
          <k-headline>Referrers by medium</k-headline>
          <!--<pie :dataset="refererMediumData" :labels="refererMediumLabels" />-->
          <pie-chart v-if="refererMediumData.length > 0"
            :data="refererMediumData"
          />
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>

        <k-column width="1/3">
          <k-headline>Referrers by domain</k-headline>
          <pie-chart v-if="refererDomainsData.length > 0"
            :data="refererDomainsData"
          />
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>

        <k-column width="1/3">
          <k-headline>Referrers by domain (this month)</k-headline>
          <pie-chart v-if="refererRecentDomainsData.length > 0" :data="refererRecentDomainsData" />
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>
      </k-grid>

      <k-grid gutter="medium">
        <k-column width="1/1">
          <br><br>
          <k-headline>Referrers by medium over time</k-headline>
          <area-chart
            :data="referersByMediumOverTimeData"
            :download="true"
            download="Site_ReferersEvolution.png"
            label="Referers"
            xtitle="Time"
            ytitle="Hits per medium"
            :stacked="true"
            v-if="referersByMediumOverTimeData.length > 0"
            :library="timeOptions"
          ></area-chart>
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>
      </k-grid>

      <k-grid gutter="medium">
        <k-column width="1/1">
          <br><br>
          <k-headline>All referrers</k-headline>
          <tbl
            :rows="refererFullData"
            :columns="refererFullLabels"
            :store="false"
            :search="true"
            :sort="true"
            :pagination="true"
            hheadline="All referers"
            v-if="refererFullData.length > 0"
          >
            <!-- Default entryslot -->
            <template slot="column-$default" slot-scope="props">
              <p>{{ props.value }}</p>
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

import Vue from 'vue'
import Chartkick from 'vue-chartkick'
import Chart from 'chart.js'

import Tbl from 'tbl-for-kirby';

export default {
  components: {
    Tbl,
  },

  data() {
    return {
      refererDomainsData: [],
      //refererDomainsLabels: [],

      refererRecentDomainsData: [],
      //refererRecentDomainsLabels: [],

      refererMediumData: [],
      //refererMediumLabels: [],

      refererFullData: [],
      refererFullLabels: [],

      referersByMediumOverTimeData: [],

      isLoading: false,
      error: "",

      timeOptions : {
        layout: {
          padding: {left: 5, right: 15, top: 5, bottom: 10}
        },
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
      }
    }
  },

  created() {
    this.load();
  },

  methods: {
    load(reload) {
      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/refererstats")
        .then(response => {
          this.isLoading = false

          this.refererDomainsData   = response.referersbydomaindata
          //this.refererDomainsLabels = response.referersbydomainlabels

          this.refererMediumData   = response.referersbymediumdata
          //this.refererMediumLabels = response.referersbymediumlabels

          this.refererRecentDomainsData   = response.referersbydomainrecentdata
          //this.refererRecentDomainsLabels = response.referersbydomainrecentlabels

          this.refererFullData   = response.allreferersrows
          this.refererFullLabels = response.allrefererscolumns

          this.referersByMediumOverTimeData = response.referersbymediumovertimedata;
          //console.log( this.referersByMediumOverTimeData);
/*
          Object.keys(this.referersByMediumOverTimeData).forEach(key => {
            //console.log(key, this.referersByMediumOverTimeData[key]);
            Object.keys(this.referersByMediumOverTimeData[key]['data']).forEach(k => {
              //console.log(k, this.referersByMediumOverTimeData[key]['data'][k]);
              var d = new Date(1000*k);
              console.log(key, this.referersByMediumOverTimeData[key]['name'],d,k);
              this.referersByMediumOverTimeData[key]['data'][d]=parseInt(this.referersByMediumOverTimeData[key]['data'][k]);
              //this.referersByMediumOverTimeData[key]['data'][k]=parseInt(this.referersByMediumOverTimeData[key]['data'][k]);
              delete this.referersByMediumOverTimeData[key]['data'][k];
            });
          });
*/

          //console.log(response.referersbymediumovertimedata);

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

          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
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
