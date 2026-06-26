<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_handle_launch_submission', 30);

function kingy_ali_submission_fields() {
    return array(
        'product_name' => array('label' => 'Product name', 'type' => 'text', 'required' => true),
        'company' => array('label' => 'Company name', 'type' => 'text', 'required' => false),
        'founder_contact_name' => array('label' => 'Founder/contact name', 'type' => 'text', 'required' => true),
        'founder_contact_email' => array('label' => 'Email', 'type' => 'email', 'required' => true),
        'official_url' => array('label' => 'Official website', 'type' => 'url', 'required' => true),
        'launch_date' => array('label' => 'Launch date', 'type' => 'date', 'required' => false),
        'launch_type' => array('label' => 'Launch type', 'type' => 'taxonomy', 'taxonomy' => 'kingy_launch_type', 'required' => true, 'exclude_slugs' => array('founder-submitted')),
        'what_launched' => array('label' => 'What launched?', 'type' => 'textarea', 'required' => true),
        'who_it_is_for' => array('label' => 'Who is it for?', 'type' => 'textarea', 'required' => false),
        'category' => array('label' => 'Category', 'type' => 'taxonomy', 'taxonomy' => 'kingy_launch_category', 'required' => true),
        'audience' => array('label' => 'Primary audience', 'type' => 'taxonomy', 'taxonomy' => 'kingy_audience', 'required' => false),
        'pricing' => array('label' => 'Pricing', 'type' => 'text', 'required' => false),
        'pricing_url' => array('label' => 'Pricing page', 'type' => 'url', 'required' => false),
        'free_plan' => array('label' => 'Free plan?', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'required' => false),
        'api_available' => array('label' => 'API available?', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'required' => false),
        'open_source_or_open_weight' => array('label' => 'Open source/open weight?', 'type' => 'select', 'options' => kingy_ali_yes_no_unknown_options(), 'required' => false),
        'demo_url' => array('label' => 'Demo video link', 'type' => 'url', 'required' => false),
        'product_hunt_url' => array('label' => 'Product Hunt link', 'type' => 'url', 'required' => false),
        'github_url' => array('label' => 'GitHub link', 'type' => 'url', 'required' => false),
        'huggingface_url' => array('label' => 'Hugging Face link', 'type' => 'url', 'required' => false),
        'x_url' => array('label' => 'X/social link', 'type' => 'url', 'required' => false),
        'funding' => array('label' => 'Funding announcement link', 'type' => 'url', 'required' => false),
        'press_kit_url' => array('label' => 'Press kit link', 'type' => 'url', 'required' => false),
        'media_urls' => array('label' => 'Screenshots/media links', 'type' => 'textarea', 'required' => false),
        'sources' => array('label' => 'Additional source links', 'type' => 'textarea', 'required' => false),
        'kingy_verdict' => array('label' => 'What makes this different?', 'type' => 'textarea', 'required' => false),
        'youtube_interest' => array('label' => 'Would you like to be considered for Kingy AI YouTube coverage?', 'type' => 'select', 'options' => array('' => 'Not sure', 'yes' => 'Yes', 'no' => 'No'), 'required' => false),
        'visibility_score_interest' => array('label' => 'Would you like a Launch Visibility Score?', 'type' => 'select', 'options' => array('' => 'Not sure', 'yes' => 'Yes', 'no' => 'No'), 'required' => false),
        'creator_coverage_interest' => array('label' => 'Would you like a creator coverage fit review?', 'type' => 'select', 'options' => array('' => 'Not sure', 'yes' => 'Yes', 'no' => 'No'), 'required' => false),
        'sponsorship_interest' => array('label' => 'Open to discussing creator education or campaign options?', 'type' => 'select', 'options' => array('' => 'Not sure', 'yes' => 'Yes', 'no' => 'No'), 'required' => false),
        'founder_notes' => array('label' => 'Anything Kingy AI should know?', 'type' => 'textarea', 'required' => false),
    );
}

function kingy_ali_shortcode_submit_form() {
    kingy_ali_enqueue_assets();

    if (kingy_ali_submission_get_value('kingy_launch_submitted') === '1') {
        return kingy_ali_render_submission_success();
    }

    ob_start();
    ?>
    <form class="kingy-ali-submit-form" method="post">
        <h2><?php esc_html_e('Launching an AI tool?', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('Submit it to Kingy AI Launch Intelligence for editorial review and potential coverage.', 'kingy-ai-launch-intelligence'); ?></p>
        <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_score_methodology_note()); ?></p>
        <?php wp_nonce_field('kingy_ali_submit_launch', 'kingy_ali_submit_launch_nonce'); ?>
        <input type="hidden" name="kingy_ali_action" value="submit_launch">
        <label class="kingy-ali-hp" aria-hidden="true">
            <span><?php esc_html_e('Leave this field empty', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="text" name="kingy_ali_website">
        </label>
        <div class="kingy-ali-form-grid">
            <?php foreach (kingy_ali_submission_fields() as $key => $field) : ?>
                <?php kingy_ali_render_submission_field($key, $field); ?>
            <?php endforeach; ?>
        </div>
        <button type="submit"><?php esc_html_e('Submit launch', 'kingy-ai-launch-intelligence'); ?></button>
        <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
        <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_submission_field($key, $field) {
    $required = !empty($field['required']);
    $required_attr = $required ? ' required' : '';
    $name = 'kingy_ali_submission[' . esc_attr($key) . ']';
    $id = 'kingy_ali_submission_' . esc_attr($key);
    ?>
    <label class="kingy-ali-field kingy-ali-field--<?php echo esc_attr($field['type']); ?>">
        <span><?php echo esc_html($field['label']); ?><?php echo $required ? ' *' : ''; ?></span>
        <?php if ($field['type'] === 'textarea') : ?>
            <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" rows="4"<?php echo esc_attr($required_attr); ?>></textarea>
        <?php elseif ($field['type'] === 'select') : ?>
            <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"<?php echo esc_attr($required_attr); ?>>
                <?php foreach ($field['options'] as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($field['type'] === 'taxonomy') : ?>
            <?php $terms = get_terms(array('taxonomy' => $field['taxonomy'], 'hide_empty' => false)); ?>
            <?php $excluded_slugs = isset($field['exclude_slugs']) ? array_map('sanitize_title', (array) $field['exclude_slugs']) : array(); ?>
            <?php $term_options = function_exists('kingy_ali_public_filter_term_options') ? kingy_ali_public_filter_term_options($terms) : array(); ?>
            <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"<?php echo esc_attr($required_attr); ?>>
                <option value=""><?php esc_html_e('Select one', 'kingy-ai-launch-intelligence'); ?></option>
                <?php if (!empty($term_options)) : ?>
                    <?php foreach ($term_options as $term_option) : ?>
                        <?php if (in_array($term_option['slug'], $excluded_slugs, true)) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <option value="<?php echo esc_attr($term_option['slug']); ?>"><?php echo esc_html($term_option['label']); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        <?php else : ?>
            <input id="<?php echo esc_attr($id); ?>" type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr($name); ?>"<?php echo esc_attr($required_attr); ?>>
        <?php endif; ?>
    </label>
    <?php
}

function kingy_ali_submission_get_value($key) {
    $values = kingy_ali_submission_get_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return kingy_ali_submission_scalar_value($value);
}

function kingy_ali_submission_post_value($key) {
    $values = kingy_ali_submission_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return kingy_ali_submission_scalar_value($value);
}

function kingy_ali_submission_post_array($key) {
    $values = kingy_ali_submission_post_values();
    if (!isset($values[$key])) {
        return array();
    }

    if (!is_array($values[$key])) {
        return array();
    }

    $value = wp_unslash($values[$key]);
    return is_array($value) ? $value : array();
}

function kingy_ali_submission_get_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_submission_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_submission_scalar_value($value) {
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_sanitize_submission_text($value, $max_length = 191) {
    $value = sanitize_text_field(kingy_ali_submission_scalar_value($value));
    $value = trim((string) $value);
    if ($max_length > 0 && strlen($value) > $max_length) {
        $value = wp_html_excerpt($value, $max_length, '');
    }

    return $value;
}

function kingy_ali_sanitize_submission_textarea($value, $max_length = 2000) {
    $value = sanitize_textarea_field(kingy_ali_submission_scalar_value($value));
    $value = trim((string) $value);
    if ($max_length > 0 && strlen($value) > $max_length) {
        $value = wp_html_excerpt($value, $max_length, '');
    }

    return $value;
}

function kingy_ali_sanitize_submission_select($value, $field) {
    $value = sanitize_key(kingy_ali_submission_scalar_value($value));
    $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : array();
    return isset($options[$value]) ? $value : '';
}

function kingy_ali_sanitize_submission_taxonomy($value, $field) {
    $slug = sanitize_title(kingy_ali_submission_scalar_value($value));
    if ($slug === '') {
        return '';
    }

    $excluded_slugs = isset($field['exclude_slugs']) ? array_map('sanitize_title', (array) $field['exclude_slugs']) : array();
    if (in_array($slug, $excluded_slugs, true)) {
        return '';
    }

    $taxonomy = isset($field['taxonomy']) ? sanitize_key($field['taxonomy']) : '';
    if (!$taxonomy || !taxonomy_exists($taxonomy)) {
        return '';
    }

    $term = get_term_by('slug', $slug, $taxonomy);
    return $term && !is_wp_error($term) ? $slug : '';
}

function kingy_ali_handle_launch_submission() {
    if (kingy_ali_submission_post_value('kingy_ali_action') !== 'submit_launch') {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(kingy_ali_submission_post_value('kingy_ali_submit_launch_nonce')), 'kingy_ali_submit_launch')) {
        return;
    }

    if (kingy_ali_submission_post_value('kingy_ali_website') !== '') {
        wp_safe_redirect(add_query_arg('kingy_launch_submitted', '1', kingy_ali_public_form_redirect_base(home_url('/ai-launches/submit/'))));
        exit;
    }

    $raw = kingy_ali_submission_post_array('kingy_ali_submission');
    $fields = kingy_ali_submission_fields();
    $data = array();

    foreach ($fields as $key => $field) {
        $value = isset($raw[$key]) ? kingy_ali_submission_scalar_value($raw[$key]) : '';
        if ($field['type'] === 'textarea') {
            $data[$key] = kingy_ali_sanitize_submission_textarea($value, $key === 'sources' ? 3000 : 2000);
        } elseif ($field['type'] === 'url') {
            $data[$key] = kingy_ali_sanitize_submission_url($value);
            if (trim((string) $value) !== '' && $data[$key] === '') {
                wp_die(
                    esc_html(
                        sprintf(
                            /* translators: %s is the submission field label. */
                            __('Please enter a valid http or https URL for %s.', 'kingy-ai-launch-intelligence'),
                            $field['label']
                        )
                    )
                );
            }
        } elseif ($field['type'] === 'email') {
            $data[$key] = sanitize_email($value);
            if (trim((string) $value) !== '' && !is_email($data[$key])) {
                wp_die(
                    esc_html(
                        sprintf(
                            /* translators: %s is the submission field label. */
                            __('Please enter a valid email address for %s.', 'kingy-ai-launch-intelligence'),
                            $field['label']
                        )
                    )
                );
            }
        } elseif ($field['type'] === 'select') {
            $data[$key] = kingy_ali_sanitize_submission_select($value, $field);
        } elseif ($field['type'] === 'taxonomy') {
            $data[$key] = kingy_ali_sanitize_submission_taxonomy($value, $field);
        } elseif ($field['type'] === 'date') {
            $data[$key] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? sanitize_text_field($value) : '';
        } else {
            $data[$key] = kingy_ali_sanitize_submission_text($value, 191);
        }

        if (!empty($field['required']) && $data[$key] === '') {
            wp_die(esc_html__('Required launch submission fields are missing.', 'kingy-ai-launch-intelligence'));
        }
    }

    if (kingy_ali_submission_rate_limited($data['founder_contact_email'])) {
        wp_die(esc_html__('Too many launch submissions were sent recently. Please wait before submitting another launch.', 'kingy-ai-launch-intelligence'));
    }

    $data['sources'] = kingy_ali_append_submission_source($data['sources'], __('Funding announcement', 'kingy-ai-launch-intelligence'), isset($data['funding']) ? $data['funding'] : '');
    $data['sources'] = kingy_ali_append_submission_source($data['sources'], __('Press kit', 'kingy-ai-launch-intelligence'), isset($data['press_kit_url']) ? $data['press_kit_url'] : '');

    $post_id = wp_insert_post(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => 'pending',
            'post_title' => $data['product_name'],
            'post_content' => $data['what_launched'],
            'post_excerpt' => wp_trim_words($data['what_launched'], 30),
        ),
        true
    );

    if (is_wp_error($post_id)) {
        wp_die(esc_html($post_id->get_error_message()));
    }

    kingy_ali_increment_submission_rate_limit($data['founder_contact_email']);

    $meta_map = array(
        'company',
        'founder_contact_name',
        'founder_contact_email',
        'official_url',
        'launch_date',
        'what_launched',
        'who_it_is_for',
        'pricing',
        'pricing_url',
        'free_plan',
        'api_available',
        'open_source_or_open_weight',
        'demo_url',
        'product_hunt_url',
        'github_url',
        'huggingface_url',
        'x_url',
        'funding',
        'press_kit_url',
        'media_urls',
        'sources',
        'kingy_verdict',
        'youtube_interest',
        'visibility_score_interest',
        'creator_coverage_interest',
        'sponsorship_interest',
        'founder_notes',
    );

    foreach ($meta_map as $key) {
        if (isset($data[$key]) && $data[$key] !== '') {
            update_post_meta($post_id, kingy_ali_meta_key($key), $data[$key]);
        }
    }

    update_post_meta($post_id, kingy_ali_meta_key('founder_submitted'), '1');
    update_post_meta($post_id, kingy_ali_meta_key('verification_status'), 'founder_submitted');

    if (!empty($data['category'])) {
        wp_set_object_terms($post_id, $data['category'], 'kingy_launch_category', false);
    }

    if (!empty($data['audience'])) {
        wp_set_object_terms($post_id, $data['audience'], 'kingy_audience', false);
    }

    wp_set_object_terms($post_id, 'founder-submitted', 'kingy_tool_attribute', true);
    $launch_type_terms = array('founder-submitted');
    if (!empty($data['launch_type'])) {
        $launch_type_terms[] = $data['launch_type'];
    }
    wp_set_object_terms($post_id, array_values(array_unique($launch_type_terms)), 'kingy_launch_type', false);
    $tool_id = kingy_ali_sync_tool_from_launch($post_id, $data['product_name']);
    kingy_ali_sync_derived_attributes($post_id);
    if ($tool_id) {
        kingy_ali_sync_derived_attributes($tool_id);
    }

    kingy_ali_track_event(
        'founder_submission',
        array(
            'event_label' => $data['product_name'],
            'object_id' => $post_id,
            'filters' => array(
                'category' => $data['category'],
                'audience' => $data['audience'],
                'launch_type' => isset($data['launch_type']) ? $data['launch_type'] : '',
                'youtube_interest' => isset($data['youtube_interest']) ? $data['youtube_interest'] : '',
                'visibility_score_interest' => isset($data['visibility_score_interest']) ? $data['visibility_score_interest'] : '',
                'creator_coverage_interest' => isset($data['creator_coverage_interest']) ? $data['creator_coverage_interest'] : '',
                'sponsorship_interest' => isset($data['sponsorship_interest']) ? $data['sponsorship_interest'] : '',
            ),
        )
    );

    $admin_email = get_option('admin_email');
    if ($admin_email) {
        wp_mail(
            $admin_email,
            sprintf(__('New AI launch submission: %s', 'kingy-ai-launch-intelligence'), $data['product_name']),
            sprintf(
                "%s\n\n%s\n\nCreator coverage interest: %s\nCreator campaign interest: %s\n\n%s",
                $data['product_name'],
                $data['what_launched'],
                isset($data['creator_coverage_interest']) && $data['creator_coverage_interest'] ? $data['creator_coverage_interest'] : __('Not sure', 'kingy-ai-launch-intelligence'),
                isset($data['sponsorship_interest']) && $data['sponsorship_interest'] ? $data['sponsorship_interest'] : __('Not sure', 'kingy-ai-launch-intelligence'),
                admin_url('post.php?post=' . $post_id . '&action=edit')
            )
        );
    }

    $redirect = add_query_arg('kingy_launch_submitted', '1', kingy_ali_public_form_redirect_base(home_url('/ai-launches/submit/')));
    wp_safe_redirect($redirect);
    exit;
}

function kingy_ali_append_submission_source($sources, $label, $url) {
    $sources = is_string($sources) ? trim($sources) : '';
    $url = kingy_ali_sanitize_submission_url($url);
    if (!$url) {
        return $sources;
    }

    if ($sources && strpos($sources, $url) !== false) {
        return $sources;
    }

    $source = sprintf('%s: %s', sanitize_text_field($label), $url);
    return $sources ? $sources . "\n" . $source : $source;
}

function kingy_ali_sanitize_submission_url($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $url = esc_url_raw($value, array('http', 'https'));
    return kingy_ali_submission_url_is_absolute_http($url) ? $url : '';
}

function kingy_ali_submission_url_is_absolute_http($url) {
    $parts = wp_parse_url((string) $url);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? trim((string) $parts['host']) : '';
    return in_array($scheme, array('http', 'https'), true) && $host !== '';
}

if (!function_exists('kingy_ali_request_server_values')) {
    function kingy_ali_request_server_values() {
        return is_array($_SERVER) ? $_SERVER : array();
    }
}

if (!function_exists('kingy_ali_request_remote_addr')) {
    function kingy_ali_request_remote_addr() {
        $values = kingy_ali_request_server_values();
        if (!isset($values['REMOTE_ADDR'])) {
            return '';
        }

        if (!is_scalar($values['REMOTE_ADDR'])) {
            return '';
        }

        $value = wp_unslash($values['REMOTE_ADDR']);
        if (!is_scalar($value)) {
            return '';
        }

        $value = sanitize_text_field((string) $value);
        return strlen($value) > 100 ? substr($value, 0, 100) : $value;
    }
}

function kingy_ali_submission_rate_limit_key($email) {
    $ip = kingy_ali_request_remote_addr();
    $email = sanitize_email($email);
    $seed = $email ? $email : $ip;
    if ($seed === '') {
        $seed = 'anonymous';
    }

    return 'kingy_ali_submit_' . hash('sha256', wp_salt('nonce') . $seed);
}

function kingy_ali_submission_rate_limited($email) {
    $count = (int) get_transient(kingy_ali_submission_rate_limit_key($email));
    return $count >= 3;
}

function kingy_ali_increment_submission_rate_limit($email) {
    $key = kingy_ali_submission_rate_limit_key($email);
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, HOUR_IN_SECONDS);
}

function kingy_ali_render_submission_success() {
    $client_examples_url = function_exists('kingy_ali_client_examples_url') ? kingy_ali_client_examples_url() : '';

    ob_start();
    ?>
    <div class="kingy-ali-success">
        <h2><?php esc_html_e('Thanks — your launch has been submitted for editorial review.', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('Strong submissions usually include a clear demo, pricing information, screenshots or video, a founder/company page, a simple explanation of who it helps, and official sources for claims.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
        <p><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get Launch Visibility Score after submission', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="submission_success" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI after submission', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="submission_success" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($client_examples_url) : ?>
                <a data-kingy-ali-track="clicked_client_examples_cta" data-event-label="<?php esc_attr_e('See Kingy AI client examples after submission', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="submission_success" href="<?php echo esc_url($client_examples_url); ?>"><?php esc_html_e('See Kingy AI client examples', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI after submission', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="submission_success" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Return to AI Launch Intelligence after submission', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="submission_success" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
