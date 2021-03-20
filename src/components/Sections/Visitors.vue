<template>
  <k-grid>
    <k-column>
      <p><br/></p>
      <k-headline size="large">Visitors (current sessions)</k-headline>

      <tbl
        :rows="rows"
        :columns="columns"
        :ssstore="false"
        :sssearch="false"
        :options="{showSearch:true}"
        :pppagination="false"
        :isLoading="isLoading"
        :search="true"
      >
        <!-- Custom headline: title
        <template slot="headline">
          <k-header>Simple Stats</k-header>
        </template> -->

        <!-- Default entryslot -->
        <template slot="column-$default" slot-scope="props">
          <p>
            {{ props.value }}
          </p>
        </template>

      </tbl>
      <k-box theme="info" text="These are active user sessions, used to count unique visits every 24H. Then, they are computed/dissociated, only keeping the minimal information visible in other tabs. " />
    </k-column>
  </k-grid>
</template>

<script>
import Tbl from 'tbl-for-kirby';

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
    Tbl
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
