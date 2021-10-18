<template>
  <k-grid>
    <k-column>
      <p><br/></p>
      <k-headline size="large">{{ $t('simplestats.info.config.currentusers.title', 'Visitors (current sessions)') }}</k-headline>

      <vue-good-table
        :rows="rows"
        :columns="columns"
        styleClass="vgt-table condensed"
        max-height="500px"
        :fixed-header="false"
        compactMode
        :search-options="{enabled: true, placeholder: $t('simplestats.table.filter', 'Filter items...')}"
        :pagination-options="{
          enabled: true,
          perPage: 20,
          perPageDropdownEnabled: false,
          nextLabel: $t('simplestats.table.pages.next', 'Next'),
          prevLabel: $t('simplestats.table.pages.prev', 'Previous'),
          ofLabel: $t('simplestats.table.pages.of', 'of'),
        }"
      >
        <div slot="emptystate">
          <k-empty>
            {{ $t('simplestats.nodatayet') }}
          </k-empty>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'timefrom'">
            <span>
              {{ props.formattedRow[props.column.field] }}
<!-- (old way)                 {{ new Date( props.row.firstvisited ).toLocaleString( userLocale, { month: "short" }) }} {{ new Date( props.row.firstvisited ).getFullYear() }} -->
            </span>
          </span>
          <span v-else>
            {{ props.formattedRow[props.column.field] }}
          </span>
        </template>
      </vue-good-table>
      <k-box theme="info" :text="$t('simplestats.info.config.currentusers.info')" />
    </k-column>
  </k-grid>
</template>

<script>
import { VueGoodTable } from 'vue-good-table';

export default {
  extends: 'k-pages-section',
  data() {
    return {
      rows: [],
      columns: [],
      isLoading: false,
      error: "",
    }
  },
  components: {
    VueGoodTable,
  },
  created() {
    this.load();
  },

  methods: {
    load() {

      this.$api
        .get("simplestats/listvisitors")
        .then(response => {
          this.isLoading = false
          this.columns = response.data.columns
          this.rows    = response.data.rows

          //console.log(response.data.rows);
          // replace default translations if needed
//           let translations = response.translations
//           Object.keys(translations).forEach(k => {
//               if (translations[k] == null) delete translations[k]
//           })
//           this.translations = Object.assign({}, this.translations, translations)
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

</style>
