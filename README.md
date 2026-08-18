# PRC Collections

> Canonical docs: [docs/plugins/prc-collections/](../../docs/plugins/prc-collections/)

Registers a hybrid `collections` post type and `collection` taxonomy that allows editors to curate groups of PRC content sharing common themes, research initiatives, or special projects (e.g., the Religious Landscape Study). The post type and taxonomy are linked via [`prc/term-data-store`](https://github.com/pewresearch/term-data-store) (namespace `PRC\TDS`), so each collection post has a corresponding taxonomy term used to tag associated content.

## What it does

- Registers a hierarchical `collections` custom post type with archive support, full REST API exposure, and a `prc-block/grid-controller` default block template
- Registers a hierarchical `collection` taxonomy applied to all public post types that declare `prc-collections` support (initially `post` and `feature`)
- Establishes a `prc/term-data-store` relationship between the `collections` post type and the `collection` taxonomy — each collection post maps to a term, enabling content to be tagged and queried by collection
- Registers a `kicker_pattern_slug` post meta field on collection posts, pointing to a Site Editor template part used as the collection's visual "kicker" (bug/label)
- Adds a `kicker` template part area to the Site Editor so kicker template parts can be managed separately from other template parts
- Provides an editor sidebar panel (Document Settings) on collection posts to assign a kicker template part via a combobox picker
- Modifies `pre_get_posts` for publication listing queries on collection pages to automatically filter posts by the corresponding collection term, while excluding the collection post itself
- Sets `hidden-on-index` as the default `_post_visibility` term when a collection post is first published
- Registers two blocks: `prc-block/collection-kicker` and `prc-block/fact-sheet-collection`

## Key files

| File | Purpose |
|------|---------|
| `prc-collections.php` | Plugin entry point; defines constants, registers activation/deactivation hooks, bootstraps the plugin |
| `includes/class-plugin.php` | Orchestrates dependency loading, block metadata registration, and block class initialization |
| `includes/class-content-type.php` | Registers the `collections` post type, `collection` taxonomy, kicker meta, `prc/term-data-store` relationship, template part area, and query modification |
| `includes/class-loader.php` | Hook registration utility used internally to defer add_action/add_filter calls |
| `includes/utils.php` | Namespace placeholder; currently empty |
| `includes/class-prc-collections-activator.php` | Activation hook handler |
| `includes/class-prc-collections-deactivator.php` | Deactivation hook handler |
| `includes/inspector-sidebar-panel/src/index.js` | Registers the `prc-platform--collections` editor plugin; renders the Document Settings panel on collection posts |
| `includes/inspector-sidebar-panel/src/control-template-part-kicker.jsx` | Combobox UI for selecting a kicker template part; links to the Site Editor kicker category |
| `includes/inspector-sidebar-panel/src/use-kicker-template-part.js` | Custom hook that fetches all `wp_template_part` records and filters for those in the `kicker` area |
| `src/collection-kicker/class-collection-kicker.php` | Server render callback for `prc-block/collection-kicker`; resolves the collection term, reads `kicker_pattern_slug`, and outputs the template part content via `do_blocks()` |
| `src/fact-sheet-collection/class-fact-sheet-collection.php` | Server render callback for `prc-block/fact-sheet-collection`; builds the collection hierarchy for fact sheets with per-language links and an optional PDF download link |

## Filters / hooks

| Hook | Direction | Description |
|------|-----------|-------------|
| `init` (priority 5) | Action | Registers `prc-collections` post type support for `post` and `feature`; adds `prc-bylines`, `prc-art-direction`, `prc-sitemap`, and `prc-publication-listing` support to the `collections` post type; registers the `collections` post type and kicker post meta so later `init` meta scanners can discover it |
| `init` (priority 20) | Action | Registers the `collection` taxonomy and `prc/term-data-store` relationship after other PRC CPTs register on default `init`. |
| `default_wp_template_part_areas` (priority 11) | Filter | Adds a `kicker` area to the Site Editor template part area list |
| `pre_get_posts` (priority 100) | Action | On publication listing queries for a `collections` page, injects a `tax_query` for the mapped collection term and excludes the collection post itself from results |
| `prc_platform_on_publish` (priority 10) | Action | Sets `hidden-on-index` as the `_post_visibility` term for newly published collection posts that have no existing visibility terms |
| `enqueue_block_editor_assets` | Action | Enqueues the inspector sidebar panel JS when editing a `collections` post |
| `prc_platform__collections_enabled_post_types` | Filter | Allows other plugins to add post types to the list of types that receive the `collection` taxonomy. Merged with post types that declare `add_post_type_support( $type, 'prc-collections' )` |

## Blocks registered

| Block | Name | Render |
|-------|------|--------|
| Collection Kicker | `prc-block/collection-kicker` | Dynamic (PHP). Resolves the `kicker_pattern_slug` meta for the associated collection post and renders the corresponding `kicker`-area template part via `do_blocks()`. Results are cached for 1 hour per term using the `prc_collection_kicker` object cache group. |
| Fact Sheet Collection | `prc-block/fact-sheet-collection` | Dynamic (PHP). Displays the collection hierarchy (parent term + sibling terms) for a fact sheet post. Primary links target English-language posts; alternate language posts are listed separately. Optional PDF download link via a media attachment attribute. |

### Fact Sheet Collection block attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `pdf` | object | Attachment object with an `id` field; generates a PDF download link when set |
| `disableHeading` | boolean | Suppresses the parent collection name/link heading when `true` |

### Collection Kicker block attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `termId` | integer | Explicit collection term to render a kicker for. Falls back to the first `collection` term on the current post if omitted. |

## Post types and taxonomies

| Object | Type | Slug | Notes |
|--------|------|------|-------|
| Collections | Post type | `collections` | Public, hierarchical, REST-enabled. Archive at `/collections/`. Supports title, editor, excerpt, revisions, custom-fields, page-attributes, thumbnail, prc-schema-seo. Default block template: `prc-block/grid-controller`. |
| Collection | Taxonomy | `collection` | Public, hierarchical, REST-enabled. No URL rewrite — taxonomy terms do not have their own front-end archive pages. Applied to all post types that declare `prc-collections` support. |

## Post meta

| Meta key | Post type | Type | REST | Description |
|----------|-----------|------|------|-------------|
| `kicker_pattern_slug` | `collections` | string | Yes | Slug of the `wp_template_part` to use as this collection's kicker. Managed via the editor sidebar panel. |

## Term Data Store relationship

The plugin calls `\PRC\TDS\add_relationship( 'collections', 'collection', false )` on `init`. This links each `collections` post to a `collection` taxonomy term (stored as `tds_post_id` in term meta and `tds_term_id` in post meta). Automatic permalink rewriting is disabled (`false` as the third argument) because collections manage their own permalink structure.

The `filter_self_reference_out` method uses `\PRC\TDS\get_related_term( $post )` to look up the term for the current collection page, then injects a `tax_query` into any `isPubListingQuery`-flagged query.

## Editor sidebar panel

The `prc-platform--collections` plugin is registered as a `PluginDocumentSettingPanel` and appears only when editing a `collections` post. It provides a combobox to select a kicker template part. The combobox is populated with all `wp_template_part` records whose `area` is `kicker` or whose title contains "kicker".

To create or edit kicker template parts, navigate to:

```
/wp-admin/site-editor.php?path=%2Fpatterns&categoryType=wp_template_part&categoryId=kicker
```

## Extending: adding post type support

To opt a custom post type into the `collection` taxonomy without modifying this plugin:

```php
add_post_type_support( 'my-post-type', 'prc-collections' );
```

Alternatively, use the filter (legacy, prefer `add_post_type_support`):

```php
add_filter( 'prc_platform__collections_enabled_post_types', function( $types ) {
    $types[] = 'my-post-type';
    return $types;
} );
```

## Dependencies

- `prc-platform-core` (required plugin)
- `prc/block-utils` (via `prc-platform-core`) — `\PRC\BlockUtils\load_blocks()` and `classNames()` used at runtime; blocks will not register if the function is unavailable
- [`prc/term-data-store`](https://github.com/pewresearch/term-data-store) (`PRC\TDS` namespace) — `\PRC\TDS\add_relationship()` and `\PRC\TDS\get_related_term()` must be available on `init`
- `prc-platform-core` platform helper `\PRC\Platform\get_wp_admin_current_post_type()` — used to conditionally enqueue the editor sidebar panel

## Build

From the repo root:

```bash
# Build blocks and inspector panel
npm run build -w @prc/collections

# Development (watch mode — blocks only)
npm run start:blocks -w @prc/collections

# Development (watch mode — inspector panel only)
npm run start:inspector-panel -w @prc/collections
```

Two separate webpack entries are built independently:

- **Blocks** — compiled from `src/` via `wp-scripts build --experimental-modules --webpack-copy-php` into `build/`
- **Inspector panel** — compiled from `includes/inspector-sidebar-panel/src/` into `includes/inspector-sidebar-panel/build/`
