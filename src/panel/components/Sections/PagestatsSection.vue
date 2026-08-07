<template>
  <k-section :label="label">
    <k-grid variant="columns">
      <!-- Loading message -->
      <k-column v-if="isLoading" width="1/1">
        <k-empty>
          <k-icon type="loader"/>
          <span>Fetching data...</span>
        </k-empty>
      </k-column>

      <!-- Total Visits -->
      <k-column v-if="showTotals && !isLoading" width="1/1">
        <div class="k-stats">
          <k-stat
            :label="$t('simplestats.visits.visitsovertime')"
            :value="totalHits"
            :info="Math.round(averageHits) + ' ' + $t('simplestats.visits.per') + ' ' + timespanUnitName"
            icon="users"
            link="simplestats"
          />
          <k-stat
            v-if="showFullInfo"
            label="Samples"
            :value="trackingPeriods"
            :info="$t('simplestats.visits.since') + ' ' + trackedSince"
            icon="chart"
            link="simplestats"
          />
        </div>
      </k-column>

      <!-- Visits Over Time -->
      <k-column v-if="showTimeline && !isLoading" width="1/1">
        <k-simplestats-chart
          type="Line"
          download="PageVisitsOverTime.png"
          :label="$t('simplestats.visits.visitsovertime')"
          :chart-data="languagesOverTime"
          :chart-labels="chartPeriodLabels"
          :stacked="languagesAreEnabled"
          :auto-colorize="true"
          :x-time-axis="true"
          :y-visits-axis="true"
          :height="chartHeight('timeline')"
        />
      </k-column>

      <!-- Visits Per Language -->
      <k-column v-if="languagesAreEnabled && showLanguages && !isLoading" width="1/1">
        <k-simplestats-chart
          type="Pie"
          :chart-data="languageTotalHits"
          :chart-labels="chartLanguagesLabels"
          :chart-options="chartOptions"
          :auto-colorize="true"
          :fill="true"
          :show-legend="languagesAreEnabled"
          :height="chartHeight('languages')"
          download="PageGlobalLanguageVisits.png"
          :label="$t('simplestats.visits.globallanguages')"
        />
      </k-column>
    </k-grid>
  </k-section>
</template>

<script>
export default {
  data() {
    return {
      showTotals: true,
      showTimeline: false,
      showLanguages: false,
      size: 'medium',
      statsdata: null,
      languagesOverTime: [],
      chartPeriodLabels: [],
      chartLanguagesLabels: [],
      languageTotalHits: [],
      trackedSince: '[unknown]',
      lastVisited: '[unknown]',
      totalHits: 0,
      averageHits: 0,
      timespanUnitName: '[unknown]',
      trackingPeriods: 0
    };
  },

  computed: {
    normalizedSize() {
      if (['small', 'compact'].includes(this.size)) return 'small';
      if (['large', 'huge'].includes(this.size)) return 'large';
      if (this.size === 'tiny') return 'tiny';
      return 'medium';
    },

    languagesAreEnabled() {
      return this.chartLanguagesLabels.length > 1;
    }
  },

  created() {
    this.load().then(response => {
      // In rare cases when received data is incorrect : prevent crashing js
      if(!response || !response.statsdata){
        this.$panel.error({
          message: 'Loading error : wrong data received',
          details: 'While data was received, it was sent in an incorrect format.'
        });
        return;
      }
      const stats = response.statsdata;
      Object.assign(this, {
        label: response.label ?? response.headline,
        showFullInfo: response.showFullInfo,
        showTotals: response.showTotals,
        showTimeline: response.showTimeline,
        showLanguages: response.showLanguages,
        size: response.size,

        statsdata: stats,
        languagesOverTime: stats.languagesOverTime,
        chartPeriodLabels: stats.chartperiodlabels,
        chartLanguagesLabels: stats.chartlanguageslabels,
        languageTotalHits: stats.languageTotalHits,
        trackedSince: stats.firstVisited,
        lastVisited: stats.lastVisited,
        averageHits: stats.averageHits,
        totalHits: stats.totalHits,
        timespanUnitName: stats.timespanUnitName,
        trackingPeriods: stats.trackingPeriods,
      });
    });
  },

  methods: {
    chartHeight(type) {
      const heights = {
        timeline: { tiny: 120, small: 240, medium: 260, large: 280 },
        languages: { tiny: 80, small: 185, medium: 205, large: 225 }
      };

      return heights[type][this.normalizedSize] ?? heights[type].medium;
    }
  }
};
</script>
