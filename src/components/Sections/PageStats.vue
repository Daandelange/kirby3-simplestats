<template>
  <div>
    <br />

    <k-grid style="margin: 0 1em;">
      <k-column>
        <k-headline size="medium">{{ $t('simplestats.visits.visitsovertime', 'Visits over time') }}</k-headline>
        <br/>
<!--      <histogram :dataset="[visitsOverTimeData]" :labels="visitsOverTimeLabels" /> -->
        <area-chart
          :data="visitsOverTimeData"
          :download="true"
          download="Site_Visits.png"
          :xtitle="$t('simplestats.charts.time', 'Time')"
          :ytitle="$t('simplestats.charts.visits', 'Visits')"
          height="150px"
        ></area-chart>
      </k-column>


      <k-column>
        <k-headline size="medium">{{ $t('simplestats.visits.pagevisitsovertime') }}</k-headline>
        <br/>
        <column-chart
          :data="pageVisitsOverTimeData"
          :download="true"
          download="Site_PageVisits.png"
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
        <k-headline size="medium">{{ $t('simplestats.visits.languagesovertime') }}</k-headline>
        <area-chart
          :data="languagesOverTimeData"
          :download="true"
          download="Site_GlobalLanguages.png"
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
        <k-headline>{{ $t('simplestats.visits.globallanguages') }}</k-headline>
        <pie-chart
          :data="globalLanguagesData"
          v-if="globalLanguagesData.length > 0"
          height="250px"
        />
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </k-column>
      <br/>

      <k-column>
        <div v-if="rows.length > 0">
          <br />
          <br />
          <k-headline
            size=""
          >
            {{ $t('simplestats.visits.visitedpages') }}
          </k-headline>
          <vue-good-table
            :columns="columns"
            :rows="rows"
            styleClass="vgt-table condensed"
            max-height="500px"
            :fixed-header="false"
            compactMode
            :search-options="{enabled: true, placeholder: $t('simplestats.table.filter', 'Filter items...')}"
            :pagination-options="{
              enabled: true,
              perPage: 20,
              perPageDropdownEnabled: false,
              nextLabel: $t('simplestats.table.pages.next', 'Next'),
              prevLabel: $t('simplestats.table.pages.prev', 'Previous'),
              ofLabel: $t('simplestats.table.pages.of', 'of'),
            }"
          >
            <div slot="emptystate">
              <k-empty>
                {{ $t('simplestats.nodatayet') }}
              </k-empty>
            </div>

            <template slot="table-row" slot-scope="props">
              <span v-if="props.column.field == 'title'">
                <a :href="props.row.url" :style="{paddingLeft: 12*props.row.depth + 'px'}">{{ props.row.title }}</a>
              </span>
              <span v-else-if="props.column.field == 'uid'">
                <a :href="props.row.url" :style="{paddingLeft: 12*props.row.depth + 'px'}"><k-icon :type="props.row.icon"/>{{ props.row.uid }}</a>
              </span>
              <span v-else-if="props.column.field == 'hitspercent'" class="row-percent">
                <span class="visualiser" :style="{ width: props.row.hitspercent *100 + '%'}"></span>
                <span class="number">{{ (props.row.hitspercent * 100).toFixed(0) + '%' }}</span>
              </span>
              <span v-else-if="props.column.field == 'firstvisited'">
                <span>
                  {{ props.formattedRow[props.column.field] }}
<!-- (old way)                 {{ new Date( props.row.firstvisited ).toLocaleString( userLocale, { month: "short" }) }} {{ new Date( props.row.firstvisited ).getFullYear() }} -->
                </span>
              </span>
              <span v-else-if="props.column.field == 'lastvisited'">
                <span>
                  {{ props.formattedRow[props.column.field] }}
<!-- (old way)                 {{ new Date( props.row.lastvisited ).toLocaleString( userLocale, { month: "short" }) }} {{ new Date( props.row.lastvisited ).getFullYear() }} -->
                </span>
              </span>
              <span v-else>
                {{ props.formattedRow[props.column.field] }}
              </span>
            </template>
          </vue-good-table>
        </div>
        <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
      </k-column>

    </k-grid>
  </div>
</template>

<script>
// import Vue from 'vue'

// Good Table
import { VueGoodTable } from 'vue-good-table';

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
    VueGoodTable,
  },
  //use() {
  //
  //},

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
          this.userLocale = window.panel.$language ? window.panel.$language.locale : (this.$store.state.i18n ? this.$store.state.i18n.locale : '');
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
