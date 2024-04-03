<template>
	<div>
		<el-label
			v-if="listItem.label"
			:label="listItem.label"
			:helpText="listItem.help_text"
			class="el-form-item__label"
		></el-label>
		<div class="ff-dynamic-warp">
			<!-- Type -->
			<el-form-item>
				<elLabel
					slot="label"
					:label="$t('Type')"
					:help-text="$t('Choose the type to populate dynamically')"
				></elLabel>
				<el-select class="el-fluid" v-model="model.type">
					<el-option
						v-for="(label, key) in listItem.types"
						:key="key"
						:label="label"
						:value="key"
					></el-option>
				</el-select>
			</el-form-item>

			<!-- Filters -->
			<el-form-item>
				<elLabel
					slot="label"
					:label="$t('Filters')"
					:help-text="$t('Refine search results by specifying database query filters. Utilize logical operators like AND/OR to group multiple filters, ensuring more precise filtering.')"
				></elLabel>
				<div v-if="model.filters.length" class="ff-dynamic-filter-wrap">
					<div v-for="(groups, groupsIndex) in model.filters" :key="'groups_' + groupsIndex">
						<div v-if="groupsIndex !== 0" class="ff-dynamic-filter-condition condition-or">
							<span class="condition-border"></span>
							<span class="condition-item">OR</span>
							<span class="condition-border"></span>
						</div>
						<div class="ff-dynamic-filter-groups">
							<!-- Filters Group-->
							<dynamic-filter-group
								v-for="(group, groupIndex) in groups" :key="'group_' + groupsIndex + groupIndex"
								:group="group"
								:groupsIndex="groupsIndex"
								:add-and-text="groupIndex !== 0"
								:list-item="listItem"
								:filter-columns="filterColumns"
								:filter_value_options="filter_value_options"
								@add-group="addFilter(groupsIndex, groupIndex)"
								@remove-group="removeFilter(groupsIndex, groupIndex)"
								@update-filter-value-options="updateFilterValueOptions"
							></dynamic-filter-group>
						</div>
					</div>
				</div>
				<div>
					<el-button
						@click="addFilterGroup"
						type="primary" size="mini"
						icon="el-icon-plus"
					>{{ $t('Add Filter Group') }}
					</el-button>
				</div>
			</el-form-item>

			<!-- Unique Result -->
			<el-form-item>
				<input-yes-no-checkbox
					v-model="model.unique_result"
					:listItem="{
						label: $t('Only Show Unique Result'),
						help_text : $t(`Toggle to display only unique results based on the `) + listItem.types[model.type]}"
				></input-yes-no-checkbox>
			</el-form-item>

			<!-- Ordering -->
			<el-form-item>
				<elLabel
					slot="label"
					:label="$t('Ordering')"
					:help-text="$t('Specify the ordering of the dynamically populate')"
				></elLabel>
				<el-row :gutter="20">
					<el-col :span="12">
						<el-select v-model="model.sort_by" clearable filterable>
							<el-option
								v-for="(label, value) in filterColumns"
								:key="'key_' + value"
								:label="label"
								:value="value"
							></el-option>
						</el-select>
					</el-col>
					<el-col :span="12">
						<el-select v-model="model.order_by">
							<el-option
								v-for="(label, value) in listItem.order"
								:key="'key_' + value"
								:label="label"
								:value="value"
							></el-option>
						</el-select>
					</el-col>
				</el-row>
			</el-form-item>

			<!-- Filter Result -->
			<el-form-item>
				<el-row :gutter="20" type="flex" align="middle">
					<el-col :span="8">
						<el-button @click="getResult" type="primary" size="mini"
						           :icon="result_loading ? 'el-icon-loading' : 'el-icon-refresh'">{{ $t('Get Result') }}
						</el-button>
					</el-col>
					<el-col :span="16">
						<p>
							<span>
								<el-link
									@click="validOptionsDialog=true"
									type="primary"
								>{{ result_counts.valid || 0 }}
								</el-link>
							    {{ $t(`valid ${ isTextType ? 'values' : 'option' } of `) }}
							</span>
							<span>
								<el-link
									@click="allOptionsDialog = true"
									type="primary"
								>{{ result_counts.total || 0 }}
								</el-link>
								{{ $t('results') }}
							</span>
						</p>
					</el-col>
				</el-row>
			</el-form-item>

			<!-- Valid Result Modal-->
			<dynamic-filter-options-dialog
				v-model="editItem.attributes.value"
				@close-modal="validOptionsDialog=false"
				:text-value-disable="'yes' === model.dynamic_fetch"
				:visible="validOptionsDialog"
				:options="valid_options"
				:type="editItem.settings.field_type"
			></dynamic-filter-options-dialog>

			<!-- Result Modal-->
			<dynamic-filter-options-dialog
				@close-modal="allOptionsDialog=false"
				:visible="allOptionsDialog"
				:dynamic="true"
				:options="all_options"
				type="result"
			></dynamic-filter-options-dialog>


			<!-- Template Mapping -->
			<el-form-item v-if="hasTemplateColumnsOptions">
				<elLabel
					slot="label"
					:label="$t('Template Mapping')"
					:help-text="$t('Define the mapping template for generate options. Use placeholders to dynamically insert values from the database records.')"
				>
				</elLabel>

				<!-- Template Label Mapping -->
				<el-row v-if="!isTextType" :gutter="20" type="flex" align="middle" class="mb-2">
					<el-col :span="4">{{ $t('Label') }}</el-col>
					<el-col :span="16">
						<inputPopover
							v-if="isCustomTemplateLabel"
							fieldType="text"
							v-model="model.template_label.value"
							:data="templateOptionsAsShortCode"
							attr-name=""
						></inputPopover>
						<el-select v-else v-model="model.template_label.value" class="el-fluid">
							<el-option
								v-for="(label, value) in templateColumnsOptions"
								:key="'key_' + value"
								:label="label"
								:value="value"
							></el-option>
						</el-select>
					</el-col>
					<el-col :span="4">
						<el-button
							:type="isCustomTemplateLabel ? 'primary' : ''"
							@click="model.template_label.custom = !isCustomTemplateLabel"
							icon="el-icon-edit"
						></el-button>
					</el-col>
				</el-row>

				<!-- Template Value Mapping -->
				<el-row :gutter="20" type="flex" align="middle">
					<el-col :span="4">{{ $t('Value') }}</el-col>
					<el-col :span="16">
						<inputPopover
							v-if="isCustomTemplateValue"
							fieldType="text"
							v-model="model.template_value.value"
							:data="templateOptionsAsShortCode"
							attr-name=""
						></inputPopover>
						<el-select v-else v-model="model.template_value.value" class="el-fluid">
							<el-option
								v-for="(label, value) in templateColumnsOptions"
								:key="'key_' + value"
								:label="label"
								:value="value"
							></el-option>
						</el-select>
					</el-col>
					<el-col :span="4">
						<el-button
							:type="isCustomTemplateValue ? 'primary' : ''"
							@click="model.template_value.custom = !isCustomTemplateValue"
							icon="el-icon-edit"
						></el-button>
					</el-col>
				</el-row>
			</el-form-item>

			<!-- Dynamic Retrieval -->
			<el-form-item>
				<input-yes-no-checkbox
					v-if="isTextType"
					v-model="model.dynamic_fetch"
					:listItem="{
						label: $t('Dynamic Value Retrieval'),
						help_text : $t('When checked, value are dynamically fetched based on filters during rendering, and the first valid value is used. Leave unchecked to use the current valid value mapping.')}"
				></input-yes-no-checkbox>

				<input-yes-no-checkbox
					v-else
					v-model="model.dynamic_fetch"
					:listItem="{
						label: $t('Dynamic Options Retrieval'),
						help_text : $t('When checked, options are dynamically fetched based on filters during rendering. If unchecked, the current valid options remain unchanged.')}"
				></input-yes-no-checkbox>
			</el-form-item>
		</div>
	</div>
</template>

<script>
import elLabel from '../../includes/el-label.vue'
import inputYesNoCheckbox from './inputYesNoCheckbox.vue';
import dynamicFilterGroup from './helpers/DynamicFilterGroup.vue'
import inputPopover from '../../input-popover.vue';
import debounce from 'lodash/debounce';
import DynamicFilterOptionsDialog from './helpers/DynamicFilterOptionsDialog.vue';

export default {
	name: 'dynamicValue',
	props: ['editItem', 'listItem', 'value'],
	components: {
		DynamicFilterOptionsDialog,
		elLabel,
		inputYesNoCheckbox,
		dynamicFilterGroup,
		inputPopover
	},
	data() {
		return {
			model: this.value,
			result_loading: false,
			filter_value_options: {},
			valid_options: [],
			all_options: [],
			result_counts: {
				total: 0,
				valid: 0,
			},
			validOptionsDialog: false,
			allOptionsDialog: false,
		}
	},
	watch: {
		'editItem.settings.field_type'() {
			this.maybeResetTextValue();
		},
		'model.type'() {
			this.getFilterValueOptions();
		},
		model: {
			handler() {
				this.$emit('input', this.model);
				this.getDebounceResult()
			},
			deep: true,
		},
		valid_options() {
			this.maybeResetTextValue()
		}
	},
	methods: {
		maybeResetTextValue(){
			// Set first valid value for dynamically fetched
			if (this.isTextType && 'yes' === this.model.dynamic_fetch) {
				this.editItem.attributes.value = this.valid_options[0]?.value || '';
			}
		},

		updateFilterValueOptions(key, options){
			this.filter_value_options = {...this.filter_value_options, [key] : options};
		},

		addFilterGroup() {
			this.model.filters = [...this.model.filters, [
				{
					column: '',
					custom: false,
					operator: '',
					value: ''
				}
			]];
		},

		removeFilter(groupsIndex, index) {
			this.model.filters[groupsIndex].splice(index, 1);
			if (this.model.filters[groupsIndex].length === 0) {
				this.model.filters.splice(groupsIndex, 1);
			}
		},

		addFilter(groupsIndex, index) {
			this.model.filters[groupsIndex].splice(index + 1, 0, {
				column: '',
				custom: false,
				operator: '',
				value: ''
			});
		},

		getFilterValueOptions(onMounted = false) {
			FluentFormsGlobal.$get({
					action: 'fluentform-get-dynamic-filter-value-options',
					type: this.model.type
				})
				.done(res => {
					this.filter_value_options = {...this.filter_value_options, ...res.data.options };
					if (!onMounted) {
						this.model.filters = res.data.default_config.filters || [];
						this.model.sort_by = res.data.default_config.sort_by || '';
						this.model.order_by = res.data.default_config.order_by || '';
						this.model.template_value = res.data.default_config.template_value || '';
						this.model.template_label = res.data.default_config.template_label || '';
					}
				})
				.fail(error => {
					console.log(error?.responseJSON)
				})
				.always(() => {
				});
		},

		getResult() {
			this.result_loading = true;
			FluentFormsGlobal.$get({
					action: 'fluentform-get-dynamic-filter-result',
					config: this.model
				})
				.done(res => {
					this.result_counts = res.data.result_counts || {};
					this.valid_options = res.data.valid_options || [];
					this.editItem.settings.advanced_options = res.data.valid_options || [];
					this.all_options = res.data.all_options || [];
				})
				.fail(error => {
					console.log(error?.responseJSON)
				})
				.always(() => {
					this.result_loading = false;
				});
		},

		getDebounceResult: debounce(function () {
			this.getResult();
		}, 3 * 1000),
	},
	computed: {
		filterColumns() {
			return this.listItem.columns[this.model.type] || [];
		},
		hasTemplateColumnsOptions() {
			return Object.keys(this.templateColumnsOptions).length;
		},
		isCustomTemplateLabel() {
			return this.model.template_label.custom;
		},
		isCustomTemplateValue() {
			return this.model.template_value.custom;
		},
		templateOptionsAsShortCode() {
			let shortcodes = {
				shortcodes: this.templateColumnsOptions,
				title: ''
			};
			return [shortcodes];
		},
		templateColumnsOptions() {
			let options = {};
			if (this.all_options && this.all_options[0]) {
				for (const prop in this.all_options[0]) {
					options[`{${ prop }}`] = _ff.startCase(prop)
				}
			}
			return options;
		},
		isTextType() {
			return 'text' === this.editItem.settings.field_type;
		},
	},
	mounted() {
		this.getFilterValueOptions(true);
		this.getResult();
	}
}
</script>