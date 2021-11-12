// Vue components
import View from "./components/View.vue";
import OnePageStats from "./components/Sections/OnePageStats.vue";

//import Vue from 'vue'
import Chartkick from 'vue-chartkick';
import Chart from 'chart.js';
import 'chartjs-adapter-moment';
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

// Register plugin @Kirby
panel.plugin("daandelange/simplestats", {
  // K 3.5 and below
  views: {
    simplestats: {
      component: View,
      icon: "chart",
      label: "SimpleStats",
    }
  },
  // K3.6+
  components: {
    'simplestats': View,
//     simplestats: {
//       component: View,
//       icon: "chart",
//       label: "SimpleStats",
//       menu: true
//     },
//     'k-simplestats-view': {
// 			template: `
// 				<k-inside>
// 					<k-view class="k-simplestats-view">
// 						<iframe v-if="sharedLink" plausible-embed v-bind:src="sharedLink" scrolling="no" frameborder="0" loading="lazy" style="width: 1px; min-width: 100%; height: 1600px;"></iframe>
// 						<div style="margin-top: 30px; text-align: center;" v-else>
// 							<code>You need to set floriankarsten.plausible.sharedLink in config.php</code>
// 						</div>
// 					</k-view>
// 				</k-inside>
// 			`,
// 			props: ["sharedLink"]
// 		},
  },
  sections: {
    //simplestats: View detailed stats (one page)
    pagestats: OnePageStats,
  },
  use: [
    Chartkick,
  ],
  devtool: 'source-map', // vue debugging
});
