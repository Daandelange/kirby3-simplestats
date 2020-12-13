<template>
  <div>
    <p><br/></p>
    <k-headline size="large">Visitors (current sessions)</k-headline>
    <k-box theme="info" text="These are active user sessions, used to count unique visits every 24H. Then, they are computed/dissociated, only keeping the minimal information visible in other tabs. " />


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
  </div>
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
    load(reload) {
      if (!reload) this.isLoading = true

      this.$api
        .get("simplestats/listvisitors")
        .then(response => {
          this.isLoading = false
          //this.options = response.options
          this.columns = response.data.columns
          //this.rows    = this.items(response.data.rows)
          this.rows    = response.data.rows
          //this.showSearch=true
          //console.warn(this.rows);
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
        })
    },
  }
};
</script>

<style lang="scss">

</style>
