<template>
  <div class="k-fieldset">
    <k-grid variant="columns">
      <!-- Headline -->
      <k-column width="1/1">
        <k-headline-field :label="$t('simplestats.info.database.headline')" />
      </k-column>

      <!-- Database Info Table -->
      <k-column width="1/1">
        <k-simplestats-info-table
          :label="$t('simplestats.info.database.info.label')"
          :rows="dbInfoRows"
        />
      </k-column>

      <!-- Database Version History -->
      <k-column width="1/1">
        <k-table
          :label="$t('simplestats.info.database.history.label')"
          :index="false"
          :rows="database.history"
          :columns="dbHistory"
        />
      </k-column>

      <!-- Database Requirements -->
      <k-column width="1/1">
        <k-section :label="$t('simplestats.info.database.rqmts.label')">
          <k-box
            v-if="requirements.passed"
            theme="text"
            :text="$t('simplestats.info.database.rqmts.note.positive')"
          />

          <k-box v-else theme="negative">
            <k-text>
              <p>{{ $t('simplestats.info.database.rqmts.note.negative') }}</p>
              <k-code>{{ requirements.message }}</k-code>
            </k-text>
          </k-box>
        </k-section>
      </k-column>

      <!-- Database Upgrade -->
      <k-column width="1/1">
        <k-section :label="$t('simplestats.info.database.upgrade.label')">
          <template v-if="upgrade.required">
            <template v-if="!upgrade.resultMessage">
              <k-box :html="true" theme="negative" :text="$t('simplestats.info.database.upgrade.note.negative')" />
              <br/>
              <k-bar>
                <k-checkbox-input
                  v-model="upgrade.consent"
                  :label="$t('simplestats.info.database.upgrade.consent')"
                />
                <k-button
                  variant="filled"
                  :disabled="!upgrade.consent"
                  :icon="upgrade.isUpgrading ? 'loader' : null"
                  :text="$t('simplestats.info.database.upgrade.button')"
                  @click="requestUpgrade"
                />
              </k-bar>
            </template>

            <k-box v-else :html="true" :theme="upgrade.resultTheme" :text="upgrade.resultMessage" />
          </template>

          <k-box v-else theme="text" :text="$t('simplestats.info.database.upgrade.note.positive')" />
        </k-section>
      </k-column>
    </k-grid>
  </div>
</template>

<script>
export default {
  data() {
    return {
      database: {
        history: [],
        historyLabels: {},
        version: 'undefined',
        softwareVersion: 'unknown',
        location: '',
        size: '',
        spanFrom: '',
        spanTo: '',
        timeframes: -1
      },

      requirements: {
        passed: true,
        message: 'unknown'
      },

      upgrade: {
        required: false,
        consent: false,
        isUpgrading: false,
        resultMessage: null,
        resultTheme: ''
      }
    };
  },

  created() {
    this.loadDbInfo();
    this.loadRequirements();
  },

  computed: {
    dbInfoRows() {
      return [
        {
          label: 'simplestats.info.database.info.file',
          preview: 'k-files-field-preview',
          value: this.database.location
        },
        {
          label: 'simplestats.info.database.info.size',
          value: this.database.size,
          format: this.niceSize
        },
        {
          label: 'simplestats.info.database.info.version.db',
          value: this.database.version
        },
        {
          label: 'simplestats.info.database.info.version.sw',
          value: this.database.softwareVersion
        },
        {
          label: 'simplestats.info.database.info.span.from',
          preview: 'k-date-field-preview',
          value: this.database.spanFrom
        },
        {
          label: 'simplestats.info.database.info.span.to',
          preview: 'k-date-field-preview',
          value: this.database.spanTo
        },
        {
          label: 'simplestats.info.database.info.span.periods',
          value: this.database.timeframes
        },
      ];
    },

    dbHistory() {
      return Object.fromEntries(
        Object.entries(this.database.historyLabels).map(([key, column]) => [key, { ...column, mobile: true }])
      );
    }
  },

  methods: {
    async loadDbInfo() {
      const response = await this.$api.get('simplestats/database/info');
      Object.assign(this.database, {
        history: response.dbHistory,
        historyLabels: response.dbHistoryLabels,
        version: response.dbVersion,
        softwareVersion: response.softwareDbVersion,
        location: response.databaseLocation,
        size: response.databaseSize,
        spanFrom: response.databaseSpanFrom,
        spanTo: response.databaseSpanTo,
        timeframes: response.databaseTimeframes
      });
      this.upgrade.required = response.upgradeRequired;
    },

    async loadRequirements() {
      const response = await this.$api.get('simplestats/database/requirements');
      this.requirements.message = response.dbRequirements;
      this.requirements.passed = response.dbRequirementsPassed;
    },

    async requestUpgrade() {
      this.upgrade.isUpgrading = true;

      try {
        const response = await this.$api.get('simplestats/database/upgrade');
        this.upgrade.resultTheme = response.status ? 'positive' : 'negative';
        this.upgrade.resultMessage = response.message;
      } finally {
        this.upgrade.isUpgrading = false;
      }
    },

    niceSize(num) {
      num = Number(num);
      if (!Number.isFinite(num)) return '?? B';
      const units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
      const neg = num < 0 ? '-' : '';
      if (neg) num = Math.abs(num);
      if (num < 1000) return `${neg}${num} B`;
      const exp = Math.min(Math.floor(Math.log(num) / Math.log(1000)), units.length - 1);
      const value = (num / Math.pow(1000, exp)).toFixed(2);
      return `${neg}${value} ${units[exp]}`;
    }
  }
};
</script>
