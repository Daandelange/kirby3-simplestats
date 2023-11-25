<template>
  <div>
  <k-headline v-if="label.length>0" :size="headerSize">{{ label }}</k-headline>
  <div :class="'ss-chart ss-'+type.toLowerCase()+'-chart'">

    <!-- A chart download link -->
    <div class="ss-download-link" v-if="download!==false && base64image.length">
      <a :href="base64image" :download="fileDownloadStr" >
        <k-button text="Download" tooltip="Exports the chart as a PNG file that you can archive or share." icon="download" />
      </a>
    </div>

    <!-- The chart component -->
    <div v-if="hasDataForCurrentPeriod" class="ss-chart-wrapper">
      <component :is="type+'Chart'" :chart-data="fullChartData" :chart-options="fullChartOptions" :height="height" :width="width" ref="linechart"/>
    </div>

    <!-- Empty and not in timeframe messages -->
    <k-empty v-else-if="!isEmpty" layout="block" class="emptyChart">{{ $t('simplestats.nodatafortimerange', 'There is no data for the selected time range.') }}</k-empty>
    <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet', 'No data yet') }}</k-empty>
  </div>
  </div>
</template>

<script>

import { Line as LineChart, Bar as BarChart, Pie as PieChart } from 'vue-chartjs/legacy'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  CategoryScale,
  PointElement,
  TimeScale,
  Filler,
  // BarController,
  BarElement,
  // Interaction,
  ArcElement, // For Pie chart
  // LogarithmicScale, // Logarithmic scale for visits
  //LogarithmicScale,
} from 'chart.js'
// Load date adapter
import 'chartjs-adapter-moment';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  CategoryScale,
  PointElement,
  TimeScale,
  Filler,
  // BarController,
  BarElement,
  // Interaction,
  ArcElement,
  // LogarithmicScale,
);

import _Merge from 'lodash.merge';

export default {
  name: 'AreaChart',
  components: { LineChart, BarChart, PieChart },
  inheritAttrs: false,
  props: {
    type: {
      type: String,
      default: 'Line',
      validator: propValue => (["Line","Bar","Pie"].includes(propValue)),
    },
    chartData: {
      // type: [Object, Array],
      type: Array,
      // required: true,
      default: () => [],
    },
    chartOptions: {
      type: Object,
      default(){
        return {
          //aspectRatio: 4.0,
        };
      }
    },
    chartLabels: {
      type: Array,
      default(){
        return [];
      }
    },
    label: {
      type: String,
      default: '',
    },
    headerSize: {
      type: String,
      default: 'medium',
    },
    xTitle: {
      type: String | null,
      default: null,
    },
    yTitle: {
      type: String | null,
      default: null,
    },
    download: {
      type: String | Boolean,
      default: false,
    },
    width: {
      type: Number,
      default: null,
    },
    height: {
      type: Number,
      default: null,
    },
    autoColorize: {
      type: Boolean,
      default: false,
    },
    autoGreyize: {
      type: Boolean,
      default: false,
    },
    showLegend: {
      type: Boolean,
      default: true,
    },
    fill: {
      type: Boolean | String,
      default: true,
    },
    xTimeAxis: {
      type: Boolean,
      default: false,
    },
    yVisitsAxis: {
      type: Boolean,
      default: false,
    },
    stacked: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    isEmpty(){
      return !this.chartData || this.chartData.length < 1
    },
    hasDataForCurrentPeriod(){
      return !this.isEmpty && (this.chartData?.some( (dataset)=> dataset.data && dataset.data.length >= 1 ));
    },
    fileDownloadStr(){
      // Download option with date added in string
      // Todo: rather include the selected timespan dates ?
      const date = new Date();
      return (String(this.download??'MyChart.png').replace('.png', '-'+date.getFullYear()+'-'+Number(date.getMonth()+1).toString().padStart(2,'0')+'-'+Number(date.getDate()).toString().padStart(2,'0')+'.png'));
    },
    fullChartOptions(){
      return _Merge(
        {}, // Empty object not to re-assign another one
        this.chartOptions, // User defaults
        
        // Shared common styles
        {
          animation: {
            onComplete: this.generateDownloadLink,
          },
          // backgroundColor: 
          barPercentage: 0.5,
          width: this.width,
          height: this.height,
          //barThickness: 5, // Fixed size 5 px ?
          //aspectRatio: 4.0,
          maintainAspectRatio: false, // To disable aspect ratio and allow setting height via CSS
          // animation: false,
          //backgroundColor: '#fff', // Default bg color
          borderColor: '#000',
          borderWidth: 1,
          // normalized: true, // todo ? (to speed up chart.js)

          // Global line chart appearance
          datasets: {
            line: {
              //fill: true,
              // backgroundColor: '#fff',
              borderWidth: 0, // Line charts have no strokes
              // stack: true,
              // pointBackgroundColor: '#313740',// same as Kirby's var(--color-dark)
              pointRadius: 0,
              pointHitRadius: 3,
              //pointBorderColor: '#313740',// same as Kirby's var(--color-dark)
              pointHoverRadius: 3,
            },
          },
          plugins: {
            legend: {
              maxHeight: 75, // Limit legend height so charts don't become too tiny. Overflow is hidden.
            },
            filler: {
                propagate: true, // Also fill when item below is hidden
            },
            interaction: {
              // intersect: false, // Fixes stacked values hover?
            },
            tooltip: {
              // Remove zero values from stacked tooltips
              filter: function (data, tooltipItem, something, chartData) {
                // console.log('TooltipFilter.stacked=', data.datasets[tooltipItem.datasetIndex]);
                // console.log('TooltipFilter.stacked=', data, tooltipItem, something, chartData); return true;
                //var value = data.dataset.data[tooltipItem.index];
                if(chartData.datasets.length > 5){ // Instead of stacked, check if there are many datasets
                  if (data.raw === 0) {
                    return false;
                  }
                }
                return true;
              },
            },
          },
        },

        // Pie specific stuff
        this.type==='Pie'?{
          //radius: this.type==='Pie'?(((this.height ?? this.width)*.5)??null):null,
          //borderColor: '#000',
          // borderWidth: 1,
        }:{
        // Styles for non-PieCharts
          scales: {
            xAxis: {
              //maxHeight: 25, not a property, need to set it with a hook !
              afterSetDimensions:  (scale) => {
                // Here, scale.height is not yet defined... but we can set the max height
                scale.maxHeight = 25;
              },
            }
          },
        },

        // Filled style
        this.fill?{
          fill: true,
          backgroundColor: (typeof this.fill==='string')?this.fill:'#313740',// same as Kirby's var(--color-dark)
        }:{},

        // Time axis
        this.xTimeAxis?{
          scales: {
            xAxis: {
              title: {
                display: false, // Tick marks are explicit enough to understand that this is about time...
                //display: true, // Show title
                text: this.xTitle??this.$t('simplestats.charts.time', 'Time'), // Axis title
              },
              type: 'time',
              time: {
                unit: 'month',
                displayFormats: {
                    month: 'MMM YYYY',
                }
              },
            },
          },
        }:{}, this.yVisitsAxis ? {
          scales: {
            yAxis: {
              title: {
                display: true,
                text: this.yTitle??this.$t('simplestats.charts.visits', 'Visits'),
              },
              //type: 'logarithmic', // Show visits logarithmically = gives better details in small values
              beginAtZero: true, // Force y axis to origin 0
            },
          },
        }:{}, this.stacked ? {
          scales: {
            yAxis: {
              stacked: true,
            },
          },
        }:{}, this.showLegend===false ? {
          plugins: {
            legend: {
              display: false,
              title: {
                display: false,
              }
            }
          },
          scales: {
            xAxis: {
              display: false, // Hides whole axis
              title: {
                display: false, // Hide x-axis title
                //text: '',
              },
              // Sets smaller font size
              // ticks: {
              //   font: {
              //     size: 6,
              //   },
              // },
            },
            yAxis: {
              tickLength: 5, // Compacter ticks
            }
          },
        }:{});
    },
    fullChartData() {
      const thisRef = this;
      const uidTree = this.chartData.map((dataset,i) => dataset.ss_uid??i);
      return {
        labels: this.chartLabels,
        datasets: this.chartData.map(function(dataset, i){
          if(
            (thisRef.autoColorize || thisRef.autoGreyize) === true && // Option enabled
            //dataset.fill === true && // Data set to fill
            !dataset.backgroundColor //&& // No custom bg already set (don't override)
            // dataset.ss_uid // Customisation string is set
          ){
            // Base color settings
            let saturation = .3;
            let lightness = .4;

            // Pie charts need the colors as an array for each data value
            if(thisRef.type==='Pie'){
              dataset.backgroundColor = [];
              const count = thisRef.chartLabels.length > 1 ? thisRef.chartLabels.length : 1;
              let pos=0;
              // Colorizing pie charts is simpler
              for(let label of thisRef.chartLabels){
                dataset.backgroundColor.push(
                  (thisRef.autoColorize) ?
                  'hsl('+Number((360/count*pos)).toFixed(0)+','+Number(saturation*100).toFixed(0)+'%,'+Number(lightness*100).toFixed(0)+'%)' :
                  'hsl(0,0%,'+Number(40+(40/count*pos)).toFixed(0)+'%)'
                );
                pos++;
              }
              return dataset;  
            }
            
            const parts = dataset.ss_uid?dataset.ss_uid.split('/'):([''+i]);
            let hueThreshold = 0;
            let lumThreshold = 0;
            let parentImportance = 1.0;
            let siblings = null;
            parts.forEach((part, pi)=> {
              if(!dataset.ss_uid){
                siblings = thisRef.chartData.map((value,key)=>key);
              }
              else if(pi===0){
                siblings = uidTree.filter(uid => String(uid).includes('/')===false);
              }
              else {
                siblings = uidTree.filter(function(uid) { const part=parts.slice(0,pi).join('/'); return String(uid).startsWith(part);});
              }

              const pos = siblings.indexOf( dataset.ss_uid?parts.slice(0,pi+1).join('/'):i);
              const numSiblings = (siblings.length>1)?siblings.length:1;

              if(thisRef.autoColorize){
                // Depth 1 determines color
                if(pi===0){
                  hueThreshold = (1.0/numSiblings) * pos;
                }
                // More depth changes luminosity
                else {
                  parentImportance /= numSiblings;
                  lumThreshold += parentImportance *.8 * pos;

                  // Deeper levels lose saturation
                  if(pi>1) saturation -= 10;
                }
              }
              // Grey-ize
              else {
                parentImportance /= numSiblings;
                lumThreshold += parentImportance * pos;
              }
            });
            
            dataset.backgroundColor = 'hsl('+Number(hueThreshold*360.0).toFixed(0)+', '+Number(saturation*100*(thisRef.autoGreyize?0:1)).toFixed(0)+'%, '+Number(lightness*100+lumThreshold*50).toFixed(0)+'%)';

          }
          return dataset;
        }),
      };
    },
  },
  data() {
    return {
      base64image : '',
    }
  },
  methods: {
    generateDownloadLink(){
      if(this.download===false) return;
      if(this.$refs.linechart?._data._chart){
        // Sometimes the function call fails when canvas doesn't exist...
        if( this.$refs.linechart._data._chart.canvas ){
          this.base64image = this.$refs.linechart._data._chart.toBase64Image('image/png');
        }
      }
      else {
        this.base64image = '';
      }
    },
  },
}
</script>

<style lang="less">
.ss-chart {

  .emptyChart {
    min-height: 250px;
    padding: 1em;
  }

  .ss-chart-wrapper {
    border: 1px solid #ddd;
    padding: var(--spacing-3) var(--spacing-3) var(--spacing-1) 0;
    background-color: var(--color-background);
  }
  &.ss-pie-chart .ss-chart-wrapper {
    padding: var(--spacing-2) 0 var(--spacing-5);
  }

  .ss-download-link {
    text-align: right;
    height: 0;
    position: relative;

    .k-button {
      padding: .2rem .5rem;
      border-radius: 2px;
      direction: rtl; // invert icon and text
      //--text-sm: var(--text-xs);
      font-size: var(--text-xs);
      .k-icon {
        --size: var(--text-xs);
      }

      &:hover {
        background-color: #fff;

        .k-button-text {
          display: inline-block;
          padding-left: 0;
          padding-right: .5rem;
        }
      }

      .k-button-text {
        display: none;
      }
    }
  }
}
</style>