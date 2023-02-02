<template>
  <p class="ss-percentage-field-preview">
    <span class="ss-progress" :style="'width: '+percentage+'%;'">{{ percentage }}%</span>
  </p>
</template>


<script>
  // Field preview for percentage
  export default {
    props: {
      value: String | Number,
      column: Object,
      field: Object
    },
    computed:{
      percentage(){
        return Number(this.value*100).toFixed();
      },
    },
  };
</script>



<style lang="less">
.ss-percentage-field-preview {
  .ss-progress {
    background-color: var(--color-gray-400);
    height: 100%;
    display: block;
  }
}
</style>