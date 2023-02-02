<template>
  <k-grid>
    <k-column>
      <k-headline size="large">{{ $t('simplestats.info.config.currentusers.title', 'Visitors (current sessions)') }}</k-headline>

      <searchable-table
        :rows="rows"
        :columns="columns"
      />

      <k-box theme="info" :text="$t('simplestats.info.config.currentusers.info')" />
    </k-column>
  </k-grid>
</template>

<script>

import SectionBase from '../Sections/SimpleStatsSectionBase.vue';
import SearchableTable from '../Ui/SearchableTable.vue';

export default {
  extends: SectionBase,
  data() {
    return {
      rows: [],
      columns: {},
    }
  },
  components: {
    SearchableTable,
  },

  methods: {
    loadData(response) {
      this.columns = response.columns;
      this.rows    = response.rows;
    },
  },
};
</script>

<style lang="scss">

</style>
