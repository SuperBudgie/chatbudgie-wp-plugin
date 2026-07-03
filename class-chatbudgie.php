<?php
/**
 * Main controller class for ChatBudgie.
 *
 * @package ChatBudgie
 */

namespace SuperBudgie\ChatBudgie;

use DateTime;
use Exception;
use SuperBudgie\ChatBudgie\Vektor\Core\Config;
use SuperBudgie\ChatBudgie\Vektor\Services\Indexer;
use SuperBudgie\ChatBudgie\Vektor\Services\Searcher;
use SuperBudgie\ChatBudgie\Vektor\Services\Optimizer;
use WP_Error;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main controller class for ChatBudgie.
 *
 * This class handles the initialization of the plugin, sets up WordPress hooks,
 * manages vector indexing, and provides the main entry point for chat functionality.
 */
class ChatBudgie {
	public const EMBEDDING_DIMENSION      = 1536;
	public const EMBEDDING_API            = CHATBUDGIE_BASE_URL . 'api/rag/embedding/v1';
	public const CHAT_API                 = CHATBUDGIE_BASE_URL . 'api/rag/chat';
	public const USER_INFO_API            = CHATBUDGIE_BASE_URL . 'api/user/info';
	public const REFRESH_APP_KEY_API      = CHATBUDGIE_BASE_URL . 'api/app/refreshkey';
	public const TOKEN_USAGE_API          = CHATBUDGIE_BASE_URL . 'api/user/tokenusage';
	public const USER_ORDERS_API          = CHATBUDGIE_BASE_URL . 'api/user/orders';
	public const CREATE_PAYPAL_ORDER_API  = CHATBUDGIE_BASE_URL . 'api/payment/paypal/create';
	public const CAPTURE_PAYPAL_ORDER_API = CHATBUDGIE_BASE_URL . 'api/payment/paypal/capture';
	public const QUERY_EMBEDDING_API      = CHATBUDGIE_BASE_URL . 'api/rag/embedding/query/v1';
	public const SSL_VERIFY               = false;
	public const INDEX_META_TABLE         = 'chatbudgie_index_meta';
	public const CHUNK_TABLE              = 'chatbudgie_chunk_data';

	/**
	 * The singleton instance of ChatBudgie.
	 *
	 * @var ChatBudgie|null
	 */
	private static ?ChatBudgie $instance = null;

	/**
	 * The Indexer instance.
	 *
	 * @var Indexer
	 */
	private Indexer $indexer;

	/**
	 * The Searcher instance.
	 *
	 * @var Searcher
	 */
	private Searcher $searcher;

	/**
	 * Get the singleton instance of ChatBudgie
	 *
	 * @return ChatBudgie The singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to initialize plugin hooks and configuration
	 * Sets up WordPress actions, filters, and registration hooks
	 */
	private function __construct() {
		// Initialize the vector index dimension and data directory.
		Config::setDimensions( self::EMBEDDING_DIMENSION );
		$data_dir = self::get_data_dir();

		if ( ! empty( $data_dir ) ) {
			$version_file = trailingslashit( $data_dir ) . 'version';
			if ( file_exists( $version_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$stored_version = trim( file_get_contents( $version_file ) );
				$stored_major   = explode( '.', $stored_version )[0];
				$current_major  = explode( '.', CHATBUDGIE_VERSION )[0];

				if ( $stored_major !== $current_major ) {
					// Major version mismatch - clear data.
					self::delete_index_data();
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "ChatBudgie: Major version mismatch ($stored_version vs " . CHATBUDGIE_VERSION . '). Cleared data directory.' );
				}
			}
		}

		if ( ! file_exists( $data_dir ) ) {
			if ( ! wp_mkdir_p( $data_dir ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'ChatBudgie: Failed to create data directory at ' . $data_dir );
			}
		}

		// Ensure version file exists in data directory.
		if ( ! empty( $data_dir ) && is_dir( $data_dir ) ) {
			$version_file = trailingslashit( $data_dir ) . 'version';
			if ( ! file_exists( $version_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $version_file, CHATBUDGIE_VERSION );
			}
		}

		Config::setDataDir( $data_dir );

		// Initialize Indexer and Searcher.
		$this->indexer  = new Indexer();
		$this->searcher = new Searcher();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_chat_widget' ) );
		add_action( 'wp_ajax_chatbudgie_search_index', array( $this, 'handle_search_index' ) );
		add_action( 'wp_ajax_nopriv_chatbudgie_search_index', array( $this, 'handle_search_index' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( CHATBUDGIE_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );
		// add_action('admin_notices', array($this, 'show_index_status_notice'));.
		add_action( 'admin_post_chatbudgie_rebuild_index', array( $this, 'handle_manual_rebuild_index' ) );

		// PayPal integration handlers.
		add_action( 'wp_ajax_chatbudgie_create_paypal_order', array( $this, 'handle_create_paypal_order' ) );
		add_action( 'wp_ajax_chatbudgie_capture_paypal_order', array( $this, 'handle_capture_paypal_order' ) );

		// Add login callback action.
		add_action( 'admin_post_chatbudgie_login_callback', array( $this, 'handle_login_callback' ) );
		add_action( 'admin_post_nopriv_chatbudgie_login_callback', array( $this, 'handle_login_callback' ) );

		// Add cron job hook.
		add_action( 'chatbudgie_daily_task', array( $this, 'daily_task' ) );

		// Add Action Scheduler hooks for indexing.
		add_action( 'chatbudgie_build_index', array( $this, 'execute_build_index' ) );
		add_action( 'chatbudgie_index_single_post', array( $this, 'execute_index_single_post' ), 10, 1 );

		// Hook into post save to schedule/remove indexing.
		add_action( 'save_post', array( $this, 'handle_post_save' ), 10, 2 );

		// Hook into post deletion and status changes to remove indexes.
		add_action( 'before_delete_post', array( $this, 'handle_post_delete' ) );
	}

	/**
	 * Load bundled translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$domain          = 'chatbudgie';
		$plugin_rel_path = trailingslashit( plugin_basename( untrailingslashit( CHATBUDGIE_PLUGIN_DIR ) ) ) . 'languages';

		load_plugin_textdomain( $domain, false, $plugin_rel_path );

		$locale       = determine_locale();
		$mofile_paths = array();

		if ( defined( 'WP_LANG_DIR' ) ) {
			$mofile_paths[] = trailingslashit( constant( 'WP_LANG_DIR' ) ) . 'plugins/' . $domain . '-' . $locale . '.mo';
		}

		$mofile_paths[] = trailingslashit( CHATBUDGIE_PLUGIN_DIR ) . 'languages/' . $domain . '-' . $locale . '.mo';

		foreach ( $mofile_paths as $mofile_path ) {
			if ( is_readable( $mofile_path ) ) {
				if ( is_textdomain_loaded( $domain ) ) {
					unload_textdomain( $domain, true );
				}

				load_textdomain( $domain, $mofile_path );
				return;
			}
		}
	}

	/**
	 * Get the writable storage directory for vector index files.
	 *
	 * @return string
	 */
	public static function get_data_dir() {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['basedir'] ) ) {
			return trailingslashit( $upload_dir['basedir'] ) . CHATBUDGIE_APP_NAME;
		}

		return '';
	}

	/**
	 * Check whether a compatible Action Scheduler API is available.
	 *
	 * @return bool
	 */
	private static function is_action_scheduler_available() {
		return function_exists( 'as_enqueue_async_action' )
			&& function_exists( 'as_schedule_recurring_action' )
			&& function_exists( 'as_unschedule_all_actions' )
			&& function_exists( 'as_get_scheduled_actions' );
	}

	/**
	 * Create the index meta table for tracking post index times
	 * Creates a custom WordPress table to store when each post was last indexed
	 *
	 * @return void
	 */
	private function create_index_meta_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::INDEX_META_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            last_indexed datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id),
            KEY last_indexed (last_indexed)
        ) {$charset_collate};";

		if ( defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		dbDelta( $sql );

		if ( $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to create index meta table: ' . $wpdb->last_error );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Index meta table created successfully' );
		}
	}

	/**
	 * Create the chunk data table for storing chunk text for each post
	 *
	 * @return void
	 */
	private function create_chunk_data_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::CHUNK_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            chunk_id int(11) NOT NULL,
            chunk_text longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_chunk (post_id, chunk_id),
            KEY post_id (post_id)
        ) {$charset_collate};";

		if ( defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		dbDelta( $sql );

		if ( $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to create chunk data table: ' . $wpdb->last_error );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Chunk data table created successfully' );
		}
	}

	/**
	 * Update the index time for a specific post.
	 * Records when a post was last indexed in the meta table.
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return bool True on success, false on failure.
	 */
	public function update_post_index_time( $post_id ) {
		global $wpdb;

		$table_name   = $wpdb->prefix . self::INDEX_META_TABLE;
		$current_time = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->replace(
			$table_name,
			array(
				'post_id'      => $post_id,
				'last_indexed' => $current_time,
			),
			array( '%d', '%s' )
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to update index time for post ' . $post_id );
			return false;
		}

		return true;
	}

	/**
	 * Get the last index time for a specific post
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return string|null The last indexed datetime or null if not found
	 */
	public function get_post_index_time( $post_id ) {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . self::INDEX_META_TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe and escaped.
				"SELECT last_indexed FROM {$table_name} WHERE post_id = %d",
				$post_id
			)
		);

		return $result;
	}

	/**
	 * Delete index time record for a specific post
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return bool True on success, false on failure
	 */
	public function delete_post_index_time( $post_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::INDEX_META_TABLE;

		$result = $wpdb->delete(
			$table_name,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Plugin activation handler
	 * Sets up scheduled tasks and schedules the initial WordPress content index build
	 */
	public function activate() {
		// Create index meta table.
		$this->create_index_meta_table();

		// Create chunk data table.
		$this->create_chunk_data_table();

		// Clear existing cron jobs (legacy WP-Cron and Action Scheduler).
		wp_clear_scheduled_hook( 'chatbudgie_daily_task' );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'chatbudgie_daily_task', array(), 'chatbudgie' );
		}

		// Schedule daily task at 3:00 AM local time using Action Scheduler.
		if ( function_exists( 'as_schedule_recurring_action' ) ) {
			try {
				$date      = new DateTime( '03:00:00', wp_timezone() );
				$timestamp = $date->getTimestamp();

				// If 3:00 AM has already passed today, schedule for tomorrow.
				if ( $timestamp <= time() ) {
					$timestamp += 86400; // 24 hours in seconds.
				}
				as_schedule_recurring_action( $timestamp, 86400, 'chatbudgie_daily_task', array(), 'chatbudgie' );
			} catch ( Exception $e ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'ChatBudgie: Failed to schedule daily task: ' . $e->getMessage() );
			}
		}

		// Schedule immediate index build via Action Scheduler.
		$this->schedule_index_build();
	}

	/**
	 * Schedule WordPress index build via Action Scheduler
	 * Schedules an immediate background task to build the full index
	 * Deletes existing build and single post indexing actions from database before starting fresh
	 *
	 * @return int The action ID
	 */
	public function schedule_index_build() {
		global $wpdb;

		if ( ! self::is_action_scheduler_available() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Cannot schedule index build - Action Scheduler is unavailable.' );
			return null;
		}

		$app_key = get_option( CHATBUDGIE_APP_KEY_OPTION, '' );
		if ( empty( $app_key ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Cannot schedule index build - You should login ChatBudgie account first.' );
			return;
		}

		// Delete all existing build and single post indexing actions via direct SQL for efficiency.
		$table_name  = esc_sql( $wpdb->prefix . 'actionscheduler_actions' );
		$group_table = esc_sql( $wpdb->prefix . 'actionscheduler_groups' );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped and cannot be passed via placeholders.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE a FROM {$table_name} a
                 INNER JOIN {$group_table} g ON a.group_id = g.group_id
                 WHERE g.slug = %s AND a.hook IN (%s, %s)",
				'chatbudgie',
				'chatbudgie_build_index',
				'chatbudgie_index_single_post'
			)
		);
		// phpcs:enable

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Deleted existing indexing actions from database before scheduling new build' );

		// Schedule fresh action.
		$action_id = as_enqueue_async_action( 'chatbudgie_build_index', array(), 'chatbudgie' );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Scheduled fresh index build with action ID: ' . $action_id );

		return $action_id;
	}

	/**
	 * Get the current indexing status
	 *
	 * @return array Status information
	 */
	public function get_index_status() {
		$error = '';

		if ( ! self::is_action_scheduler_available() || ! class_exists( 'ActionScheduler_Store', false ) ) {
			return array(
				'status'                => 'failed',
				'scheduled_posts_count' => 0,
				'completed_posts_count' => 0,
				'progress'              => 0,
				'error'                 => __( 'Action Scheduler is unavailable. Please make sure no other plugin is loading an incompatible copy.', 'chatbudgie' ),
			);
		}

		$store = \ActionScheduler_Store::instance();

		// Get total count of all post indexing tasks (no status filter).
		$scheduled_count = $store->query_actions(
			array(
				'hook' => 'chatbudgie_index_single_post',
			),
			'count'
		);

		// Get count of completed tasks.
		$completed_count = $store->query_actions(
			array(
				'hook'   => 'chatbudgie_index_single_post',
				'status' => \ActionScheduler_Store::STATUS_COMPLETE,
			),
			'count'
		);

		// Check build index task status.
		$pending_build_actions = as_get_scheduled_actions(
			array(
				'hook'   => 'chatbudgie_build_index',
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			)
		);

		$running_build_actions = as_get_scheduled_actions(
			array(
				'hook'   => 'chatbudgie_build_index',
				'status' => \ActionScheduler_Store::STATUS_RUNNING,
			)
		);

		$failed_build_actions = as_get_scheduled_actions(
			array(
				'hook'   => 'chatbudgie_build_index',
				'status' => \ActionScheduler_Store::STATUS_FAILED,
			)
		);

		// Determine status based on rules:.
		// 1. If build task failed → 'failed'.
		// 2. If build task is pending/running → 'pending'.
		// 3. If build task done but post tasks still running → 'running'.
		// 4. If all post tasks complete → 'completed'.
		if ( ! empty( $failed_build_actions ) ) {
			$status = 'failed';
			if ( isset( $failed_build_actions[0] ) ) {
				$error = $failed_build_actions[0]->get_message();
			}
		} elseif ( ! empty( $pending_build_actions ) || ! empty( $running_build_actions ) ) {
			$status = 'pending';
		} elseif ( $scheduled_count > 0 && $completed_count < $scheduled_count ) {
			$status = 'running';
		} elseif ( $scheduled_count > 0 && $completed_count >= $scheduled_count ) {
			$status = 'completed';
		} else {
			$status = 'completed';
		}

		// Calculate progress (based on completed vs total).
		$progress = 0;
		if ( $scheduled_count > 0 ) {
			$progress = min( 100, round( ( $completed_count / $scheduled_count ) * 100 ) );
		} elseif ( 'completed' === $status ) {
			$progress = 100;
		}

		return array(
			'status'                => $status,
			'scheduled_posts_count' => $scheduled_count,
			'completed_posts_count' => $completed_count,
			'progress'              => $progress,
			'error'                 => $error,
		);
	}

	/**
	 * Schedule a single post index via Action Scheduler
	 * Checks if the post needs indexing before scheduling
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return int|null The action ID or null if skipped
	 */
	public function schedule_post_index( $post_id ) {
		if ( ! self::is_action_scheduler_available() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Cannot schedule post index - Action Scheduler is unavailable.' );
			return null;
		}

		// Check if post needs indexing.
		$post = get_post( $post_id );
		if ( ! $post ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Post not found for scheduling: ' . $post_id );
			return null;
		}

		// Skip if post is not published.
		if ( 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return null;
		}

		// Get post modified time.
		$post_modified = $post->post_modified_gmt;

		// Get last index time.
		$last_indexed = $this->get_post_index_time( $post_id );

		// Skip if post hasn't been modified since last index.
		if ( $last_indexed && strtotime( $post_modified ) <= strtotime( $last_indexed ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Skipping scheduling post ' . $post_id . ' - not modified since last index' );
			return null;
		}

		$action_id = as_enqueue_async_action( 'chatbudgie_index_single_post', array( $post_id ), 'chatbudgie' );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Scheduled post ' . $post_id . ' index with action ID: ' . $action_id );

		return $action_id;
	}

	/**
	 * Handle post save event to schedule or remove indexing
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function handle_post_save( $post_id, $post ) {
		// Skip autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Skip revisions.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only index posts and pages.
		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		// Post is published - schedule indexing.
		if ( 'publish' === $post->post_status ) {
			$this->schedule_post_index( $post_id );

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Post saved, scheduling index ' . $post_id );
		} else {
			// Post is not published - remove index if exists.
			$this->delete_post_vectors( $post_id );
			$this->delete_post_chunks( $post_id );
			$this->delete_post_index_time( $post_id );

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Deleted index for unpublished post ' . $post_id . ' (status: ' . $post->post_status . ')' );
		}
	}

	/**
	 * Handle post deletion to remove index
	 *
	 * @param int $post_id The post ID being deleted.
	 * @return void
	 */
	public function handle_post_delete( $post_id ) {
		// Delete vectors for this post.
		$this->delete_post_vectors( $post_id );

		// Delete chunk data.
		$this->delete_post_chunks( $post_id );

		// Delete index time record.
		$this->delete_post_index_time( $post_id );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Deleted index for deleted post ' . $post_id );
	}

	/**
	 * Delete all vector entries for a specific post
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return void
	 */
	private function delete_post_vectors( $post_id ) {
		global $wpdb;

		try {
			// Get chunk IDs from the chunk table for this post.
			$chunk_table = esc_sql( $wpdb->prefix . self::CHUNK_TABLE );
			$chunk_ids   = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe and escaped.
					"SELECT chunk_id FROM {$chunk_table} WHERE post_id = %d",
					$post_id
				)
			);

			if ( ! empty( $chunk_ids ) ) {
				// Delete vectors for each chunk.
				foreach ( $chunk_ids as $chunk_id ) {
					$vector_id = $post_id . '_' . $chunk_id;
					$this->indexer->delete( $vector_id );
				}
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'ChatBudgie: Deleted ' . count( $chunk_ids ) . ' vectors for post ' . $post_id );
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Error deleting vectors for post ' . $post_id . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Execute the build index action (called by Action Scheduler)
	 * Schedules all published posts for indexing via Action Scheduler
	 *
	 * @return void
	 * @throws Exception If scheduling fails.
	 */
	public function execute_build_index() {
		// Prevent PHP from timing out.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Action Scheduler executing build_wordpress_index' );

		try {
			$scheduled_count = 0;
			$skipped_count   = 0;

			// Get all published posts page by page.
			$paged          = 1;
			$posts_per_page = 10;

			do {
				$args = array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_page,
					'paged'          => $paged,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				);

				$query = new WP_Query( $args );

				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$post_id = get_the_ID();
						// Schedule each post indexing as a separate async task.
						$action_id = $this->schedule_post_index( $post_id );
						if ( $action_id ) {
							++$scheduled_count;
						} else {
							++$skipped_count;
						}
					}
					wp_reset_postdata();
				}

				++$paged;

			} while ( $query->have_posts() );

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie full index schedule task is completed. Post index tasks have been scheduled, indexing summary: ' . $scheduled_count . ' posts scheduled, ' . $skipped_count . ' posts skipped (already indexed)' );
		} catch ( Exception $e ) {
			$error_message = esc_html( $e->getMessage() );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie full index schedule task failed: ' . $error_message );
			// Re-throw exception so Action Scheduler knows this task failed.
			throw new Exception( esc_html( 'Full index schedule task failed: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Execute the single post index action (called by Action Scheduler)
	 * Indexes a single post by embedding its content and storing vectors
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return void
	 * @throws Exception If indexing fails.
	 */
	public function execute_index_single_post( $post_id ) {
		// Prevent PHP from timing out.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Action Scheduler executing index_post for post ' . $post_id );

		// Get post data.
		$post = get_post( $post_id );
		if ( ! $post ) {
			$safe_post_id = absint( $post_id );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Post not found: ' . $safe_post_id );
			throw new Exception( esc_html( 'Post not found: ' . $safe_post_id ) );
		}

		// Skip if post is not published.
		if ( 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Skipping post ' . $post_id . ' - not published or wrong post type' );
			return;
		}

		// Get post modified time.
		$post_modified = $post->post_modified_gmt;

		// Get last index time.
		$last_indexed = $this->get_post_index_time( $post_id );

		// Skip if post hasn't been modified since last index.
		if ( $last_indexed && strtotime( $post_modified ) <= strtotime( $last_indexed ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Skipping post ' . $post_id . ' - not modified since last index' );
			return;
		}

		try {
			$title   = wp_strip_all_tags( $post->post_title );
			$content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			$excerpt = wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ) );

			// Get embedding chunks from API.
			$chunks = $this->get_embedding( $title, $content, $excerpt );

			// Index each chunk.
			foreach ( $chunks as $chunk_index => $chunk ) {
				$vector_id = $post_id . '_' . $chunk_index;

				// Check if vector_id exists in index, delete first if it does.
				if ( $this->indexer->delete( $vector_id ) ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'Deleted existing vector: ' . $vector_id . ' before re-indexing' );
				}

				$this->indexer->insert( $vector_id, $chunk['embedding'] );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Indexed chunk: ' . $vector_id . ' (' . strlen( $chunk['content'] ) . ' chars)' );
			}

			// Save chunk text to database.
			$this->update_post_chunks( $post_id, $chunks );

			// Update index time for this post.
			$this->update_post_index_time( $post_id );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Indexed post ' . $post_id . ' - ' . $title . ' (' . count( $chunks ) . ' chunks)' );
		} catch ( Exception $e ) {
			$safe_post_id       = absint( $post_id );
			$safe_error_message = esc_html( $e->getMessage() );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to index post ' . $safe_post_id . ': ' . $safe_error_message );
			// Re-throw exception so Action Scheduler knows this task failed.
			throw new Exception( esc_html( 'Failed to index post ' . $safe_post_id . ': ' . $safe_error_message ) );
		}
	}

	/**
	 * Delete all chunks and their text for a specific post
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return void
	 */
	private function delete_post_chunks( $post_id ) {
		// Delete old chunks from chunk data table.
		global $wpdb;
		$chunk_table = $wpdb->prefix . self::CHUNK_TABLE;
		$wpdb->delete( $chunk_table, array( 'post_id' => $post_id ), array( '%d' ) );
	}

	/**
	 * Delete all index data for all posts
	 * Deletes vector data, truncates the index meta table and chunk data table
	 *
	 * @return void
	 */
	private function delete_all_index_data() {
		global $wpdb;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: Deleting all index data' );

		// Delete vector index data (files).
		self::delete_index_data();

		// Truncate index meta table.
		$index_meta_table = esc_sql( $wpdb->prefix . self::INDEX_META_TABLE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe and escaped.
		$wpdb->query( "TRUNCATE TABLE {$index_meta_table}" );

		// Truncate chunk data table.
		$chunk_table = esc_sql( $wpdb->prefix . self::CHUNK_TABLE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe and escaped.
		$wpdb->query( "TRUNCATE TABLE {$chunk_table}" );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie: All index data deleted' );
	}

	/**
	 * Save chunk text to the database for a specific post
	 *
	 * @param int   $post_id The WordPress post ID.
	 * @param array $chunks Array of chunks with 'content' key.
	 * @return void
	 */
	private function update_post_chunks( $post_id, $chunks ) {
		$this->delete_post_chunks( $post_id );

		global $wpdb;
		$chunk_table = $wpdb->prefix . self::CHUNK_TABLE;

		foreach ( $chunks as $chunk_index => $chunk ) {
			$wpdb->insert(
				$chunk_table,
				array(
					'post_id'    => $post_id,
					'chunk_id'   => $chunk_index,
					'chunk_text' => $chunk['content'] ?? '',
				),
				array( '%d', '%d', '%s' )
			);
		}
	}

	/**
	 * Get embedding vectors from the RAG embedding API
	 * Sends content to the embedding API and returns chunked embeddings with their text
	 *
	 * @param string $title The post title.
	 * @param string $content The post content.
	 * @param string $excerpt The post excerpt.
	 * @return array Array of chunks containing 'content' and 'embedding'
	 * @throws Exception If API request fails or returns invalid response.
	 */
	private function get_embedding( $title, $content, $excerpt ) {

		$body = array(
			'title'       => $title,
			'excerpt'     => $excerpt,
			'content'     => $content,
			'contentType' => 'text/html',
			'appName'     => CHATBUDGIE_APP_NAME,
		);

		$headers = array(
			'Content-Type' => 'application/json',
			'appKey'       => get_option( CHATBUDGIE_APP_KEY_OPTION, '' ),
			'Referer'      => site_url(),
		);

		$response = wp_remote_post(
			self::EMBEDDING_API,
			array(
				'headers'   => $headers,
				'body'      => wp_json_encode( $body ),
				'timeout'   => 60,
				'sslverify' => self::SSL_VERIFY,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			throw new Exception( esc_html( 'Embedding API request failed: ' . $error_message ) );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( isset( $data['code'] ) && 200 !== (int) $data['code'] ) {
			$error_msg = isset( $data['message'] ) ? $data['message'] : 'Unknown API error';
			throw new Exception( esc_html( 'API error: ' . $error_msg ) );
		}

		// Check the embedding dimension.
		if ( isset( $data['data'] ) && isset( $data['data']['embeddingDimension'] ) ) {
			$embedding_dimension = $data['data']['embeddingDimension'];
			if ( self::EMBEDDING_DIMENSION !== $embedding_dimension ) {
				/* translators: 1: API returned embedding dimension, 2: configured embedding dimension */
				$error_msg = sprintf(
					'Embedding dimension mismatch: API returned %d dimensions, but configured %d dimensions',
					$embedding_dimension,
					self::EMBEDDING_DIMENSION
				);
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'ChatBudgie: ' . $error_msg );
				throw new Exception( esc_html( $error_msg ) );
			}
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Warning - embedding dimension not returned by API, expected ' . self::EMBEDDING_DIMENSION );
		}

		// Extract embedding from the response.
		// Response structure: data.chunks[0].embedding.
		if ( ! isset( $data['data'] ) || ! isset( $data['data']['chunks'] ) ) {
			throw new Exception( 'Invalid response format: embedding not found' );
		}

		$chunks = $data['data']['chunks'];

		// Return the chunks array (contains content and embedding for each chunk).
		return $chunks;
	}

	/**
	 * Get chunk text by vector ID from the database
	 *
	 * @param string $vector_id The vector ID (e.g., '123_0').
	 * @return string The chunk text or empty string if not found
	 */
	private function get_chunk_text( $vector_id ) {
		global $wpdb;

		$chunk_table = esc_sql( $wpdb->prefix . self::CHUNK_TABLE );

		// Parse vector_id to get post_id and chunk_id.
		$parts = explode( '_', $vector_id );
		if ( count( $parts ) < 2 ) {
			return '';
		}

		$post_id  = (int) $parts[0];
		$chunk_id = (int) $parts[1];

		$result = $wpdb->get_var(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe and escaped.
				"SELECT chunk_text FROM {$chunk_table} WHERE post_id = %d AND chunk_id = %d",
				$post_id,
				$chunk_id
			)
		);

		return $result ? $result : '';
	}

	/**
	 * Plugin deactivation handler
	 * Cleans up scheduled cron jobs and deletes vector index data
	 *
	 * @return void
	 */
	public function deactivate() {
		// Clean up cron jobs (legacy WP-Cron and Action Scheduler).
		wp_clear_scheduled_hook( 'chatbudgie_daily_task' );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'chatbudgie_daily_task', array(), 'chatbudgie' );
		}

		// Delete app key.
		delete_option( CHATBUDGIE_APP_KEY_OPTION );

		// Delete all index data (vectors + truncate tables).
		$this->delete_all_index_data();

		// Drop tables.
		$this->drop_index_meta_table();
		$this->drop_chunk_data_table();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'ChatBudgie plugin deactivated, cron jobs cleaned up, index data deleted, app key removed' );
	}

	/**
	 * Drop the index meta table on plugin deactivation
	 *
	 * @return void
	 */
	private function drop_index_meta_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::INDEX_META_TABLE;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to drop index meta table: ' . $wpdb->last_error );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Index meta table dropped successfully' );
		}
	}

	/**
	 * Drop the chunk data table on plugin deactivation
	 *
	 * @return void
	 */
	private function drop_chunk_data_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::CHUNK_TABLE;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to drop chunk data table: ' . $wpdb->last_error );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Chunk data table dropped successfully' );
		}
	}

	/**
	 * Delete all vector index data by removing the entire data directory
	 *
	 * @return void
	 */
	public static function delete_index_data() {
		$data_dir = self::get_data_dir();

		if ( ! is_dir( $data_dir ) ) {
			return;
		}

		// Recursively remove directory using PHP functions.
		self::rrmdir( $data_dir );

		if ( ! is_dir( $data_dir ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Deleted index data directory: ' . $data_dir );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Failed to delete index data directory: ' . $data_dir );
		}
	}

	/**
	 * Recursively remove a directory and its contents
	 *
	 * @param string $dir Directory path to remove.
	 * @return void
	 */
	private static function rrmdir( $dir ) {
		global $wp_filesystem;
		if ( ! isset( $wp_filesystem ) ) {
			if ( ! defined( 'ABSPATH' ) ) {
				return;
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			return;
		}

		$files = $wp_filesystem->dirlist( $dir );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$path = $dir . '/' . $file['name'];
				if ( 'd' === $file['type'] ) {
					self::rrmdir( $path );
				} else {
					$wp_filesystem->delete( $path );
				}
			}
		}

		$wp_filesystem->rmdir( $dir );
	}

	/**
	 * Daily cron task handler
	 * Runs vector index optimization to maintain search performance
	 *
	 * @return void
	 */
	public function daily_task() {
		// Prevent PHP from timing out during optimization.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}

		try {
			// Log task start.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie daily task started at ' . current_time( 'Y-m-d H:i:s' ) );

			// Run vector optimization task.
			$optimizer = new Optimizer();
			$optimizer->run();

			// Schedule index build after optimization.
			$this->schedule_index_build();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie: Scheduled daily full index build after optimization.' );

			// Log task completion.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie daily task completed at ' . current_time( 'Y-m-d H:i:s' ) );
		} catch ( Exception $e ) {
			// Log task failure.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie daily task failed: ' . $e->getMessage() . ' at ' . current_time( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * Sanitize client-supplied conversation history for the chat API.
	 *
	 * @param mixed $conversation_history Raw decoded conversation history.
	 * @return array Sanitized conversation history.
	 */
	private function sanitize_conversation_history( $conversation_history ) {
		if ( ! is_array( $conversation_history ) ) {
			return array();
		}

		$allowed_roles     = array( 'user', 'assistant', 'system' );
		$sanitized_history = array();

		foreach ( $conversation_history as $message ) {
			if ( ! is_array( $message ) ) {
				continue;
			}

			$role    = isset( $message['role'] ) ? sanitize_key( $message['role'] ) : '';
			$content = isset( $message['content'] ) ? sanitize_textarea_field( $message['content'] ) : '';

			if ( ! in_array( $role, $allowed_roles, true ) || '' === $content ) {
				continue;
			}

			$sanitized_history[] = array(
				'role'    => $role,
				'content' => $content,
			);
		}

		return $sanitized_history;
	}

	/**
	 * Handle API response and return the data (usually a PagedModel for paginated requests)
	 *
	 * @param array|WP_Error $response The response from wp_remote_get/post.
	 * @param string         $error_prefix Prefix for error messages.
	 * @return array The processed data
	 * @throws Exception If API request fails or returns an error.
	 */
	private function handle_api_response( $response, $error_prefix = 'API request failed' ) {
		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "ChatBudgie: $error_prefix: $message" );
			throw new Exception( esc_html( "$error_prefix: $message" ) );
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$message = isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'API error' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "ChatBudgie: $error_prefix error: $message (Status: $status_code)" );
			throw new Exception( esc_html( $message ), absint( $status_code ) );
		}

		// If data is wrapped in 'data', unwrap it to get the actual payload (e.g. PagedModel).
		if ( isset( $data['data'] ) ) {
			return $data['data'];
		}

		return $data;
	}

	/**
	 * Get user account information from the API
	 *
	 * @return array User info array on success
	 * @throws Exception If API request fails or returns an error.
	 */
	public function get_user_info() {
		$app_key = get_option( CHATBUDGIE_APP_KEY_OPTION, '' );
		$headers = array(
			'appKey' => $app_key,
		);

		$response = wp_remote_get(
			self::USER_INFO_API,
			array(
				'headers'   => $headers,
				'timeout'   => 15,
				'sslverify' => self::SSL_VERIFY,
			)
		);

		return $this->handle_api_response( $response, 'User info API request' );
	}

	/**
	 * Get token usage data from the API
	 *
	 * @param int $page Page number (starts from 1).
	 * @param int $size Items per page.
	 * @return array Usage data (PagedModel) on success
	 * @throws Exception If API request fails or returns an error.
	 */
	public function get_token_usage( $page = 1, $size = 20 ) {
		$app_key = get_option( CHATBUDGIE_APP_KEY_OPTION, '' );

		if ( empty( $app_key ) ) {
			throw new Exception( 'Application key is missing', 401 );
		}

		$headers = array(
			'appKey' => $app_key,
		);

		$api_url = add_query_arg(
			array(
				'page' => $page - 1,
				'size' => $size,
			),
			self::TOKEN_USAGE_API
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'headers'   => $headers,
				'timeout'   => 15,
				'sslverify' => self::SSL_VERIFY,
			)
		);

		return $this->handle_api_response( $response, 'Token usage API request' );
	}

	/**
	 * Get user orders data from the API
	 *
	 * @param int $page Page number (starts from 1).
	 * @param int $size Items per page.
	 * @return array Orders data (PagedModel) on success
	 * @throws Exception If API request fails or returns an error.
	 */
	public function get_user_orders( $page = 1, $size = 20 ) {
		$app_key = get_option( CHATBUDGIE_APP_KEY_OPTION, '' );

		if ( empty( $app_key ) ) {
			throw new Exception( 'Application key is missing', 401 );
		}

		$headers = array(
			'appKey' => $app_key,
		);

		$api_url = add_query_arg(
			array(
				'page' => $page - 1,
				'size' => $size,
			),
			self::USER_ORDERS_API
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'headers'   => $headers,
				'timeout'   => 15,
				'sslverify' => self::SSL_VERIFY,
			)
		);

		return $this->handle_api_response( $response, 'User orders API request' );
	}

	/**
	 * Enqueue frontend scripts and styles
	 * Loads CSS, JavaScript, and passes PHP variables to the frontend via wp_localize_script
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'chatbudgie-style',
			CHATBUDGIE_PLUGIN_URL . 'assets/css/chatbudgie.css',
			array(),
			CHATBUDGIE_VERSION
		);

		$chatbudgie_primary_color = $this->sanitize_hex_color(
			get_option( 'chatbudgie_primary_color', '#2f7bff' )
		);

		wp_add_inline_style(
			'chatbudgie-style',
			sprintf(
				'#chatbudgie-widget{--chatbudgie-accent:%1$s;--chatbudgie-accent-strong:%1$s;}',
				esc_html( $chatbudgie_primary_color )
			)
		);

		wp_enqueue_script(
			'marked-js',
			CHATBUDGIE_PLUGIN_URL . 'assets/js/marked.min.js',
			array(),
			'12.0.0',
			true
		);

		wp_enqueue_script(
			'chatbudgie-script',
			CHATBUDGIE_PLUGIN_URL . 'assets/js/chatbudgie.js',
			array( 'jquery', 'marked-js' ),
			CHATBUDGIE_VERSION,
			true
		);

		$app_key = get_option( CHATBUDGIE_APP_KEY_OPTION, '' );
		if ( empty( $app_key ) ) {
			$welcome = __( "Hi, I'm ChatBudgie, assistant of the website. To use the chat feature, please go to the plugin settings page to connect your ChatBudgie account.", 'chatbudgie' );
		} else {
			$welcome = get_option( 'chatbudgie_welcome_message' );
			if ( empty( $welcome ) ) {
				$welcome = __( "Hi, I'm ChatBudgie, assistant of the website. How can I help you today?", 'chatbudgie' );
			}
		}

		wp_localize_script(
			'chatbudgie-script',
			'chatbudgie_params',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'chat_api_url' => self::CHAT_API,
				'nonce'        => wp_create_nonce( 'chatbudgie_nonce' ),
				'strings'      => array(
					'placeholder' => __( 'Please enter your question...', 'chatbudgie' ),
					'welcome'     => $welcome,
					'error'       => __( 'Failed to send, please try again', 'chatbudgie' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Only load on our settings page.
		if ( strpos( $hook, 'chatbudgie' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'chatbudgie-admin-style',
			CHATBUDGIE_PLUGIN_URL . 'assets/css/chatbudgie-admin.css',
			array( 'wp-color-picker' ),
			CHATBUDGIE_VERSION
		);

		wp_enqueue_script(
			'chatbudgie-admin-script',
			CHATBUDGIE_PLUGIN_URL . 'assets/js/chatbudgie-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			CHATBUDGIE_VERSION,
			true
		);

		wp_localize_script(
			'chatbudgie-admin-script',
			'chatbudgie_admin_params',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'chatbudgie_nonce' ),
				'confirm_rebuild' => __( 'Are you sure you want to rebuild the index? This action will delete the existing index and rebuild it, it may take some time and consume your tokens.', 'chatbudgie' ),
				'choose_icon'     => __( 'Choose Icon', 'chatbudgie' ),
				'select_icon'     => __( 'Select Icon', 'chatbudgie' ),
			)
		);

		// Enqueue WordPress media scripts.
		wp_enqueue_media();

		if ( 'chatbudgie_page_chatbudgie-orders' !== $hook ) {
			return;
		}

		$orders_currency = 'USD';

		wp_enqueue_style(
			'chatbudgie-admin-orders-style',
			CHATBUDGIE_PLUGIN_URL . 'assets/css/chatbudgie-admin-orders.css',
			array( 'chatbudgie-admin-style' ),
			CHATBUDGIE_VERSION
		);

		wp_enqueue_script(
			'chatbudgie-paypal-sdk',
			'https://www.paypal.com/sdk/js?client-id=' . rawurlencode( CHATBUDGIE_PAYPAL_CLIENT_ID ) . '&currency=' . rawurlencode( $orders_currency ) . '&components=buttons&disable-funding=venmo',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'chatbudgie-admin-orders-script',
			CHATBUDGIE_PLUGIN_URL . 'assets/js/chatbudgie-admin-orders.js',
			array( 'chatbudgie-admin-script', 'chatbudgie-paypal-sdk' ),
			CHATBUDGIE_VERSION,
			true
		);

		wp_localize_script(
			'chatbudgie-admin-orders-script',
			'chatbudgie_orders_params',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'chatbudgie_nonce' ),
				'currency'     => $orders_currency,
				'redirect_url' => admin_url( 'admin.php?page=chatbudgie' ),
				'strings'      => array(
					'create_order_error'  => __( 'Failed to create order', 'chatbudgie' ),
					'capture_order_error' => __( 'Failed to capture payment', 'chatbudgie' ),
					'transaction_error'   => __( 'An error occurred during the transaction:', 'chatbudgie' ),
				),
				'packages'     => array(
					array(
						'id'        => '5m',
						'amount'    => 5,
						'showPrice' => '$4.99',
					),
					array(
						'id'        => '20m',
						'amount'    => 20,
						'showPrice' => '$19.50',
					),
					array(
						'id'        => '100m',
						'amount'    => 100,
						'showPrice' => '$95.00',
					),
				),
			)
		);
	}

	/**
	 * Render the chat widget HTML markup
	 * Outputs the chat bubble toggle, container, header, message area, and input form
	 *
	 * @return void
	 */
	public function render_chat_widget() {
		include CHATBUDGIE_PLUGIN_DIR . 'templates/chatbudgie-widget.php';
	}

	/**
	 * Handle AJAX request to search the vector index using chat history
	 * Calls remote embedding API with chat history, then performs local vector search
	 *
	 * @throws Exception If the API request fails or returns an error.
	 * @return void Outputs JSON response and exits
	 */
	public function handle_search_index() {
		if ( ! check_ajax_referer( 'chatbudgie_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session has expired. Please refresh the page and try again.', 'chatbudgie' ) ), 403 );
		}

		$conversation_history_raw = sanitize_textarea_field( wp_unslash( $_POST['conversation_history'] ?? '[]' ) );
		$conversation_history     = $this->sanitize_conversation_history(
			json_decode( $conversation_history_raw, true )
		);

		try {
			$headers = array(
				'Content-Type' => 'application/json',
				'appKey'       => get_option( CHATBUDGIE_APP_KEY_OPTION, '' ),
				'Referer'      => site_url(),
			);

			$response = wp_remote_post(
				self::QUERY_EMBEDDING_API,
				array(
					'headers'   => $headers,
					'body'      => wp_json_encode( $conversation_history ),
					'timeout'   => 60,
					'sslverify' => self::SSL_VERIFY,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message(), 500 );
			}

			$response_body = wp_remote_retrieve_body( $response );
			$data          = json_decode( $response_body, true );

			if ( isset( $data['code'] ) && 200 !== (int) $data['code'] ) {
				throw new Exception( $data['message'] ?? 'API error', (int) $data['code'] );
			}

			$result_data = $data['data'] ?? array();
			$embedding   = $result_data['embedding'] ?? array();

			$grouped_results = array();

			if ( empty( $embedding ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'ChatBudgie handle_search_index: Failed to get query embedding from API' );
			} else {
				// Perform local vector search.
				$k         = 5;
				$threshold = 0.2;
				$results   = $this->searcher->search( $embedding, $k, false );

				foreach ( $results as $result ) {
					if ( $result['score'] >= $threshold ) {
						$chunk_text = $this->get_chunk_text( $result['id'] );

						// Extract post_id from vector_id (format: post_id_chunk_id).
						$parts   = explode( '_', $result['id'] );
						$post_id = (int) $parts[0];

						if ( ! isset( $grouped_results[ $post_id ] ) ) {
							$post                        = get_post( $post_id );
							$grouped_results[ $post_id ] = array(
								'url'    => $post ? get_permalink( $post ) : '',
								'title'  => $post ? get_the_title( $post ) : '',
								'chunks' => array(),
							);
						}
						$grouped_results[ $post_id ]['chunks'][] = array(
							'content' => $chunk_text,
							'score'   => $result['score'],
						);
					}
				}
			}

			wp_send_json_success(
				array(
					'query'          => $result_data['query'] ?? '',
					'appConfigId'    => $result_data['appConfigId'] ?? '',
					'timestamp'      => $result_data['timestamp'] ?? '',
					'signature'      => $result_data['signature'] ?? '',
					'search_results' => array_values( $grouped_results ),
				)
			);

		} catch ( Exception $e ) {
			$status_code   = $e->getCode();
			$error_message = $status_code . ' -' . $e->getMessage();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'ChatBudgie handle_search_index error: ' . $error_message );

			// Format error message for frontend.
			if ( 401 === $status_code ) {
				$error_message = '401 - You are not allowed to access the API. Please login ChatBudgie account in the settings page.';
			} elseif ( 402 === $status_code ) {
				$error_message = '402 - Your token has been used up. Please go to ChatBudgie settings page to recharge.';
			}
			wp_send_json_error( array( 'message' => $error_message ), absint( $status_code ) );
		}
	}

	/**
	 * Show admin notice for index status
	 * Displays the current status of background indexing tasks
	 *
	 * @return void
	 */
	public function show_index_status_notice() {
		$status = $this->get_index_status();

		if ( 'pending' === $status['status'] ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<strong><?php echo esc_html__( 'ChatBudgie:', 'chatbudgie' ); ?></strong>
					<?php echo esc_html__( 'Index build is scheduled and will start shortly.', 'chatbudgie' ); ?>
				</p>
			</div>
			<?php
		} elseif ( 'running' === $status['status'] ) {
			$completed = $status['completed_posts_count'];
			$scheduled = $status['scheduled_posts_count'];
			$progress  = $status['progress'];
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<strong><?php echo esc_html__( 'ChatBudgie:', 'chatbudgie' ); ?></strong>
					<?php
					printf(
						/* translators: 1: number of completed posts, 2: total number of scheduled posts, 3: progress percentage */
						esc_html__( 'Indexing progress: %1$d of %2$d posts completed (%3$d%%)', 'chatbudgie' ),
						intval( $completed ),
						intval( $scheduled ),
						intval( $progress )
					);
					?>
				</p>
				<progress value="<?php echo esc_attr( $progress ); ?>" max="100" style="width: 100%; height: 20px;"></progress>
			</div>
			<?php
		} elseif ( 'completed' === $status['status'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php echo esc_html__( 'ChatBudgie:', 'chatbudgie' ); ?></strong>
					<?php echo esc_html__( 'Index build completed successfully.', 'chatbudgie' ); ?>
				</p>
			</div>
			<?php
		} elseif ( 'failed' === $status['status'] ) {
			$error_msg = isset( $status['error'] ) ? $status['error'] : 'Unknown error';
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php echo esc_html__( 'ChatBudgie:', 'chatbudgie' ); ?></strong>
					<?php echo esc_html__( 'Index build failed:', 'chatbudgie' ); ?>
					<?php echo esc_html( $error_msg ); ?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=chatbudgie_rebuild_index' ), 'chatbudgie_rebuild_index' ) ); ?>">
						<?php echo esc_html__( 'Try again', 'chatbudgie' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Handle manual index rebuild request
	 *
	 * @return void
	 */
	public function handle_manual_rebuild_index() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'chatbudgie_rebuild_index' );

		// Clear all existing index data.
		$this->delete_all_index_data();

		// Schedule fresh index build.
		$this->schedule_index_build();

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'options-general.php?page=chatbudgie' ) );
		exit;
	}

	/**
	 * Add admin menu page for plugin settings
	 * Registers the plugin admin menu and submenus.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'ChatBudgie', 'chatbudgie' ),
			__( 'ChatBudgie', 'chatbudgie' ),
			'manage_options',
			'chatbudgie',
			array( $this, 'render_settings_page' ),
			'dashicons-format-chat'
		);

		add_submenu_page(
			'chatbudgie',
			__( 'Settings', 'chatbudgie' ),
			__( 'Settings', 'chatbudgie' ),
			'manage_options',
			'chatbudgie',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'chatbudgie',
			__( 'Usage', 'chatbudgie' ),
			__( 'Usage', 'chatbudgie' ),
			'manage_options',
			'chatbudgie-usage',
			array( $this, 'render_usage_page' )
		);

		add_submenu_page(
			'chatbudgie',
			__( 'Orders', 'chatbudgie' ),
			__( 'Orders', 'chatbudgie' ),
			'manage_options',
			'chatbudgie-orders',
			array( $this, 'render_orders_page' )
		);
	}

	/**
	 * Add action links shown on the WordPress plugins page.
	 *
	 * @param array<string,string> $links Existing plugin action links.
	 * @return array<string,string>
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'admin.php?page=chatbudgie' ) ),
			esc_html__( 'Settings', 'chatbudgie' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Render the usage page
	 *
	 * @return void
	 */
	public function render_usage_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'chatbudgie' ), esc_html__( 'Unauthorized', 'chatbudgie' ), array( 'response' => 403 ) );
		}

		try {
			$user_info = $this->get_user_info();

			// Get current page and size.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
			$size = 20;

			$usage_data = $this->get_token_usage( $page, $size );

			include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-usage.php';
		} catch ( Exception $e ) {
			if ( $e->getCode() === 401 ) {
				// Key is invalid, clear it and show login page.
				update_option( CHATBUDGIE_APP_KEY_OPTION, '' );
				$this->render_login_page();
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to fetch usage info. Please try again later.', 'chatbudgie' ) . ' (' . esc_html( $e->getMessage() ) . ')</p></div>';
				// Still try to show the page but without user info.
				$user_info = null;
				include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-usage.php';
			}
		}
	}

	/**
	 * Render the orders page
	 *
	 * @return void
	 */
	public function render_orders_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'chatbudgie' ), esc_html__( 'Unauthorized', 'chatbudgie' ), array( 'response' => 403 ) );
		}

		try {
			$user_info = $this->get_user_info();

			// Get current page and size.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
			$size = 20;

			$orders_data = $this->get_user_orders( $page, $size );

			include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-orders.php';
		} catch ( Exception $e ) {
			if ( $e->getCode() === 401 ) {
				// Key is invalid, clear it and show login page.
				update_option( CHATBUDGIE_APP_KEY_OPTION, '' );
				$this->render_login_page();
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to fetch user info. Please try again later.', 'chatbudgie' ) . ' (' . esc_html( $e->getMessage() ) . ')</p></div>';
				$user_info   = null;
				$orders_data = null;
				include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-orders.php';
			}
		}
	}

	/**
	 * Handle the login callback from the login server
	 *
	 * @return void
	 */
	public function handle_login_callback() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to complete this action.', 'chatbudgie' ), esc_html__( 'Unauthorized', 'chatbudgie' ), array( 'response' => 403 ) );
		}

		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		if ( empty( $state ) || ! wp_verify_nonce( $state, 'chatbudgie_login_callback' ) ) {
			wp_die( esc_html__( 'Invalid login callback request.', 'chatbudgie' ), esc_html__( 'Login Error', 'chatbudgie' ), array( 'response' => 403 ) );
		}

		$code = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );

		if ( empty( $code ) ) {
			wp_die( esc_html__( 'Authorization code is missing', 'chatbudgie' ), esc_html__( 'Login Error', 'chatbudgie' ), array( 'response' => 400 ) );
		}

		// Call the refresh appkey API.
		$response = wp_remote_post(
			self::REFRESH_APP_KEY_API,
			array(
				'headers'   => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				'body'      => array(
					'code'    => $code,
					'appName' => CHATBUDGIE_APP_NAME,
					'siteUrl' => get_site_url(),
				),
				'timeout'   => 30,
				'sslverify' => self::SSL_VERIFY,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_die( esc_html( $response->get_error_message() ), esc_html__( 'API Error', 'chatbudgie' ), array( 'response' => 500 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Extract appkey from response. Try different common keys.
		$app_key = '';
		if ( isset( $body['data']['appKey'] ) ) {
			$app_key = $body['data']['appKey'];
		} elseif ( isset( $body['data']['app_key'] ) ) {
			$app_key = $body['data']['app_key'];
		} elseif ( isset( $body['appKey'] ) ) {
			$app_key = $body['appKey'];
		} elseif ( isset( $body['app_key'] ) ) {
			$app_key = $body['app_key'];
		}

		if ( $app_key ) {
			update_option( CHATBUDGIE_APP_KEY_OPTION, $app_key );

			// Schedule initial index build.
			$this->schedule_index_build();

			// Redirect to settings page.
			wp_safe_redirect( admin_url( 'admin.php?page=chatbudgie' ) );
			exit;
		}

		wp_die( esc_html__( 'Failed to retrieve appKey from login server', 'chatbudgie' ), esc_html__( 'Login Error', 'chatbudgie' ), array( 'response' => 500 ) );
	}

	/**
	 * Render the login page to authenticate user and set appKey
	 *
	 * @return void
	 */
	private function render_login_page() {
		include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-login.php';
	}

	/**
	 * Register plugin settings with WordPress
	 * Defines all settings fields that can be saved via the settings page
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'chatbudgie_general_settings',
			CHATBUDGIE_APP_KEY_OPTION,
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'chatbudgie_appearance_settings',
			'chatbudgie_welcome_message',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'chatbudgie_appearance_settings',
			'chatbudgie_custom_icon',
			array(
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_setting(
			'chatbudgie_appearance_settings',
			'chatbudgie_primary_color',
			array(
				'sanitize_callback' => array( $this, 'sanitize_hex_color' ),
			)
		);
		register_setting(
			'chatbudgie_appearance_settings',
			'chatbudgie_secondary_color',
			array(
				'sanitize_callback' => array( $this, 'sanitize_hex_color' ),
			)
		);
	}

	/**
	 * Sanitize hexadecimal color values
	 *
	 * @param string $color The color value.
	 * @return string The sanitized hex color or default
	 */
	public function sanitize_hex_color( $color ) {
		if ( preg_match( '/^#[a-f0-9]{6}$/i', $color ) ) {
			return $color;
		}
		return '#2f7bff'; // Default color.
	}

	/**
	 * Render the plugin settings page HTML
	 * Displays the admin settings form with icon selection, token management, and API configuration
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'chatbudgie' ), esc_html__( 'Unauthorized', 'chatbudgie' ), array( 'response' => 403 ) );
		}

		// Check if appKey is set.
		$app_key = get_option( CHATBUDGIE_APP_KEY_OPTION, '' );

		if ( empty( $app_key ) ) {
			$this->render_login_page();
			return;
		}

		try {
			$user_info = $this->get_user_info();
			include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-settings.php';
		} catch ( Exception $e ) {
			if ( $e->getCode() === 401 ) {
				// Key is invalid, clear it and show login page.
				update_option( CHATBUDGIE_APP_KEY_OPTION, '' );
				$this->render_login_page();
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to fetch account info. Please try again later.', 'chatbudgie' ) . ' (' . esc_html( $e->getMessage() ) . ')</p></div>';
				// Still try to show the page but without user info.
				$user_info = null;
				include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-settings.php';
			}
		}
	}

	/**
	 * Handle AJAX request to create a PayPal order
	 *
	 * @throws Exception If order creation fails.
	 * @return void
	 */
	public function handle_create_paypal_order() {
		check_ajax_referer( 'chatbudgie_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$package    = sanitize_key( wp_unslash( $_POST['package'] ?? '' ) );
		$amount     = absint( wp_unslash( $_POST['amount'] ?? 0 ) );
		$currency   = strtoupper( sanitize_key( wp_unslash( $_POST['currency'] ?? '' ) ) );
		$show_price = sanitize_text_field( wp_unslash( $_POST['show_price'] ?? '' ) );

		if ( empty( $package ) || empty( $amount ) ) {
			wp_send_json_error( array( 'message' => 'Invalid package information' ), 400 );
		}

		try {
			$extra    = wp_json_encode(
				array(
					'package'   => $package,
					'amount'    => $amount . 'M',
					'currency'  => $currency,
					'showPrice' => $show_price,
					'appName'   => CHATBUDGIE_APP_NAME,
					'siteUrl'   => get_site_url(),
				)
			);
			$response = wp_remote_post(
				self::CREATE_PAYPAL_ORDER_API,
				array(
					'headers'   => array(
						'Content-Type' => 'application/json',
						'appKey'       => get_option( CHATBUDGIE_APP_KEY_OPTION, '' ),
					),
					'body'      => wp_json_encode(
						array(
							'amount'   => $amount * 1000000,
							'currency' => $currency,
							'extra'    => $extra,
						)
					),
					'timeout'   => 30,
					'sslverify' => self::SSL_VERIFY,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['code'] ) && 200 !== (int) $body['code'] ) {
				throw new Exception( $body['message'] ?? 'API error', (int) $body['code'] );
			}

			wp_send_json_success( $body['data'] );
		} catch ( Exception $e ) {
			$status_code = $e->getCode();
			if ( $status_code < 400 || $status_code >= 600 ) {
				$status_code = 500;
			}
			wp_send_json_error( array( 'message' => $e->getMessage() ), absint( $status_code ) );
		}
	}

	/**
	 * Handle AJAX request to capture a PayPal order
	 *
	 * @throws Exception If order capture fails.
	 * @return void
	 */
	public function handle_capture_paypal_order() {
		check_ajax_referer( 'chatbudgie_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$order_id = sanitize_text_field( wp_unslash( $_POST['order_id'] ?? '' ) );

		if ( empty( $order_id ) ) {
			wp_send_json_error( array( 'message' => 'Order ID is missing' ), 400 );
		}

		try {
			$extra    = wp_json_encode(
				array(
					'orderId' => $order_id,
					'appName' => CHATBUDGIE_APP_NAME,
					'siteUrl' => get_site_url(),
				)
			);
			$response = wp_remote_post(
				self::CAPTURE_PAYPAL_ORDER_API,
				array(
					'headers'   => array(
						'Content-Type' => 'application/json',
						'appKey'       => get_option( CHATBUDGIE_APP_KEY_OPTION, '' ),
					),
					'body'      => wp_json_encode(
						array(
							'orderId' => $order_id,
							'extra'   => $extra,
						)
					),
					'timeout'   => 30,
					'sslverify' => self::SSL_VERIFY,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['code'] ) && 200 !== (int) $body['code'] ) {
				throw new Exception( $body['message'] ?? 'API error', (int) $body['code'] );
			}

			wp_send_json_success( $body['data'] );
		} catch ( Exception $e ) {
			$status_code = $e->getCode();
			if ( $status_code < 400 || $status_code >= 600 ) {
				$status_code = 500;
			}
			wp_send_json_error( array( 'message' => $e->getMessage() ), absint( $status_code ) );
		}
	}
}
