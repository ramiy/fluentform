<template>
    <div class="fc_item_num_selector">
        <el-input :disabled="disabled" :placeholder="field.numeric_placeholder" size="mini" type="number" :min="0"
                  v-model="model[1]"></el-input>
        <span v-if="field.input_help">
            <el-tooltip class="item" effect="dark" :content="field.input_help" placement="top-start">
                <i class="el-icon el-icon-info"></i>
            </el-tooltip>
        </span>
    </div>
</template>

<script type="text/babel">
import isArray from 'lodash/isArray';

export default {
    name: 'ItemTimesSelection',
    props: ['field', 'value', 'disabled'],
    components: {

    },
    data() {
        return {
            model: this.value,
            loading: false,
            options: []
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    mounted() {
        if (!isArray(this.model)) {
            this.model = ['', 2];
        }
    }
}
</script>
