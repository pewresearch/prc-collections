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
		'description' => 'Display the \'kicker\' for a collection term. You can select a specific term or default behavior is to display the term for the current post.',
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
		'description' => 'Display the hierarchy of this fact sheet\'s collection term and a link to download an associated PDF if provided. If this collection has multiple language posts the main link will link to the English language post and then a listing of other languages will be provided automatically. These alternate language links will appear above the main collection.',
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
