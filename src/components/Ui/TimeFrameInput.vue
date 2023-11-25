<template>
  <k-grid gutter="medium" class="ss-timeframe-input">

    <!-- Date FROM input -->
    <k-column width="3/12">
      <k-field :input="_uid" v-bind="$props" class="k-date-field">
        <div
          class="k-date-field-body"
          data-theme="field"
        >
          <k-input
            ref="fromDateField"
            :autofocus="false"
            :display="staticDateFieldProps.display"
            :min="minDate"
            :max="maxDate"
            :required="true"
            v-model="dateFrom"
            theme="field"
            type="date"
            icon="calendar"
            :before="$t('simplestats.timeframe.date.from', 'From')"
          >
            <template #icon>
              <k-dropdown>
                <k-button
                  icon="calendar"
                  :tooltip="$t('date.select')"
                  class="k-input-icon-button"
                  @click="$refs.calendarFromDrawer.toggle()"
                />
                <k-dropdown-content ref="calendarFromDrawer" align="right">
                  <k-calendar
                    :value="dateFrom+' 00:00:00'"
                    :min="minDate"
                    :max="maxDate"
                    @input="onUpdateKirbyDateFrom"
                    ref="calendarFrom"
                  />
                </k-dropdown-content>
              </k-dropdown>
            </template>
          </k-input>
        </div>
      </k-field>
    </k-column>

    <!-- Time Range Slider -->
    <k-column width="6/12">
      <div class="k-range-input ss-timeframe-range">
        <slider-fixed
          ref="slider"
          :handles="timeFrame"
          :set="timeFrame"
          class=""
          :range="sliderRange"
          behaviour="drag"
          connect
          :step="1"
          :tooltips="sliderDateTooltipsFunc"
          :format="sliderDateFormatter"
          :fixedpips="sliderStepSettings"
          @update="onSliderUpdate"
          @change="onSliderChange"
          @set="onSliderSet"
        />
      </div>
    </k-column>

    <!-- Date TO input -->
    <k-column width="3/12">
      <k-field :input="_uid" v-bind="$props" class="k-date-field">
        <div
          class="k-date-field-body"
          data-theme="field"
        >
          
          <k-input
            ref="toDateField"
            :autofocus="false"
            :display="staticDateFieldProps.display"
            :min="minDate"
            :max="maxDate"
            :required="true"
            v-model="dateTo"
            theme="field"
            type="date"
            icon="calendar"
            :before="$t('simplestats.timeframe.date.to', 'To')"
          >
            <template #icon>
              <k-dropdown>
                <k-button
                  icon="calendar"
                  :tooltip="$t('date.select')"
                  class="k-input-icon-button"
                  @click="$refs.calendarToDrawer.toggle()"
                />
                <k-dropdown-content ref="calendarToDrawer" align="right">
                  <k-calendar
                    :value="dateTo+' 00:00:00'"
                    :min="minDate"
                    :max="maxDate"
                    @input="onUpdateKirbyDateTo"
                    ref="calendarTo"
                  />
                </k-dropdown-content>
              </k-dropdown>
            </template>
          </k-input>
        </div>
      </k-field>
    </k-column>
  </k-grid>
</template>


<script>

// Interesting alternative candidate : https://github.com/oguzhaninan/vue-histogram-slider
import Slider from 'noui-vue-fr';

export default {
  components: {
    // Slider,
    'slider-fixed': {
      extends: Slider,
      props: {
        // New attribute manually injected later, because the native is buggy
        fixedpips: {
          type: Array | Boolean,
          default: false,
        },
        // Allow objects too according to the nouislider docs
        tooltips: {
          type: Boolean | Array | Object,
          default: true, // Default to true
        }
      },
      // Inject pips options on mount to bypass the native injection.
      mounted(){
        if(this.$props.fixedpips){
          this.$el?.noUiSlider?.updateOptions({pips:this.$props.fixedpips}, false);
        }

        // this.$nextTick(function () {
        //   //console.log('Setting Initial Handles to :', this.$props.handles);
        //   // this.$el.noUiSlider?.set(this.$props.handles);
        //   // this.$el.noUiSlider?.set(this.timeFrame);
        // });
      },
    },
  },
  mounted(){
    // Parse initial values
    const minmax = this.sliderRange;
    this.dateFrom = this.dateChoices[minmax.min];
    this.dateTo = this.dateChoices[minmax.max];
    
    // Initial sanitation / sync
    this.updateDateFields(this.timeFrame);
  },
  // watch: {
  //   dateFrom(value){
  //     // Parse initial values ?
  //     this.updateDateFields([value, null]);
  //   },
  //   dateTo(value){
  //     // Parse initial values ?
  //     this.updateDateFields([null, value]);
  //   },
  // },
  data() {
    const thisRef = this;

    // Some "static" variables to use by filter functions
    // Quite hacky but it seems to work nicely.
    let prevYear = null;
    let prevMonth = null;
    let prevYearFilter = null;
    let prevMonthFilter = null;

    return {
      dateFrom: '',
      dateTo: '',
      sliderDisplayFormat: 'YYYY-MM-DD',
      staticDateFieldProps: { // Todo: is this still used ?
        class: 'ss-header-date',
        calendar: true,
        time: false,
        times: false,
        required: false,
        invalid: false,
        display: 'YYYY-MM-DD',
      },

      // Slider date converters
      sliderDateFormatter: {
        to: function (value) {
            return ''+thisRef.dateChoices[Number(value).toFixed(0)];
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function (value) {
            let num = Number( thisRef.dateChoices.indexOf(value) );
            // Date doesn't exist, try to find the closest one. Happens in very rare cases
            if(num < 0){
              let wantedNum = Number(value.replaceAll('-','')).toFixed(0);
              let closest = null;
              num = 0;
              thisRef.dateChoices.forEach( function(dateCandidate, i) {
                const candidateNum = Number(dateCandidate.replaceAll('-','')).toFixed(0);
                if( closest===null || Math.abs(candidateNum - wantedNum) < Math.abs(closest - wantedNum) ) {
                  closest = candidateNum;
                  num = i;
                }
              });
            }
            return num;
        }
      },
      
      sliderStepSettings: {
        mode: 'steps',
        // mode: 'filter',
        density: 5,
        filter: function(value, type){
          // -1 (no pip at all)
          // 0 (no value)
          // 1 (large value)
          // 2 (small value)
          if(type===1 || type===2){
            let date = ''+thisRef.dateChoices[Number(value).toFixed(0)];
            let year = date.split('-')[0];
            let month = date.split('-')[1];

            if( year && (year !== prevYearFilter)){
              prevMonthFilter = month;
              prevYearFilter = year;
              return 1;
            }
            if(prevMonthFilter !== month){
              prevYearFilter = year;
              prevMonthFilter = month;
              return 2;
            }
            return 0; // No text if same year/month, but mark
          }
          return type;
        },
        // Pips formatting : return tiny or no strings
        format: {
          to: function (value) {
              let dateStr = thisRef.dateChoices[Number(value).toFixed(0)];
              let dt = thisRef.$library.dayjs(dateStr);
              // let year = date.split('-')[0];
              // let month = date.split('-')[1];
              let year = dt.$y;
              let month = dt.$M;

              // New Year = show year
              if( (year && (year !== prevYear))){
                prevYear = year;
                prevMonth = month;
                return year;
              }
              // New month = show month
              if(month && prevMonth!=month){
                prevMonth = month;
                prevYear = year;
                return thisRef.monthnames[month].substring(0,3);
              }

              // Hide intermediates
              return '';//+(thisRef.dateChoices[Number(value).toFixed(0)]??'???');
          },
          // 'from' the formatted value.
          // Receives a string, should return a number.
          from: function (value) {
              let num = Number( thisRef.dateChoices.indexOf(value) );
              return num;
              
          },
        },
      },

      // Tooltip formatter
      sliderDateTooltipsFunc: [
        {
          to: function (value) {
            const other = thisRef.dateChoices.indexOf(thisRef.dateTo);
            return ''+thisRef.dateChoices[Number(value).toFixed(0)]+((other>0)?('<br/>('+Number(Math.abs(value-other)).toFixed(0)+' timeframes)'):'');
          },
          from: function (value) { return 0;},// unused ?
        },
        {
          to: function (value) {
            const other = thisRef.dateChoices.indexOf(thisRef.dateFrom);
            return ''+thisRef.dateChoices[Number(value).toFixed(0)]+((other>0)?('<br/>('+Number(Math.abs(value-other)).toFixed(0)+' timeframes)'):'');
          },
          from: function (value) { return 0;},// unused ?
        },
      ],

      monthnames: [
        "january",
        "february",
        "march",
        "april",
        "may",
        "june",
        "july",
        "august",
        "september",
        "october",
        "november",
        "december"
      ].map((day) => this.$t("months." + day)),


    };
  },
  props: {
    dateChoices: {
      type: Array,
      default(){
        return [];// '2022-01-01', '2022-02-01', '2022-03-01', '2022-04-01', '2022-05-01', '2022-06-01', '2022-07-01', '2022-08-01', '2022-09-01', '2022-10-01', '2022-11-01', '2022-12-01', '2023-01-01'];
      },
    }
  },
  computed: {

    // Min/Max time frame
    sliderRange(){
      return {
        'min': 0,
        'max': this.dateChoices.length-1,
      };
    },
    // Cur selected time frame indexes/values 
    timeFrame() {
      return [this.dateFrom, this.dateTo];
      // return [0,this.dateChoices.length-1];
    },
    minDate() {
      return this.dateChoices.reduce((a, b) => a < b ? a : b);
    },
    maxDate() {
      return this.dateChoices.reduce((a, b) => a > b ? a : b);
    },
  },

  methods: {
    onSliderSet(data){
      if(data){
        // Note: Both from+to are only send in this fork, not in origin
        this.dateFrom = data.values[0];
        this.dateTo = data.values[1];
      }
      // this.updateDateFields(data.values);
    },
    // onSliderUpdate(x){
    //   // window.console.log("SliderUpdate", x.values[0], x.values[1], x.positions);
    // },
    // onSliderChange(x){
    //   //window.console.log("SliderChange", x.values, x.positions);
    // },
    // Parses and syncs the new from/to values into all fields
    updateDateFields(fromto){
      // console.log('UpdateDateFields=', fromto[0], fromto[1]);
      if(!Array.isArray(fromto) || fromto.length!==2) return; // Todo: rather throw ?
      for(let i = 0; i<2; i++){
        const target = (i===0)?'dateFrom':'dateTo'; // Var name mapping
        const value = fromto[i];//??this[target];
        if(!value || !value.length) continue; // Null value = dont set

        // Clean the format. Kirby adds an empty time string to it.
        let parts = value.split(' ');
        if(parts.length>1){
            this[target] = parts[0];
        }

        // Map date to exact begin of timespan
        if(this[target].length && this.dateChoices){
          // let [year, month, day] = this.dateFrom.split('-');
          // let result = {year:null,month:null,day:null};
          let newDateNum = Number(value.replaceAll('-','')).toFixed(0);
          let defaultI = i*(this.dateChoices.length-1);
          let prevDate = { i: defaultI, num: Number(this.dateChoices[defaultI].replaceAll('-','')).toFixed(0) };

          this.dateChoices.forEach( function(date, di){

            let curDateNum = Number(date.replaceAll('-','')).toFixed(0);
            
            // New date after/before this candidate ? Ignore entries after.
            if((i===0 && curDateNum > newDateNum) || (i===1 && curDateNum < newDateNum)){
              return;
            }
            // Keep value if after/before previous one
            else if((i===0 && curDateNum >= prevDate.num) || (i===1 && curDateNum <= prevDate.num)) {
              prevDate.i = di;
              prevDate.num = curDateNum;
            }
          });

          this[target] = this.dateChoices[prevDate.i];
          //this.timeFrame[i] = prevDate.i;//this.dateChoices.indexOf(this.dateFrom);

          // When the value doesn't change (eg it remains in the same timespan) the watcher doesn't fire, not updating the selected date.
          // Ensure to select it here
          // Hacky part....
          const dt = this.$library.dayjs(this[target]);
          let calInstance = this.$refs[((i===0)?'calendarFrom':'calendarTo')];
          if(calInstance){
            calInstance.data.dt = dt;
          }
          this.$refs[(i===0)?'calendarFromDrawer':'calendarToDrawer']?.close();
        }

        // Update slider accoringly
        this.$refs.slider?.$el?.noUiSlider?.set([fromto[0]?this.dateFrom:null, fromto[1]?this.dateTo:null], false, false); // Nofire, NoignoreSteps
      }
    },
    onUpdateKirbyDateTo(value){
      this.updateDateFields([null, value]);
    },
    onUpdateKirbyDateFrom(value){
      // this.$refs.calendar?.close();
      this.updateDateFields([value, null]);
    },
  },
};
</script>

<style lang="less">

.ss-timeframe-input {

  // Hide any date field label for more space
  .k-field-header {
    display: none;
  }

  // Scoped import : don't mess with other people's no-ui styles
  @import (less) 'nouislider/dist/nouislider.min.css';

  // Over-ride nouislider.css to match Kirby styles
  .ss-timeframe-range {

    // Need to inherit from this to grab kirby range-input style vars.
    // So unset diplay flex which messes up our layour
    &.k-range-input {
      display: block;
    }

    // Add space on both sides because the steps can overflow
    padding: 0 15px;

    // Better v-alignment with siblings
    // padding-top: calc( var(--field-input-padding)*.5);
    padding-top: var(--field-input-padding);
    min-height: var(--field-input-height);

    .noUi-pips-horizontal {
      padding: calc(0.25*var(--range-thumb-size)) 0;
      height: 28px;
    }

    .noUi-horizontal {
      background-color: var(--range-track-background);
      // padding: calc(0.25*var(--range-thumb-size)) 0;

      .noUi-value {
        background-color: var(--color-background);
        margin-top: 2px;
      }

      .noUi-handle {
        width: var(--range-thumb-size);
        height: var(--range-thumb-size);
        border-radius: calc(var(--range-thumb-size) * 0.5);
        // background-color: var(--color-gray-900);
        background-color: var(--range-thumb-background);
        border: var(--range-thumb-border);
        cursor: pointer;
        right: calc(-0.5*var(--range-thumb-size));

        // Hide tooltip by default, show when active
        .noUi-tooltip {
          display: none;
          font-size: var(--font-size-tiny);
          bottom: 180%;
        }

        // Active state
        &.noUi-active {
          border-color: var(--range-track-focus-color);

          .noUi-tooltip {
            display: block;
          }
        }
      }
    }
    .noUi-target {
      border: none;

      // When any handle is active
      &.noUi-state-drag {
        // Hide tooltip by default, show when active
        // .noUi-tooltip {
        //   display: block;
        // }

        // Active color
        .noUi-connect {
          background-color: var(--range-track-focus-color);
        }
      }
    }
    .noUi-value-large, .noUi-value-sub {
      font-size: var(--font-size-tiny);
    }

    .noUi-value-sub {
      z-index: -1; /// behind years
    }

    .noUi-handle:before, .noUi-handle:after {
      display: none;
    }

    .noUi-connect {
      background-color: var(--range-track-color);
    }
    .noUi-base, .noUi-connects, .noUi-horizontal {
      height: var(--range-track-height);
    }
    // .noUi-base {
    //   //margin-left: -9px; // Aligns steps with slider positions
    // }

  }
}
</style>
