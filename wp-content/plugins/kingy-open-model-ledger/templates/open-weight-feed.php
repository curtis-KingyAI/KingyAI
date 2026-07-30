<?php

if (!defined('ABSPATH')) {
    exit;
}

$events = KOML_Frontend::model_events(50);
get_header();
?>

<main id="primary" class="koml-wrap">
    <header class="koml-hero koml-hero--feed">
        <div class="koml-hero__copy">
            <p class="koml-kicker"><?php esc_html_e('Chronological ledger events', 'kingy-open-model-ledger'); ?></p>
            <h1><?php esc_html_e('Open-Weight Model Release & Change Feed', 'kingy-open-model-ledger'); ?></h1>
            <p class="koml-deck"><?php esc_html_e('A dated feed of weights becoming available, artifact revisions, license changes, runtime support, provider pricing, deprecations and replacements. Each event resolves to one canonical model record.', 'kingy-open-model-ledger'); ?></p>
            <div class="koml-hero__actions"><a class="koml-button" href="<?php echo esc_url(KOML_Frontend::ledger_url()); ?>"><?php esc_html_e('Open the Model Ledger', 'kingy-open-model-ledger'); ?></a></div>
        </div>
        <aside class="koml-principle"><strong><?php esc_html_e('Important', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('“Open weight” means model parameters are downloadable under stated terms. It does not by itself mean open source, unrestricted commercial use, or OSAID 1.0 conformance.', 'kingy-open-model-ledger'); ?></p></aside>
    </header>

    <figure class="koml-record-media"><?php echo get_the_post_thumbnail(get_queried_object_id(), 'large', array('loading' => 'eager')); ?></figure>

    <section class="koml-feed-intro">
        <div><strong><?php echo esc_html(number_format_i18n(count($events))); ?></strong><span><?php esc_html_e('latest verified events', 'kingy-open-model-ledger'); ?></span></div>
        <p><?php esc_html_e('Effective date says when the change happened. Recorded date says when Kingy added it. Corrections remain in the history rather than silently replacing prior claims.', 'kingy-open-model-ledger'); ?></p>
    </section>

    <?php if ($events) : ?>
        <ol class="koml-event-feed">
            <?php foreach ($events as $event) : ?>
                <li>
                    <time datetime="<?php echo esc_attr($event['effective_on']); ?>"><?php echo esc_html($event['effective_on']); ?></time>
                    <article>
                        <div class="koml-event-feed__meta">
                            <span class="koml-badge koml-badge--neutral"><?php echo esc_html(ucwords(str_replace('_', ' ', $event['event_type']))); ?></span>
                            <?php if (!empty($event['recorded_at'])) : ?><span><?php echo esc_html(sprintf(__('Recorded %s', 'kingy-open-model-ledger'), substr($event['recorded_at'], 0, 10))); ?></span><?php endif; ?>
                        </div>
                        <h2><a href="<?php echo esc_url($event['url']); ?>"><?php echo esc_html($event['title']); ?></a></h2>
                        <p><?php echo esc_html($event['summary']); ?></p>
                        <div class="koml-event-feed__actions">
                            <a href="<?php echo esc_url($event['url']); ?>"><?php esc_html_e('Canonical model record →', 'kingy-open-model-ledger'); ?></a>
                            <?php if (!empty($event['source_url'])) : ?><a href="<?php echo esc_url($event['source_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Event source ↗', 'kingy-open-model-ledger'); ?></a><?php endif; ?>
                        </div>
                    </article>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else : ?>
        <section class="koml-empty"><h2><?php esc_html_e('No ledger events have passed review yet', 'kingy-open-model-ledger'); ?></h2><p><?php esc_html_e('The legacy launch feed remains available until model events are migrated with canonical IDs, effective dates and sources.', 'kingy-open-model-ledger'); ?></p></section>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
