<template>
  <k-grid variant="columns" style="column-gap: var(--spacing-8)">
    <!-- Devices -->
    <k-column width="1/3">
      <k-simplestats-chart
        type="Pie"
        height="200"
        download="Site_Devices.png"
        :label="$t('simplestats.devices.visitordevices')"
        :chart-data="charts.devices.data"
        :chart-labels="charts.devices.labels"
        :auto-colorize="true"
      />
    </k-column>

    <!-- Browsers -->
    <k-column width="1/3">
      <k-simplestats-chart
        type="Pie"
        height="200"
        download="Site_BrowserEngines.png"
        :label="$t('simplestats.devices.browserengines')"
        :chart-data="charts.browsers.data"
        :chart-labels="charts.browsers.labels"
        :auto-colorize="true"
      />
    </k-column>

    <!-- Systems -->
    <k-column width="1/3">
      <k-simplestats-chart
        type="Pie"
        height="200"
        download="Site_OperatingSystems.png"
        :label="$t('simplestats.devices.oses')"
        :chart-data="charts.systems.data"
        :chart-labels="charts.systems.labels"
        :auto-colorize="true"
      />
    </k-column>

    <!-- Devices Over Time -->
    <k-column width="1/1">
      <k-simplestats-chart
        type="Line"
        height="300"
        download="Site_DevicesEvolution.png"
        :label="$t('simplestats.devices.devicehistory')"
        :chart-data="charts.devicesOverTime.data"
        :chart-labels="charts.devicesOverTime.labels"
        :x-title="$t('simplestats.chart.time')"
        :y-title="$t('simplestats.chart.devicehistory')"
        :stacked="true"
        :auto-colorize="true"
        :x-time-axis="true"
        :y-visits-axis="true"
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
        devices: {
          data: [],
          labels: []
        },
        browsers: {
          data: [],
          labels: []
        },
        systems: {
          data: [],
          labels: []
        },
        devicesOverTime: {
          data: [],
          labels: []
        }
      }
    };
  },

  methods: {
    loadData(response) {
      const map = {
        devices: ['devicesdata', 'deviceslabels'],
        browsers: ['enginesdata', 'engineslabels'],
        systems: ['systemsdata', 'systemslabels'],
        devicesOverTime: ['devicesovertime', 'chartperiodlabels']
      };

      Object.entries(map).forEach(([key, [dataKey, labelKey]]) => {
        this.charts[key].data = response[dataKey] || [];
        this.charts[key].labels = response[labelKey] || [];
      });
    }
  }
};
</script>
