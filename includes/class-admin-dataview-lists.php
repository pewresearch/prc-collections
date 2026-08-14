<?php
/**
 * Registers the Collections DataViews admin list.
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

/**
 * Soft-depends on prc-wp-admin-dataview via the register_lists action.
 */
class Admin_Dataview_Lists {
	/**
	 * Constructor.
	 *
	 * @param Loader $loader Plugin loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'prc_wp_admin_dataview_register_lists', $this, 'register_lists', 10, 1 );
	}

	/**
	 * Register the collections list config.
	 *
	 * @param object $lists List registry from prc-wp-admin-dataview.
	 */
	public function register_lists( $lists ): void {
		if ( ! is_object( $lists ) || ! method_exists( $lists, 'register' ) ) {
			return;
		}

		foreach ( self::list_configs() as $config ) {
			$lists->register( $config );
		}
	}

	/**
	 * List configs owned by this plugin.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function list_configs(): array {
		return array(
			array(
				'postType'  => Content_Type::$post_object_name,
				'pageSlug'  => 'prc-wp-admin-dataview-collections',
				'menuTitle' => __( 'All Collections', 'prc-collections' ),
				'pageTitle' => __( 'All Collections', 'prc-collections' ),
			),
		);
	}
}
