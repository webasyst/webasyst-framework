import { createStore } from 'vuex'

export default createStore({
  state: {
    current_user_id: null,
    tokens: {},
    users: {},
    apps: {},
    counts: {},
    method_groups: {},
    swagger: {},
    loaded: false
  },
  mutations: {
    add_token(state, payload) {
      state.tokens[payload.user_id] = payload.token;
    },
    delete_token(state, user_id) {
      delete state.tokens[user_id];
    },
    set_current_user_id(state, user_id) {
      state.current_user_id = user_id;
    },
    load_users(state, users) {
      state.users = users;
      state.current_user_id = null;
      state.tokens = {};
    },
    load_apps(state, apps) {
      state.apps = apps;
    },
    load_methods(state, methods) {
      Object.entries(methods).forEach(([app_id, app_methods]) => {
        Object.entries(app_methods).forEach(([method_name, value]) => {
          helpers.method_to_group(state, app_id, method_name, value);
        });
      });
    },
    load_app_methods(state, payload) {
      if ('methods' in payload && typeof payload.methods !== 'undefined' && payload.methods != null) {
        Object.entries(payload.methods).forEach(([method_name, value]) => {
          value['isSwagger'] = true;
          helpers.method_to_group(state, payload.app_id, method_name, value);
        });
      }
    },
    load_method(state, payload) {
      helpers.method_to_group(state, payload.app_id, payload.method_name, payload.method);
    },
    load_swagger(state, payload) {
      if ('servers' in payload.swagger.spec && Array.isArray(payload.swagger.spec.servers)) {
        payload.swagger.spec.servers.forEach( (el, idx) => {
          if (el.url === '/api.php') {
            payload.swagger.spec.servers[idx].url = window.appState.rootUrl + 'api.php';
          }
        });
      }
      state.swagger[payload.app_id] = payload.swagger;
    },
    load_finish(state) {
      state.loaded = true;
    }
  },
  getters: {
    current_token: state => {
      if (!state.current_user_id) return null;
      return state.tokens[state.current_user_id];
    },
    current_user: (state) => {
      return state.users[state.current_user_id];
    },
    getApp: (state) => (app_id) => {
      return state.apps[app_id];
    },
    getMethod: (state) => (app_id, method_name) => {
      if (!(app_id in state.method_groups)) {
        return null;
      }
      const group = method_name.split('.')[1];
      if (!(group in state.method_groups[app_id])) {
        return null;
      }
      if (!(method_name in state.method_groups[app_id][group])) {
        return null;
      }
      return state.method_groups[app_id][group][method_name];
    }
  },
  modules: {
  }
});

const helpers = {
  method_to_group(state, app_id, method_name, method_value) {
    method_name = method_name.replace(/^\//, '');
    const group = method_name.split('.')[1];
    if (!(app_id in state.method_groups)) {
      state.method_groups[app_id] = {};
    }
    if (!(group in state.method_groups[app_id])) {
      state.method_groups[app_id][group] = {};
    }
    if (!(method_name in state.method_groups[app_id][group])) {
      if (app_id in state.counts) {
        state.counts[app_id]++;
      } else {
        state.counts[app_id] = 1;
      }
    }
    if (!(method_name in state.method_groups[app_id][group]) 
        || !('isSwagger' in state.method_groups[app_id][group][method_name]) 
        || ('isSwagger' in method_value && method_value.isSwagger)
    ) {
      // Сохраняем метод, только если его еще нет в списке 
      // ИЛИ если мы сохраняем Swagger-описание - тогда Swagger заменяет данные из Reflection
      state.method_groups[app_id][group][method_name] = method_value;
      state.method_groups[app_id][group][method_name]['http_type'] = helpers.getHttpType(method_value);
    }
  },

  getHttpType(method_value) {
    if ('type' in method_value) {
      if (Array.isArray(method_value.type)) {
        return method_value.type[0].toLowerCase();
      } else {
        return method_value.type.toLowerCase();
      }
    }
    if ('get' in method_value) {
      return 'get';
    }
    if ('post' in method_value) {
      return 'post';
    }
    if ('put' in method_value) {
      return 'put';
    }
    if ('delete' in method_value) {
      return 'delete';
    }
    return 'unknown';
  }
};
