<template>
  <k-button-group layout="collapsed">
    <!-- From Picker -->
    <k-button
      :dropdown="true"
      :text="formatDateLabel(dateFrom)"
      icon="calendar"
      size="sm"
      variant="filled"
      @click="$refs.fromDropdown.toggle()"
    />
    <k-picklist-dropdown
      ref="fromDropdown"
      :multiple="false"
      :options="fromOptions"
      :search="{ display: 12 }"
      :value="dateFrom"
      @input="onSelect('from', $event)"
    />

    <k-icon type="expand-horizontal" alt="to" />

    <!-- To Picker -->
    <k-button
      :dropdown="true"
      :text="formatDateLabel(dateTo)"
      icon="calendar"
      size="sm"
      variant="filled"
      @click="$refs.toDropdown.toggle()"
    />
    <k-picklist-dropdown
      ref="toDropdown"
      :multiple="false"
      :options="toOptions"
      :search="{ display: 12 }"
      :value="dateTo"
      @input="onSelect('to', $event)"
    />
  </k-button-group>
</template>

<script>
import { useLibrary } from "kirbyuse";

export default {
  props: {
    dateChoices: {
      type: Array,
      default: () => []
    },
    timePeriod: {
      type: String,
      default: 'Monthly'
    },
    timeFormat: {
      type: String,
      default: 'MMM YYYY'
    },
    initialViewPeriods: {
      type: Number,
      default: -1
    }
  },

  data() {
    return {
      dateFrom: '',
      dateTo: ''
    };
  },

  mounted() {
    const lastIndex = this.dateChoices.length - 1;

    this.dateFrom = this.dateChoices[0];
    this.dateTo = this.dateChoices[lastIndex];

    if (this.initialViewPeriods > 0) {
      this.dateFrom = this.dateChoices[Math.max(0, lastIndex - this.initialViewPeriods)];
    }
  },

  computed: {
    fromOptions() {
      return this.mapOptions(this.dateFrom, this.dateTo, 'from');
    },

    toOptions() {
      return this.mapOptions(this.dateFrom, this.dateTo, 'to');
    }
  },

  methods: {
    formatDateLabel(date) {
      if (!date) return 'Select date';
      const format = this.timeFormat;//this.timePeriod === 'Monthly' ? 'MMM YYYY' : 'MMM YYYY'; // use timeFrameUtility?
      const library = useLibrary();
      return library.dayjs(date).format(format);
    },

    mapOptions(from, to, type) {
      const fromIndex = this.dateChoices.indexOf(from);
      const toIndex = this.dateChoices.indexOf(to);

      return this.dateChoices.map((date, i) => ({
        value: date,
        text: this.formatDateLabel(date),
        disabled:
          type === 'from'
            ? toIndex >= 0 && i > toIndex
            : fromIndex >= 0 && i < fromIndex,
      }));
    },

    onSelect(which, value) {
      if (!value || this[`date${this.capitalize(which)}`] === value) {
        this.$refs[`${which}Dropdown`]?.close();
        return;
      }

      this[`date${this.capitalize(which)}`] = value;
      this.$refs[`${which}Dropdown`]?.close();
      this.update();
    },

    capitalize(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    },

    update() {
      this.$emit('change', {
        from: this.dateFrom,
        to: this.dateTo
      });
    }
  }
};
</script>
