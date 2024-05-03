<template>
<div class="post_meta_plugins_mappings">
    <div class="meta_fields_mapping_head" v-if="labels.section_title">
        <h6>{{ labels.section_title }}</h6>
    </div>
    <div class="meta_fields_mapping_head no_border">
        <h6 class="fs-14">{{ $t('General Fields') }}</h6>
        <el-button
            size="small"
            icon="el-icon-plus"
            @click="addMapping('general_settings')"
        >
            {{ $t('Add Another General Field') }}
        </el-button>
    </div>

    <table v-if="general_settings && general_settings.length" class="ff-table">
        <thead>
        <tr>
            <th>{{ labels.remote_label }}</th>
            <th>{{ labels.local_label }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(mapField,index) in general_settings" :key="index">
            <td>
                <el-select class="w-100" v-model="mapField.field_key" :placeholder="$t('Select Field')">
                    <el-option 
                        v-for="(field, fieldKey) in general_fields" 
                        :key="fieldKey"
                        :label="field.label" 
                        :value="fieldKey"></el-option>
                </el-select>
            </td>
            <td>
                <inputPopover
                    fieldType="text"
                    :data="editorShortcodes"
                    v-model="mapField.field_value"
                />
            </td>
            <td>
                <el-button
                    class="el-button--icon"
                    type="danger"
                    size="mini"
                    icon="el-icon-close"
                    style="margin-top: 3px;"
                    @click="deleteMapping('general_settings',index)"
                />
            </td>
        </tr>
        </tbody>
    </table>
    <div v-else class="no-mapping-alert">
        {{ $t('There is no mapping found for General Meta fields.') }}
    </div>

    <template>
        <hr class="mt-4 mb-4">
        <div class="meta_fields_mapping_head no_border">
            <h6 class="fs-14">{{ $t('Advanced Fields') }}</h6>
            <el-button
                size="small"
                icon="el-icon-plus"
                @click="addAdvancedMetaFieldMapping()"
            >
                {{ $t('Add Another Advanced Field') }}
            </el-button>
        </div>

        <table v-if="addAdvancedMetaFieldMapping && advanced_settings && advanced_settings.length" class="ff-table">
            <thead>
                <tr>
                    <th>{{ labels.remote_label }}</th>
                    <th>{{ labels.local_label }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(mapField,index) in advanced_settings" :key="index">
                    <td>
                        <el-select 
                            class="w-100" 
                            @change="mapField.field_value = ''" 
                            v-model="mapField.field_key" 
                            :placeholder="$t('Select Field')"
                        >
                            <el-option 
                                v-for="(field, fieldKey) in advanced_fields"
                                :key="fieldKey" 
                                :label="field.label" 
                                :value="fieldKey"></el-option>
                        </el-select>
                    </td>
                    <td>
                        <p v-if="!mapField.field_key">{{ $t('Select') }} {{ labels.remote_label }} First</p>
                        <template v-else>
                            <el-select 
                                class="w-100" 
                                v-model="mapField.field_value" 
                                :placeholder="$t('Select Form Field')"
                                clearable
                            >
                                <el-option
                                    v-for="(formField,fieldName) in getFilteredFields(mapField.field_key)"
                                    :key="fieldName" 
                                    :value="fieldName"
                                    :label="formField.admin_label"></el-option>
                            </el-select>
                            <small v-if="advanced_fields[mapField.field_key]">{{advanced_fields[mapField.field_key].help_message}}</small>
                        </template>

                    </td>
                    <td>
                        <el-button
                            class="el-button--icon"
                            type="danger"
                            size="mini"
                            icon="el-icon-close"
                            style="margin-top: 3px;"
                            @click="deleteMapping('advanced_settings', index)"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-else class="no-mapping-alert">
            {{ $t('There is no advanced field mapping for this section.') }}
        </div>
    </template>

</div>
</template>

<script type="text/babel">
import inputPopover from '../input-popover.vue';
import each from "lodash/each";

export default {
    name: 'PostMetaPluginMapping',
    props: [
        'general_settings',
        'advanced_settings',
        'labels',
        'general_fields',
        'advanced_fields',
        'editorShortcodes',
        'form_fields'
    ],
    components: {
        inputPopover
    },
    methods: {
        addMapping(type) {
            this[type].push({
                field_key: '',
                field_value: ''
            });
        },
        deleteMapping(type, index) {
            this[type].splice(index, 1);
        },
        addAdvancedMetaFieldMapping() {
            if(!this.advanced_settings) {
                this.$set(this, 'advanced_settings', []);
            }
            this.advanced_settings.push({
                field_key: '',
                field_value: ''
            });
        },
        getFilteredFields(fieldKey) {
            let settingFields = this.advanced_fields[fieldKey];
            if (!settingFields) {
                return {};
            }
            const fields = {};
            each(this.form_fields, (item, itemName) => {
                if (settingFields.acceptable_fields.indexOf(item.element) !== -1) {
                    fields[itemName] = item;
                }
            });
            return fields;
        }
    }
}
</script>
