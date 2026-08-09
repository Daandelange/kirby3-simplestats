<template>
  <div class="k-fieldset">
    <k-grid variant="columns">
      <!-- Headline -->
      <k-column width="1/1">
        <k-headline-field :label="$t('simplestats.info.visitors.headline')" />
      </k-column>

      <!-- Visitors Table -->
      <k-column width="1/1">
        <k-simplestats-filter-table
          :label="$t('simplestats.info.visitors.label')"
          :rows="rows"
          :columns="columns"
        />

        <br />
        <k-box theme="info" :text="$t('simplestats.info.visitors.note')" />
      </k-column>
    </k-grid>
  </div>
</template>

<script>
import apiFetch from '../../../mixins/apiFetch.js';

export default {
  mixins: [apiFetch],

  data() {
    return {
      isLoading: true,

      rows: [],
      columns: {}
    };
  },

  methods: {
    loadData(response) {
      this.columns = response.columns;
      this.rows = response.rows;
    }
  }
};
</script>
