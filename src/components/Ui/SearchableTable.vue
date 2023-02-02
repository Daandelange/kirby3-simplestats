<template>
  <div class="ss-table">
    <header class="k-section-header">
      <k-headline size="">
        {{ label }}
      </k-headline>
      <k-button-group class="ss-table-buttons">
        <k-input
          v-if="isSearchable"
          v-model="searchterm"
          :autofocus="false"
          :placeholder="$t('simplestats.table.filter', 'Filter items …')"
          type="text"
          class="k-models-section-search k-button"
          @keydown.esc="cancelSearch"
          icon="search"
        />
        <!-- <k-button icon="search" :text="$t('search')" @click="onSearchToggle" :responsive="true" /> -->
      </k-button-group>
    </header>

    <k-table
      :columns="columns"
      :rows="sortedFilteredRows"
      layout="table"
      :sortable="false"
      size="tiny"
      :empty="((searchterm && searchterm.length)?'No Search results...':$t('simplestats.nodatayet'))"
      @header="onHeaderClick"
    />
  </div>
</template>



<script>

export default {
  // k-table with search inspired by k-pages-section
  // Maybe later sortable too ?

  components: {
    
  },
  mounted(){

  },
  data() {
    // const thisRef = this;

    return {
      searchterm: null,
			// searching: false,
      sortBy: null,
      sortAsc: true,
    }
  },
  props: {
    label: {
      type: String,
      default: '',
    },
    columns: {
      type: Object,
      default(){
        return {};
      },
    },
    rows: {
      type: Array,
      default(){
        return [];
      },
    },
  },
  computed: {
    isSearchable(){
      // Not searchable when empty
      if( false === (this.rows && this.rows.length>=0) ) return false;
      // Not searchable when columns are empty
      if( false === (this.columns && Object.keys(this.columns).length>=0) ) return false;
      // Is there at least one searchable column ?
      return Object.values(this.columns).some((l) => l.search === true);
    },
    sortedFilteredRows(){
      return this.rows.filter( (value, index) => {
        if(!(this.searchterm?.length)) return true;
        for(const[colID, colAttrs] of Object.entries(this.columns)){
          if(colAttrs.search===true){
            if(typeof value[colID] === 'object') continue;
            const colValue = String(value[colID]);
            if(colValue && colValue.includes(this.searchterm)){
              return true;
            }
          }
        }
        return value.slug?.includes(this.searchterm);
      }).sort((a, b) => {
        // No sorting = keep same
        if(this.sortBy===null) return 0;
        
        const column = this.columns[this.sortBy];
        const valueA = (column.type==='number' || column.type==='percentage')?Number(a[this.sortBy]):String(a[this.sortBy]);
        const valueB = (column.type==='number' || column.type==='percentage')?Number(b[this.sortBy]):String(b[this.sortBy]);
        
        if(valueA > valueB) return this.sortAsc?-1:1;
        else if(valueA < valueB) return this.sortAsc?1:-1;
        return 0;
      });
    },
  },

  methods: {
    cancelSearch() {
			this.searchterm = null;
		},
    onHeaderClick(params){
      if(params.column.sortable){
        if(this.sortBy !== params.columnIndex){
          this.sortBy = params.columnIndex;
          this.sortAsc = true;
        }
        else if( this.sortAsc ){
          this.sortAsc = false;
        }
        else {
          this.sortBy = null;
        }
      }
    }
  },
};
</script>

<style lang="less">
.ss-table {

  .k-table {
    // Make native css smaller
    --table-row-height: 26px;

    .k-table-empty {
      padding: var(--spacing-4);
    }

    thead tr th {
      cursor: pointer;
    }
  }

  .k-item-figure.k-image-field-preview, .k-flag-field-preview {
    height: var(--table-row-height);
    width: var(--table-row-height);
    margin: 0 auto;
  }

  .k-table-index-column {
    display: none;
  }
  // .k-flag-field-preview {
    
  // }

  .ss-table-buttons {
    // right: 0;
    --padding-y: 0;
    --padding-x: 0;
  }
  // .ss-no-hover {
  //   &, &:hover {
  //     cursor: default;
  //   }
  // }
  .k-models-section-search.k-input {
    margin-bottom: 0;
  }
}
</style>
