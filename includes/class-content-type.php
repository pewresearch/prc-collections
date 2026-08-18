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
		'supports'           => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields', 'page-attributes', 'thumbnail', 'prc-schema-seo' ),
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
		$loader->add_action( 'init', $this, 'register_default_post_type_support', 5 );
		$loader->add_action( 'init', $this, 'register_post_object', 5 );
		$loader->add_action( 'init', $this, 'register_term_data_store', 20 );
		$loader->add_filter( 'default_wp_template_part_areas', $this, 'kicker_template_areas', 11, 1 );
		$loader->add_action( 'pre_get_posts', $this, 'filter_self_reference_out', 100, 1 );
		$loader->add_filter( 'prc_platform_pub_listing_default_args', $this, 'scope_pub_listing_to_collection_on_singular', 20, 2 );
		$loader->add_filter( 'prc_platform_pub_listing_visibility_terms', $this, 'filter_listing_visibility_terms', 10, 3 );
		$loader->add_filter( 'prc_platform_pub_listing_default_visibility', $this, 'default_collections_visibility' );
	}

	/**
	 * Register default post type support for collections.
	 *
	 * @hook init
	 */
	public function register_default_post_type_support() {
		add_post_type_support( 'post', 'prc-collections' );
		add_post_type_support( 'feature', 'prc-collections' );
		// Add supports to the collections post type itself.
		add_post_type_support( self::$post_object_name, 'prc-bylines' );
		add_post_type_support( self::$post_object_name, 'prc-art-direction' );
		add_post_type_support( self::$post_object_name, 'prc-sitemap' );
		add_post_type_support( self::$post_object_name, 'prc-publication-listing' );
	}

	/**
	 * Get the enabled post types for the collections taxonomy.
	 *
	 * @return array The enabled post types.
	 */
	public static function get_enabled_post_types() {
		$post_types      = get_post_types( array( 'public' => true ), 'names' );
		$supported_types = array_values(
			array_filter(
				$post_types,
				function ( $pt ) {
					return post_type_supports( $pt, 'prc-collections' );
				}
			)
		);
		// Maintain backward compatibility with filter.
		$filter_types       = apply_filters( 'prc_platform__collections_enabled_post_types', array() );
		$enabled_post_types = array_unique( array_merge( $supported_types, $filter_types ) );
		return array_values( $enabled_post_types );
	}

	/**
	 * Register the Collections post type and kicker meta.
	 *
	 * Runs at priority 5 so plugins that bind per-type meta on default
	 * `init` (or at 11) still see `collections` in `get_post_types()`.
	 *
	 * @hook init
	 */
	public function register_post_object() {
		register_post_type( self::$post_object_name, self::$post_object_args );
		$this->register_kicker_meta();
	}

	/**
	 * Register the collection taxonomy and establish a relationship with the post type.
	 *
	 * Runs at priority 20 so opted-in CPTs (feature, fact-sheet, quiz, …)
	 * exist before get_enabled_post_types() reads them.
	 *
	 * @hook init
	 */
	public function register_term_data_store() {
		$enabled_post_types = self::get_enabled_post_types();
		register_taxonomy( self::$taxonomy_object_name, $enabled_post_types, self::$taxonomy_object_args );

		// Establish a relationship between the post type and taxonomy.
		// Disable automatic permalink rewrites since collections handles its own permalink structure.
		\PRC\TDS\add_relationship( self::$post_object_name, self::$taxonomy_object_name, false );
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
		$queried_object = get_queried_object();
		if ( ! is_a( $queried_object, 'WP_Post' ) || $queried_object->post_type !== self::$post_object_name ) {
			return;
		}
		$collection_term = \PRC\TDS\get_related_term( $queried_object );
		if ( ! $collection_term ) {
			return;
		}

		// Merge the collection term clause into the existing tax_query
		// so we preserve any earlier clauses (e.g. _post_visibility).
		$existing_tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $existing_tax_query ) ) {
			$existing_tax_query = array();
		}
		$existing_tax_query[] = array(
			'taxonomy' => self::$taxonomy_object_name,
			'field'    => 'term_id',
			'terms'    => $collection_term->term_id,
		);
		$query->set( 'tax_query', $existing_tax_query );

		// Merge the current collection post into any existing exclusions.
		$existing_not_in = $query->get( 'post__not_in' );
		if ( ! is_array( $existing_not_in ) ) {
			$existing_not_in = array();
		}
		$existing_not_in[] = $queried_object->ID;
		$query->set( 'post__not_in', array_unique( $existing_not_in ) );
	}

	/**
	 * Scope publication listing Query blocks to the current collection on singular collection URLs.
	 *
	 * Collection templates use a non-inherited core/query (`inherit: false`). Those queries are built
	 * via `query_loop_block_query_vars` and never receive `isPubListingQuery` on the main loop, so
	 * {@see filter_self_reference_out()} does not run. Without this filter, the listing is not limited
	 * to the collection term and block pagination (`query-{id}-page`) has no stable result set.
	 *
	 * @hook prc_platform_pub_listing_default_args
	 *
	 * @param array    $args  Query arguments.
	 * @param WP_Query $query Query instance when merged from the main loop; null for block/REST paths.
	 * @return array
	 */
	public function scope_pub_listing_to_collection_on_singular( $args, $query ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		if ( ! is_singular( self::$post_object_name ) ) {
			return $args;
		}
		$queried_object = get_queried_object();
		if ( ! is_a( $queried_object, 'WP_Post' ) || self::$post_object_name !== $queried_object->post_type ) {
			return $args;
		}
		$collection_term = \PRC\TDS\get_related_term( $queried_object );
		if ( ! $collection_term ) {
			return $args;
		}

		$collection_clause = array(
			'taxonomy' => self::$taxonomy_object_name,
			'field'    => 'term_id',
			'terms'    => $collection_term->term_id,
		);

		$existing_tax = $args['tax_query'] ?? array();
		if ( ! empty( $existing_tax ) ) {
			$args['tax_query'] = array(
				'relation' => 'AND',
				$existing_tax,
				$collection_clause,
			);
		} else {
			$args['tax_query'] = array( $collection_clause );
		}

		$exclude_ids          = $args['post__not_in'] ?? array();
		$exclude_ids          = is_array( $exclude_ids ) ? $exclude_ids : array( $exclude_ids );
		$exclude_ids[]        = $queried_object->ID;
		$args['post__not_in'] = array_values( array_unique( array_map( 'absint', $exclude_ids ) ) );

		return $args;
	}

	/**
	 * @param string[] $terms Visibility slugs from Query::get_visibility_terms().
	 * @return string[]
	 */
	public static function skip_index_visibility_for_current_request( array $terms ): array {
		$on_collection_listing = is_singular( self::$post_object_name ) || is_tax( self::$taxonomy_object_name );
		if ( ! $on_collection_listing ) {
			return $terms;
		}

		if ( in_array( 'hidden-on-search', $terms, true ) ) {
			return $terms;
		}

		if ( in_array( 'hidden-on-index', $terms, true ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * @hook prc_platform_pub_listing_visibility_terms
	 *
	 * @param mixed $terms Visibility slugs.
	 * @param mixed $args  Query args.
	 * @param mixed $query Query object or null.
	 * @return string[]
	 */
	public function filter_listing_visibility_terms( $terms, $args, $query ) {
		if ( ! is_array( $terms ) ) {
			return array();
		}

		return self::skip_index_visibility_for_current_request( $terms );
	}

	/**
	 * Hide collections from the publications archive by default.
	 *
	 * Applied by prc-publication-listing on post init when `_post_visibility`
	 * is empty. Editors can uncheck the toggle to opt a collection into listings.
	 *
	 * @hook prc_platform_pub_listing_default_visibility
	 *
	 * @param mixed $defaults Map of post_type => term slug[].
	 * @return mixed
	 */
	public function default_collections_visibility( $defaults ) {
		if ( ! is_array( $defaults ) ) {
			return $defaults;
		}

		$defaults[ self::$post_object_name ] = array( 'hidden-on-index' );
		return $defaults;
	}
}
