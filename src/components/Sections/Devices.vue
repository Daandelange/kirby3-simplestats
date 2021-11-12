<template>
  <div>
    <k-view>

      <k-grid gutter="medium">
        <k-column width="1/3">
          <k-headline align="center">{{ $t('simplestats.devices.graph.devices') }}</k-headline>
          <pie-chart
            :data="devicesData"
            v-if="devicesData.length > 0"
          />
          <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
        </k-column>

        <k-column width="1/3">
        <k-headline align="center">{{ $t('simplestats.devices.graph.engines') }}</k-headline>
          <pie-chart
            :data="browsersData"
            v-if="browsersData.length > 0"
          />
          <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
        </k-column>

        <k-column width="1/3">
          <k-headline align="center">{{ $t('simplestats.devices.graph.oses') }}</k-headline>
          <pie-chart
            :data="systemsData"
            v-if="systemsData.length > 0"
          />
          <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
        </k-column>
      </k-grid>

      <k-grid gutter="medium">
        <k-column width="1/1">
          <br>
          <br>
          <k-headline align="center">{{ $t('simplestats.devices.graph.devicehistory') }}</k-headline>
          <area-chart
            :data="devicesOverTimeData"
            :download="true"
            download="Site_DevicesEvolution.png"
            label="Devices"
            :xtitle="$t('simplestats.charts.time')"
            :ytitle="$t('simplestats.devices.graph.devicehistory.y')"
            :stacked="true"
            :library="chartOptions"
            v-if="devicesOverTimeData.length > 0"
          ></area-chart>
          <k-empty v-else layout="block" class="emptyChart">{{ $t('simplestats.nodatayet') }}</k-empty>
        </k-column>
      </k-grid>
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

</style>
