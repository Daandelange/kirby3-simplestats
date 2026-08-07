<template>
  <div class="k-fieldset k-simplestats-info-testers">
    <k-grid variant="columns">
      <!-- Headline -->
      <k-column width="1/1">
        <k-headline-field :label="$t('simplestats.info.testers.headline')" />
      </k-column>

      <!-- LEFT COLUMN: Device & Referer -->
      <k-column width="1/2">
        <!-- Device Testing -->
        <k-section :label="$t('simplestats.info.testers.device.label')">
          <div class="k-table">
            <table>
              <tbody>
                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.device.current.useragent') }}</th>
                  <td data-mobile="true" colspan="2" class="k-table-cell">
                    <k-text-field-preview :value="currentUserAgent" />
                  </td>
                </tr>

                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.device.current.detected') }}</th>
                  <td data-mobile="true" colspan="2" class="k-table-cell">
                    <k-text-field-preview :value="this.formatDevice(this.currentDevice)" />
                  </td>
                </tr>

                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.device.custom.useragent') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-text-input v-model="customUserAgent" class="k-text-field-preview" />
                  </td>
                  <td data-mobile="true" class="k-table-button">
                    <k-button
                      size="sm"
                      variant="filled"
                      :disabled="!customUserAgent"
                      :text="$t('simplestats.info.testers.button')"
                      @click="testUserAgent"
                    />
                  </td>
                </tr>

                <tr v-if="customDevice">
                  <th data-mobile="true">{{ $t('simplestats.info.testers.device.custom.detected') }}</th>
                  <td data-mobile="true" colspan="2" class="k-table-cell">
                    <k-text-field-preview :value="this.formatDevice(this.customDevice)" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <template v-if="customDevice">
            <br />
            <k-box theme="info" :text="$t('simplestats.info.testers.device.note')" />
          </template>
        </k-section>

        <!-- Referer Testing -->
        <k-section :label="$t('simplestats.info.testers.referer.label')">
          <div class="k-table">
            <table>
              <tbody>
                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.referer.field') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-url-input v-model="refererUrl" class="k-text-field-preview" />
                  </td>
                  <td data-mobile="true" class="k-table-button">
                    <k-button
                      size="sm"
                      variant="filled"
                      :disabled="!refererUrl"
                      :text="$t('simplestats.info.testers.button')"
                      @click="testReferer"
                    />
                  </td>
                </tr>

                <tr v-if="hasValidReferer" v-for="row in refererInfo" :key="row.key">
                  <th data-mobile="true">{{ $t(row.label) }}</th>
                  <td data-mobile="true" colspan="2" class="k-table-cell">
                    <k-text-field-preview :value="row.value" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <template v-if="refererResponse?.error">
            <br />
            <k-box theme="negative" :text="refererResponse.error" />
          </template>
        </k-section>

      </k-column>

      <!-- RIGHT COLUMN: Stats Generator -->
      <k-column width="1/2">
        <k-section :label="$t('simplestats.info.testers.generator.label')">
          <div class="k-table">
            <table>
              <tbody>
                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.generator.mode') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-select-input v-model="generatorMode" :options="generatorModes"/>
                  </td>
                </tr>

                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.generator.date.from') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-date-input v-model="generatorFrom" />
                  </td>
                </tr>

                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.generator.date.to') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-date-input v-model="generatorTo" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <br />
          <k-bar>
            <k-choice-input
              v-model="generatorConsent"
              :label="$t('simplestats.info.testers.generator.consent')"
            />
            <k-button
              variant="filled"
              :disabled="!generatorConsent"
              :icon="isGenerating ? 'loader' : null"
              :text="$t('simplestats.info.testers.generator.button')"
              @click="generateStats"
            />
          </k-bar>

          <template v-if="generatorResponse?.error || generatorResponse?.data">
            <br />
            <k-box v-if="generatorResponse?.error" theme="negative" :text="generatorResponse.error" />
            <k-code v-if="generatorResponse?.data" language="json">{{ generatorResponse.data }}</k-code>
          </template>
        </k-section>

        <k-section :label="$t('simplestats.info.testers.tfutility.label')">
          <div class="k-table">
            <table>
              <tbody>
                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.generator.date.from') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-date-input v-model="tfutilityFrom" />
                  </td>
                </tr>
                <tr>
                  <th data-mobile="true">{{ $t('simplestats.info.testers.generator.date.to') }}</th>
                  <td data-mobile="true" class="k-table-cell">
                    <k-date-input v-model="tfutilityTo" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <br />
          <k-bar>
            <k-button
              variant="filled"
              :icon="isTesting ? 'loader' : null"
              :text="$t('simplestats.info.testers.tfutility.button')"
              @click="testTimeframeUtility"
            />
          </k-bar>

          <template v-if="tfutilityResponse?.error || tfutilityResponse?.data">
            <br />
            <k-box v-if="tfutilityResponse?.error" theme="negative" :text="tfutilityResponse.error" />
            <k-code v-if="tfutilityResponse?.data" language="json">{{ tfutilityResponse.data }}</k-code>
          </template>
        </k-section>
      </k-column>
    </k-grid>
  </div>
</template>

<script>
export default {
  data() {
    const to   = new Date();
    const from = new Date();
    const tfFrom = new Date();
    const tfTo = new Date();
    from.setDate(to.getDate() - 30);    // today - 1m
    tfFrom.setDate(to.getDate() - 365); // today - 1y
    tfTo.setDate(to.getDate() + 365*5); // today + 5y

    return {
      currentDevice: null,
      currentUserAgent: '',

      customUserAgent: '',
      customDevice: null,

      refererUrl: '',
      refererResponse: null,

      isGenerating: false,
      generatorMode: 'randommulti',
      generatorFrom: from.toISOString(),
      generatorTo: to.toISOString(),
      generatorConsent: false,
      generatorResponse: null,

      isTesting: false,
      tfutilityFrom: tfFrom.toISOString(),
      tfutilityTo: tfTo.toISOString(),
      tfutilityResponse: null
    };
  },

  created() {
    this.getCurrentUserAgent();
  },

  computed: {
    hasValidReferer() {
      return this.refererResponse && !this.refererResponse.error;
    },

    refererInfo() {
      const fields = ['host', 'source', 'medium', 'url'];
      const referer = this.refererResponse || {};

      return fields.map(field => ({
        field,
        label: `simplestats.info.testers.referer.field.${field}`,
        value: referer[field] || '-'
      }));
    },

    generatorModes() {
      return [
        {
          value: 'all',
          text: this.$t('simplestats.info.testers.generator.mode.all')
        },
        {
          value: 'randomsingle',
          text: this.$t('simplestats.info.testers.generator.mode.randomsingle')
        },
        {
          value: 'randommulti',
          text: this.$t('simplestats.info.testers.generator.mode.randommulti')
        }
      ];
    },
  },

  watch: {
    customUserAgent() {
      this.customDevice = null;
    },

    refererUrl() {
      this.refererResponse = null;
    }
  },

  methods: {
    formatDevice(device) {
      return `${device?.device} - ${device?.system} - ${device?.engine}`;
    },

    async getCurrentUserAgent() {
      const response = await this.$api.get('simplestats/testers/user-agent');
      this.currentUserAgent = response.userAgent;
      this.currentDevice = response.deviceInfo;
    },

    async testUserAgent() {
      const response = await this.$api.get('simplestats/testers/user-agent?ua=' + encodeURIComponent(this.customUserAgent));
      this.customDevice = response.deviceInfo;
    },

    async testReferer() {
      this.refererResponse = await this.$api.get('simplestats/testers/referer?url=' + encodeURIComponent(this.refererUrl));
    },

    async generateStats() {
      if (!this.generatorConsent || this.isGenerating) return;

      this.isGenerating = true;

      const fromTimestamp = Math.floor(new Date(this.generatorFrom).getTime() / 1000);
      const toTimestamp = Math.floor(new Date(this.generatorTo).getTime() / 1000);

      try {
        this.generatorResponse = await this.$api.get(
          `simplestats/testers/generatestats?from=${fromTimestamp}&to=${toTimestamp}&mode=${this.generatorMode}`
        );
      } finally {
        this.isGenerating = false;
      }
    },
    async testTimeframeUtility() {
      if (this.isTesting) return;
      this.isTesting = true;

      const fromTimestamp = Math.floor(new Date(this.tfutilityFrom).getTime() / 1000);
      const toTimestamp = Math.floor(new Date(this.tfutilityTo).getTime() / 1000);

      try {
        this.tfutilityResponse = await this.$api.get(
          `simplestats/testers/timeframeutility?from=${fromTimestamp}&to=${toTimestamp}`
        );
      } finally {
        this.isTesting = false;
      }
    }
  }
};
</script>

<style>
.k-simplestats-info-testers table {
  table-layout: auto;
}

.k-simplestats-info-testers .k-table-button {
  width: 0 !important;
}

.k-simplestats-info-testers .k-text-field-preview {
  white-space: normal;
}
</style>
