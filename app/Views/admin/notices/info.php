<div id="ff_notice_<?php echo esc_attr($notice['name']); ?>" class="update-nag fluentform-admin-notice <?php  echo isset($notice['class'])?esc_attr($notice['class']) : '' ; ?>">
    <?php if($show_logo): ?>
        <div class="ff_logo_holder">
            <img alt="Fluent Forms Logo" src="<?php echo esc_url($logo_url); ?>" />
        </div>
    <?php endif; ?>
    <div class="ff_notice_container">
        <?php if($show_hide_nag): ?>
            <div class="ff_temp_hide_nag"><span data-notice_type="permanent" data-notice_name="<?php echo esc_attr($notice['name']); ?>" title="Hide this Notification" class="dashicons dashicons-dismiss ff_nag_cross nag_cross_btn"></span></div>
        <?php endif; ?>
        
        <h3><?php echo esc_html($notice['title']); ?></h3>
        <p><?php echo wp_kses_post($notice['message']); ?></p>
        
        <div class="ff_notice_buttons">
            <?php if (isset($notice['inputs'])) {
                foreach ($notice['inputs'] as $input) {
                    echo "<label for='{$input['label']}'>{$input['label']} </label><input type='{$input['type']}' value='{$input['value']}' placeholder='{$input['label']}'></input> ";
                }
            } ?>
            <?php foreach ($notice['links'] as $link): ?>
                <a <?php echo wp_kses_post($link['btn_atts']); ?> href="<?php echo esc_url($link['href']); ?>"><?php echo esc_html($link['btn_text']); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="ff_notice_response"></div>
</div>
