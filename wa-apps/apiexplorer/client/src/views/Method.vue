<template>
    <div v-if="state.loading" class="box skeleton" style="width: 70%;">
        <span class="skeleton-header" style="width: 60%;"></span>
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
        <span class="skeleton-line"></span>
    </div>
    <div v-else>

        <nav class="tablet-only"><a href="javascript:void(0);" @click="backToApp()"><i class="fas fa-arrow-left"></i> {{ app.name }}</a></nav>
        <nav class="mobile-only"><a href="javascript:void(0);" @click="backToApp()"><i class="fas fa-arrow-left"></i> {{ app.name }}</a></nav>
        <div v-if="!!name" style="display: flex; align-items: flex-end;">
            <div :class="['badge', 'squared', 'a-' + type.toLowerCase(), 'custom-mb-4', 'custom-mr-8']">
                {{ type }}
            </div>
            <h1 class="break-word">
                {{ name }}
            </h1>
        </div>
        <div v-if="!!name" class="flexbox full-width custom-mt-32">
            <div>
                <p v-if="summary">
                    {{ summary }}
                    <span v-if="externalDocUrl" class="badge button-light-gray small nowrap">
                        <a :href="externalDocUrl">
                            <i class="fas fa-info-circle"></i> {{ $t('Documentation') }} <i class="fas fa-angle-double-right"></i>
                        </a>
                    </span>
                </p>
                <p v-else>
                    <span v-if="externalDocUrl">
                        <a :href="externalDocUrl">
                            <i class="fas fa-info-circle"></i> {{ $t('Method documentation') }} <i class="fas fa-angle-double-right"></i>
                        </a>
                    </span>
                    <em v-else class="gray">&lt;{{ $t('No method description') }}&gt;</em>
                </p>
            </div>
            <div v-if="isSwagger" class="nowrap">
                <a v-if="show_method_description" href="javascript:void(0)" :title="$t('Hide OpenAPI method description')" @click="show_method_description=false" class="button circle gray"><i class="fas fa-times"></i></a>
                <a v-else href="javascript:void(0)" :title="$t('Show OpenAPI method description')" @click="show_method_description=true" class="button circle success"><i class="fas fa-book-open"></i></a>
                <a :href="swaggerDescriptionUrl" :title="$t('Download OpenAPI document')" class="button circle success"><i class="fas fa-file-download"></i></a>
            </div>
            <div v-else></div>
        </div>

        <div class="custom-mt-8" v-if="isSwagger && show_method_description">
            <ul class="tabs">
                <li class="grey">{{ $t("OpenAPI method description:") }}</li>
                <li :class="description_tab_selected == 'raw' ? ['selected'] : []">
                    <a href="javascript:void(0);" @click="description_tab_selected='raw'">
                        {{ $t("Raw description") }}
                    </a>
                </li>
                <li :class="description_tab_selected == 'structure' ? ['selected'] : []">
                    <a href="javascript:void(0);" @click="description_tab_selected='structure'">
                        {{ $t("Structure") }}
                    </a>
                </li>
                <li><button href="javascript:void(0)" @click="show_method_description=false" class="smaller nobutton">{{ $t("hide description") }}</button></li>
            </ul>
            <div :class="description_tab_selected == 'structure' ? ['fields'] : ['hidden']" style="margin-top: -1rem;">
                <structure :structure="openApiDescription" :deep="0" :collapse="false" />
            </div>
            <div :class="description_tab_selected == 'raw' ? [] : ['hidden']">
                <code-block :value="openApiDescription" />
            </div>
        </div>

        <div v-if="method.bazaexplorerdata && method.bazaexplorerdata.returns">
            <div class="bold custom-mb-8">
                {{ $t('Response data format') }}
                <span v-if="method.bazaexplorerdata.returns.length > 500">
                    <button v-if="show_returns_description" class="nobutton small" @click="show_returns_description=false">{{ $t('hide') }}</button>
                    <button v-else class="nobutton small" @click="show_returns_description=true">{{ $t('show') }}</button>
                </span>
            </div>
            <div class="hint custom-mb-16" v-if="show_returns_description" v-html="method.bazaexplorerdata.returns"></div>
        </div>

        <div class="fields custom-mb-32">
            <div class="fields-group">
                <div class="field">
                    <div class="name">
                        URL
                    </div>
                    <div class="value bold break-word">
                        {{ methodUrl }}
                    </div>
                </div>
                <div class="field">
                    <div class="name">
                        {{ $t('Token') }}
                    </div>
                    <div class="value">
                        <span v-if="!user" class="gray">
                            <em>&lt;{{ $t('No user selected') }}&gt;</em>
                        </span>
                        <span v-else>
                            <span class="icon userpic userpic-20" :style='{ "background-image": "url(" + user.photo_url_32 + ")"}'></span>
                            {{ user.name }}
                            <token :value="api_token" :user="user" />
                            <span v-if="!!user && !api_token" class="spinner custom-mx-8"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isSwagger" class="fields">
            <div v-if="methodQueryParams.length > 0" class="fields-group">
                <div>{{ $t('Query string parameters') }}</div>
                <div v-for="param in methodQueryParams" :key="param.name" class="field">
                    <div class="name for-input">
                        <label :for="param.name">
                            {{ param.name }}
                            <span v-if="param.required" class="state-caution">*</span>
                        </label>
                    </div>
                    <div class="value">
                        <schema-input
                            :name="param.name"
                            :schema="param.schema"
                            :required="param.required"
                            :description="param.description"
                            :post_body_type="post_body_type"
                            v-model="api_params[param.name]"
                            @updated="checkRequestDataPresence"
                        />
                    </div>
                </div>
            </div>
            <div v-if="type !== 'GET' && post_data_schema" class="fields-group">
                <div>{{ $t('Request body parameters') }}</div>
                <schema-input
                    name="body"
                    :schema="post_data_schema"
                    :description="post_data_schema.description || ''"
                    :post_body_type="post_body_type"
                    :required="false"
                    v-model="api_request_body"
                    @updated="checkRequestDataPresence"
                />
            </div>
        </div>
        <div v-else>

            <div class="fields">
                <div class="fields-group">
                    <div class="field">
                        <div class="name">
                            {{ $t('URL parameters') }}
                        </div>
                        <div class="value">
                            <input type="text" v-model="api_query_string" style="width: 100%" class="custom-mb-4" @input="checkRequestDataPresence" placeholder="key1=value1&amp;key2=value2" />
                            <div v-if="method.bazaexplorerdata && method.bazaexplorerdata.params_get" class="hint custom-mt-0">
                            <ul>
                                <li v-for="param in method.bazaexplorerdata.params_get" :key="param.name" class="custom-mb-4">
                                    <parameter-description :parameter="param" />
                                </li>
                            </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="type !== 'GET'">
                <ul class="tabs bordered-bottom">
                    <li :class="post_body_tab==='params' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchPostTabToParams">
                            {{ $t('POST request parameters') }}
                        </a>
                    </li>
                    <li :class="post_body_tab==='raw' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchPostTabToRaw">
                            {{ $t('Raw request body') }}
                        </a>
                    </li>
                </ul>
                <div v-if="post_body_tab === 'params'" class="custom-mt-4">
                    <table class="borderless custom-mb-8">
                    <tbody>
                        <tr>
                            <th>{{ $t('Key') }}</th>
                            <th class="max-width">{{ $t('Value') }}</th>
                            <th class="min-width"></th>
                        </tr>
                        <tr v-for="(param, index) in api_post_params" :key="index">
                            <td>
                                <input type="text" v-model="api_post_params[index][0]" @input="checkRequestDataPresence" class="short" />
                            </td>
                            <td class="max-width">
                                <input type="text" v-model="api_post_params[index][1]" @input="checkRequestDataPresence" style="width: 100%" />
                            </td>
                            <td class="min-width">
                                <button class="circle outlined light-gray small" @click="api_post_params.splice(index, 1)"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                    <button class="outlined light-gray small" @click="api_post_params.push(['', ''])"><i class="fas fa-plus"></i> {{ $t('Add param') }}</button>
                </div>
                <div v-if="post_body_tab === 'raw'" class="custom-mt-16">
                    <textarea v-model="api_post_data" style="width: 100%; height: 160px;" @input="checkRequestDataPresence" :placeholder="$t('Raw request body')"></textarea>
                </div>

                <div v-if="method.bazaexplorerdata && method.bazaexplorerdata.params_post" class="hint custom-mt-8">
                <ul>
                    <li v-for="param in method.bazaexplorerdata.params_post" :key="param.name" class="custom-mb-4">
                        <parameter-description :parameter="param" />
                    </li>
                </ul>
                </div>
            </div>
        </div>

        <div class="flexbox middle space-12 full-width custom-mt-12">
            <div class="flexbox middle space-12">
                <button
                    :class="['button', 'a-' + type.toLowerCase()]"
                    @click="call()"
                    :disabled="!api_token || state.calling"
                >
                    <span v-if="!api_token">{{ $t('No token selected') }}</span>
                    <span v-else>
                        <i class="fas fa-play"></i>
                        <span class="custom-pl-8">{{ $t('Run API') }}</span>
                    </span>
                </button>
                <div class="spinner" v-if="state.calling"></div>
            </div>
            <button v-if="is_request_data" class="button light-gray" @click="reset()">
                <i class="fas fa-eraser"></i>
                <span class="custom-pl-8">{{ $t('Clear') }}</span>
            </button>
        </div>

        <div v-if="api_response" class="custom-pt-32">
            <h4>
                {{ $t('Response code') }}:
                <span :class="['badge', 'squared', 'small', 's-r'+ ('' + api_response.status).substring(0, 1)]">
                    {{ api_response.status }}
                    <span v-if="api_response.statusText">({{ api_response.statusText }})</span>
                </span>
            </h4>
            <div v-if="response_info.description" v-html="descriptionMarkdown"></div>
            <div>
                <ul class="tabs bordered-bottom large">
                    <li v-if="api_response.body && response_info.schema && Object.keys(response_info.schema).length > 0" :class="response_tab_smart==='data' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchResponseTab('data')">
                            Result data
                        </a>
                    </li>
                    <li :class="response_tab_smart==='raw' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchResponseTab('raw')">
                            {{ $t('Response') }}
                        </a>
                    </li>
                    <li :class="response_tab_smart==='request' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="response_tab='request'">
                            {{ $t('Request') }}
                        </a>
                    </li>
                    <li v-if="response_info.schema && Object.keys(response_info.schema).length > 0" :class="response_tab_smart==='schema' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="response_tab='schema'">
                            Result schema
                        </a>
                    </li>
                </ul>
                <div v-if="response_tab_smart === 'data' && api_response.body && response_info.schema && Object.keys(response_info.schema).length > 0" class="fields custom-mt-16">
                    <schema-data
                        :schema="response_info.schema"
                        :value="api_response.body"
                        :deep="0"
                        :iden="'root'"
                    />
                </div>
                <div v-else-if="response_tab_smart === 'request'" class="custom-pt-16">
                    <div class="custom-pb-16"><span class="bold">URL</span><br><code class="small">{{ api_request.url }}</code></div>
                    <div class="custom-pb-8 bold">Headers</div>
                    <div class="fields">
                        <div v-for="[name, value] of Object.entries(api_request.headers)" :key="name" class="field">
                            <div class="name">{{ name }}</div>
                            <div v-if="typeof value === 'object' && Array.isArray(value)" class="value">
                                {{ value.join(', ') }}
                            </div>
                            <div v-else class="value">{{ value }}</div>
                        </div>
                    </div>
                    <div v-if="type !== 'GET'">
                        <div class="custom-pt-16 custom-pb-8 bold">Body</div>
                        <code-block v-if="api_request.body" :value="api_request.body" />
                        <div v-else class="small highlighted dark-mode-inverted custom-p-8">
                            <em class="gray">&lt;{{ $t('empty') }}&gt;</em>
                        </div>

                        <div v-if="isSwagger && api_request_body_called">
                            <div class="custom-py-8 bold">Body data</div>
                            <code-block :value="api_request_body" />
                        </div>
                    </div>
                </div>
                <div v-else-if="response_tab_smart === 'raw'" class="custom-pt-16">
                    <div class="custom-pb-8 bold">Headers</div>
                    <div class="fields">
                        <div v-for="[name, value] of Object.entries(api_response.headers)" :key="name" class="field">
                            <div class="name">{{ name }}</div>
                            <div v-if="typeof value === 'object' && Array.isArray(value)" class="value">
                                {{ value.join(', ') }}
                            </div>
                            <div v-else class="value">{{ value }}</div>
                        </div>
                    </div>
                    <div class="custom-pt-16 custom-pb-8 bold">Body</div>
                    <span v-if="api_binary_response">
                        <img v-if="['image/jpeg', 'image/png', 'image/webp'].includes(response_content_type)" :src="api_binary_response"/>
                        <a v-else :href="api_binary_response" :download="binary_response_file_name">{{ $t("File") }} <strong v-if="binary_response_file_name">{{ binary_response_file_name }}</strong></a>
                    </span>
                    <code-block v-else-if="response_body" :value="response_body" :error="api_response.status >= 400" />
                    <div v-else class="small highlighted dark-mode-inverted custom-p-8">
                        <em class="gray">&lt;{{ $t('empty') }}&gt;</em>
                    </div>
                </div>
                
                <div v-if="response_tab_smart === 'schema' && response_info.schema && Object.keys(response_info.schema).length > 0" class="fields custom-mt-16">
                    <schema-info
                        :schema="response_info.schema"
                        :deep="0"
                        :iden="'root'"
                        :collapsable="true"
                    />
                </div>
                <!--pre v-else-if="response_tab_smart === 'schema'" class="small highlighted dark-mode-inverted custom-p-8" v-html="prettifyJson(response_info.schema)"></pre-->
                
            </div>
        </div>
        <p v-else-if="api_error" class="state-error">{{ api_error }}</p>
    </div>
</template>

<script>
import Axios from 'axios';
import { marked } from 'marked';
import SchemaInput from '@/components/SchemaInput.vue';
import SchemaData from '@/components/SchemaData.vue';
import SchemaInfo from '@/components/SchemaInfo.vue';
import Token from '@/components/Token.vue';
import CodeBlock from '@/components/CodeBlock.vue';
import Structure from '@/components/Structure.vue';
import ParameterDescription from '@/components/ParameterDescription.vue';
import { swaggerUrl } from '@/funcs.js'
import { prettyPrintJson } from 'pretty-print-json';
export default {
    name: "Method",
    components: {
        SchemaInput,
        SchemaData,
        SchemaInfo,
        Structure,
        Token,
        CodeBlock,
        ParameterDescription
    },
    data() {
        return {
            state: {
                loading: false,
                error: false,
                calling: false
            },
            name: "",
            method: {
                name: "",
                type: ""
            },
            app: {
                id: "",
                name: ""
            },
            api_query_string: '',
            api_post_data: "",
            api_post_params: [['', '']],
            api_response: null,
            api_binary_response: null,
            binary_response_file_name: null,
            api_error: null,
            api_params: {},
            api_request: null,
            api_request_body: {},
            api_request_body_called: null,
            is_request_data: false,
            response_tab: 'data',
            post_body_tab: 'params',
            show_token: false,
            show_returns_description: false,
            show_method_description: false,
            description_tab_selected: 'raw'
        };
    },
    computed: {
        api_token() {
            return this.$store.getters.current_token;
        },
        user() {
            return this.$store.getters.current_user;
        },
        type() {
            if (!this.method) {
                return '';
            }
            if ('type' in this.method) {
                if (Array.isArray(this.method.type)) {
                    return this.method.type[0];
                }
                return this.method.type;
            }
            for (const m of ['get', 'post', 'put', 'patch', 'delete']) {
                if (m in this.method) {
                    return m.toUpperCase();
                }
            }
            return false;
        },
        typeLc() {
            return this.type.toLowerCase();
        },
        summary() {
            if (!this.method) {
                return '';
            }
            if (this.typeLc in this.method && this.method[this.typeLc].summary) {
                return this.method[this.typeLc].summary;
            }
            if (this.method.bazaexplorerdata && this.method.bazaexplorerdata.description) {
                return this.method.bazaexplorerdata.description;
            }
            return false;
        },
        isSwagger() {
            if (!this.method) {
                return false;
            }
            return 'isSwagger' in this.method && this.method.isSwagger;
        },
        openApiDescription() {
            if (!this.isSwagger) {
                return {};
            }
            let desc = {};
            Object.assign(desc, this.method);
            delete desc.isSwagger;
            delete desc.http_type;
            return desc;
        },
        externalDocUrl() {
            if (!this.isSwagger) {
                return false;
            }
            if (this.typeLc in this.method && 'externalDocs' in this.method[this.typeLc] && 'url' in this.method[this.typeLc].externalDocs) {
                return this.method[this.typeLc].externalDocs.url;
            }
            return false;
        },
        methodQueryParams() {
            if (!this.isSwagger) {
                return [];
            }
            if (!(this.type.toLowerCase() in this.method) || !('parameters' in this.method[this.type.toLowerCase()])) {
                return [];
            }
            const methodParams = this.method[this.type.toLowerCase()].parameters;
            return methodParams.filter(param => 'in' in param && param.in === 'query');
        },
        post_data_schema() {
            if (!this.post_body_type) {
                return null;
            }
            return this.method[this.typeLc].requestBody.content[this.post_body_type].schema;
        },
        post_body_type() {
            if (!this.method || !this.isSwagger || !(this.typeLc in this.method) || !('requestBody' in this.method[this.typeLc])) {
                return null;
            }
            const bodyTypes = this.method[this.typeLc].requestBody.content;
            if ('application/json' in bodyTypes) {
                return 'application/json';
            }
            if ('application/x-www-form-urlencoded' in bodyTypes) {
                return 'application/x-www-form-urlencoded';
            }
            if ('multipart/form-data' in bodyTypes) {
                return 'multipart/form-data';
            }
            return Object.keys(bodyTypes)[0];
        },
        response_info() {
            if (!this.isSwagger || !this.api_response || !this.type
                || !(this.type.toLowerCase() in this.method)
                || !('responses' in this.method[this.type.toLowerCase()])
                || !(this.api_response.status in this.method[this.type.toLowerCase()].responses)
            ) {
                return {};
            }
            const response = this.method[this.type.toLowerCase()].responses[this.api_response.status];
            let schema = {};
            if ('content' in response) {
                if ('application/json' in response.content) {
                    schema = response.content['application/json'].schema || {};
                } else if ('text/plain' in response.content) {
                    schema = response.content['text/plain'].schema || {};
                }
            }
            return { schema: schema, description: response.description };
        },
        response_body() {
            return this.isSwagger ? 
                ((this.response_content_type.startsWith('application/json') ? this.api_response.body : this.escapeHtml(this.api_response.text)) || this.escapeHtml(this.api_response.text)) 
                : this.api_response.data;
        },
        response_content_type() {
            if ('content-type' in this.api_response.headers) {
                return this.api_response.headers['content-type'].toLowerCase();
            }
            return '';
        },
        response_tab_smart() {
            if (this.response_tab === 'data' && (
                !this.api_response.body || 
                !this.response_info.schema || 
                Object.keys(this.response_info.schema).length == 0
            )) {
                return 'raw';
            }
            return this.response_tab;
        },
        descriptionMarkdown() {
            return marked.parse(this.response_info.description);
        },
        methodUrl() {
            return document.location.protocol + '//' + document.location.host + window.appState.rootUrl + 'api.php/' + this.name;
        },
        swaggerDescriptionUrl() {
            return swaggerUrl(this.app.id);
        }
    },
    async mounted() {
        scroll(0, 0);
        this.name = this.$route.params.name;
        if (this.name) {
            const app_id = this.name.split('.')[0];
            this.app = this.$store.getters.getApp(app_id);
            this.method = this.$store.getters.getMethod(app_id, this.name);
            if (!this.method) {
                return;
            }
            document.title = this.name + ' — API Explorer';
        }
        if (!this.isSwagger && !this.method.bazaexplorerdata) {
            try {
                const params = new URLSearchParams();
                if (window.appState.locale === "ru") {
                    params.append("locale", "ru_RU");
                } else if (window.appState.locale === "en") {
                    params.append("locale", "en_US");
                }
                const resp = await Axios.get(window.appState.centralUrl + this.app.id + '/' + this.name + '/?' + params.toString());
                if (resp.data && resp.data.status && resp.data.status === 'ok' && resp.data.data) {
                    this.method.bazaexplorerdata = resp.data.data;
                    if (resp.data.data.params) {
                        this.method.bazaexplorerdata.params_get = resp.data.data.params.filter((el) => {
                            return el.call_type && ["GET", "GET+POST"].includes(el.call_type);
                        });
                        this.method.bazaexplorerdata.params_post = resp.data.data.params.filter((el) => {
                            return el.call_type && ["POST", "GET+POST"].includes(el.call_type);
                        });
                        delete this.method.bazaexplorerdata.params;
                    }

                    this.$store.commit('load_method', { app_id: this.app.id, method_name: this.name, method: this.method });
                }
            } catch (err) {
                console.error(err);
            }
        }
        
        this.show_returns_description = this.method.bazaexplorerdata && this.method.bazaexplorerdata.returns && this.method.bazaexplorerdata.returns.length <= 500;
        const storedData = JSON.parse(localStorage.getItem('method:' + this.name));
        if (storedData) {
            this.api_params = ('api_params' in storedData) ? storedData.api_params : {};
            this.api_request_body = ('api_request_body' in storedData) ? storedData.api_request_body : {};
            this.api_query_string = ('api_query_string' in storedData) ? storedData.api_query_string : "";
            this.api_post_data = ('api_post_data' in storedData) ? storedData.api_post_data : "";
            this.parseRawBody();
        }
        this.checkRequestDataPresence();
        const response_tab = localStorage.getItem('response_tab');
        if (response_tab) {
            this.response_tab = response_tab;
        }
    },
    methods: {
        async call() {
            this.api_error = null;
            this.api_response = null;
            this.binary_response_file_name = null;
            this.api_binary_response = null;

            const requestInterceptor = (request) => {
                this.api_request = request;
                if (this.api_request.url.startsWith('/') && !this.api_request.url.startsWith('//')) {
                    this.api_request.url = document.location.protocol + '//' + document.location.host + this.api_request.url;
                } else if (this.api_request.url.startsWith('api.php/')) {
                    this.api_request.url = document.location.protocol + '//' + document.location.host + '/' + this.api_request.url;
                }
                if (this.type.toLowerCase() !== 'get') {
                    if (this.post_body_type === 'multipart/form-data' && 
                        typeof this.api_request.body === 'object' && 
                        this.api_request.body instanceof FormData
                    ) {
                        new Response(this.api_request.body).text().then(text => { this.api_request.body = text; });
                    }

                    if (!('body' in this.api_request) && 'data' in this.api_request) {
                        this.api_request.body = this.api_request.data;
                    }
                }
                return request;
            };

            const responseInterceptor = async (res) => {
                if (!res || !(res?.data instanceof Blob) || !('headers' in res) || !('content-type' in res.headers)) {
                    return res;
                }
                if (res.data.type == 'application/json') {
                    res.data = JSON.parse(await (res.data)?.text());
                } else if (['application/xml', 'text/xml', 'text/plain', 'text/html'].includes(res.data.type)) {
                    res.data = await (res.data)?.text();
                } else {
                    // binary response case
                    const reader = new FileReader();
                    reader.onload = e => this.api_binary_response = e.target.result;
                    reader.readAsDataURL(res.data);

                    if ('content-disposition' in res.headers) {
                        const file_name = res.headers['content-disposition'].match(/attachment;\s+filename="([^"]+)"/);
                        if (file_name) {
                            this.binary_response_file_name = file_name[1];
                        }
                    }
                }
                return res;
            }

            this.state.calling = true;
            if (this.isSwagger) {
                // swagger case
                try {
                    let call_data = {
                        pathName: '/' + this.name,
                        method: this.type.toLowerCase(),
                        parameters: this.filterUndefinedFields(this.api_params),
                        securities: { authorized: { ApiKeyAuth: this.api_token, BearerAuth: this.api_token } },
                        requestInterceptor,
                        responseInterceptor
                    };
                    if (this.type.toLowerCase() !== 'get' && Object.keys(this.api_request_body).length > 0) {
                        call_data.requestBody = this.filterUndefinedFields(this.api_request_body);
                    }
                    this.api_response = await this.$store.state.swagger[this.app.id].execute(call_data);
                } catch (err) {
                    console.error(err);
                    this.api_error = err.toString();
                    if (err.response) {
                        this.api_response = err.response;
                    } else {
                        const required_param = this.api_error.match(/^Error: Required parameter (\S+) is not provided$/);
                        if (required_param) {
                            this.api_error = this.$t("required-parameter", [required_param[1]]);
                        }
                    }
                }
                this.api_request_body_called = this.prettifyJson(this.api_request_body);
                localStorage.setItem('method:' + this.name, JSON.stringify({ 
                    api_params: this.api_params, 
                    api_request_body: this.filterLongFields(this.api_request_body) 
                }));
            } else {
                const method_type = Array.isArray(this.method.type) ? this.method.type[0] : this.method.type;
                const axiosInstance = Axios.create();
                axiosInstance.interceptors.request.use(requestInterceptor);
                axiosInstance.interceptors.response.use(responseInterceptor);
                
                if (this.post_body_tab === 'params') {
                    this.api_post_data = this.getRawBodyFromParams();
                }
                try {
                    const resp = await axiosInstance({
                        method: method_type,
                        url: window.appState.rootUrl + 'api.php/' + this.name + '?' + this.api_query_string,
                        data: (method_type === 'GET') ? null : this.api_post_data,
                        headers: { Authorization: 'Bearer ' + this.api_token },
                        responseType: 'blob'
                    });
                    this.api_response = resp;
                } catch (err) {
                    this.api_response = await responseInterceptor(err.response);
                }
                this.api_request_body_called = this.api_post_data;
                localStorage.setItem('method:' + this.name, JSON.stringify({ 
                    api_query_string: this.api_query_string, 
                    api_post_data: this.filterLongFields(this.api_post_data)
                }));
            }
            this.state.calling = false;
        },
        reset() {
            this.api_params = {};
            this.api_request_body = {};
            this.api_query_string = "";
            this.api_post_data = "";
            this.parseRawBody();
            this.checkRequestDataPresence();
            localStorage.removeItem('method:' + this.name);
            this.emitter.emit('reset');
        },
        backToApp() {
            this.$router.push({name: 'Application', params: {name: this.app.id}});
        },
        switchPostTabToRaw() {
            this.api_post_data = this.getRawBodyFromParams();
            this.post_body_tab = 'raw';
        },
        switchPostTabToParams() {
            this.parseRawBody();
            this.post_body_tab = 'params';
        },
        parseRawBody() {
            if (this.api_post_data.indexOf('=') === -1) {
                this.api_post_params = [['', '']];
                return;
            }
            try {
                const params = new URLSearchParams(this.api_post_data.replace(/\s+/g, ''));
                this.api_post_params = [];
                for (const [key, value] of params) {
                    this.api_post_params.push([key, value]);
                }
            } catch(err) {
                console.error(err);
            }
        },
        getRawBodyFromParams() {
            if (this.api_post_params.length == 0) {
                return "";
            }
            const params = new URLSearchParams();
            this.api_post_params.forEach(element => {
                if (element[0] || element[1]) {
                    params.append(element[0], element[1]);
                }
            });
            return params.toString();
        },
        prettifyJson(obj) {
            return prettyPrintJson.toHtml(obj);
        },
        switchResponseTab(tab) {
            this.response_tab = tab;
            if (this.response_info.schema && Object.keys(this.response_info.schema).length > 0) {
                localStorage.setItem('response_tab', tab);
            }
        },
        isObjectEmpty(obj) {
            if (typeof obj === 'undefined' || obj === null || obj === '' || obj === false) {
                return true;
            }
            if (typeof obj !== 'object') {
                return false;
            }
            if (Array.isArray(obj)) {
                if (obj.length === 0) {
                    return true;
                }
                for (const el of obj) {
                    if (!this.isObjectEmpty(el)) {
                        return false;
                    }
                }
                return true;
            }
            for (const el of Object.values(obj)) {
                if (!this.isObjectEmpty(el)) {
                    return false;
                }
            }
            return true;
        },
        checkRequestDataPresence() {
            this.is_request_data = !this.isObjectEmpty(this.api_params)
                || !this.isObjectEmpty(this.api_request_body)
                || this.api_query_string
                || this.api_post_data
                || !this.isObjectEmpty(this.api_post_params);
        },
        filterUndefinedFields(obj) {
            if (obj === null) {
                return null;
            }
            if (typeof obj !== 'object') {
                return obj;
            }
            if (Array.isArray(obj)) {
                return obj.map(item => { return this.filterUndefinedFields(item); });
            }
            for (const prop in obj) {
                if (typeof obj[prop] === 'undefined') {
                    delete obj[prop];
                } else {
                    obj[prop] = this.filterUndefinedFields(obj[prop]);
                }
            }
            return obj;
        },
        filterLongFields(obj) {
            if (obj === null) {
                return null;
            }
            if (typeof obj === 'string') {
                return obj.length > 1024 ? '' : obj;
            }
            if (typeof obj !== 'object') {
                return obj;
            }
            if (Array.isArray(obj)) {
                return obj.map(item => { return this.filterLongFields(item); });
            }
            let result = {};
            for (const prop in obj) {
                result[prop] = this.filterLongFields(obj[prop]);
            }
            return result;
        },
        escapeHtml(str) {
            if (typeof str !== 'string') {
                return '';
            }
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        binaryToBase64(data) {
            return btoa(data);
        }
    }
};
</script>
