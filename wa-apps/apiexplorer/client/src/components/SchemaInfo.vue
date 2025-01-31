<template>
    <div v-if="oneOf">
        <div v-if="!hidetype" class="flexbox middle">
            <a v-if="collapsable" href="javascript:void(0);" @click="toggleCollapse" class="button light-gray small nowrap">
                <span v-if="'oneOf' in schema">{{ $t("One of:") }}</span>
                <span v-else-if="'anyOf' in schema">{{ $t("Any of:") }}</span>
                <span class="custom-ml-8 icon" v-if="collapse"><i class="fas fa-caret-right"></i></span>
                <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
            </a>
            <span v-else>
                <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-if="'oneOf' in schema">{{ $t("One of:") }}</button>
                <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-else-if="'anyOf' in schema">{{ $t("Any of:") }}</button>
            </span>
            <span v-if="!collapse" class="toggle">
                <span v-for="(el, idx) in oneOf" :key="'tab' + idx" :class="tab_selected == idx ? ['selected'] : []" @click="switchTab(idx)">
                    {{ $t("Case") }} {{ idx+1 }}
                </span>
            </span>
            <span v-if="!collapse" class="custom-ml-4">
                <span v-for="(el, idx) in oneOf" :key="'tabtype' + idx">
                    <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-if="tab_selected == idx">{{ getType(el) }}</button>
                </span>
            </span>
        </div>

        <div v-if="!collapse">
            <div v-if="hidetype" class="flexbox middle">
                <span class="toggle">
                    <span v-for="(el, idx) in oneOf" :key="'tab' + idx" :class="tab_selected == idx ? ['selected'] : []" @click="switchTab(idx)">
                        {{ $t("Case") }} {{ idx+1 }}
                    </span>
                </span>
                <span class="custom-ml-4">
                    <span v-for="(el, idx) in oneOf" :key="'tabtype' + idx">
                        <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-if="tab_selected == idx">{{ getType(el) }}</button>
                    </span>
                </span>
            </div>

            <div v-for="(el, idx) in oneOf" :key="'tabcontent' + idx">
                <schema-info 
                    :schema="el"
                    :deep="deep"
                    :description="el.description || schema.description"
                    :collapsable="false"
                    :hidetype="true"
                     v-if="tab_selected == idx"
                />
            </div>
        </div>
    </div>
    <span v-else-if="isScalar(schema)">
        <span v-if="!hidetype">
            <button class="button light-gray small nowrap not-disabled" disabled="disabled">
                {{ schema.type }} 
                <span class="badge smaller light-gray squared" v-if="schema.format">{{ schema.format }}</span>
            </button>
        </span>
        <span v-else-if="schema.format" class="badge smaller light-gray squared">{{ schema.format }}</span>
        <a v-if="schema.enum && collapsable" href="javascript:void(0);" @click="collapse=!collapse" class="button light-gray smaller nowrap custom-ml-4">
            enum
            <span class="custom-ml-8 icon" v-if="collapse"><i class="fas fa-caret-right"></i></span>
            <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
        </a>
        <ul v-if="schema.enum && !collapse" class="small enum">
            <li v-for="el of schema.enum" :key="el">{{ (el == null || el === '') ? $t("empty value") : el }}</li>
        </ul>

        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
        <div v-if="schema.example" class="hint description">{{ $t("Example:") }} <strong>{{ schema.example }}</strong></div>
    </span>
    <div v-else-if="schema.type === 'object'">
        <span v-if="!hidetype">
            <a v-if="collapsable" href="javascript:void(0);" @click="toggleCollapse" class="button light-gray small nowrap">
                {{ schema.type }}
                <span class="custom-ml-8 icon" v-if="collapse"><i class="fas fa-caret-right"></i></span>
                <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
            </a>
            <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-else>{{ schema.type }}</button>
        </span>

        <div class="fields-group" v-if="!collapse && 'properties' in schema">
            <div v-for="[param, val] of Object.entries(schema.properties)" :key="param" class="custom-pb-8">
                <div :class="compressed(param) ? ['compressed'] : []">
                    <div class="field">
                        <div class="name">
                            <span :class="compressed(param) ? ['button', 'white', 'small', 'label', 'disabled'] : []">{{ param }}:</span>
                        </div>
                        <div class="value">
                            <schema-info 
                                :schema="val"
                                :deep="new_deep"
                                :description="val.description"
                                :collapsable="!compressed(param)"
                                :hidetype="false"
                                v-if="isScalar(val)"
                            />
                            <div v-else>
                                <a href="javascript:void(0);" @click="toggleCompress(param)" class="button light-gray small nowrap">
                                    <span>{{ getType(val) }}</span>
                                    <span class="custom-ml-8 icon" v-if="!compressed(param)"><i class="fas fa-caret-right"></i></span>
                                    <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
                                </a>
                                <span v-if="compressed(param) && isArray(val) && 'items' in val">
                                    <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-if="['object', 'array', 'oneOf', 'anyOf'].includes(getType(val.items))">{{ val.items.type }}</button>
                                    <schema-info 
                                        :schema="val.items"
                                        :deep="new_deep"
                                        :description="'description' in val.items ? val.items.description : ''"
                                        :collapsable="true"
                                        :hidetype="false"
                                        v-else
                                    />
                                </span>
                                <div :class="!compressed(param) ? ['hint', 'description'] : ['hint', 'description', 'hstrong']" v-if="val.description" v-html="markdown(val.description)"></div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!isScalar(val) && !isSimpleItemsArray(val) && compressed(param)">
                        <schema-info 
                            :schema="val"
                            :deep="new_deep"
                            :description="val.description"
                            :collapsable="!compressed(param)"
                            :hidetype="true"
                            v-if="param in schema.properties"
                        />
                        <pre v-else class="small" v-html="prettifyJson(val)"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="schema.type === 'array'">
        <div v-if="!hidetype">
            <a href="javascript:void(0);" @click="toggleCollapse" class="button light-gray small nowrap">
                {{ schema.type }}
                <span class="custom-ml-8 icon" v-if="collapse"><i class="fas fa-caret-right"></i></span>
                <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
            </a>
            <span v-if="!collapse && 'items' in schema">

                <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-if="'type' in schema.items">{{ schema.items.type }}</button>
                <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-else-if="'oneOf' in schema.items">{{ $t("One of:") }}</button>
                <button class="button light-gray small nowrap not-disabled" disabled="disabled" v-else-if="'anyOf' in schema.items">{{ $t("Any of:") }}</button>
            </span>

            <div :class="collapse ? ['hint', 'description'] : ['hint', 'description', 'hstrong']" v-if="description" v-html="descriptionMarkdown"></div>
            <div :class="collapse ? ['hint', 'description'] : ['hint', 'description', 'hstrong']" v-if="'items' in schema && schema.items.description" v-html="markdown(schema.items.description)"></div>
        </div>
        <div v-if="!collapse">
            <schema-info 
                :schema="schema.items"
                :deep="new_deep"
                :collapsable="false"
                :hidetype="true"
                v-if="'items' in schema"
            />
        </div>
    </div>
    <div v-else>
        <p>
            {{ $t('Unknown field type') }} - <strong>{{ schema.type }}</strong>. 
        </p>
        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
    </div>
</template>

<script>
import { marked } from 'marked';
import { prettyPrintJson } from 'pretty-print-json';
export default {
    name: "SchemaInfo",
    props: {
        schema: Object,
        deep: Number,
        description: String,
        collapsable: Boolean,
        hidetype: Boolean
    },
    data() {
        return {
            new_deep: this.deep + 1,
            oneOf: null,
            tab_selected: 0,
            collapse: this.collapsable && this.deep > 0,
            compress: {}
        };
    },
    computed: {
        descriptionMarkdown() {
            return marked.parse(this.description);
        },
    },
    mounted() {
        if (!('type' in this.schema)) {
            if ('oneOf' in this.schema) {
                this.oneOf = this.schema.oneOf;
            } else if ('anyOf' in this.schema) {
                this.oneOf = this.schema.anyOf;
            }
        }
    },
    methods: {
        markdown(str) {
            if (!str) {
                return '';
            }
            return marked.parse(str);
        },
        prettifyJson(obj) {
            return prettyPrintJson.toHtml(obj);
        },
        switchTab(tab) {
            this.tab_selected = tab;
        },
        toggleCollapse() {
            this.collapse = !this.collapse;
        },
        compressed(param) {
            if (!(param in this.compress)) {
                this.compress[param] = false;
            }
            return this.compress[param];
        },
        toggleCompress(param) {
            this.compress[param] = !this.compressed(param);
        },
        isScalar(schema) {
            return 'type' in schema && (schema.type === 'string' || schema.type === 'integer' || schema.type === 'number' || schema.type === 'boolean');
        },
        isArray(schema) {
            return 'type' in schema && schema.type === 'array';
        },
        isSimpleItemsArray(schema) {
            return 'items' in schema && 'type' in schema.items
                && schema.items.type !== 'object'
                && schema.items.type !== 'array'
        },
        getType(schema) {
            if ('type' in schema) {
                return schema.type;
            }
            if ('oneOf' in schema) {
                return 'oneOf';
            }
            if ('anyOf' in this.schema) {
                return 'anyOf';
            }
            return null;
        }
    }
}
</script>

<style>
.description {
    margin-bottom: 0.5em;
}
.description p, .description ul {
    margin: 0;
}
.description ul {
    padding-left: 1.25rem;
}
</style>

<style scoped>
.compressed {
    color: var(--text-color);
    padding: 1rem 0.75rem;
    background: var(--background-color-blockquote);
    border-radius: 0.5rem;
}
ul.enum {
    padding-left: 1.25rem;
    margin: 0;
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
.hint.description {
    color: var(--text-color-hint-strong);
}
.label {
    text-align: left;
}
</style>