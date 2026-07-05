<template>
    <div v-if="is_scalar">
        <div v-if="deep == 0">
            <span v-if="is_empty" class="badge squared light-gray small">{{ $t('empty') }}</span>
            <span v-else :class="is_number ? ['json-number'] : ( is_boolean ? ['json-boolean'] : ['json-string'])">{{ structure }}</span>
        </div>
    </div>
    <div v-else-if="is_object">
        <div class="fields-group" v-if="!collapse">
            <div v-for="[param, val] of Object.entries(structure)" :key="param">
                <div v-if="param != '$$ref'" :class="compressed(param) ? ['compressed'] : ['custom-my-4']">
                    <div class="field">
                        <div class="name">
                            <button v-if="isEmpty(val) || isScalar(val)" class="button light-gray small not-disabled" disabled="disabled">
                                {{ param }}
                            </button>
                            <a v-else href="javascript:void(0);" @click="toggleCompress(param)" class="button light-gray small nowrap">
                                {{ param }}
                                <span class="custom-ml-8 icon" v-if="!compressed(param)"><i class="fas fa-caret-right"></i></span>
                                <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
                            </a>
                        </div>
                        <div class="value">
                            <span v-if="isScalar(val)">
                                <div v-if="!collapse">
                                    <span v-if="isEmpty(val)">ПУСТО</span>
                                    <span v-else :class="isNumber(val) ? ['json-number'] : ( isBoolean(val) ? ['json-boolean'] : ['json-string'])">{{ val }}</span>
                                </div>
                            </span>
                            <span v-else-if="isEmpty(val)">
                                <span v-if="isArray(val)" class="badge squared light-gray small">{{ $t('empty array') }}</span>
                                <span v-else class="badge squared light-gray small">{{ $t('empty object') }}</span>
                            </span>
                        </div>
                    </div>
                    <structure 
                        v-if="!isScalar(val) && !isEmpty(val)"
                        :structure="val"
                        :deep="new_deep"
                        :collapse="!compressed(param)"
                    />
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="is_array">
        <div v-if="!collapse">
            <div v-for="(val, index) in structure" :key="index">
                <span v-if="isScalar(val)">
                    <span v-if="isEmpty(val)">ПУСТО</span>
                    <span v-else :class="isNumber(val) ? ['json-number'] : ( isBoolean(val) ? ['json-boolean'] : ['json-string'])">{{ val }}</span>
                </span>
                <span v-else-if="isEmpty(val)">
                    <span v-if="isArray(val)" class="badge squared light-gray small">{{ $t('empty array') }}</span>
                    <span v-else class="badge squared light-gray small">{{ $t('empty object') }}</span>
                </span>
                <structure 
                    v-else
                    :structure="val"
                    :deep="new_deep"
                    :collapse="false"
                />
            </div>
        </div>
    </div>
    <div v-else>
        <p>
            {{ $t('Unknown field type') }} - <strong>{{ structure }}</strong>.
        </p>
    </div>
</template>

<script>
export default {
    name: "Structure",
    props: {
        structure: [Object, Array],
        collapse: Boolean,
        deep: Number,
        hidetype: Boolean
    },
    data() {
        return {
            new_deep: this.deep + 1,
            compress: {}
        };
    },
    computed: {
        is_array() {
            return this.isArray(this.structure);
        },
        is_object() {
            return this.isObject(this.structure);
        },
        is_number() {
            return this.isNumber(this.structure);
        },
        is_boolean() {
            return this.isBoolean(this.structure);
        },
        is_scalar() {
            return this.isScalar(this.structure);
        },
        is_empty() {
            return this.isEmpty(this.structure);
        }
    },
    mounted() {
        
    },
    methods: {
        isArray(val) {
            return Array.isArray(val);
        },
        isObject(val) {
            return val !== null && !Array.isArray(val) && typeof val === 'object';
        },
        isNumber(val) {
            return typeof val === 'number';
        },
        isBoolean(val) {
            return typeof val === 'boolean';
        },
        isScalar(val) {
            return !this.isArray(val) && !this.isObject(val);
        },
        isEmpty(val) {
            return val === null
                || val === ''
                || Array.isArray(val) && val.length === 0
                || typeof val === 'object' && Object.entries(val).length === 0
        },
        doCollapseNestedArrayDeep(param) {
            return this.isArrayNested(param)
                && 'items' in this.schema.properties[param]
                //&& this.schema.properties[param].items.type === 'object'
        },
        isArrayNested(param) {
            return 'properties' in this.schema 
                && param in this.schema.properties 
                && this.schema.properties[param].type === 'array'
        },
        compressed(param) {
            if (!(param in this.compress)) {
                this.compress[param] = this.deep < 2;
            }
            return this.compress[param];
        },
        toggleCompress(param) {
            this.compress[param] = !this.compressed(param);
        }
    }
}
</script>

<style scoped>
.compressed {
    color: var(--text-color);
    padding: 1rem 0.75rem;
    margin: 0.5rem 0;
    background: var(--background-color-blockquote);
    border-radius: 0.5rem;
}
.fields-group {
    padding: 1rem 0.75rem;
    border-radius: 0.5rem;
}
.fields-group:not(:first-child) {
    margin-top: 1rem;
}
button.light-gray.not-disabled:disabled:not(.outlined) {
    color: var(--text-color-input) !important;
}
</style>