<template>
  <div class="dbinformation">

    <k-headline size="large">{{ $t('simplestats.info.title') }}</k-headline>

    <k-headline>{{ $t('simplestats.info.db.title') }}</k-headline>
    <k-text-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.db.file')" :value="databaseLocation" icon="file-zip" />
    <k-text-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.db.size')" :value="databaseSize | prettyBytes" icon="download" />
    <k-number-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.db.dbversion')" :value="dbVersion" icon="bolt" />
    <k-number-field name="" :counter="false" :disabled="true" :label="$t('simplestats.info.db.softwareversion')" :value="softwareDbVersion" icon="bolt" />
    <br />
    <br />

    <!-- HISTORY -->
    <k-headline>
      {{ $t('simplestats.info.db.versionhistory') }}
    </k-headline>
    <vue-good-table
        :rows="dbHistory"
        :columns="dbHistoryLabels"
        styleClass="vgt-table condensed nosearch"
        max-height="500px"
        :fixed-header="false"
        compactMode
        :search-options="{enabled: false}"
        :pagination-options="{
          enabled: true,
          perPage: 5,
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

    <k-line-field />

    <k-headline>{{ $t('simplestats.info.dbreqs.title') }}</k-headline>
    <div v-if="dbRequirementsPassed">
      <k-info-field theme="positive" :text="$t('simplestats.info.dbreqs.positive')"></k-info-field>
    </div>
    <div v-else>
      <k-info-field theme="negative" :text="$t('simplestats.info.dbreqs.negative')"></k-info-field>
      <hr />
      {{ dbRequirements }}
    </div>
    <br/>


    <!-- UPGRADE -->
    <div class="upgrade">
      <br/>
      <br/>
      <div v-if="upgradeRequired">
        <div v-if="!updateMessage">
          <k-info-field :label="$t('simplestats.info.dbupdate.required')" theme="negative" :text="$t('simplestats.info.dbupdate.requiredmsg')" />
          <br/>
          <k-checkbox-input @input="acceptUpgrade" :value="unlockUpgrade" :label="$t('simplestats.info.dbupdate.isbackedup')" />
          <br/>
          <k-button icon="bolt" @click="requestUpgrade">{{ $t('simplestats.info.dbupdate.go') }}</k-button>
          <k-label v-if="isUpdatingDb">{{ $t('simplestats.loading') }}</k-label>
        </div>

        <div v-else>
          <k-info-field :label="$t('simplestats.info.dbupdate.result')" :text="updateMessage" :theme="updateMessageTheme" />
          <k-button @click="load" theme="neutral">{{ $t('simplestats.info.dbupdate.refresh') }}</k-button>
        </div>
      </div>
      <div v-else-if="updateMessage==null">
        <k-info-field :label="$t('simplestats.info.dbupdate.title')" :text="$t('simplestats.info.dbupdate.isuptodate')" theme="positive" />
      </div>
      <div v-else-if="updateMessage!==null">
        <k-info-field :label="$t('simplestats.loaderror')" :text="updateMessage" theme="negative" />
      </div>
    </div>
  </div>
</template>

<script>

// Todo: separate db and config into separate vue components, like visitor info

import { VueGoodTable } from 'vue-good-table';

export default {
  extends: 'k-pages-section',
  data() {
    return {
      isLoading: true,
      error: "",

      // Db stuff
      dbHistory: [],
      dbHistoryLabels: [],
      upgradeRequired: false,
      softwareDbVersion: "unknown",
      dbVersion: "undefined",
      dbRequirements: "unknown",
      dbRequirementsPassed: true,
      unlockUpgrade: false,
      isUpdatingDb: false,
      updateMessage: null,
      updateMessageTheme: "",
      databaseLocation : '',
      databaseSize : '',
    }
  },
  components: {
    VueGoodTable,
  },

  filters: {
    // from https://gist.github.com/james2doyle/4aba55c22f084800c199
    // usage: {{ file.size | prettyBytes }}
    prettyBytes: function (num) {
      num = Number(num);
      if (typeof num !== 'number' || isNaN(num)) {
        return '?? kb';
      }

      var exponent;
      var unit;
      var neg = num < 0;
      var units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

      if (neg) {
        num = -num;
      }

      if (num < 1) {
        return (neg ? '-' : '') + num + ' B';
      }

      exponent = Math.min(Math.floor(Math.log(num) / Math.log(1000)), units.length - 1);
      num = (num / Math.pow(1000, exponent)).toFixed(2) * 1;
      unit = units[exponent];

      return (neg ? '-' : '') + num + ' ' + unit;
    },
  },
  created() {
    this.load();
  },
  methods: {
    load() {
      this.updateMessage=null
      // Load DB info
      this.$api
        .get("simplestats/listdbinfo")
        .then(response => {
          this.isLoading        = false
          this.dbHistoryLabels  = response.dbHistoryLabels
          this.dbHistory        = response.dbHistory
          this.upgradeRequired  = response.upgradeRequired
          this.softwareDbVersion= response.softwareDbVersion
          this.dbVersion        = response.dbVersion
          this.databaseLocation = response.databaseLocation
          this.databaseSize     = response.databaseSize
          //this.dbRequirements   = response.dbRequirements
          //this.unlockUpgrade = true
          this.updateMessage    = null
          //console.log(response.data.rows);
        })
        .catch(error => {
          this.isLoading = false
          this.updateMessage = error.message
          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        });
      // Load requirements
      this.$api
        .get("simplestats/checkrequirements")
        .then(response => {
          this.isLoading            = false
          this.dbRequirements       = response.dbRequirements
          this.dbRequirementsPassed = response.dbRequirementsPassed
        })
        .catch(error => {
          this.isLoading      = false
          this.dbRequirements = error.message
          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        });
    },
    acceptUpgrade(v) {
      this.unlockUpgrade=v;
    },
    requestUpgrade(e) {
      e.stopPropagation();

      if(this.unlockUpgrade){
        this.isUpdatingDb=true;
        this.updateMessage=null;
        this.$api
          .get("simplestats/dbupgrade")
          .then(response => {
            this.isUpdatingDb=false;
            this.updateMessage=response.message;
            this.updateMessageTheme=response.status?'positive':'negative';
          })
          .catch(error => {
            this.isUpdatingDb = false
            this.error = error.message
            this.$store.dispatch("notification/open", {
              type: "error",
              message: error.message,
              timeout: 5000
            });
          });
      }
      else {
        this.$store.dispatch("notification/open", {
          type: "error",
          message: "Before hitting that button, please ensure to backup your database file !",
          timeout: 5000
        });
      }
    },
  },
};
</script>

<style lang="scss">
.upgrade {

  .k-button {
    margin-top: .2em;
    border: 2px solid #eabb00;
    padding: .5em 1em;
    border-radius: .4em;
    background-color: #DDD;
    //text-transform: uppercase;
    font-size: 1em;
    line-height: .8em;
    font-weight: bold;

    .k-icon {
      color: #cf8a00;
    }
  }
}

.dbinformation {
  .k-field:not(.k-info-field) {
    display: flex;

    .k-field-label {
      font-weight: normal;
      font-size: .9em;
      line-height: 1em;
      align-items: flex-end;
      border-bottom: 1px rgba(0,0,0,.1) dashed;
    }
    .k-field-header {
      //display: inline-block;
      width: 40%;
      float: left;
      align-items: flex-end;
    }
    .k-input {
      //display: inline-block;
      width: 60%;
      float: left;

      &[data-disabled] {
        background-color: white;
      }
    }
    &[data-disabled] {
      cursor: inherit;
    }
  }
  .rightColumnAlign {
    margin-left: 40%;
    display: block;
  }
}
</style>
