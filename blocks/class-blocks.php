<?php
/**
 * The collections blocks class.
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

/**
 * The collections blocks class.
 */
class Blocks {
	/**
	 * The loader object.
	 *
	 * @var object
	 */
	protected $loader;

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;

		require_once plugin_dir_path( __FILE__ ) . 'build/collection-kicker/class-collection-kicker.php';
		require_once plugin_dir_path( __FILE__ ) . 'build/fact-sheet-collection/class-fact-sheet-collection.php';

		$this->init();
	}

	/**
	 * Initialize the class.
	 */
	public function init() {
		wp_register_block_metadata_collection(
			plugin_dir_path( __FILE__ ) . 'build',
			plugin_dir_path( __FILE__ ) . 'build/blocks-manifest.php'
		);

		new Collection_Kicker( $this->loader );
		new Fact_Sheet_Collection( $this->loader );
	}
}
