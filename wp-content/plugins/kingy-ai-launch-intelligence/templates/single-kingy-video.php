<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template kingy-ali-companion-single">
    <?php
    while (have_posts()) :
        the_post();
        $post_id = get_the_ID();
        $tool_ids = kingy_ali_companion_featured_tool_ids($post_id);
        $publish_date = get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true);
        $snapshot = kingy_ali_companion_snapshot($post_id);
        $snapshot_verified = get_post_meta($post_id, kingy_ali_companion_meta_key('snapshot_verified_date'), true);
        $sponsored = (bool) get_post_meta($post_id, kingy_ali_companion_meta_key('sponsored'), true);
        $publish_date_label = $publish_date ? kingy_ali_public_profile_date_label($publish_date) : '';
        $snapshot_verified_label = $snapshot_verified ? kingy_ali_public_profile_date_label($snapshot_verified) : '';
        ?>
        <article <?php post_class('kingy-ali-single kingy-ali-companion-article'); ?>>
            <header class="kingy-ali-companion-single__header">
                <p class="kingy-ali-kicker"><?php esc_html_e('Living Video Companion', 'kingy-ai-launch-intelligence'); ?></p>
                <h1><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
            </header>

            <?php echo kingy_ali_render_profile_featured_image($post_id); ?>

            <section class="kingy-ali-companion-video" aria-labelledby="kingy-companion-video-heading">
                <h2 class="screen-reader-text" id="kingy-companion-video-heading"><?php esc_html_e('Video', 'kingy-ai-launch-intelligence'); ?></h2>
                <?php echo kingy_ali_render_companion_youtube_facade($post_id); ?>
            </section>

            <section class="kingy-ali-companion-provenance" aria-label="<?php esc_attr_e('Data provenance', 'kingy-ai-launch-intelligence'); ?>">
                <?php if ($sponsored) : ?>
                    <p class="kingy-ali-companion-disclosure"><?php esc_html_e('Disclosure: this video was sponsored.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
                <?php if ($snapshot) : ?>
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Video published %1$s. The snapshot records below are tied to that publication date; their latest verification date is %2$s. Live data is labeled separately and updates from current KALI records.', 'kingy-ai-launch-intelligence'),
                                $publish_date_label ?: __('date unknown', 'kingy-ai-launch-intelligence'),
                                $snapshot_verified_label ?: __('unknown', 'kingy-ai-launch-intelligence')
                            )
                        );
                        ?>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e('No verified historical snapshot is available. Current values are shown only in the clearly labeled Live now section and are not presented as historical facts.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </section>

            <section class="kingy-ali-companion-section kingy-ali-companion-editorial" aria-labelledby="kingy-companion-context-heading">
                <h2 id="kingy-companion-context-heading"><?php esc_html_e('Editorial context', 'kingy-ai-launch-intelligence'); ?></h2>
                <?php echo apply_filters('the_content', get_the_content(null, false, $post_id)); ?>
            </section>

            <section class="kingy-ali-companion-section kingy-ali-companion-section--snapshot" aria-labelledby="kingy-companion-snapshot-heading">
                <h2 id="kingy-companion-snapshot-heading"><?php esc_html_e('As of publication', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-companion-section__intro"><?php esc_html_e('These values come only from the verified, write-once snapshot stored for this video. Current tool values are never substituted here.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-companion-tool-stack">
                    <?php foreach ($tool_ids as $tool_id) : ?>
                        <?php echo kingy_ali_render_companion_snapshot_tool($post_id, $tool_id); ?>
                    <?php endforeach; ?>
                    <?php if (!$tool_ids) : ?>
                        <div class="kingy-ali-companion-unavailable" role="status"><p><?php esc_html_e('No verified featured-tool relationship is available.', 'kingy-ai-launch-intelligence'); ?></p></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="kingy-ali-companion-section kingy-ali-companion-section--live" aria-labelledby="kingy-companion-live-heading">
                <h2 id="kingy-companion-live-heading"><?php esc_html_e('Live now', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-companion-section__intro"><?php esc_html_e('These modules read the current KALI tool records. Their verification dates are current-record dates, not historical evidence.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-companion-tool-stack">
                    <?php foreach ($tool_ids as $tool_id) : ?>
                        <?php echo kingy_ali_render_companion_live_tool($tool_id); ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="kingy-ali-companion-section kingy-ali-companion-section--changes" aria-labelledby="kingy-companion-changes-heading">
                <h2 id="kingy-companion-changes-heading"><?php esc_html_e("What's changed since this video", 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-companion-section__intro"><?php esc_html_e('Compare the verified publication snapshot with the current KALI record. This side-by-side view does not claim changes that the evidence cannot verify.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php foreach ($tool_ids as $tool_id) : ?>
                    <article class="kingy-ali-companion-change">
                        <h3><?php echo esc_html(get_the_title($tool_id)); ?></h3>
                        <div class="kingy-ali-companion-change-grid">
                            <div class="kingy-ali-companion-change-column kingy-ali-companion-change-column--publication">
                                <h3><?php esc_html_e('At publication', 'kingy-ai-launch-intelligence'); ?></h3>
                                <?php echo kingy_ali_render_companion_snapshot_tool($post_id, $tool_id, 4); ?>
                            </div>
                            <div class="kingy-ali-companion-change-column kingy-ali-companion-change-column--live">
                                <h3><?php esc_html_e('Current record', 'kingy-ai-launch-intelligence'); ?></h3>
                                <?php echo kingy_ali_render_kali_tool_pricing($tool_id, 4, true); ?>
                                <?php echo kingy_ali_render_kali_tool_features($tool_id, 4, true); ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="kingy-ali-companion-section kingy-ali-companion-links" aria-labelledby="kingy-companion-links-heading">
                <h2 id="kingy-companion-links-heading"><?php esc_html_e('Canonical tool guides', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-companion-section__intro"><?php esc_html_e('Use the canonical tool, pricing, and comparison pages for the latest entity-level guidance.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-companion-links-grid">
                    <?php foreach ($tool_ids as $tool_id) : ?>
                        <article class="kingy-ali-companion-link-card">
                            <h3><?php echo esc_html(get_the_title($tool_id)); ?></h3>
                            <ul>
                                <?php foreach (kingy_ali_companion_tool_canonical_links($tool_id) as $link) : ?>
                                    <li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
