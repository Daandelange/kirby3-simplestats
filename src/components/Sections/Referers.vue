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
          <br><br><br>
          <k-headline>
            All referrers
            <!-- {{ $t('simplestats.visitedpages') }} -->
          </k-headline>
          <div v-if="refererFullData.length > 0">
            <vue-good-table
              :rows="refererFullData"
              :columns="refererFullLabels"
              styleClass="vgt-table condensed"
              max-height="500px"
              :fixed-header="false"
              compactMode
              :search-options="{enabled: true, placeholder: 'Filter items...'}"
              :pagination-options="{
                enabled: true,
                perPage: 20,
                perPageDropdownEnabled: false,
              }"
            >
              <div slot="emptystate">
                <k-empty>
                  There is nothing to show...
                </k-empty>
              </div>

              <template slot="table-row" slot-scope="props">
                <span v-if="props.column.field == 'hitspercent'" class="row-percent">
                  <span class="visualiser" :style="{ width: props.row.hitspercent *100 + '%'}"></span>
                  <span class="number">{{ (props.row.hitspercent * 100).toFixed(0) + '%' }}</span>
                </span>
                <span v-else-if="props.column.field == 'timefrom'">
                  <span>
                    {{ props.formattedRow[props.column.field] }}
  <!-- (old way)                 {{ new Date( props.row.firstvisited ).toLocaleString( userLocale, { month: "short" }) }} {{ new Date( props.row.firstvisited ).getFullYear() }} -->
                  </span>
                </span>
                <span v-else>
                  {{ props.formattedRow[props.column.field] }}
                </span>
              </template>
            </vue-good-table>
          </div>
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>
      </k-grid>

  </div>
</template>

<script>

//import Vue from 'vue';
//import Chartkick from 'vue-chartkick';
//import Chart from 'chart.js';

import { VueGoodTable } from 'vue-good-table';

export default {
  components: {
    VueGoodTable,
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
      userLocale: 'en',

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

          this.userLocale = window.panel.$language ? window.panel.$language.code : "";//this.$store.state.i18n.locale;
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
</style>
