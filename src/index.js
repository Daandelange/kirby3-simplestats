// Vue components
import SimpleStatsArea from "./components/SimpleStatsArea.vue";
import OnePageStats from "./components/Sections/OnePageStats.vue";
import PercentageFieldPreview from "./components/Ui/PercentagePreview.vue";

// Register plugin @Kirby
panel.plugin("daandelange/simplestats", {
  // K3.6+
  components: {
    'k-simplestats-view': SimpleStatsArea,
    // Slider,

    // Replacement of k-tabs that allows switching tabs without reloading the page
    'k-tabs' : {
      extends: 'k-tabs',
      mounted(){
        this.bindTabClicks();
      },
      emits: ['tabChange'],
      methods: {
        bindTabClicks(){
          const thisRef = this;
          for(const child of this.$children){
            if(child.$el.classList.contains('k-tab-button')){
              child.$el.addEventListener('click', function(){ thisRef.onClick(child.$vnode.key); } );
            }
          }
        },
        onClick(tabKey){
          if(this.tab !== tabKey){
            // k-header < k-tabs < k-button : fire $emit on header
            this.$parent.$emit('tabChange', tabKey);
          }
        }
      },
    },
    // Custom field preview for table
    'k-percentage-field-preview': PercentageFieldPreview,
  },
  sections: {
    //simplestats: View detailed stats (one page)
    pagestats: OnePageStats,
  },
  use: [
    // Slider,
  ],
  devtool: 'source-map', // vue debugging
});
