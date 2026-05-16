<?php

/**
 * Uninstall ChatBudgie
 *
 * This file is called when the plugin is uninstalled via the WordPress dashboard.
 * It cleans up all data created by the plugin, including database tables,
 * options, and scheduled tasks.
 */

// If uninstall not called from WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Clear scheduled tasks from Action Scheduler and WP-Cron
wp_clear_scheduled_hook('chatbudgie_daily_task');

if (function_exists('as_unschedule_all_actions')) {
    as_unschedule_all_actions(null, array(), 'chatbudgie');
} else {
    $actions_table = $wpdb->prefix . 'actionscheduler_actions';
    $groups_table = $wpdb->prefix . 'actionscheduler_groups';

    $group_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT group_id FROM {$groups_table} WHERE slug = %s",
            'chatbudgie'
        )
    );

    if ($group_id) {
        $wpdb->delete(
            $actions_table,
            array('group_id' => (int) $group_id),
            array('%d')
        );

        $wpdb->delete(
            $groups_table,
            array('group_id' => (int) $group_id),
            array('%d')
        );
    }
}

// 2. Drop custom database tables
$tables = array(
    $wpdb->prefix . 'chatbudgie_index_meta',
    $wpdb->prefix . 'chatbudgie_chunk_data'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// 3. Delete plugin options
$options = array(
    'chatbudgie_app_key',
    'chatbudgie_welcome_message',
    'chatbudgie_custom_icon',
    'chatbudgie_primary_color',
    'chatbudgie_secondary_color'
);

foreach ($options as $option) {
    delete_option($option);
}
