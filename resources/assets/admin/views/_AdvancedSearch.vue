<template>
    <div class="ff_as_container" v-show="advanced_filter">
        <div v-for="(rich_filter, filterIndex) in advanced_filters" :key="filterIndex">
            <div class="fc_rich_filter">
                <rich-filter :filterOptions="editorShortcodes" :add_label="filterLabel" @maybeRemove="maybeRemoveGroup(filterIndex)" :items="rich_filter"/>
            </div>


            <div class="ff_cond_or">
                <em @click="addConditionGroup()"
                    style="cursor: pointer; color: rgb(0, 119, 204); font-weight: bold;"><i
                        class="el-icon-plus"></i> {{ $t('OR') }}</em>
            </div>


        </div>
        <el-row :gutter="20">
            <el-col :md="12" :xs="24">
                <el-button type="primary" size="small" @click="runSearch">{{ $t('Filter') }}</el-button>
            </el-col>
            <el-col :md="12" :xs="24">
                <div class="text-right">
                    <el-button type="default" size="small" @click="advanced_filters = [[]]; runSearch()">
                        {{ $t('Clear Filters') }}
                    </el-button>

                </div>
            </el-col>
        </el-row>
    </div>
</template>
<script>
    import RichFilter from './EntryFilters/Filters';
    export default {
        name: 'AdvancedSearch',
        props: {
            advanced_filter: {},

        },
        components: {
            RichFilter,
        },
        data() {
            return {
                app_ready: false,
                subscribers: [],
                loading: true,
                pagination: {
                    current_page: 1,
                    per_page: 10,
                    total: 0
                },
                editorShortcodes: [],
                advanced_filters: [[]],
                filterLabel: this.$t('Filters Info'),}
        },
        methods:{
            fetchAllEditorShortcodes() {
                const url = FluentFormsGlobal.$rest.route('getFormShortcodes', this.form_id);
                this.editorShortcodes = window.fluent_form_entries_vars.advanced_filters
            },
            maybeRemoveGroup(index) {
                if (this.advanced_filters.length > 1) {
                    this.advanced_filters.splice(index, 1);
                }
            },
            runSearch(){
                console.log('x')
                console.log(this.advanced_filters)

                this.$emit("runSearch", this.advanced_filters);
            },
            addConditionGroup() {
                this.advanced_filters.push([]);
            },
        },
        mounted() {
            this.fetchAllEditorShortcodes();
        }
    }
</script>
