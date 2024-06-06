<template>
	<component  v-if="ready" :form_id="form_id" :form="form" :has_pro="has_pro" :inputs="inputs" :editorShortcodes="editorShortcodes" :has_pdf="has_pdf" :app="app" :is="component"></component>
</template>

<script type="text/babel">
export default {
    name: 'CustomComponent',
	props: ['form_id', 'form', 'has_pro' , 'inputs', 'editorShortcodes', 'has_pdf', 'app'],
    data() {
        return {
            ready: false
        }
    },
    computed: {
        component() {
            return window.fluentformCustomComponents[this.name] || null;
        },
	    name() {
			return this.$attrs?.component_name || this.$route?.params.component_name || '';
	    },
    },
    mounted() {
        if(window.fluentformCustomComponents[this.name]) {
            this.ready = true;
        }
    },
    created() {
	    window.fluentformCustomComponents = window.fluentformCustomComponents || {};
    }
}
</script>
