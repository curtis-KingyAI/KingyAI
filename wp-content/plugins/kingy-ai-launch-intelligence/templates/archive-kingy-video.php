<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template kingy-ali-companion-archive">
    <header class="kingy-ali-companion-single__header">
        <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI Videos', 'kingy-ai-launch-intelligence'); ?></p>
        <h1><?php esc_html_e('Living Companion Videos', 'kingy-ai-launch-intelligence'); ?></h1>
        <p class="kingy-ali-companion-archive__intro"><?php esc_html_e('Permanent companions to Kingy AI videos, with publication-moment snapshots kept separate from clearly labeled current KALI records.', 'kingy-ai-launch-intelligence'); ?></p>
    </header>

    <?php if (have_posts()) : ?>
        <div class="kingy-ali-companion-card-grid">
            <?php while (have_posts()) : the_post();
                $post_id = get_the_ID();
                $youtube_id = get_post_meta($post_id, kingy_ali_companion_meta_key('youtube_video_id'), true);
                $publish_date = get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true);
                ?>
                <article <?php post_class('kingy-ali-companion-card'); ?>>
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url(kingy_ali_companion_youtube_thumbnail($youtube_id)); ?>" data-fallback-src="<?php echo esc_url(kingy_ali_companion_youtube_thumbnail($youtube_id, 'hqdefault')); ?>" data-kingy-youtube-thumbnail alt="<?php echo esc_attr(sprintf(__('Video thumbnail for %s', 'kingy-ai-launch-intelligence'), get_the_title())); ?>" width="1280" height="720" loading="lazy" decoding="async">
                        <span><?php the_title(); ?></span>
                    </a>
                    <?php if ($publish_date) : ?><time datetime="<?php echo esc_attr($publish_date); ?>"><?php echo esc_html(kingy_ali_public_profile_date_label($publish_date)); ?></time><?php endif; ?>
                </article>
            <?php endwhile; ?>
        </div>
        <nav class="kingy-ali-companion-pagination" aria-label="<?php esc_attr_e('Companion video pages', 'kingy-ai-launch-intelligence'); ?>">
            <?php the_posts_pagination(); ?>
        </nav>
    <?php else : ?>
        <div class="kingy-ali-companion-unavailable" role="status"><p><?php esc_html_e('No companion pages have been published.', 'kingy-ai-launch-intelligence'); ?></p></div>
    <?php endif; ?>
</main>
<?php
get_footer();
