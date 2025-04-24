<?php
/**
 * Content Type
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

/**
 * Content Type
 *
 * @package PRC\Platform\Collections
 */
class Content_Type {
	/**
	 * The loader object.
	 *
	 * @var object
	 */
	protected $loader;

	/**
	 * Post type
	 *
	 * @var string
	 */
	public static $post_object_name = 'collections';

	/**
	 * Taxonomy
	 *
	 * @var string
	 */
	public static $taxonomy_object_name = 'collection';

	/**
	 * Kicker meta key
	 *
	 * @var string
	 */
	public static $kicker_meta_key = 'kicker_pattern_slug';

	/**
	 * Settings for the Collection post type.
	 *
	 * @var array
	 */
	public static $post_object_args = array(
		'labels'             => array(
			'name'                       => 'Collections',
			'singular_name'              => 'Collection',
			'description'                => 'A collection organizes and presents related Pew Research Center content together, allowing editors to curate groups of research reports, articles, and other content that share common themes, topics, research initiatives, special projects or grants ~ like Religious Landscape Study. Collections help readers discover interconnected research findings and provide contextual understanding of broader research areas.',
			'add_new'                    => 'Add New',
			'add_new_item'               => 'Add New Collection',
			'edit_item'                  => 'Edit Collection',
			'new_item'                   => 'New Collection',
			'all_items'                  => 'Collection',
			'view_item'                  => 'View collection',
			'search_items'               => 'Search collections',
			'not_found'                  => 'No collection found',
			'not_found_in_trash'         => 'No collection found in Trash',
			'parent_item_colon'          => '',
			'parent_item'                => 'Parent Collection',
			'new_item_name'              => 'New Collection Name',
			'separate_items_with_commas' => 'Separate collections with commas',
			'add_or_remove_items'        => 'Add or remove collections',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Collections',
			'items_list'                 => 'Collections list',
			'items_list_navigation'      => 'Collections list navigation',
			'menu_name'                  => 'Collections',
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-tagcloud',
		'query_var'          => true,
		'rewrite'            => array(
			'slug' => 'collections', // We're giving the post type a rewrite but not the taxonomy. I dont expect this will receive anything but internal traffic for right now.
		),
		'taxonomies'         => array( '_post_visibility' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => true,
		'menu_position'      => 60,
		'supports'           => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields', 'page-attributes', 'thumbnail' ),
		'template'           => array(
			array( 'prc-block/grid-controller', array() ),
		),
	);

	/**
	 * Settings for the Collections taxonomy.
	 *
	 * @var array
	 */
	public static $taxonomy_object_args = array(
		'labels'            => array(
			'name'                       => 'Collections',
			'singular_name'              => 'Collection',
			'menu_name'                  => 'Collections',
			'all_items'                  => 'All Collections',
			'parent_item'                => 'Parent Collection',
			'parent_item_colon'          => 'Parent Collection:',
			'new_item_name'              => 'New Collection Name',
			'add_new_item'               => 'Add New Collection',
			'edit_item'                  => 'Edit Collection',
			'update_item'                => 'Update Collection',
			'view_item'                  => 'View Collection',
			'separate_items_with_commas' => 'Separate collections with commas',
			'add_or_remove_items'        => 'Add or remove collections',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular collections',
			'search_items'               => 'Search collections',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No Collections',
			'items_list'                 => 'Collections list',
			'items_list_navigation'      => 'Collections list navigation',
		),
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud'     => false,
		'show_in_rest'      => true,
		'rewrite'           => false,
	);

	/**
	 * Constructor
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'init', $this, 'register_term_data_store' );
		$loader->add_filter( 'default_wp_template_part_areas', $this, 'kicker_template_areas', 11, 1 );
		$loader->add_filter( 'prc_platform_pub_listing_default_args', $this, 'opt_into_pub_listing' );
		$loader->add_action( 'pre_get_posts', $this, 'filter_self_reference_out', 100, 1 );
		$loader->add_filter( 'prc_platform__art_direction_enabled_post_types', $this, 'enable_art_direction_support' );
	}

	/**
	 * Register the Collections post type and taxonomy and establish a relationship between them.
	 * Additionally, register the kicker meta.
	 *
	 * @hook init
	 * @uses prc_platform__collections_enabled_post_types
	 */
	public function register_term_data_store() {
		// Register the post type.
		register_post_type( self::$post_object_name, self::$post_object_args );

		// Register the taxonomy.
		$enabled_post_types = apply_filters( 'prc_platform__collections_enabled_post_types', array( 'post', 'feature' ) );
		register_taxonomy( self::$taxonomy_object_name, $enabled_post_types, self::$taxonomy_object_args );

		// Establish a relationship between the post type and taxonomy.
		\TDS\add_relationship( self::$post_object_name, self::$taxonomy_object_name );

		// Register the kicker meta.
		$this->register_kicker_meta();
	}

	/**
	 * Register the kicker meta.
	 */
	public function register_kicker_meta() {
		register_post_meta(
			self::$post_object_name,
			self::$kicker_meta_key,
			array(
				'single'        => true,
				'type'          => 'string',
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Adds custom "kicker" template part area to the default template part areas.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/default_wp_template_part_areas/
	 *
	 * @param array $areas Existing array of template part areas.
	 * @return array Modified array of template part areas including the new kicker area.
	 */
	public function kicker_template_areas( array $areas ) {
		$areas[] = array(
			'area'        => 'kicker',
			'label'       => 'Kicker',
			'description' => 'A "kicker" is a small label and/or icon that denotes a post is part of a collection.',
			'icon'        => 'layout',
			'area_tag'    => 'div',
		);
		return $areas;
	}

	/**
	 * Hide the collection post type from the main query on collection pages.
	 * Additionally, set a tax_query for queries on this page to look at the referenced
	 * collection term.
	 *
	 * @hook pre_get_posts
	 *
	 * @param WP_Query $query The query object.
	 */
	public function filter_self_reference_out( $query ) {
		if ( true !== $query->get( 'isPubListingQuery' ) ) {
			return;
		}
		// Basically, on collection pages we want to get the term id and assign a tax_query to the block query so that we're filtering by only the posts in that collection.
		$queried_object = get_queried_object();
		if ( ! is_a( $queried_object, 'WP_Post' ) || $queried_object->post_type !== self::$post_object_name ) {
			return;
		}
		$collection_term = \TDS\get_related_term( $queried_object );
		if ( ! $collection_term ) {
			return;
		}
		$new_tax_query = array(
			'relation' => 'AND',
			array(
				'taxonomy' => self::$taxonomy_object_name,
				'field'    => 'term_id',
				'terms'    => $collection_term->term_id,
			),
		);
		$tax_query     = $query->get( 'tax_query' );
		if ( $tax_query ) {
			$new_tax_query = array_merge( $new_tax_query, $tax_query );
		}
		$query->set( 'post__not_in', array( $queried_object->ID ) );
		$query->set( 'tax_query', $new_tax_query );
	}

	/**
	 * Opt the post type into the publication listing.
	 *
	 * @hook prc_platform_pub_listing_default_args
	 *
	 * @param array $args The arguments.
	 * @return array The arguments.
	 */
	public function opt_into_pub_listing( $args = array() ) {
		$post_types        = $args['post_type'] ?? array();
		$post_types        = is_array( $post_types ) ? $post_types : array( $post_types );
		$args['post_type'] = array_merge( $post_types, array( self::$post_object_name ) );
		return $args;
	}

	/**
	 * Enable art direction support.
	 *
	 * @hook prc_platform__art_direction_enabled_post_types
	 *
	 * @param array $post_types The post types.
	 * @return array The post types.
	 */
	public function enable_art_direction_support( $post_types ) {
		$post_types[] = self::$post_object_name;
		return $post_types;
	}
}
