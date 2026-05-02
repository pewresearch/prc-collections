/**
 * External Dependencies
 */

/**
 * WordPress Dependencies
 */
import { useMemo, useCallback } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import {
	useEntityProp,
	useEntityRecords,
	useEntityRecord,
} from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	ComboboxControl,
	ExternalLink,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal Dependencies
 */
import KickerTemplatePartControl from './control-template-part-kicker';

const PLUGIN_NAME = 'prc-platform--collections';

export default function CollectionPanel() {
	const { postType, postId } = useSelect((select) => {
		const currentPostType = select(editorStore).getCurrentPostType();
		const currentPostId = select(editorStore).getCurrentPostId();
		return {
			postType: currentPostType,
			postId: currentPostId,
		};
	}, []);

	const [meta, setMeta] = useEntityProp('postType', postType, 'meta', postId);

	const kickerSlug = meta?.kicker_pattern_slug ?? '';
	const updateKickerSlug = (newSlug) =>
		setMeta({
			...(meta || {}),
			kicker_pattern_slug: newSlug,
		});

	const [postParent, setPostParent] = useEntityProp(
		'postType',
		postType,
		'post_parent',
		postId
	);

	const { records: topLevelCollections, hasResolved } = useEntityRecords(
		'postType',
		'collections',
		{
			parent: 0,
			per_page: -1,
			status: 'publish,draft,pending,private,future',
			orderby: 'title',
			order: 'asc',
			exclude: postId ? [postId] : undefined,
		}
	);

	const { record: currentParentPost, hasResolved: parentPostResolved } =
		useEntityRecord('postType', 'collections', postParent, {
			enabled: Boolean(postParent),
		});

	const parentOptions = useMemo(() => {
		const opts = [
			{
				label: __('None (top-level collection)', 'prc-collections'),
				value: '0',
			},
		];
		if (topLevelCollections?.length) {
			for (const p of topLevelCollections) {
				const title = p.title?.rendered
					? decodeEntities(p.title.rendered)
					: `#${p.id}`;
				opts.push({
					label: title,
					value: String(p.id),
				});
			}
		}
		if (postParent && !opts.some((o) => o.value === String(postParent))) {
			const label =
				parentPostResolved && currentParentPost?.title?.rendered
					? decodeEntities(currentParentPost.title.rendered)
					: __('(current parent)', 'prc-collections');
			opts.push({
				label,
				value: String(postParent),
			});
		}
		return opts;
	}, [
		topLevelCollections,
		postParent,
		parentPostResolved,
		currentParentPost,
	]);

	const onParentChange = useCallback(
		(val) => {
			const next = !val || val === '0' ? 0 : parseInt(val, 10);
			if (Number.isNaN(next)) {
				return;
			}
			setPostParent(next);
		},
		[setPostParent]
	);

	const parentValue = postParent ? String(postParent) : '0';

	const parentPermalink =
		typeof currentParentPost?.link === 'string'
			? currentParentPost.link
			: '';

	return (
		<PluginDocumentSettingPanel
			name={PLUGIN_NAME}
			title={__('Collection', 'prc-collections')}
		>
			<VStack spacing="2">
				<ComboboxControl
					label={__('Parent collection', 'prc-collections')}
					help={__(
						'Choose a top-level collection to nest this collection under. Saving the post updates the linked taxonomy hierarchy.',
						'prc-collections'
					)}
					value={parentValue}
					options={parentOptions}
					onChange={onParentChange}
					__nextHasNoMarginBottom
					disabled={!hasResolved}
				/>
				{postParent > 0 && parentPostResolved && parentPermalink ? (
					<p className="components-base-control__help">
						<ExternalLink href={parentPermalink}>
							{__('View parent collection', 'prc-collections')}
						</ExternalLink>
					</p>
				) : null}
				<KickerTemplatePartControl
					kickerSlug={kickerSlug}
					onChange={(value) => updateKickerSlug(value)}
				/>
			</VStack>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin(PLUGIN_NAME, {
	render: CollectionPanel,
});
