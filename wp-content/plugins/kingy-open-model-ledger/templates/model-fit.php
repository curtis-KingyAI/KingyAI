<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="koml-wrap">
    <header class="koml-hero koml-hero--fit">
        <div class="koml-hero__copy">
            <p class="koml-kicker"><?php esc_html_e('Interactive planning tool', 'kingy-open-model-ledger'); ?></p>
            <h1><?php esc_html_e('Model Fit Calculator', 'kingy-open-model-ledger'); ?></h1>
            <p class="koml-deck"><?php esc_html_e('Estimate whether a specific release, quantization and context can fit your GPU and system memory. Results expose every assumption and link back to the canonical ledger record.', 'kingy-open-model-ledger'); ?></p>
        </div>
        <aside class="koml-principle"><strong><?php esc_html_e('Estimate, not a benchmark', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('Architecture, runtime, KV cache, batching and offload can move actual memory materially. Prefer a ledger observation made on your exact artifact and runtime when one exists.', 'kingy-open-model-ledger'); ?></p></aside>
    </header>
    <figure class="koml-record-media"><?php echo get_the_post_thumbnail(get_queried_object_id(), 'large', array('loading' => 'eager')); ?></figure>
    <?php echo do_shortcode('[kingy_model_fit]'); ?>
</main>
<?php get_footer(); ?>
