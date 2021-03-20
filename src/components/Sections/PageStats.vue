<template>
  <div>
    <br />

    <k-grid style="margin: 0 1em;">
      <k-column>
        <k-headline size="medium">{{ $t('simplestats.visitsovertime') }}</k-headline>
        <br/>
<!--      <histogram :dataset="[visitsOverTimeData]" :labels="visitsOverTimeLabels" /> -->
        <area-chart
          :data="visitsOverTimeData"
          :download="true"
          download="Site_PageVisits.png"
          label="Unique visits"
          :xtitle="$t('simplestats.charts.time')"
          :ytitle="$t('simplestats.charts.visits')"
          height="150px"
        ></area-chart>
      </k-column>


      <k-column>
        <k-headline size="medium">{{ $t('simplestats.pagevisitsovertime') }}</k-headline>
        <br/>
        <column-chart
          :data="pageVisitsOverTimeData"
          :download="true"
          download="Site_PageVisits.png"
          label="Unique visits"
          :xtitle="$t('simplestats.charts.time')"
          :ytitle="$t('simplestats.charts.visits')"
          height="300px"
          :stacked="true"
          :library="chartOptions"
          v-if="pageVisitsOverTimeData.length > 0"
        ></column-chart>
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </k-column>

      <k-column width="3/4" v-if="languagesAreEnabled">
        <k-headline size="medium">{{ $t('simplestats.languagesovertime') }}</k-headline>
        <area-chart
          :data="languagesOverTimeData"
          :download="true"
          download="Site_GlobalLanguages.png"
          label="Language visits (any page)"
          :xtitle="$t('simplestats.charts.time')"
          :ytitle="$t('simplestats.charts.visits')"
          height="250px"
          :stacked="true"
          :library="chartOptions"
          v-if="languagesOverTimeData.length > 0"
        ></area-chart>
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </k-column>

      <k-column width="1/4" v-if="languagesAreEnabled">
        <k-headline>{{ $t('simplestats.globallanguages') }}</k-headline>
        <pie-chart
          :data="globalLanguagesData"
          v-if="globalLanguagesData.length > 0"
          height="250px"
        />
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </k-column>
      <br/>

      <k-column>
        <tbl
          :rows="rows"
          :columns="columns"
          :store="false"
          :search="true"
          :sort="true"
          :pagination="{}"
          :isLoading="isLoading"
          :options="{add:false,reset:false}"
          :headline="$t('simplestats.visitedpages')"
          v-if="rows.length > 0"
        >
          <!-- Default entryslot -->
          <template slot="column-$default" slot-scope="props">
            <p>
              {{ props.value }}
            </p>
          </template>
          <!-- percentage entryslot -->
          <template slot="column-hitspercent" slot-scope="props">
            <p v-bind:style="[ !props.value ? { width: '0%' } : { width: props.value + '%' }]"></p>
          </template>
          <!-- Timeframe date entryslot -->
          <template slot="column-firstvisited" slot-scope="props">
            <p>
              {{ new Date( props.value ).toLocaleString( userLocale, { month: "short" }) }} {{ new Date( props.value ).getFullYear() }}
            </p>
          </template>
          <!-- Timeframe date entryslot -->
          <template slot="column-lastvisited" slot-scope="props">
            <p>
              {{ new Date( props.value ).toLocaleString( userLocale, { month: "short" }) }} {{ new Date( props.value ).getFullYear() }}
            </p>
          </template>
          <!-- UID is HTML format -->
          <template slot="column-uid" slot-scope="props">
            <p v-html="props.value"></p>
          </template>
        </tbl>
        <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
      </k-column>

    </k-grid>
  </div>
</template>

<script>
// import Vue from 'vue'

import Tbl from 'tbl-for-kirby';

export default {
  extends: 'k-pages-section',
  data() {
    return {
      rows: [],
      columns: [],

      visitsOverTimeData:   [],
      pageVisitsOverTimeData:   [],

      languagesOverTimeData: [],
      globalLanguagesData: [],
      languagesAreEnabled: false,
      userLocale: 'en',

      chartOptions: {
        scales: {
          xAxes: [{
            //display: false,
            type: 'time',
            time: {
              unit: 'month',
              displayFormats: {
                  month: 'MMM YYYY'
              }
            }
          }],
          yAxes: [{
            stacked: true
          }]
        }
      },

      isLoading: false,
      error: "",
    }
  },
  components: {
    Tbl,
  },
  use() {

  },
  // see: https://forum.getkirby.com/t/can-i-use-vue-use-inside-my-plugin/17822
  // Also see https://github.com/getkirby/ideas/issues/219
  beforeCreate() {
  	// Instead of: Vue.use(PluginName)
  	//window.panel.app.$root.constructor.use(Chartkick.use(Chart), {});
  	//app.$root.constructor.use(Chartkick.use(Chart), {});
  },
  created() {
    this.load();
  },
  mounted() {
    //debugger;

  },
  methods: {
    load(reload) {

      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/pagestats")
        .then(response => {
          this.isLoading = false
          this.columns = response.pagestatslabels
          this.rows    = response.pagestatsdata

          this.visitsOverTimeData   = response.visitsovertimedata
          //this.visitsOverTimeLabels = response.visitsovertimelabels

          this.pageVisitsOverTimeData = response.pagevisitsovertimedata

          this.globalLanguagesData = response.globallanguagesdata
          this.languagesOverTimeData = response.languagesovertimedata
          this.languagesAreEnabled = response.languagesAreEnabled
          this.userLocale = this.$store.state.i18n.locale;// );//this.$store.state.languages.all.find(el => el.code=='en') ).url;
          //console.warn(response);
          //console.log(response.data.rows);
          // replace default translations if needed
//           let translations = response.translations
//           Object.keys(translations).forEach(k => {
//               if (translations[k] == null) delete translations[k]
//           })
//           this.translations = Object.assign({}, this.translations, translations)
        })
        .catch(error => {
          this.isLoading = false
          this.error = error.message

          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        })
    },
  }
};
</script>

<style lang="scss">
.emptyChart {
  min-height: 250px;
  margin-top: 1em;
  padding: 1em;
}


</style>
