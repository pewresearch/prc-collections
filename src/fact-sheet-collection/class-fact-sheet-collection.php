<?php
/**
 * Fact Sheet Collection Block
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

use WP_Term;
use WP_Post;
use WP_Error;
/**
 * Block Name:        Fact Sheet Collection
 * Description:       Display the hierarchy of the fact sheet&#39;s collection term as well and a link to download an associated PDF.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Seth Rubenstein
 *
 * @package           prc-block
 */
class Fact_Sheet_Collection {
	/**
	 * Object cache group for assembled collection render data.
	 *
	 * @var string
	 */
	public const CACHE_GROUP = 'prc_fact_sheet_collection';

	/**
	 * Cache TTL for assembled collection render data.
	 *
	 * @var int
	 */
	public const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Taxonomy
	 *
	 * @var string
	 */
	public static $taxonomy = 'collection';

	/**
	 * Post type
	 *
	 * @var string
	 */
	public static $post_type = 'fact-sheet';

	/**
	 * Languages
	 *
	 * @var array
	 */
	public $languages = array();

	/**
	 * Constructor
	 *
	 * @param mixed $loader Loader.
	 */
	public function __construct( $loader ) {
		$this->init( $loader );
	}

	/**
	 * Initialize the block
	 *
	 * @param mixed $loader Loader.
	 */
	public function init( $loader = null ) {
		if ( null !== $loader ) {
			$loader->add_action( 'init', $this, 'get_languages', 10 );
			$loader->add_action( 'init', $this, 'block_init', 11 );
			$loader->add_action( 'prc_platform_on_update', $this, 'clear_cache_on_update', 10, 1 );
			$loader->add_action( 'prc_platform_on_publish', $this, 'clear_cache_on_update', 10, 1 );
			$loader->add_action( 'set_object_terms', $this, 'clear_cache_on_term_assignment', 10, 6 );
			$loader->add_action( 'edited_term', $this, 'clear_cache_on_term_edit', 10, 3 );
		}
	}

	/**
	 * Build the cache key for a fact-sheet render payload.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_cache_key( int $post_id ): string {
		$visibility = is_user_logged_in() ? 'auth' : 'guest';
		return 'fact_sheet_collection_' . $post_id . '_' . $visibility;
	}

	/**
	 * Delete cached render data for a fact sheet.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function clear_cache_for_post( int $post_id ): void {
		wp_cache_delete( 'fact_sheet_collection_' . $post_id . '_guest', self::CACHE_GROUP );
		wp_cache_delete( 'fact_sheet_collection_' . $post_id . '_auth', self::CACHE_GROUP );
	}

	/**
	 * Clear cache when a fact sheet is updated or first published.
	 *
	 * Also clears sibling fact sheets that embed shared collection / alt-language data.
	 *
	 * @hook prc_platform_on_update
	 * @hook prc_platform_on_publish
	 * @param object $post Extended WP_Post-like object from the pipeline.
	 * @return void
	 */
	public function clear_cache_on_update( $post ): void {
		// Pipeline passes stdClass from setup_extra_wp_post_object_fields(), not WP_Post.
		if ( ! is_object( $post ) || empty( $post->ID ) || empty( $post->post_type ) || self::$post_type !== $post->post_type ) {
			return;
		}
		$this->clear_related_collection_caches( (int) $post->ID );
	}

	/**
	 * Clear cache when collection or language terms change on a fact sheet.
	 *
	 * @hook set_object_terms
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Term IDs.
	 * @param array  $tt_ids     Term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether terms were appended.
	 * @param array  $old_tt_ids Old term taxonomy IDs.
	 * @return void
	 */
	public function clear_cache_on_term_assignment( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ): void {
		unset( $terms, $tt_ids, $append );
		if ( ! in_array( $taxonomy, array( self::$taxonomy, 'languages' ), true ) ) {
			return;
		}
		$post = get_post( (int) $object_id );
		if ( ! $post instanceof WP_Post || self::$post_type !== $post->post_type ) {
			return;
		}
		$this->clear_related_collection_caches( (int) $post->ID );

		// Invalidate former collection siblings when a fact sheet moves between terms.
		if ( self::$taxonomy === $taxonomy && ! empty( $old_tt_ids ) ) {
			foreach ( (array) $old_tt_ids as $tt_id ) {
				$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, self::$taxonomy );
				if ( $term instanceof WP_Term ) {
					$this->clear_caches_for_collection_branch( (int) $term->term_id );
				}
			}
		}
	}

	/**
	 * Clear caches when a collection term is renamed or re-parented.
	 *
	 * @hook edited_term
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function clear_cache_on_term_edit( $term_id, $tt_id, $taxonomy ): void {
		unset( $tt_id );
		if ( self::$taxonomy !== $taxonomy ) {
			return;
		}
		$this->clear_caches_for_collection_branch( (int) $term_id );
	}

	/**
	 * Clear render caches for a fact sheet and siblings that share its collection branch.
	 *
	 * Cached payloads include parent/child term labels and alt-language links derived
	 * from other posts in the same collection hierarchy.
	 *
	 * @param int $post_id Fact sheet post ID.
	 * @return void
	 */
	private function clear_related_collection_caches( int $post_id ): void {
		self::clear_cache_for_post( $post_id );

		$terms = wp_get_post_terms( $post_id, self::$taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		$collection_term = $terms[0];
		if ( ! $collection_term instanceof WP_Term ) {
			return;
		}

		$branch_root = $collection_term->parent ? (int) $collection_term->parent : (int) $collection_term->term_id;
		$this->clear_caches_for_collection_branch( $branch_root );
	}

	/**
	 * Clear render caches for all fact sheets under a collection term branch.
	 *
	 * @param int $term_id Collection term ID (parent or child).
	 * @return void
	 */
	private function clear_caches_for_collection_branch( int $term_id ): void {
		if ( $term_id <= 0 ) {
			return;
		}

		$term = get_term( $term_id, self::$taxonomy );
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$branch_root = $term->parent ? (int) $term->parent : (int) $term->term_id;
		$term_ids    = get_term_children( $branch_root, self::$taxonomy );
		if ( is_wp_error( $term_ids ) ) {
			$term_ids = array();
		}
		$term_ids[] = $branch_root;
		$term_ids[] = (int) $term->term_id;
		$term_ids   = array_values( array_unique( array_map( 'intval', $term_ids ) ) );

		$posts = get_posts(
			array(
				'post_type'              => self::$post_type,
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => self::$taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_ids,
					),
				),
			)
		);

		foreach ( $posts as $id ) {
			self::clear_cache_for_post( (int) $id );
		}
	}

	/**
	 * Get collection term post link
	 *
	 * @param int $term_id Term ID.
	 * @return string|false
	 */
	public function get_collection_term_post_link( $term_id ) {
		$matched_post_id = get_term_meta( $term_id, 'tds_post_id', true );
		if ( $matched_post_id ) {
			return get_permalink( $matched_post_id );
		}
		return false;
	}

	/**
	 * Get languages
	 *
	 * @return void
	 */
	public function get_languages() {
		// get all the languages terms slugs even if they're empty
		$languages       = get_terms(
			array(
				'taxonomy'   => 'languages',
				'hide_empty' => false,
				'fields'     => 'slugs',
			)
		);
		$this->languages = is_wp_error( $languages ) ? array() : $languages;
	}

	/**
	 * Get english language link
	 *
	 * @param int $term_id Term ID.
	 * @return string|false
	 */
	public function get_english_language_link( $term_id ) {
		$post_status = array( 'publish', 'hidden_from_index' );
		if ( is_user_logged_in() ) {
			$post_status[] = 'draft';
			$post_status[] = 'private';
		}
		$languages_to_filter_out = is_array( $this->languages ) ? $this->languages : array();
		$languages_to_filter_out = array_diff( $languages_to_filter_out, array( 'en' ) );
		// If this child term has english posts then use the english post link, otherwise fallback to the child term link.
		$english_post = get_posts(
			array(
				'posts_per_page' => 1,
				'post_type'      => self::$post_type,
				'post_status'    => $post_status,
				'fields'         => 'ids', // 'ids' returns an array of post IDs instead of full post objects.
				'tax_query'      => array(
					'relation' => 'AND',
					array(
						'taxonomy' => self::$taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
					array(
						'taxonomy' => 'languages',
						'field'    => 'slug',
						'operator' => 'NOT IN',
						'terms'    => $languages_to_filter_out,
					),
				),
			)
		);
		if ( ! empty( $english_post ) ) {
			$english_post = array_pop( $english_post );
			return get_permalink( $english_post );
		}
		return false;
	}

	/**
	 * Get alt language posts
	 *
	 * @param int   $term_id Term ID.
	 * @param array $exclude_posts Exclude posts.
	 * @return array
	 */
	public function get_alt_language_posts( $term_id, $exclude_posts = array() ) {
		$post_status = array( 'publish' );
		if ( is_user_logged_in() ) {
			$post_status[] = 'draft';
			$post_status[] = 'private';
		}
		$args = array(
			'posts_per_page' => 50,
			'post_type'      => self::$post_type,
			'post__not_in'   => $exclude_posts,
			'post_status'    => $post_status,
			'tax_query'      => array(
				array(
					'taxonomy' => self::$taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
				array(
					'taxonomy' => 'languages',
					'field'    => 'slug',
					'terms'    => array( 'en' ),
					'operator' => 'NOT IN',
				),
			),
		);
		// get all the other NON en language posts that have the child_term->term_id in the collection taxonomy.
		return get_posts( $args );
	}

	/**
	 * Get collection
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_collection( $post_id ) {
		$collection_terms = wp_get_post_terms( $post_id, self::$taxonomy );
		if ( empty( $collection_terms ) ) {
			return array(
				'parent_term' => null,
				'child_terms' => array(),
			);
		}
		// Get the first term out of the collection_terms array.
		$collection_term = array_shift( $collection_terms );
		if ( ! $collection_term instanceof WP_Term ) {
			return array(
				'parent_term' => null,
				'child_terms' => array(),
			);
		}

		$parent_term      = get_term( $collection_term->parent, self::$taxonomy );
		$parent_term_id   = isset( $parent_term->term_id ) ? $parent_term->term_id : null;
		$parent_term_link = $this->get_collection_term_post_link( $parent_term_id );
		$parent_term      = array(
			'term_id' => isset( $parent_term->term_id ) ? $parent_term->term_id : null,
			'name'    => isset( $parent_term->name ) ? $parent_term->name : '',
			'link'    => isset( $parent_term_link ) ? $parent_term_link : '',
		);

		// Get the parent term's children, returns a list of term names, ids, links, prioritizes a link back to an english post if it exists.
		$child_terms = get_term_children( $parent_term_id, self::$taxonomy );
		$child_terms = array_map(
			function ( $term_id ) use ( $collection_term ) {
				$child_term = get_term( $term_id, self::$taxonomy );
				if ( ! $child_term instanceof WP_Term ) {
					return;
				}
				$english_link = $this->get_english_language_link( $child_term->term_id );
				$link         = $english_link ? $english_link : $this->get_collection_term_post_link( $child_term->term_id );
				$child_term   = array(
					'term_id' => $child_term->term_id,
					'name'    => $child_term->name,
					'link'    => $link,
				);
				return $child_term;
			},
			$child_terms
		);

		// Filter out any null values.
		$child_terms = array_filter( $child_terms );
		// Sort the child terms by name.
		usort(
			$child_terms,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return array(
			'collection_term' => $collection_term,
			'parent_term'     => $parent_term,
			'child_terms'     => $child_terms,
		);
	}

	/**
	 * Get assembled collection + alt-language data for render, with object cache.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null
	 */
	public function get_render_collection_data( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return null;
		}

		$use_cache = ! is_user_logged_in() && ! is_preview();
		$cache_key = self::get_cache_key( $post_id );

		if ( $use_cache ) {
			$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}
		}

		$collection = $this->get_collection( $post_id );
		if ( empty( $collection ) || ! isset( $collection['collection_term'] ) ) {
			return null;
		}

		$collection_term    = $collection['collection_term'];
		$alt_language_posts = $this->get_alt_language_posts( $collection_term->term_id, array( $post_id ) );
		$alt_languages      = array();

		foreach ( $alt_language_posts as $other_language_post ) {
			if ( ! $other_language_post instanceof WP_Post ) {
				continue;
			}
			$language_terms = wp_get_post_terms( $other_language_post->ID, 'languages' );
			$language_term  = is_array( $language_terms ) ? array_shift( $language_terms ) : false;
			if ( ! $language_term instanceof WP_Term ) {
				continue;
			}
			$alt_languages[] = array(
				'permalink' => get_permalink( $other_language_post->ID ),
				'language'  => $language_term->name,
			);
		}

		$payload = array(
			'collection_term_id' => $collection_term->term_id,
			'parent_term'        => $collection['parent_term'],
			'child_terms'        => $collection['child_terms'],
			'alt_languages'      => $alt_languages,
		);

		if ( $use_cache ) {
			wp_cache_set( $cache_key, $payload, self::CACHE_GROUP, self::CACHE_TTL );
		}

		return $payload;
	}

	/**
	 * Render block callback
	 *
	 * @param array  $attributes Attributes.
	 * @param string $content Content.
	 * @param object $block Block.
	 * @return string
	 */
	public function render_block_callback( $attributes, $content, $block ) {
		if ( is_admin() ) {
			return;
		}
		$post_id = get_the_ID();
		$cached  = $this->get_render_collection_data( $post_id );
		if ( null === $cached ) {
			return;
		}

		$collection_term_id = $cached['collection_term_id'];
		$parent_term        = $cached['parent_term'];
		$child_terms        = $cached['child_terms'];
		$collection         = array(
			'terms'           => array_map(
				function ( $child_term ) use ( $collection_term_id ) {
					return wp_sprintf(
						'<a href="%1$s" class="%2$s">%3$s</a>',
						$child_term['link'],
						\PRC\BlockUtils\classNames(
							'wp-block-prc-block-fact-sheet-collection--term-link',
							array(
								'is-active' => $child_term['term_id'] === $collection_term_id,
							)
						),
						$child_term['name'],
					);
				},
				$child_terms
			),
			'alt_languages'   => array_map(
				function ( $alt_language ) {
					return wp_sprintf(
						'<a href="%1$s" class="wp-block-prc-block-fact-sheet-collection__term-link__alt-language-link">%2$s</a>',
						$alt_language['permalink'],
						$alt_language['language'],
					);
				},
				$cached['alt_languages']
			),
			'collection_name' => $parent_term['name'],
			'collection_link' => $parent_term['link'],
		);

		$pdf = array_key_exists( 'pdf', $attributes ) ? $attributes['pdf'] : null;
		if ( $pdf ) {
			$pdf_id = $pdf['id'];
			$pdf    = wp_get_attachment_url( $pdf_id );
			$pdf    = wp_sprintf(
				'<a href="%1$s" class="wp-block-prc-block-fact-sheet-collection--pdf-link" download><i class="file pdf icon"></i> %2$s</a>',
				$pdf,
				__( 'Download PDF', 'prc-block-library' ),
			);
		}

		$block_wrapper_attrs = get_block_wrapper_attributes();

		$disable_heading = array_key_exists( 'disableHeading', $attributes ) ? $attributes['disableHeading'] : false;

		return wp_sprintf(
			'<div %1$s>%2$s%3$s<div class="wp-block-prc-block-fact-sheet-collection--term-list">%4$s</div>%5$s</div>',
			$block_wrapper_attrs,
			$disable_heading ? '' : wp_sprintf(
				'<div class="wp-block-prc-block-fact-sheet-collection--parent-term"><a href="%1$s">%2$s</a></div>',
				! is_wp_error( $collection['collection_link'] ) ? $collection['collection_link'] : '',
				! is_wp_error( $collection['collection_name'] ) ? $collection['collection_name'] : '',
			),
			! empty( $collection['alt_languages'] ) ? wp_sprintf(
				'<div class="wp-block-prc-block-fact-sheet-collection--alt-languages">%1$s</div>',
				implode( '', $collection['alt_languages'] ),
			) : '',
			implode( '', $collection['terms'] ),
			$pdf,
		);
	}

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 * Behind the scenes, it registers also all assets so they can be enqueued
	 * through the block editor in the corresponding context.
	 *
	 * @hook init
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	public function block_init() {
		register_block_type_from_metadata(
			PRC_COLLECTIONS_DIR . '/build/fact-sheet-collection',
			array(
				'render_callback' => array( $this, 'render_block_callback' ),
			)
		);
	}
}
