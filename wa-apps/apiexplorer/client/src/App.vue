<template>

  <section class="flexbox wrap-mobile">

      <aside class="sidebar hide-scrollbar width-adaptive flexbox mobile-friendly" style="overflow: inherit;" id="apiexplorer-main-sidebar">
          <nav class="sidebar-mobile-toggle">
              <div class="box align-center">
                  <a href="javascript:void(0);" @click="mobileSidebarToggle()"><i class="fas fa-bars"></i> Меню</a>
              </div>
          </nav>
          <div class="sidebar-header" :style="sidebarStyle">
            <app-list :apps_with_api="app_ids" :layout_loaded="loaded" @app-selected="appSelected" @apps-loaded="appsLoaded" />
          </div>
          <div class="sidebar-body hide-scrollbar custom-pt-16" id="wa-app-navigation" :style="sidebarStyle">
            <user-list @user-selected="mobileSidebarHide" />
          </div>
          <div class="sidebar-footer" :style="sidebarStyle">
            <ul class="menu">
              <li :class="[$route.name === 'About' && 'selected']" @click="mobileSidebarHide">
                <router-link :to="{ name: 'About' }">
                  <i class="fas fa-info"></i>
                  <span>About</span>
                </router-link>
              </li>
            </ul>
          </div>
      </aside>

      <main class="content blank hide-scrollbar flexbox">

        <aside class="sidebar blank width-18rem bordered-right desktop-only hide-scrollbar" v-if="$route.name === 'Application' || $route.name === 'Method'">
          <div class="sidebar-body hide-scrollbar">
            <method-list :app_id="current_app" />
          </div>
        </aside>

          <div class="article content">
              <div class="article-body" id="wa-app-content">
                  <router-view v-if="loaded" :key="$route.fullPath"/>
                  <div v-else  class="box skeleton" style="width: 70%;">
                    <span class="skeleton-header" style="width: 60%;"></span>
                    <span class="skeleton-line"></span>
                    <span class="skeleton-line"></span>
                    <span class="skeleton-line"></span>
                    <span class="skeleton-line"></span>
                  </div>
              </div>
          </div>
      </main>

  </section>

  <div class="alert-fixed-box" v-if="error.state">
    <span class="alert danger">
      <a href="#" class="alert-close" @click="error.state = false"><i class="fas fa-times"></i></a>
      <i class="fas fa-skull"></i> {{ error.description }}
    </span>
  </div>

</template>

<script>
import AppList from '@/components/AppList';
import UserList from '@/components/UserList';
import MethodList from '@/components/MethodList';
export default {
  components: {
    AppList,
    UserList,
    MethodList,
  },
  data() {
    return {
      //sidebar: ['/', '/about'],
      current_app: "",
      app_ids: [],
      error: {
        state: false,
        description: ""
      },
      mobileSidebarOpen: false,
      sidebarStyle: {}
    };
  },
  computed: {
    loaded() {
      return this.$store.state.loaded;
    }
  },
  created() {
    this.emitter.on('error', (err_desc) => {
      this.error.description = err_desc;
      this.error.state = true;
    });
  },
  methods: {
    appsLoaded(apps) {
      this.app_ids = apps;
      this.$store.commit('load_finish');
    },
    appSelected(app_id) {
      this.current_app = app_id;
      this.mobileSidebarHide();
    },
    mobileSidebarToggle() {
      if (this.mobileSidebarOpen) {
        this.mobileSidebarHide();
      } else {
        this.mobileSidebarShow();
      }
    },
    mobileSidebarHide() {
      this.mobileSidebarOpen = false;
      this.sidebarStyle = {};
    },
    mobileSidebarShow() {
      this.mobileSidebarOpen = true;
      this.sidebarStyle = {
        display: 'block',
      };
    },
  }
};
</script>