<?php

/**
 * Template for the ChatBudgie admin settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

$index_status = $this->get_index_status();
$welcome_message = get_option('chatbudgie_welcome_message', __("I'm ChatBudgie, your AI assistant. How can I help you today?", 'chatbudgie'));
$primary_color = get_option('chatbudgie_primary_color', '#2f7bff');
$secondary_color = get_option('chatbudgie_secondary_color', '#dbe9ff');
$selected_avatar = get_option('chatbudgie_custom_icon', CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie-avatar.png');
$progress = max(0, min(100, (int) $index_status['progress']));
$total_files = max(0, (int) $index_status['scheduled_posts_count']);
$indexed_files = max(0, (int) $index_status['completed_posts_count']);
$remaining_files = max(0, $total_files - $indexed_files);

$status_labels = array(
    'pending' => __('Preparing index', 'chatbudgie'),
    'running' => __('Indexing content', 'chatbudgie'),
    'completed' => __('Index is ready', 'chatbudgie'),
    'failed' => __('Index needs attention', 'chatbudgie'),
);
$chatbudgie_status_label = isset($chatbudgie_status_labels[$chatbudgie_index_status['status']]) ? $chatbudgie_status_labels[$chatbudgie_index_status['status']] : __('Status unavailable', 'chatbudgie');

if ($chatbudgie_index_status['status'] === 'failed') {
    $chatbudgie_eta_label = __('Check logs', 'chatbudgie');
} elseif ($chatbudgie_index_status['status'] === 'completed') {
    $chatbudgie_eta_label = __('Ready now', 'chatbudgie');
} elseif ($chatbudgie_remaining_files > 0) {
    $chatbudgie_eta_seconds = $chatbudgie_remaining_files * 12;
    $chatbudgie_eta_minutes = floor($chatbudgie_eta_seconds / 60);
    $chatbudgie_eta_remainder = $chatbudgie_eta_seconds % 60;
    /* translators: 1: minutes, 2: seconds */
    $chatbudgie_eta_label = $chatbudgie_eta_minutes > 0 ? sprintf(__('%1$dm %2$02ds', 'chatbudgie'), $chatbudgie_eta_minutes, $chatbudgie_eta_remainder) : sprintf(__('%1$ds', 'chatbudgie'), $chatbudgie_eta_remainder);
} else {
    $chatbudgie_eta_label = __('Starting', 'chatbudgie');
}


$color_options = array(
    array('primary' => '#2f7bff', 'secondary' => '#dbe9ff', 'label' => __('Ocean Blue', 'chatbudgie')),
    array('primary' => '#7c3aed', 'secondary' => '#ede9fe', 'label' => __('Violet', 'chatbudgie')),
    array('primary' => '#14b8a6', 'secondary' => '#ccfbf1', 'label' => __('Teal', 'chatbudgie')),
    array('primary' => '#f59e0b', 'secondary' => '#fef3c7', 'label' => __('Amber', 'chatbudgie')),
    array('primary' => '#f43f5e', 'secondary' => '#ffe4e6', 'label' => __('Rose', 'chatbudgie')),
);

$avatar_options = array(
    CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie-avatar.png',
    CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie-avatar-2.png',
    CHATBUDGIE_PLUGIN_URL . 'assets/images/support-budgie-avatar.png',
    CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie-avatar-green.png',
    CHATBUDGIE_PLUGIN_URL . 'assets/images/support-budgie-avatar2.png'
);

$is_predefined_avatar = in_array($selected_avatar, $avatar_options);

$has_matching_palette = false;
foreach ($color_options as $color_option) {
    if (strcasecmp($color_option['primary'], $primary_color) === 0) {
        $has_matching_palette = true;
        break;
    }
}

?>
<script
    src="https://www.paypal.com/sdk/js?client-id=BAAHkTrcvIPFDyIlGzKjawQ8JAH_x8dUc4pcN6Mc9S8wKKZ8EJLydPQneqe6PtQDR3Ik4GYqiGewVF7iP8&components=hosted-buttons&disable-funding=venmo&currency=USD">
</script>
<div class="page page--settings">
    <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-header.php'; ?>

    <main class="settings" role="main">
        <section class="settings__hero" aria-labelledby="settings-title">
            <h1 id="settings-title" class="settings__title"><?php echo esc_html__('Settings', 'chatbudgie'); ?></h1>
            <p class="settings__sub"><?php echo esc_html__('Manage your chatbot settings and account.', 'chatbudgie'); ?></p>
        </section>

        <?php
        // $user_info is passed from the controller
        include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-account-summary.php';
        ?>

        <section class="settings-card" aria-labelledby="index-status-title">
            <div class="settings-card__header">
                <div>
                    <h2 id="index-status-title" class="settings-card__title"><?php echo esc_html__('knowledge Base Indexing Status', 'chatbudgie'); ?></h2>
                    <p class="settings-card__sub"><?php echo esc_html($status_label); ?></p>
                </div>
                <a class="cb-btn cb-btn--ghost" id="chatbudgie-rebuild-index" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=chatbudgie_rebuild_index'), 'chatbudgie_rebuild_index')); ?>">
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                            <path d="M21 3v6h-6"></path>
                        </svg>
                    </span>
                    <?php echo esc_html__('Rebuild Index', 'chatbudgie'); ?>
                </a>
            </div>

            <div class="indexing">
                <div class="indexing__progress">
                    <div class="indexing__track" aria-hidden="true">
                        <span class="indexing__bar" style="width: <?php echo esc_attr($chatbudgie_progress); ?>%;"></span>
                    </div>
                    <span class="indexing__percent"><?php echo esc_html($chatbudgie_progress); ?>%</span>
                </div>

                <div class="stats-grid">
                    <article class="stat-tile">
                        <span class="stat-tile__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <path d="M14 2v6h6"></path>
                                <path d="M8 13h8"></path>
                                <path d="M8 17h8"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="stat-tile__label"><?php echo esc_html__('Total Files', 'chatbudgie'); ?></p>
                            <p class="stat-tile__value"><?php echo esc_html(number_format_i18n($chatbudgie_total_files)); ?></p>
                        </div>
                    </article>

                    <article class="stat-tile">
                        <span class="stat-tile__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 3 4 7l8 4 8-4-8-4Z"></path>
                                <path d="m4 12 8 4 8-4"></path>
                                <path d="m4 17 8 4 8-4"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="stat-tile__label"><?php echo esc_html__('Indexed Files', 'chatbudgie'); ?></p>
                            <p class="stat-tile__value"><?php echo esc_html(number_format_i18n($chatbudgie_indexed_files)); ?></p>
                        </div>
                    </article>

                    <article class="stat-tile">
                        <span class="stat-tile__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 3"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="stat-tile__label"><?php echo esc_html__('Estimated Time', 'chatbudgie'); ?></p>
                            <p class="stat-tile__value"><?php echo esc_html($chatbudgie_eta_label); ?></p>
                        </div>
                    </article>
                </div>

                <?php if (!empty($chatbudgie_index_status['error'])) : ?>
                    <p class="settings-alert settings-alert--error"><?php echo esc_html($chatbudgie_index_status['error']); ?></p>
                <?php else : ?>
                    <p class="settings-note"><?php echo esc_html__("Indexing runs in the background. It builds the knowledge base index on the public content of your website. After the indexing is complete, ChatBudgie will be able to answer questions based on your content.", 'chatbudgie'); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <form method="post" action="options.php" class="settings-form">
            <?php settings_fields('chatbudgie_appearance_settings'); ?>
            <input type="hidden" name="chatbudgie_secondary_color" id="chatbudgie_secondary_color" value="<?php echo esc_attr($chatbudgie_secondary_color); ?>" />
            <input type="hidden" name="chatbudgie_custom_icon" id="chatbudgie_custom_icon" value="<?php echo esc_attr($chatbudgie_selected_avatar); ?>" />

            <section class="settings-card" aria-labelledby="appearance-title">
                <div class="settings-card__header">
                    <h2 id="appearance-title" class="settings-card__title"><?php echo esc_html__('Appearance', 'chatbudgie'); ?></h2>
                </div>

                <div class="settings-stack">
                    <div class="setting-row">
                        <div class="setting-row__intro">
                            <div class="setting-row__icon setting-row__icon--avatar">
                                <img id="chatbudgie-icon-preview" src="<?php echo esc_url($chatbudgie_selected_avatar); ?>" alt="" />
                            </div>
                            <div>
                                <h3 class="setting-row__title"><?php echo esc_html__('Chatbot Icon', 'chatbudgie'); ?></h3>
                                <p class="setting-row__text"><?php echo esc_html__('Choose the avatar that represents your chatbot.', 'chatbudgie'); ?></p>
                            </div>
                        </div>

                        <div class="setting-row__content">
                            <div class="avatar-options" role="radiogroup" aria-label="<?php echo esc_attr__('Avatar options', 'chatbudgie'); ?>">
                                <?php foreach ($chatbudgie_avatar_options as $chatbudgie_avatar_url) : ?>
                                    <?php $chatbudgie_is_selected = ($chatbudgie_avatar_url === $chatbudgie_selected_avatar); ?>
                                    <label class="avatar-choice<?php echo esc_attr($chatbudgie_is_selected ? ' is-active' : ''); ?>">
                                        <input
                                            type="radio"
                                            name="chatbudgie_avatar_choice"
                                            value="<?php echo esc_attr($chatbudgie_avatar_url); ?>"
                                            <?php checked($chatbudgie_is_selected); ?>
                                            class="screen-reader-text" />
                                        <img src="<?php echo esc_url($chatbudgie_avatar_url); ?>" alt="" />
                                    </label>
                                <?php endforeach; ?>

                                <label class="avatar-choice custom-choice<?php echo esc_attr(!$chatbudgie_is_predefined_avatar && $chatbudgie_selected_avatar !== '' ? ' is-active' : ''); ?>" <?php echo (!$chatbudgie_is_predefined_avatar && $chatbudgie_selected_avatar !== '' ? '' : 'style="display: none;"'); ?>>
                                    <input
                                        type="radio"
                                        name="chatbudgie_avatar_choice"
                                        value="<?php echo esc_attr($chatbudgie_selected_avatar); ?>"
                                        <?php checked(!$chatbudgie_is_predefined_avatar && $chatbudgie_selected_avatar !== ''); ?>
                                        class="screen-reader-text" />
                                    <img src="<?php echo esc_url($chatbudgie_selected_avatar); ?>" alt="" />
                                </label>
                                <button type="button" class="cb-btn cb-btn--ghost cb-btn--icon" id="chatbudgie-change-icon" aria-label="<?php echo esc_attr__('Change Icon', 'chatbudgie'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row__intro">
                            <div class="setting-row__icon setting-row__icon--swatch" style="--swatch-color: <?php echo esc_attr($chatbudgie_primary_color); ?>;"></div>
                            <div>
                                <h3 class="setting-row__title"><?php echo esc_html__('Primary Color', 'chatbudgie'); ?></h3>
                                <p class="setting-row__text"><?php echo esc_html__('Set the main color for your chatbot UI.', 'chatbudgie'); ?></p>
                            </div>
                        </div>

                        <div class="setting-row__content">
                            <div class="color-options" role="radiogroup" aria-label="<?php echo esc_attr__('Primary color options', 'chatbudgie'); ?>">
                                <?php foreach ($chatbudgie_color_options as $chatbudgie_color_option) : ?>
                                    <?php $chatbudgie_is_selected = strcasecmp($chatbudgie_color_option['primary'], $chatbudgie_primary_color) === 0; ?>
                                    <label class="color-choice<?php echo esc_attr($chatbudgie_is_selected ? ' is-active' : ''); ?>" title="<?php echo esc_attr($chatbudgie_color_option['label']); ?>">
                                        <input
                                            type="radio"
                                            name="chatbudgie_primary_color"
                                            value="<?php echo esc_attr($chatbudgie_color_option['primary']); ?>"
                                            data-secondary="<?php echo esc_attr($chatbudgie_color_option['secondary']); ?>"
                                            <?php checked($chatbudgie_is_selected); ?> />
                                        <span class="color-choice__swatch" style="--color-choice: <?php echo esc_attr($chatbudgie_color_option['primary']); ?>;"></span>
                                        <span class="screen-reader-text"><?php echo esc_html($chatbudgie_color_option['label']); ?></span>
                                    </label>
                                <?php endforeach; ?>

                                <div class="color-choice color-choice--picker<?php echo esc_attr(!$chatbudgie_has_matching_palette ? ' is-active' : ''); ?>">
                                    <input type="radio" name="chatbudgie_primary_color" value="<?php echo esc_attr($chatbudgie_primary_color); ?>" data-secondary="<?php echo esc_attr($chatbudgie_secondary_color); ?>" <?php checked(!$chatbudgie_has_matching_palette); ?> class="screen-reader-text" />
                                    <input type="text" id="chatbudgie-custom-color-picker" value="<?php echo esc_attr($chatbudgie_primary_color); ?>" class="chatbudgie-color-picker" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row__intro">
                            <div class="setting-row__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="setting-row__title"><?php echo esc_html__('Welcome Message', 'chatbudgie'); ?></h3>
                                <p class="setting-row__text"><?php echo esc_html__('Set the default greeting message for your visitors.', 'chatbudgie'); ?></p>
                            </div>
                        </div>

                        <div class="setting-row__content">
                            <label class="field field--textarea">
                                <textarea
                                    name="chatbudgie_welcome_message"
                                    id="chatbudgie_welcome_message"
                                    class="field__input field__input--textarea"
                                    rows="4"
                                    maxlength="500"><?php echo esc_textarea($chatbudgie_welcome_message); ?></textarea>
                                <span class="field__counter"><span id="chatbudgie-message-count"><?php echo esc_html(strlen($chatbudgie_welcome_message)); ?></span> / 500</span>
                            </label>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-row__intro"></div>
                        <div class="setting-row__content settings-form__footer">
                            <button type="submit" class="cb-btn cb-btn--primary" id="chatbudgie-save-appearance" disabled>
                                <?php echo esc_html__('Save Settings', 'chatbudgie'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </form>


        <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-support-footer.php'; ?>
    </main>
</div>
