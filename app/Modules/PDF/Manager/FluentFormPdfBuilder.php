<?php

namespace FluentForm\App\Modules\PDF\Manager;

use FluentForm\App\Helpers\Protector;
use FluentForm\App\Modules\Acl\Acl;
use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentFormPdf\Support\Arr;
use FluentFormPdf\Classes\Controller\AvailableOptions;
use FluentFormPdf\Classes\Controller\FontDownloader;
use FluentFormPdf\Classes\Controller\Activator;
use FluentFormPdf\API\Pdf;

class FluentFormPdfBuilder
{
    protected $optionKey = '_fluentform_pdf_settings';

    public function __construct()
    {
        $this->registerHooks();
    }

    protected function registerHooks()
    {
        // Global settings register
        add_filter('fluentform/global_settings_components', [$this, 'globalSettingMenu']);
        add_filter('fluentform/form_settings_menu', [$this, 'formSettingsMenu']);

        // single form pdf settings fields ajax
        add_action(
            'wp_ajax_fluentform_get_form_pdf_template_settings',
            [$this, 'getFormTemplateSettings']
        );

        add_action('wp_ajax_fluentform_pdf_admin_ajax_actions', [$this, 'ajaxRoutes']);
        /*
                 * Changed from : fluentform_single_entry_widgets
                 */
        add_filter('fluentform/submissions_widgets', array($this, 'pushPdfButtons'), 10, 3);

        add_filter('fluentform/email_attachments', array($this, 'maybePushToEmail'), 10, 5);

        add_action('fluentform/addons_page_render_fluentform_pdf_settings', array($this, 'renderGlobalPage'));

        add_filter('fluentform/pdf_body_parse', function ($content, $entryId, $formData, $form) {

            if (!defined('FLUENTFORMPRO')) {
                return $content;
            }
            $processor = new \FluentFormPro\classes\ConditionalContent();
            return $processor::initiate($content, $entryId, $formData, $form);
        }, 10, 4);

        add_filter('fluentform/all_editor_shortcodes', [$this, 'pushShortCode'], 10, 2);
        add_filter(
            'fluentform/shortcode_parser_callback_pdf.download_link',
            [$this, 'createLink'],
            10,
            2
        );

        add_filter(
            'fluentform/shortcode_parser_callback_pdf.download_link.public',
            [$this, 'createPublicLink'],
            10,
            2
        );

        add_action('wp_ajax_fluentform_pdf_download', [$this, 'download']);
        add_action('wp_ajax_fluentform_pdf_download_public', [$this, 'downloadPublic']);
        add_action('wp_ajax_nopriv_fluentform_pdf_download_public', [$this, 'downloadPublic']);
    }

    public function globalSettingMenu($setting)
    {
        $setting["pdf_settings"] = [
            "hash" => "pdf_settings",
            "title" => __("PDF Settings", 'fluentform-pdf')
        ];

        return $setting;
    }

    public function formSettingsMenu($settingsMenus)
    {
        $settingsMenus['pdf'] = [
            'title' => __('PDF Feeds', 'fluentform-pdf'),
            'slug' => 'pdf-feeds',
            'hash' => 'pdf',
            'route' => '/pdf-feeds'
        ];

        return $settingsMenus;
    }

    public function ajaxRoutes()
    {
        $maps = [
            'get_global_settings' => 'getGlobalSettingsAjax',
            'save_global_settings' => 'saveGlobalSettings',
            'get_feeds' => 'getFeedsAjax',
            'feed_lists' => 'getFeedListAjax',
            'create_feed' => 'createFeedAjax',
            'get_feed' => 'getFeedAjax',
            'save_feed' => 'saveFeedAjax',
            'delete_feed' => 'deleteFeedAjax',
            'download_pdf' => 'getPdf',
            'downloadFonts' => 'downloadFonts'
        ];

        $route = sanitize_text_field($_REQUEST['route']);

        Acl::verify('fluentform_forms_manager');

        if (isset($maps[$route])) {
            $this->{$maps[$route]}();
        }
    }

    public function getGlobalSettingsAjax()
    {
        wp_send_json_success([
            'settings' => Pdf::getGlobalConfig(),
            'fields' => $this->getGlobalFields()
        ]);
    }


    public function saveGlobalSettings()
    {
        $settings = wp_unslash($_REQUEST['settings']);

        update_option('_fluentform_pdf_settings', $settings);
        wp_send_json_success([
            'message' => __('Settings successfully updated', 'fluentform-pdf')
        ], 200);
    }

    public function getFeedsAjax()
    {
        $formId = intval($_REQUEST['form_id']);

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        $feeds = $this->getFeeds($form->id);

        wp_send_json_success([
            'pdf_feeds' => $feeds,
            'templates' => $this->getAvailableTemplates($form)
        ], 200);
    }

    public function getFeedListAjax()
    {
        $formId = intval($_REQUEST['form_id']);

        $feeds = $this->getFeeds($formId);

        $formattedFeeds = [];
        foreach ($feeds as $feed) {
            $formattedFeeds[] = [
                'label' => $feed['name'],
                'id' => $feed['id']
            ];
        }

        wp_send_json_success([
            'pdf_feeds' => $formattedFeeds
        ], 200);
    }

    public function createFeedAjax()
    {
        $templateName = sanitize_text_field($_REQUEST['template']);
        $formId = intval($_REQUEST['form_id']);

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        $templates = $this->getAvailableTemplates($form);

        if (!isset($templates[$templateName]) || !$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No template found!', 'fluentform-pdf')
            ], 423);
        }

        $template = $templates[$templateName];

        $class = $template['class'];
        if (!class_exists($class)) {
            wp_send_json_error([
                'message' => __('Sorry! No template Class found!', 'fluentform-pdf')
            ], 423);
        }
        $instance = new $class();

        $defaultSettings = $instance->getDefaultSettings($form);

        $data = [
            'name' => $template['name'],
            'template_key' => $templateName,
            'settings' => $defaultSettings,
            'appearance' => Pdf::getGlobalConfig()
        ];

        $insertId = wpFluent()->table('fluentform_form_meta')
            ->insertGetId([
                'meta_key' => '_pdf_feeds',
                'form_id' => $formId,
                'value' => wp_json_encode($data)
            ]);

        wp_send_json_success([
            'feed_id' => $insertId,
            'message' => __('Feed has been created, edit the feed now')
        ], 200);
    }

    private function getFeeds($formId)
    {
        $feeds = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', '_pdf_feeds')
            ->get();
        $formattedFeeds = [];
        foreach ($feeds as $feed) {
            $settings = json_decode($feed->value, true);
            $settings['id'] = $feed->id;
            $formattedFeeds[] = $settings;
        }

        return $formattedFeeds;
    }

    public function getFeedAjax()
    {
        $formId = intval($_REQUEST['form_id']);

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        $feedId = intval($_REQUEST['feed_id']);

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->where('meta_key', '_pdf_feeds')
            ->first();

        $settings = json_decode($feed->value, true);
        $templateName = Arr::get($settings, 'template_key');

        $templates = $this->getAvailableTemplates($form);

        if (!isset($templates[$templateName]) || !$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No template found!', 'fluentform-pdf')
            ], 423);
        }

        $template = $templates[$templateName];

        $class = $template['class'];
        if (!class_exists($class)) {
            wp_send_json_error([
                'message' => __('Sorry! No template Class found!', 'fluentform-pdf')
            ], 423);
        }
        $instance = new $class();

        $globalFields = $this->getGlobalFields();

        $globalFields['watermark_image'] = [
            'key' => 'watermark_image',
            'label' => 'Water Mark Image',
            'component' => 'image_widget'
        ];

        $globalFields['watermark_text'] = [
            'key' => 'watermark_text',
            'label' => 'Water Mark Text',
            'component' => 'text',
            'placeholder' => 'Watermark text'
        ];

        $globalFields['watermark_opacity'] = [
            'key' => 'watermark_opacity',
            'label' => 'Water Mark Opacity',
            'component' => 'number',
            'inline_tip' => 'Value should be between 1 to 100'
        ];
        $globalFields['watermark_img_behind'] = [
            'key' => 'watermark_img_behind',
            'label' => 'Water Mark Position',
            'component' => 'checkbox-single',
            'inline_tip' => 'Set as background'
        ];

        $globalFields['security_pass'] = [
            'key' => 'security_pass',
            'label' => 'PDF Password',
            'component' => 'text',
            'inline_tip' => 'If you want to set password please give on otherwise leave it empty'
        ];

        $settingsFields = $instance->getSettingsFields();

        $settingsFields[] = [
            'key' => 'allow_download',
            'label' => 'Allow Download',
            'tips' => 'Allow this feed to be downloaded on form submission. Only logged in users will be able to download.',
            'component' => 'radio_choice',
            'options' => [
                true => 'Yes',
                false => 'No'
            ]
        ];

        $settingsFields[] = [
            'key' => 'shortcode',
            'label' => 'Shortcode',
            'tips' => 'Use this shortcode on submission message to generate PDF link.',
            'component' => 'text',
            'readonly' => true
        ];

        $settings['settings']['shortcode'] = '{pdf.download_link.' . $feedId . '}';

        wp_send_json_success([
            'feed' => $settings,
            'settings_fields' => $settingsFields,
            'appearance_fields' => $globalFields
        ], 200);
    }

    public function saveFeedAjax()
    {
        $formId = intval($_REQUEST['form_id']);

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        $feedId = intval($_REQUEST['feed_id']);
        $feed = wp_unslash($_REQUEST['feed']);

        if (empty($feed['name'])) {
            wp_send_json_error([
                'message' => __('Feed name is required', 'fluentform-pdf')
            ], 423);
        }

        wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->update([
                'value' => wp_json_encode($feed)
            ]);

        wp_send_json_success([
            'message' => __('Settings successfully updated', 'fluentform-pdf')
        ], 200);
    }

    public function deleteFeedAjax()
    {
        $feedId = intval($_REQUEST['feed_id']);
        wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->where('meta_key', '_pdf_feeds')
            ->delete();

        wp_send_json_success([
            'message' => __('Feed successfully deleted', 'fluentform-pdf')
        ], 200);
    }

    /*
    * @return key => [ path, name]
    * To register a new template this filter must hook for path mapping
    * filter: fluentform_pdf_template_map
    */
    public function getAvailableTemplates($form)
    {
        $templates = [
            "general" => [
                'name' => 'General',
                'class' => '\FluentForm\App\Modules\PDF\Templates\GeneralTemplate',
                'key' => 'general',
                'preview' => FLUENTFORM_PDF_URL . 'assets/images/basic_template.png'
            ],
            "custom" => [
                'name' => 'PDF Builder',
                'class' => '\FluentForm\App\Modules\PDF\Templates\CustomTemplate',
                'key' => 'custom',
                'preview' => FLUENTFORM_PDF_URL . 'assets/images/basic_template.png'
            ]
        ];

        if ($form->has_payment) {
            $templates['invoice'] = [
                'name' => 'Invoice',
                'class' => '\FluentFormPdf\Classes\Templates\InvoiceTemplate',
                'key' => 'invoice',
                'preview' => FLUENTFORM_PDF_URL . 'assets/images/tabular.png'
            ];
        }

        $pdfTemplates = apply_filters_deprecated(
            'fluentform_pdf_templates',
            [
                $templates,
                $form
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/pdf_templates',
            'Use fluentform/pdf_templates instead of fluentform_pdf_templates.'
        );

        return apply_filters('fluentform/pdf_templates', $templates, $form);
    }


    /*
    * @return [ key name]
    * global pdf setting fields
    */
    public function getGlobalFields()
    {
        return [
            [
                'key' => 'paper_size',
                'label' => 'Paper size',
                'component' => 'dropdown',
                'tips' => 'All available templates are shown here, select a default template',
                'options' => AvailableOptions::getPaperSizes()
            ],
            [
                'key' => 'orientation',
                'label' => 'Orientation',
                'component' => 'dropdown',
                'options' => AvailableOptions::getOrientations()
            ],
            [
                'key' => 'font_family',
                'label' => 'Font Family',
                'component' => 'dropdown-group',
                'placeholder' => 'Select Font',
                'options' => AvailableOptions::getInstalledFonts()
            ],
            [
                'key' => 'font_size',
                'label' => 'Font size',
                'component' => 'number'
            ],
            [
                'key' => 'font_color',
                'label' => 'Font color',
                'component' => 'color_picker'
            ],
            [
                'key' => 'heading_color',
                'label' => 'Heading color',
                'tips' => 'The Color Form Headings',
                'component' => 'color_picker'
            ],
            [
                'key' => 'accent_color',
                'label' => 'Accent color',
                'tips' => 'The accent color is used for the borders, breaks etc.',
                'component' => 'color_picker'
            ],
            [
                'key' => 'language_direction',
                'label' => 'Language Direction',
                'tips' => 'Script like Arabic and Hebrew are written right to left. For Arabic/Hebrew please select RTL',
                'component' => 'radio_choice',
                'options' => [
                    'ltr' => 'LTR',
                    'rtl' => 'RTL'
                ]
            ]
        ];
    }

    public function pushPdfButtons($widgets, $data, $submission)
    {
        $formId = $submission->form->id;

        $feeds = $this->getFeeds($formId);
        if (!$feeds) {
            return $widgets;
        }
        $widgetData = [
            'title' => __('PDF Downloads', 'fluentform-pdf'),
            'type' => 'html_content'
        ];

        $fluent_forms_admin_nonce = wp_create_nonce('fluent_forms_admin_nonce');

        $contents = '<ul class="ff_list_items">';
        foreach ($feeds as $feed) {
            $contents .= '<li><a href="' . admin_url('admin-ajax.php?action=fluentform_pdf_admin_ajax_actions&fluent_forms_admin_nonce=' . $fluent_forms_admin_nonce . '&$fluent_forms_admin_nonce=&route=download_pdf&submission_id=' . $submission->id . '&id=' . $feed['id']) . '" target="_blank"><span style="font-size: 12px;" class="dashicons dashicons-arrow-down-alt"></span>' . $feed['name'] . '</a></li>';
        }
        $contents .= '</ul>';
        $widgetData['content'] = $contents;

        $widgets['pdf_feeds'] = $widgetData;
        return $widgets;
    }

    public function getPdfConfig($settings, $default)
    {
        return [
            'mode' => 'utf-8',
            'format' => Arr::get($settings, 'paper_size', Arr::get($default, 'paper_size')),
            'orientation' => Arr::get($settings, 'orientation', Arr::get($default, 'orientation')),
            // 'debug' => true //uncomment this debug on development
        ];
    }

    /*
    * when download button will press
    * Pdf rendering will control from here
    */
    public function getPdf()
    {
        $feedId = intval($_REQUEST['id']);
        $submissionId = intval($_REQUEST['submission_id']);
        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->where('meta_key', '_pdf_feeds')
            ->first();

        $settings = json_decode($feed->value, true);

        $settings['id'] = $feed->id;

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $feed->form_id)
            ->first();

        $templateName = Arr::get($settings, 'template_key');

        $templates = $this->getAvailableTemplates($form);

        if (!isset($templates[$templateName])) {
            die('Sorry! No template found');
        }

        $template = $templates[$templateName];

        $class = $template['class'];
        if (!class_exists($class)) {
            die('Sorry! No template class found');
        }

        $instance = new $class();

        $instance->viewPDF($submissionId, $settings);
    }

    public function maybePushToEmail($emailAttachments, $emailData, $formData, $entry, $form)
    {
        if (!Arr::get($emailData, 'pdf_attachments')) {
            return $emailAttachments;
        }

        $pdfFeedIds = Arr::get($emailData, 'pdf_attachments');

        $feeds = wpFluent()->table('fluentform_form_meta')
            ->whereIn('id', $pdfFeedIds)
            ->where('meta_key', '_pdf_feeds')
            ->where('form_id', $form->id)
            ->get();

        $templates = $this->getAvailableTemplates($form);

        foreach ($feeds as $feed) {
            $settings = json_decode($feed->value, true);
            $settings['id'] = $feed->id;
            $templateName = Arr::get($settings, 'template_key');

            if (!isset($templates[$templateName])) {
                continue;
            }
            $template = $templates[$templateName];
            $class = $template['class'];
            if (!class_exists($class)) {
                continue;
            }
            $instance = new $class();

            // we have to compute the file name to make it unique
            $fileName = $settings['name'] . '_' . $entry->id . '_' . $feed->id;

            //parse shortcodes in file name
            $fileName = ShortCodeParser::parse($fileName,  $entry->id, $formData);
            $fileName = sanitize_title($fileName, 'pdf-file', 'display');

            if (is_multisite()) {
                $fileName .= '_' . get_current_blog_id();
            }

            $file = $instance->outputPDF($entry->id, $settings, $fileName, false);
            if ($file) {
                $emailAttachments[] = $file;
            }
        }


        return $emailAttachments;
    }


    public function renderGlobalPage()
    {
        wp_enqueue_script('fluentform_pdf_admin', FLUENTFORM_PDF_URL . 'assets/js/admin.js', ['jquery'], FLUENTFORM_PDF_VERSION, true);
        $fontManager = new FontDownloader();
        $downloadableFiles = $fontManager->getDownloadableFonts();

        wp_localize_script('fluentform_pdf_admin', 'fluentform_pdf_admin', [
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
        $inheritStyle = true;
        $statuses = [];
        $globalSettingsUrl = '#';
        if (!$downloadableFiles) {
            $statuses = $this->getSystemStatuses();
          
    
            $globalSettingsUrl = admin_url('admin.php?page=fluent_forms_settings#pdf_settings');

            if (!get_option($this->optionKey)) {
                update_option($this->optionKey, Pdf::getGlobalConfig(), 'no');
            }
        }

        include FLUENTFORM_PDF_PATH . '/views/admin_screen.php';
    }

    public function downloadFonts()
    {
        Activator::maybeCreateFolderStructure();

        $fontManager = new FontDownloader();
        $downloadableFiles = $fontManager->getDownloadableFonts(3);

        $downloadedFiles = [];
        foreach ($downloadableFiles as $downloadableFile) {
            $fontName = $downloadableFile['name'];
            $res = $fontManager->download($fontName);
            $downloadedFiles[] = $fontName;
            if (is_wp_error($res)) {
                wp_send_json_error([
                    'message' => 'Font Download failed. Please reload and try again'
                ], 423);
            }
        }

        wp_send_json_success([
            'downloaded_files' => $downloadedFiles
        ], 200);
    }

    private function getSystemStatuses()
    {
        $mbString = extension_loaded('mbstring');
        $mbRegex = extension_loaded('mbstring') && function_exists('mb_regex_encoding');
        $gd = extension_loaded('gd');
        $dom = extension_loaded('dom') || class_exists('DOMDocument');
        $libXml = extension_loaded('libxml');
        $extensions = [
            'mbstring' => [
                'status' => $mbString,
                'label' => ($mbString) ? 'MBString is enabled' : 'The PHP Extension MB String could not be detected. Contact your web hosting provider to fix.'
            ],
            'mb_regex_encoding' => [
                'status' => $mbRegex,
                'label' => ($mbRegex) ? 'MBString Regex is enabled' : 'The PHP Extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix.'
            ],
            'gd' => [
                'status' => $gd,
                'label' => ($gd) ? 'GD Library is enabled' : 'The PHP Extension GD Image Library could not be detected. Contact your web hosting provider to fix.'
            ],
            'dom' => [
                'status' => $dom,
                'label' => ($dom) ? 'PHP Dom is enabled' : 'The PHP DOM Extension was not found. Contact your web hosting provider to fix.'
            ],
            'libXml' => [
                'status' => $libXml,
                'label' => ($libXml) ? 'LibXml is OK' : 'The PHP Extension libxml could not be detected. Contact your web hosting provider to fix'
            ]
        ];

        $overAllStatus = $mbString && $mbRegex && $gd && $dom && $libXml;

        return [
            'status' => $overAllStatus,
            'extensions' => $extensions
        ];
    }

    public function pushShortCode($shortCodes, $formID)
    {
        $feeds = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formID)
            ->where('meta_key', '_pdf_feeds')
            ->get();

        $feedShortCodes = [
            '{pdf.download_link}' => 'Submission PDF link'
        ];

        foreach ($feeds as $feed) {
            $feedSettings = json_decode($feed->value);
            $key = '{pdf.download_link.' . $feed->id . '}';
            $feedShortCodes[$key] = $feedSettings->name . ' feed PDF link';
        }

        $shortCodes[] = [
            'title' => __('PDF', 'fluentform-pdf'),
            'shortcodes' => $feedShortCodes
        ];

        return $shortCodes;
    }

    /**
     * @var string $shortCode
     * @var \FluentForm\App\Services\FormBuilder\ShortCodeParser $parser
     */
    public function createLink($shortCode, $parser)
    {
        $form = $parser->getForm();
        $entry = $parser->getEntry();

        // Currently we are assuming there is only one PDF Feed.
        // Hence the PDF Download Link will always be the first one.

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $form->id)
            ->where('meta_key', '_pdf_feeds')
            ->first();

        if ($feed) {
            $feedSettings = json_decode($feed->value, true);

            if (Arr::get($feedSettings, 'settings.allow_download')) {

                $nonce = wp_create_nonce('fluent_forms_admin_nonce');

                $url = admin_url('admin-ajax.php?action=fluentform_pdf_download&fluent_forms_admin_nonce=' . $nonce . '&submission_id=' . $entry->id . '&id=' . $feed->id);

                return $url;
            }
        }
    }

    public function download()
    {
        Acl::verifyNonce();

        if (!is_user_logged_in()) {
            $message = __('Sorry! You have to login first.', 'fluentform-pdf');

            wp_send_json_error([
                'message' => $message
            ], 422);
        }

        $hasPermission = Acl::hasPermission('fluentform_entries_viewer');

        if (!$hasPermission) {
            $submissionId = intval($_REQUEST['submission_id']);

            $submission = wpFluent()->table('fluentform_submissions')
                ->where('id', $submissionId)
                ->where('user_id', get_current_user_id())
                ->first();

            if (!$submission) {
                $message = __("You don't have permission to download the PDF.", 'fluentform-pdf');

                wp_send_json_error([
                    'message' => $message
                ], 422);
            }
        }

        return $this->getPdf();
    }

    public function createPublicLink($shortCode, $parser)
    {
        $feedID = str_replace('pdf.download_link.', '', $shortCode);

        if ($feedID) {
            $feed = wpFluent()->table('fluentform_form_meta')
                ->where('id', $feedID)
                ->first();

            if ($feed) {
                $entry = $parser->getEntry();
                $hashedEntryID = base64_encode(Protector::encrypt($entry->id));
                $hashedFeedID = base64_encode(Protector::encrypt($feedID));

                return admin_url('admin-ajax.php?action=fluentform_pdf_download_public&submission_id=' . $hashedEntryID . '&id=' . $hashedFeedID);
            }
        }
    }

    public function downloadPublic()
    {
        $feedId = intval(Protector::decrypt(base64_decode($_REQUEST['id'])));
        $submissionId = intval(Protector::decrypt(base64_decode($_REQUEST['submission_id'])));

        $_REQUEST['id'] = $feedId;
        $_REQUEST['submission_id'] = $submissionId;

        return $this->getPdf();
    }
}
