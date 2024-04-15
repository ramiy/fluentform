<template>
	<div :class="{'ff_backdrop': show}">
		<el-dialog
			:visible.sync="show"
			width="50%"
		>
			<div slot="title">
				<h4 class="mb-2">{{ label }}</h4>
				<p>{{ description }}</p>
			</div>

			<div class="mt-2" v-if="options.length">
				<el-table
					:data="options"
					stripe
					style="width: 100%"
					max-height="500"
				>
					<template v-if="dynamic && dynamicColumns.length">
						<el-table-column
							v-for="(column, i) in dynamicColumns"
							:prop="column.prop"
							:label="$t(column.label)"
							:key="column.prop"
							:min-width="i === 0 ? '80' : '150'"
							:fixed="i === 0"
						></el-table-column>
					</template>

					<template v-else>
						<el-table-column v-if="isText">
							<template slot="header">
								<span>
								      {{ $t('Value') }}
								      <el-tooltip
									      :content="$t('Choose the option you want to be used as the value')"
									      placement="top"
								      >
								        <i class="ff-icon el-icon-info"></i>
								      </el-tooltip>
							    </span>
							</template>
							<template v-slot="scope">
								<el-radio
									:disabled="textValueDisable"
									v-model="defaultValue"
									:label="scope.row.value"
									@change="$emit('close-modal')"
								></el-radio>
							</template>
						</el-table-column>

						<template v-else>
							<el-table-column v-if="isCheckable">
								<template slot="header">
								    <span>
								      {{ $t('Value') }}
								      <el-tooltip
									      :content="$t('Check if you want to be used value as the default value.')"
									      placement="top"
								      >
								        <i class="ff-icon el-icon-info"></i>
								      </el-tooltip>
								    </span>
								</template>
								<template v-slot="scope">
									<el-checkbox
										v-model="selectedValues"
										:label="scope.row.value"
									></el-checkbox>
								</template>
							</el-table-column>
							<el-table-column v-else>
								<template slot="header">
								    <span>
								      {{ $t('Value') }}
								      <el-tooltip
									      :content="$t('Select option if you want to be used value as the default value.')"
									      placement="top">
								        <i class="ff-icon el-icon-info"></i>
								      </el-tooltip>
								    </span>
								</template>
								<template v-slot="scope">
									<el-radio
										v-model="defaultValue"
										:label="scope.row.value"
										@change="$emit('close-modal')"
									></el-radio>
								</template>
							</el-table-column>

							<el-table-column
								prop="label"
								:label="$t('Label')"
							></el-table-column>
						</template>
					</template>
				</el-table>
			</div>
			<div v-else>
				<p>{{ $t('Empty ' + (isText ? 'Values' : 'Options')) }}</p>
			</div>
		</el-dialog>
	</div>
</template>

<script type="text/babel">

export default {
	name: 'DynamicFilterOptionsDialog',
	props: ['visible', 'options', 'dynamic', 'type', 'value', 'textValueDisable'],
	data() {
		return {
			selectedValues: Array.isArray(this.value) ? this.value : []
		}
	},
	watch: {
		selectedValues() {
			this.$emit('input', this.selectedValues);
		}
	},
	computed: {
		defaultValue: {
			get() {
				return this.value;
			},
			set(value) {
				this.$emit('input', value);
			}
		},
		show: {
			get() {
				return this.visible;
			},
			set() {
				this.$emit('close-modal');
			}
		},
		dynamicColumns() {
			const keys = Object.keys(this.options[0] || {});
			return keys.map(key => ({
				prop: key,
				label: _ff.startCase(key),
			}));
		},
		label() {
			let label = this.$t('Options');
			if ('result' === this.type) {
				label = this.$t('Results');
			} else if (this.isText) {
				label = this.$t('Values');
			}
			return label;
		},
		isText() {
			return 'text' === this.type;
		},
		isCheckable() {
			return ['multi_select', 'checkbox'].includes(this.type)
		},
		description() {
			let description = this.$t('Valid options make by template mapping');
			if ('result' === this.type) {
				description = this.$t('Result found by filters');
			} else if (this.isText) {
				description = this.$t('Valid values by template mapping');
			}
			return description;
		}
	}
}
</script>
