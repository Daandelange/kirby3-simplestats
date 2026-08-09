<template>
  <div class="k-fieldset">
    <k-grid variant="columns">
      <!-- Headline -->
      <k-column width="1/1">
        <k-headline-field :label="$t('simplestats.info.config.headline')" />
      </k-column>

      <!-- Tracking Table -->
      <k-column width="1/1">
        <k-simplestats-info-table
          :label="$t('simplestats.info.config.tracking.label')"
          :rows="trackingRows"
        />

        <!-- Unsalted Warning -->
        <template v-if="!isLoading && !tracking.salted">
          <br />
          <k-box theme="negative" :text="$t('simplestats.info.config.tracking.unsalted.note')" />
        </template>
      </k-column>

      <!-- Logging Table -->
      <k-column width="1/1">
        <k-simplestats-info-table
          :label="$t('simplestats.info.config.logging.label')"
          :rows="loggingRows"
        />
      </k-column>
    </k-grid>
  </div>
</template>

<script>
import apiFetch from '../../../mixins/apiFetch.js';

export default {
  mixins: [apiFetch],

  data() {
    return {
      isLoading: true,

      tracking: {
        period: '',
        since: '',
        unique: '',
        salted: false,
        referers: false,
        devices: false,
        visits: false,
        languages: false,
        ignoredRoles: [],
        ignoredPages: [],
        ignoredTemplates: []
      },

      logging: {
        file: '',
        tracking: false,
        warnings: false,
        verbose: false
      }
    };
  },

  computed: {
    trackingRows() {
      return [
        {
          label: 'simplestats.info.config.tracking.period',
          value: this.tracking.period
        },
        {
          label: 'simplestats.info.config.tracking.unique',
          value: this.uniqueFormatted,
        },
        {
          label: 'simplestats.info.config.tracking.salted',
          preview: 'k-toggle-field-preview',
          value: this.tracking.salted
        },
        {
          label: 'simplestats.info.config.tracking.features',
          preview: 'k-tags-field-preview',
          value: this.trackingFeatures
        },
        {
          label: 'simplestats.info.config.tracking.ignore.roles',
          preview: 'k-tags-field-preview',
          value: this.tracking.ignoredRoles
        },
        {
          label: 'simplestats.info.config.tracking.ignore.pages',
          preview: 'k-tags-field-preview',
          value: this.tracking.ignoredPages
        },
        {
          label: 'simplestats.info.config.tracking.ignore.templates',
          preview: 'k-tags-field-preview',
          value: this.tracking.ignoredTemplates
        }
      ];
    },

    loggingRows() {
      return [
        {
          label: 'simplestats.info.config.logging.file',
          preview: 'k-files-field-preview',
          value: this.logging.file
        },
        {
          label: 'simplestats.info.config.logging.levels',
          preview: 'k-tags-field-preview',
          value: this.loggingLevels
        }
      ];
    },

    trackingFeatures() {
      return [
        ['referers', 'simplestats.info.config.tracking.feature.referers'],
        ['devices', 'simplestats.info.config.tracking.feature.devices'],
        ['visits', 'simplestats.info.config.tracking.feature.visits'],
        ['languages', 'simplestats.info.config.tracking.feature.languages']
      ]
        .filter(([key]) => this.tracking[key])
        .map(([_, label]) => this.$t(label));
    },

    loggingLevels() {
      return [
        ['tracking', 'simplestats.info.config.logging.level.tracking'],
        ['warnings', 'simplestats.info.config.logging.level.warnings'],
        ['verbose', 'simplestats.info.config.logging.level.verbose']
      ]
        .filter(([key]) => this.logging[key])
        .map(([_, label]) => this.$t(label));
    },

    uniqueFormatted() {
      const seconds = this.tracking.unique;
      const minutes = Math.floor((seconds % 3600) / 60);
      const hours   = Math.floor(seconds / 3600);

      return (
        [
          hours && `${hours}h`,
          minutes && `${minutes}m`
        ]
          .filter(Boolean)
          .join(' ')
      ) || '-';
    }
  },

  methods: {
    loadData(response) {
      Object.assign(this.tracking, {
        period: response.period,
        since: response.since,
        unique: response.unique,
        salted: response.salted,
        referers: response.features.referers,
        devices: response.features.devices,
        visits: response.features.visits,
        languages: response.features.languages,
        ignoredRoles: response.ignored.roles,
        ignoredPages: response.ignored.pages,
        ignoredTemplates: response.ignored.templates
      });

      Object.assign(this.logging, {
        file: response.logFile,
        tracking: response.logLevels.tracking,
        warnings: response.logLevels.warnings,
        verbose: response.logLevels.verbose
      });
    }
  }
};
</script>
