<template>
<div :class="[ error && 'orange', 'highlighted', 'dark-mode-inverted', 'flexbox', 'full-width']">
    <pre class="small custom-p-8 custom-m-0 wide" v-html="value_to_show"></pre>
    <div v-if="just_copied" class="custom-my-4 custom-mx-8 large" style="color: var(--green);"><i class="fas fa-check" title="copied"></i></div>
    <a v-else style="color: var(--gray);" title="Copy to clipboard" class="custom-my-4 custom-mx-8 large" href="javascript:void(0);" @click="copy()"><i class="far fa-copy"></i></a>
</div>
</template>

<script>
import { prettyPrintJson } from 'pretty-print-json';
export default {
    name: "CodeBlock",
    props: {
        value: [Object, String],
        error: {
            type: Boolean,
            default: () => false
        }
    },
    data() {
        return {
            just_copied: false
        };
    },
    computed: {
        value_to_show() {
            if (typeof this.value === 'object') {
                return prettyPrintJson.toHtml(this.value);
            }
            return this.value;
        },
        value_to_copy() {
            if (typeof this.value === 'object') {
                return JSON.stringify(this.value, null, '\t');
            }
            return this.value;
        }
    },
    methods: {
        copy() {
            navigator.clipboard.writeText(this.value_to_copy).then(() => {
                this.just_copied = true;
                setTimeout(() => {
                    this.just_copied = false;
                }, 3000);
            });
        }
    }
}
</script>
