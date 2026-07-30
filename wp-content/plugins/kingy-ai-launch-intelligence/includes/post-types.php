<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_register_post_types');

function kingy_ali_register_post_types() {
    register_post_type(
        'kingy_ai_launch',
        array(
            'labels' => array(
                'name' => __('AI Launches', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('AI Launch', 'kingy-ai-launch-intelligence'),
                'add_new_item' => __('Add New AI Launch', 'kingy-ai-launch-intelligence'),
                'edit_item' => __('Edit AI Launch', 'kingy-ai-launch-intelligence'),
                'new_item' => __('New AI Launch', 'kingy-ai-launch-intelligence'),
                'view_item' => __('View AI Launch', 'kingy-ai-launch-intelligence'),
                'search_items' => __('Search AI Launches', 'kingy-ai-launch-intelligence'),
                'not_found' => __('No AI launches found', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('AI Launches', 'kingy-ai-launch-intelligence'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'show_in_menu' => 'kingy-ali-dashboard',
            'menu_icon' => 'dashicons-chart-line',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => false,
            'rewrite' => array(
                'slug' => 'ai-launch',
                'with_front' => false,
            ),
        )
    );

    register_post_type(
        'kingy_ai_tool',
        array(
            'labels' => array(
                'name' => __('AI Tools', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('AI Tool', 'kingy-ai-launch-intelligence'),
                'add_new_item' => __('Add New AI Tool', 'kingy-ai-launch-intelligence'),
                'edit_item' => __('Edit AI Tool', 'kingy-ai-launch-intelligence'),
                'new_item' => __('New AI Tool', 'kingy-ai-launch-intelligence'),
                'view_item' => __('View AI Tool', 'kingy-ai-launch-intelligence'),
                'search_items' => __('Search AI Tools', 'kingy-ai-launch-intelligence'),
                'not_found' => __('No AI tools found', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('AI Tools', 'kingy-ai-launch-intelligence'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'show_in_menu' => 'kingy-ali-dashboard',
            'menu_icon' => 'dashicons-admin-tools',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'ai-tools',
                'with_front' => false,
            ),
        )
    );

    register_post_type(
        'kingy_ai_company',
        array(
            'labels' => array(
                'name' => __('AI Companies', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('AI Company', 'kingy-ai-launch-intelligence'),
                'add_new_item' => __('Add New AI Company', 'kingy-ai-launch-intelligence'),
                'edit_item' => __('Edit AI Company', 'kingy-ai-launch-intelligence'),
                'new_item' => __('New AI Company', 'kingy-ai-launch-intelligence'),
                'view_item' => __('View AI Company', 'kingy-ai-launch-intelligence'),
                'search_items' => __('Search AI Companies', 'kingy-ai-launch-intelligence'),
                'not_found' => __('No AI companies found', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('AI Companies', 'kingy-ai-launch-intelligence'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'show_in_menu' => 'kingy-ali-dashboard',
            'menu_icon' => 'dashicons-building',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'ai-companies',
                'with_front' => false,
            ),
        )
    );

    register_post_type(
        'kingy_ai_model',
        array(
            'labels' => array(
                'name' => __('AI Models', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('AI Model', 'kingy-ai-launch-intelligence'),
                'add_new_item' => __('Add New AI Model', 'kingy-ai-launch-intelligence'),
                'edit_item' => __('Edit AI Model', 'kingy-ai-launch-intelligence'),
                'new_item' => __('New AI Model', 'kingy-ai-launch-intelligence'),
                'view_item' => __('View AI Model', 'kingy-ai-launch-intelligence'),
                'search_items' => __('Search AI Models', 'kingy-ai-launch-intelligence'),
                'not_found' => __('No AI models found', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('AI Models', 'kingy-ai-launch-intelligence'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'show_in_menu' => 'kingy-ali-dashboard',
            'menu_icon' => 'dashicons-superhero',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'ai-models',
                'with_front' => false,
            ),
        )
    );

    register_post_type(
        'kingy_video',
        array(
            'labels' => array(
                'name' => __('Companion Videos', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('Companion Video', 'kingy-ai-launch-intelligence'),
                'add_new_item' => __('Add New Companion Video', 'kingy-ai-launch-intelligence'),
                'edit_item' => __('Edit Companion Video', 'kingy-ai-launch-intelligence'),
                'new_item' => __('New Companion Video', 'kingy-ai-launch-intelligence'),
                'view_item' => __('View Companion Video', 'kingy-ai-launch-intelligence'),
                'search_items' => __('Search Companion Videos', 'kingy-ai-launch-intelligence'),
                'not_found' => __('No companion videos found', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('Companion Videos', 'kingy-ai-launch-intelligence'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'show_in_menu' => 'kingy-ali-dashboard',
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'videos',
                'with_front' => false,
            ),
        )
    );
}
