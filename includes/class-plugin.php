<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

use WP_Error;

/**
 * Plugin class.
 *
 * @package    PRC\Platform\Collections
 */
class Plugin {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the platform as initialized by hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = '1.0.0';
		$this->plugin_name = 'prc-collections';

		$this->load_dependencies();
		$this->init_dependencies();
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load plugin loading class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';

		require_once plugin_dir_path( __DIR__ ) . '/includes/class-content-type.php';

		require_once plugin_dir_path( __DIR__ ) . '/blocks/class-blocks.php';

		// Initialize the loader.
		$this->loader = new Loader();
	}

	/**
	 * Initialize the dependencies.
	 *
	 * @since    1.0.0
	 */
	private function init_dependencies() {
		new Content_Type( $this->get_loader() );
		new Blocks( $this->get_loader() );
		$this->loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_inspector_sidebar_panel_assets' );
	}

	/**
	 * Register the plugin panel assets.
	 *
	 * @return bool|WP_Error True if the assets are registered, false if not.
	 */
	public function register_inspector_sidebar_panel_assets() {
		$asset_file = include plugin_dir_path( __FILE__ ) . 'inspector-sidebar-panel/build/index.asset.php';
		$asset_slug = 'prc-collections-inspector-panel';
		$script_src = plugin_dir_url( __FILE__ ) . 'inspector-sidebar-panel/build/index.js';

		$script = wp_register_script(
			$asset_slug,
			$script_src,
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		if ( ! $script ) {
			return new WP_Error( $asset_slug, 'Failed to register all assets' );
		}

		return $asset_slug;
	}

	/**
	 * Enqueue the plugin panel assets.
	 *
	 * @hook enqueue_block_editor_assets
	 */
	public function enqueue_inspector_sidebar_panel_assets() {
		$registered = $this->register_inspector_sidebar_panel_assets();
		if ( is_wp_error( $registered ) ) {
			return;
		}
		if ( wp_script_is( $registered, 'registered' ) && \PRC\Platform\get_wp_admin_current_post_type() === Content_Type::$post_object_name ) {
			wp_enqueue_script( $registered );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PRC\Platform\Collections\Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
