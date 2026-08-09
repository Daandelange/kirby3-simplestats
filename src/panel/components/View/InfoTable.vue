<template>
  <k-section :label="label">
    <div class="k-simplestats-info-table k-table">
      <table>
        <tbody>
          <tr v-for="(row, i) in previewData" :key="i">
            <th data-mobile="true">{{ $t(row.label) }}</th>
            <td data-mobile="true" class="k-table-cell">
              <preview
                :is="row.preview"
                :value="row.value"
                v-bind="row.props"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </k-section>
</template>

<script>
import previewRules from '../../mixins/previewRules.js';

export default {
  mixins: [previewRules],

  props: {
    label: String,
    rows: {
      type: Array,
      default: () => []
    }
  },

  computed: {
    previewData() {
      return this.rows.map(this.normalizeRow);
    }
  },

  methods: {
    normalizeRow(row) {
      const preview = row.preview || 'k-text-field-preview';
      const rawValue = row.value;
      const formattedValue = row.format ? row.format(rawValue) : rawValue;

      const resolved = this.resolvePreview(preview, { ...row, value: formattedValue });

      return { ...row, preview, value: resolved.value ?? formattedValue, props: resolved.props };
    }
  }
};
</script>

<style>
.k-simplestats-info-table {
  overflow: auto;
}

.k-simplestats-info-table table {
  table-layout: auto;
}
</style>
