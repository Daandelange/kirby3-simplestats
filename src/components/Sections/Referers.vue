<template>
  <k-grid gutter="medium">
    <k-column width="1/2">
      <area-chart v-if="refererMediumData.length > 0"
        type="Pie"
        :chart-data="refererMediumData"
        :chart-labels="refererMediumLabels"
        download="Site_ReferrersByMedium.png"
        :label="$t('simplestats.referers.referersbymedium', 'Referers by medium')"
        :fill="true"
        :auto-colorize="true"
        :height="200"
      />
    </k-column>

    <k-column width="1/2">
      <area-chart
        type="Pie"
        :chart-data="refererDomainsData"
        :chart-labels="refererDomainsLabels"
        download="Site_ReferrersByDomain.png"
        :label="$t('simplestats.referers.referersbydomain', 'Referers by domain')"
        :fill="true"
        :auto-colorize="true"
        :height="200"
      />
    </k-column>

    <!-- <k-column width="1/3">
      <k-headline>Referrers by domain (this month)</k-headline>
      <area-chart
        type="Pie"
        :chart-data="refererRecentDomainsData"
      />
    </k-column> -->

    <k-column width="1/1">
      <area-chart
        type="Line"
        :chart-data="referersByMediumOverTimeData"
        :chart-labels="chartPeriodLabels"
        download="Site_ReferersEvolution.png"
        :label="$t('simplestats.referers.referersovertime')"
        :y-title="$t('simplestats.charts.hitspermedium', 'Hits per medium')"
        :stacked="true"
        :fill="true"
        :auto-colorize="true"
        :x-time-axis="true"
        :y-visits-axis="true"
        :height="300"
      ></area-chart>
    </k-column>

    <k-column width="1/1">
      <k-line-field />
    </k-column>

    <k-column width="1/1">
      <searchable-table
        :rows="refererTableData"
        :columns="refererTableLabels"
        :label="$t('simplestats.referers.allreferers')"
      />
    </k-column>
  </k-grid>
</template>


<script>

import AreaChart from '../Ui/AreaChart.vue';
import SearchableTable from '../Ui/SearchableTable.vue';
import SectionBase from '../Sections/SimpleStatsSectionBase.vue';

export default {
  extends: SectionBase,
  components: {
    AreaChart,
    SearchableTable,
  },
  props: {
    dateFrom : {
      type: String,
      required: true,
    },
    dateTo : {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      //...SectionBase.data(),

      chartPeriodLabels: [],

      refererDomainsData: [],
      refererDomainsLabels: [],

      // refererRecentDomainsData: [],
      //refererRecentDomainsLabels: [],

      refererMediumData: [],
      refererMediumLabels: [],

      refererTableData: [],
      refererTableLabels: {},

      referersByMediumOverTimeData: [],

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
                  month: 'MMM YYYY',
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

  methods: {
    loadData(response) {

      this.chartPeriodLabels = response.chartperiodlabels;

      this.refererDomainsData   = response.referersbydomaindata
      this.refererDomainsLabels = response.referersbydomainlabels

      this.refererMediumData   = response.referersbymediumdata
      this.refererMediumLabels = response.referersbymediumlabels

      // this.refererRecentDomainsData   = response.referersbydomainrecentdata
      //this.refererRecentDomainsLabels = response.referersbydomainrecentlabels

      this.refererTableData   = response.refererstabledata
      this.refererTableLabels = response.refererstablelabels

      this.referersByMediumOverTimeData = response.referersbymediumovertimedata;
      //console.log( this.referersByMediumOverTimeData);

      this.userLocale = window.panel.$language ? window.panel.$language.code : "";//this.$store.state.i18n.locale;
    },
  },
};

</script>


<style lang="less">

</style>
