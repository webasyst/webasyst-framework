<template>
    <strong>{{ parameter.name }}</strong>
    <em v-if="parameter.is_optional == 1" class="small">
        ({{ $t('optional') }})
    </em>
    <span v-else class="state-caution"> *</span>
    
    <span v-if="parameter.description.length <= DESC_LIMIT"> : {{ parameter.description }}</span>

    <span v-if="parameter.description.length > DESC_LIMIT">
        <button v-if="show_description" class="nobutton small" @click="show_description=false">{{ $t('hide') }}</button>
        <button v-else class="nobutton small" @click="show_description=true">{{ $t('show') }}</button>
    </span>

    <div v-if="parameter.description.length > DESC_LIMIT && show_description" style="white-space: pre-line;">{{ parameter.description }}</div>
</template>

<script>
export default {
    name: "ParameterDescription",
    props: {
        parameter: Object
    },
    data() {
        return {
            show_description: false,
            DESC_LIMIT: 150
        }
    },
    mounted() {
        this.show_description = this.parameter.description.length <= this.DESC_LIMIT;
    }
}
</script>
