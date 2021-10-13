<template>
  <div class="configuration">
    <k-headline size="large">{{ $t('simplestats.info.tester.title') }}</k-headline>

    <k-headline class="rightColumnAlign">{{ $t('simplestats.info.tester.device') }}</k-headline>
    <k-text-field :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.device.currentua')" :value="currentUserAgent" icon="display"/>
    <k-text-field :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.device.currentdetected')" :value="formattedCurrentUA" icon="display"/>
    <k-form @submit="testUserAgent">
      <k-text-field  class="field-with-btn" :counter="false" :disabled="false" :label="$t('simplestats.info.tester.device.customua')" v-model="customUserAgent" icon="display"/>
      <k-button @click="testUserAgent" class="floating-btn">Go!</k-button>
      <k-text-field v-if="this.customDevice" :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.device.customdetected')" :value="formattedCustomUA" icon="display"/>
    </k-form>
    <k-box v-if="this.customDevice" theme="info" :text="$t('simplestats.info.tester.device.note')" />
    <br />

    <k-headline class="rightColumnAlign">{{ $t('simplestats.info.tester.referrer') }}</k-headline>
    <k-form @submit="testReferrer">
      <k-text-field class="field-with-btn" :counter="false" :disabled="false" :label="$t('simplestats.info.tester.referrer.field')" v-model="referrerField" icon="globe" />
      <k-button name="btn" @click="testReferrer" class="floating-btn">Go!</k-button>
      <!-- <k-text-field :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.referrer.response')" :value="formattedReferrer" icon="globe"/> -->
      <k-text-field v-if="this.referrerResponse" :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.referrer.response.host')" :value="formattedReferrerHost" icon="globe"/>
      <k-text-field v-if="this.referrerResponse" :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.referrer.response.source')" :value="formattedReferrerSource" icon="globe"/>
      <k-text-field v-if="this.referrerResponse" :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.referrer.response.medium')" :value="formattedReferrerMedium" icon="globe"/>
      <k-text-field v-if="this.referrerResponse" :counter="false" :disabled="true"  :label="$t('simplestats.info.tester.referrer.response.url')" :value="formattedReferrerUrl" icon="globe"/>
    </k-form>
  </div>
</template>

<script>

export default {
  extends: 'k-pages-section',
  data() {
    return {
      isLoading: true,
      error: "",
      currentDevice: "",
      currentUserAgent: this.currentUserAgentJS,
      customDevice: "",
      customUserAgent: "",
      referrerField: "",
      referrerResponse: null,
    }
  },
  created() {
    this.load();
  },
  computed: {
    currentUserAgentJS() {
      return navigator.userAgent;
    },
    formattedCurrentUA() {
      if(this.currentDevice && this.currentDevice!==""){
        return this.currentDevice.device + ' - ' + this.currentDevice.system + ' - ' + this.currentDevice.engine;
      }
      return "-";
    },
    formattedCustomUA() {
      if(this.customDevice && this.customDevice!==""){
        return this.customDevice.device + ' - ' + this.customDevice.system + ' - ' + this.customDevice.engine;
      }
      return "-";
    },
    formattedReferrer() {
      if(this.referrerResponse){
        if( this.referrerResponse.medium ){
          return this.referrerResponse.host + ' - ' + ' - ' + this.referrerResponse.source + ' - ' + this.referrerResponse.url + ' ('+this.referrerResponse.medium+')';
        }
        else if(this.referrerResponse.error){
          return this.referrerResponse.error;
        }
        return 'Error...';
      }
      return "-";
    },
    formattedReferrerUrl(){
      if(this.referrerResponse){
        if(this.referrerResponse.url) return this.referrerResponse.url;
        else if(this.referrerResponse.error) return this.referrerResponse.error;
      }
      return "-none-";
    },
    formattedReferrerSource(){
      if(this.referrerResponse){
        if(this.referrerResponse.source) return this.referrerResponse.source;
        else if(this.referrerResponse.error) return this.referrerResponse.error;
      }
      return "-none-";
    },
    formattedReferrerHost(){
      if(this.referrerResponse){
        if(this.referrerResponse.host) return this.referrerResponse.host;
        else if(this.referrerResponse.error) return this.referrerResponse.error;
      }
      return "-none-";
    },
    formattedReferrerMedium(){
      if(this.referrerResponse){
        if(this.referrerResponse.medium) return this.referrerResponse.medium;
        else if(this.referrerResponse.error) return this.referrerResponse.error;
      }
      return "-none-";
    }
  },
  methods: {
    load() {

      // Load configuration
      this.$api
        .get("simplestats/trackingtester")
        .then(response => {
          this.isLoading = false
          //this.ignoredTemplates = response.ignoredTemplates
          this.currentDevice = response.currentDeviceInfo
          this.currentUserAgent = response.currentUserAgent
        })
        .catch(error => {
          this.isLoading = false
          this.error = error.message
          this.$store.dispatch("notification/open", {
            type: "error",
            message: error.message,
            timeout: 5000
          });
        });
    },
    testReferrer() {
      this.$api
        .get("simplestats/trackingtester/referrer?referrer="+encodeURIComponent(this.referrerField) )
        .then(response => {
          //console.log('Type=', typeof(response.referrerInfo) );
          if(response && typeof(response.referrerInfo)=='string') this.referrerResponse = {
            error: response.referrerInfo
          };
          else if(response && typeof(response.referrerInfo)=='object') this.referrerResponse = response.referrerInfo;
          else this.referrerResponse = {
            error: 'Data format error !'
          };
          //console.log(this.referrerResponse);
        })
        .catch(error => {
          if(error && error.message) this.referrerResponse = error.message;
          else this.referrerResponse = 'Loading error !';
        });

    },
    testUserAgent() {
      this.$api
        .get("simplestats/trackingtester/ua?ua="+encodeURIComponent(this.customUserAgent) )
        .then(response => {
          //console.log(response);
          if(typeof(response)=='string') this.customDevice = {
            error: response
          };
          else if(typeof(response)=='object') this.customDevice = response;
          else this.customDevice = {
            error: 'Data format error !'
          };
          //console.log(this.customDevice);
        })
        .catch(error => {
          if(error && error.message) this.customDevice = error.message;
          else this.customDevice = 'Loading Error !';
        });

    }
  }
};
</script>

<style lang="scss">
.configuration {
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
  // CSS hack to place button next-to field
  .field-with-btn {

    .k-input {
      padding-right: 40px;
    }
  }
  .floating-btn {
    float: right;
    margin-right: 1em;
    margin-top: -2.2em;
    display: block;
    position: relative;
    //background-color: red;
    padding: .1em 0.2em;
    border: 1px black solid;
    border-radius: 2px;
  }
}
</style>
