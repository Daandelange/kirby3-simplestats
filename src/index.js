// Vue components
import View from "./components/View.vue";

//import Vue from 'vue'
import Chartkick from 'vue-chartkick'
import Chart from 'chart.js'
Chartkick.options = {
//  colors: ['rgba(11,133,55,1)', 'rgba(15,115,90,1)', 'rgba(190, 115, 15,1)', 'rgba(45,55,65,1)', 'rgba(130,140,150)', 'rgba(136,36,25,1)'],
colors: ['rgba(46, 64, 87,1)', 'rgba(102, 161, 130,1)', 'rgba(237, 174, 73,1)', 'rgba(209, 73, 91,1)', 'rgba(0, 121, 140,1)'],
  //colors: ['rgba(249, 65, 68,1)', 'rgba(243, 114, 44,1)', 'rgba(248, 150, 30,1)', 'rgba(249, 199, 79,1)', 'rgba(144, 190, 109,1)', 'rgba(67, 170, 139,1)', 'rgba(87, 117, 144,1)'],
  dataset : {borderWidth: 1, borderColor: 'black'},
  options: {scales: {
          xAxes: [{
            //display: false,
            type: 'time',
            time: {
              unit: 'month',
              displayFormats: {
                  month: 'MMM YYYY'
              }
            }
          }],
          yAxes: [{
            stacked: true
          }]
        }}
}
Chartkick.use(Chart);

// 3rd party assets
import "tbl-for-kirby/index.css";

// Register plugin @Kirby
panel.plugin("daandelange/simplestats", {
  views: {
    simplestats: {
      component: View,
      icon: "chart",
      label: "SimpleStats"
    }
  },
//   beforeCreate(){
//     console.warn('Chartkick injected !');
//     Chartkick.use(Chart);
//   },
  components: {
    //Chartkick,
  },
  use: {
    Chartkick,
    //console.warn('UUSSEE!!');
    //chartkick: [Chartkick.use(Chart)]
  },
  devtool: 'source-map', // vue debugging
});
