<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_tool_directory', 'kingy_ali_shortcode_tool_directory');
add_shortcode('kingy_company_directory', 'kingy_ali_shortcode_company_directory');

function kingy_ali_shortcode_tool_directory($atts = array()) {
    kingy_ali_enqueue_assets();
    $atts = shortcode_atts(array('limit' => 0, 'per_page' => 24, 'heading' => 'yes'), $atts, 'kingy_tool_directory');
    $filters = kingy_ali_directory_request_filters();
    $core_page = absint(get_query_var('paged'));
    $legacy_page = absint(kingy_ali_request_get_value('kali_tool_page'));
    $page = max(1, $core_page ? $core_page : $legacy_page);
    $query = kingy_ali_query_tool_directory(
        array_merge(
            $filters,
            array(
                'limit' => absint($atts['limit']),
                'track_search' => kingy_ali_directory_has_filters($filters),
            )
        )
    );
    $pagination = kingy_ali_paginate_directory_query($query, $page, absint($atts['per_page']));
    $query = $pagination['query'];

    ob_start();
    echo '<section class="kingy-ali-directory kingy-ali-tool-directory">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            __('AI Product Records', 'kingy-ai-launch-intelligence'),
            __('Search durable AI product records with launch history, pricing status, demos, target users and source notes. Each profile shows what is verified, what the company claims and what is still unknown.', 'kingy-ai-launch-intelligence'),
            'tools'
        );
    }
    echo kingy_ali_render_directory_filters($filters, 'tools');
    echo kingy_ali_render_tool_directory_results_summary($pagination);
    echo kingy_ali_render_tool_directory_grid($query);
    echo kingy_ali_render_tool_directory_pagination($pagination, $filters);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_render_tool_directory_results_summary($pagination) {
    $total = isset($pagination['total']) ? absint($pagination['total']) : 0;
    if ($total < 1) {
        return '';
    }

    return sprintf(
        '<p class="kingy-ali-directory-results" role="status">%s</p>',
        esc_html(
            sprintf(
                __('Showing %1$s–%2$s of %3$s product records', 'kingy-ai-launch-intelligence'),
                number_format_i18n(absint($pagination['first_result'])),
                number_format_i18n(absint($pagination['last_result'])),
                number_format_i18n($total)
            )
        )
    );
}

function kingy_ali_tool_directory_page_url($page, $filters) {
    $page = max(1, absint($page));
    $url = get_pagenum_link($page);
    $args = array();
    $filter_map = array(
        'kali_q' => 'q',
        'kali_category' => 'category',
        'kali_audience' => 'audience',
        'kali_attribute' => 'attribute',
        'kali_free_plan' => 'free_plan',
        'kali_api_available' => 'api_available',
        'kali_open_weight' => 'open_source_or_open_weight',
        'kali_video_demo' => 'video_demo',
    );

    foreach ($filter_map as $request_key => $filter_key) {
        if (!empty($filters[$filter_key])) {
            $args[$request_key] = $filters[$filter_key];
        }
    }

    return empty($args) ? $url : add_query_arg($args, $url);
}

function kingy_ali_render_tool_directory_pagination($pagination, $filters) {
    $current_page = isset($pagination['current_page']) ? absint($pagination['current_page']) : 1;
    $total_pages = isset($pagination['total_pages']) ? absint($pagination['total_pages']) : 1;
    if ($total_pages < 2) {
        return '';
    }

    $page_numbers = array(1, $total_pages);
    for ($page = max(1, $current_page - 2); $page <= min($total_pages, $current_page + 2); $page++) {
        $page_numbers[] = $page;
    }
    $page_numbers = array_values(array_unique(array_map('absint', $page_numbers)));
    sort($page_numbers, SORT_NUMERIC);

    ob_start();
    ?>
    <nav class="kingy-ali-pagination" aria-label="<?php esc_attr_e('Product record directory pages', 'kingy-ai-launch-intelligence'); ?>">
        <ul class="kingy-ali-pagination__list">
            <?php if ($current_page > 1) : ?>
                <li><a class="kingy-ali-pagination__link kingy-ali-pagination__link--wide" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('Product records page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($current_page - 1))); ?>" data-event-surface="tool_directory_pagination" href="<?php echo esc_url(kingy_ali_tool_directory_page_url($current_page - 1, $filters)); ?>" rel="prev"><?php esc_html_e('Previous', 'kingy-ai-launch-intelligence'); ?></a></li>
            <?php endif; ?>
            <?php $previous_page = 0; ?>
            <?php foreach ($page_numbers as $page_number) : ?>
                <?php if ($previous_page && $page_number > $previous_page + 1) : ?>
                    <li class="kingy-ali-pagination__ellipsis"><span aria-hidden="true">…</span><span class="screen-reader-text"><?php esc_html_e('More pages', 'kingy-ai-launch-intelligence'); ?></span></li>
                <?php endif; ?>
                <?php if ($page_number === $current_page) : ?>
                    <li><span class="kingy-ali-pagination__link is-current" aria-current="page"><span class="screen-reader-text"><?php esc_html_e('Page', 'kingy-ai-launch-intelligence'); ?> </span><?php echo esc_html(number_format_i18n($page_number)); ?></span></li>
                <?php else : ?>
                    <li><a class="kingy-ali-pagination__link" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('Product records page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($page_number))); ?>" data-event-surface="tool_directory_pagination" aria-label="<?php echo esc_attr(sprintf(__('Go to product records page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($page_number))); ?>" href="<?php echo esc_url(kingy_ali_tool_directory_page_url($page_number, $filters)); ?>"><?php echo esc_html(number_format_i18n($page_number)); ?></a></li>
                <?php endif; ?>
                <?php $previous_page = $page_number; ?>
            <?php endforeach; ?>
            <?php if ($current_page < $total_pages) : ?>
                <li><a class="kingy-ali-pagination__link kingy-ali-pagination__link--wide" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('Product records page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($current_page + 1))); ?>" data-event-surface="tool_directory_pagination" href="<?php echo esc_url(kingy_ali_tool_directory_page_url($current_page + 1, $filters)); ?>" rel="next"><?php esc_html_e('Next', 'kingy-ai-launch-intelligence'); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_directory_results_summary($pagination) {
    $total = isset($pagination['total']) ? absint($pagination['total']) : 0;
    if ($total < 1) {
        return '';
    }

    return sprintf(
        '<p class="kingy-ali-directory-results" role="status">%s</p>',
        esc_html(
            sprintf(
                __('Showing %1$s–%2$s of %3$s models', 'kingy-ai-launch-intelligence'),
                number_format_i18n(absint($pagination['first_result'])),
                number_format_i18n(absint($pagination['last_result'])),
                number_format_i18n($total)
            )
        )
    );
}

function kingy_ali_model_directory_page_url($page, $filters) {
    $page = max(1, absint($page));
    $url = get_pagenum_link($page);
    $args = array();
    $filter_map = array(
        'kali_q' => 'q',
        'kali_model_provider' => 'provider',
        'kali_model_family' => 'family',
        'kali_model_modality' => 'modality',
        'kali_model_use_case' => 'use_case',
        'kali_model_access_type' => 'access_type',
        'kali_model_license_type' => 'license_type',
        'kali_model_status' => 'status',
        'kali_api_available' => 'api_available',
        'kali_open_weight' => 'open_weight',
        'kali_local_available' => 'local_available',
    );

    foreach ($filter_map as $request_key => $filter_key) {
        if (!empty($filters[$filter_key])) {
            $args[$request_key] = $filters[$filter_key];
        }
    }

    return empty($args) ? $url : add_query_arg($args, $url);
}

function kingy_ali_render_model_directory_pagination($pagination, $filters) {
    $current_page = isset($pagination['current_page']) ? absint($pagination['current_page']) : 1;
    $total_pages = isset($pagination['total_pages']) ? absint($pagination['total_pages']) : 1;
    if ($total_pages < 2) {
        return '';
    }

    $page_numbers = array(1, $total_pages);
    for ($page = max(1, $current_page - 2); $page <= min($total_pages, $current_page + 2); $page++) {
        $page_numbers[] = $page;
    }
    $page_numbers = array_values(array_unique(array_map('absint', $page_numbers)));
    sort($page_numbers, SORT_NUMERIC);

    ob_start();
    ?>
    <nav class="kingy-ali-pagination" aria-label="<?php esc_attr_e('AI model directory pages', 'kingy-ai-launch-intelligence'); ?>">
        <ul class="kingy-ali-pagination__list">
            <?php if ($current_page > 1) : ?>
                <li><a class="kingy-ali-pagination__link kingy-ali-pagination__link--wide" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('AI models page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($current_page - 1))); ?>" data-event-surface="model_directory_pagination" href="<?php echo esc_url(kingy_ali_model_directory_page_url($current_page - 1, $filters)); ?>" rel="prev"><?php esc_html_e('Previous', 'kingy-ai-launch-intelligence'); ?></a></li>
            <?php endif; ?>
            <?php $previous_page = 0; ?>
            <?php foreach ($page_numbers as $page_number) : ?>
                <?php if ($previous_page && $page_number > $previous_page + 1) : ?>
                    <li class="kingy-ali-pagination__ellipsis"><span aria-hidden="true">…</span><span class="screen-reader-text"><?php esc_html_e('More pages', 'kingy-ai-launch-intelligence'); ?></span></li>
                <?php endif; ?>
                <?php if ($page_number === $current_page) : ?>
                    <li><span class="kingy-ali-pagination__link is-current" aria-current="page"><span class="screen-reader-text"><?php esc_html_e('Page', 'kingy-ai-launch-intelligence'); ?> </span><?php echo esc_html(number_format_i18n($page_number)); ?></span></li>
                <?php else : ?>
                    <li><a class="kingy-ali-pagination__link" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('AI models page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($page_number))); ?>" data-event-surface="model_directory_pagination" aria-label="<?php echo esc_attr(sprintf(__('Go to AI models page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($page_number))); ?>" href="<?php echo esc_url(kingy_ali_model_directory_page_url($page_number, $filters)); ?>"><?php echo esc_html(number_format_i18n($page_number)); ?></a></li>
                <?php endif; ?>
                <?php $previous_page = $page_number; ?>
            <?php endforeach; ?>
            <?php if ($current_page < $total_pages) : ?>
                <li><a class="kingy-ali-pagination__link kingy-ali-pagination__link--wide" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('AI models page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($current_page + 1))); ?>" data-event-surface="model_directory_pagination" href="<?php echo esc_url(kingy_ali_model_directory_page_url($current_page + 1, $filters)); ?>" rel="next"><?php esc_html_e('Next', 'kingy-ai-launch-intelligence'); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
    return ob_get_clean();
}

add_action('init', 'kingy_ali_register_paginated_model_directory_shortcode', 99);
function kingy_ali_register_paginated_model_directory_shortcode() {
    remove_shortcode('kingy_ai_model_directory');
    add_shortcode('kingy_ai_model_directory', 'kingy_ali_shortcode_paginated_model_directory');
}

function kingy_ali_shortcode_paginated_model_directory($atts = array()) {
    try {
        return kingy_ali_shortcode_paginated_model_directory_inner($atts);
    } catch (Throwable $throwable) {
        return kingy_ali_render_model_emergency_safe_fallback('directory', $atts, $throwable);
    }
}

function kingy_ali_shortcode_paginated_model_directory_inner($atts = array()) {
    kingy_ali_enqueue_model_assets();
    $atts = shortcode_atts(array('limit' => 0, 'per_page' => 24, 'heading' => 'yes'), $atts, 'kingy_ai_model_directory');
    $filters = kingy_ali_model_request_filters();
    $core_page = absint(get_query_var('paged'));
    $legacy_page = absint(kingy_ali_request_get_value('kali_model_page'));
    $page = max(1, $core_page ? $core_page : $legacy_page);
    $query = kingy_ali_query_model_directory(
        array_merge(
            $filters,
            array(
                'limit' => absint($atts['limit']),
                'track_search' => kingy_ali_model_directory_has_filters($filters),
            )
        )
    );
    $pagination = kingy_ali_paginate_directory_query($query, $page, absint($atts['per_page']));
    $query = $pagination['query'];
    $filter_form = kingy_ali_render_model_directory_filters($filters);
    $filter_form = str_replace(
        '<form class="kingy-ali-search kingy-ali-model-search" method="get">',
        '<form class="kingy-ali-search kingy-ali-model-search" method="get" action="' . esc_url(home_url('/ai-models/')) . '">',
        $filter_form
    );

    ob_start();
    echo '<section class="kingy-ali-model-hub kingy-ali-model-directory">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_model_hub_hero();
    }
    echo kingy_ali_render_model_hub_foundation_sections();
    echo '<div class="kingy-ali-model-disclosure"><strong>' . esc_html__('Benchmark caveat', 'kingy-ai-launch-intelligence') . '</strong><span>' . esc_html(kingy_ali_model_benchmark_caveat_note()) . '</span></div>';
    echo $filter_form;
    echo kingy_ali_render_model_directory_results_summary($pagination);
    echo kingy_ali_render_model_directory_grid($query);
    echo kingy_ali_render_model_directory_pagination($pagination, $filters);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_shortcode_company_directory($atts = array()) {
    kingy_ali_enqueue_assets();
    $atts = shortcode_atts(array('limit' => 0, 'per_page' => 24, 'heading' => 'yes'), $atts, 'kingy_company_directory');
    $filters = kingy_ali_directory_request_filters();
    $core_page = absint(get_query_var('paged'));
    $legacy_page = absint(kingy_ali_request_get_value('kali_company_page'));
    $page = max(1, $core_page ? $core_page : $legacy_page);
    $query = kingy_ali_query_company_directory(
        array_merge(
            $filters,
            array(
                'limit' => absint($atts['limit']),
                'track_search' => kingy_ali_directory_has_filters($filters),
            )
        )
    );
    $pagination = kingy_ali_paginate_company_directory_query($query, $page, absint($atts['per_page']));
    $query = $pagination['query'];

    ob_start();
    echo '<section class="kingy-ali-directory kingy-ali-company-directory">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            __('AI Company Directory', 'kingy-ai-launch-intelligence'),
            __('Browse AI company and founder profiles connected to launch history, tool portfolios, funding notes, categories, and audience focus.', 'kingy-ai-launch-intelligence')
        );
    }
    echo kingy_ali_render_directory_filters($filters, 'companies');
    echo kingy_ali_render_company_directory_results_summary($pagination);
    echo kingy_ali_render_company_directory_grid($query);
    echo kingy_ali_render_company_directory_pagination($pagination, $filters);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_paginate_directory_query($query, $requested_page = 1, $per_page = 24) {
    $per_page = max(1, min(48, absint($per_page)));
    $all_posts = is_object($query) && isset($query->posts) ? array_values((array) $query->posts) : array();
    $total = count($all_posts);
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = min(max(1, absint($requested_page)), $total_pages);
    $offset = ($current_page - 1) * $per_page;
    $page_posts = array_slice($all_posts, $offset, $per_page);

    if (is_object($query)) {
        $query->posts = $page_posts;
        $query->post_count = count($page_posts);
        $query->found_posts = $total;
        $query->max_num_pages = $total_pages;
        $query->current_post = -1;
        $query->in_the_loop = false;
    }

    return array(
        'query' => $query,
        'current_page' => $current_page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages,
        'first_result' => $total > 0 ? $offset + 1 : 0,
        'last_result' => $total > 0 ? min($offset + $per_page, $total) : 0,
    );
}

function kingy_ali_paginate_company_directory_query($query, $requested_page = 1, $per_page = 24) {
    return kingy_ali_paginate_directory_query($query, $requested_page, $per_page);
}

function kingy_ali_render_company_directory_results_summary($pagination) {
    $total = isset($pagination['total']) ? absint($pagination['total']) : 0;
    if ($total < 1) {
        return '';
    }

    return sprintf(
        '<p class="kingy-ali-directory-results" role="status">%s</p>',
        esc_html(
            sprintf(
                __('Showing %1$s–%2$s of %3$s companies', 'kingy-ai-launch-intelligence'),
                number_format_i18n(absint($pagination['first_result'])),
                number_format_i18n(absint($pagination['last_result'])),
                number_format_i18n($total)
            )
        )
    );
}

function kingy_ali_company_directory_page_url($page, $filters) {
    $page = max(1, absint($page));
    $url = get_pagenum_link($page);
    $args = array();
    $filter_map = array(
        'kali_q' => 'q',
        'kali_category' => 'category',
        'kali_audience' => 'audience',
        'kali_attribute' => 'attribute',
    );

    foreach ($filter_map as $request_key => $filter_key) {
        if (!empty($filters[$filter_key])) {
            $args[$request_key] = $filters[$filter_key];
        }
    }

    return empty($args) ? $url : add_query_arg($args, $url);
}

function kingy_ali_render_company_directory_pagination($pagination, $filters) {
    $current_page = isset($pagination['current_page']) ? absint($pagination['current_page']) : 1;
    $total_pages = isset($pagination['total_pages']) ? absint($pagination['total_pages']) : 1;
    if ($total_pages < 2) {
        return '';
    }

    $page_numbers = array(1, $total_pages);
    for ($page = max(1, $current_page - 2); $page <= min($total_pages, $current_page + 2); $page++) {
        $page_numbers[] = $page;
    }
    $page_numbers = array_values(array_unique(array_map('absint', $page_numbers)));
    sort($page_numbers, SORT_NUMERIC);

    ob_start();
    ?>
    <nav class="kingy-ali-pagination" aria-label="<?php esc_attr_e('Company directory pages', 'kingy-ai-launch-intelligence'); ?>">
        <ul class="kingy-ali-pagination__list">
            <?php if ($current_page > 1) : ?>
                <li><a class="kingy-ali-pagination__link kingy-ali-pagination__link--wide" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('Company directory page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($current_page - 1))); ?>" data-event-surface="company_directory_pagination" href="<?php echo esc_url(kingy_ali_company_directory_page_url($current_page - 1, $filters)); ?>" rel="prev"><?php esc_html_e('Previous', 'kingy-ai-launch-intelligence'); ?></a></li>
            <?php endif; ?>
            <?php $previous_page = 0; ?>
            <?php foreach ($page_numbers as $page_number) : ?>
                <?php if ($previous_page && $page_number > $previous_page + 1) : ?>
                    <li class="kingy-ali-pagination__ellipsis"><span aria-hidden="true">…</span><span class="screen-reader-text"><?php esc_html_e('More pages', 'kingy-ai-launch-intelligence'); ?></span></li>
                <?php endif; ?>
                <?php if ($page_number === $current_page) : ?>
                    <li><span class="kingy-ali-pagination__link is-current" aria-current="page"><span class="screen-reader-text"><?php esc_html_e('Page', 'kingy-ai-launch-intelligence'); ?> </span><?php echo esc_html(number_format_i18n($page_number)); ?></span></li>
                <?php else : ?>
                    <li><a class="kingy-ali-pagination__link" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('Company directory page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($page_number))); ?>" data-event-surface="company_directory_pagination" aria-label="<?php echo esc_attr(sprintf(__('Go to company directory page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($page_number))); ?>" href="<?php echo esc_url(kingy_ali_company_directory_page_url($page_number, $filters)); ?>"><?php echo esc_html(number_format_i18n($page_number)); ?></a></li>
                <?php endif; ?>
                <?php $previous_page = $page_number; ?>
            <?php endforeach; ?>
            <?php if ($current_page < $total_pages) : ?>
                <li><a class="kingy-ali-pagination__link kingy-ali-pagination__link--wide" data-kingy-ali-track="clicked_filter" data-event-label="<?php echo esc_attr(sprintf(__('Company directory page %s', 'kingy-ai-launch-intelligence'), number_format_i18n($current_page + 1))); ?>" data-event-surface="company_directory_pagination" href="<?php echo esc_url(kingy_ali_company_directory_page_url($current_page + 1, $filters)); ?>" rel="next"><?php esc_html_e('Next', 'kingy-ai-launch-intelligence'); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
    return ob_get_clean();
}

function kingy_ali_directory_request_filters() {
    return array(
        'q' => kingy_ali_sanitize_public_search_query(kingy_ali_request_get_value('kali_q')),
        'category' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_category')),
        'audience' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_audience')),
        'attribute' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_attribute')),
        'free_plan' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_free_plan')),
        'api_available' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_api_available')),
        'open_source_or_open_weight' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_open_weight')),
        'video_demo' => kingy_ali_sanitize_yes_filter(kingy_ali_request_get_value('kali_video_demo')),
    );
}

function kingy_ali_directory_has_filters($filters) {
    return (bool) array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    });
}

function kingy_ali_query_tool_directory($args = array()) {
    $defaults = array(
        'limit' => 24,
        'q' => '',
        'category' => '',
        'audience' => '',
        'attribute' => '',
        'free_plan' => '',
        'api_available' => '',
        'open_source_or_open_weight' => '',
        'video_demo' => '',
        'track_search' => false,
    );
    $args = wp_parse_args($args, $defaults);
    $limit = absint($args['limit']);

    $query_args = array(
        'post_type' => 'kingy_ai_tool',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    if ($args['q']) {
        $matching_ids = kingy_ali_directory_matching_post_ids('kingy_ai_tool', kingy_ali_tool_search_meta_keys(), array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute'), $args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
    }

    kingy_ali_apply_directory_tax_filters($query_args, $args);
    kingy_ali_apply_directory_meta_filters($query_args, $args, true);
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $query = kingy_ali_run_public_filtered_query(
        $query_args,
        $limit,
        function ($post) use ($args) {
            if (!kingy_ali_public_query_accepts_index_ready_post($post)) {
                return false;
            }

            return empty($args['video_demo']) || kingy_ali_public_query_accepts_valid_url_meta($post, array('demo_url'));
        }
    );
    if ($args['track_search']) {
        kingy_ali_track_directory_search('tool_directory', $args, $query);
    }

    return $query;
}

function kingy_ali_query_company_directory($args = array()) {
    $defaults = array(
        'limit' => 160,
        'q' => '',
        'category' => '',
        'audience' => '',
        'attribute' => '',
        'track_search' => false,
    );
    $args = wp_parse_args($args, $defaults);
    $limit = absint($args['limit']);

    $query_args = array(
        'post_type' => 'kingy_ai_company',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    if ($args['q']) {
        $matching_ids = kingy_ali_directory_matching_post_ids('kingy_ai_company', kingy_ali_company_search_meta_keys(), array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute'), $args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
    }

    kingy_ali_apply_directory_tax_filters($query_args, $args);
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $query = kingy_ali_run_public_filtered_query(
        $query_args,
        $limit,
        function ($post) {
            return kingy_ali_company_directory_card_is_public(kingy_ali_public_query_post_id($post));
        }
    );
    if ($args['track_search']) {
        kingy_ali_track_directory_search('company_directory', $args, $query);
    }

    return $query;
}

function kingy_ali_filter_company_directory_query_posts($query, $limit = 0) {
    if (!is_object($query) || !isset($query->posts)) {
        return $query;
    }

    $posts = array();
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id || !kingy_ali_company_directory_card_is_public($post_id)) {
            continue;
        }

        $posts[] = $post;
    }

    $limit = absint($limit);
    if ($limit > 0) {
        $posts = array_slice($posts, 0, $limit);
    }

    return kingy_ali_replace_query_posts($query, $posts, $limit);
}

function kingy_ali_company_directory_card_is_public($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || !kingy_ali_related_post_is_public($post_id, 'kingy_ai_company')) {
        return false;
    }

    if (function_exists('kingy_ali_profile_should_noindex') && kingy_ali_profile_should_noindex($post_id)) {
        return false;
    }

    return kingy_ali_company_directory_has_profile_basics($post_id)
        || kingy_ali_company_directory_has_public_graph($post_id);
}

function kingy_ali_company_directory_has_profile_basics($post_id) {
    $summary = kingy_ali_public_profile_meta_text($post_id, 'company_summary', get_the_excerpt($post_id));
    $official_url = kingy_ali_sanitize_public_source_url(kingy_ali_get_meta($post_id, 'official_url'));

    return $summary !== '' && $official_url !== '';
}

function kingy_ali_company_directory_has_public_graph($post_id) {
    if (!function_exists('kingy_ali_company_related_count')) {
        return false;
    }

    return kingy_ali_company_public_related_count($post_id, 'kingy_ai_launch') > 0
        || kingy_ali_company_public_related_count($post_id, 'kingy_ai_tool') > 0;
}

function kingy_ali_track_directory_search($context, $args, $query) {
    static $tracked = array();

    $filters = kingy_ali_directory_event_filters($context, $args);
    $tracking_key = $context . ':' . md5(wp_json_encode($filters));
    if (isset($tracked[$tracking_key])) {
        return;
    }

    kingy_ali_track_event(
        $query->found_posts === 0 ? 'zero_result_search' : 'search',
        array(
            'event_label' => $context,
            'query_text' => isset($args['q']) ? $args['q'] : '',
            'filters' => $filters,
            'result_count' => (int) $query->found_posts,
        )
    );

    $tracked[$tracking_key] = true;
}

function kingy_ali_directory_event_filters($context, $args) {
    $filters = array(
        'context' => $context,
        'category' => isset($args['category']) ? $args['category'] : '',
        'audience' => isset($args['audience']) ? $args['audience'] : '',
        'attribute' => isset($args['attribute']) ? $args['attribute'] : '',
    );

    if ($context === 'tool_directory') {
        $filters['free_plan'] = isset($args['free_plan']) ? $args['free_plan'] : '';
        $filters['api_available'] = isset($args['api_available']) ? $args['api_available'] : '';
        $filters['open_source_or_open_weight'] = isset($args['open_source_or_open_weight']) ? $args['open_source_or_open_weight'] : '';
        $filters['video_demo'] = isset($args['video_demo']) ? $args['video_demo'] : '';
    }

    return array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    });
}

function kingy_ali_directory_matching_post_ids($post_type, $meta_keys, $taxonomies, $query) {
    $ids = array_merge(
        kingy_ali_search_post_ids($post_type, $query),
        kingy_ali_search_meta_post_ids($post_type, $meta_keys, $query),
        kingy_ali_search_taxonomy_post_ids($post_type, $taxonomies, $query)
    );

    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
    sort($ids);
    return $ids;
}

function kingy_ali_apply_directory_tax_filters(&$query_args, $args) {
    $tax_query = array();
    foreach (array(
        'kingy_launch_category' => isset($args['category']) ? $args['category'] : '',
        'kingy_audience' => isset($args['audience']) ? $args['audience'] : '',
        'kingy_tool_attribute' => isset($args['attribute']) ? $args['attribute'] : '',
    ) as $taxonomy => $slug) {
        if ($slug) {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => function_exists('kingy_ali_public_filter_slug_terms') ? kingy_ali_public_filter_slug_terms($taxonomy, $slug) : $slug,
            );
        }
    }

    if ($tax_query) {
        $query_args['tax_query'] = $tax_query;
    }
}

function kingy_ali_apply_directory_meta_filters(&$query_args, $args, $include_demo_filter = false) {
    $meta_query = array();
    foreach (array('free_plan', 'api_available', 'open_source_or_open_weight') as $key) {
        if (!empty($args[$key])) {
            $meta_query[] = array(
                'key' => kingy_ali_meta_key($key),
                'value' => $args[$key],
                'compare' => '=',
            );
        }
    }

    if ($include_demo_filter && !empty($args['video_demo'])) {
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('demo_url'),
            'value' => '',
            'compare' => '!=',
        );
    }

    if ($meta_query) {
        $query_args['meta_query'] = $meta_query;
    }
}

function kingy_ali_render_directory_hero($title, $description, $context = '') {
    $is_tool_directory = $context === 'tools';
    ?>
    <div class="kingy-ali-hero">
        <p class="kingy-ali-kicker"><?php echo esc_html($is_tool_directory ? __('Kingy AI Product Intelligence', 'kingy-ai-launch-intelligence') : __('Kingy AI Launch Intelligence', 'kingy-ai-launch-intelligence')); ?></p>
        <h1><?php echo esc_html($title); ?></h1>
        <p><?php echo esc_html($description); ?></p>
        <?php if ($is_tool_directory) : ?>
            <p><?php esc_html_e('Use the filters to find a product by workflow, audience, cost, availability or evidence signal.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row">
                <a class="kingy-ali-button kingy-ali-button--primary" data-kingy-ali-track="clicked_directory_search" data-event-surface="tool_directory_hero" href="#kingy-ali-directory-q"><?php esc_html_e('Search product records', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button" data-kingy-ali-track="clicked_launch_hub" data-event-surface="tool_directory_hero" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('See the latest product changes', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button" data-kingy-ali-track="clicked_submit_launch_record" data-event-surface="tool_directory_hero" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a launch record', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function kingy_ali_render_directory_filters($filters, $context = 'tools') {
    $categories = kingy_ali_public_filter_terms('kingy_launch_category');
    $audiences = kingy_ali_public_filter_terms('kingy_audience');
    $attributes = kingy_ali_public_filter_terms('kingy_tool_attribute');
    $has_filters = kingy_ali_directory_has_filters($filters);
    $reset_url = $context === 'companies' ? home_url('/ai-companies/') : home_url('/ai-tools/');
    $reset_surface = $context === 'companies' ? 'company_directory_filters' : 'tool_directory_filters';

    ob_start();
    ?>
    <form class="kingy-ali-search" method="get" action="<?php echo esc_url($reset_url); ?>">
        <div class="kingy-ali-search__bar">
            <label class="screen-reader-text" for="kingy-ali-directory-q"><?php echo esc_html($context === 'companies' ? __('Search company directory', 'kingy-ai-launch-intelligence') : __('Search product records', 'kingy-ai-launch-intelligence')); ?></label>
            <input id="kingy-ali-directory-q" type="search" name="kali_q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="<?php echo esc_attr($context === 'companies' ? __('Search companies, founders, funding, categories, and audiences...', 'kingy-ai-launch-intelligence') : __('Search products, companies, categories, pricing, demos, and use cases...', 'kingy-ai-launch-intelligence')); ?>">
            <button type="submit"><?php esc_html_e('Search', 'kingy-ai-launch-intelligence'); ?></button>
            <?php if ($has_filters) : ?>
                <a class="kingy-ali-search__reset" data-kingy-ali-track="clicked_directory_reset" data-event-label="<?php esc_attr_e('Reset directory filters', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($reset_surface); ?>" href="<?php echo esc_url($reset_url); ?>"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
        <div class="kingy-ali-filter-grid">
            <?php kingy_ali_render_term_select('kali_category', __('Category', 'kingy-ai-launch-intelligence'), $categories, $filters['category']); ?>
            <?php kingy_ali_render_term_select('kali_audience', __('Audience', 'kingy-ai-launch-intelligence'), $audiences, $filters['audience']); ?>
            <?php kingy_ali_render_term_select('kali_attribute', __('Attribute', 'kingy-ai-launch-intelligence'), $attributes, $filters['attribute']); ?>
            <?php if ($context !== 'companies') : ?>
                <?php kingy_ali_render_yes_no_select('kali_free_plan', __('Free plan', 'kingy-ai-launch-intelligence'), $filters['free_plan']); ?>
                <?php kingy_ali_render_yes_no_select('kali_api_available', __('API', 'kingy-ai-launch-intelligence'), $filters['api_available']); ?>
                <?php kingy_ali_render_yes_no_select('kali_open_weight', __('Open source/weight', 'kingy-ai-launch-intelligence'), $filters['open_source_or_open_weight']); ?>
                <label>
                    <span><?php esc_html_e('Demo', 'kingy-ai-launch-intelligence'); ?></span>
                    <select name="kali_video_demo">
                        <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
                        <option value="yes" <?php selected($filters['video_demo'], 'yes'); ?>><?php esc_html_e('Demo available', 'kingy-ai-launch-intelligence'); ?></option>
                    </select>
                </label>
            <?php endif; ?>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_tool_directory_grid($query) {
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="kingy-ali-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo kingy_ali_render_tool_directory_card(get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo kingy_ali_render_directory_empty_state('tools');
    }

    return ob_get_clean();
}

function kingy_ali_render_company_directory_grid($query) {
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="kingy-ali-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo kingy_ali_render_company_directory_card(get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo kingy_ali_render_directory_empty_state('companies');
    }

    return ob_get_clean();
}

function kingy_ali_render_directory_empty_state($context) {
    $is_company_context = $context === 'companies';
    $surface = $is_company_context ? 'company_no_results' : 'tool_no_results';
    $browse_url = $is_company_context ? home_url('/ai-companies/') : home_url('/ai-tools/');
    $browse_label = $is_company_context ? __('Browse all companies', 'kingy-ai-launch-intelligence') : __('Browse all tools', 'kingy-ai-launch-intelligence');
    $heading = $is_company_context ? __('No matching company profiles yet.', 'kingy-ai-launch-intelligence') : __('No matching tool profiles yet.', 'kingy-ai-launch-intelligence');
    $body = $is_company_context
        ? __('That gap is useful signal for company coverage, founder research, and partner-fit planning. Submit a related launch, request a visibility review, or reset to the full company directory.', 'kingy-ai-launch-intelligence')
        : __('That gap is useful signal for the tool graph and Launch Intelligence roadmap. Submit a missing launch, request a visibility review, or reset to the full tool directory.', 'kingy-ai-launch-intelligence');
    $submit_label = $is_company_context ? __('Submit a related launch', 'kingy-ai-launch-intelligence') : __('Submit a missing launch', 'kingy-ai-launch-intelligence');
    $submit_event = $is_company_context ? __('Submit missing company launch from no results', 'kingy-ai-launch-intelligence') : __('Submit missing tool launch from no results', 'kingy-ai-launch-intelligence');
    $visibility_event = $is_company_context ? __('Get visibility score from company no results', 'kingy-ai-launch-intelligence') : __('Get visibility score from tool no results', 'kingy-ai-launch-intelligence');
    $browse_event = $is_company_context ? __('Browse all companies from no results', 'kingy-ai-launch-intelligence') : __('Browse all tools from no results', 'kingy-ai-launch-intelligence');

    ob_start();
    ?>
    <div class="kingy-ali-empty">
        <h3><?php echo esc_html($heading); ?></h3>
        <p><?php echo esc_html($body); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php echo esc_attr($submit_event); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php echo esc_html($submit_label); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php echo esc_attr($visibility_event); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_directory_reset" data-event-label="<?php echo esc_attr($browse_event); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url($browse_url); ?>"><?php echo esc_html($browse_label); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_tool_directory_card($post_id) {
    $latest_launch_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
    if (!kingy_ali_related_post_is_public_index_ready($latest_launch_id, 'kingy_ai_launch')) {
        $latest_launch_id = 0;
    }
    $summary = kingy_ali_public_profile_meta_text($post_id, 'what_it_does', get_the_excerpt($post_id));
    $company = kingy_ali_directory_public_fact_value(kingy_ali_public_profile_meta_text($post_id, 'company'));
    $pricing = kingy_ali_directory_public_fact_value(kingy_ali_public_profile_meta_text($post_id, 'pricing'));

    ob_start();
    ?>
    <article class="kingy-ali-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html(kingy_ali_directory_terms_to_string($post_id, 'kingy_launch_category', __('Uncategorized', 'kingy-ai-launch-intelligence'))); ?></span>
        </div>
        <h3><a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="tool_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words($summary, 28)); ?></p>
        <div class="kingy-ali-badges">
            <?php kingy_ali_render_fact_badge(__('Free', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'free_plan')); ?>
            <?php kingy_ali_render_fact_badge(__('API', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'api_available')); ?>
            <?php kingy_ali_render_fact_badge(__('Open', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'open_source_or_open_weight')); ?>
        </div>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Company', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($company !== '' ? $company : __('Company not confirmed', 'kingy-ai-launch-intelligence')); ?></dd></div>
            <div><dt><?php esc_html_e('Pricing', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($pricing !== '' ? $pricing : __('Not publicly confirmed', 'kingy-ai-launch-intelligence')); ?></dd></div>
        </dl>
        <div class="kingy-ali-card__actions">
            <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="tool_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php esc_html_e('Open product record', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($latest_launch_id) : ?>
                <a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($latest_launch_id); ?>" data-event-surface="tool_directory_card" href="<?php echo esc_url(get_permalink($latest_launch_id)); ?>"><?php esc_html_e('Latest recorded change', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_company_directory_card($post_id) {
    $launch_count = kingy_ali_company_public_related_count($post_id, 'kingy_ai_launch');
    $tool_count = kingy_ali_company_public_related_count($post_id, 'kingy_ai_tool');
    $summary = kingy_ali_public_profile_meta_text($post_id, 'company_summary', get_the_excerpt($post_id));
    $funding = kingy_ali_directory_public_fact_value(kingy_ali_public_profile_meta_text($post_id, 'funding'));
    $launch_count_label = sprintf(_n('%s launch', '%s launches', $launch_count, 'kingy-ai-launch-intelligence'), number_format_i18n($launch_count));
    $tool_count_label = sprintf(_n('%s tool', '%s tools', $tool_count, 'kingy-ai-launch-intelligence'), number_format_i18n($tool_count));
    $graph_status = ($launch_count > 0 || $tool_count > 0)
        ? sprintf(__('%1$s / %2$s', 'kingy-ai-launch-intelligence'), $launch_count_label, $tool_count_label)
        : __('Needs verified launch/tool links', 'kingy-ai-launch-intelligence');
    $profile_status = ($launch_count > 0 || $tool_count > 0)
        ? __('Connected profile', 'kingy-ai-launch-intelligence')
        : __('Needs verification', 'kingy-ai-launch-intelligence');

    ob_start();
    ?>
    <article class="kingy-ali-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html(kingy_ali_directory_terms_to_string($post_id, 'kingy_launch_category', __('Uncategorized', 'kingy-ai-launch-intelligence'))); ?></span>
            <span><?php echo esc_html($profile_status); ?></span>
        </div>
        <h3><a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="company_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words($summary, 28)); ?></p>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Funding', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($funding !== '' ? $funding : __('Not publicly confirmed', 'kingy-ai-launch-intelligence')); ?></dd></div>
            <div><dt><?php esc_html_e('Graph', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($graph_status); ?></dd></div>
        </dl>
        <div class="kingy-ali-card__actions">
            <a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="company_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php esc_html_e('View company', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_directory_public_fact_value($value) {
    $value = trim(wp_strip_all_tags((string) $value));
    $normalized = strtolower(preg_replace('/\s+/', ' ', $value));

    if (in_array($normalized, array('unknown', 'n/a', 'na', 'none', 'tbd', 'to be confirmed', 'not known'), true)) {
        return '';
    }

    return $value;
}

function kingy_ali_directory_terms_to_string($post_id, $taxonomy, $fallback = '') {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return $fallback;
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function kingy_ali_directory_related_count($post_type, $meta_key, $post_id) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key($meta_key),
                    'value' => absint($post_id),
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ),
            ),
        )
    );

    return (int) $query->post_count;
}
