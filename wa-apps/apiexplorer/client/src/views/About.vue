<template>
    <h1>{{ $t('How it works') }}</h1>
    <div v-if="state.loading" class="box skeleton custom-pt-32" style="width: 70%;">
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
    </div>
    <div v-else v-html="page"></div>
</template>

<script>
export default {
    name: 'About',
    data() {
        return {
            state: {
                loading: false,
                error: false
            },
            page: ""
        };
    },
    async mounted() {
        this.state.loading = true;
        const resp = await this.axios.get('?module=pages&action=about');
        this.page = resp.data;
        this.state.loading = false;
        document.title = 'About — API Explorer';
    }
}
</script>
