<template>
  <k-panel-inside class="k-simplestats-view">
    <!-- Disclaimer -->
    <k-simplestats-disclaimer
      :visible="!dismissDisclaimer"
    />

    <!-- Header -->
    <k-header class="k-simplestats-view-header">
      {{ $t('simplestats.view') }}

      <template #buttons>
        <k-simplestats-timespan
          ref="timespan"
          :dateChoices="timeframes"
          :time-period="timePeriod"
          :initial-view-periods="initialViewPeriods"
        />
      </template>
    </k-header>

    <!-- Tabs -->
    <k-tabs :tab="tab" :tabs="tabsWithLinks" />

    <!-- Visits View -->
    <k-simplestats-visits-view
      v-if="tab === 'pagevisits'"
      :dateFrom="dateFrom"
      :dateTo="dateTo"
      path="pagestats"
    />

    <!-- Devices View -->
    <k-simplestats-devices-view
      v-else-if="tab === 'visitordevices'"
      :dateFrom="dateFrom"
      :dateTo="dateTo"
      path="devicestats"
    />

    <!-- Referrers View -->
    <k-simplestats-referrers-view
      v-else-if="tab === 'referers'"
      :dateFrom="dateFrom"
      :dateTo="dateTo"
      path="refererstats"
    />

    <!-- Information View -->
    <k-simplestats-info-view
      v-else-if="tab === 'information'"
    />

    <!-- Empty state -->
    <k-empty
      v-else
      layout="cards"
      :text="$t('simplestats.view.error')"
    />

    <!-- Invisible color getter util for CSS to JS style data transfer -->
    <div id="chart-default-color-getter"></div>
  </k-panel-inside>
</template>

<script>
import tabsNav from "../../mixins/tabsNav.js";

export default {
  mixins: [tabsNav],

  props: {
    timePeriod: {
      type: String,
      default: "Monthly"
    },
    timeframes: {
      type: Array,
      default: []
    },
    initialViewPeriods: {
      type: Number,
      default: -1
    },
    dismissDisclaimer: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      //dismissDisclaimer : false,
      //isLoading : false
    };
  },

  computed: {
    dateFrom() {
      return this.$refs.timespan?.dateFrom;
    },

    dateTo() {
      return this.$refs.timespan?.dateTo;
    }
  }
};
</script>

<style>
/* K4 header fix */
.k-simplestats-view .k-header:has(+ .k-simplestats-view-header) {
  margin-bottom: 0;
}

.k-simplestats-view #chart-default-color-getter {
  display: none;
}

@container (max-width: 30rem) {
  .k-simplestats-view .k-header {
    flex-direction: column;
  }
}
</style>
