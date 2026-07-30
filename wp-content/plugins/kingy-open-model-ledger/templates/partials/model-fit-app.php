<?php

if (!defined('ABSPATH')) {
    exit;
}

$fit_models = KOML_Frontend::open_weight_posts(200);
$selected_slug = isset($_GET['model']) ? sanitize_title(wp_unslash($_GET['model'])) : '';
?>
<section class="koml-fit-app" data-koml-fit-app>
    <form class="koml-fit-form" data-koml-fit-form>
        <div class="koml-fit-form__header"><p class="koml-kicker"><?php esc_html_e('Inputs', 'kingy-open-model-ledger'); ?></p><h2><?php esc_html_e('Describe the exact load you want to run', 'kingy-open-model-ledger'); ?></h2></div>
        <label class="koml-field koml-field--wide">
            <span><?php esc_html_e('Ledger release', 'kingy-open-model-ledger'); ?></span>
            <select data-koml-model>
                <option value=""><?php esc_html_e('Manual parameters', 'kingy-open-model-ledger'); ?></option>
                <?php foreach ($fit_models as $fit_model) : ?>
                    <?php
                    $fit_id = (int) $fit_model->ID;
                    $fit_total = KOML_Frontend::meta($fit_id, 'total_parameters', '');
                    $fit_active = KOML_Frontend::meta($fit_id, 'active_parameters', $fit_total);
                    $fit_context = KOML_Frontend::meta($fit_id, 'native_context_tokens', '');
                    $fit_hardware = KOML_Frontend::meta($fit_id, 'hardware_fit', array());
                    $fit_slug = $fit_model->post_name;
                    ?>
                    <option value="<?php echo esc_attr($fit_slug); ?>" data-total="<?php echo esc_attr($fit_total); ?>" data-active="<?php echo esc_attr($fit_active); ?>" data-context="<?php echo esc_attr($fit_context); ?>" data-url="<?php echo esc_url(get_permalink($fit_id)); ?>" data-observations="<?php echo esc_attr(is_array($fit_hardware) ? count($fit_hardware) : 0); ?>" <?php selected($selected_slug, $fit_slug); ?>><?php echo esc_html(get_the_title($fit_id)); ?></option>
                <?php endforeach; ?>
            </select>
            <small><?php esc_html_e('Selecting a record fills verified values when available. Blank fields remain explicit unknowns.', 'kingy-open-model-ledger'); ?></small>
        </label>
        <label class="koml-field"><span><?php esc_html_e('Total parameters (billions)', 'kingy-open-model-ledger'); ?></span><input type="number" min="0.1" step="0.1" value="7" data-koml-total required></label>
        <label class="koml-field"><span><?php esc_html_e('Active parameters (billions)', 'kingy-open-model-ledger'); ?></span><input type="number" min="0.1" step="0.1" value="7" data-koml-active required></label>
        <label class="koml-field"><span><?php esc_html_e('Weight precision', 'kingy-open-model-ledger'); ?></span><select data-koml-bits><option value="2">2-bit</option><option value="3">3-bit</option><option value="4" selected>4-bit</option><option value="5">5-bit</option><option value="6">6-bit</option><option value="8">8-bit</option><option value="16">FP16 / BF16</option></select></label>
        <label class="koml-field"><span><?php esc_html_e('Context tokens', 'kingy-open-model-ledger'); ?></span><input type="number" min="512" step="512" value="8192" data-koml-context required></label>
        <label class="koml-field"><span><?php esc_html_e('KV-cache precision', 'kingy-open-model-ledger'); ?></span><select data-koml-kv><option value="8">8-bit</option><option value="16" selected>16-bit</option></select></label>
        <label class="koml-field"><span><?php esc_html_e('GPU / unified memory (GB)', 'kingy-open-model-ledger'); ?></span><input type="number" min="0" step="1" value="24" data-koml-vram required></label>
        <label class="koml-field"><span><?php esc_html_e('System RAM (GB)', 'kingy-open-model-ledger'); ?></span><input type="number" min="4" step="1" value="64" data-koml-ram required></label>
        <label class="koml-field"><span><?php esc_html_e('GPU offload', 'kingy-open-model-ledger'); ?></span><select data-koml-offload><option value="none">None — GPU/unified memory only</option><option value="partial">Partial CPU/RAM offload</option><option value="cpu">CPU/RAM load</option></select></label>
        <button class="koml-button koml-field--wide" type="submit"><?php esc_html_e('Calculate fit', 'kingy-open-model-ledger'); ?></button>
    </form>

    <section class="koml-fit-result" aria-live="polite" data-koml-result>
        <p class="koml-kicker"><?php esc_html_e('Planning estimate', 'kingy-open-model-ledger'); ?></p>
        <div class="koml-fit-result__status" data-koml-status><?php esc_html_e('Enter your setup to calculate', 'kingy-open-model-ledger'); ?></div>
        <p class="koml-fit-result__summary" data-koml-summary><?php esc_html_e('The estimate separates weight storage, context-sensitive KV cache, runtime overhead and headroom.', 'kingy-open-model-ledger'); ?></p>
        <dl class="koml-fit-breakdown">
            <div><dt><?php esc_html_e('Quantized weights', 'kingy-open-model-ledger'); ?></dt><dd data-koml-weight-memory>—</dd></div>
            <div><dt><?php esc_html_e('KV cache', 'kingy-open-model-ledger'); ?></dt><dd data-koml-kv-memory>—</dd></div>
            <div><dt><?php esc_html_e('Runtime overhead', 'kingy-open-model-ledger'); ?></dt><dd data-koml-overhead>—</dd></div>
            <div><dt><?php esc_html_e('15% operating headroom', 'kingy-open-model-ledger'); ?></dt><dd data-koml-headroom>—</dd></div>
            <div class="koml-fit-breakdown__total"><dt><?php esc_html_e('Estimated working set', 'kingy-open-model-ledger'); ?></dt><dd data-koml-total-memory>—</dd></div>
        </dl>
        <div class="koml-fit-assumptions"><strong><?php esc_html_e('Assumptions', 'kingy-open-model-ledger'); ?></strong><ul><li><?php esc_html_e('Weights use total parameters; MoE active parameters affect the KV/runtime heuristic, not file size.', 'kingy-open-model-ledger'); ?></li><li><?php esc_html_e('Quantization metadata, embeddings, allocator behavior and multimodal towers can add memory.', 'kingy-open-model-ledger'); ?></li><li><?php esc_html_e('“Fits” does not promise useful throughput or support in your chosen runtime.', 'kingy-open-model-ledger'); ?></li></ul></div>
        <div class="koml-fit-result__actions"><a class="koml-button koml-button--secondary" data-koml-record-link hidden><?php esc_html_e('Inspect model record', 'kingy-open-model-ledger'); ?></a><span data-koml-observations></span></div>
    </section>
</section>

<section class="koml-fit-guides">
    <div><p class="koml-kicker"><?php esc_html_e('Curated guidance', 'kingy-open-model-ledger'); ?></p><h2><?php esc_html_e('Use the estimate to choose the right hardware tier', 'kingy-open-model-ledger'); ?></h2></div>
    <div class="koml-fit-guides__links"><a href="<?php echo esc_url(home_url('/ai-hardware/local-ai-compatibility/how-much-vram-local-ai/')); ?>"><?php esc_html_e('VRAM guide →', 'kingy-open-model-ledger'); ?></a><a href="<?php echo esc_url(home_url('/ai-hardware/local-ai-compatibility/how-much-ram-local-ai/')); ?>"><?php esc_html_e('RAM guide →', 'kingy-open-model-ledger'); ?></a><a href="<?php echo esc_url(home_url('/ai-hardware/buying-guides/')); ?>"><?php esc_html_e('Hardware buying guides →', 'kingy-open-model-ledger'); ?></a></div>
</section>
