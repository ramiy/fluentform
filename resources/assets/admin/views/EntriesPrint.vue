<template>
	<el-dialog
		top="42px"
		:visible.sync="show"
		width="60%"
	>
		<template slot="title">
			<h4>{{ for_bulk_action ? $t('Entries Print') : $t('Entry Print') }}</h4>
		</template>
		<template v-if="has_entry_print">
			<div>
				<el-form>
					<el-form-item class="ff-form-item mt-4">
						<template slot="label">
							{{ $t('Select PDF Template') }}

							<el-tooltip class="item" style="z-index: 99999" placement="top-start" popper-class="ff_tooltip_wrap">
								<div slot="content" style="z-index: 999999">
									<p>
										{{ $t('Select the PDF template you would like to map.') }}
									</p>
								</div>
								<i class="ff-icon ff-icon-info-filled text-primary"></i>
							</el-tooltip>
						</template>
						<el-select v-model="feed_id" :placeholder="$t('Select Pdf Feed')">
							<el-option
								v-for="option in feed_list"
								:key="option.id"
								:label="option.label"
								:value="option.id"
							>
							</el-option>
						</el-select>
					</el-form-item>
					<el-form-item class="ff-form-item">
						<el-checkbox v-model="with_notes" :label="$t('With Entry Notes')"></el-checkbox>
					</el-form-item>
				</el-form>
				<div style="text-align: right;">
					<el-button size="medium" @click="printEntry">
						<i class="ff-icon el-icon-printer"/> <span>{{ $t('Print') }}</span>
					</el-button>
				</div>
			</div>
		</template>
		<notice class="ff_alert_between mt-4" type="info-soft" v-else>
			<div>
				<h6 class="title">{{ $t('This is a Fluent Forms PDF Feature') }}</h6>
				<p class="text">{{ $t('Please upgrade to Fluent Forms PDF to unlock this feature.') }}</p>
			</div>
		</notice>
	</el-dialog>
</template>

<script type="text/babel">
import Notice from '@/admin/components/Notice/Notice.vue';

export default {
	name: 'entries-print',
	props: ['has_pdf', 'form_id', 'entry_ids', 'show_print', 'sort_by'],
	components: {
		Notice
	},
	data() {
		return {
			with_notes: true,
			show: false,
			feed_id: '',
			pdf_feeds: []
		}
	},
	computed: {
		has_entry_print() {
			return this.has_pdf && window.fluent_form_entries_vars?.has_entry_print;
		},
		for_bulk_action() {
			return Array.isArray(this.entry_ids);
		},
		feed_list() {
			const list = [
				{
					id: '',
					label: 'Default'
				}
			];
			return [...list, ...this.pdf_feeds];
		}
	},
	watch: {
		show(value) {
			if (!value) {
				this.$emit('close');
			}
		},
		show_print(value) {
			this.show = value;
		}
	},
	methods: {
		printEntry() {
			if (!this.has_entry_print) {
				return;
			}
			FluentFormsGlobal.$post({
						action: 'fluentform_pdf_admin_ajax_actions',
						route: 'print_entries',
						submission_ids: this.for_bulk_action ? this.entry_ids : [this.entry_ids],
						form_id: this.form_id,
						notes: this.with_notes,
						feed_id: this.feed_id,
						sort_by: this.sort_by || 'DESC'
					}
				)
				.then(res => {
					if (res?.data?.success && res.data.url) {
						jQuery('#printFrame').remove();
						const frame = jQuery('<iframe>', {
							src: res.data.url,
							name: 'printFrame',
							id: 'printFrame',
							style: 'display:none;',
							width:'100%',
							height:'100%',
						}).appendTo('body');
						frame.on('load', () => {
							const contentWindow = frame[0]?.contentWindow;
							if (contentWindow) {
								contentWindow.focus();
								contentWindow.print();
							} else {
								window.frames['printFrame']?.focus();
								window.frames['printFrame']?.print();
							}
						})
					} else {
						this.$notify.error({
							offset: 32,
							title: 'Error',
							message: 'Failed to create pdf file.'
						});
					}
				})
				.catch(e => {
					this.$notify.error({
						offset: 32,
						title: 'Error',
						message: 'Failed to create pdf file.'
					});
				})
				.always(() => {
					this.$emit('close');
				});
		},
		getPdfFeeds() {
			FluentFormsGlobal.$get({
					action: 'fluentform_pdf_admin_ajax_actions',
					form_id: this.form_id,
					route: 'feed_lists'
				})
				.then(response => {
					if (response?.data?.pdf_feeds && Array.isArray(response.data.pdf_feeds)) {
						this.pdf_feeds = response.data.pdf_feeds;
					}
				})
				.fail(() => {});
		}
	},

	mounted() {
		this.getPdfFeeds()
	}
}
</script>

