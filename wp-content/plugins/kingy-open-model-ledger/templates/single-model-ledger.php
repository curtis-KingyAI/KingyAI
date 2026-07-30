<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="koml-wrap koml-single">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $model_id = get_the_ID();
        $openness = KOML_Frontend::openness($model_id);
        $scope = KOML_Frontend::scope_status($model_id);
        $announced = KOML_Frontend::announced_on($model_id);
        $weights_on = KOML_Frontend::weight_date($model_id);
        $lag = KOML_Frontend::date_lag($model_id);
        $lifecycle = KOML_Frontend::meta($model_id, 'lifecycle_status', KOML_Frontend::legacy($model_id, 'model_status_note', ''));
        $replacement_id = absint(KOML_Frontend::meta($model_id, 'replaced_by', 0));
        $commercial = KOML_Frontend::meta($model_id, 'commercial_use', 'under_review');
        $repository = KOML_Frontend::meta($model_id, 'repository_url', KOML_Frontend::legacy($model_id, 'weights_url', ''));
        $revision = KOML_Frontend::meta($model_id, 'repository_revision', '');
        $artifacts = KOML_Frontend::meta($model_id, 'artifacts', array());
        $variants = KOML_Frontend::meta($model_id, 'variants', array());
        $license_components = KOML_Frontend::meta($model_id, 'license_components', array());
        $runtime_rows = KOML_Frontend::meta($model_id, 'runtime_support', array());
        $hardware_rows = KOML_Frontend::meta($model_id, 'hardware_fit', array());
        $api_rows = KOML_Frontend::meta($model_id, 'api_offers', array());
        $evidence_rows = KOML_Frontend::meta($model_id, 'evidence', array());
        $history = KOML_Frontend::meta($model_id, 'change_log', array());
        foreach (array('artifacts', 'variants', 'license_components', 'runtime_rows', 'hardware_rows', 'api_rows', 'evidence_rows', 'history') as $array_name) {
            if (!is_array($$array_name)) {
                $$array_name = array();
            }
        }
        ?>

        <article <?php post_class('koml-record-shell'); ?>>
            <header class="koml-record-hero">
                <div>
                    <p class="koml-kicker"><?php esc_html_e('Canonical open-model release record', 'kingy-open-model-ledger'); ?></p>
                    <h1><?php the_title(); ?></h1>
                    <p class="koml-deck"><?php echo esc_html(KOML_Frontend::first_value($model_id, 'curation_note', 'model_overview', get_the_excerpt())); ?></p>
                    <div class="koml-card__badges">
                        <span class="koml-badge koml-badge--<?php echo esc_attr($openness['key']); ?>"><?php echo esc_html($openness['label']); ?></span>
                        <span class="koml-badge koml-badge--neutral"><?php echo esc_html(ucwords(str_replace('_', ' ', KOML_Frontend::display_value($lifecycle)))); ?></span>
                        <?php if ($scope === 'legacy_review' || $scope === 'under_review') : ?>
                            <span class="koml-badge koml-badge--review"><?php esc_html_e('Ledger review pending', 'kingy-open-model-ledger'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <aside class="koml-record-verification">
                    <span><?php esc_html_e('Record status', 'kingy-open-model-ledger'); ?></span>
                    <strong><?php echo esc_html($scope === 'curated' ? __('Curated', 'kingy-open-model-ledger') : __('Under review', 'kingy-open-model-ledger')); ?></strong>
                    <p><?php echo esc_html(sprintf(__('Last verified: %s', 'kingy-open-model-ledger'), KOML_Frontend::display_value(KOML_Frontend::meta($model_id, 'last_verified', KOML_Frontend::legacy($model_id, 'last_verified', ''))))); ?></p>
                    <a href="#field-sources"><?php esc_html_e('Inspect field-level evidence', 'kingy-open-model-ledger'); ?></a>
                </aside>
            </header>

            <figure class="koml-record-media"><?php the_post_thumbnail('large', array('loading' => 'eager')); ?></figure>

            <section class="koml-decision-strip" aria-label="Decision summary">
                <div><span><?php esc_html_e('Commercial local use', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $commercial))); ?></strong></div>
                <div><span><?php esc_html_e('Weight access', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(ucwords(str_replace('_', ' ', KOML_Frontend::display_value(KOML_Frontend::meta($model_id, 'weight_access', ''))))); ?></strong></div>
                <div><span><?php esc_html_e('OSAID 1.0 assessment', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(ucwords(str_replace('_', ' ', KOML_Frontend::display_value(KOML_Frontend::meta($model_id, 'osaid_outcome', 'not_assessed'))))); ?></strong></div>
                <div><span><?php esc_html_e('Lifecycle', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(ucwords(str_replace('_', ' ', KOML_Frontend::display_value($lifecycle)))); ?></strong><?php if ($replacement_id && get_post_type($replacement_id) === 'kingy_ai_model' && get_post_status($replacement_id) === 'publish') : ?><a href="<?php echo esc_url(get_permalink($replacement_id)); ?>"><?php echo esc_html(sprintf(__('Replacement: %s', 'kingy-open-model-ledger'), get_the_title($replacement_id))); ?></a><?php endif; ?></div>
            </section>

            <section class="koml-section" aria-labelledby="availability-heading">
                <div class="koml-section__head">
                    <div><p class="koml-kicker"><?php esc_html_e('Chronology', 'kingy-open-model-ledger'); ?></p><h2 id="availability-heading"><?php esc_html_e('Announcement versus actual availability', 'kingy-open-model-ledger'); ?></h2></div>
                    <?php if ($lag) : ?><span class="koml-lag"><?php echo esc_html($lag); ?></span><?php endif; ?>
                </div>
                <ol class="koml-timeline">
                    <li><span><?php esc_html_e('Announced', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(KOML_Frontend::display_value($announced)); ?></strong><?php echo wp_kses_post(KOML_Frontend::source_note($model_id, 'announced_on')); ?></li>
                    <li><span><?php esc_html_e('Weights verified downloadable', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(KOML_Frontend::display_value($weights_on)); ?></strong><?php echo wp_kses_post(KOML_Frontend::source_note($model_id, 'weights_available_on')); ?></li>
                    <li><span><?php esc_html_e('Kingy last checked', 'kingy-open-model-ledger'); ?></span><strong><?php echo esc_html(KOML_Frontend::display_value(KOML_Frontend::meta($model_id, 'last_verified', KOML_Frontend::legacy($model_id, 'last_verified', '')))); ?></strong><?php echo wp_kses_post(KOML_Frontend::source_note($model_id, 'last_verified')); ?></li>
                </ol>
            </section>

            <section class="koml-section" aria-labelledby="spec-heading">
                <div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Release facts', 'kingy-open-model-ledger'); ?></p><h2 id="spec-heading"><?php esc_html_e('Architecture, parameters, modalities and context', 'kingy-open-model-ledger'); ?></h2></div></div>
                <dl class="koml-fact-grid">
                    <?php KOML_Frontend::fact($model_id, __('Architecture', 'kingy-open-model-ledger'), KOML_Frontend::meta($model_id, 'architecture', ''), 'architecture'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Total parameters', 'kingy-open-model-ledger'), KOML_Frontend::format_parameter_count(KOML_Frontend::meta($model_id, 'total_parameters', '')), 'total_parameters'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Active parameters', 'kingy-open-model-ledger'), KOML_Frontend::format_parameter_count(KOML_Frontend::meta($model_id, 'active_parameters', '')), 'active_parameters'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Native context', 'kingy-open-model-ledger'), KOML_Frontend::first_value($model_id, 'native_context_tokens', 'context_window', ''), 'native_context_tokens'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Input modalities', 'kingy-open-model-ledger'), KOML_Frontend::meta($model_id, 'input_modalities', KOML_Frontend::legacy($model_id, 'model_modality', '')), 'input_modalities'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Output modalities', 'kingy-open-model-ledger'), KOML_Frontend::meta($model_id, 'output_modalities', ''), 'output_modalities'); ?>
                </dl>
                <?php if ($variants) : ?>
                    <div class="koml-table-wrap">
                        <table class="koml-table">
                            <caption><?php esc_html_e('Official release variants', 'kingy-open-model-ledger'); ?></caption>
                            <thead><tr><th><?php esc_html_e('Variant', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Role', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Total / active', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Context', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Modalities', 'kingy-open-model-ledger'); ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($variants as $variant) : if (!is_array($variant)) { continue; } ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html(isset($variant['name']) ? $variant['name'] : __('Unnamed variant', 'kingy-open-model-ledger')); ?></th>
                                    <td><?php echo esc_html(isset($variant['role']) ? $variant['role'] : '—'); ?></td>
                                    <td><?php echo esc_html(KOML_Frontend::format_parameter_count(isset($variant['total_parameters']) ? $variant['total_parameters'] : '') . ' / ' . KOML_Frontend::format_parameter_count(isset($variant['active_parameters']) ? $variant['active_parameters'] : '')); ?></td>
                                    <td><?php echo esc_html(isset($variant['context_tokens']) ? $variant['context_tokens'] : '—'); ?></td>
                                    <td><?php echo esc_html(trim((isset($variant['input_modalities']) ? $variant['input_modalities'] : '') . ' → ' . (isset($variant['output_modalities']) ? $variant['output_modalities'] : ''), ' →')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="koml-section" aria-labelledby="artifacts-heading">
                <div class="koml-section__head">
                    <div><p class="koml-kicker"><?php esc_html_e('Artifact identity', 'kingy-open-model-ledger'); ?></p><h2 id="artifacts-heading"><?php esc_html_e('Repositories, revisions, formats and sizes', 'kingy-open-model-ledger'); ?></h2></div>
                    <?php if ($repository) : ?><a class="koml-button koml-button--secondary" href="<?php echo esc_url($repository); ?>" rel="noopener noreferrer" target="_blank"><?php esc_html_e('Open repository', 'kingy-open-model-ledger'); ?></a><?php endif; ?>
                </div>
                <dl class="koml-fact-grid koml-fact-grid--two">
                    <?php KOML_Frontend::fact($model_id, __('Canonical repository', 'kingy-open-model-ledger'), $repository, 'repository_url'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Pinned revision', 'kingy-open-model-ledger'), $revision, 'repository_revision'); ?>
                </dl>
                <?php if ($artifacts) : ?>
                    <div class="koml-table-wrap">
                        <table class="koml-table">
                            <caption><?php esc_html_e('Known downloadable artifacts. “Official” describes provenance, not openness.', 'kingy-open-model-ledger'); ?></caption>
                            <thead><tr><th><?php esc_html_e('Artifact', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Revision', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Format / quant', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Exact download size', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Provenance', 'kingy-open-model-ledger'); ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($artifacts as $artifact) : if (!is_array($artifact)) { continue; } ?>
                                <tr>
                                    <th scope="row"><?php if (!empty($artifact['url'])) : ?><a href="<?php echo esc_url($artifact['url']); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html(isset($artifact['name']) ? $artifact['name'] : $artifact['url']); ?></a><?php else : ?><?php echo esc_html(isset($artifact['name']) ? $artifact['name'] : '—'); ?><?php endif; ?></th>
                                    <td><code><?php echo esc_html(isset($artifact['revision']) ? $artifact['revision'] : '—'); ?></code></td>
                                    <td><?php echo esc_html(trim((isset($artifact['format']) ? strtoupper($artifact['format']) : '') . ' ' . (isset($artifact['quantization']) ? $artifact['quantization'] : ''))); ?></td>
                                    <td><?php echo esc_html(KOML_Frontend::format_bytes(isset($artifact['size_bytes']) ? $artifact['size_bytes'] : 0)); ?></td>
                                    <td><?php echo esc_html(isset($artifact['provenance']) ? ucwords(str_replace('_', ' ', $artifact['provenance'])) : __('Unknown', 'kingy-open-model-ledger')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="koml-callout koml-callout--warning"><strong><?php esc_html_e('Artifact manifest pending', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('This record has not yet pinned exact artifact revisions, formats and byte sizes. Do not infer them from a repository name.', 'kingy-open-model-ledger'); ?></p></div>
                <?php endif; ?>
            </section>

            <section class="koml-section" aria-labelledby="license-heading">
                <div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Legal scope', 'kingy-open-model-ledger'); ?></p><h2 id="license-heading"><?php esc_html_e('License and openness assessment', 'kingy-open-model-ledger'); ?></h2></div><a href="https://opensource.org/ai/open-source-ai-definition" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Read OSAID 1.0 ↗', 'kingy-open-model-ledger'); ?></a></div>
                <p class="koml-section__intro"><?php esc_html_e('Kingy assesses components against the OSI Open Source AI Definition. This is not OSI certification, and public weights alone do not establish open-source status.', 'kingy-open-model-ledger'); ?></p>
                <dl class="koml-fact-grid koml-fact-grid--two">
                    <?php KOML_Frontend::fact($model_id, __('Release-level label', 'kingy-open-model-ledger'), $openness['label'], 'rights_profile'); ?>
                    <?php KOML_Frontend::fact($model_id, __('Named weight license / terms', 'kingy-open-model-ledger'), KOML_Frontend::meta($model_id, 'license_name', KOML_Frontend::legacy($model_id, 'license_notes', '')), 'license_name'); ?>
                </dl>
                <?php if ($license_components) : ?>
                    <div class="koml-table-wrap">
                        <table class="koml-table">
                            <caption><?php esc_html_e('Component-level rights assessment', 'kingy-open-model-ledger'); ?></caption>
                            <thead><tr><th><?php esc_html_e('Component', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Terms', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Commercial / modify / redistribute', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Restrictions', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('OSAID status', 'kingy-open-model-ledger'); ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($license_components as $component) : if (!is_array($component)) { continue; } ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html(isset($component['component']) ? $component['component'] : '—'); ?></th>
                                    <td><?php if (!empty($component['terms_url'])) : ?><a href="<?php echo esc_url($component['terms_url']); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html(isset($component['license']) ? $component['license'] : __('Terms', 'kingy-open-model-ledger')); ?></a><?php else : ?><?php echo esc_html(isset($component['license']) ? $component['license'] : '—'); ?><?php endif; ?></td>
                                    <td><?php echo esc_html(implode(' / ', array_map(function ($key) use ($component) { return isset($component[$key]) ? (string) $component[$key] : 'unknown'; }, array('commercial_use', 'modification', 'redistribution')))); ?></td>
                                    <td><?php echo esc_html(isset($component['restrictions']) ? $component['restrictions'] : '—'); ?></td>
                                    <td><?php echo esc_html(isset($component['osaid_status']) ? ucwords(str_replace('_', ' ', $component['osaid_status'])) : __('Not assessed', 'kingy-open-model-ledger')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="koml-callout koml-callout--warning"><strong><?php esc_html_e('Component assessment incomplete', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('Weight terms, training and inference code, data information, tokenizer and supporting tools still need separate evidence.', 'kingy-open-model-ledger'); ?></p></div>
                <?php endif; ?>
            </section>

            <section class="koml-section" aria-labelledby="runtime-heading">
                <div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Deployment', 'kingy-open-model-ledger'); ?></p><h2 id="runtime-heading"><?php esc_html_e('Runtime support and hardware fit', 'kingy-open-model-ledger'); ?></h2></div><a class="koml-button" href="<?php echo esc_url(add_query_arg('model', get_post_field('post_name', $model_id), home_url('/model-fit/'))); ?>"><?php esc_html_e('Open fit calculator', 'kingy-open-model-ledger'); ?></a></div>
                <?php if ($runtime_rows) : ?>
                    <div class="koml-table-wrap">
                        <table class="koml-table"><caption><?php esc_html_e('Artifact-specific runtime support', 'kingy-open-model-ledger'); ?></caption><thead><tr><th><?php esc_html_e('Runtime', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Version / backend', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Artifact', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Status', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Evidence', 'kingy-open-model-ledger'); ?></th></tr></thead><tbody>
                            <?php foreach ($runtime_rows as $runtime) : if (!is_array($runtime)) { continue; } ?>
                                <tr><th scope="row"><?php echo esc_html(isset($runtime['runtime']) ? $runtime['runtime'] : '—'); ?></th><td><?php echo esc_html(trim((isset($runtime['version']) ? $runtime['version'] : '') . ' ' . (isset($runtime['backend']) ? $runtime['backend'] : ''))); ?></td><td><?php echo esc_html(isset($runtime['artifact']) ? $runtime['artifact'] : '—'); ?></td><td><?php echo esc_html(isset($runtime['status']) ? ucwords(str_replace('_', ' ', $runtime['status'])) : '—'); ?></td><td><?php if (!empty($runtime['source_url'])) : ?><a href="<?php echo esc_url($runtime['source_url']); ?>" rel="noopener noreferrer" target="_blank"><?php esc_html_e('Source ↗', 'kingy-open-model-ledger'); ?></a><?php else : ?>—<?php endif; ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table>
                    </div>
                <?php endif; ?>
                <?php if ($hardware_rows) : ?>
                    <div class="koml-table-wrap">
                        <table class="koml-table"><caption><?php esc_html_e('Estimated and observed fit — never blended', 'kingy-open-model-ledger'); ?></caption><thead><tr><th><?php esc_html_e('Basis', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Hardware', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Artifact / runtime', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Context / batch', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Memory / speed', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Fit', 'kingy-open-model-ledger'); ?></th></tr></thead><tbody>
                            <?php foreach ($hardware_rows as $fit) : if (!is_array($fit)) { continue; } ?>
                                <tr><th scope="row"><?php echo esc_html(isset($fit['basis']) ? ucfirst($fit['basis']) : __('Unknown', 'kingy-open-model-ledger')); ?></th><td><?php echo esc_html(isset($fit['hardware']) ? $fit['hardware'] : '—'); ?></td><td><?php echo esc_html(trim((isset($fit['artifact']) ? $fit['artifact'] : '') . ' / ' . (isset($fit['runtime']) ? $fit['runtime'] : ''), ' /')); ?></td><td><?php echo esc_html(trim((isset($fit['context_tokens']) ? $fit['context_tokens'] : '') . ' / ' . (isset($fit['batch']) ? $fit['batch'] : ''), ' /')); ?></td><td><?php echo esc_html(trim((isset($fit['peak_memory_gb']) ? $fit['peak_memory_gb'] . ' GB' : '') . (isset($fit['tokens_per_second']) ? ' · ' . $fit['tokens_per_second'] . ' tok/s' : ''))); ?></td><td><?php echo esc_html(isset($fit['fit']) ? ucwords(str_replace('_', ' ', $fit['fit'])) : '—'); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table>
                    </div>
                <?php else : ?>
                    <div class="koml-callout"><strong><?php esc_html_e('No observed hardware result yet', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('Calculator estimates are planning aids. A confident fit claim requires the exact artifact, runtime version, hardware, context, batch, memory peak and test date.', 'kingy-open-model-ledger'); ?></p></div>
                <?php endif; ?>
            </section>

            <section class="koml-section" aria-labelledby="api-heading">
                <div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Hosted access', 'kingy-open-model-ledger'); ?></p><h2 id="api-heading"><?php esc_html_e('API providers and time-versioned pricing', 'kingy-open-model-ledger'); ?></h2></div></div>
                <?php if ($api_rows) : ?>
                    <div class="koml-table-wrap"><table class="koml-table"><caption><?php esc_html_e('Provider offers are kept separate; aliases may not identify an immutable model revision.', 'kingy-open-model-ledger'); ?></caption><thead><tr><th><?php esc_html_e('Provider / model ID', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Context / output', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Input', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Output', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Effective / status', 'kingy-open-model-ledger'); ?></th></tr></thead><tbody>
                        <?php foreach ($api_rows as $offer) : if (!is_array($offer)) { continue; } ?>
                            <tr><th scope="row"><?php echo esc_html(trim((isset($offer['provider']) ? $offer['provider'] : '') . ' / ' . (isset($offer['model_id']) ? $offer['model_id'] : ''), ' /')); ?></th><td><?php echo esc_html(trim((isset($offer['context_tokens']) ? $offer['context_tokens'] : '') . ' / ' . (isset($offer['output_tokens']) ? $offer['output_tokens'] : ''), ' /')); ?></td><td><?php echo esc_html(isset($offer['input_price']) ? $offer['input_price'] : '—'); ?></td><td><?php echo esc_html(isset($offer['output_price']) ? $offer['output_price'] : '—'); ?></td><td><?php echo esc_html(trim((isset($offer['effective_on']) ? $offer['effective_on'] : '') . ' · ' . (isset($offer['status']) ? $offer['status'] : ''), ' ·')); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                <?php else : ?>
                    <div class="koml-callout"><strong><?php esc_html_e('No provider offer verified', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('A downloadable model may still be sold by first-party and reseller APIs. Each offer needs its exact endpoint ID, limits, effective pricing date and lifecycle.', 'kingy-open-model-ledger'); ?></p></div>
                <?php endif; ?>
            </section>

            <section id="field-sources" class="koml-section" aria-labelledby="sources-heading">
                <div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Provenance', 'kingy-open-model-ledger'); ?></p><h2 id="sources-heading"><?php esc_html_e('Field-level sources, confidence and verification', 'kingy-open-model-ledger'); ?></h2></div></div>
                <?php if ($evidence_rows) : ?>
                    <div class="koml-table-wrap"><table class="koml-table"><thead><tr><th><?php esc_html_e('Field', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Value / method', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Source and locator', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Confidence', 'kingy-open-model-ledger'); ?></th><th><?php esc_html_e('Verified', 'kingy-open-model-ledger'); ?></th></tr></thead><tbody>
                        <?php foreach ($evidence_rows as $evidence) : if (!is_array($evidence)) { continue; } ?>
                            <tr><th scope="row"><code><?php echo esc_html(isset($evidence['field']) ? $evidence['field'] : '—'); ?></code></th><td><?php echo esc_html(isset($evidence['method']) ? ucwords(str_replace('_', ' ', $evidence['method'])) : '—'); ?></td><td><?php if (!empty($evidence['source_url'])) : ?><a href="<?php echo esc_url($evidence['source_url']); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html(isset($evidence['locator']) && $evidence['locator'] ? $evidence['locator'] : $evidence['source_url']); ?></a><?php else : ?>—<?php endif; ?></td><td><?php echo esc_html(isset($evidence['confidence']) ? ucfirst($evidence['confidence']) : __('Unverified', 'kingy-open-model-ledger')); ?></td><td><?php echo esc_html(isset($evidence['verified_on']) ? $evidence['verified_on'] : '—'); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                <?php else : ?>
                    <div class="koml-callout koml-callout--warning"><strong><?php esc_html_e('Field-level provenance has not been migrated', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('Legacy source links do not prove every displayed fact. This record remains visible for continuity but should not be used for a high-confidence decision until migration is complete.', 'kingy-open-model-ledger'); ?></p></div>
                <?php endif; ?>
            </section>

            <section class="koml-section" aria-labelledby="history-heading">
                <div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Append-only ledger', 'kingy-open-model-ledger'); ?></p><h2 id="history-heading"><?php esc_html_e('Complete recorded change history', 'kingy-open-model-ledger'); ?></h2></div></div>
                <?php if ($history) : ?>
                    <ol class="koml-history">
                        <?php foreach (array_reverse($history) as $event) : if (!is_array($event)) { continue; } ?>
                            <li><time><?php echo esc_html(isset($event['effective_on']) && $event['effective_on'] ? $event['effective_on'] : (isset($event['recorded_at']) ? substr($event['recorded_at'], 0, 10) : '')); ?></time><div><strong><?php echo esc_html(ucwords(str_replace('_', ' ', isset($event['event_type']) ? $event['event_type'] : 'record_updated'))); ?></strong><p><?php echo esc_html(isset($event['summary']) ? $event['summary'] : __('Ledger fields updated.', 'kingy-open-model-ledger')); ?></p><?php if (!empty($event['source_url'])) : ?><a href="<?php echo esc_url($event['source_url']); ?>" rel="noopener noreferrer" target="_blank"><?php esc_html_e('Event source ↗', 'kingy-open-model-ledger'); ?></a><?php endif; ?></div></li>
                        <?php endforeach; ?>
                    </ol>
                <?php else : ?>
                    <div class="koml-callout"><strong><?php esc_html_e('Initial import', 'kingy-open-model-ledger'); ?></strong><p><?php esc_html_e('Kingy has not recorded a ledger event for this legacy profile yet. History begins when the record is migrated; earlier changes are not implied.', 'kingy-open-model-ledger'); ?></p></div>
                <?php endif; ?>
            </section>

            <?php if (get_the_content()) : ?>
                <section class="koml-section koml-editorial-note"><div class="koml-section__head"><div><p class="koml-kicker"><?php esc_html_e('Editorial context', 'kingy-open-model-ledger'); ?></p><h2><?php esc_html_e('Kingy analysis', 'kingy-open-model-ledger'); ?></h2></div></div><?php the_content(); ?></section>
            <?php endif; ?>

            <nav class="koml-record-nav" aria-label="Model ledger navigation">
                <a class="koml-button" href="<?php echo esc_url(KOML_Frontend::ledger_url()); ?>"><?php esc_html_e('Back to Open Model Ledger', 'kingy-open-model-ledger'); ?></a>
                <a class="koml-button koml-button--secondary" href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-weight change feed', 'kingy-open-model-ledger'); ?></a>
            </nav>
        </article>
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
