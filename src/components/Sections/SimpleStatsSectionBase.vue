

<script>

export default {
  extends: 'k-pages-section', // Re-use loading mechanisms
  data() {
    return {
      isLoading: true,
      error: "",
    }
  },
  props: {
    sectionName : {
      type: String,
      required: true,
    },
    dateFrom : {
      type: String,
      // required: true,
      default: null,
    },
    dateTo : {
      type: String,
      // required: true,
      default: null,
    },
  },
  created() {
    this.load();
  },
  computed: {
    
  },
  watch: {
    // Fetch new data when date changes
    dateFrom(){
      this.load(true);
    },
    dateTo(){
      this.load(true);
    },
  },
  methods: {
    // Getter
    dateQueryString(startwith='?'){
      if(!this.dateFrom || !this.dateTo) return '';
      return ''+startwith+'dateFrom='+this.dateFrom+'&dateTo='+this.dateTo;
    },

    // Heavily based on ModelsSection.vue::load() (the k-pages-section parent)
    async load(reload) {
      if(!reload) this.isLoading = true;
      this.isProcessing = true;
      // Load configuration
      try {
        const response = await this.$api.get("simplestats/"+this.sectionName+this.dateQueryString());
        this.loadData(response);
      } catch (error) {
        this.error = error.message;
        this.$store.dispatch("notification/error", error.message??'Unknown error');
        //console.log("Error=", error.message, { e: error });
      } finally {
        this.isLoading = false;
        this.isProcessing = false;
      }
    },
    // To be implemented by each section
    loadData(apiResponse){
      // console.log('DummyLoadData!', apiResponse);
    },
  },
};
</script>

<style lang="scss">

</style>
