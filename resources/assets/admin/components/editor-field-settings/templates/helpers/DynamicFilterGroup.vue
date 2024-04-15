<template>
	<div class="ff-dynamic-filter-group">
		<div v-if="addAndText" class="ff-dynamic-filter-condition">
			<span class="condition-border"></span>
			<span class="condition-item">AND</span>
			<span class="condition-border"></span>
		</div>
		<el-row :gutter="20" class="mb-2">
			<el-col :span="12">
				<el-select
					v-model="group['column']"
					@change="resetValue"
					clearable filterable
				>
					<el-option
						v-for="(label, value) in filterColumns"
						:key="'key_' + value"
						:label="label"
						:value="value"
					></el-option>
				</el-select>
			</el-col>
			<el-col :span="12">
				<el-select
					@change="resetValue"
					v-model="group['operator']"
				>
					<el-option
						v-for="(label, operator) in operators"
						:key="'key_' + operator"
						:label="label"
						:value="operator"
					></el-option>
				</el-select>
			</el-col>
		</el-row>
		<el-row :gutter="20" class="mb-2" v-if="group['column'] && group['operator']">
			<el-col :span="24">
				<template v-if="isSelectableValue">
					<el-row :gutter="20">
						<el-col :span="19">
							<el-input v-if="isCustom" v-model="custom_value"></el-input>
							<el-select
								v-else
								class="el-fluid"
								v-model="group['value']"
								:multiple="isMultipleTypeOperator" filterable
							>
								<el-option
									v-for="(label, value) in filterValueOptions"
									:key="'key_' + value"
									:label="label"
									:value="value"
								></el-option>
							</el-select>
						</el-col>
						<el-col :span="4">
							<el-button
								:type="isCustom ? 'primary' : ''"
								@click="toggleCustom"
								icon="el-icon-edit"
							></el-button>
						</el-col>
					</el-row>
				</template>
				<el-date-picker
					class="w-100"
					v-else-if="isDateType"
					v-model="group['value']"
					value-format="yyyy-MM-dd HH:mm:ss"
					:type="dateType"
					:range-separator="$t('To')"
					:start-placeholder="$t('Start date')"
					:end-placeholder="$t('End date')">
				</el-date-picker>
				<el-input v-else v-model="group['value']"></el-input>
			</el-col>
		</el-row>
		<action-btn>
			<action-btn-add @click="$emit('add-group')" size="mini"></action-btn-add>
			<action-btn-remove @click="$emit('remove-group')" size="mini"></action-btn-remove>
		</action-btn>
	</div>
</template>

<script type="text/babel">
import ActionBtn from '@/admin/components/ActionBtn/ActionBtn.vue';
import ActionBtnAdd from '@/admin/components/ActionBtn/ActionBtnAdd.vue';
import ActionBtnRemove from '@/admin/components/ActionBtn/ActionBtnRemove.vue';
import debounce from 'lodash/debounce';

export default {
	name: 'DynamicFilterGroup',
	props: ['addAndText', 'listItem', 'filterColumns', 'group', 'filter_value_options', 'groupsIndex', 'groups'],
	data() {
		return {
			custom_value: String(this.group.value),
			form_fields_key : `form_fields_${this.groupsIndex}`
		}
	},
	watch: {
		'custom_value'() {
			this.group.value = this.custom_value;
		},
		'group.value'() {
			this.getDebounceFormFields();
		},
		'group.column'() {
			this.group.operator = Object.keys(this.operators)[0];
			this.getDebounceFormFields();
		}
	},
	components: {
		ActionBtn,
		ActionBtnAdd,
		ActionBtnRemove,
	},
	methods: {
		getDebounceFormFields: debounce(function () {
			this.maybeGetFormFields();
		}, 2 * 1000),

		maybeGetFormFields(mounted = false) {
			if ('fluentform_submissions.form_id' !== this.group.column) {
				return;
			}
			let formId = this.group.value;
			if (Array.isArray(formId)) {
				formId = formId[0];
			}
			if (formId) {
				FluentFormsGlobal.$get({
						action: 'fluentform-get-dynamic-filter-form-fields',
						form_id: formId
					})
					.done(res => {
						if (res.data.options) {
							this.$emit('update-filter-value-options', this.form_fields_key, res.data.options);
						}
					})
					.fail(error => {
					})
					.always(() => {
					});
			}
			if (!mounted) {
				let nameField = this.groups.find(g => g.column === 'field_name');
				if (nameField && ['IN', 'NOT IN'].includes(nameField.operator)) {
					nameField.value = [];
				} else {
					nameField.value = '';
				}
			}
		},
		toggleCustom() {
			this.group.custom = !this.group.custom;
			this.resetValue();
		},
		resetValue() {
			let value = '';
			if (this.isCustom) {
				this.custom_value = '';
			} else if (this.isMultipleTypeOperator) {
				value = [];
			}
			this.group.value = value;
		}
	},
	computed: {
		operators() {
			let operators = { ...this.listItem.operators };
			if (this.isDateType) {
				for (const key in operators) {
					if (!(['>', '>=', '<', '<=', 'BETWEEN', 'NOT BETWEEN'].includes(key))) {
						delete operators[key];
					}
				}
				return operators;
			}

			if ('is_favourite' === this.group.column) {
				return {
					'=' : operators['='],
					'!=' : operators['!='],
				};
			}

			if (!this.listItem.numeric_columns.includes(this.group.column)) {
				for (const key in operators) {
					if (['>', '>=', '<', '<='].includes(key)) {
						delete operators[key];
					}
				}
			}
			delete operators['BETWEEN'];
			delete operators['NOT BETWEEN'];
			return operators;
		},
		isDateType() {
			return this.listItem.date_columns?.includes(this.group.column)
		},
		dateType() {
			if (['BETWEEN', 'NOT BETWEEN'].includes(this.group.operator)) {
				return 'daterange';
			}
			return 'date';
		},
		filterValueOptions() {
			let options = false;
			if (this.group.column) {
				let key = this.group.column;
				if ('field_name' === key) {
					key = this.form_fields_key;
				}
				options = this.filter_value_options[key];
			}
			return options;
		},
		isSelectableValue() {
			return this.filterValueOptions && ['=', '!=', 'IN', 'NOT IN'].includes(this.group.operator);
		},
		isMultipleTypeOperator() {
			return ['IN', 'NOT IN'].includes(this.group.operator)
		},
		isCustom() {
			return this.group.custom;
		}
	},
	mounted() {
		if ('fluentform_submissions.form_id' === this.group.column) {
			this.maybeGetFormFields(true);
		}
	}
}
</script>
