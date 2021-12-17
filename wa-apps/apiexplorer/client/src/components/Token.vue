<template>
    <span v-if="value">
        <span v-if="show_token">
            <a href="javascript:void(0);" class="custom-mx-8" :title="$t('Hide token')" @click="show_token=false">
                <i class="fas fa-eye-slash"></i>
            </a>
            <span class="hint custom-mr-8">{{ value }}</span>
            <span v-if="delete_status.loading" class="spinner hint"></span>
            <a v-else href="javascript:void(0);" class="hint nowrap" :title="$t('Delete token')" @click="deleteToken">
                <i class="fas fa-trash-alt"></i> {{ $t('delete') }}
            </a>
        </span>
        <a href="javascript:void(0);" v-else class="custom-mx-8" :title="$t('Show token')" @click="show_token=true"><i class="fas fa-eye"></i></a>
    </span>
</template>

<script>
export default {
    name: "Token",
    props: {
        value: String,
        user: Object
    },
    data() {
        return {
            show_token: false,
            delete_status: {
                loading: false,
                error: false
            }
        };
    },
    methods: {
        async deleteToken() {
            if (!this.user || !this.value) {
                return;
            }
            this.delete_status.error = false;
            this.delete_status.loading = true;
            const params = new URLSearchParams();
            params.append("module", "deleteToken");
            params.append("user", this.user.login);
            const post_params = new URLSearchParams();
            post_params.append("token", this.value);
            await this.axios.post('?' + params.toString(), post_params);
            this.$store.commit('delete_token', this.user.id);
            this.$store.commit('set_current_user_id', 0);
            this.delete_status.loading = false;
            this.show_token = false;
        }
    }
}
</script>
