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
            <h1>
                {{ name }}
            </h1>
        </div>
        <p v-if="summary">{{ summary }}</p>
        <p v-else-if="!!method && method.doc">{{ method.doc }}</p>
        <p v-else class="gray"><em>&lt;No method description&gt;</em></p>
        <hr />

        <h3>Execute API call</h3>

        <div class="fields custom-mb-32">
            <div class="fields-group">
                <div class="field">
                    <div class="name">
                        Token:
                    </div>
                    <div class="value">
                        <span v-if="!user" class="gray">
                            <em>&lt;No user selected&gt;</em>
                        </span>
                        <span v-else>
                            <span class="icon userpic userpic-20" :style='{ "background-image": "url(" + user.photo_url_32 + ")"}'></span>
                            {{ user.name }}
                            <token :value="api_token" :user="user" />
                            <span v-if="!!user && !api_token" class="spinner custom-mx-8"></span>
                        </span>
                    </div>
                </div>
                <div class="field">
                    <div class="name">
                        URL:
                    </div>
                    <div class="value">
                        {{ methodUrl }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isSwagger" class="fields">
            <div v-if="type === 'GET'" class="fields-group">
                <div v-for="param in method.get.parameters" :key="param.name" class="field">
                    <div class="name for-input">
                        <label :for="param.name">
                            {{ param.name }}:
                        </label>
                    </div>
                    <div class="value">
                        <schema-input 
                            :name="param.name"
                            :schema="param.schema"
                            :required="param.required"
                            :description="param.description"
                            v-model="api_params[param.name]"
                        />
                    </div>
                </div>
            </div>
            <div v-else-if="post_data_schema.type === 'object'" class="fields-group">
                <div v-for="[param, schema] of Object.entries(post_data_schema.properties)" :key="param" class="field">
                    <div class="name for-input">
                        <label :for="param">
                            {{ param }}:
                        </label>
                    </div>
                    <div class="value">
                        <schema-input 
                            :name="param"
                            :schema="schema"
                            :description="schema.description"
                            :required="post_data_schema.required && post_data_schema.required.includes(param)"
                            v-model="api_request_body[param]"
                        />
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <input type="text" v-model="api_query_string" style="width: 100%" class="custom-mb-4" placeholder="Request query string" />
            
            <div v-if="type !== 'GET'">
                <ul class="tabs bordered-bottom">
                    <li :class="post_body_tab==='params' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchPostTabToParams">
                            Request POST params
                        </a>
                    </li>
                    <li :class="post_body_tab==='raw' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchPostTabToRaw">
                            Raw request body
                        </a>
                    </li>
                </ul>
                <div v-if="post_body_tab === 'params'" class="custom-mt-4">
                    <table class="borderless custom-mb-8">
                        <tr>
                            <th>Name</th>
                            <th class="max-width">Value</th>
                            <th class="min-width"></th>
                        </tr>
                        <tr v-for="(param, index) in api_post_params" :key="index">
                            <td>
                                <input type="text" v-model="api_post_params[index][0]" class="short" />
                            </td>
                            <td class="max-width">
                                <input type="text" v-model="api_post_params[index][1]" style="width: 100%" />
                            </td>
                            <td class="min-width">
                                <button class="circle outlined light-gray small" @click="api_post_params.splice(index, 1)"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                    </table>
                    <button class="outlined light-gray small" @click="api_post_params.push(['', ''])"><i class="fas fa-plus"></i> Add param</button>
                </div>
                <div v-if="post_body_tab === 'raw'" class="custom-mt-16">
                    <textarea v-model="api_post_data" style="width: 100%; height: 160px;" placeholder="Raw request body"></textarea>
                </div>
            </div>
        </div>

        <div class="flexbox middle space-12 custom-mt-12">
            <button class="button light-gray" @click="reset()">
                <i class="fas fa-eraser"></i>
                <span class="custom-pl-8">Clear</span>
            </button>
            <button 
                :class="['button', 'a-' + type.toLowerCase()]" 
                @click="call()" 
                :disabled="!api_token || state.calling" 
            >
                <span v-if="!api_token">No token selected</span>
                <span v-else>
                    <i class="fas fa-robot"></i>
                    <span class="custom-pl-8">Call API method</span>
                </span>
            </button>
            <div class="spinner" v-if="state.calling"></div>
        </div>

        <div v-if="api_response" class="custom-pt-32">
            <h4>
                Response code: 
                <span :class="['badge', 'squared', 'small', 's-r'+ ('' + api_response.status).substring(0, 1)]">
                    {{ api_response.status }} 
                    <span v-if="api_response.statusText">({{ api_response.statusText }})</span>
                </span>
            </h4>
            <div v-if="response_info.description" v-html="descriptionMarkdown"></div>
            <div v-if="response_info.schema && Object.keys(response_info.schema).length > 0">
                <ul class="tabs bordered-bottom">
                    <li :class="response_tab==='data' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchResponseTab('data')">
                            Response data
                        </a>
                    </li>
                    <li :class="response_tab==='raw' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="switchResponseTab('raw')">
                            Response raw
                        </a>
                    </li>
                    <li :class="response_tab==='schema' ? ['selected'] : []">
                        <a href="javascript:void(0);" @click="response_tab='schema'">
                            Response schema
                        </a>
                    </li>
                </ul>
                <div v-if="response_tab === 'data'" class="fields custom-mt-16">
                    <schema-data 
                        :schema="response_info.schema"
                        :value="api_response.body"
                        :deep="0"
                        :iden="'root'"
                    />
                </div>
                <pre v-else-if="response_tab === 'schema'" class="small highlighted custom-p-8" v-html="prettifyJson(response_info.schema)"></pre>
                <pre v-else :class="[ 'small', api_response.status >= 400 && 'orange', 'highlighted', 'custom-p-8']" v-html="prettifyJson(api_response.body || api_response.data)"></pre>
            </div>
            <pre v-else :class="[ 'small', api_response.status >= 400 && 'orange', 'highlighted', 'custom-p-8']" v-html="prettifyJson(api_response.body || api_response.data)"></pre>
        </div>
        <p v-else-if="api_error" class="state-error">Ошибке: {{ api_error }}</p>
    </div>
</template>

<script>
import Axios from 'axios';
import marked from 'marked';
import SchemaInput from '@/components/SchemaInput';
import SchemaData from '@/components/SchemaData';
import Token from '@/components/Token';
import { prettyPrintJson } from 'pretty-print-json';
//import SwaggerClient from 'swagger-client';
export default {
    name: "Method",
    components: {
        SchemaInput,
        SchemaData,
        Token
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
            api_error: null,
            api_params: {},
            api_request_body: {},
            response_tab: 'data',
            post_body_tab: 'params',
            show_token: false,
        };
    },
    computed: {
        api_token() {
            return this.$store.getters.current_token;
        },
        user() {
            return this.$store.getters.current_user;
        },
        summary() {
            if (!this.method) {
                return '';
            }
            if (this.method.post && this.method.post.summary) {
                return this.method.post.summary;
            }
            if (this.method.get && this.method.get.summary) {
                return this.method.get.summary;
            }
            return false;
        },
        type() {
            if (!this.method) {
                return '';
            }
            if ('type' in this.method) {
                return this.method.type;
            }
            if ('post' in this.method) {
                return 'POST';
            }
            if ('get' in this.method) {
                return 'GET';
            }
            return false;
        },
        isSwagger() {
            if (!this.method) {
                return false;
            }
            return 'isSwagger' in this.method && this.method.isSwagger;
        },
        post_data_schema() {
            if (!this.method) {
                return {};
            }
            if (!this.isSwagger || !('post' in this.method) || !('requestBody' in this.method.post)) {
                return {};
            }
            const bodyTypes = this.method.post.requestBody.content;
            if ('application/json' in bodyTypes) {
                return bodyTypes['application/json'].schema;
            }
            if ('application/x-www-form-urlencoded' in bodyTypes) {
                return bodyTypes['application/x-www-form-urlencoded'].schema;
            }
            if ('multipart/form-data' in bodyTypes) {
                return bodyTypes['multipart/form-data'].schema;
            }
            return {};
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
        descriptionMarkdown() {
            return marked(this.response_info.description);
        },
        methodUrl() {
            return document.location.protocol + '//' + document.location.host  + window.appState.rootUrl + 'api.php/' + this.name;
        }
    },
    async mounted() {
        this.name = this.$route.params.name;
        if (this.name) {
            const app_id = this.name.split('.')[0];
            this.app = this.$store.getters.getApp(app_id);
            this.method = this.$store.getters.getMethod(app_id, this.name);
            if (!this.method) {
                return;
            }
            if (!('name' in this.method) && !this.isSwagger) {
                this.state.loading = true;
                const params = new URLSearchParams();
                params.append("module", "methodinfo");
                params.append("method", this.name);
                const resp = await this.axios.get('?' + params.toString());
                this.method = resp.data.data;
                this.$store.commit('load_method', { app_id: app_id, method_name: this.name, method: this.method });
                this.state.loading = false;
            }
        }
        const storedData = JSON.parse(localStorage.getItem('method:' + this.name));
        if (storedData) {
            this.api_params = ('api_params' in storedData) ? storedData.api_params : {};
            this.api_request_body = ('api_request_body' in storedData) ? storedData.api_request_body : {};
            this.api_query_string = ('api_query_string' in storedData) ? storedData.api_query_string : "";
            this.api_post_data = ('api_post_data' in storedData) ? storedData.api_post_data : "";
            this.parseRawBody();
        }
        const response_tab = localStorage.getItem('response_tab');
        if (response_tab) {
            this.response_tab = response_tab;
        }
    },
    methods: {
        async call() {
            this.state.calling = true;
            if (this.isSwagger) {
                // swagger case
                try {
                    let call_data = {
                        pathName: '/' + this.name,
                        method: this.type.toLowerCase(),
                        parameters: this.api_params,
                        securities: { authorized: { ApiKeyAuth: this.api_token, BearerAuth: this.api_token } },
                    };
                    if (this.type.toLowerCase() !== 'get') {
                        call_data.requestBody = this.api_request_body;
                    }
                    this.api_response = await this.$store.state.swagger[this.app.id].execute(call_data);
                } catch (err) {
                    this.api_error = err.toString();
                    if (err.response) {
                        this.api_response = err.response;
                    }
                }
                localStorage.setItem('method:' + this.name, JSON.stringify({ api_params: this.api_params, api_request_body: this.api_request_body }));
            } else {
                const method_type = Array.isArray(this.method.type) ? this.method.type[0] : this.method.type;
                const axiosInstance = Axios.create({
                    baseURL: window.appState.rootUrl
                });
                if (this.post_body_tab === 'params') {
                    this.api_post_data = this.getRawBodyFromParams();
                }
                try {
                    const resp = await axiosInstance({
                        method: method_type,
                        url: 'api.php/' + this.method.name + '?' + this.api_query_string,
                        data: (method_type === 'GET') ? null : this.api_post_data,
                        headers: { Authorization: 'Bearer ' + this.api_token }
                    });
                    this.api_response = resp;
                } catch (err) {
                    this.api_response = err.response;
                }
                localStorage.setItem('method:' + this.name, JSON.stringify({ api_query_string: this.api_query_string, api_post_data: this.api_post_data }));
            }
            this.state.calling = false;
        },
        reset() {
            this.api_params = {};
            this.api_request_body = {};
            this.api_query_string = "";
            this.api_post_data = "";
            this.parseRawBody();
            localStorage.removeItem('method:' + this.name);
            this.emitter.emit('reset');
        },
        show(value) {
            console.log(value);
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
                console.log(err);
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
            localStorage.setItem('response_tab', tab);
        },
    }
}
</script>

<style>
.json-key           { color: var(--dark-gray); }
.json-string        { color: var(--purple); }
.json-number        { color: var(--orange); }
.json-boolean       { color: var(--pink); }
.json-null          { color: var(--light-gray); }
.json-mark          { color: var(--black); }
/*
a.json-link         { color: purple var(--purple); transition: all 400ms; }
a.json-link:visited { color: slategray var(--); }
a.json-link:hover   { color: blueviolet var(--); }
a.json-link:active  { color: slategray var(--); }
*/
.badge.s-r2 { background-color: var(--green); }
.badge.s-r4 { background-color: var(--red); }
.badge.s-r5 { background-color: var(--red); }
</style>