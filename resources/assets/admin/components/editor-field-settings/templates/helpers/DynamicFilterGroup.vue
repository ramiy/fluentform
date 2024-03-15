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

export default {
	name: 'DynamicFilterGroup',
	props: ['addAndText', 'listItem', 'filterColumns', 'group', 'filter_value_options'],
	data() {
		return {
			custom_value: String(this.group.value)
		}
	},
	watch: {
		'custom_value'() {
			this.group.value = this.custom_value;
		}
	},
	components: {
		ActionBtn,
		ActionBtnAdd,
		ActionBtnRemove,
	},
	methods: {
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
			if (!this.listItem.numeric_columns.includes(this.group.column)) {
				for (const key in operators) {
					if (['>', '>=', '<', '<='].includes(key)) {
						delete operators[key];
					}
				}
			}
			return operators;
		},
		filterValueOptions() {
			let options = false;
			if (this.group.column) {
				options = this.filter_value_options[this.group.column];
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
	}
}
</script>
