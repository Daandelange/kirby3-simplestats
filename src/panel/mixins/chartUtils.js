/**
 * @internal
 * Chart Options & Utilities Mixin
 *
 * Shared Chart.js setup for SimpleStats charts.
 */
import 'chartjs-adapter-date-fns';
import {
  Chart,
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  CategoryScale,
  PointElement,
  TimeScale,
  Filler,
  BarElement,
  ArcElement,
} from 'chart.js';

Chart.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  CategoryScale,
  PointElement,
  TimeScale,
  Filler,
  BarElement,
  ArcElement
);

import { usePanel } from "kirbyuse";
import { useLibrary } from "kirbyuse";

export default {
  props: {
    autoColorize: Boolean,
    autoGreyize: Boolean,
    fill: {
      type: [Boolean, String],
      default: true
    },
    height: Number,
    showLegend: {
      type: Boolean,
      default: true
    },
    stacked: Boolean,
    xTimeAxis: Boolean,
    xTitle: String,
    yVisitsAxis: Boolean,
    yTitle: String
  },

  data() {
    return { base64image: '' };
  },

  computed: {
    chartColor() {
      if (typeof this.fill === 'string' && this.fill.length) {
        const parsed = this.$library.colors.parseAs(this.fill, 'hex');
        if (parsed?.length) return parsed;
      }

      const defaultEl = document.getElementById('chart-default-color-getter');
      if (defaultEl) {
        const style = window.getComputedStyle(defaultEl);
        const parsed = this.$library.colors.parseAs(style?.color, 'hex');
        if (parsed?.length) return parsed;
      }

      return '#313740';
    }
  },

  methods: {
    generateDatasetColor(dataset, uidTree, index) {
      const panel = usePanel();
      const isDark = (panel.theme.current=="dark");
      let hue = 0, lightness = isDark ? 40 : 60, saturation = this.autoGreyize ? 0 : 30;
      const parts = dataset.ss_uid?.split('/') || [index];

      parts.forEach((_, depth) => {
        const siblings = depth === 0
          ? uidTree.filter(uid => !String(uid).includes('/'))
          : uidTree.filter(uid => String(uid).startsWith(parts.slice(0, depth).join('/')));

        const pos = siblings.indexOf(dataset.ss_uid ?? index);
        const count = Math.max(siblings.length, 1);

        if (depth === 0) hue = (360 / count) * pos;
        else lightness += ((90-lightness) / count) * pos;
      });

      return `hsl(${Math.round(hue)}, ${saturation}%, ${Math.round(lightness)}%)`;
    },

    generatePieColors(labels) {
      const count = Math.max(labels.length, 1);

      const panel = usePanel();
      const isDark = (panel.theme.current=="dark");
      const lightness = isDark ? 40 : 60;

      return labels.map((_, i) => this.autoColorize
        ? `hsl(${Math.round((360 / count) * i)}, 30%, ${lightness}%)`
        : `hsl(0, 0%, ${40 + (40 / count) * i}%)`
      );
    },

    prepareChartData(rawData, labels, type) {
      const uidTree = rawData.map((d, i) => d.ss_uid ?? i);

      return {
        labels,
        datasets: rawData.map((source, index) => {
          const dataset = { ...source };

          if ((this.autoColorize || this.autoGreyize) && !dataset.backgroundColor) {
            dataset.backgroundColor = type === 'Pie'
              ? this.generatePieColors(labels)
              : this.generateDatasetColor(dataset, uidTree, index);
          }

          return dataset;
        }),
      };
    },

    generateDownloadLink(chartRef) {
      const chart = chartRef?._data?._chart;
      if (!chart?.canvas) return;

      this.base64image = chart.toBase64Image('image/png');
    },

    buildChartOptions() {
      const options = this.deepMerge({}, this.chartOptions, {
        animation: {
          onComplete: () => this.generateDownloadLink(this.$refs.chart)
        },

        borderColor: '#1f1f1f',
        borderWidth: 1,
        height: this.height,
        maintainAspectRatio: false,

        datasets: {
          line: {
            borderWidth: 0,
            pointRadius: 0,
            pointHitRadius: 3,
            pointHoverRadius: 3
          }
        },

        plugins: {
          filler: {
            propagate: true
          },

          legend: {
            maxHeight: 75,
            display: this.showLegend
          },

          tooltip: {
            filter: ctx => ctx.chart.data.datasets.length <= 5 || ctx.raw !== 0
          }
        }
      });

      if (this.fill) {
        options.fill = true,
        options.backgroundColor = this.chartColor;
      }

      if (this.xTimeAxis) {
        options.scales = options.scales || {};
        options.scales.xAxis = {
          type: 'time',
          time: {
            unit: 'month',
            displayFormats: { month: 'MMM yyyy'}
          },
          title: {
            display: false,
            text: this.xTitle ?? this.$t('simplestats.chart.time')
          }
        };
        options.plugins.tooltip = {
          callbacks: {
            // Sets the tooltip title
            title: function(context){
              let date = new Date(context[0].parsed.x);
              const library = useLibrary();
              return library.dayjs(date).format('ddd D MMM YYYY');
            }
          }
        };
      }

      if (this.yVisitsAxis) {
        options.scales = options.scales || {};
        options.scales.yAxis = {
          ...(options.scales.yAxis || {}),
          beginAtZero: true,
          title: {
            display: true,
            text: this.yTitle ?? this.$t('simplestats.chart.visits')
          },
          ticks: {
            stepSize: 1,
          }
        };
      }

      if (this.stacked) {
        options.scales = options.scales || {};
        options.scales.yAxis = options.scales.yAxis || {};
        options.scales.yAxis.stacked = true;
      }

      if (this.showLegend === false) {
        options.plugins.legend = {
          display: false,
          title: { display: false }
        };
      }

      return options;
    },

    deepMerge(target, ...sources) {
      for (const source of sources) {
        if (!source || typeof source !== 'object') continue;

        for (const key in source) {
          const value = source[key];

          target[key] = value && typeof value === 'object' && !Array.isArray(value)
            ? this.deepMerge(target[key] || {}, value)
            : value;
        }
      }

      return target;
    }
  }
};
