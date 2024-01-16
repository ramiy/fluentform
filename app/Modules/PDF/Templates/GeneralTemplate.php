<?php

namespace FluentForm\App\Modules\PDF\Templates;

use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentForm\App\Modules\PDF\Templates\TemplateManager;

class GeneralTemplate extends TemplateManager
{

    public $headerHtml = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function getDefaultSettings($form)
    {
        return [
            'header' => '<h2>PDF Title</h2>',
            'footer' => '<table width="100%"><tr><td width="50%">{DATE j-m-Y}</td><td width="50%"  style="text-align: right;" align="right">{PAGENO}/{nbpg}</td></tr></table>',
            'body'   => '{all_data}'
        ];
    }

    public function getSettingsFields()
    {
        return array(
            [
                'key'       => 'header',
                'label'     => 'Header Content',
                'tips'      => 'Write your header content which will be shown every page of the PDF',
                'component' => 'wp-editor'
            ],
            [
                'key'        => 'body',
                'label'      => 'PDF Body Content',
                'tips'       => 'Write your Body content for actual PDF body',
                'component'  => 'wp-editor',
                'inline_tip' => defined('FLUENTFORMPRO') ?
                    sprintf(
                        __(
                            'You can use Conditional Content in PDF body, for details please check this %s. ',
                            'fluentform-pdf'
                        ),
                        '<a target="_blank" href="https://wpmanageninja.com/docs/fluent-form/advanced-features-functionalities-in-wp-fluent-form/conditional-shortcodes-in-email-notifications-form-confirmation/">Documentation</a>'
                    ) : __(
                        'Conditional PDF Body Content is supported in Fluent Forms Pro Version',
                        'fluentform-pdf'
                    ),

            ],
            [
                'key'        => 'footer',
                'label'      => 'Footer Content',
                'tips'       => 'Write your Footer content which will be shown every page of the PDF',
                'component'  => 'wp-editor',
                'inline_tip' => 'Write your Footer content which will be shown every page of the PDF',

            ]
        );
    }

    public function generatePdf($submissionId, $feed, $outPut = 'I', $fileName = '')
    {
        $settings = $feed['settings'];
        $submission = wpFluent()->table('fluentform_submissions')
            ->where('id', $submissionId)
            ->first();
        $formData = json_decode($submission->response, true);

        $settings = ShortCodeParser::parse($settings, $submissionId, $formData, null, false, 'pdfFeed');

        if (!empty($settings['header'])) {
            $this->headerHtml = $settings['header'];
        }

        $htmlBody = $settings['body'];  // Inserts HTML line breaks before all newlines in a string

        $form = wpFluent()->table('fluentform_forms')->find($submission->form_id);

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

        $footer = $settings['footer'];

        if (!$fileName) {
            $fileName = ShortCodeParser::parse($feed['name'], $submissionId, $formData);
            $fileName = sanitize_title($fileName, 'pdf-file', 'display');
        }

        return $this->pdfBuilder($fileName, $feed, $htmlBody, $footer, $outPut);
    }
}
