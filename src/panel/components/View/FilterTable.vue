<template>
  <k-section :label="label" class="k-simplestats-filter-table">
    <k-button
      v-if="isSearchable"
      slot="options"
      icon="filter"
      size="xs"
      variant="filled"
      :text="$t('simplestats.table.filter')"
      @click="toggleSearch"
    />

    <k-input
      v-if="isSearchable && showSearch"
      ref="searchInput"
      v-model="searchTerm"
      icon="search"
      type="text"
      class="k-models-section-search"
      :placeholder="$t('simplestats.table.filter.placeholder')"
    />

    <k-table
      :index="false"
      :columns="columns"
      :rows="sortedFilteredRows"
      :empty="emptyText"
      @header="onHeaderClick"
      @cell="onCellClick"
    />
  </k-section>
</template>

<script>
export default {
  props: {
    label: String,
    columns: Object,
    rows: {
      type: Array,
      default: () => []
    }
  },

  data() {
    return {
      searchTerm: '',
      showSearch: false,
      sortBy: null,
      sortAsc: true
    };
  },

  computed: {
    isSearchable() {
      return Object.values(this.columns).some(col => col.search);
    },

    emptyText() {
      return this.searchTerm
        ? this.$t('simplestats.table.filter.noresults')
        : this.$t('simplestats.table.empty');
    },

    searchableColumns() {
      return Object.entries(this.columns)
        .filter(([, col]) => col.search)
        .map(([key]) => key);
    },

    sortedFilteredRows() {
      const filtered = this.filterRows(this.rows);
      return this.sortRows(filtered);
    }
  },

  watch: {
    showSearch(val) {
      if (!val) return;

      this.$nextTick(() => {
        this.$refs.searchInput?.focus();
      });
    }
  },

  methods: {
    toggleSearch() {
      this.showSearch = !this.showSearch;
    },

    filterRows(rows) {
      if (!this.searchTerm) return rows;

      const term = this.searchTerm.toLowerCase();

      return rows.filter(row => {
        if (row.slug?.toLowerCase().includes(term)) return true;

        return this.searchableColumns.some(key => {
          const value = row[key];
          if (value == null || typeof value === 'object') return false;
          return String(value).toLowerCase().includes(term);
        });
      });
    },

    sortRows(rows) {
      if (!this.sortBy) return rows;

      const { type } = this.columns[this.sortBy];

      return [...rows].sort((a, b) => {
        const valueA = this.normalizeSortValue(a[this.sortBy], type);
        const valueB = this.normalizeSortValue(b[this.sortBy], type);

        if (valueA === valueB) return 0;
        return (valueA > valueB ? 1 : -1) * (this.sortAsc ? 1 : -1);
      });
    },

    normalizeSortValue(value, type) {
      if (type === 'number' || type === 'percentage') {
        return Number(value) || 0;
      };

      return String(value ?? '').toLowerCase();
    },

    onHeaderClick({ column, columnIndex }) {
      if (!column.sortable) return;

      if (this.sortBy !== columnIndex) {
        this.sortBy = columnIndex;
        this.sortAsc = true;
        return;
      };

      this.sortAsc = !this.sortAsc;
    },
    onCellClick(row, rowIndex){
      this.$emit('row', row, rowIndex);
    },
  }
};
</script>

<style>
.k-simplestats-filter-table .k-table thead tr th {
  cursor: pointer;
}
</style>
