/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line no-restricted-imports
import {
	createInterpolateElement,
	useState,
	useEffect,
	useRef,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { plus } from '@wordpress/icons';
import {
	Button,
	ComboboxControl,
	ExternalLink,
	Notice,
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line
	__experimentalHStack as HStack, // eslint-disable-line
} from '@wordpress/components';

/**
 * Internal Dependencies
 */
import useKickerTemplatePart from './use-kicker-template-part';

function getUniqueTitle(title, existingRecords) {
	const lower = title.toLowerCase();
	const existing = (existingRecords || []).map((r) =>
		r.title?.rendered ? r.title.rendered.toLowerCase() : ''
	);
	if (!existing.includes(lower)) {
		return title;
	}
	let suffix = 2;
	while (existing.includes(`${lower} ${suffix}`)) {
		suffix++;
	}
	return `${title} ${suffix}`;
}

function getCleanSlugFromTitle(title) {
	const fromTitle = title
		.trim()
		.toLowerCase()
		.replace(/\s+/g, '-')
		.replace(/[^a-z0-9-]/g, '');
	return fromTitle || 'kicker-part';
}

function getUniqueSlug(baseSlug, existingRecords) {
	const slugs = new Set(
		(existingRecords || []).map((r) => (r.slug ? String(r.slug) : ''))
	);
	if (!slugs.has(baseSlug)) {
		return baseSlug;
	}
	let suffix = 2;
	while (slugs.has(`${baseSlug}-${suffix}`)) {
		suffix++;
	}
	return `${baseSlug}-${suffix}`;
}

export default function KickerTemplatePartControl({
	kickerSlug,
	onChange = () => {},
}) {
	const { siteUrl } = window.prcPlatform;
	const kickerTemplateUrl = siteUrl
		? `${siteUrl}/wp-admin/site-editor.php?path=%2Fpatterns&categoryType=wp_template_part&categoryId=kicker`
		: '';

	const {
		kickerOptions,
		hasKickers,
		selectedKickerAndExists,
		kickerId,
		records,
		hasResolved,
	} = useKickerTemplatePart({
		kickerSlug,
		setKickerSlug: (newVal) => onChange(newVal),
	});

	const { saveEntityRecord, invalidateResolution } = useDispatch(coreStore);
	const { createSuccessNotice, createErrorNotice } =
		useDispatch(noticesStore);

	const [isCreating, setIsCreating] = useState(false);
	const [newKickerTitle, setNewKickerTitle] = useState('');
	const [isSaving, setIsSaving] = useState(false);
	const [pendingSlug, setPendingSlug] = useState(null);
	const onChangeRef = useRef(onChange);
	useEffect(() => {
		onChangeRef.current = onChange;
	});

	useEffect(() => {
		if (!pendingSlug || !records) {
			return;
		}
		const found = records.find((r) => r.slug === pendingSlug);
		if (found) {
			onChangeRef.current(pendingSlug);
			setPendingSlug(null);
		}
	}, [pendingSlug, records]);

	const siteEditorEditUrl =
		siteUrl && kickerId
			? `${siteUrl}/wp-admin/site-editor.php?p=${encodeURIComponent(
					`/wp_template_part/${kickerId}`
			  )}&canvas=edit`
			: '';

	const handleCreateKicker = async () => {
		const trimmed = newKickerTitle.trim();
		if (!trimmed) {
			return;
		}
		setIsSaving(true);
		try {
			const uniqueTitle = getUniqueTitle(trimmed, records);
			const baseSlug = getCleanSlugFromTitle(uniqueTitle);
			const slug = getUniqueSlug(baseSlug, records);

			await saveEntityRecord(
				'postType',
				'wp_template_part',
				{
					slug,
					title: uniqueTitle,
					content: '',
					area: 'kicker',
				},
				{ throwOnError: true }
			);

			invalidateResolution('getEntityRecords', [
				'postType',
				'wp_template_part',
				{ per_page: -1 },
			]);

			setPendingSlug(slug);
			setNewKickerTitle('');
			setIsCreating(false);
			createSuccessNotice(
				__('Kicker template created.', 'kicker-control'),
				{
					type: 'snackbar',
				}
			);
		} catch (error) {
			const message =
				error?.message ||
				__('Could not create kicker template.', 'kicker-control');
			createErrorNotice(message, {
				type: 'snackbar',
			});
		} finally {
			setIsSaving(false);
		}
	};

	const handleCancelCreate = () => {
		setIsCreating(false);
		setNewKickerTitle('');
	};

	// Notice for when no kickers have been created.
	const noKickersNotice = (
		<Notice status="warning" isDismissible={false}>
			{createInterpolateElement(
				__(
					'No kicker templates could be found. Create a new one in the <a>Site Editor</a>.',
					'kicker-control'
				),
				{
					a: (
						<a // eslint-disable-line
							href={kickerTemplateUrl}
							target="_blank"
							rel="noreferrer"
						/>
					),
				}
			)}
		</Notice>
	);

	// Notice for when the selected kicker template no longer exists.
	const kickerDoesntExistNotice = (
		<Notice status="warning" isDismissible={false}>
			{__(
				'The selected kicker template no longer exists. Choose another.',
				'kicker-control'
			)}
		</Notice>
	);

	return (
		<VStack spacing="2">
			<ComboboxControl
				label={__('Kicker Template', 'kicker-control')}
				value={kickerSlug}
				options={kickerOptions}
				onChange={onChange}
				disabled={!hasResolved}
				help={
					hasKickers &&
					createInterpolateElement(
						__(
							'Design and customize kicker templates in the <a>Site Editor</a>. A "kicker" or otherwise known as a "bug" is a visual design element that combines a distinctive icon with styled text, serving as a navigational shortcut to collection pages. These elements help readers quickly identify and access themed content collections across the site.',
							'kicker-control'
						),
						{
							a: (
								<a // eslint-disable-line
									href={kickerTemplateUrl}
									target="_blank"
									rel="noreferrer"
								/>
							),
						}
					)
				}
			/>
			<VStack spacing="3">
				{kickerSlug && kickerId && siteEditorEditUrl ? (
					<ExternalLink href={siteEditorEditUrl}>
						{__('Edit in Site Editor', 'kicker-control')}
					</ExternalLink>
				) : null}
				<Button
					variant="tertiary"
					icon={plus}
					onClick={() => setIsCreating((open) => !open)}
					aria-expanded={isCreating}
				>
					{__('Create new kicker', 'kicker-control')}
				</Button>
			</VStack>
			{isCreating ? (
				<VStack spacing="2">
					<TextControl
						label={__('Name', 'kicker-control')}
						value={newKickerTitle}
						onChange={setNewKickerTitle}
						onKeyDown={(event) => {
							if (event.key === 'Enter' && !isSaving) {
								event.preventDefault();
								handleCreateKicker();
							}
						}}
					/>
					<HStack spacing="2">
						<Button
							variant="primary"
							onClick={handleCreateKicker}
							isBusy={isSaving}
							disabled={isSaving || !newKickerTitle.trim()}
						>
							{__('Create', 'kicker-control')}
						</Button>
						<Button
							variant="secondary"
							onClick={handleCancelCreate}
							disabled={isSaving}
						>
							{__('Cancel', 'kicker-control')}
						</Button>
					</HStack>
				</VStack>
			) : null}
			{!hasKickers && noKickersNotice}
			{hasKickers && !selectedKickerAndExists && kickerDoesntExistNotice}
		</VStack>
	);
}
