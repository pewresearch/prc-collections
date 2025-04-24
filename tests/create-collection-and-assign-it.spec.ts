import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const testTitle = 'Test Collection';
const testContent = 'This is a test collection';

test.describe('Create Collection', () => {
	test('Ensure collection post type is properly registered', async ({
		requestUtils,
	}) => {
		const collectionPosts = await requestUtils.rest({
			path: '/wp/v2/collections',
			method: 'GET',
		});
		expect(collectionPosts).toBeDefined();
	});

	test('Ensure collections taxonomy is properly registered', async ({
		requestUtils,
	}) => {
		const collectionsTerms = await requestUtils.rest({
			path: '/wp/v2/collection',
			method: 'GET',
		});
		expect(collectionsTerms).toBeDefined();
	});

	test('Collection post created', async ({ admin, editor, requestUtils }) => {
		await admin.createNewPost({
			title: testTitle,
			content: testContent,
			postType: 'collections',
		});
		// Publish the collection
		await editor.publishPost();

		// Get the created collection via REST API
		const collectionPosts = await requestUtils.rest({
			path: '/wp/v2/collections',
			method: 'GET',
		});
		// Get the first item out of the collectionPosts array
		const collectionPost = collectionPosts?.[0];
		// Verify the collection was created with correct title and content
		expect(collectionPost.title.rendered).toBe(testTitle);
	});

	test('Matching collections term created with collection post', async ({
		requestUtils,
	}) => {
		const collectionsTerms = await requestUtils.rest({
			path: '/wp/v2/collection',
			method: 'GET',
		});
		// Get the first item out of the collectionsTerms array
		const collectionsTerm = collectionsTerms?.[0];
		// Verify the collections term was created with correct title and content
		expect(collectionsTerm.name).toBe(testTitle);
	});

	test('Publish new post with collection term', async ({
		admin,
		editor,
		page,
		requestUtils,
	}) => {
		await admin.createNewPost({
			title: 'Test Post',
			content: 'This is a test post',
			postType: 'post',
		});

		await page.getByRole('button', { name: 'Collections' }).click();
		await page.getByLabel(testTitle).first().click();

		// Publish the posts.
		await editor.publishPost();

		// Confirm the post has a collections term in the rest api
		const testPosts = await requestUtils.rest({
			path: '/wp/v2/posts',
			method: 'GET',
		});
		// Get the first item out of the testPosts array
		const testPost = testPosts?.[0];
		// Verify the post has a collections term in the rest api
		expect(testPost.collection).toHaveLength(1);
	});
});
