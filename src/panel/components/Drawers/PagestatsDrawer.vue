<script>

import KPagestatsSection from "../Sections/PagestatsSection.vue"

const PagestatsSectionRender = KPagestatsSection.render;

export default {

  extends: KPagestatsSection,
  mixins: [
    'k-pages-section', // Needed to manually inject (yet unresolved) KPagestatsSection mixins (for `load()`)
    'k-drawer', // Needed to inherit drawer properties
  ], 

  emits: [
    "close",
    "next",
    "prev",
    "submit",
  ],

  props: {
    uid: {
      type: String,
      required: true,
    },
    next: {
      type: Object
    },
    prev: {
      type: Object
    },
  },

  // Custom render function that wraps a `k-drawer` around our original section template
  render(createElement) {
    const closeFn = () => { this.$emit('close') };
    const defaultSlot = () => {
      return [KPagestatsSection.render.call(this, createElement)];
    };

    const drawerProps = {
        attrs: {
          ...this.$attrs,
          ...this.$props,
          options: [
            this.$props.prev ? {
              title: "Previous",
              icon: 'angle-left',
              click: () => { this.$emit('prev'); if(this.$props.prev instanceof Function) this.$props.prev(); },
            } : {},
            this.$props.next ? {
              title: 'Next',
              icon: 'angle-right',
              click: () => { this.$emit('next'); if(this.$props.next instanceof Function) this.$props.next(); },
            } : {},
          ],
        },
        on: {
          submit: closeFn,
        },
        scopedSlots: {
          default: defaultSlot,
        }
    };

    // Simply return a drawer to which we bind the parenting render fn
    // We put the original KPagestatsSection renderFn as childs
    return createElement(
      'k-drawer', // Element to create
      drawerProps//, // Element Props
    );
  },

  methods: {
    // Like native K5 section load but with modified URL and no setting member vars, use return instead
    async load(reload) {
      this.isProcessing = true;

      if (!reload) {
        this.isLoading = true;
      }

      try {
        const response = await this.$api.get(
          'simplestats/onepagestats/'+this.uid+(this.$attrs.dateFrom?('?dateFrom='+this.$attrs.dateFrom+'&dateTo='+this.$attrs.dateTo):''),
        );
        return response;
      } catch (error) {
        this.error = error.message;
      } finally {
        // Note: This gets called correctly even when return in try is called.
        this.isProcessing = false;
        this.isLoading = false;
      }
      return null;
    },
  }
};
</script>
