<template>
  <div>
    <k-view>

      <k-grid gutter="medium">
        <k-column width="1/3">
          <k-headline align="center">Devices</k-headline>
          <pie-chart
            :data="devicesData"
            v-if="devicesData.length > 0"
          />
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>

        <k-column width="1/3">
        <k-headline align="center">Browser Engines</k-headline>
          <pie-chart
            :data="browsersData"
            v-if="browsersData.length > 0"
          />
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>

        <k-column width="1/3">
          <k-headline align="center">Operating Systems</k-headline>
          <pie-chart
            :data="systemsData"
            v-if="systemsData.length > 0"
          />
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>
      </k-grid>

      <k-grid gutter="medium">
        <k-column width="1/1">
          <br>
          <br>
          <k-headline align="center">Devices over time</k-headline>
          <area-chart
            :data="devicesOverTimeData"
            :download="true"
            download="Site_DevicesEvolution.png"
            :diiiiscrete="true"
            label="Devices"
            xtitle="Time"
            ytitle="Referer Hits by medium"
            :stacked="true"
            :library="chartOptions"
            v-if="devicesOverTimeData.length > 0"
          ></area-chart>
          <k-empty v-else layout="block" class="emptyChart">No data yet</k-empty>
        </k-column>

    </k-view>
  </div>
</template>

<script>


export default {
  components: {
    //
  },

  data() {
    return {
      devicesData: [],
      devicesOverTimeData: [],
      browsersData: [],
      systemsData: [],

      isLoading: false,
      error: "",

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
    }
  },

  created() {
    this.load();
  },

  methods: {
    load(reload) {
      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/devicestats")
        .then(response => {
          this.isLoading = false
          this.devicesData   = response.devicesdata
          this.browsersData   = response.enginesdata

          this.devicesOverTimeData = response.devicesovertime

          this.systemsData   = response.systemsdata
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
.row-percent {
  //background-color: #bbb;
}
.row-percent p {
  background-color: rgba(46, 64, 87,1);
  color: white;
  display: inline-block;
  height: 4px;
  position: relative;
}
</style>
