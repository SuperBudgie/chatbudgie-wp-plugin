<?php
/**
 * Template for the ChatBudgie frontend chat widget
 */

if (!defined('ABSPATH')) {
    exit;
}

$icon_type = get_option('chatbudgie_icon_type', 'default');
$custom_icon = get_option('chatbudgie_custom_icon', '');
?>
<div id="chatbudgie-widget" class="chatbudgie-widget">
    <div class="chatbudgie-toggle">
        <?php $this->render_icon($icon_type, $custom_icon, 'toggle'); ?>
    </div>
    <div class="chatbudgie-container">
        <div class="chatbudgie-header">
            <div class="chatbudgie-header-icon">
                <?php $this->render_icon($icon_type, $custom_icon, 'header'); ?>
            </div>
            <h3><?php echo esc_html__('ChatBudgie', 'chatbudgie'); ?></h3>
            <button class="chatbudgie-close">&times;</button>
        </div>
        <div class="chatbudgie-messages"></div>
        <div class="chatbudgie-input-area">
            <input type="text" class="chatbudgie-input" placeholder="<?php echo esc_attr__('Please enter your question...', 'chatbudgie'); ?>">
            <button class="chatbudgie-send"><?php echo esc_html__('Send', 'chatbudgie'); ?></button>
        </div>
    </div>
</div>
