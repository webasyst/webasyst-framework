<template>
  <div v-if="!ready" class="box skeleton custom-pt-32">
    <span class="skeleton-header custom-mb-0"></span>
  </div>
  <div v-else-if="state.error" class="highlighted bg-light-gray opacity-70 custom-mt-32 custom-mx-16 custom-p-8">
    <i class="fas fa-skull"></i> {{ $t('error') }}
  </div>
  <div v-else class="bricks dropdown" id="apps-dropdown">
    <div :class="['brick', 'selected', 'full-width', 'dropdown-toggle', !selected_app && 'pulsar' ]">
      <div v-if="!selected_app">
        {{ $t('Select app') }}
      </div>
      <div v-else class="flexbox middle" @click="selectApp(selected_app)">
        <img :src="rootUrl + apps[selected_app].icon['48']" class="icon" :title="apps[selected_app].name"/>
        <div class="app-wrapper">
          <span class="app-name">{{ apps[selected_app].name }}</span>
          <span v-if="$store.state.counts[selected_app]" class="count">{{ $store.state.counts[selected_app] }} API</span>
          <span v-else class="empty">{{ $t('No API') }}</span>
        </div>
      </div>
    </div>
    <div class="dropdown-body">
        <ul class="menu">
            <li v-for="app in apps" :key="app.app_id" :class="[ app.id === selected_app && 'selected']">
                <a href="javascript:void(0);" @click="selectApp(app.id)">
                  <span v-if="app.id in $store.state.counts" class="count">{{ $store.state.counts[app.id] }}</span>
                  <span v-else class="count">0</span>
                  <i class="icon16 app-icon" :title="app.name" :style='{ "background-image": "url(" + rootUrl + app.icon["16"] + ")" }'></i>
                  <span class="nowrap">{{ app.name }}</span>
                </a>
            </li>
        </ul>
    </div>
  </div>
</template>

<script>
import SwaggerClient from 'swagger-client';
import { swaggerUrl } from '@/funcs'
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
      },
      rootUrl() {
        return window.appState.rootUrl;
      }
    },
    async mounted() {
      if (!this.apps || Object.keys(this.apps).length === 0) {
        this.state.loading = true;
        this.state.error = false;
        const resp = await this.axios.get('?module=applist');
        if (typeof resp.data === 'object') {
          const apps = resp.data.data.apps;
          this.$store.commit('load_apps', apps);
        } else {
          this.emitter.emit('error', {
            title: "Can't load app list",
            description: resp.data.substring(0, 2000),
            contentType: resp.headers['content-type'] || ''
          });
          this.state.error = true;
        }
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
      } else {
        const nonEmptyApiApps = Object.keys(this.$store.state.counts);
        const apps = Object.keys(this.$store.state.method_groups);
        if (nonEmptyApiApps.length > 0) {
          this.selectApp(nonEmptyApiApps[0]);
        } else if (apps.length > 0) {
          this.selectApp(apps[0]);
        }
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
        const swagger = await new SwaggerClient({ url: swaggerUrl(app_id) });
        if (!('spec' in swagger) || Object.entries(swagger.spec).length == 0 || ('errors' in swagger && swagger.errors.length > 0) || ('status' in swagger.spec && swagger.spec.status === 'fail')) {
          this.emitter.emit('error', {
            title: "Can't load Open API description for " + app_id,
            description: (swagger.errors.length > 0) ? swagger.errors : '',
            contentType: ''
          });
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
          this.emitter.emit('error', {
            title: "Can't load method list",
            description: resp.data.substring(0, 2000),
            contentType: resp.headers['content-type'] || ''
          });
        }
      }
    }
}
</script>
