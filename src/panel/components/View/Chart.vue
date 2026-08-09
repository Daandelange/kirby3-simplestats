<template>
  <k-section :class="chartClass" :label="label">
    <!-- Download Button -->
    <a
      v-if="download !== false"
      :download="downloadFileName"
      :href="base64image"
      slot="options"
    >
      <k-button
        :text="$t('simplestats.chart.download')"
        :disabled="!base64image"
        icon="download"
        size="xs"
        variant="filled"
      />
    </a>

    <!-- Chart -->
    <Chart
      v-if="hasData"
      :is="type + 'Chart'"
      :chart-data="fullChartData"
      :chart-options="fullChartOptions"
      :height="height"
      ref="chart"
      class="k-simplestats-chart-wrapper"
    />

    <!-- Empty state -->
    <k-empty
      v-else
      :icon="isLoading?'loader':'chart'"
      layout="cards"
      :text="emptyText"
      :style="{ height: emptyHeight }"
    />
  </k-section>
</template>

<script>
import {Line as LineChart, Bar as BarChart, Pie as PieChart } from 'vue-chartjs/legacy';
import chartUtils from '../../mixins/chartUtils.js';

export default {
  mixins: [chartUtils],

  components: { LineChart, BarChart, PieChart },

  props: {
    label: String,
    type: {
      type: String,
      validator: v => ['Line', 'Bar', 'Pie'].includes(v)
    },
    chartData: {
      type: Array,
      default: () => []
    },
    chartLabels: {
      type: Array,
      default: () => []
    },
    chartOptions: {
      type: Object,
      default: () => ({})
    },
    download: {
      type: [String, Boolean],
      default: false
    }
  },

  computed: {
    chartClass() {
      return `k-simplestats-chart k-simplestats-${this.type.toLowerCase()}-chart`;
    },

    hasData() {
      return this.chartData.some(dataset => Array.isArray(dataset.data) && dataset.data.length);
    },

    fullChartData() {
      return this.prepareChartData(this.chartData, this.chartLabels, this.type);
    },

    fullChartOptions() {
      return this.buildChartOptions(this.chartOptions);
    },

    emptyText() {
      return this.chartData.length === 0
        ? this.$t('simplestats.chart.empty')
        : this.$t('simplestats.chart.nodatatimeframe');
    },

    emptyHeight() {
      const spacing = this.type === 'Pie' ? '--spacing-10' : '--spacing-8';
      return `calc(${this.height}px + var(${spacing}))`;
    },

    downloadFileName() {
      const date = new Date();
      const suffix = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
      return String(this.download || 'MyChart.png').replace('.png', `-${suffix}.png`);
    }
  }
};
</script>

<style>
.k-simplestats-chart .k-simplestats-chart-wrapper {
  background-color: var(--table-color-back);
  padding: var(--spacing-4) var(--spacing-3);
  box-shadow: var(--shadow);
  border-radius: var(--rounded);
}

.k-simplestats-chart.k-simplestats-pie-chart .k-simplestats-chart-wrapper {
  padding: var(--spacing-4) 0 var(--spacing-6);
}
</style>
