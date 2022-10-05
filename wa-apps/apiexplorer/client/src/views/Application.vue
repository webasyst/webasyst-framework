<template>
  <div class="desktop-only">
    <div class="box align-center" style="margin-top: 10vh;">
      <span style="opacity: 0.15; font-size: 18rem;" class="text-gray"><i class="fas fa-compass"></i></span>
    </div>
  </div>
  <div class="tablet-only">
    <method-list :app_id="app_id" />
  </div>
  <div class="mobile-only">
    <h1 class="custom-px-12"><img :src="rootUrl + app.img" class="icon"/> {{ app.name }}</h1>
    <method-list :app_id="app_id" />
  </div>
</template>

<script>
import MethodList from '@/components/MethodList';
export default {
  components: {
    MethodList
  },
  data() {
    return {
      app_id: "",
      app: {
        name: "",
        img: "",
        icon: []
      }
    };
  },
  computed: {
    rootUrl() {
      return window.appState.rootUrl;
    }
  },
  mounted() {
    this.app_id = this.$route.params.name;
    this.app = this.$store.getters.getApp(this.app_id);
    document.title = this.app.name + ' — API Explorer';
  }
}
</script>

<style scoped>
.mobile-only > h1 > .icon {
  vertical-align: bottom; 
  width: 3rem; 
  height: 3rem;
}
</style>