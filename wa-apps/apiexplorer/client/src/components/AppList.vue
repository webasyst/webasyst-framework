<template>
  <div v-if="!ready" class="box skeleton custom-pt-32">
    <span class="skeleton-header custom-mb-0"></span>
  </div>
  <div v-else class="bricks dropdown" id="apps-dropdown">
    <div class="brick selected full-width dropdown-toggle">
      <div v-if="!selected_app">
        Select App
      </div>
      <div v-else class="flexbox middle" @click="selectApp(selected_app)">
        <img :src="'/' + apps[selected_app].icon['48']" class="icon" :title="apps[selected_app].name"/>
        <div class="app-wrapper">
          <span class="app-name">{{ apps[selected_app].name }}</span>
          <span v-if="$store.state.counts[selected_app]" class="count">{{ $store.state.counts[selected_app] }} APIs</span>
          <span v-else class="empty">No API</span>
        </div>
      </div>
    </div>
    <div class="dropdown-body">
        <ul class="menu">
            <li v-for="app in apps" :key="app.app_id" :class="[ app.id === selected_app && 'selected']">
                <a href="javascript:void(0);" @click="selectApp(app.id)">
                  <span v-if="app.id in $store.state.counts" class="count">{{ $store.state.counts[app.id] }}</span>
                  <span v-else class="count">-</span>
                  <i class="icon16 app-icon" :title="app.name" :style='{ "background-image": "url(/" + app.icon["16"] + ")" }'></i>
                  <span class="nowrap">{{ app.name }}</span>
                </a>
            </li>
        </ul>
    </div>
  </div>
</template>

<script>
import SwaggerClient from 'swagger-client';
export default {
    name: "AppList",
    emits: ["apps-loaded", "app-selected"],
    props: {
      apps_with_api: {
        type: Array,
        default: () => []
      },
      layout_loaded: Boolean
    },
    data() {
        return {
            state: {
                loading: false,
                error: false
            },
            selected_app: false,
            dropdown: false
        };
    },
    computed: {
      apps() {
        return this.$store.state.apps;
      },
      ready() {
        return !this.state.loading && this.layout_loaded;
      }
    },
    async mounted() {
      if (!this.apps || Object.keys(this.apps).length === 0) {
        this.state.loading = true;
        const resp = await this.axios.get('?module=applist');
        const apps = resp.data.data.apps;
        this.$store.commit('load_apps', apps);

        let promises = [];
        Object.entries(this.$store.state.apps).forEach(([app_id, app]) => {
          if (app.swagger) {
            promises.push(this.loadSwagger(app_id));
          }
        });
        promises.push(this.loadMethodList());
        await Promise.all(promises);
        this.$emit('apps-loaded', Object.keys(this.$store.state.method_groups));

        this.state.loading = false;
      }
      if (this.$route.name === 'Method') {
        const current_method = this.$route.params.name;
        this.selected_app = current_method.split('.')[0];
        this.$emit('app-selected', this.selected_app);
      } else if (this.$route.name === 'Application') {
        this.selected_app = this.$route.params.name;
        this.$emit('app-selected', this.selected_app);
      }
      this.doDropdown();
    },
    methods: {
      doDropdown() {
        if (this.dropdown) {
          return;
        }
        if (!this.ready) {
          setTimeout(() => {this.doDropdown()}, 1000);
          return;
        }
        let dd = window.jQuery("#apps-dropdown");
        if (!dd) {
          setTimeout(() => {this.doDropdown()}, 1000);
          return;
        }
        this.dropdown = dd.waDropdown();
      },
      selectApp(app_id) {
        this.selected_app = app_id;
        this.$emit('app-selected', app_id);
        this.$router.push({name: 'Application', params: {name: app_id}});
      },
      async loadSwagger(app_id) {
        const swagger = await new SwaggerClient({ url: window.appState.baseUrl + '?module=swaggerRead&app=' + app_id});
        if ('status' in swagger.spec && swagger.spec.status === 'fail') {
          console.log('Swagger для ' + app_id + ' не шмогла');
        } else {
          this.$store.commit('load_app_methods', { app_id: app_id, methods: swagger.spec.paths });
          this.$store.commit('load_swagger', { app_id: app_id, swagger: swagger });
        }
      },
      async loadMethodList() {
        const resp = await this.axios.get('?module=methodlist');
        if (typeof resp.data === 'object') {
          const methods = resp.data.data.methods;
          this.$store.commit('load_methods', methods);
        } else {
          this.emitter.emit('error', 'Invalid server response: ' + resp.data.substring(0, 150));
          this.state.error = true;
        }
      }
    }
}
</script>


<style scoped>
.brick .icon {
  width: 3rem; 
  height: 3rem; 
  margin-right: 0.5rem; 
}
.brick .count {
  color: var(--gray);
  display: block;
  white-space: nowrap;
}
.brick .empty {
  color: var(--light-gray);
  display: block;
  white-space: nowrap;
}
.brick .app-wrapper {
  vertical-align: top; 
  padding-top: 0.25rem; 
  display: inline-block;
  overflow: hidden; 
  text-overflow: ellipsis;
}
.brick .app-name {
  font-size: 1rem;
  padding-bottom: 0.5rem; 
  display: block;
  white-space: nowrap;
}
.app-icon {
  background-size: 1.25rem 1.25rem;
  left: -0.125rem;
  top: -0.125rem;
  position: relative;
  flex: 0 0 1.25rem;
  max-width: 1.25rem;
  max-height: 1.25rem;
  width: 1.25rem;
  height: 1.25rem;
}
.bricks {
  width: 100%;
  display: block;
}
.bricks .dropdown-body {
  width: calc(100% - 2.5rem);
  min-width: calc(100% - 2.5rem);
  left: -1px;
}
</style>
