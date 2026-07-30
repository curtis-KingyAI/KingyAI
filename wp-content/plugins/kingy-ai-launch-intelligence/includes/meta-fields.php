<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_register_meta_fields');
add_action('rest_api_init', 'kingy_ali_register_public_rest_meta_fields');
add_action('add_meta_boxes', 'kingy_ali_add_meta_boxes');
add_action('save_post_kingy_ai_launch', 'kingy_ali_save_launch_meta');
add_action('save_post_kingy_ai_tool', 'kingy_ali_save_tool_meta');
add_action('save_post_kingy_ai_company', 'kingy_ali_save_company_meta');
add_action('save_post_kingy_ai_model', 'kingy_ali_save_model_meta');
add_action('transition_post_status', 'kingy_ali_handle_launch_status_transition', 10, 3);

function kingy_ali_meta_key($key) {
    return '_kingy_ali_' . $key;
}

function kingy_ali_yes_no_unknown_options() {
    return array(
        '' => __('Unknown', 'kingy-ai-launch-intelligence'),
        'yes' => __('Yes', 'kingy-ai-launch-intelligence'),
        'no' => __('No', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_interest_options() {
    return array(
        '' => __('Not sure', 'kingy-ai-launch-intelligence'),
        'yes' => __('Yes', 'kingy-ai-launch-intelligence'),
        'no' => __('No', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_budget_likelihood_options() {
    return array(
        '' => __('Unknown', 'kingy-ai-launch-intelligence'),
        'low' => __('Low', 'kingy-ai-launch-intelligence'),
        'medium' => __('Medium', 'kingy-ai-launch-intelligence'),
        'high' => __('High', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_launch_meta_fields() {
    return array(
        'company' => array('label' => 'Company', 'type' => 'text', 'section' => 'Basics'),
        'official_url' => array('label' => 'Official link', 'type' => 'url', 'section' => 'Basics'),
        'launch_date' => array(
            'label' => 'Launch date',
            'type' => 'date',
            'allow_month' => true,
            'section' => 'Basics',
        ),
        'what_launched' => array('label' => 'What launched', 'type' => 'textarea', 'section' => 'Basics'),
        'who_it_is_for' => array('label' => 'Who it is for', 'type' => 'textarea', 'section' => 'Basics'),
        'pricing' => array('label' => 'Pricing', 'type' => 'text', 'section' => 'Basics'),
        'pricing_url' => array('label' => 'Pricing URL', 'type' => 'url', 'section' => 'Sources'),
        'official_announcement_url' => array('label' => 'Official announcement URL', 'type' => 'url', 'section' => 'Sources'),
        'official_docs_url' => array('label' => 'Official documentation URL', 'type' => 'url', 'section' => 'Sources'),
        'free_plan' => array('label' => 'Free plan', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Basics'),
        'api_available' => array('label' => 'API available', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Basics'),
        'open_source_or_open_weight' => array('label' => 'Open source/open weight', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Basics'),
        'demo_url' => array('label' => 'Demo link', 'type' => 'url', 'section' => 'Sources'),
        'product_hunt_url' => array('label' => 'Product Hunt link', 'type' => 'url', 'section' => 'Sources'),
        'github_url' => array('label' => 'GitHub link', 'type' => 'url', 'section' => 'Sources'),
        'huggingface_url' => array('label' => 'Hugging Face link', 'type' => 'url', 'section' => 'Sources'),
        'x_url' => array('label' => 'X/social link', 'type' => 'url', 'section' => 'Sources'),
        'youtube_url' => array('label' => 'YouTube/demo video link', 'type' => 'url', 'section' => 'Sources'),
        'funding' => array('label' => 'Funding', 'type' => 'text', 'section' => 'Sources'),
        'press_kit_url' => array('label' => 'Press kit URL', 'type' => 'url', 'section' => 'Sources'),
        'founder_team' => array('label' => 'Founder/team', 'type' => 'text', 'section' => 'Sources'),
        'media_urls' => array('label' => 'Screenshots/media links', 'type' => 'textarea', 'section' => 'Sources'),
        'sources' => array('label' => 'Source links', 'type' => 'textarea', 'section' => 'Sources'),
        'reddit_signal' => array('label' => 'Reddit/community signal', 'type' => 'textarea', 'section' => 'Traction'),
        'youtube_signal' => array('label' => 'YouTube/creator signal', 'type' => 'textarea', 'section' => 'Traction'),
        'traction_notes' => array('label' => 'Traction notes', 'type' => 'textarea', 'section' => 'Traction'),
        'kingy_launch_score' => array('label' => 'Kingy Launch Score', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => '0.1', 'section' => 'Scores'),
        'demo_quality_score' => array('label' => 'Demo Quality Score', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => '0.1', 'section' => 'Scores'),
        'youtube_score' => array('label' => 'YouTube Content Score', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => '0.1', 'section' => 'Scores'),
        'seo_score' => array('label' => 'SEO Content Score', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => '0.1', 'section' => 'Scores'),
        'scores_suppressed' => array('label' => 'Suppress public scores until reviewed', 'type' => 'checkbox', 'section' => 'Scores', 'public' => false),
        'score_editorial_approval' => array('label' => 'Editorially approve exceptional/repeated score pattern', 'type' => 'checkbox', 'section' => 'Scores', 'public' => false),
        'sponsor_fit_score_internal' => array('label' => 'Internal Partner-Fit Score', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => '0.1', 'section' => 'Scores', 'public' => false),
        'kingy_verdict' => array('label' => 'Kingy AI take', 'type' => 'textarea', 'section' => 'Basics'),
        'what_feels_promising' => array('label' => 'What feels promising', 'type' => 'textarea', 'section' => 'Basics'),
        'what_feels_unproven' => array('label' => 'What feels unproven', 'type' => 'textarea', 'section' => 'Basics'),
        'related_tool_id' => array('label' => 'Related tool profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_tool', 'empty_label' => 'Auto-create or select a tool profile', 'section' => 'Related Content'),
        'related_company_id' => array('label' => 'Related company profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_company', 'empty_label' => 'Auto-link from company name or select a company profile', 'section' => 'Related Content'),
        'related_article_url' => array('label' => 'Related Kingy article URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_course_url' => array('label' => 'Related course URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_review_url' => array('label' => 'Related review URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_alternatives_url' => array('label' => 'Related alternatives URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_calculator_url' => array('label' => 'Related calculator URL', 'type' => 'url', 'section' => 'Related Content'),
        'best_next_link_url' => array('label' => 'Best next link URL', 'type' => 'url', 'section' => 'Related Content'),
        'best_next_link_label' => array('label' => 'Best next link label', 'type' => 'text', 'section' => 'Related Content'),
        'inline_internal_links' => array(
            'label' => 'Approved inline internal links',
            'type' => 'textarea',
            'section' => 'Related Content',
            'public' => false,
            'show_in_rest' => true,
        ),
        'founder_submitted' => array('label' => 'Founder submitted', 'type' => 'checkbox', 'section' => 'Founder/Submission'),
        'founder_contact_name' => array('label' => 'Founder/contact name', 'type' => 'text', 'section' => 'Founder/Submission', 'public' => false),
        'founder_contact_email' => array('label' => 'Founder/contact email', 'type' => 'email', 'section' => 'Founder/Submission', 'public' => false),
        'youtube_interest' => array('label' => 'Founder wants YouTube coverage', 'type' => 'select', 'options' => kingy_ali_interest_options(), 'section' => 'Founder/Submission', 'public' => false),
        'visibility_score_interest' => array('label' => 'Founder wants Launch Visibility Score', 'type' => 'select', 'options' => kingy_ali_interest_options(), 'section' => 'Founder/Submission', 'public' => false),
        'creator_coverage_interest' => array('label' => 'Founder wants creator coverage review', 'type' => 'select', 'options' => kingy_ali_interest_options(), 'section' => 'Founder/Submission', 'public' => false),
        'sponsorship_interest' => array('label' => 'Founder open to creator campaign conversation', 'type' => 'select', 'options' => kingy_ali_interest_options(), 'section' => 'Founder/Submission', 'public' => false),
        'founder_notes' => array('label' => 'Founder notes', 'type' => 'textarea', 'section' => 'Founder/Submission', 'public' => false),
        'outreach_status' => array(
            'label' => 'Outreach status',
            'type' => 'select',
            'options' => array(
                '' => 'Not started',
                'researching' => 'Researching',
                'contacted' => 'Contacted',
                'replied' => 'Replied',
                'not_fit' => 'Not a fit',
                'sponsor_candidate' => 'Partner-fit candidate',
            ),
            'section' => 'Founder/Submission',
            'public' => false,
        ),
        'seo_title' => array('label' => 'SEO title', 'type' => 'text', 'section' => 'SEO'),
        'meta_description' => array('label' => 'Meta description', 'type' => 'textarea', 'section' => 'SEO'),
        'target_search_query' => array('label' => 'Target search query', 'type' => 'text', 'section' => 'SEO'),
        'featured_snippet_answer' => array('label' => 'Featured snippet answer', 'type' => 'textarea', 'section' => 'SEO'),
        'verification_status' => array(
            'label' => 'Verification status',
            'type' => 'select',
            'options' => array(
                '' => 'Needs verification',
                'verified' => 'Verified',
                'founder_submitted' => 'Founder submitted',
                'partially_verified' => 'Partially verified',
                'outdated' => 'Outdated',
            ),
            'section' => 'SEO',
        ),
        'last_verified' => array('label' => 'Last verified', 'type' => 'date', 'section' => 'SEO'),
        'noindex' => array('label' => 'Noindex this launch profile', 'type' => 'checkbox', 'section' => 'SEO', 'public' => false),
        'budget_likelihood_internal' => array('label' => 'Internal budget likelihood', 'type' => 'select', 'options' => kingy_ali_budget_likelihood_options(), 'section' => 'Internal Notes', 'public' => false),
        'internal_notes' => array('label' => 'Internal notes', 'type' => 'textarea', 'section' => 'Internal Notes', 'public' => false),
        'canonical_fingerprint' => array('label' => 'Canonical import fingerprint', 'type' => 'text', 'section' => 'Internal Notes', 'public' => false),
        'quality_gate_result' => array('label' => 'Last quality gate result', 'type' => 'textarea', 'section' => 'Internal Notes', 'public' => false),
    );
}

function kingy_ali_tool_meta_fields() {
    return array(
        'company' => array('label' => 'Company', 'type' => 'text', 'section' => 'Basics'),
        'official_url' => array('label' => 'Official site', 'type' => 'url', 'section' => 'Basics'),
        'demo_url' => array('label' => 'Official demo URL', 'type' => 'url', 'section' => 'Sources'),
        'what_it_does' => array('label' => 'What it does', 'type' => 'textarea', 'section' => 'Basics'),
        'best_for' => array('label' => 'Best for', 'type' => 'textarea', 'section' => 'Basics'),
        'pricing' => array('label' => 'Pricing', 'type' => 'text', 'section' => 'Basics'),
        'pricing_url' => array('label' => 'Pricing URL', 'type' => 'url', 'section' => 'Sources'),
        'free_plan' => array('label' => 'Free plan', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Basics'),
        'api_available' => array('label' => 'API available', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Basics'),
        'open_source_or_open_weight' => array('label' => 'Open source/open weight', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Basics'),
        'main_competitors' => array('label' => 'Main competitors', 'type' => 'textarea', 'section' => 'Related Content'),
        'alternatives_url' => array('label' => 'Alternatives page URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_article_url' => array('label' => 'Related Kingy article URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_course_url' => array('label' => 'Related course URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_review_url' => array('label' => 'Related review URL', 'type' => 'url', 'section' => 'Related Content'),
        'latest_launch_id' => array('label' => 'Latest launch profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_launch', 'empty_label' => 'Auto-link from launch history or select a launch profile', 'section' => 'Related Content'),
        'related_company_id' => array('label' => 'Related company profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_company', 'empty_label' => 'Auto-link from company name or select a company profile', 'section' => 'Related Content'),
        'last_verified' => array('label' => 'Last verified', 'type' => 'date', 'section' => 'SEO'),
    );
}

function kingy_ali_company_meta_fields() {
    return array(
        'official_url' => array('label' => 'Official site', 'type' => 'url', 'section' => 'Basics'),
        'company_summary' => array('label' => 'Company summary', 'type' => 'textarea', 'section' => 'Basics'),
        'ai_evidence' => array('label' => 'AI product evidence', 'type' => 'textarea', 'section' => 'Basics'),
        'buyer_notes' => array('label' => 'Public research notes', 'type' => 'textarea', 'section' => 'Basics'),
        'founder_team' => array('label' => 'Founder/team', 'type' => 'text', 'section' => 'Basics'),
        'funding' => array('label' => 'Funding', 'type' => 'text', 'section' => 'Basics'),
        'contact_url' => array('label' => 'Contact URL', 'type' => 'url', 'section' => 'Outreach'),
        'founder_contact_email' => array('label' => 'Founder/contact email', 'type' => 'email', 'section' => 'Outreach', 'public' => false),
        'outreach_status' => array(
            'label' => 'Outreach status',
            'type' => 'select',
            'options' => array(
                '' => 'Not started',
                'researching' => 'Researching',
                'contacted' => 'Contacted',
                'replied' => 'Replied',
                'not_fit' => 'Not a fit',
                'sponsor_candidate' => 'Partner-fit candidate',
            ),
            'section' => 'Outreach',
            'public' => false,
        ),
        'sponsor_fit_score_internal' => array('label' => 'Internal Partner-Fit Score', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => '0.1', 'section' => 'Outreach', 'public' => false),
        'budget_likelihood_internal' => array('label' => 'Internal budget likelihood', 'type' => 'select', 'options' => kingy_ali_budget_likelihood_options(), 'section' => 'Outreach', 'public' => false),
        'internal_notes' => array('label' => 'Internal notes', 'type' => 'textarea', 'section' => 'Outreach', 'public' => false),
        'sources' => array('label' => 'Source links', 'type' => 'textarea', 'section' => 'SEO'),
        'source_notes' => array('label' => 'Public source notes', 'type' => 'textarea', 'section' => 'SEO'),
        'verification_status' => array(
            'label' => 'Verification status',
            'type' => 'select',
            'options' => array(
                '' => 'Needs verification',
                'verified' => 'Verified',
                'founder_submitted' => 'Founder submitted',
                'partially_verified' => 'Partially verified',
                'outdated' => 'Outdated',
            ),
            'section' => 'SEO',
        ),
        'last_verified' => array('label' => 'Last verified', 'type' => 'date', 'section' => 'SEO'),
        'noindex' => array('label' => 'Noindex this company profile', 'type' => 'checkbox', 'section' => 'SEO', 'public' => false),
    );
}

function kingy_ali_model_meta_fields() {
    return array(
        'provider_name' => array('label' => 'Provider name', 'type' => 'text', 'section' => 'Basics'),
        'model_family_name' => array('label' => 'Model family', 'type' => 'text', 'section' => 'Basics'),
        'release_date' => array('label' => 'Release date', 'type' => 'date', 'section' => 'Basics'),
        'model_status_note' => array('label' => 'Status note', 'type' => 'text', 'section' => 'Basics'),
        'model_overview' => array('label' => 'What this model is', 'type' => 'textarea', 'section' => 'Basics'),
        'what_changed' => array('label' => 'What changed at launch', 'type' => 'textarea', 'section' => 'Basics'),
        'best_for' => array('label' => 'Best for', 'type' => 'textarea', 'section' => 'Basics'),
        'skip_if' => array('label' => 'Skip if', 'type' => 'textarea', 'section' => 'Basics'),
        'strengths' => array('label' => 'Strengths', 'type' => 'textarea', 'section' => 'Editorial'),
        'weaknesses' => array('label' => 'Weaknesses / caveats', 'type' => 'textarea', 'section' => 'Editorial'),
        'kingy_verdict' => array('label' => 'Kingy AI take', 'type' => 'textarea', 'section' => 'Editorial'),
        'official_url' => array('label' => 'Official model/product URL', 'type' => 'url', 'section' => 'Sources'),
        'official_announcement_url' => array('label' => 'Official announcement URL', 'type' => 'url', 'section' => 'Sources'),
        'official_docs_url' => array('label' => 'Official docs URL', 'type' => 'url', 'section' => 'Sources'),
        'api_reference_url' => array('label' => 'API reference URL', 'type' => 'url', 'section' => 'Sources'),
        'model_card_url' => array('label' => 'Model card URL', 'type' => 'url', 'section' => 'Sources'),
        'system_card_url' => array('label' => 'System/safety card URL', 'type' => 'url', 'section' => 'Sources'),
        'evals_url' => array('label' => 'Official evals URL', 'type' => 'url', 'section' => 'Sources'),
        'pricing_url' => array('label' => 'Pricing URL', 'type' => 'url', 'section' => 'Sources'),
        'license_url' => array('label' => 'License URL', 'type' => 'url', 'section' => 'Sources'),
        'weights_url' => array('label' => 'Weights/download URL', 'type' => 'url', 'section' => 'Sources'),
        'sources' => array('label' => 'Additional source links', 'type' => 'textarea', 'section' => 'Sources'),
        'context_window' => array('label' => 'Context window', 'type' => 'text', 'section' => 'Capabilities'),
        'context_source_url' => array('label' => 'Context window source URL', 'type' => 'url', 'section' => 'Capabilities'),
        'output_limit' => array('label' => 'Output limit', 'type' => 'text', 'section' => 'Capabilities'),
        'tool_calling' => array('label' => 'Tool/function calling', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Capabilities'),
        'fine_tuning' => array('label' => 'Fine-tuning', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Capabilities'),
        'agent_suitability' => array('label' => 'Agent suitability', 'type' => 'textarea', 'section' => 'Capabilities'),
        'coding_notes' => array('label' => 'Coding notes', 'type' => 'textarea', 'section' => 'Capabilities'),
        'reasoning_notes' => array('label' => 'Reasoning notes', 'type' => 'textarea', 'section' => 'Capabilities'),
        'creative_notes' => array('label' => 'Creative notes', 'type' => 'textarea', 'section' => 'Capabilities'),
        'research_notes' => array('label' => 'Research notes', 'type' => 'textarea', 'section' => 'Capabilities'),
        'api_available' => array('label' => 'API available', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Access & Cost'),
        'web_app_available' => array('label' => 'Web app available', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Access & Cost'),
        'local_available' => array('label' => 'Local/self-hosted availability', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Access & Cost'),
        'open_weight' => array('label' => 'Open weights', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Access & Cost'),
        'open_source' => array('label' => 'Open source', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'section' => 'Access & Cost'),
        'pricing' => array('label' => 'Pricing summary', 'type' => 'text', 'section' => 'Access & Cost'),
        'api_pricing' => array('label' => 'API pricing notes', 'type' => 'textarea', 'section' => 'Access & Cost'),
        'hardware_requirements' => array('label' => 'Local hardware requirements', 'type' => 'textarea', 'section' => 'Access & Cost'),
        'license_notes' => array('label' => 'License notes', 'type' => 'textarea', 'section' => 'Access & Cost'),
        'benchmark_summary' => array('label' => 'Benchmark summary', 'type' => 'textarea', 'section' => 'Benchmarks'),
        'benchmark_caveat' => array('label' => 'Benchmark caveat', 'type' => 'textarea', 'section' => 'Benchmarks'),
        'benchmark_url' => array('label' => 'Benchmark/source URL', 'type' => 'url', 'section' => 'Benchmarks'),
        'related_launch_id' => array('label' => 'Related launch profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_launch', 'empty_label' => 'Select a related model launch', 'section' => 'Related Content'),
        'related_tool_id' => array('label' => 'Related tool profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_tool', 'empty_label' => 'Select a related tool profile', 'section' => 'Related Content'),
        'related_company_id' => array('label' => 'Related company profile', 'type' => 'post_select', 'post_type' => 'kingy_ai_company', 'empty_label' => 'Select a related company profile', 'section' => 'Related Content'),
        'alternatives_url' => array('label' => 'Alternatives/comparison URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_article_url' => array('label' => 'Related Kingy article URL', 'type' => 'url', 'section' => 'Related Content'),
        'related_course_url' => array('label' => 'Related course URL', 'type' => 'url', 'section' => 'Related Content'),
        'seo_title' => array('label' => 'SEO title', 'type' => 'text', 'section' => 'SEO'),
        'meta_description' => array('label' => 'Meta description', 'type' => 'textarea', 'section' => 'SEO'),
        'target_search_query' => array('label' => 'Target search query', 'type' => 'text', 'section' => 'SEO'),
        'verification_status' => array(
            'label' => 'Verification status',
            'type' => 'select',
            'options' => array(
                '' => 'Needs verification',
                'verified' => 'Verified',
                'partially_verified' => 'Partially verified',
                'founder_submitted' => 'Founder submitted',
                'outdated' => 'Outdated',
                'rumored' => 'Rumored / do not index',
            ),
            'section' => 'SEO',
        ),
        'last_verified' => array('label' => 'Last verified', 'type' => 'date', 'section' => 'SEO'),
        'noindex' => array('label' => 'Noindex this model profile', 'type' => 'checkbox', 'section' => 'SEO', 'public' => false),
        'internal_notes' => array('label' => 'Internal notes', 'type' => 'textarea', 'section' => 'Internal Notes', 'public' => false),
    );
}

function kingy_ali_register_meta_fields() {
    register_post_meta(
        'post',
        '_kingy_ali_daily_radar_email_v1',
        array(
            'single' => true,
            'type' => 'string',
            'sanitize_callback' => 'kingy_ali_sanitize_daily_radar_email_contract',
            'show_in_rest' => true,
            'auth_callback' => 'kingy_ali_authorize_daily_radar_email_contract',
        )
    );

    foreach (kingy_ali_launch_meta_fields() as $key => $field) {
        kingy_ali_register_single_meta('kingy_ai_launch', $key, $field);
    }

    kingy_ali_register_private_launch_metadata_schema();

    foreach (kingy_ali_tool_meta_fields() as $key => $field) {
        kingy_ali_register_single_meta('kingy_ai_tool', $key, $field);
    }

    foreach (kingy_ali_company_meta_fields() as $key => $field) {
        kingy_ali_register_single_meta('kingy_ai_company', $key, $field);
    }

    foreach (kingy_ali_model_meta_fields() as $key => $field) {
        kingy_ali_register_single_meta('kingy_ai_model', $key, $field);
    }
}

function kingy_ali_private_launch_metadata_schema() {
    return array(
        'official_launch_url' => array('type' => 'url'),
        'why_it_matters' => array('type' => 'textarea'),
        'best_use_case' => array('type' => 'textarea'),
        'current_limitations' => array('type' => 'textarea'),
        'kingy_last_updated' => array('type' => 'date'),
        'score_components' => array('type' => 'json'),
        'score_history' => array('type' => 'json'),
        'verdict_history' => array('type' => 'json'),
    );
}

function kingy_ali_register_private_launch_metadata_schema() {
    foreach (kingy_ali_private_launch_metadata_schema() as $key => $field) {
        register_post_meta(
            'kingy_ai_launch',
            kingy_ali_meta_key($key),
            array(
                'single' => true,
                'type' => 'string',
                'sanitize_callback' => function ($value) use ($field) {
                    if ($field['type'] === 'json') {
                        if (!is_scalar($value)) {
                            return '';
                        }
                        $value = trim((string) $value);
                        json_decode($value, true);
                        return $value !== '' && json_last_error() === JSON_ERROR_NONE ? $value : '';
                    }
                    return kingy_ali_sanitize_meta_value($value, $field);
                },
                'show_in_rest' => false,
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            )
        );
    }
}

function kingy_ali_sanitize_daily_radar_email_contract($value) {
    if (!is_string($value)) {
        return '';
    }

    $value = trim($value);
    if ($value === '' || strlen($value) > 100000) {
        return '';
    }

    $decoded = json_decode($value, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        return '';
    }

    if ((int) ($decoded['schema_version'] ?? 0) !== 1) {
        return '';
    }

    $issue_date = isset($decoded['issue_date']) ? (string) $decoded['issue_date'] : '';
    $lead_slug = isset($decoded['lead_slug']) ? (string) $decoded['lead_slug'] : '';
    $quick_launches = isset($decoded['quick_launches']) && is_array($decoded['quick_launches'])
        ? $decoded['quick_launches']
        : null;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue_date) || $lead_slug === '' || $quick_launches === null) {
        return '';
    }

    return $value;
}

function kingy_ali_authorize_daily_radar_email_contract($allowed, $meta_key, $post_id) {
    return $post_id && current_user_can('edit_post', (int) $post_id);
}

function kingy_ali_register_single_meta($post_type, $key, $field) {
    $schema_type = 'string';
    if ($field['type'] === 'number') {
        $schema_type = 'number';
    } elseif ($field['type'] === 'post_select') {
        $schema_type = 'integer';
    } elseif ($field['type'] === 'checkbox') {
        $schema_type = 'boolean';
    }

    register_post_meta(
        $post_type,
        kingy_ali_meta_key($key),
        array(
            'single' => true,
            'type' => $schema_type,
            'sanitize_callback' => function ($value) use ($field) {
                return kingy_ali_sanitize_meta_value($value, $field);
            },
            'show_in_rest' => isset($field['show_in_rest']) ? (bool) $field['show_in_rest'] : (!isset($field['public']) || $field['public'] !== false),
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        )
    );
}

function kingy_ali_register_public_rest_meta_fields() {
    if (!function_exists('register_rest_field')) {
        return;
    }

    $record_fields = array(
        'kingy_ai_launch' => kingy_ali_launch_meta_fields(),
        'kingy_ai_tool' => kingy_ali_tool_meta_fields(),
        'kingy_ai_company' => kingy_ali_company_meta_fields(),
        'kingy_ai_model' => kingy_ali_model_meta_fields(),
    );

    foreach ($record_fields as $post_type => $fields) {
        register_rest_field(
            $post_type,
            'kingy_ali_meta',
            array(
                'get_callback' => function ($object) use ($fields) {
                    return kingy_ali_rest_public_meta_for_object($object, $fields);
                },
                'schema' => array(
                    'description' => __('Public Kingy AI Launch Intelligence structured fields.', 'kingy-ai-launch-intelligence'),
                    'type' => 'object',
                    'context' => array('view', 'edit'),
                    'readonly' => true,
                    'properties' => kingy_ali_rest_public_meta_schema_properties($fields),
                ),
            )
        );
    }

    register_rest_field(
        'kingy_ai_launch',
        'kingy_ali_trust',
        array(
            'get_callback' => function ($object) {
                $post_id = isset($object['id']) ? absint($object['id']) : 0;
                return $post_id ? kingy_ali_rest_launch_payload($post_id) : array();
            },
            'schema' => array(
                'description' => __('Canonical public verification and score snapshot.', 'kingy-ai-launch-intelligence'),
                'type' => 'object',
                'context' => array('view', 'edit'),
                'readonly' => true,
            ),
        )
    );

    kingy_ali_register_inline_internal_links_rest_field();
}

function kingy_ali_rest_launch_payload($post_id) {
    return kingy_ali_launch_trust_snapshot(absint($post_id));
}

function kingy_ali_register_inline_internal_links_rest_field() {
    register_rest_field(
        'kingy_ai_launch',
        'kingy_ali_inline_internal_links',
        array(
            'get_callback' => function ($object) {
                $post_id = isset($object['id']) ? absint($object['id']) : 0;
                if (!$post_id || !current_user_can('edit_post', $post_id)) {
                    return null;
                }

                return get_post_meta($post_id, kingy_ali_meta_key('inline_internal_links'), true);
            },
            'update_callback' => function ($value, $object) {
                $post_id = 0;
                if ($object instanceof WP_Post) {
                    $post_id = absint($object->ID);
                } elseif (is_array($object) && isset($object['id'])) {
                    $post_id = absint($object['id']);
                } elseif (is_object($object) && isset($object->id)) {
                    $post_id = absint($object->id);
                }

                if (!$post_id || !current_user_can('edit_post', $post_id)) {
                    return new WP_Error(
                        'kingy_ali_inline_internal_links_forbidden',
                        __('You are not allowed to edit inline internal links for this launch.', 'kingy-ai-launch-intelligence'),
                        array('status' => 403)
                    );
                }

                if (is_array($value) || is_object($value)) {
                    return new WP_Error(
                        'kingy_ali_inline_internal_links_invalid',
                        __('Inline internal links must be a plain text whitelist.', 'kingy-ai-launch-intelligence'),
                        array('status' => 400)
                    );
                }

                $fields = kingy_ali_launch_meta_fields();
                $field = isset($fields['inline_internal_links']) ? $fields['inline_internal_links'] : array('type' => 'textarea');
                $sanitized = kingy_ali_sanitize_meta_value((string) $value, $field);
                $meta_key = kingy_ali_meta_key('inline_internal_links');

                if ($sanitized === '') {
                    delete_post_meta($post_id, $meta_key);
                    return true;
                }

                update_post_meta($post_id, $meta_key, $sanitized);
                return true;
            },
            'schema' => array(
                'description' => __('Approved inline internal link whitelist for authenticated launch editors.', 'kingy-ai-launch-intelligence'),
                'type' => 'string',
                'context' => array('edit'),
            ),
        )
    );
}

function kingy_ali_rest_public_meta_for_object($object, $fields) {
    $post_id = isset($object['id']) ? absint($object['id']) : 0;
    if (!$post_id) {
        return array();
    }

    $public_meta = array();
    $is_launch = get_post_type($post_id) === 'kingy_ai_launch';
    $trust = $is_launch && function_exists('kingy_ali_launch_trust_snapshot')
        ? kingy_ali_launch_trust_snapshot($post_id)
        : array();
    foreach ($fields as $key => $field) {
        if (!kingy_ali_is_public_meta_field($field)) {
            continue;
        }

        if ($is_launch && $key === 'verification_status' && $trust) {
            $value = $trust['status'];
        } elseif ($is_launch && $key === 'last_verified' && $trust) {
            $value = $trust['last_verified'];
        } elseif ($is_launch && $key === 'kingy_launch_score' && $trust) {
            $value = $trust['score']['kingy']['value'];
        } elseif ($is_launch && $key === 'demo_quality_score' && $trust) {
            $value = $trust['score']['demo']['value'];
        } elseif ($is_launch && $key === 'youtube_score' && $trust) {
            $value = $trust['score']['youtube']['value'];
        } else {
            $value = get_post_meta($post_id, kingy_ali_meta_key($key), true);
            if (function_exists('kingy_ali_public_profile_meta_text') && (!isset($field['type']) || in_array($field['type'], array('text', 'textarea'), true))) {
                $value = kingy_ali_public_profile_meta_text($post_id, $key);
            }
        }
        if ($value === '' || $value === array()) {
            continue;
        }
        if ($value === null) {
            continue;
        }

        if (function_exists('kingy_ali_optional_value_is_unknown') && kingy_ali_optional_value_is_unknown($value)) {
            continue;
        }

        if (!is_scalar($value)) {
            continue;
        }

        if (isset($field['type']) && $field['type'] === 'post_select' && !kingy_ali_rest_public_related_post_is_visible($value, $field)) {
            continue;
        }

        $public_value = kingy_ali_rest_cast_public_meta_value($value, $field);
        if ($public_value === '' || $public_value === array()) {
            continue;
        }

        $public_meta[$key] = $public_value;
    }

    return $public_meta;
}

function kingy_ali_is_public_meta_field($field) {
    return !isset($field['public']) || $field['public'] !== false;
}

function kingy_ali_rest_public_related_post_is_visible($value, $field) {
    if (!is_scalar($value)) {
        return false;
    }

    $post_type = isset($field['post_type']) ? $field['post_type'] : '';
    return kingy_ali_related_post_is_public_index_ready($value, $post_type);
}

function kingy_ali_related_post_is_valid($post_id, $post_type = '', $allowed_statuses = array('publish', 'pending', 'draft')) {
    if (!is_scalar($post_id)) {
        return false;
    }

    $post_id = absint($post_id);
    if (!$post_id) {
        return false;
    }

    $post_type = sanitize_key($post_type);
    if ($post_type && get_post_type($post_id) !== $post_type) {
        return false;
    }

    if ($allowed_statuses === null) {
        return true;
    }

    return in_array(get_post_status($post_id), (array) $allowed_statuses, true);
}

function kingy_ali_related_post_is_public($post_id, $post_type = '') {
    return kingy_ali_related_post_is_valid($post_id, $post_type, array('publish'));
}

function kingy_ali_related_post_is_public_index_ready($post_id, $post_type = '') {
    if (!is_scalar($post_id)) {
        return false;
    }

    $post_id = absint($post_id);
    if (!kingy_ali_related_post_is_public($post_id, $post_type)) {
        return false;
    }

    return function_exists('kingy_ali_entity_seo_is_indexable')
        ? kingy_ali_entity_seo_is_indexable($post_id)
        : (!function_exists('kingy_ali_profile_should_noindex') || !kingy_ali_profile_should_noindex($post_id));
}

function kingy_ali_rest_cast_public_meta_value($value, $field) {
    if (!is_scalar($value)) {
        return '';
    }

    $type = isset($field['type']) ? $field['type'] : 'text';

    if ($type === 'url') {
        return kingy_ali_rest_public_url_value($value);
    }

    if ($type === 'number') {
        return kingy_ali_rest_public_number_value($value, $field);
    }

    if ($type === 'post_select') {
        return absint($value);
    }

    if ($type === 'checkbox') {
        return !empty($value);
    }

    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_rest_public_number_value($value, $field) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    if ($value === '' || !is_numeric($value)) {
        return '';
    }

    $number = (float) $value;
    if (isset($field['min'])) {
        $number = max((float) $field['min'], $number);
    }
    if (isset($field['max'])) {
        $number = min((float) $field['max'], $number);
    }

    return $number;
}

function kingy_ali_rest_public_url_value($value) {
    if (function_exists('kingy_ali_schema_url')) {
        return kingy_ali_schema_url($value);
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($value);
    }

    if (!is_scalar($value)) {
        return '';
    }

    $url = trim((string) $value);
    if ($url === '') {
        return '';
    }

    $url = esc_url_raw($url, array('http', 'https'));
    if ($url === '') {
        return '';
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true) ? $url : '';
}

function kingy_ali_rest_public_meta_schema_properties($fields) {
    $properties = array();
    foreach ($fields as $key => $field) {
        if (!kingy_ali_is_public_meta_field($field)) {
            continue;
        }

        $properties[$key] = array(
            'description' => isset($field['label']) ? $field['label'] : $key,
            'type' => kingy_ali_rest_public_meta_schema_type($field),
            'readonly' => true,
        );
    }

    return $properties;
}

function kingy_ali_rest_public_meta_schema_type($field) {
    $type = isset($field['type']) ? $field['type'] : 'text';
    if ($type === 'number') {
        return 'number';
    }
    if ($type === 'post_select') {
        return 'integer';
    }
    if ($type === 'checkbox') {
        return 'boolean';
    }

    return 'string';
}

function kingy_ali_add_meta_boxes() {
    add_meta_box(
        'kingy_ali_launch_details',
        __('Launch Intelligence Details', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_launch_meta_box',
        'kingy_ai_launch',
        'normal',
        'high'
    );

    add_meta_box(
        'kingy_ali_tool_details',
        __('AI Tool Details', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_tool_meta_box',
        'kingy_ai_tool',
        'normal',
        'high'
    );

    add_meta_box(
        'kingy_ali_company_details',
        __('AI Company Details', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_company_meta_box',
        'kingy_ai_company',
        'normal',
        'high'
    );

    add_meta_box(
        'kingy_ali_model_details',
        __('AI Model Details', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_model_meta_box',
        'kingy_ai_model',
        'normal',
        'high'
    );
}

function kingy_ali_render_launch_meta_box($post) {
    kingy_ali_render_meta_box($post, kingy_ali_launch_meta_fields(), 'kingy_ali_launch_meta_nonce');
}

function kingy_ali_render_tool_meta_box($post) {
    kingy_ali_render_meta_box($post, kingy_ali_tool_meta_fields(), 'kingy_ali_tool_meta_nonce');
}

function kingy_ali_render_company_meta_box($post) {
    kingy_ali_render_meta_box($post, kingy_ali_company_meta_fields(), 'kingy_ali_company_meta_nonce');
}

function kingy_ali_render_model_meta_box($post) {
    kingy_ali_render_meta_box($post, kingy_ali_model_meta_fields(), 'kingy_ali_model_meta_nonce');
}

function kingy_ali_render_meta_box($post, $fields, $nonce_name) {
    wp_nonce_field($nonce_name, $nonce_name);
    $sections = array();

    foreach ($fields as $key => $field) {
        $section = isset($field['section']) ? $field['section'] : 'Details';
        $sections[$section][$key] = $field;
    }

    $section_names = array_keys($sections);
    kingy_ali_render_editor_readiness_panel($post);
    echo '<div class="kingy-ali-admin-fields kingy-ali-meta-tabs" data-kingy-ali-meta-tabs>';
    echo '<div class="kingy-ali-meta-tabs__nav" role="tablist">';
    foreach ($section_names as $index => $section) {
        $tab_id = 'kingy-ali-tab-' . esc_attr($nonce_name) . '-' . sanitize_title($section);
        $panel_id = 'kingy-ali-panel-' . esc_attr($nonce_name) . '-' . sanitize_title($section);
        echo '<button type="button" id="' . esc_attr($tab_id) . '" class="kingy-ali-meta-tabs__button' . ($index === 0 ? ' is-active' : '') . '" role="tab" aria-selected="' . ($index === 0 ? 'true' : 'false') . '" aria-controls="' . esc_attr($panel_id) . '" data-kingy-ali-meta-tab="' . esc_attr($panel_id) . '">' . esc_html($section) . '</button>';
    }
    echo '</div>';

    $section_index = 0;
    foreach ($sections as $section => $section_fields) {
        $tab_id = 'kingy-ali-tab-' . esc_attr($nonce_name) . '-' . sanitize_title($section);
        $panel_id = 'kingy-ali-panel-' . esc_attr($nonce_name) . '-' . sanitize_title($section);
        echo '<section id="' . esc_attr($panel_id) . '" class="kingy-ali-meta-tabs__panel' . ($section_index === 0 ? ' is-active' : '') . '" role="tabpanel" aria-labelledby="' . esc_attr($tab_id) . '"' . ($section_index === 0 ? '' : ' hidden') . '>';
        echo '<table class="form-table" role="presentation"><tbody>';
        foreach ($section_fields as $key => $field) {
            $value = get_post_meta($post->ID, kingy_ali_meta_key($key), true);
            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr('kingy_ali_' . $key) . '">' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            kingy_ali_render_field_control($key, $field, $value);
            if (isset($field['public']) && $field['public'] === false) {
                echo '<p class="description">' . esc_html__('Internal only. Do not display publicly.', 'kingy-ai-launch-intelligence') . '</p>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</section>';
        $section_index++;
    }
    echo '</div>';
}

function kingy_ali_render_editor_readiness_panel($post) {
    if (!$post instanceof WP_Post) {
        return;
    }

    $items = kingy_ali_editor_readiness_items($post->ID, $post->post_type);
    if (!$items) {
        return;
    }

    $missing = array_filter(
        $items,
        function ($item) {
            return empty($item['complete']);
        }
    );
    $is_ready = empty($missing);
    $state_class = $is_ready ? 'kingy-ali-editor-readiness--ready' : 'kingy-ali-editor-readiness--needs-work';
    $summary = $is_ready
        ? __('Ready for public profile review.', 'kingy-ai-launch-intelligence')
        : sprintf(
            _n('%d item needs attention before public profile review.', '%d items need attention before public profile review.', count($missing), 'kingy-ai-launch-intelligence'),
            count($missing)
        );

    echo '<section class="kingy-ali-editor-readiness ' . esc_attr($state_class) . '">';
    echo '<div><strong>' . esc_html__('Profile readiness', 'kingy-ai-launch-intelligence') . '</strong><span>' . esc_html($summary) . '</span></div>';
    echo '<ul>';
    foreach ($items as $item) {
        $item_class = !empty($item['complete']) ? 'is-complete' : 'is-missing';
        $status = !empty($item['complete']) ? __('Ready', 'kingy-ai-launch-intelligence') : __('Needs attention', 'kingy-ai-launch-intelligence');
        echo '<li class="' . esc_attr($item_class) . '"><span>' . esc_html($item['label']) . '</span><em>' . esc_html($status) . '</em></li>';
    }
    echo '</ul>';
    echo '</section>';
}

function kingy_ali_editor_readiness_items($post_id, $post_type) {
    $post_id = absint($post_id);
    if (!$post_id) {
        return array();
    }

    if ($post_type === 'kingy_ai_launch') {
        return array(
            kingy_ali_editor_readiness_item(__('Primary category', 'kingy-ai-launch-intelligence'), kingy_ali_editor_has_term($post_id, 'kingy_launch_category')),
            kingy_ali_editor_readiness_item(__('Official link', 'kingy-ai-launch-intelligence'), kingy_ali_editor_public_url_meta_is_ready($post_id, 'official_url')),
            kingy_ali_editor_readiness_item(__('Launch date', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'launch_date') !== ''),
            kingy_ali_editor_readiness_item(__('What launched', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'what_launched') !== ''),
            kingy_ali_editor_readiness_item(__('Audience/use case', 'kingy-ai-launch-intelligence'), kingy_ali_editor_audience_is_ready($post_id)),
            kingy_ali_editor_readiness_item(__('Known pricing', 'kingy-ai-launch-intelligence'), kingy_ali_editor_pricing_is_ready($post_id)),
            kingy_ali_editor_readiness_item(__('Kingy AI take', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'kingy_verdict') !== ''),
            kingy_ali_editor_readiness_item(__('Useful source/related link', 'kingy-ai-launch-intelligence'), kingy_ali_editor_launch_has_useful_related_link($post_id)),
            kingy_ali_editor_readiness_item(__('Verification status', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'verification_status') !== ''),
            kingy_ali_editor_readiness_item(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'last_verified') !== ''),
        );
    }

    if ($post_type === 'kingy_ai_tool') {
        return array(
            kingy_ali_editor_readiness_item(__('Primary category', 'kingy-ai-launch-intelligence'), kingy_ali_editor_has_term($post_id, 'kingy_launch_category')),
            kingy_ali_editor_readiness_item(__('Official site', 'kingy-ai-launch-intelligence'), kingy_ali_editor_public_url_meta_is_ready($post_id, 'official_url')),
            kingy_ali_editor_readiness_item(__('What it does', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'what_it_does') !== ''),
            kingy_ali_editor_readiness_item(__('Known pricing', 'kingy-ai-launch-intelligence'), kingy_ali_editor_pricing_is_ready($post_id)),
            kingy_ali_editor_readiness_item(__('Source link', 'kingy-ai-launch-intelligence'), kingy_ali_editor_public_source_count($post_id) > 0),
            kingy_ali_editor_readiness_item(__('Linked launch history', 'kingy-ai-launch-intelligence'), kingy_ali_editor_tool_has_launch_history($post_id)),
            kingy_ali_editor_readiness_item(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'last_verified') !== ''),
        );
    }

    if ($post_type === 'kingy_ai_company') {
        return array(
            kingy_ali_editor_readiness_item(__('Primary category', 'kingy-ai-launch-intelligence'), kingy_ali_editor_has_term($post_id, 'kingy_launch_category')),
            kingy_ali_editor_readiness_item(__('Official site', 'kingy-ai-launch-intelligence'), kingy_ali_editor_public_url_meta_is_ready($post_id, 'official_url')),
            kingy_ali_editor_readiness_item(__('Company summary', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'company_summary') !== ''),
            kingy_ali_editor_readiness_item(__('Contact URL', 'kingy-ai-launch-intelligence'), kingy_ali_editor_public_url_meta_is_ready($post_id, 'contact_url')),
            kingy_ali_editor_readiness_item(__('Linked launches or tools', 'kingy-ai-launch-intelligence'), kingy_ali_editor_company_has_graph_links($post_id)),
            kingy_ali_editor_readiness_item(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'last_verified') !== ''),
        );
    }

    if ($post_type === 'kingy_ai_model') {
        $items = array(
            kingy_ali_editor_readiness_item(__('Provider', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'provider_name') !== '' || kingy_ali_editor_has_term($post_id, 'model_provider')),
            kingy_ali_editor_readiness_item(__('Modality', 'kingy-ai-launch-intelligence'), kingy_ali_editor_has_term($post_id, 'model_modality')),
            kingy_ali_editor_readiness_item(__('Official source', 'kingy-ai-launch-intelligence'), kingy_ali_editor_model_has_official_source($post_id)),
            kingy_ali_editor_readiness_item(__('Model overview', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'model_overview') !== ''),
            kingy_ali_editor_readiness_item(__('Release date or status note', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'release_date') !== '' || kingy_ali_editor_meta_text($post_id, 'model_status_note') !== ''),
            kingy_ali_editor_readiness_item(__('Access or pricing notes', 'kingy-ai-launch-intelligence'), kingy_ali_editor_model_access_is_ready($post_id)),
            kingy_ali_editor_readiness_item(__('Benchmark caveat', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'benchmark_caveat') !== ''),
            kingy_ali_editor_readiness_item(__('Source links', 'kingy-ai-launch-intelligence'), kingy_ali_editor_public_source_count($post_id) > 0),
            kingy_ali_editor_readiness_item(__('Verification status', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'verification_status') !== ''),
            kingy_ali_editor_readiness_item(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_editor_meta_text($post_id, 'last_verified') !== ''),
        );

        if (function_exists('kingy_ali_model_source_review_items')) {
            foreach (kingy_ali_model_source_review_items($post_id) as $source_item) {
                if (empty($source_item['queue']) || in_array($source_item['queue'], array('missing_sources', 'missing_benchmark_caveat', 'missing_last_verified'), true)) {
                    continue;
                }

                $items[] = kingy_ali_editor_readiness_item($source_item['label'], !empty($source_item['complete']));
            }
        }

        if (function_exists('kingy_ali_model_is_comparison_selectable') && function_exists('kingy_ali_model_comparison_readiness_issues')) {
            $items[] = kingy_ali_editor_readiness_item(
                __('Comparison selectable based on currently verified fields', 'kingy-ai-launch-intelligence'),
                kingy_ali_model_is_comparison_selectable($post_id) && !kingy_ali_model_comparison_readiness_issues($post_id)
            );
        }

        return $items;
    }

    return array();
}

function kingy_ali_editor_readiness_item($label, $complete) {
    return array(
        'label' => $label,
        'complete' => (bool) $complete,
    );
}

function kingy_ali_editor_meta_text($post_id, $key, $default = '') {
    return kingy_ali_editor_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_editor_text_value($value, $default = '') {
    if (function_exists('kingy_ali_public_profile_text')) {
        return kingy_ali_public_profile_text($value, $default);
    }

    if (!is_scalar($value)) {
        return is_scalar($default) ? (string) $default : '';
    }

    $value = trim((string) $value);
    if ($value === '' && is_scalar($default)) {
        return (string) $default;
    }

    return $value;
}

function kingy_ali_editor_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_editor_public_url_meta_is_ready($post_id, $key) {
    $url = kingy_ali_get_meta($post_id, $key);
    if (function_exists('kingy_ali_schema_url')) {
        return kingy_ali_schema_url($url) !== '';
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($url) !== '';
    }

    if (!is_scalar($url)) {
        return false;
    }

    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }

    $url = esc_url_raw($url, array('http', 'https'));
    if ($url === '') {
        return false;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }

    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true);
}

function kingy_ali_editor_has_term($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    return !is_wp_error($terms) && !empty($terms);
}

function kingy_ali_editor_audience_is_ready($post_id) {
    return kingy_ali_editor_meta_text($post_id, 'who_it_is_for') !== '' || kingy_ali_editor_has_term($post_id, 'kingy_audience');
}

function kingy_ali_editor_pricing_is_ready($post_id) {
    $pricing = kingy_ali_editor_meta_text($post_id, 'pricing');
    if (function_exists('kingy_ali_pricing_is_indexable')) {
        return kingy_ali_pricing_is_indexable($pricing);
    }

    return $pricing !== '';
}

function kingy_ali_editor_launch_has_useful_related_link($post_id) {
    if (function_exists('kingy_ali_launch_has_useful_related_link')) {
        return kingy_ali_launch_has_useful_related_link($post_id);
    }

    return kingy_ali_editor_public_source_count($post_id) > 0;
}

function kingy_ali_editor_public_source_count($post_id) {
    if (function_exists('kingy_ali_public_source_links')) {
        return count(kingy_ali_public_source_links($post_id));
    }

    return kingy_ali_editor_public_url_meta_is_ready($post_id, 'official_url') ? 1 : 0;
}

function kingy_ali_editor_model_has_official_source($post_id) {
    foreach (array('official_url', 'official_announcement_url', 'official_docs_url', 'model_card_url') as $key) {
        if (kingy_ali_editor_public_url_meta_is_ready($post_id, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_editor_model_access_is_ready($post_id) {
    foreach (array('pricing', 'api_pricing', 'license_notes', 'hardware_requirements') as $key) {
        if (kingy_ali_editor_meta_text($post_id, $key) !== '') {
            return true;
        }
    }

    foreach (array('api_available', 'web_app_available', 'local_available', 'open_weight', 'open_source') as $key) {
        if (kingy_ali_editor_meta_text($post_id, $key) !== '') {
            return true;
        }
    }

    return false;
}

function kingy_ali_editor_tool_has_launch_history($post_id) {
    if (function_exists('kingy_ali_tool_has_public_launch_history')) {
        return kingy_ali_tool_has_public_launch_history($post_id);
    }

    $latest_launch_id = kingy_ali_editor_related_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
    if (kingy_ali_related_post_is_public_index_ready($latest_launch_id, 'kingy_ai_launch')) {
        return true;
    }

    if (function_exists('kingy_ali_tool_launch_rollup')) {
        $rollup = kingy_ali_tool_launch_rollup($post_id);
        return !empty($rollup['count']);
    }

    return false;
}

function kingy_ali_editor_company_has_graph_links($post_id) {
    if (function_exists('kingy_ali_company_has_public_graph_links')) {
        return kingy_ali_company_has_public_graph_links($post_id);
    }

    foreach (array('kingy_ai_launch', 'kingy_ai_tool') as $post_type) {
        $query = new WP_Query(
            array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => kingy_ali_meta_key('related_company_id'),
                        'value' => absint($post_id),
                        'compare' => '=',
                    ),
                ),
            )
        );

        if (!empty($query->posts)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_render_field_control($key, $field, $value) {
    $name = 'kingy_ali_meta[' . esc_attr($key) . ']';
    $id = 'kingy_ali_' . esc_attr($key);
    $type = $field['type'];

    if ($type === 'textarea') {
        echo '<textarea class="large-text" rows="4" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        return;
    }

    if ($type === 'select') {
        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
        foreach ($field['options'] as $option_value => $label) {
            echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        return;
    }

    if ($type === 'checkbox') {
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="1"' . checked((bool) $value, true, false) . '>';
        return;
    }

    if ($type === 'post_select') {
        kingy_ali_render_post_select_control($id, $name, $field, $value);
        return;
    }

    $attributes = '';
    foreach (array('min', 'max', 'step') as $attribute) {
        if (isset($field[$attribute])) {
            $attributes .= ' ' . $attribute . '="' . esc_attr($field[$attribute]) . '"';
        }
    }

    $input_type = $type;
    $date_precision_help = '';
    if ($type === 'date' && !empty($field['allow_month'])) {
        $input_type = 'text';
        $attributes .= ' inputmode="numeric" placeholder="YYYY-MM or YYYY-MM-DD" pattern="\d{4}-\d{2}(-\d{2})?"';
        $date_precision_help = '<p class="description">' . esc_html__('Use YYYY-MM when only the launch month is publicly supported, or YYYY-MM-DD when the exact day is verified.', 'kingy-ai-launch-intelligence') . '</p>';
    }

    echo '<input class="regular-text" type="' . esc_attr($input_type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $attributes . '>';
    echo $date_precision_help;
}

function kingy_ali_render_post_select_control($id, $name, $field, $value) {
    $post_type = isset($field['post_type']) ? sanitize_key($field['post_type']) : '';
    $selected_id = absint($value);
    $empty_label = isset($field['empty_label']) ? $field['empty_label'] : __('Select a related record', 'kingy-ai-launch-intelligence');
    $choices = kingy_ali_post_select_choices($post_type, $selected_id);

    echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" class="regular-text">';
    echo '<option value="">' . esc_html($empty_label) . '</option>';
    foreach ($choices as $choice) {
        echo '<option value="' . esc_attr($choice['id']) . '"' . selected($selected_id, $choice['id'], false) . '>' . esc_html($choice['label']) . '</option>';
    }
    echo '</select>';

    if ($selected_id) {
        $edit_link = get_edit_post_link($selected_id);
        $view_link = get_permalink($selected_id);
        echo '<p class="description">';
        if ($edit_link) {
            echo '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit selected record', 'kingy-ai-launch-intelligence') . '</a>';
        }
        if ($view_link) {
            echo $edit_link ? ' · ' : '';
            echo '<a href="' . esc_url($view_link) . '">' . esc_html__('View selected record', 'kingy-ai-launch-intelligence') . '</a>';
        }
        echo '</p>';
    }
}

function kingy_ali_post_select_choices($post_type, $selected_id = 0, $limit = 100) {
    $post_type = sanitize_key($post_type);
    if (!in_array($post_type, array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'), true)) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => max(1, absint($limit)),
            'fields' => 'ids',
            'orderby' => $post_type === 'kingy_ai_launch' ? 'date' : 'title',
            'order' => $post_type === 'kingy_ai_launch' ? 'DESC' : 'ASC',
            'no_found_rows' => true,
        )
    );

    $ids = is_array($query->posts) ? array_map('absint', $query->posts) : array();
    if ($selected_id && get_post_type($selected_id) === $post_type && !in_array($selected_id, $ids, true)) {
        array_unshift($ids, $selected_id);
    }

    $choices = array();
    foreach (array_values(array_unique($ids)) as $post_id) {
        $choices[] = array(
            'id' => $post_id,
            'label' => kingy_ali_post_select_label($post_id),
        );
    }

    return $choices;
}

function kingy_ali_post_select_label($post_id) {
    $title = get_the_title($post_id);
    if ($title === '') {
        $title = __('Untitled record', 'kingy-ai-launch-intelligence');
    }

    $parts = array($title);
    $status = get_post_status($post_id);
    if ($status && $status !== 'publish') {
        $status_object = get_post_status_object($status);
        $parts[] = $status_object && !empty($status_object->label) ? $status_object->label : ucfirst(str_replace(array('_', '-'), ' ', sanitize_key($status)));
    }

    $launch_date = get_post_type($post_id) === 'kingy_ai_launch' ? kingy_ali_editor_meta_text($post_id, 'launch_date') : '';
    if ($launch_date) {
        $parts[] = $launch_date;
    }

    if (get_post_type($post_id) === 'kingy_ai_model') {
        $provider = kingy_ali_editor_meta_text($post_id, 'provider_name');
        if ($provider) {
            $parts[] = $provider;
        }

        $release_date = kingy_ali_editor_meta_text($post_id, 'release_date');
        if ($release_date) {
            $parts[] = $release_date;
        }
    }

    return implode(' · ', $parts);
}

function kingy_ali_save_launch_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!kingy_ali_save_meta_fields($post_id, kingy_ali_launch_meta_fields(), 'kingy_ali_launch_meta_nonce')) {
        return;
    }

    kingy_ali_sync_launch_relationships($post_id);
}

function kingy_ali_handle_launch_status_transition($new_status, $old_status, $post) {
    static $reverting = false;

    if ($reverting || !$post instanceof WP_Post || $post->post_type !== 'kingy_ai_launch' || $new_status === $old_status) {
        return;
    }

    if ($new_status !== 'publish' || wp_is_post_revision($post->ID)) {
        return;
    }

    $result = kingy_ali_sync_launch_relationships($post->ID);
    if (empty($result['blocked'])) {
        return;
    }

    $reverting = true;
    wp_update_post(
        array(
            'ID' => $post->ID,
            'post_status' => 'draft',
        )
    );
    $reverting = false;
}

function kingy_ali_launch_companion_profile_gate($post_id, $post_type) {
    $post_id = absint($post_id);
    $post_type = sanitize_key((string) $post_type);
    $blockers = array();

    if (!$post_id) {
        return array(
            'approved' => true,
            'post_id' => 0,
            'post_type' => $post_type,
            'blockers' => array(),
        );
    }

    if (!in_array($post_type, array('kingy_ai_tool', 'kingy_ai_company'), true) || get_post_type($post_id) !== $post_type) {
        $blockers[] = 'invalid_companion_type';
    } elseif (get_post_status($post_id) !== 'publish') {
        $blockers[] = 'companion_not_published';
    } else {
        if ((int) get_post_thumbnail_id($post_id) <= 0) {
            $blockers[] = 'companion_missing_featured_image';
        }

        foreach (kingy_ali_editor_readiness_items($post_id, $post_type) as $item) {
            if (empty($item['complete'])) {
                $blockers[] = 'companion_readiness_incomplete';
                break;
            }
        }

        if (function_exists('kingy_ali_profile_should_noindex') && kingy_ali_profile_should_noindex($post_id)) {
            $blockers[] = 'companion_noindex';
        }
    }

    return array(
        'approved' => empty($blockers),
        'post_id' => $post_id,
        'post_type' => $post_type,
        'blockers' => array_values(array_unique($blockers)),
    );
}

function kingy_ali_sync_launch_relationships($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || get_post_type($post_id) !== 'kingy_ai_launch') {
        return array(
            'tool_id' => 0,
            'company_id' => 0,
        );
    }

    $tool_id = kingy_ali_editor_related_id(get_post_meta($post_id, kingy_ali_meta_key('related_tool_id'), true));
    $company_id = kingy_ali_editor_related_id(get_post_meta($post_id, kingy_ali_meta_key('related_company_id'), true));
    $tool_gate = kingy_ali_launch_companion_profile_gate($tool_id, 'kingy_ai_tool');
    $company_gate = kingy_ali_launch_companion_profile_gate($company_id, 'kingy_ai_company');
    $blockers = array_merge($tool_gate['blockers'], $company_gate['blockers']);

    if ($blockers) {
        return array(
            'tool_id' => 0,
            'company_id' => 0,
            'blocked' => true,
            'blockers' => array_values(array_unique($blockers)),
        );
    }

    kingy_ali_sync_derived_attributes($post_id);

    return array(
        'tool_id' => $tool_id,
        'company_id' => $company_id,
        'blocked' => false,
        'blockers' => array(),
    );
}

function kingy_ali_save_tool_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!kingy_ali_save_meta_fields($post_id, kingy_ali_tool_meta_fields(), 'kingy_ali_tool_meta_nonce')) {
        return;
    }

    $related_company_id = kingy_ali_editor_related_id(get_post_meta($post_id, kingy_ali_meta_key('related_company_id'), true));
    if (!$related_company_id) {
        kingy_ali_sync_company_from_tool($post_id);
    }

    kingy_ali_sync_derived_attributes($post_id);
}

function kingy_ali_save_company_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!kingy_ali_save_meta_fields($post_id, kingy_ali_company_meta_fields(), 'kingy_ali_company_meta_nonce')) {
        return;
    }

    kingy_ali_sync_derived_attributes($post_id);
}

function kingy_ali_save_model_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    kingy_ali_save_meta_fields($post_id, kingy_ali_model_meta_fields(), 'kingy_ali_model_meta_nonce');
}

function kingy_ali_save_meta_fields($post_id, $fields, $nonce_name) {
    if (!wp_verify_nonce(sanitize_text_field(kingy_ali_meta_post_value($nonce_name)), $nonce_name)) {
        return false;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return false;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return false;
    }

    $posted_meta = kingy_ali_meta_post_array('kingy_ali_meta');

    foreach ($fields as $key => $field) {
        $raw_value = isset($posted_meta[$key]) ? $posted_meta[$key] : '';
        if ($field['type'] === 'checkbox') {
            $raw_value = isset($posted_meta[$key]) ? '1' : '';
        }

        $value = kingy_ali_sanitize_meta_value($raw_value, $field);
        if ($value === '' || $value === null) {
            delete_post_meta($post_id, kingy_ali_meta_key($key));
        } else {
            update_post_meta($post_id, kingy_ali_meta_key($key), $value);
        }
    }

    return true;
}

function kingy_ali_meta_post_value($key) {
    $values = kingy_ali_meta_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_meta_post_array($key) {
    $values = kingy_ali_meta_post_values();
    if (!isset($values[$key])) {
        return array();
    }

    if (!is_array($values[$key])) {
        return array();
    }

    $value = wp_unslash($values[$key]);
    return is_array($value) ? $value : array();
}

function kingy_ali_meta_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_sanitize_meta_value($value, $field) {
    if (is_array($value)) {
        $value = '';
    }

    $type = isset($field['type']) ? $field['type'] : 'text';

    if ($type === 'url') {
        return kingy_ali_sanitize_meta_url($value);
    }

    if ($type === 'email') {
        return sanitize_email($value);
    }

    if ($type === 'textarea') {
        return sanitize_textarea_field($value);
    }

    if ($type === 'number') {
        if ($value === '' || !is_scalar($value) || !is_numeric($value)) {
            return '';
        }

        $number = (float) $value;
        if (isset($field['min'])) {
            $number = max((float) $field['min'], $number);
        }
        if (isset($field['max'])) {
            $number = min((float) $field['max'], $number);
        }

        return $number;
    }

    if ($type === 'post_select') {
        $post_id = absint($value);
        if (!$post_id) {
            return '';
        }

        $post_type = isset($field['post_type']) ? sanitize_key($field['post_type']) : '';
        if ($post_type && get_post_type($post_id) !== $post_type) {
            return '';
        }

        return $post_id;
    }

    if ($type === 'checkbox') {
        return !empty($value) ? '1' : '';
    }

    if ($type === 'select') {
        $value = sanitize_key($value);
        if (!isset($field['options'][$value])) {
            return '';
        }

        return $value;
    }

    if ($type === 'date') {
        $value = sanitize_text_field($value);
        if (!empty($field['allow_month']) && preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            $month = (int) $matches[2];
            return $month >= 1 && $month <= 12 ? $value : '';
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return '';
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : '';
    }

    return sanitize_text_field($value);
}

function kingy_ali_sanitize_meta_url($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $url = esc_url_raw($value, array('http', 'https'));
    return kingy_ali_meta_url_is_absolute_http($url) ? $url : '';
}

function kingy_ali_meta_url_is_absolute_http($url) {
    $parts = wp_parse_url((string) $url);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? trim((string) $parts['host']) : '';
    return in_array($scheme, array('http', 'https'), true) && $host !== '';
}

function kingy_ali_get_meta($post_id, $key, $default = '') {
    $value = get_post_meta($post_id, kingy_ali_meta_key($key), true);
    return $value === '' ? $default : $value;
}
