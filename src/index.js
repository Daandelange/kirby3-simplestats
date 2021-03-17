// Vue components
import View from "./components/View.vue";

//import Vue from 'vue'
import Chartkick from 'vue-chartkick'
import Chart from 'chart.js'
Chartkick.options = {
  colors: [
    // Generate a color palette in console :
    // var colors=8; var variants=4; out=""; for(var v=0; v<variants; v++){var b=70+(v%2)*10; var s=70+((v+1)%2)*10; for(var i=0; i<colors; i++){out+="'hsl("+((i/colors)*360)+", "+s+"%, "+b+"%)', ";}} out;
    'hsl(0, 80%, 70%)', 'hsl(45, 80%, 70%)', 'hsl(90, 80%, 70%)', 'hsl(135, 80%, 70%)', 'hsl(180, 80%, 70%)', 'hsl(225, 80%, 70%)', 'hsl(270, 80%, 70%)', 'hsl(315, 80%, 70%)', 'hsl(0, 70%, 80%)', 'hsl(45, 70%, 80%)', 'hsl(90, 70%, 80%)', 'hsl(135, 70%, 80%)', 'hsl(180, 70%, 80%)', 'hsl(225, 70%, 80%)', 'hsl(270, 70%, 80%)', 'hsl(315, 70%, 80%)', 'hsl(0, 80%, 70%)', 'hsl(45, 80%, 70%)', 'hsl(90, 80%, 70%)', 'hsl(135, 80%, 70%)', 'hsl(180, 80%, 70%)', 'hsl(225, 80%, 70%)', 'hsl(270, 80%, 70%)', 'hsl(315, 80%, 70%)', 'hsl(0, 70%, 80%)', 'hsl(45, 70%, 80%)', 'hsl(90, 70%, 80%)', 'hsl(135, 70%, 80%)', 'hsl(180, 70%, 80%)', 'hsl(225, 70%, 80%)', 'hsl(270, 70%, 80%)', 'hsl(315, 70%, 80%)',
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
