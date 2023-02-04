<template>
  <k-grid gutter="medium">
    <k-column width="1/3">
      <area-chart
        type="Pie"
        :chart-data="devicesData"
        :chart-labels="devicesLabels"
        download="Site_Devices.png"
        :label="$t('simplestats.devices.graph.devices')"
        :fill="true"
        :auto-colorize="true"
        :height="200"
      />
    </k-column>

    <k-column width="1/3">
      <area-chart
        type="Pie"
        :chart-data="browsersData"
        :chart-labels="browsersLabels"
        download="Site_BrowserEngines.png"
        :label="$t('simplestats.devices.graph.engines')"
        :fill="true"
        :auto-colorize="true"
        :height="200"
      />
    </k-column>

    <k-column width="1/3">
      <area-chart
        type="Pie"
        :chart-data="systemsData"
        :chart-labels="systemsLabels"
        download="Site_OperatingSystems.png"
        :label="$t('simplestats.devices.graph.oses')"
        :fill="true"
        :auto-colorize="true"
        :height="200"
      />
    </k-column>

    <k-column width="1/1">
      <area-chart
        type="Line"
        :chart-data="devicesOverTimeData"
        :chart-labels="chartPeriodLabels"
        download="Site_DevicesEvolution.png"
        :label="$t('simplestats.devices.graph.devicehistory')"
        header-size="large"
        :x-title="$t('simplestats.charts.time')"
        :y-title="$t('simplestats.devices.graph.devicehistory.y')"
        :stacked="true"
        :fill="true"
        :auto-colorize="true"
        :x-time-axis="true"
        :y-visits-axis="true"
        :height="300"
      ></area-chart>
    </k-column>
  </k-grid>
</template>

<script>

import AreaChart from '../Ui/AreaChart.vue';
import SectionBase from '../Sections/SimpleStatsSectionBase.vue';

export default {
  extends: SectionBase,
  components: {
    AreaChart,
  },

  data() {
    return {
      devicesData: [],
      devicesLabels: [],
      devicesOverTimeData: [],
      chartPeriodLabels: [],
      browsersData: [],
      browsersLabels: [],
      systemsData: [],
      systemsLabels: [],
    }
  },

  methods: {
    loadData(response) {
      this.devicesData    = response.devicesdata;
      this.devicesLabels  = response.deviceslabels;

      this.browsersData   = response.enginesdata;
      this.browsersLabels = response.engineslabels;

      this.devicesOverTimeData = response.devicesovertime;
      this.chartPeriodLabels   = response.chartperiodlabels;

      this.systemsData   = response.systemsdata;
      this.systemsLabels = response.systemslabels;
    },
  }
};
</script>

<style lang="scss">

</style>
