<?php

namespace FluentForm\App\Modules\PDF\Templates;

use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentForm\App\Modules\PDF\Templates\TemplateManager;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;


class InvoiceTemplate extends TemplateManager
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getDefaultSettings($form)
    {
        return [
            'logo' => '',
            'invoice_upper_text' => '',
            'invoice_thanks' => 'Thank you for your order',
            'invoice_prefix' => '',
            'customer_name' => '',
            'customer_email' => '',
            'customer_address' => ''
        ];
    }

    public function getSettingsFields()
    {
        return array(
            [
                'key' => 'logo',
                'label' => 'Business Logo',
                'tips' => 'Your Business Logo which will be shown in the invoice header',
                'component' => 'image_widget'
            ],
            [
                'key' => 'customer_name',
                'label' => 'Customer Name',
                'tips' => 'Please select the customer name field from the smartcode dropdown',
                'component' => 'value_text'
            ],
            [
                'key' => 'customer_email',
                'label' => 'Customer Email',
                'tips' => 'Please select the customer email field from the smartcode dropdown',
                'component' => 'value_text'
            ],
            [
                'key' => 'customer_address',
                'label' => 'Customer Address',
                'tips' => 'Please select the customer address field from the smartcode dropdown',
                'component' => 'value_text'
            ],
            [
                'key' => 'invoice_prefix',
                'label' => 'Invoice Prefix',
                'tips' => 'Add your invoice prefix which will be prepended with the invoice number',
                'component' => 'value_text'
            ],
            [
                'key' => 'invoice_upper_text',
                'label' => 'Invoice Body Text',
                'tips' => 'Write Invoice body text. This will show before the invoice items',
                'component' => 'wp-editor'
            ],
            [
                'key' => 'invoice_thanks',
                'label' => 'Invoice Footer Text',
                'tips' => 'Write Invoice Footer Text. This will show at the end of the invoice',
                'component' => 'value_textarea'
            ]
        );
    }

    public function generatePdf($submissionId, $feed, $outPut, $fileName = '')
    {
        $settings = $feed['settings'];
        $submission = wpFluent()->table('fluentform_submissions')
            ->where('id', $submissionId)
            ->first();
        $formData = json_decode($submission->response, true);

        $settings['invoice_lines'] = '{payment.order_items}';
        $invoiceUpperText = Arr::get($settings, 'invoice_upper_text', '');
        if (
            false !== strpos($invoiceUpperText, '{payment.receipt}') ||
            false !== strpos($invoiceUpperText, '{payment.order_items}')
        ) {
            $settings['invoice_lines'] = '';
        }
        $settings['payment_summary'] = '{payment.summary_list}';
        $settings = ShortCodeParser::parse($settings, $submissionId, $formData, null, false, 'pdfFeed');


        $htmlBody = $this->generateInvoiceHTML($submission, $settings, $feed);

        $htmlBody = str_replace('{page_break}', '<page_break />', $htmlBody);

        $form = wpFluent()->table('fluentform_forms')->find($submissionId);

        $htmlBody = apply_filters_deprecated(
            'ff_pdf_body_parse',
            [
                $htmlBody,
                $submissionId,
                $formData,
                $form
            ],
            FLUENTPDF_FRAMEWORK_UPGRADE,
            'fluentform/pdf_body_parse',
            'Use fluentform/pdf_body_parse instead of ff_pdf_body_parse.'
        );

        $htmlBody = apply_filters('fluentform/pdf_body_parse', $htmlBody, $submissionId, $formData, $form);

        if (!$fileName) {
            $fileName = ShortCodeParser::parse($feed['name'], $submissionId, $formData);
            $fileName = sanitize_title($fileName, 'pdf-file', 'display');
        }

        return $this->pdfBuilder($fileName, $feed, $htmlBody, '', $outPut);
    }

    private function generateInvoiceHTML($submission, $settings, $feed)
    {
        $paymentSettings = false;
        $logo = Arr::get($settings, 'logo');
        if (class_exists('\FluentFormPro\Payments\PaymentHelper')) {
            $paymentSettings = \FluentFormPro\Payments\PaymentHelper::getPaymentSettings();
            if (!$logo) {
                $logo = Arr::get($paymentSettings, 'business_logo');
            }
        }

        ob_start();
?>
        <table style="width: 100%; border: 0px solid transparent;">
            <tr>
                <td style="width: 40%" class="business_details">
                    <?php if ($logo) : ?>
                        <div class="business_logo">
                            <img src="<?php echo $logo; ?>" />
                        </div>
                    <?php endif; ?>
                    <?php if ($paymentSettings) : ?>
                        <div class="business_address">
                            <div class="business_name"><?php echo Arr::get($paymentSettings, 'business_name'); ?></div>
                            <div class="business_address"><?php echo Arr::get($paymentSettings, 'business_address'); ?></div>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="width: 20%"></td>
                <td style="width: 40%" class="customer_row">
                    <?php if (Arr::get($settings, 'invoice_prefix')) : ?>
                        <h2 style="padding-bottom: 30px" class="invoice_title"><?php _e('RECEIPT:', 'fluentform-pdf'); ?> <?php echo Arr::get($settings, 'invoice_prefix') . '-' . $submission->serial_number; ?></h2>
                        <br />
                    <?php endif; ?>

                    <div class="heading_items">
                        <div class="order_number"><b><?php _e('Order Number:', 'fluentform-pdf'); ?></b> <?php echo $submission->id; ?></div>
                        <div class="payment_date"><b><?php _e('Payment Date:', 'fluentform-pdf'); ?></b> <?php echo date(get_option('date_format'), strtotime($submission->created_at)); ?></div>
                        <br />
                        <div class="customer_details">
                            <?php if (Arr::get($settings, 'customer_name') || Arr::get($settings, 'customer_address') || Arr::get($settings, 'customer_email')) : ?>
                                <p style="font-weight: bold; margin-bottom:10px;" class="customer_heading"><?php _e('Customer Details', 'fluentform-pdf'); ?></p>
                                <p class="customer_name"><?php echo Arr::get($settings, 'customer_name'); ?></p>
                                <p class="customer_address"><?php echo Arr::get($settings, 'customer_address'); ?></p>
                                <p class="customer_email"><?php echo Arr::get($settings, 'customer_email'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <hr />
        <div class="receipt_upper_text"><?php echo Arr::get($settings, 'invoice_upper_text'); ?></div>

        <div class="invoice_lines"><?php echo Arr::get($settings, 'invoice_lines'); ?></div>

        <?php if (strpos(Arr::get($settings, 'payment_summary'), 'class="ffp_payment_info_table"') !== false) : ?>
            <div class="invoice_summary">
                <h3><?php _e('Payment Details', 'fluentform-pdf'); ?></h3>
                <?php echo Arr::get($settings, 'payment_summary'); ?>
            </div>
        <?php endif; ?>

        <div class="invoice_thanks">
            <?php echo Arr::get($settings, 'invoice_thanks'); ?>
        </div>
        <style>
            .business_logo {
                max-width: 200px;
                margin-bottom: 20px;
            }

            .business_logo img {
                margin-bottom: 20px;
                max-width: 200px;
                max-height: 100px;
            }

            .business_name {
                font-weight: bold;
                margin-bottom: 10px;
            }

            td.customer_row {
                text-align: right;
            }

            td.customer_row ul,
            td.customer_row ul li {
                list-style: none;
            }

            td.customer_row ul li {
                padding-bottom: 7px;
            }
        </style>
<?php
        return ob_get_clean();
    }
}
