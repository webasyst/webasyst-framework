<template>
  <div v-if="!loaded" class="box skeleton custom-pt-32" style="width: 70%;">
    <span class="skeleton-line" style="width: 50%;"></span>
    <span class="skeleton-line"></span>
    <span class="skeleton-line"></span>
    <span class="skeleton-line"></span>
    <span class="skeleton-line"></span>
  </div>
  <div v-else-if="!!app_id && !!method_groups[app_id]" class="custom-pt-32">
    <div v-for="group_name in Object.keys(method_groups[app_id])" :key="group_name">
      <h5 class="heading">{{ group_name }}</h5>
      <ul class="menu mobile-friendly">
        <li v-for="method_name in Object.keys(method_groups[app_id][group_name])" :key="method_name" :class="[selected_method === method_name && 'selected']">
            <a href="javascript:void(0);" @click="selectMethod(method_name)">
                <div v-if="typeof method_groups[app_id][group_name][method_name] === 'object'">
                  <div :class="['badge', 'small', 'squared', 'a-' + method_groups[app_id][group_name][method_name].http_type]">
                    {{ method_groups[app_id][group_name][method_name].http_type.toUpperCase() }}
                  </div>
                </div>
                <span class="custom-pl-8">{{ method_name }}</span>
            </a>
        </li>
      </ul>
    </div>
  </div>
  <div v-else-if="!!app_id && !method_groups[app_id]" class="custom-p-32 align-center">
    <i class="fas fa-times-circle" style="font-size: 8rem; opacity: 0.15;"></i>
    <h4>{{ $t('no-api-message', [$store.state.apps[app_id].name]) }}</h4>
    <p class="hint" v-html="$t('no-api-hint')"></p>
  </div>
</template>

<script>
export default {
    name: "MethodList",
    props: {
        app_id: {
            type: String,
            default: ""
        },
    },
    data() {
        return {
        };
    },
    computed: {
      method_groups() {
        return this.$store.state.method_groups;
      },
      loaded() {
        return this.$store.state.loaded;
      },
      selected_method() {
        if (this.$route.name === 'Method') {
          return this.$route.params.name;
        } else {
          return "";
        }
      }
    },
    methods: {
      selectMethod(name) {
        this.$router.push({name: 'Method', params: { name: name }});
      }
    }
}
</script>
