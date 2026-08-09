<template>
  <k-grid variant="columns" style="column-gap: var(--spacing-8)">
    <!-- Referrers by Medium -->
    <k-column width="1/2">
      <k-simplestats-chart
        type="Pie"
        height="200"
        download="Site_ReferrersByMedium.png"
        :label="$t('simplestats.referers.referersbymedium')"
        :chart-data="charts.byMedium.data"
        :chart-labels="charts.byMedium.labels"
        :auto-colorize="true"
      />
    </k-column>

    <!-- Referrers by Domain -->
    <k-column width="1/2">
      <k-simplestats-chart
        type="Pie"
        height="200"
        download="Site_ReferrersByDomain.png"
        :label="$t('simplestats.referers.referersbydomain')"
        :chart-data="charts.byDomain.data"
        :chart-labels="charts.byDomain.labels"
        :auto-colorize="true"
      />
    </k-column>

    <!-- Referrers Over Time -->
    <k-column width="1/1">
      <k-simplestats-chart
        type="Line"
        height="300"
        download="Site_ReferersEvolution.png"
        :label="$t('simplestats.referers.referersovertime')"
        :chart-data="charts.byMediumOverTime.data"
        :chart-labels="charts.byMediumOverTime.labels"
        :y-title="$t('simplestats.chart.hitspermedium')"
        :stacked="true"
        :auto-colorize="true"
        :x-time-axis="true"
        :y-visits-axis="true"
      />
    </k-column>

    <!-- Referrers Table -->
    <k-column width="1/1">
      <k-simplestats-filter-table
        :label="$t('simplestats.referers.allreferers')"
        :rows="table.rows"
        :columns="table.columns"
      />
    </k-column>
  </k-grid>
</template>

<script>
import apiFetch from '../../mixins/apiFetch.js';

export default {
  mixins: [apiFetch],

  data() {
    return {
      charts: {
        byDomain: {
          data: [],
          labels: []
        },
        byMedium: {
          data: [],
          labels: []
        },
        byMediumOverTime: {
          data: [],
          labels: []
        }
      },

      table: {
        rows: [],
        columns: {}
      }
    };
  },

  methods: {
    loadData(response) {
      const map = {
        byDomain: ['referersbydomaindata', 'referersbydomainlabels'],
        byMedium: ['referersbymediumdata', 'referersbymediumlabels'],
        byMediumOverTime: ['referersbymediumovertimedata', 'chartperiodlabels']
      };

      Object.entries(map).forEach(([key, [dataKey, labelKey]]) => {
        this.charts[key].data = response[dataKey] || [];
        this.charts[key].labels = response[labelKey] || [];
      });

      this.table.rows = response.refererstabledata || [];
      this.table.columns = response.refererstablelabels || {};
    }
  }
};
</script>
