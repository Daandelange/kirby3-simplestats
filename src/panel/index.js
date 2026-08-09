// Views
import View from "./components/Views/View.vue";
import VisitsView from "./components/Views/VisitsView.vue";
import DevicesView from "./components/Views/DevicesView.vue";
import ReferrersView from "./components/Views/ReferrersView.vue";
import InfoView from "./components/Views/Information/InfoView.vue";
import InfoConfig from "./components/Views/Information/InfoConfig.vue";
import InfoDatabase from "./components/Views/Information/InfoDatabase.vue";
import InfoTesters from "./components/Views/Information/InfoTesters.vue";
import InfoVisitors from "./components/Views/Information/InfoVisitors.vue";
import PagestatsDrawer from "./components/Drawers/PagestatsDrawer.vue";

// Components
import Chart from "./components/View/Chart.vue";
import Disclaimer from "./components/View/Disclaimer.vue";
import FilterTable from "./components/View/FilterTable.vue";
import InfoTable from "./components/View/InfoTable.vue";
import Timespan from "./components/View/Timespan.vue";

// Table Previews
import PercentageFieldPreview from "./components/Previews/PercentageFieldPreview.vue";

// Sections
import PagestatsSection from "./components/Sections/PagestatsSection.vue";

panel.plugin("daandelange/simplestats", {
  components: {
    "k-percentage-field-preview": PercentageFieldPreview,

    "k-simplestats-chart": Chart,
    "k-simplestats-disclaimer": Disclaimer,
    "k-simplestats-filter-table": FilterTable,
    "k-simplestats-info-table": InfoTable,
    "k-simplestats-timespan": Timespan,

    "k-simplestats-view": View,
    "k-simplestats-visits-view": VisitsView,
    "k-simplestats-devices-view": DevicesView,
    "k-simplestats-referrers-view": ReferrersView,
    "k-simplestats-info-view": InfoView,
    "k-simplestats-info-config": InfoConfig,
    "k-simplestats-info-database": InfoDatabase,
    "k-simplestats-info-testers": InfoTesters,
    "k-simplestats-info-visitors": InfoVisitors,
    "k-simplestats-pagestats-drawer": PagestatsDrawer,
  },

  sections: {
    "pagestats": PagestatsSection,
  },
});
