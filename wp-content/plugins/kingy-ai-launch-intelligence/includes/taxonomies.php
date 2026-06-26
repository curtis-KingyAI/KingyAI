<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_register_taxonomies', 20);

function kingy_ali_faceted_noindex_taxonomies() {
    return array(
        'kingy_audience',
        'kingy_tool_attribute',
        'kingy_launch_type',
        'model_provider',
        'model_family',
        'model_modality',
        'model_use_case',
        'model_access_type',
        'model_license_type',
        'model_status',
    );
}

function kingy_ali_is_faceted_noindex_taxonomy($taxonomy) {
    return in_array((string) $taxonomy, kingy_ali_faceted_noindex_taxonomies(), true);
}

function kingy_ali_register_taxonomies() {
    register_taxonomy(
        'kingy_launch_category',
        array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company'),
        array(
            'labels' => array(
                'name' => __('Launch Categories', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('Launch Category', 'kingy-ai-launch-intelligence'),
                'search_items' => __('Search Launch Categories', 'kingy-ai-launch-intelligence'),
                'all_items' => __('All Launch Categories', 'kingy-ai-launch-intelligence'),
                'edit_item' => __('Edit Launch Category', 'kingy-ai-launch-intelligence'),
                'update_item' => __('Update Launch Category', 'kingy-ai-launch-intelligence'),
                'add_new_item' => __('Add New Launch Category', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('Launch Categories', 'kingy-ai-launch-intelligence'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'show_in_nav_menus' => false,
            'rewrite' => false,
        )
    );

    register_taxonomy(
        'kingy_audience',
        array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company'),
        array(
            'labels' => array(
                'name' => __('Audiences', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('Audience', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('Audiences', 'kingy-ai-launch-intelligence'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'show_in_nav_menus' => false,
            'rewrite' => false,
        )
    );

    register_taxonomy(
        'kingy_tool_attribute',
        array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company'),
        array(
            'labels' => array(
                'name' => __('Tool Attributes', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('Tool Attribute', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('Tool Attributes', 'kingy-ai-launch-intelligence'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => false,
        )
    );

    register_taxonomy(
        'kingy_launch_type',
        array('kingy_ai_launch'),
        array(
            'labels' => array(
                'name' => __('Launch Types', 'kingy-ai-launch-intelligence'),
                'singular_name' => __('Launch Type', 'kingy-ai-launch-intelligence'),
                'menu_name' => __('Launch Types', 'kingy-ai-launch-intelligence'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'show_in_nav_menus' => false,
            'rewrite' => false,
        )
    );

    $model_taxonomies = array(
        'model_provider' => array(
            'name' => __('Model Providers', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model Provider', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model Providers', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
        'model_family' => array(
            'name' => __('Model Families', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model Family', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model Families', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
        'model_modality' => array(
            'name' => __('Model Modalities', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model Modality', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model Modalities', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
        'model_use_case' => array(
            'name' => __('Model Use Cases', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model Use Case', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model Use Cases', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
        'model_access_type' => array(
            'name' => __('Model Access Types', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model Access Type', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model Access Types', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
        'model_license_type' => array(
            'name' => __('Model License Types', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model License Type', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model License Types', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
        'model_status' => array(
            'name' => __('Model Statuses', 'kingy-ai-launch-intelligence'),
            'singular_name' => __('Model Status', 'kingy-ai-launch-intelligence'),
            'menu_name' => __('Model Statuses', 'kingy-ai-launch-intelligence'),
            'hierarchical' => false,
        ),
    );

    foreach ($model_taxonomies as $taxonomy => $labels) {
        register_taxonomy(
            $taxonomy,
            array('kingy_ai_model'),
            array(
                'labels' => array(
                    'name' => $labels['name'],
                    'singular_name' => $labels['singular_name'],
                    'search_items' => sprintf(__('Search %s', 'kingy-ai-launch-intelligence'), $labels['name']),
                    'all_items' => sprintf(__('All %s', 'kingy-ai-launch-intelligence'), $labels['name']),
                    'edit_item' => sprintf(__('Edit %s', 'kingy-ai-launch-intelligence'), $labels['singular_name']),
                    'update_item' => sprintf(__('Update %s', 'kingy-ai-launch-intelligence'), $labels['singular_name']),
                    'add_new_item' => sprintf(__('Add New %s', 'kingy-ai-launch-intelligence'), $labels['singular_name']),
                    'menu_name' => $labels['menu_name'],
                ),
                'hierarchical' => !empty($labels['hierarchical']),
                'public' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
                'show_in_nav_menus' => false,
                'rewrite' => false,
            )
        );
    }
}

function kingy_ali_default_terms() {
    return array(
        'kingy_launch_category' => array(
            'AI Agents' => 'ai-agents',
            'AI Video Tools' => 'ai-video-tools',
            'AI Coding Tools' => 'ai-coding-tools',
            'AI Models' => 'ai-models',
            'Foundation Models' => 'foundation-models',
            'Open-Source AI' => 'open-source-ai',
            'AI Open-Weight Models' => 'open-weight-models',
            'AI Image Tools' => 'ai-image-tools',
            'AI Search Tools' => 'ai-search-tools',
            'AI Browser Agents' => 'ai-browser-agents',
            'AI Productivity Tools' => 'ai-productivity-tools',
            'AI Writing Tools' => 'ai-writing-tools',
            'AI Voice/Audio Tools' => 'ai-voice-audio-tools',
            'AI Music Tools' => 'ai-music-tools',
            'AI Infrastructure' => 'ai-infrastructure',
            'AI Developer Tools' => 'ai-developer-tools',
            'AI Local Models' => 'ai-local-models',
            'AI Automation Tools' => 'ai-automation-tools',
            'AI Marketing Tools' => 'ai-marketing-tools',
            'AI Research Tools' => 'ai-research-tools',
            'AI Security Tools' => 'ai-security-tools',
            'AI Robotics' => 'ai-robotics',
            'AI Hardware' => 'ai-hardware',
        ),
        'kingy_audience' => array(
            'Creators' => 'creators',
            'YouTubers' => 'youtubers',
            'Founders' => 'founders',
            'Marketers' => 'marketers',
            'Developers' => 'developers',
            'Small Business Owners' => 'small-business-owners',
            'Agencies' => 'agencies',
            'Researchers' => 'researchers',
            'Students' => 'students',
            'Enterprises' => 'enterprises',
            'Designers' => 'designers',
            'Sales Teams' => 'sales-teams',
            'Operators' => 'operators',
        ),
        'kingy_tool_attribute' => array(
            'Free Plan' => 'free-plan',
            'Paid Only' => 'paid-only',
            'Open Source' => 'open-source',
            'Open Weight' => 'open-weight',
            'API Available' => 'api-available',
            'Browser Extension' => 'browser-extension',
            'Mac App' => 'mac-app',
            'Windows App' => 'windows-app',
            'Mobile App' => 'mobile-app',
            'Self-Hosted' => 'self-hosted',
            'Local-First' => 'local-first',
            'No-Code' => 'no-code',
            'Developer-First' => 'developer-first',
            'Beginner-Friendly' => 'beginner-friendly',
            'Enterprise-Ready' => 'enterprise-ready',
            'Founder-Submitted' => 'founder-submitted',
            'Funding Announced' => 'funding-announced',
            'Video Demo Available' => 'video-demo-available',
            'Strong Demo' => 'strong-demo',
            'Clear Use Case' => 'clear-use-case',
            'Creator-Friendly' => 'creator-friendly',
            'Business-Friendly' => 'business-friendly',
            'Developer-Friendly' => 'developer-friendly',
            'Product Hunt Traction' => 'product-hunt-traction',
            'GitHub Traction' => 'github-traction',
            'High YouTube Potential' => 'high-youtube-potential',
            'Traction Signal' => 'traction-signal',
            'Creator Coverage Candidate' => 'creator-coverage-candidate',
            'Creator Campaign Candidate' => 'sponsor-candidate',
        ),
        'kingy_launch_type' => array(
            'New Product' => 'new-product',
            'Model Release' => 'model-release',
            'Funding' => 'funding',
            'Major Update' => 'major-update',
            'Open-Source Release' => 'open-source-release',
            'Founder Submitted' => 'founder-submitted',
        ),
        'model_provider' => array(
            'Anthropic' => 'anthropic',
            'Cohere' => 'cohere',
            'Google DeepMind' => 'google-deepmind',
            'Meta' => 'meta',
            'Mistral AI' => 'mistral-ai',
            'OpenAI' => 'openai',
            'xAI' => 'xai',
        ),
        'model_family' => array(
            'Claude' => 'claude',
            'Command' => 'command',
            'Gemini' => 'gemini',
            'GPT' => 'gpt',
            'Grok' => 'grok',
            'Llama' => 'llama',
            'Mistral' => 'mistral',
        ),
        'model_modality' => array(
            'Text' => 'text',
            'Code' => 'code',
            'Image' => 'image',
            'Video' => 'video',
            'Audio' => 'audio',
            'Multimodal' => 'multimodal',
            'Embeddings' => 'embeddings',
        ),
        'model_use_case' => array(
            'Agents' => 'agents',
            'Coding' => 'coding',
            'Creative writing' => 'creative-writing',
            'Research' => 'research',
            'Reasoning' => 'reasoning',
            'Search' => 'search',
            'Local deployment' => 'local-deployment',
            'Enterprise' => 'enterprise',
        ),
        'model_access_type' => array(
            'API' => 'api',
            'Web app' => 'web-app',
            'Open weights' => 'open-weights',
            'Local' => 'local',
            'Enterprise' => 'enterprise',
        ),
        'model_license_type' => array(
            'Commercial API' => 'commercial-api',
            'Open weights' => 'open-weights',
            'Open source' => 'open-source',
            'Research license' => 'research-license',
            'Custom enterprise terms' => 'custom-enterprise-terms',
        ),
        'model_status' => array(
            'Active' => 'active',
            'Preview' => 'preview',
            'Deprecated' => 'deprecated',
            'Retired' => 'retired',
            'Rumored' => 'rumored',
        ),
    );
}

function kingy_ali_seed_default_terms() {
    foreach (kingy_ali_default_terms() as $taxonomy => $terms) {
        foreach ($terms as $name => $slug) {
            if (!term_exists($slug, $taxonomy)) {
                wp_insert_term($name, $taxonomy, array('slug' => $slug));
            } else {
                $existing_term = get_term_by('slug', $slug, $taxonomy);
                if ($existing_term && !is_wp_error($existing_term) && $existing_term->name !== $name) {
                    wp_update_term($existing_term->term_id, $taxonomy, array('name' => $name));
                }
            }
        }
    }
}
