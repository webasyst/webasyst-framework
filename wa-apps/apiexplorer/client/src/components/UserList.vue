<template>
  <h5 class="heading">{{ $t('Users') }}</h5>
  <div v-if="state.loading" class="box skeleton custom-pt-32">
    <span class="skeleton-list"></span>
    <span class="skeleton-list"></span>
    <span class="skeleton-list"></span>
    <span class="skeleton-list"></span>
    <span class="skeleton-list"></span>
    <span class="skeleton-list"></span>
  </div>
  <div v-else-if="state.error" class="opacity-50 custom-mt-32 custom-mx-16 custom-p-8">
    <i class="fas fa-skull"></i> {{ $t('error') }}
  </div>
  <ul class="menu mobile-friendly" v-else>
    <li v-for="user_id in Object.keys(users)" :key="user_id" :class="[current_user_id === user_id && 'selected']">
      <a href="javascript:void(0)" @click="setCurrentUser(user_id)">
        <span class="icon"><i class="userpic" :style='{ "background-image": "url(" + users[user_id].photo_url_16 + ")"}'></i></span>
        <span>{{ users[user_id].name }}</span>
      </a>
    </li>
  </ul>
</template>

<script>
export default {
    name: "UserList",
    emits: ["user-selected"],
    data() {
        return {
            state: {
              loading: false,
              error: false
            }
        };
    },
    async mounted() {
      if (!this.users || Object.keys(this.users).length === 0) {
        this.loadUsers();
      }
    },
    computed: {
      users() {
        return this.$store.state.users;
      },
      current_user_id() {
        return this.$store.state.current_user_id;
      },
      current_token() {
        return this.$store.getters.current_token;
      }
    },
    methods: {
      async loadUsers() {
        this.state.loading = true;
        this.state.error = false;
        const resp = await this.axios.get('?module=userlist');
        if (typeof resp.data === 'object') {
          this.$store.commit('load_users', resp.data.data.users);
          this.setCurrentUser(window.appState.user_id);
        } else {
          this.emitter.emit('error', { 
            title: "Can't load user list", 
            description: resp.data.substring(0, 2000), 
            contentType: resp.headers['content-type'] || ''
          });
          this.state.error = true;
        }
        this.state.loading = false;
      },
      async setCurrentUser(user_id) {
        if (!(user_id in this.users)) {
          return;
        }
        this.$store.commit('set_current_user_id', user_id);
        this.$emit('user-selected', user_id);
        if (!this.current_token) {
          const params = new URLSearchParams();
          params.append("module", "getToken");
          params.append("user", this.users[user_id].login);
          const resp = await this.axios.get('?' + params.toString());
          if (typeof resp.data === 'object') {
            this.$store.commit('add_token', {user_id: user_id, token: resp.data.data.token});
          } else {
            this.emitter.emit('error', 'Invalid server response: ' + resp.data.substring(0, 150));
          }
        }
      }
    }
}
</script>
