<template>
    <withLabel :item="item" class="ff_upload_btn_editor">
        <span v-if="item.settings.upload_bttn_ui && item.settings.upload_bttn_ui == 'dropzone'" class="ff-dropzone-preview" tabindex="0">{{ item.settings.btn_text }}</span>
        <el-button v-else type="info">{{ item.settings.btn_text }}</el-button>
    </withLabel>
</template>

<script>
import withLabel from './withLabel.vue';

export default {
    name: 'inputFile',
    props: ['item'],
    components: {
        withLabel
    }
}
</script>
