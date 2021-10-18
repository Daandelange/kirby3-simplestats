<template>
    <div>
      <ul class="" v-if="arrayData" v-bind:style="{ paddingLeft: (20*depth) + 'px' }">
        <li
          v-for="(value, key, index) in arrayData"
          v-bind="arrayData.status"
          :key="key"
          class=""
          v-bind:class="{ isChild: subpage, 'text-danger': hasError }"
        >
          <div v-if="(typeof value === 'Array' || typeof value === 'object')">
            <span>
              {{ key }} =
            </span>
            <list-viewer v-if="(typeof value === 'Array' || typeof value === 'object') && depth < 10" :array-data="value" :depth="depth+1" />
          </div>
          <div v-else>
            <span>
              {{key}} = {{value}}
            </span>
          </div>
        </li>
      </ul>
    </div>
</template>

<script>


export default {
  name: 'list-viewer', // Needed for self-recursiveness
  props: {
    'arrayData':{
      type: Array,
      default: []
    },
    'depth': {
      type: Number,
      default: 0
    }
  },
  methods: {

  }
};
</script>

<style>

</style>
