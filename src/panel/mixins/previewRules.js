/**
 * @internal
 * Preview Rules Mixin
 *
 * This mixin defines how different Kirby Panel field previews
 * should be rendered inside Simplestats InfoTable component.
 *
 * Extend by adding a new resolver method and mapping it
 * inside `resolvePreview()`.
 */
export default {
  methods: {
    resolveTextPreview(rowData) {
      return {
        props: { column: rowData.column }
      };
    },

    resolveTogglePreview() {
      return {
        props: { field: { disabled: true } }
      };
    },

    resolveTagsPreview(rowData) {
      return {
        value: Array.isArray(rowData.value) ? rowData.value : []
      };
    },

    resolveFilesPreview(rowData) {
      return {
        value: Array.isArray(rowData.value)
          ? rowData.value
          : [{
              image: { icon: 'file', back: 'black', color: 'white' },
              filename: rowData.value
            }]
      };
    },

    resolvePreview(preview, rowData) {
      const rules = {
        'k-text-field-preview': this.resolveTextPreview,
        'k-toggle-field-preview': this.resolveTogglePreview,
        'k-tags-field-preview': this.resolveTagsPreview,
        'k-files-field-preview': this.resolveFilesPreview
      };

      const resolver = rules[preview];
      return resolver ? resolver(rowData) : {};
    }
  }
};
