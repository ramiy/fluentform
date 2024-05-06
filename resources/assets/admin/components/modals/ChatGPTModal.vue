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
    import each from 'lodash/each';

    export default {
        name: 'ChatGPTModal',
        props: {
            categories: Array,
            visibility: Boolean,
            predefinedForms: Object
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
                const url = FluentFormsGlobal.$rest.route('gptForm');
                FluentFormsGlobal.$rest.post(url, {
                    query: this.query,
                    additional_query: this.additional_query,
                })
                .then((response) => {
                    this.$success(response.message);

                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    }
                })
                .catch(error => {
                    this.$fail(error.message);
                })
                .finally(() => {
                    this.loading = false;

                });
            },


        },
        mounted() {
        }
    };
</script>
