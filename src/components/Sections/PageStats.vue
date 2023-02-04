<template>
    <k-grid gutter="medium">
      <!-- TODO: Add more stats: Text stats (total unique visits total and per lang, most popular pages, etc) - Pie chart of page-by-page popularity - -->
      <k-column>
        <k-headline size="medium">{{ $t('simplestats.visits.visitsovertime', 'Visits over time') }}</k-headline>
        <area-chart
          type="Bar"
          :chart-data="visitsOverTimeData"
          :chart-options="chartOptions"
          :chart-labels="chartPeriodLabels"
          download="Site_Visits.png"
          :x-title="$t('simplestats.charts.time', 'Time')"
          :y-title="$t('simplestats.charts.visits', 'Visits')"
          :height="100"
          :x-time-axis="true"
          :y-visits-axis="true"
          :show-legend="false"
          :fill="true"
        />
      </k-column>

      <k-column>
        <k-headline size="medium">{{ $t('simplestats.visits.pagevisitsovertime') }}</k-headline>
        <area-chart
          :chart-data="pageVisitsOverTimeDataSorted"
          :chart-labels="chartPeriodLabels"
          :chart-options="chartOptions"
          download="Site_PageVisits.png"
          :x-title="$t('simplestats.charts.time')"
          :y-title="$t('simplestats.charts.visits')"
          :height="450"
          :stacked="true"
          :auto-colorize="true"
          :x-time-axis="true"
          :y-visits-axis="true"
          :fill="true"
        ></area-chart>
      </k-column>

      <k-column width="3/4" v-if="languagesAreEnabled">
        <area-chart
          :chart-data="languagesOverTimeData"
          :chart-labels="chartPeriodLabels"
          :chart-options="chartOptions"
          download="Site_LanguagesOverTime.png"
          :x-title="$t('simplestats.charts.time')"
          :y-title="$t('simplestats.charts.visits')"
          :height="250"
          :stacked="true"
          :auto-colorize="true"
          :fill="true"
          :x-time-axis="true"
          :y-visits-axis="true"
          :label="$t('simplestats.visits.languagesovertime')"
        ></area-chart>
      </k-column>

      <k-column width="1/4" v-if="languagesAreEnabled">
        
        <k-headline>{{ $t('simplestats.visits.globallanguages') }}</k-headline>
        
        <area-chart
          v-if="globalLanguagesData.length > 0"
          type="Pie"
          download="Site_LanguagePopularity.png"
          :chart-data="globalLanguagesData"
          :chart-labels="chartLanguagesLabels"
          :chart-options="chartOptions"
          :auto-colorize="true"
          :height="200"
          :fill="true"
        />
      </k-column>

      <k-column>

        <searchable-table
          :columns="columns"
          :rows="rows"
          layout="table"
          :label="$t('simplestats.visits.visitedpages')"
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
    SearchableTable,
    AreaChart,
  },
  data() {
    return {
      //...SectionBase.data(),

      // Page Visits table
      rows: [],
      columns: {},

      chartPeriodLabels: [],

      visitsOverTimeData:   [],
      pageVisitsOverTimeData:   [],

      languagesOverTimeData: [],
      globalLanguagesData: [],
      languagesAreEnabled: false,
      userLocale: 'en',

      chartOptions: {
        animation: {
          onComplete: this.generateDownloadLink,
        },        
      },
    }
  },
  props: {

  },
  computed: {
    pageVisitsOverTimeDataSorted(){
      return this.pageVisitsOverTimeData?.sort((a, b) => (a.ss_uid<b.ss_uid?-1:(a.ss_uid>b.ss_uid)?1:0));
    },
  },
  methods: {
    
    loadData(apiResponse){

      this.columns = apiResponse.pagestatslabels
      this.rows    = apiResponse.pagestatsdata

      this.chartPeriodLabels = apiResponse.chartperiodlabels
      this.visitsOverTimeData   = apiResponse.visitsovertimedata

      this.pageVisitsOverTimeData = apiResponse.pagevisitsovertimedata

      this.globalLanguagesData = apiResponse.globallanguagesdata
      this.chartLanguagesLabels = apiResponse.chartlanguageslabels
      this.languagesOverTimeData = apiResponse.languagesovertimedata
      this.languagesAreEnabled = apiResponse.languagesAreEnabled
      this.userLocale = window.panel.$language ? window.panel.$language.locale : (this.$store.state.i18n ? this.$store.state.i18n.locale : '');

    }
 
  }
};
</script>

<style lang="scss">

</style>