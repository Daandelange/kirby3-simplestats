// Vue components
import View from "./components/View.vue";

//import Vue from 'vue'
import Chartkick from 'vue-chartkick'
import Chart from 'chart.js'
Chartkick.options = {
  colors: [
    // Generate a color palette in console :
    // var colors=8; var variants=4; out=""; for(var v=0; v<variants; v++){var b=70+(i%2)*10; var s=70+((i+1)%2)*10; for(var i=0; i<colors; i++){out+="'hsl("+((i/colors)*360)+", "+s+"%, "+b+"%)', ";}} out;
    var colors=8; var variants=4; out=""; for(var v=0; v<variants; v++){var b=70+(i%2)*10; var s=70+((i+1)%2)*10; for(var i=0; i<colors; i++){out+="'hsl("+((i/colors)*360)+", "+s+"%, "+b+"%)', ";}} out;
  ],
  dataset : {
    borderWidth: 1,
    borderColor: 'black',
  },
  options: {
    scales: {
      xAxes: [{
        //display: false,
        type: 'time',
        time: {
          unit: 'month',
          displayFormats: {
              month: 'MMM YYYY',
          }
        }
      }],
      yAxes: [{
        stacked: true,
      }]
    }
  }
};
Chartkick.use(Chart);

// 3rd party assets
import "tbl-for-kirby/index.css";

// Register plugin @Kirby
panel.plugin("daandelange/simplestats", {
  views: {
    simplestats: {
      component: View,
      icon: "chart",
      label: "SimpleStats",
    }
  },
  use: {
    Chartkick,
  },
  devtool: 'source-map', // vue debugging
});
