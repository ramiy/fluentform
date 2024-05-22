<template>
    <div class="ff_choose_template_wrap" :class="{'ff_backdrop': visibility}">
        <el-dialog
            :visible.sync="visibility"
            width="70%"
            top= "50px"
            :before-close="close"
        >
            <template slot="title">
                <h3 class="title">{{$t('ChatGPT')}}</h3>
                <p class="text">{{$t('GPT')}}
                </p>
            </template>

            <div class="mt-6">
                <el-form class="mt-4" :model="{}" label-position="top" >
                    <el-form-item class="ff-form-item" :label="$t('Create a form for')">
                        <el-input placeholder="Create a form for"  type="textarea" v-model="query">
                        </el-input>
                    </el-form-item>
                    <el-form-item class="ff-form-item" :label="$t('Including these Questions')">
                        <el-input placeholder="ask questions"  type="textarea" v-model="additional_query">
                        </el-input>
                    </el-form-item>
                    <el-button v-loading="loading" @click="createForm">
                        Create
                    </el-button>
                </el-form>
            </div><!-- .ff_predefined_options -->
        </el-dialog>
    </div>
</template>

<script>

    export default {
        name: 'ChatGPTModal',
        props: {
            visibility: Boolean,
        },
        data() {
            return {
                query: '',
                additional_query: '',
                creatingForm: false,
                loading: false,
                has_pro: !!window.FluentFormApp.hasPro,
                current: null,
            }
        },
        computed: {
        },
        methods: {
            close() {
                this.$emit('update:visibility', false);
            },

            createForm() {
                this.loading = true;
	            FluentFormsGlobal.$post({
			            action: 'fluentform_chat_gpt_create_form',
			            query: this.query,
			            additional_query: this.additional_query,
		            })
                .then((response) => {
                    this.$notify.success({
	                    title: this.$t('Success'),
	                    message: response.data.message,
	                    position: 'bottom-right'
                    });
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    }
                })
                .fail(error => {
                    this.$notify.error({
	                    title: this.$t('Error'),
	                    message: error.responseJSON?.data.message,
	                    position: 'bottom-right'
                    });
                })
                .always(() => {
                    this.loading = false;
                });
            },
        },
        mounted() {
        }
    };
</script>
