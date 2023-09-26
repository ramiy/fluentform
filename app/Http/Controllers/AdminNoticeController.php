<?php
namespace FluentForm\App\Http\Controllers;

use FluentForm\App\Modules\Registerer\SubscribeQuery;
use FluentForm\App\Modules\Track\TrackModule;
use FluentForm\Framework\Helpers\ArrayHelper;

class AdminNoticeController extends Controller
{
    private static $notice = [];
    private static $noticeShowing = false;
    private $noticeDisabledTime = 60 * 60 * 24 * 15; // 15 days
    private $noticePrefKey = '_fluentform_notice_pref';
    private $pref = false;
    
    public function __construct()
    {
        parent::__construct();
        add_action('fluentform/global_menu', [$this, 'showNotice'], 99);
    
    }
    
    public function showNotice()
    {
        if (is_array(self::$notice) && !self::$noticeShowing) {
            $shuffledNoticeKey = array_rand(self::$notice);
            $selectedNotice = self::$notice[$shuffledNoticeKey];
            $this->renderNotice($selectedNotice, $selectedNotice['name']);
        }
    }
    
    public function addNotice($notice)
    {
        self::$notice[$notice['name']] = $notice;
    }
    
    public function noticeActions()
    {
        $noticeName = sanitize_text_field($this->request->get('notice_name'));
        $actionType = sanitize_text_field($this->request->get('action_type', 'permanent'));
        
        if ($noticeName == 'track_data_query' && $actionType == 'approved') {
            $notificationPref = $this->getNoticePref();
            $notificationPref[$noticeName] = [
                'permanent'        => 'yes',
            ];
            update_option($this->noticePrefKey, $notificationPref, 'no');
            update_option('_fluentform_share_essential','yes','no');
            $this->pref = $notificationPref;
            
            (new TrackModule())->maybeSendTrackInfo();
    
            return $this->sendSuccess([
                'status' => 'success',
                'message' => __('Thank you for your help!','fluentform')
    
            ]);
        } else {
            if ($noticeName == 'subscribe_query' && $actionType == 'approved') {
                $email = sanitize_email($this->request->get('email', ''));
                $name = sanitize_text_field($this->request->get('name', ''));
                if (!is_email($email)) {
                    return $this->sendError([
                        'status'  => 'error',
                        'message' => __('Please enter a valid email!','fluentform')
                    ]);
                }
                if (empty($name)) {
                    return $this->sendError([
                        'status'  => 'error',
                        'message' => __('Please enter your name!','fluentform')
                    ]);
                }
                $notificationPref = $this->getNoticePref();
                $notificationPref[$noticeName] = [
                    'permanent'        => 'yes',
                    'email_subscribed' => 'yes',
                    'email'            => $email,
                ];
                update_option($this->noticePrefKey, $notificationPref, 'no');
                $response  = (new SubscribeQuery())->sendSubscriptionInfo($name,$email);
    
                $this->pref = $notificationPref;
                return $this->sendSuccess($response);
            }
        }
        $this->disableNotice($noticeName, $actionType);
        return $this->sendSuccess(true);
    }
    
    public function renderNotice($notice, $notice_key = false)
    {
        if (!$this->hasPermission()) {
            return;
        }
        if ($notice_key) {
            if (!$this->shouldShowNotice($notice_key)) {
                return;
            }
        }
        self::$noticeShowing = true;
        wp_enqueue_style('fluentform_admin_notice', fluentformMix('css/admin_notices.css'));
        wp_enqueue_script('fluentform_admin_notice', fluentformMix('js/admin_notices.js'), array(
            'jquery'
        ), FLUENTFORM_VERSION);
        wpFluentForm('view')->render('admin.notices.info', array(
            'notice'        => $notice,
            'show_logo'     => false,
            'show_hide_nag' => isset($notice['show_hide_nag']),
            'logo_url'      => fluentformMix('img/fluent_icon.png')
        ));
    }
    
    private function disableNotice($notice_key, $type = 'temp')
    {
        $noticePref = $this->getNoticePref();
        $noticePref[$notice_key][$type] = time();
        update_option($this->noticePrefKey, $noticePref, 'no');
        $this->pref = $noticePref;
    }
    
    public function getNoticePref()
    {
        if (!$this->pref) {
            $this->pref = is_array(get_option($this->noticePrefKey)) ? get_option($this->noticePrefKey) : [];
        }
        return $this->pref;
    }
    
    public function shouldShowNotice($noticeName)
    {
        $notificationPref = $this->getNoticePref();
        if (!$notificationPref) {
            return true;
        }
        
        $maybeHidePermanently = isset($notificationPref[$noticeName]['permanent']);
        if ($maybeHidePermanently) {
            return false;
        }
        
        if ($this->haveTempHideNotice($noticeName)) {
            return false;
        }
        
        return true;
    }
    
    private function haveTempHideNotice($noticeName)
    {
        $tempHideNotices = get_option($this->noticePrefKey);
        if ($tempHideNotices && isset($tempHideNotices[$noticeName]['temp'])) {
            $tempDisabledTime = $tempHideNotices[$noticeName]['temp'];
            $difference = time() - intval($tempDisabledTime);
            
            if ($difference < $this->noticeDisabledTime) {
                return true;
            }
            return false;
        }
        return false;
    }
    
    private function hasPermission()
    {
        return current_user_can('fluentform_dashboard_access');
    }
}
