<?php
// This file is generated. Do not modify it manually.
return array(
	'collection-kicker' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-block/collection-kicker',
		'version' => '1.0.0',
		'title' => 'Collection Kicker',
		'category' => 'theme',
		'description' => 'Display the collection term\'s kicker. You can choose a specific term, or default to the current post\'s term.',
		'attributes' => array(
			'termId' => array(
				'type' => 'integer'
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false
		),
		'usesContext' => array(
			'postType',
			'postId',
			'templateSlug',
			'previewPostType'
		),
		'textdomain' => 'collection-kicker',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	),
	'fact-sheet-collection' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-block/fact-sheet-collection',
		'version' => '1.0.0',
		'title' => 'Fact Sheet Collection',
		'category' => 'design',
		'description' => 'Display this fact sheet\'s collection hierarchy and provide a PDF download link if available.
If the collection has posts in multiple languages, the main link will point to the English post, with alternate language links listed above it.',
		'attributes' => array(
			'pdf' => array(
				'type' => 'object'
			),
			'disableHeading' => array(
				'type' => 'boolean',
				'default' => false
			),
			'style' => array(
				'type' => 'object',
				'default' => array(
					'spacing' => array(
						'blockGap' => 'var:preset|spacing|20'
					)
				)
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'color' => array(
				'background' => true,
				'text' => false,
				'link' => true
			),
			'spacing' => array(
				'blockGap' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'__experimentalFontFamily' => true
				)
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'example' => array(
			'attributes' => array(
				
			)
		),
		'styles' => array(
			array(
				'name' => 'list',
				'label' => 'List',
				'isDefault' => true
			)
		),
		'textdomain' => 'collection',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	)
);
