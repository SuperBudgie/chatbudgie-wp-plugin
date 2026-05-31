<?php
/**
 * Uninstall ChatBudgie
 *
 * This file is called when the plugin is uninstalled via the WordPress dashboard.
 * It cleans up all data created by the plugin, including database tables,
 * options, and scheduled tasks.
 *
 * @package ChatBudgie
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/chatbudgie.php';

global $wpdb;

// 1. Clear scheduled tasks from Action Scheduler and WP-Cron.
wp_clear_scheduled_hook( 'superbudgie_chatbudgie_daily_task' );

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( null, array(), 'superbudgie-chatbudgie' );
} else {
	$actions_table = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
	$groups_table  = esc_sql( $wpdb->prefix . 'actionscheduler_groups' );

	$group_id = $wpdb->get_var(
		$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from $wpdb->prefix and escaped above.
			"SELECT group_id FROM {$groups_table} WHERE slug = %s",
			'superbudgie-chatbudgie'
		)
	);

	if ( $group_id ) {
		$wpdb->delete(
			$actions_table,
			array( 'group_id' => (int) $group_id ),
			array( '%d' )
		);

		$wpdb->delete(
			$groups_table,
			array( 'group_id' => (int) $group_id ),
			array( '%d' )
		);
	}
}

// 2. Drop custom database tables.
$tables = array(
	$wpdb->prefix . 'superbudgie_chatbudgie_index_meta',
	$wpdb->prefix . 'superbudgie_chatbudgie_chunk_data',
);

foreach ( $tables as $table ) {
	$table = esc_sql( $table );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is generated from $wpdb->prefix and escaped above.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// 3. Delete plugin options.
$options = array(
	'superbudgie_chatbudgie_app_key',
	'superbudgie_chatbudgie_welcome_message',
	'superbudgie_chatbudgie_custom_icon',
	'superbudgie_chatbudgie_primary_color',
	'superbudgie_chatbudgie_secondary_color',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// 4. Delete vector index files from the shared data directory.
SuperBudgie_ChatBudgie::delete_index_data();
