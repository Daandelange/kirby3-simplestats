<template>
  <div>

    <k-headline size="large">Database Information</k-headline>

    <k-grid>
      <!-- HISTORY -->
      <k-column width="1/2">
        <tbl
          :rows="dbHistory"
          :columns="dbHistoryLabels"
          :options="{showSearch:true}"
          :pagination="false"
          :isLoading="isLoading"
          :search="false"
          headline="Version History"
        >

          <!-- Default entryslot -->
          <template slot="column-$default" slot-scope="props">
            <p>
              {{ props.value }}
            </p>
          </template>

        </tbl>

        <k-headline>Database Requirements</k-headline>
        {{dbRequirements}}<br/>
      </k-column>

      <!-- UPGRADE -->
      <k-column width="1/2">
        <div class="upgrade">
          <k-headline>Version</k-headline>
          Current version : <strong>{{ dbVersion }}</strong><br/>
          Software version : <strong>{{ softwareDbVersion }}</strong><br/>
          <!-- TODO : add dependency check -->
          <!-- TODO : add db path & existence -->
          <br/>
          <div v-if="upgradeRequired">
            <div v-if="!updateMessage">
              <k-info-field label="Upgrade required" theme="negative" text="Your database needs to be upgraded.<br/>SimpleStats will not work until you've done this.<br/>" />
              <br/>
              <k-checkbox-input @input="acceptUpgrade" :value="unlockUpgrade" label="I have backed up my database, or I don't care about backups." />
              <br/>
              <k-button icon="bolt" @click="requestUpgrade">Upgrade database</k-button> <k-label v-if="isUpdatingDb">Loading...</k-label>
            </div>

            <div v-else>
              <k-info-field label="Upgrade Result" :text="updateMessage" :theme="updateMessageTheme" />
              <k-button @click="load" theme="neutral">Refresh db info</k-button>
            </div>
          </div>
          <div v-else-if="updateMessage==null">
            <k-info-field label="Up to date" text="Your database is up to date. &nbsp; :) " theme="positive" />
          </div>
          <div v-else-if="updateMessage!==null">
            <k-info-field label="Load Error" :text="updateMessage" theme="negative" />
          </div>
        </div>
      </k-column>
    <k-grid>
  </div>
</template>

<script>
import Tbl from 'tbl-for-kirby';

export default {
  extends: 'k-pages-section',
  data() {
    return {
      dbHistory: [],
      dbHistoryLabels: [],
      upgradeRequired: false,
      isLoading: false,
      error: "",
      softwareDbVersion: "unknown",
      dbVersion: "undefined",
      dbRequirements: "unknown",
      unlockUpgrade: false,
      isUpdatingDb: false,
      updateMessage: null,
      updateMessageTheme: "",
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
          this.dbRequirements   = response.dbRequirements
        })
        .catch(error => {
          this.dbRequirements = error.message
          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        })
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
  padding-left: 2em;

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
</style>
