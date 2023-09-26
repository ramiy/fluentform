<?php

namespace FluentForm\App\Modules\Track;

use FluentForm\App\Http\Controllers\AdminNoticeController;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;


class TrackModule
{
    private $apiUrl = 'https://fluentforms.com/?wp_plug_opt=1';
    private $noticePrefKey = '_fluentform_notice_pref';
    private $delayTimeStamp = 172800; // 7 days
    

    public function register()
    {
        if ($this->isLocalhost()) {
            return;
        }
       $this->showInitialConsent();
        // Run weekly from daily cron using time stamp
        add_action('fluentform_do_email_report_scheduled_tasks',[$this, 'maybeSendTrackInfo']);
    }

    public function showInitialConsent()
    {
        $notice = $this->getInitialNotice();
        (new AdminNoticeController())->addNotice($notice);
    }
    
    public function maybeSendTrackInfo()
    {
        if (!$this->isAllowed() && !$this->timeMatched()) {
            return false;
        }
        
        try {
            wp_remote_post(
                $this->apiUrl, [
                    'body'       => $this->getLogData(),
                    'ssl_verify' => false,
                    'timeout'    => 30,
                ]
            );
        } catch (\Exception $exception) {
        }
    }

    private function getLogData()
    {
        global $wpdb;
        //WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $debug = 1;
        } else {
            $debug = 0;
        }

        //WPLANG
        if (defined('WPLANG') && WPLANG) {
            $lang = WPLANG;
        } else {
            $lang = 'default';
        }

        $ip_address = '';

        $server = wpFluentForm('request')->server();

        if (array_key_exists('SERVER_ADDR', $server)) {
            $ip_address = sanitize_text_field($server['SERVER_ADDR']);
        } elseif (array_key_exists('LOCAL_ADDR', $server)) {
            $ip_address = sanitize_text_field($server['LOCAL_ADDR']);
        }

        $host_name = gethostbyaddr($ip_address);

        $active_plugins = (array) get_option('active_plugins', []);
        $current_user = wp_get_current_user();
        if (!empty($current_user->user_email)) {
            $email = $current_user->user_email;
        } else {
            $email = get_option('admin_email');
        }
    
        $notificationPref = get_option($this->noticePrefKey);
        $email_subscribed = $subscribed_email = false;
        if(is_array($notificationPref) && isset($notificationPref['track_data_query'])){
            $email_subscribed = Arr::get($notificationPref,'subscribe_query.email_subscribed') && Arr::get($notificationPref,'subscribe_query.email_subscribed') =='yes';
            if($email_subscribed){
                $subscribed_email =  Arr::get($notificationPref,'subscribe_query.email');
            }
        }
        $integrations = (array)get_option('fluentform_global_modules_status');
        $active_integrations =  array_filter($integrations, function($value) {
            return $value === "yes";
        });
        return [
            'plugin'             => 'fluentform',
            'has_pro'            => defined('FLUENTFORMPRO'),
            'integrations_list'  => implode(',',array_keys($active_integrations)),
            'version'            => FLUENTFORM_VERSION,
            'wp_version'         => get_bloginfo('version'),
            'multisite_enabled'  => is_multisite(),
            'server_type'        => sanitize_text_field($server['SERVER_SOFTWARE']),
            'php_version'        => phpversion(),
            'mysql_version'      => $wpdb->db_version(),
            'wp_memory_limit'    => WP_MEMORY_LIMIT,
            'wp_debug_mode'      => $debug,
            'wp_lang'            => $lang,
            'wp_max_upload_size' => size_format(wp_max_upload_size()),
            'php_max_post_size'  => ini_get('post_max_size'),
            'hostname'           => $host_name,
            'smtp'               => ini_get('SMTP'),
            'smtp_port'          => ini_get('smtp_port'),
            'active_plugins'     => $active_plugins,
            'email'              => $email,
            'display_name'       => $current_user->display_name,
            'ip_address'         => $ip_address,
            'domain'             => site_url(),
            'email_subscribed'   => $email_subscribed,
            'subscribed_email'   => $subscribed_email,
            'theme_style'   => fluentform_get_active_theme_slug(),
        ];
    }

    public function getInitialNotice()
    {
        $message = __('We will collect a few server <span class="tooltip">data  <span class="tooltiptext"> %1$s</span></span> if you permit us. It will help us troubleshoot any inconveniences you may face while using Fluent Forms, and guide us to add better features according to your usage. NO FORM SUBMISSION DATA WILL BE COLLECTED.', 'fluentform');
    
        $content = sprintf(
            $message,
            'Server environment details (php, mysql, server, WordPress versions), Site language, Number of active plugins, Site name and url, Your name and email address. No sensitive data is tracked.',
        );
        return [
            'name'    => 'track_data_query',
            'title'   => __('Want to make Fluent Forms better with just one click?', 'fluentform'),
            'class' => 'fluent_info_notice',
            'message' => $content,
            'links'   => [
                [
                    'href'     => admin_url('admin.php?page=fluent_forms'),
                    'btn_text' => 'Yes, I want to help make Fluent Forms Better',
                    'btn_atts' => 'class="mr-1 el-button--success el-button--mini ff_track_yes" data-notice_type="approved" data-notice_name="track_data_query"',
                ],
                [
                    'href'     => admin_url('admin.php?page=fluent_forms'),
                    'btn_text' => 'No, Please don\'t collect errors or other data',
                    'btn_atts' => 'class="el-button--info el-button--soft el-button--mini  ff_nag_cross" data-notice_type="permanent" data-notice_name="track_data_query"',
                ],
            ],
        ];
    }

    private function isLocalhost()
    {
        return in_array(sanitize_text_field(wpFluentForm('request')->server('REMOTE_ADDR')), ['127.0.0.1', '::1']);
    }
    
    private function isAllowed()
    {
        return apply_filters('fluentform/allow_share_essential', get_option('_fluentform_share_essential', 'no') == 'yes');
    }
    
    private function timeMatched()
    {
        $prevValue = get_option('_fluentform_last_tracking_info_run');
        if (!$prevValue) {
            return true;
        }
        
        return (time() - $prevValue) > $this->delayTimeStamp;
    }
}
