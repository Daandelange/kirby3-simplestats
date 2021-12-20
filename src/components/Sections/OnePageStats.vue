<template>
  <div :class="{'simplestatsonepagedetailssection': true, 'small': sectionSize=='small', 'medium': sectionSize=='medium', 'large': sectionSize=='large'}">
    <div v-if="headline">
      <k-headline size="medium"><k-icon type="chart" style="display: inline-block; padding-right: 0.5rem;" size="tiny"/> {{ headline }}</k-headline>
      <br/>
    </div>
    <p v-if="showFullInfo && showTotals">
      This page has been <strong>visited {{ totalHits }} times</strong> since {{ trackedSince }} and was last visited on {{ lastVisited }} which averages to <strong>{{ Math.round(averageHits) }} visits per {{ timespanUnitName }}</strong> using {{ trackingPeriods }} samples.
    </p>
    <p v-else-if="showTotals">
      Total hits: <strong>{{ totalHits }}</strong> (<strong>{{ Math.round(averageHits) }}</strong> per {{ timespanUnitName }})
    </p>
    <k-line-field v-if="showTotals" />
<!--
        <k-text-field name="trackedsince" :counter="false" :disabled="true" label="Tracked since" :value="trackedSince" icon="clock"/>
        <k-text-field name="lastvisited" :counter="false" :disabled="true" label="Last Visited" :value="lastVisited" icon="clock"/>
-->

<!--     <k-grid gutter="large"> -->

      <div v-if="languagesAreEnabled && showTimeline" class="detailcolumn visitsovertime">
        <k-headline size="medium">{{ $t('simplestats.visits.visitsovertime') }}</k-headline>
        <area-chart
          :data="languagesOverTime"
          :download="true"
          download="PageLanguagesOverTime.png"
          :xtitle="$t('simplestats.charts.time')"
          :ytitle="$t('simplestats.charts.visits')"
          :height="(this.sectionSize=='small')?'240px':(this.sectionSize=='large')?'280px':(this.sectionSize=='tiny')?'120px':'260px'"
          :stacked="true"
          :library="chartOptions"
          v-if="languagesOverTime.length > 0"
        ></area-chart>
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </div>

      <div v-else-if="showTimeline" class="detailcolumn visitsovertime">
        <br/>
        <k-headline size="medium">{{ $t('simplestats.visits.languagesovertime') }}</k-headline>
        <area-chart
          :data="visitsOverTime"
          :download="true"
          download="PageVisitsOverTime.png"
          :xtitle="$t('simplestats.charts.time')"
          :ytitle="$t('simplestats.charts.visits')"
          :height="(this.sectionSize=='small')?'240px':(this.sectionSize=='large')?'280px':(this.sectionSize=='tiny')?'120px':'260px'"
          :library="chartOptions"
          v-if="visitsOverTime.length > 0"
        ></area-chart>
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </div>

      <div v-if="languagesAreEnabled && showLanguages" class="detailcolumn globallanguages">
        <k-headline>{{ $t('simplestats.visits.globallanguages') }}</k-headline>
        <pie-chart
          :data="languageTotalHits"
          v-if="languageTotalHits.length > 0"
          :height="(this.sectionSize=='small')?'185px':(this.sectionSize=='large')?'225px':(this.sectionSize=='tiny')?'80px':'205px'"
          :library="pieOptions"
        />
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>

      </div>

<!--     </k-grid> -->
  </div>
</template>

<script>

export default {
  //extends: 'k-pages-section',
  data() {
    return {
      // PHP props (auto populated)
      headline: null,
      showFullInfo: false,
      showTotals: false,
      showTimeline: false,
      showLanguages: false,
      size: 'medium',

      // JS-only data props
      statsdata: null, // to remove later ?
      isLoading: false,
      error: "",
      languagesOverTime: [],
      visitsOverTime: [],
      languageTotalHits: [],
      //languagesAreEnabled: false,
      trackedSince: '[unknown]',
      totalHits: 0,
      averageHits: false,
      timespanUnitName: '[unknown]',
      trackingPeriods : false,
    }
  },
  props: {
//     size: {
//       type: String,
//       default: 'medium',
//     },
//     showFullInfo: {
//       type: Boolean,
//       default: true,
//     },
//       showTotals: false,
//       showTimeline: false,
//       showLanguages: false,
  },
/*
  props: {
    //leftcolumnwidth: "1/1",
  },
*/
  computed: {
    languagesAreEnabled(){
      return this.languagesOverTime && this.languagesOverTime.length > 0;
    },
    sectionSize(){
      if(this.size=='small'||this.size=='compact') return 'small';
      else if(this.size=='medium'||this.size=='normal') return 'medium';
      else if(this.size=='large'||this.size=='huge') return 'large';
      else if(this.size=='tiny') return 'tiny';
      return 'medium';
    },
    chartOptions(){
      return {
        scales: {
          xAxes: [{
            display: !(this.sectionSize=='small'||this.sectionSize=='tiny'),
            type: 'time',
            time: {
              unit: 'month',
              displayFormats: {
                  month: 'MMM YYYY'
              }
            },
            scaleLabel: {
              //labelString: '',
              //display: true,
            },
          }],
          yAxes: [{
            stacked: true,
            legend: true,
            scaleLabel: {
              //labelString: '',
              display: !(this.sectionSize=='small'||this.sectionSize=='tiny'),
            },
          }],
        },
        tooltips: {
          //enabled: false,
          position: 'nearest', // places tooltip on points in x-mode
          //mode: 'dataset',
          mode: 'x', // Tooltip sends all item on x axis, so we can sum them up
          callbacks: {
            footer: this.graphFooter,
          },
          //bodyAlign: '',
          bodyFontSize:   (this.sectionSize=='tiny')?9:(this.sectionSize=='small')?11:12,
          titleFontSize:  (this.sectionSize=='tiny')?9:(this.sectionSize=='small')?11:12,
          footerFontSize: (this.sectionSize=='tiny')?9:(this.sectionSize=='small')?11:12,
        },
        legend: {
          display: !(this.sectionSize=='small'||this.sectionSize=='tiny'), // hides labels on small
//           labels: {
//             filter: function(legenditem, chartdata){
//               return true;
//             }
//           },
        },
      };
    },
    pieOptions(){
      return {
        tooltips: {
          //enabled: false,
          //mode: 'dataset',
          //mode: 'x', // Tooltip sends all item on x axis, so we can sum them up
          callbacks: {
            //footer: this.pieFooter,
            label: this.pieLabel,
            //footer: this.pieLabel,
          }
        },
        //legend: {
        //  display: false,
        //},
      };
    },
  },
  components: {

  },
  created() {
    this.load().then(response => {
      // Php props
      this.headline       = response.headline;
      this.showFullInfo   = response.showFullInfo;
      this.showTotals     = response.showTotals;
      this.showTimeline   = response.showTimeline;
      this.showLanguages  = response.showLanguages;
      this.size           = response.size;

      // Other
      this.statsdata  = response.statsdata;
      this.languagesOverTime = response.statsdata.languagesOverTime;
      this.visitsOverTime = response.statsdata.visitsOverTime;
      this.languageTotalHits = response.statsdata.languageTotalHits;
      //this.languagesAreEnabled = response.statsdata.languagesAreEnabled;
      this.trackedSince = response.statsdata.firstVisited;
      this.lastVisited = response.statsdata.lastVisited;
      this.averageHits = response.statsdata.averageHits;
      this.totalHits = response.statsdata.totalHits;
      this.timespanUnitName = response.statsdata.timespanUnitName;
      this.trackingPeriods = response.statsdata.trackingPeriods;
    });
  },
  mounted() {
    //debugger;
  },
  methods: {
    // Custom tooltip
    graphFooter(tooltipItems, object) {
      let sum = 0;
      let numItems = 0;

      tooltipItems.forEach(function(tooltipItem) {
        //console.log('Tooltipitem=', tooltipItem.value);
        sum += parseInt(tooltipItem.value);
        numItems++;
      });
      if(numItems <= 1) return ''; // Don't show sum when too few elements
      return 'Total: ' + sum;
    },
//     pieFooter(tooltipItems, object) {
//       let sum = 0;
//       let numItems = 0;
//
//       tooltipItems.forEach(function(tooltipItem) {
//         //console.log('Tooltipitem=', tooltipItem.value);
//         sum += parseInt(tooltipItem.value);
//         numItems++;
//       });
//       if(numItems <= 1) return ''; // Don't show sum when too few elements
//       return 'Total: ' + sum;
//     },
    // Based on : https://stackoverflow.com/a/37260662/58565
    pieLabel(tooltipItem, data) {
      //get the concerned dataset
      var dataset = data.datasets[tooltipItem.datasetIndex];
      //console.log('Dataset=', dataset);
      //console.log('Data=', data, tooltipItem, data.labels[tooltipItem.index]);
      //calculate the total of this data set
      var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
        return previousValue + currentValue;
      });
      //get the current items value
      var currentValue = dataset.data[tooltipItem.index];
      //calculate the precentage based on the total and current item, also this does a rough rounding to give a whole number
      var percentage = Math.floor(((currentValue/total) * 100)+0.5);

      return data.labels[tooltipItem.index] + ': ' + currentValue + ' ('+percentage + "%)";
    }
  }
};
</script>

<style lang="scss">
.simplestatsonepagedetailssection {
  .detailcolumn {

    &.medium {
      margin-bottom: 0.6rem;
    }
    &.large {
      margin-bottom: 1.2rem;
    }
  }
  &.medium, &.large {
    .detailcolumn {
      display: inline-block;
      &.globallanguages {
        width: 30%;
        padding-left: 2em;
      }
      &.visitsovertime {
        width: 70%;
      }
    }
  }
}

/*
.k-column[data-width="1/2"], .k-column[data-width="1/1"], .k-column[data-width="1/2"] {
  .simplestatsonepagedetailssection {
    .detailcolumn {
}
*/

</style>
