<template>
  <p class="k-percentage-field-preview k-text-field-preview" :style="cssVars">
    <span class="track" :title="`${column.label}: ${percentage}%`">
      <span class="fill" />
      <span class="percentage">
        {{ percentage }}%
      </span>
    </span>
  </p>
</template>

<script>
export default {
  props: {
    value: Number,
    row: Object,
    column: Object
  },

  computed: {
    percentage() {
      return Math.min(100, Math.max(0, Math.round(this.value * 100)));
    },

    cssVars() {
      return {
        '--percentage': `${this.percentage}%`,
        '--percentage-color': this.percentageColor
      };
    },

    percentageColor() {
      if (this.percentage < 25) return 'var(--color-red-600)';
      if (this.percentage < 50) return 'var(--color-orange-600)';
      if (this.percentage < 75) return 'var(--color-yellow-600)';
      return 'var(--color-green-600)';
    }
  }
};
</script>

<style>
.k-percentage-field-preview .track {
  position: relative;
  display: flex;
  align-items: center;
  height: 1.25rem;
  font-size: 0.9em;
  background-color: light-dark(
    var(--color-gray-200),
    var(--color-gray-800)
  );
  border-radius: 0.25rem;
  overflow: hidden;
  user-select: none;
}

.k-percentage-field-preview .fill {
  height: 100%;
  width: var(--percentage);
  background-color: var(--percentage-color);
  transition: width 0.3s ease;
}

.k-percentage-field-preview .percentage {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}
</style>
