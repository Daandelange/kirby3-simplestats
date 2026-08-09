<template>
  <!-- TODO: Add more stats: Text stats (total unique visits total and per lang, most popular pages, etc) - Pie chart of page-by-page popularity - -->
  <k-grid variant="columns" style="column-gap: var(--spacing-8)">
    <!-- Visits Over Time -->
    <k-column width="1/1">
      <k-simplestats-chart
        type="Bar"
        height="150"
        download="Site_Visits.png"
        :label="$t('simplestats.visits.visitsovertime')"
        :chart-data="charts.visitsOverTime.data"
        :chart-labels="charts.visitsOverTime.labels"
        :x-title="$t('simplestats.chart.time')"
        :y-title="$t('simplestats.chart.visits')"
        :x-time-axis="true"
        :y-visits-axis="true"
      />
    </k-column>

    <!-- Page Visits Over Time -->
    <k-column width="1/1">
      <k-simplestats-chart
        type="Line"
        height="450"
        download="Site_PageVisits.png"
        :label="$t('simplestats.visits.pagevisitsovertime')"
        :chart-data="sortedPageVisitsOverTime"
        :chart-labels="charts.pageVisitsOverTime.labels"
        :x-title="$t('simplestats.chart.time')"
        :y-title="$t('simplestats.chart.visits')"
        :stacked="true"
        :auto-colorize="true"
        :x-time-axis="true"
        :y-visits-axis="true"
      />
    </k-column>

    <!-- Languages Over Time -->
    <k-column width="2/3" v-if="languagesAreEnabled">
      <k-simplestats-chart
        type="Line"
        height="250"
        download="Site_LanguagesOverTime.png"
        :label="$t('simplestats.visits.languagesovertime')"
        :chart-data="charts.languagesOverTime.data"
        :chart-labels="charts.languagesOverTime.labels"
        :x-title="$t('simplestats.chart.time')"
        :y-title="$t('simplestats.chart.visits')"
        :stacked="true"
        :auto-colorize="true"
        :x-time-axis="true"
        :y-visits-axis="true"
      />
    </k-column>

    <!-- Global Language Popularity -->
    <k-column width="1/3" v-if="languagesAreEnabled">
      <k-simplestats-chart
        type="Pie"
        height="242"
        download="Site_LanguagePopularity.png"
        :label="$t('simplestats.visits.globallanguages')"
        :chart-data="charts.globalLanguages.data"
        :chart-labels="charts.globalLanguages.labels"
        :auto-colorize="true"
      />
    </k-column>

    <!-- Page Stats Table -->
    <k-column>
      <k-simplestats-filter-table
        :label="$t('simplestats.visits.visitedpages')"
        :rows="table.rows"
        :columns="table.columns"
        @row="onRowClick"
      />
    </k-column>
  </k-grid>
</template>

<script>
import apiFetch from '../../mixins/apiFetch.js';
import {usePanel} from 'kirbyuse';

export default {
  mixins: [apiFetch],

  props: {
    currentItem: {
      default: -1,
      type: Number,
    },
  },

  data() {
    return {
      charts: {
        visitsOverTime: {
          data: [],
          labels: []
        },
        pageVisitsOverTime: {
          data: [],
          labels: []
        },
        languagesOverTime: {
          data: [],
          labels: []
        },
        globalLanguages: {
          data: [],
          labels: []
        }
      },

      table: {
        rows: [],
        columns: {}
      },

      languagesAreEnabled: false
    };
  },

  computed: {
    sortedPageVisitsOverTime() {
      return [...this.charts.pageVisitsOverTime.data].sort((a, b) =>
        a.ss_uid < b.ss_uid ? -1 : a.ss_uid > b.ss_uid ? 1 : 0
      );
    }
  },

  methods: {
    loadData(response) {
      const map = {
        visitsOverTime: ['visitsovertimedata', 'chartperiodlabels'],
        pageVisitsOverTime: ['pagevisitsovertimedata', 'chartperiodlabels'],
        languagesOverTime: ['languagesovertimedata', 'chartperiodlabels'],
        globalLanguages: ['globallanguagesdata', 'chartlanguageslabels']
      };

      Object.entries(map).forEach(([key, [dataKey, labelKey]]) => {
        this.charts[key].data = response[dataKey] || [];
        this.charts[key].labels = response[labelKey] || [];
      });

      this.table.rows = response.pagestatsdata || [];
      this.table.columns = response.pagestatslabels || {};
      this.languagesAreEnabled = !!response.languagesAreEnabled;
    },
    openDrawer(current){
      const panel = usePanel();
      const curr = this.table.rows[current]??null;
      const prev = this.table.rows[current+1]??null;
      const next = this.table.rows[current-1]??null;
      const drawerProps = {
        title: this.$t('simplestats.visits.pagestats')+': '+curr.title?.text+' ('+curr.uid+')',
        size: 'huge',
        uid: curr.uid,
        dateFrom: this.$props.dateFrom,
        dateTo: this.$props.dateTo,
        next: this.onNext,
        prev: this.onPrev,
      };

      if(curr){
        if(panel.drawer.isOpen){
          panel.drawer.history.clear();
          // panel.drawer.refresh(drawerProps);
          // panel.drawer.reload(drawerProps);
        }
        panel.drawer.open({
          component: 'k-simplestats-pagestats-drawer',
          props: drawerProps,
        });
        this.currentItem = current;
      }
      //else window.console.log("Issue!!!");
    },
    onRowClick(eventData){
      // Open the drawer if oo
      if(eventData?.row?.uid){
        this.openDrawer(eventData.rowIndex);
      }
    },
    onNext(){
      this.openDrawer(this.currentItem+1);
    },
    onPrev(){
      this.openDrawer(this.currentItem-1);
    },
  }
};
</script>

<style>
.k-simplestats-filter-table .k-table tbody tr:hover td {
  cursor: pointer;
}
</style>
