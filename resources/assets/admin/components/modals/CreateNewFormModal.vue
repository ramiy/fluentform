<template>
    <div>
        <div :class="{'ff_backdrop': visibility}">
            <el-dialog
                top="50px"
                :width="has_pro ? '84%' : '64%'"
                :element-loading-text="$t('Creating Form, Please wait...')"
                element-loading-spinner="el-icon-loading"
                :loading="creatingForm"
                :visible="visibility"
                :before-close="close"
                class="ff-create-new-form-modal"
            >
                <template slot="title">
                    <div class="el-dialog__header_group">
                        <h4 class="mr-3">{{ $t('Create A New Form') }}</h4>
                        <el-button size="medium" @click="showFormsImport = !showFormsImport" type="info"
                                   :class="{'el-button--soft': !showFormsImport}">
                            {{ $t('Import Form') }}
                            &nbsp;<i v-if=" showFormsImport" class="el-icon-circle-close"></i>
                        </el-button>
                    </div>
                    <transition name="slide-down">
                        <import-forms class="import-forms-section mt-4" v-if="showFormsImport" @forms-imported="updateFormsImported" :app="{forms:[]}"/>
                    </transition>
                </template>

                <div class="ff_card_wrap mt-5 mb-4">
                    <el-row :gutter="32">
                        <el-col :sm="has_post_feature ? 6 : 8" class="mb-5" v-if="has_gpt_feature">
                            <el-skeleton :loading="loading" animated class="h-100">
                                <template slot="template">
                                    <el-skeleton-item variant="image" style="margin-bottom: 16px; height: 214px;"/>
                                    <el-skeleton-item variant="h3" style="width: 80%;"/>
                                    <el-skeleton-item variant="text" style="width: 60%; margin-top: 10px;" />
                                </template>
                                <template>
                                    <card class="ff_card_form_action ff_card_shadow_lg hover-zoom"  @click="showChatGPT" :img="blankFormImg" imgClass="mb-3">
                                        <card-body>
                                            <h6 class="mb-2 ff_card_title">{{$t('Chat GPT')}}</h6>
                                            <p class="ff_card_text">{{$t('Create a New Blank form from scratch.')}}</p>
                                        </card-body>
                                    </card>
                                </template>
                            </el-skeleton>
                        </el-col>

                        <el-col :sm="has_post_feature ? 6 : 8" class="mb-5">
                            <el-skeleton :loading="loading" animated class="h-100">
                                <template slot="template">
                                    <el-skeleton-item variant="image" style="margin-bottom: 16px; height: 214px;"/>
                                    <el-skeleton-item variant="h3" style="width: 80%;"/>
                                    <el-skeleton-item variant="text" style="width: 60%; margin-top: 10px;" />
                                </template>
                                <template>
                                    <card class="ff_card_form_action ff_card_shadow_lg hover-zoom" v-loading="creatingForm && creatingFormType === 'blank_form'" @click="createForm('blank_form')" :img="blankFormImg" imgClass="mb-3">
                                        <card-body>
                                            <h6 class="mb-2 ff_card_title">{{$t('New Blank Form')}}</h6>
                                            <p class="ff_card_text">{{$t('Create a New Blank form from scratch.')}}</p>
                                        </card-body>
                                    </card>
                                </template>
                            </el-skeleton>
                        </el-col>
                        <el-col :sm="has_post_feature ? 6 : 8" class="mb-5">
                            <el-skeleton :loading="loading" animated class="h-100">
                                <template slot="template">
                                    <el-skeleton-item variant="image" style="margin-bottom: 16px; height: 214px;"/>
                                    <el-skeleton-item variant="h3" style="width: 80%;"/>
                                    <el-skeleton-item variant="text" style="width: 60%; margin-top: 10px;" />
                                </template>
                                <template>
                                    <card class="ff_card_form_action ff_card_shadow_lg hover-zoom" @click="showChooseTemplate" :img="chooseTemplateImg" imgClass="mb-3">
                                        <card-body>
                                            <h6 class="mb-2 ff_card_title">{{$t('Choose a Template')}}</h6>
                                            <p class="ff_card_text">{{$t('Choose a pre-made form template and customize it.')}}</p>
                                        </card-body>
                                    </card>
                                </template>
                            </el-skeleton>
                        </el-col>
                        <el-col :sm="has_post_feature ? 6 : 8" class="mb-5">
                            <el-skeleton :loading="loading" animated class="h-100">
                                <template slot="template">
                                    <el-skeleton-item variant="image" style="margin-bottom: 16px; height: 214px;"/>
                                    <el-skeleton-item variant="h3" style="width: 80%;"/>
                                    <el-skeleton-item variant="text" style="width: 60%; margin-top: 10px;" />
                                </template>
                                <template>
                                    <card class="ff_card_form_action ff_card_shadow_lg hover-zoom" v-loading="creatingForm && creatingFormType === 'conversational'" @click="createForm('conversational')" :img="conversationalFormImg" imgClass="mb-3">
                                        <card-body>
                                            <h6 class="mb-2 ff_card_title">{{$t('Create Conversational Form')}}</h6>
                                            <p class="ff_card_text">{{$t('Turn your content, surveys into conversations.')}}</p>
                                        </card-body>
                                    </card>
                                </template>
                            </el-skeleton>
                        </el-col>
                        <el-col :sm="has_post_feature ? 6 : 8" v-if="has_post_feature" class="mb-5">
                            <el-skeleton :loading="loading" animated class="h-100">
                                <template slot="template">
                                    <el-skeleton-item variant="image" style="margin-bottom: 16px; height: 214px;"/>
                                    <el-skeleton-item variant="h3" style="width: 80%;"/>
                                    <el-skeleton-item variant="text" style="width: 60%; margin-top: 10px;" />
                                </template>
                                <template>
                                    <card class="ff_card_form_action ff_card_shadow_lg hover-zoom" @click="showPostType" :img="postTypeFormImg" imgClass="mb-3">
                                        <card-body>
                                            <h6 class="mb-2 ff_card_title">{{$t('Create A Post Form')}}</h6>
                                            <p class="ff_card_text">{{$t('Create a Post type form from scratch.')}}</p>
                                        </card-body>
                                    </card>
                                </template>
                            </el-skeleton>
                        </el-col>
                    </el-row>
                    <div class="scroll-wrap">
                        <div class="scroll" @click="showChooseTemplate"></div>
                    </div>
                </div>
            </el-dialog>
        </div>

        <ChooseTemplateModal
            :categories="categories"
            :predefinedForms="predefinedForms"
            :visibility.sync="showChooseTemplateModal"
        >
        </ChooseTemplateModal>

        <PostTypeSelectionModal
            @on-post-type-selction-end="onPostTypeSelctionEnd"
            :postTypeSelectionDialogVisibility="postTypeSelectionDialogVisibility"
            :hasPro="has_pro"
        />

        <ChatGPTModal :visibility.sync="showChatGPTModal"/>


    </div>
</template>

<script type="text/babel">
    import Card from '../Card/Card.vue';
    import CardBody from '../Card/CardBody.vue';
    import ChooseTemplateModal from './ChooseTemplateModal.vue';
    import ChatGPTModal from './ChatGPTModal.vue';
    import PostTypeSelectionModal from './PostTypeSelectionModal.vue';
    import ImportForms from '@/admin/transfer/ImportForms';

    export default {
        name: 'CreateNewFormModal',
        components: {
            Card,
            CardBody,
            ChooseTemplateModal,
            PostTypeSelectionModal,
            ImportForms,
            ChatGPTModal
        },
        props: {
            visibility: Boolean
        },
        data() {
            return {
                innerVisible: false,
                chatQuery: '',
                has_post_feature: !!window.FluentFormApp.has_post_feature,
                has_gpt_feature: !!window.FluentFormApp.has_gpt_feature,
                postFormData: {
                    type: 'post',
                    predefined: 'blank_form',
                    action: 'fluentform-predefined-create'
                },
                creatingForm: false,
                predefinedForms: {},
                selectedPredefinedForm: '',
                form_title: '',
                categories: [],
                search: '',
                has_pro: !!window.FluentFormApp.hasPro,
                blankFormImg:  window.FluentFormApp.plugin_public_url + 'img/blank-form.png',
                chooseTemplateImg:  window.FluentFormApp.plugin_public_url + 'img/choose-template.png',
                conversationalFormImg:  window.FluentFormApp.plugin_public_url + 'img/conversational-form.png',
                postTypeFormImg:  window.FluentFormApp.plugin_public_url + 'img/post-type-form.png',
                showChooseTemplateModal: false,
                showChatGPTModal: false,
                postTypeSelectionDialogVisibility: false,
                showFormsImport : false,
                formsImported : false,
                creatingFormType : '',
                loading: false
            }
        },
        methods: {
            chatGptModal(){
                console.log('x')
                this.showChat = true;
            },
            getPredefinedForms() {
                this.loading = true;

                const url = FluentFormsGlobal.$rest.route('getTemplates');

                FluentFormsGlobal.$rest.get(url)
                .then(response => {
                    this.predefinedForms = response.forms;
                    this.categories = response.categories;
                }).catch(error => {
                    this.$fail(error.message);
                })
                .finally(() => {
                    this.loading = false;
                });
            },
            close() {
                this.formsImported && location.reload();
                this.$emit('update:visibility', false);
            },
            showChooseTemplate(){
                this.showChooseTemplateModal = true;
                this.$emit('update:visibility', false);
            },
            showChatGPT(){
                this.showChatGPTModal = true;
                this.$emit('update:visibility', false);
            },
            showPostType(){
                this.postTypeSelectionDialogVisibility = true;
                this.$emit('update:visibility', false);
            },
            createForm(formType, form) {
	            if (this.creatingForm) {
		            return;
	            }
	            this.creatingFormType = formType;
                let selectedFormType = 'form';
                if (form) {
                    if (form.is_pro && !window.FluentFormApp.hasPro) {
                        return this.$fail(this.$t('This form required pro add-on of fluentform. Please install pro add-on'));
                    }
                    selectedFormType = form.type;
                }

                this.creatingForm = true;

                let data = {
                    type: selectedFormType,
                    predefined: formType,
                    action: 'fluentform-predefined-create'
                };

                if (selectedFormType === 'post') {
                    return this.createPostForm(data);
                }

                return this.doCreateForm(data)
            },
            createPostForm(data) {
                this.postFormData = data;
                this.postTypeSelectionDialogVisibility = true;
            },
            doCreateForm(data) {
                const url = FluentFormsGlobal.$rest.route('getForms');

                FluentFormsGlobal.$rest.post(url, data)
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
                        this.creatingForm = false;
                    });
            },
            onPostTypeSelctionEnd(post_type) {
                this.creatingForm = false;
                this.postTypeSelectionDialogVisibility = false;
                if (post_type) {
                    this.doCreateForm({post_type, ...this.postFormData});
                }
            },
            gotoPage(url) {
                location.href = url;
            },
            updateFormsImported(value) {
                this.formsImported = value;
            }
        },
        watch: {
	        visibility: {
		        immediate: true,
		        handler(newVal, oldVal) {
			        if (this.visibility && Object.entries(this.predefinedForms).length === 0) {
				        this.getPredefinedForms();
			        }
		        }
	        }
        },
    };
</script>
