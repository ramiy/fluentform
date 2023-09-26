const ffNoticeApp = {

    initNagButton() {
        let $btn = jQuery('.ff_nag_cross');
        $btn.on('click', function (e) {
            e.preventDefault();
            let noticeName = jQuery(this).attr('data-notice_name');
            let noticeType = jQuery(this).attr('data-notice_type');

            jQuery('#ff_notice_' + noticeName).remove();

            const url = FluentFormsGlobal.$rest.route('noticeAction')
            FluentFormsGlobal.$rest.post(url,{
                notice_name: noticeName,
                action_type: noticeType
            })
                .then(function (response) {
                    console.log(response);
                })
                .catch(function (error) {
                    console.log(error);
                });
        });

    },

    initSubscription() {
        let $btn = jQuery('.ff_subscribe_yes');
        $btn.on('click', function (e) {
            e.preventDefault();
            $btn.addClass('is_disabled')
            let noticeName = jQuery(this).attr('data-notice_name');
            let noticeType = jQuery(this).attr('data-notice_type');

            const email = $btn.closest(".ff_notice_buttons").find("input[type='email']").val();
            const name = $btn.closest(".ff_notice_buttons").find("input[type='text']").val();

            const url = FluentFormsGlobal.$rest.route('noticeAction');
            FluentFormsGlobal.$rest.post(url, {
                notice_name: noticeName,
                email : email,
                name : name,
                action_type: noticeType
            })
                .then( (response) =>{
                    $btn.removeClass('is_disabled')
                    if (response && response.success == true){
                        jQuery('#ff_notice_' + noticeName + ' .ff_notice_container').remove();
                    }
                    const $ffNoticeResponse = jQuery('#ff_notice_' + noticeName + ' .ff_notice_response');
                    $ffNoticeResponse.html(response.message);
                    $ffNoticeResponse.show();

                })
                .catch(function (error) {
                    console.log(error);
                });
        });

    },

    initTrackYes() {
        let $btn = jQuery('.ff_track_yes');
        $btn.on('click', function (e) {
            e.preventDefault();
            let noticeName = jQuery(this).attr('data-notice_name');
            let noticeType = jQuery(this).attr('data-notice_type');

            jQuery(this).attr('disabled', true);

            const url = FluentFormsGlobal.$rest.route('noticeAction');
            FluentFormsGlobal.$rest.post(url, {
                notice_name: noticeName,
                email: false,
                share_essentials: true,
                action_type: noticeType
            })
                .then(function (response) {
                    if (response.status == 'success'){
                        jQuery('#ff_notice_' + noticeName + ' .ff_notice_container').remove();
                        var $ffNoticeResponse = jQuery('#ff_notice_' + noticeName + ' .ff_notice_response');
                        $ffNoticeResponse.html(response.message);
                    }

                })
                .catch(function (error) {
                    console.log(error);
                });
        });

    },

    initSmtpInstall() {
        let $btn = jQuery('.intstall_fluentsmtp');
        $btn.on('click', function (e) {
            e.preventDefault();
            jQuery(this).attr('disabled', true);
            jQuery('.ff_addon_installing').show();
            FluentFormsGlobal.$post({
                action: 'fluentform_install_fluentsmtp'
            })
                .then(function (response) {
                    $btn.text('Please wait....');
                    if(response.is_installed && response.config_url) {
                        window.location.href = response.config_url;
                    } else if(response.is_installed) {
                        location.reload();
                    } else {
                        alert('something is wrong when installing the plugin. Please install FluentSMTP manually.')
                    }
                    console.log(response);
                })
                .fail(function (error) {
                    let message = 'something is wrong when installing the plugin. Please install FluentSMTP manually.';
                    if(error.responseJSON && error.responseJSON.message) {
                        message = error.responseJSON.message;
                    }
                    alert(message);
                    console.log(error);
                })
                .always(() => {
                    jQuery(this).attr('disabled', false);
                    jQuery('.ff_addon_installing').hide();
                });
        });
    },
    handleReviewQuery() {
        let $btn = jQuery('.ff_review_now');
        $btn.on('click', function (e) {
            e.preventDefault();
            jQuery(this).attr('disabled', true);
            let noticeName = jQuery(this).attr('data-notice_name');

            const route = FluentFormsGlobal.$rest.route('noticeAction')
            FluentFormsGlobal.$rest.post(route, {
                notice_name: noticeName,
            })
                .then(function (response) {
                    if (response) {
                        $btn.html('Thank You! We Really appreciate it.').fadeIn();
                        setTimeout(function () {
                            const url = $btn.attr('href');
                            jQuery('#ff_notice_' + noticeName).remove();
                            window.open(url, '_blank');
                        }, 1000)
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });

        });
    },

    initReady() {
        jQuery(document).ready(() => {
            this.initNagButton();
            this.initTrackYes();
            this.initSubscription();
            this.initSmtpInstall();
            this.handleReviewQuery();
        });
    }
};

ffNoticeApp.initReady();
