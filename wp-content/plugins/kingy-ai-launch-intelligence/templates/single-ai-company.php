<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kingy_ali_company_terms_to_string')) {
    function kingy_ali_company_terms_to_string($terms) {
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        return implode(', ', wp_list_pluck($terms, 'name'));
    }
}

if (!function_exists('kingy_ali_company_fact')) {
    function kingy_ali_company_fact($label, $value) {
        $value = kingy_ali_public_profile_text($value);
        if ($value === '') {
            $value = __('Unknown', 'kingy-ai-launch-intelligence');
        }

        echo '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }
}

if (!function_exists('kingy_ali_company_link')) {
    function kingy_ali_company_link($label, $url, $surface = 'company_profile_links') {
        $url = kingy_ali_sanitize_public_profile_link_url($url);
        if (!$url) {
            return;
        }

        $rel = kingy_ali_source_link_target_attrs($url);

        echo '<a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="' . esc_attr($surface) . '" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a>';
    }
}

if (!function_exists('kingy_ali_company_query_post_ids')) {
    function kingy_ali_company_query_post_ids($query) {
        if (!is_object($query) || empty($query->posts)) {
            return array();
        }

        return array_values(
            array_filter(
                array_map(
                    function ($post) {
                        return is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
                    },
                    (array) $query->posts
                )
            )
        );
    }
}

if (!function_exists('kingy_ali_company_count_label')) {
    function kingy_ali_company_count_label($count, $singular, $plural) {
        $count = absint($count);
        return sprintf('%1$d %2$s', $count, $count === 1 ? $singular : $plural);
    }
}

if (!function_exists('kingy_ali_company_render_source_cards')) {
    function kingy_ali_company_render_source_cards($source_links, $surface = 'company_profile_sources') {
        if (!$source_links) {
            return;
        }
        ?>
        <div class="kingy-ali-source-card-grid">
            <?php foreach ($source_links as $source) : ?>
                <a class="kingy-ali-source-card" data-kingy-ali-track="clicked_source_link" data-event-label="<?php echo esc_attr($source['label']); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url($source['url']); ?>"<?php echo kingy_ali_source_link_target_attrs($source['url']); ?>>
                    <strong><?php echo esc_html($source['label']); ?></strong>
                    <span><?php echo esc_html(wp_parse_url($source['url'], PHP_URL_HOST)); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

if (!function_exists('kingy_ali_company_fallback_summary')) {
    function kingy_ali_company_fallback_summary($company_name, $category_text) {
        if ($category_text === '') {
            return sprintf(
                __('Kingy AI is tracking %s as part of the AI company graph, with connected launch records, tool profiles, source checks, and verification notes.', 'kingy-ai-launch-intelligence'),
                $company_name
            );
        }

        return sprintf(
            __('Kingy AI is tracking %1$s across %2$s, with connected launch records, tool profiles, source checks, and verification notes.', 'kingy-ai-launch-intelligence'),
            $company_name,
            $category_text
        );
    }
}

if (!function_exists('kingy_ali_company_render_tool_card')) {
    function kingy_ali_company_render_tool_card($tool_id) {
        $tool_id = absint($tool_id);
        if (!$tool_id || !kingy_ali_related_post_is_public_index_ready($tool_id, 'kingy_ai_tool')) {
            return;
        }

        $summary = kingy_ali_public_profile_meta_text($tool_id, 'what_it_does', get_the_excerpt($tool_id));
        ?>
        <article class="kingy-ali-card">
            <div class="kingy-ali-card__meta">
                <span><?php echo esc_html(kingy_ali_company_terms_to_string(get_the_terms($tool_id, 'kingy_launch_category'))); ?></span>
            </div>
            <h3><a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($tool_id); ?>" data-event-surface="company_profile_tools" href="<?php echo esc_url(get_permalink($tool_id)); ?>"><?php echo esc_html(get_the_title($tool_id)); ?></a></h3>
            <?php if ($summary) : ?>
                <p><?php echo esc_html(wp_trim_words($summary, 34)); ?></p>
            <?php endif; ?>
            <dl class="kingy-ali-score-list">
                <div><dt><?php esc_html_e('Pricing', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_public_profile_meta_text($tool_id, 'pricing', __('Unknown', 'kingy-ai-launch-intelligence'))); ?></dd></div>
                <div><dt><?php esc_html_e('API', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_public_profile_meta_text($tool_id, 'api_available', __('Unknown', 'kingy-ai-launch-intelligence'))); ?></dd></div>
            </dl>
            <div class="kingy-ali-card__actions">
                <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($tool_id); ?>" data-event-surface="company_profile_tools" href="<?php echo esc_url(get_permalink($tool_id)); ?>"><?php esc_html_e('View tool', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </article>
        <?php
    }
}

if (!function_exists('kingy_ali_company_render_launch_timeline')) {
    function kingy_ali_company_render_launch_timeline($launch_ids) {
        if (!$launch_ids) {
            return;
        }
        ?>
        <ol class="kingy-ali-company-timeline">
            <?php foreach ($launch_ids as $launch_id) : ?>
                <?php
                $launch_id = absint($launch_id);
                if (!$launch_id || !kingy_ali_related_post_is_public_index_ready($launch_id, 'kingy_ai_launch')) {
                    continue;
                }
                $launch_date = kingy_ali_public_profile_meta_text($launch_id, 'launch_date');
                $launch_date_label = kingy_ali_public_profile_date_label($launch_date);
                $summary = kingy_ali_public_profile_meta_text($launch_id, 'what_launched', get_the_excerpt($launch_id));
                ?>
                <li>
                    <div>
                        <?php if ($launch_date_label) : ?>
                            <time datetime="<?php echo esc_attr($launch_date); ?>"><?php echo esc_html($launch_date_label); ?></time>
                        <?php endif; ?>
                        <h3><a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($launch_id); ?>" data-event-surface="company_profile_timeline" href="<?php echo esc_url(get_permalink($launch_id)); ?>"><?php echo esc_html(get_the_title($launch_id)); ?></a></h3>
                    </div>
                    <?php if ($summary) : ?>
                        <p><?php echo esc_html(wp_trim_words($summary, 36)); ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php
    }
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template">
    <?php
    while (have_posts()) :
        the_post();
        $post_id = get_the_ID();
        kingy_ali_enqueue_assets();
        $official_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'official_url'));
        $contact_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'contact_url'));
        $launches = kingy_ali_query_company_launches($post_id, 18);
        $tools = kingy_ali_query_company_tools($post_id, 18);
        $launch_ids = kingy_ali_company_query_post_ids($launches);
        $tool_ids = kingy_ali_company_query_post_ids($tools);
        $launch_count = kingy_ali_company_public_related_count($post_id, 'kingy_ai_launch');
        $tool_count = kingy_ali_company_public_related_count($post_id, 'kingy_ai_tool');
        $company_summary = kingy_ali_public_profile_meta_text($post_id, 'company_summary');
        $company_name = get_the_title($post_id);
        $category_text = kingy_ali_company_terms_to_string(get_the_terms($post_id, 'kingy_launch_category'));
        $audience_text = kingy_ali_company_terms_to_string(get_the_terms($post_id, 'kingy_audience'));
        $display_summary = $company_summary;
        $ai_evidence = kingy_ali_public_profile_meta_text($post_id, 'ai_evidence');
        // Internal editorial notes never belong on the public company profile.
        $buyer_notes = '';
        $source_notes = '';
        $verification_status = function_exists('kingy_ali_profile_verification_label') ? kingy_ali_profile_verification_label($post_id, 'company') : '';
        $source_links = function_exists('kingy_ali_public_source_links') ? kingy_ali_public_source_links($post_id) : array();
        $source_count = count($source_links);
        if (!$display_summary && has_excerpt()) {
            $display_summary = get_the_excerpt();
        }
        if (!$display_summary) {
            $display_summary = kingy_ali_company_fallback_summary($company_name, $category_text);
        }
        $latest_launch_id = $launch_ids ? absint($launch_ids[0]) : 0;
        $latest_launch_title = $latest_launch_id ? get_the_title($latest_launch_id) : '';
        $latest_launch_summary = $latest_launch_id ? kingy_ali_public_profile_meta_text($latest_launch_id, 'what_launched', get_the_excerpt($latest_launch_id)) : '';
        $latest_launch_date = $latest_launch_id ? kingy_ali_public_profile_date_label(kingy_ali_public_profile_meta_text($latest_launch_id, 'launch_date')) : '';
        $primary_tool_id = $tool_ids ? absint($tool_ids[0]) : 0;
        $primary_tool_title = $primary_tool_id ? get_the_title($primary_tool_id) : '';
        $primary_tool_summary = $primary_tool_id ? kingy_ali_public_profile_meta_text($primary_tool_id, 'what_it_does', get_the_excerpt($primary_tool_id)) : '';
        $launch_label = kingy_ali_company_count_label($launch_count, __('launch', 'kingy-ai-launch-intelligence'), __('launches', 'kingy-ai-launch-intelligence'));
        $tool_label = kingy_ali_company_count_label($tool_count, __('tool', 'kingy-ai-launch-intelligence'), __('tools', 'kingy-ai-launch-intelligence'));
        ?>
        <article <?php post_class('kingy-ali-single kingy-ali-company-single'); ?>>
            <header class="kingy-ali-single__header">
                <p class="kingy-ali-kicker"><?php esc_html_e('AI Company Profile', 'kingy-ai-launch-intelligence'); ?></p>
                <h1><?php the_title(); ?></h1>
                <p><?php echo esc_html($display_summary); ?></p>
            </header>

            <?php echo kingy_ali_render_profile_featured_image($post_id); ?>

            <section class="kingy-ali-facts">
                <?php kingy_ali_company_fact(__('Primary category', 'kingy-ai-launch-intelligence'), $category_text); ?>
                <?php kingy_ali_company_fact(__('Audience', 'kingy-ai-launch-intelligence'), $audience_text); ?>
                <?php kingy_ali_company_fact(__('Founder/team', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'founder_team')); ?>
                <?php kingy_ali_company_fact(__('Funding', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'funding')); ?>
                <?php kingy_ali_company_fact(__('Linked launches', 'kingy-ai-launch-intelligence'), $launch_label); ?>
                <?php kingy_ali_company_fact(__('Linked tools', 'kingy-ai-launch-intelligence'), $tool_label); ?>
                <?php kingy_ali_company_fact(__('Verification', 'kingy-ai-launch-intelligence'), $verification_status); ?>
                <?php kingy_ali_company_fact(__('Source links', 'kingy-ai-launch-intelligence'), (string) $source_count); ?>
                <?php kingy_ali_company_fact(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'last_verified')); ?>
            </section>

            <section class="kingy-ali-content-band kingy-ali-company-overview">
                <h2><?php esc_html_e('Company Overview', 'kingy-ai-launch-intelligence'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('%1$s is tracked in the Kingy AI company directory because it is connected to public AI launch and tool records. %2$s', 'kingy-ai-launch-intelligence'),
                            $company_name,
                            $display_summary
                        )
                    );
                    ?>
                </p>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('The current public graph connects %1$s and %2$s to %3$s. That turns this profile into a working research hub: use it to move from the company to its product surface, then into dated launch records with source checks, verification status, and related Kingy AI context.', 'kingy-ai-launch-intelligence'),
                            $launch_label,
                            $tool_label,
                            $company_name
                        )
                    );
                    ?>
                </p>
                <p>
                    <?php
                    $overview_audience = $audience_text ? $audience_text : __('AI builders, buyers, creators, founders, researchers, and operators', 'kingy-ai-launch-intelligence');
                    echo esc_html(
                        sprintf(
                            __('For %1$s, the useful question is not only what %2$s says about itself. The useful question is what the launch pattern shows: which categories the company is active in, which tools have durable profiles, whether pricing and demos are clear, and whether the source trail is strong enough for deeper editorial or creator coverage.', 'kingy-ai-launch-intelligence'),
                            $overview_audience,
                            $company_name
                        )
                    );
                    ?>
                </p>
            </section>

            <section class="kingy-ali-content-grid kingy-ali-company-analysis-grid kingy-ali-company-source-grid">
                <div class="kingy-ali-text-panel">
                    <h2><?php esc_html_e('AI Product Evidence', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p>
                        <?php
                        echo esc_html(
                            $ai_evidence
                                ? $ai_evidence
                                : sprintf(
                                    __('%1$s is included because the current Kingy AI record connects it to AI-specific categories, public product positioning, or linked AI launch/tool history. This page should stay focused on AI products and workflows rather than generic company background.', 'kingy-ai-launch-intelligence'),
                                    $company_name
                                )
                        );
                        ?>
                    </p>
                </div>
                <div class="kingy-ali-text-panel">
                    <h2><?php esc_html_e('Research Notes', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p>
                        <?php
                        echo esc_html(
                            $buyer_notes
                                ? $buyer_notes
                                : sprintf(
                                    __('When reviewing %1$s, check the official product surface, docs, demos, pricing, model or API pages, and linked Kingy AI launch records before relying on claims for buying, writing, comparison, or creator-coverage decisions.', 'kingy-ai-launch-intelligence'),
                                    $company_name
                                )
                        );
                        ?>
                    </p>
                </div>
            </section>

            <section class="kingy-ali-content-band kingy-ali-company-source-review">
                <h2><?php esc_html_e('Source-Backed Profile Notes', 'kingy-ai-launch-intelligence'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        $source_notes
                            ? $source_notes
                            : __('This profile is checked against public source links where available. Official product pages, documentation, model pages, pricing pages, launch announcements, and verified company pages should carry more weight than social posts or unsourced summaries.', 'kingy-ai-launch-intelligence')
                    );
                    ?>
                </p>
                <?php if ($source_links) : ?>
                    <?php kingy_ali_company_render_source_cards($source_links); ?>
                <?php else : ?>
                    <p><?php esc_html_e('No public source links are attached yet. Treat this profile as a research queue item until official sources are added.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </section>

            <section class="kingy-ali-content-grid kingy-ali-company-analysis-grid">
                <div class="kingy-ali-text-panel">
                    <h2><?php esc_html_e('Market Position', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Kingy AI currently classifies %1$s around %2$s. Those categories are not meant to be a marketing slogan; they are a practical way to understand where the company shows up in the launch database and how it may intersect with product strategy, adoption, search demand, and creator education.', 'kingy-ai-launch-intelligence'),
                                $company_name,
                                $category_text ? $category_text : __('AI product and launch activity', 'kingy-ai-launch-intelligence')
                            )
                        );
                        ?>
                    </p>
                </div>
                <div class="kingy-ali-text-panel">
                    <h2><?php esc_html_e('Audience Fit', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                __('This profile is especially useful for %1$s. A complete read should start with the company snapshot, continue through the linked tools and launch timeline, and end with the source panel so claims can be checked before a buying decision, article, comparison, or video brief.', 'kingy-ai-launch-intelligence'),
                                $audience_text ? $audience_text : __('teams comparing AI products and launch signals', 'kingy-ai-launch-intelligence')
                            )
                        );
                        ?>
                    </p>
                </div>
            </section>

            <?php if ($latest_launch_id || $primary_tool_id) : ?>
                <section class="kingy-ali-content-grid kingy-ali-company-analysis-grid">
                    <?php if ($latest_launch_id) : ?>
                        <div class="kingy-ali-text-panel">
                            <h2><?php esc_html_e('Latest Tracked Move', 'kingy-ai-launch-intelligence'); ?></h2>
                            <p>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        __('The most recent linked launch for %1$s is %2$s%3$s. %4$s', 'kingy-ai-launch-intelligence'),
                                        $company_name,
                                        $latest_launch_title,
                                        $latest_launch_date ? sprintf(__(' from %s', 'kingy-ai-launch-intelligence'), $latest_launch_date) : '',
                                        $latest_launch_summary ? wp_trim_words($latest_launch_summary, 46) : __('Open the launch record for the event-level source trail, scoring context, and related product links.', 'kingy-ai-launch-intelligence')
                                    )
                                );
                                ?>
                            </p>
                            <p><a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($latest_launch_id); ?>" data-event-surface="company_profile_analysis" href="<?php echo esc_url(get_permalink($latest_launch_id)); ?>"><?php esc_html_e('Read the latest launch record', 'kingy-ai-launch-intelligence'); ?></a></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($primary_tool_id) : ?>
                        <div class="kingy-ali-text-panel">
                            <h2><?php esc_html_e('Product Surface', 'kingy-ai-launch-intelligence'); ?></h2>
                            <p>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        __('One linked tool profile is %1$s. %2$s The tool profile is where Kingy AI keeps product-level details such as what it does, pricing clarity, API availability, alternatives, related launches, and source-backed evaluation notes.', 'kingy-ai-launch-intelligence'),
                                        $primary_tool_title,
                                        $primary_tool_summary ? wp_trim_words($primary_tool_summary, 38) : ''
                                    )
                                );
                                ?>
                            </p>
                            <p><a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($primary_tool_id); ?>" data-event-surface="company_profile_analysis" href="<?php echo esc_url(get_permalink($primary_tool_id)); ?>"><?php esc_html_e('Open linked tool profile', 'kingy-ai-launch-intelligence'); ?></a></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="kingy-ali-content-band">
                <h2><?php esc_html_e('Launch Graph And Timeline', 'kingy-ai-launch-intelligence'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('The launch graph is the most important part of this page. It shows how %1$s appears through dated AI product events instead of a generic company description. Each launch record can include category, audience, source links, pricing notes, demo signals, scoring, and a best-next-link path for deeper research.', 'kingy-ai-launch-intelligence'),
                            $company_name
                        )
                    );
                    ?>
                </p>
                <?php if ($launch_ids) : ?>
                    <?php kingy_ali_company_render_launch_timeline($launch_ids); ?>
                <?php else : ?>
                    <p><?php esc_html_e('No published launch records are linked to this company yet. That usually means the company page exists before the public launch graph has been fully backfilled.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </section>

            <?php if ($official_url || $contact_url) : ?>
                <section class="kingy-ali-link-panel">
                    <h2><?php esc_html_e('Company Links', 'kingy-ai-launch-intelligence'); ?></h2>
                    <div class="kingy-ali-link-list">
                        <?php kingy_ali_company_link(__('Official site', 'kingy-ai-launch-intelligence'), $official_url); ?>
                        <?php kingy_ali_company_link(__('Contact', 'kingy-ai-launch-intelligence'), $contact_url); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (function_exists('kingy_ali_render_related_model_profile_panel')) : ?>
                <?php kingy_ali_render_related_model_profile_panel($post_id, 'related_company_id', 'company_profile_related_models'); ?>
            <?php endif; ?>

            <section class="kingy-ali-content-band">
                <h2><?php esc_html_e('Tool Portfolio', 'kingy-ai-launch-intelligence'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('The tool portfolio section turns the company profile into a navigable product map. For %1$s, Kingy AI currently links %2$s that can be reviewed separately for pricing, demos, use cases, alternatives, related launches, and source-backed notes.', 'kingy-ai-launch-intelligence'),
                            $company_name,
                            $tool_label
                        )
                    );
                    ?>
                </p>
                <?php if ($tool_ids) : ?>
                    <div class="kingy-ali-grid">
                        <?php
                        foreach ($tool_ids as $tool_id) {
                            kingy_ali_company_render_tool_card($tool_id);
                        }
                        ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('No published tool profiles are linked to this company yet.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </section>

            <section class="kingy-ali-content-band">
                <h2><?php esc_html_e('Launch Record Cards', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use these cards when you want the shorter scan view: category, launch date, scores, open/API/free-plan signals, and the path into the full launch profile.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php if ($launch_ids) : ?>
                    <div class="kingy-ali-grid">
                        <?php
                        foreach ($launch_ids as $launch_id) {
                            echo kingy_ali_render_launch_card($launch_id);
                        }
                        ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('No published launch records are linked to this company yet.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </section>

            <section class="kingy-ali-content-band kingy-ali-company-evaluation">
                <h2><?php esc_html_e('How To Evaluate This Company', 'kingy-ai-launch-intelligence'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('A useful %s review should combine company-level context with product-level evidence. Start with the official site, then inspect the linked tools and launch records for source quality, pricing clarity, demo availability, audience fit, and how recently the profile was verified.', 'kingy-ai-launch-intelligence'),
                            $company_name
                        )
                    );
                    ?>
                </p>
                <ul>
                    <li><?php esc_html_e('Check whether the company has a clear official product path, documentation, demo, pricing page, or public launch announcement.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Compare the linked tool profiles against the launch timeline to see whether the product story is current or stale.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Use the category and audience tags as discovery aids, then verify claims through the source links before making a buying, writing, or creator-coverage decision.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Treat unknown funding, founder, or contact fields as research gaps, not negative signals. They identify where the profile needs more public evidence.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('When a launch or tool looks important but under-documented, submit a correction or related launch so the graph can be improved.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </section>

            <section class="kingy-ali-content-band kingy-ali-company-coverage-notes">
                <h2><?php esc_html_e('Editorial And Creator Coverage Notes', 'kingy-ai-launch-intelligence'); ?></h2>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('For Kingy AI, %1$s is most interesting when the company has a clear product change, a useful demo surface, a founder or team story, a strong comparison angle, or a launch that helps buyers understand where the AI market is moving. The current graph gives editors and creators a starting point without pretending that every profile is already complete.', 'kingy-ai-launch-intelligence'),
                            $company_name
                        )
                    );
                    ?>
                </p>
                <p><?php esc_html_e('Good coverage candidates usually have a specific workflow, visible product proof, a concrete audience, and enough official or high-quality public sources to avoid thin summaries. If those pieces are missing, this page should be read as a research queue as much as a company profile.', 'kingy-ai-launch-intelligence'); ?></p>
            </section>

            <section class="kingy-ali-content-band kingy-ali-company-verification-notes">
                <h2><?php esc_html_e('Verification And Source Notes', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The verification panel below summarizes profile freshness and key source checks. It is intentionally visible because AI company pages become low-trust quickly when dates, product claims, funding notes, or source links are not kept current.', 'kingy-ai-launch-intelligence'); ?></p>
            </section>

            <?php kingy_ali_render_trust_panel($post_id, 'company'); ?>

            <?php if (kingy_ali_company_has_creator_coverage_signal($post_id)) : ?>
                <section class="kingy-ali-link-panel kingy-ali-coverage-next">
                    <h2><?php esc_html_e('Creator Coverage Next Steps', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p><?php esc_html_e('This company has launch, tool, or audience signals that may support demos, reviews, creator education, founder storytelling, or practical product explainers.', 'kingy-ai-launch-intelligence'); ?></p>
                    <div class="kingy-ali-link-list">
                        <a data-kingy-ali-track="clicked_category_path" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('View creator coverage company list', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_coverage" href="<?php echo esc_url(home_url('/ai-launches/creator-coverage-ai-launches/')); ?>"><?php esc_html_e('View creator coverage list', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_sponsorship_cta" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_coverage" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_coverage')); ?>"><?php esc_html_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_roi_calculator" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_coverage" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
                    </div>
                </section>
            <?php endif; ?>

            <div class="kingy-ali-cta-row">
                <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit a related launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a related launch', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI from company profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_cta" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator ROI', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from company profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="company_profile_cta" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
