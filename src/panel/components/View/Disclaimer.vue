<template>
  <k-box v-if="visible" theme="text">
    <k-text size="small">
      <h3>{{ $t('simplestats.disclaimer.title') }}</h3>
      <p v-html="$t('simplestats.disclaimer.text')"></p>
      <kbd>{{ $t('simplestats.disclaimer.dismiss') }}</kbd>
    </k-text>
  </k-box>
</template>

<script>
export default {
  props: {
    visible: {
      type: Boolean,
      default: true
    }
  }
};
</script>
