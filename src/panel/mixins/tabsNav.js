/**
 * @internal
 * Tabs Navigation Mixin
 */
import { usePanel } from "kirbyuse";

export default {
  props: {
    initialTab: {
      type: String,
      default: "pagevisits"
    },
    tabs: {
      type: Array,
      default: () => []
    }
  },

  data() {
    return { tab: "" };
  },

  computed: {
    tabsWithLinks() {
      return this.tabs.map(tab => ({
        ...tab,
        click: () => this.selectTab(tab.name)
      }));
    }
  },

  watch: {
    initialTab(value) {
      if (value) this.selectTab(value);
    }
  },

  mounted() {
    this.selectTab();
  },

  methods: {
    getStoredTab() {
      try {
        return localStorage.getItem("ss-tabs-menu");
      } catch {
        return null;
      }
    },

    storeTab(tab) {
      try {
        localStorage.setItem("ss-tabs-menu", tab);
      } catch {
        /* ignore */
      }
    },

    selectTab(tab) {
      const tabNames = this.tabs.map(t => t.name);
      const resolved = tab || this.getStoredTab() || this.initialTab || tabNames[0];

      this.tab = tabNames.includes(resolved) ? resolved : tabNames[0];

      this.updateBreadcrumb();
      this.storeTab(this.tab);
    },

    updateBreadcrumb() {
      const panel = usePanel();
      const label = this.tabs.find(t => t.name === this.tab)?.label || this.tab;
      panel.view.breadcrumb[0].label = label;
    }
  }
};
