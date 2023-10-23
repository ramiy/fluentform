<?php

namespace FluentForm\App\Modules\Registerer;

use FluentForm\App\Models\Form;
use FluentForm\App\Helpers\Helper;
use FluentForm\App\Http\Controllers\AdminNoticeController;
use FluentForm\App\Models\SubmissionMeta;

class SubscribeQuery
{
    private $apiUrl = 'https://fluentforms.com/?wp_plug_opt=1';
    public function register()
    {
        if ($this->shouldRegister() && !$this->isLocalhost()) {
            $this->show();
        }
    }
    
    protected function shouldRegister()
    {
        $firstSubmission = SubmissionMeta::select('created_at')->first();
        $hasTwoWeeksOldSubmission = strtotime($firstSubmission->created_at) < strtotime('-14 days');
        
        if (Helper::isFluentAdminPage() && !wp_doing_ajax() && $hasTwoWeeksOldSubmission) {
            return true;
        }
        return false;
    }
    
    public function show()
    {
        $notice = new AdminNoticeController();
        $msg = $this->getMessage();
        $notice->addNotice($msg);
    }
    
    private function getMessage()
    {
        $current_user = wp_get_current_user();
        if (!empty($current_user->user_email)) {
            $email = $current_user->user_email;
        } else {
            $email = get_option('admin_email');
        }
        return [
            'name'    => 'subscribe_query',
            'title'   => __('Subscribe to Our Newsletter','fluentform'),
            'class' => 'fluent_info_notice',
            'message' => __('You can subscribe to our monthly newsletter where we will email you all about Fluent Forms Plugin with tips and advanced usage.','fluentform'),
            'inputs' =>[
                [
                    'type'=>'text',
                    'label'=>'Name',
                    'value'=> $current_user->user_firstname. ' '.$current_user->user_lastname,
                ],
                [
                    'type'=>'email',
                    'label'=>'Email',
                    'value'=> $email,
                ],
            ],
            'links' => [
                [
                    'href'     => '#',
                    'btn_text' => 'Subscribe',
                    'btn_atts' => 'class="mr-1 el-button--success el-button--mini ff_subscribe_yes" data-notice_type="approved" data-notice_name="subscribe_query"',
                ]
            ],
            'show_hide_nag' => true
        ];
    }
    private function isLocalhost()
    {
        return in_array(sanitize_text_field(wpFluentForm('request')->server('REMOTE_ADDR')), ['127.0.0.1', '::1']);
    }
    
    public function sendSubscriptionInfo($name,$email)
    {
        try {
            $response = wp_remote_post(
                $this->apiUrl,
                [
                    'ssl_verify' => false,
                    'timeout'    => 30,
                    'body'       => [
                        'name'    => $name,
                        'email'   => $email,
                        'domain'  => site_url(),
                        'has_pro' => defined('FLUENTFORMPRO'),
                    ],
                ]
            );
            
            if (is_wp_error($response)) {
                return [
                    'message' => __('Please try again later!','fluentform'),
                    'success' => false,
                ];
            }
            return json_decode(wp_remote_retrieve_body($response));
            
        } catch (\Exception $exception) {
            return [
                'message' => __('Please try again later!','fluentform'),
                'success' => false,
            ];
        }
    }
}
