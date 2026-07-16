<?php
/**
 * Plugin Name: Kingy AI Agent Directory
 * Description: Server-rendered AI Agent Directory and workflow-readiness scorecard for Kingy AI.
 * Version: 1.1.0
 * Author: Kingy AI
 * Text Domain: kingy-agent-directory
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KAD_VERSION', '1.1.0');
define('KAD_PLUGIN_FILE', __FILE__);
define('KAD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KAD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KAD_PAGE_ID', 15708);
define('KAD_AGENT_TERM_ID', 2395);
define('KAD_CACHE_KEY', 'kad_verified_agent_items_v2');

add_action('init', 'kad_register_shortcode', 10);
add_action('wp_enqueue_scripts', 'kad_enqueue_assets');
add_action('wp_head', 'kad_output_schema', 40);
add_action('wp_head', 'kad_output_fallback_meta_description', 1);
add_action('wp_head', 'kad_output_nonpublic_canonical', 2);
add_action('template_redirect', 'kad_redirect_legacy_commercial_routes', 0);
add_action('save_post_kingy_ai_tool', 'kad_flush_directory_cache', 30);
add_action('deleted_post', 'kad_flush_directory_cache_for_deleted_post', 30, 2);
add_action('set_object_terms', 'kad_flush_directory_cache_for_terms', 30, 6);
add_filter('wpseo_metadesc', 'kad_filter_meta_description');
add_filter('wpseo_opengraph_desc', 'kad_filter_meta_description');
add_filter('wpseo_canonical', 'kad_filter_canonical');

function kad_register_shortcode() {
    add_shortcode('kingy_agent_directory', 'kad_render_directory_app');
}

function kad_is_directory_page() {
    return is_page(KAD_PAGE_ID) || is_page('ai-agent-directory');
}

function kad_meta_description() {
    return __('Compare verified AI agents by use case and audience, then use Kingy AI’s readiness scorecard to plan a safer, measurable agent pilot.', 'kingy-agent-directory');
}

function kad_filter_meta_description($description) {
    return kad_is_directory_page() ? kad_meta_description() : $description;
}

function kad_filter_canonical($canonical) {
    return kad_is_directory_page() ? home_url('/ai-agent-directory/') : $canonical;
}

function kad_output_fallback_meta_description() {
    if (!kad_is_directory_page() || defined('WPSEO_VERSION')) {
        return;
    }

    echo '<meta name="description" content="' . esc_attr(kad_meta_description()) . '">' . "\n";
}

function kad_output_nonpublic_canonical() {
    if (!kad_is_directory_page() || (int) get_option('blog_public') === 1) {
        return;
    }

    echo '<link rel="canonical" href="' . esc_url(home_url('/ai-agent-directory/')) . '">' . "\n";
}

function kad_enqueue_assets() {
    if (!kad_is_directory_page()) {
        return;
    }

    wp_enqueue_style(
        'kingy-agent-directory',
        KAD_PLUGIN_URL . 'assets/css/directory.css',
        array(),
        KAD_VERSION
    );
    wp_enqueue_script(
        'kingy-agent-directory',
        KAD_PLUGIN_URL . 'assets/js/directory.js',
        array(),
        KAD_VERSION,
        true
    );
}

function kad_get_meta($post_id, $key) {
    return trim((string) get_post_meta($post_id, '_kingy_ali_' . $key, true));
}

function kad_valid_https_url($url) {
    $url = esc_url_raw((string) $url, array('https'));
    if (!$url || !wp_http_validate_url($url)) {
        return '';
    }

    $parts = wp_parse_url($url);
    return isset($parts['scheme'], $parts['host']) && strtolower($parts['scheme']) === 'https' ? $url : '';
}

function kad_valid_verification_date($value) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
        return '';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}

function kad_format_verification_date($value) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value, wp_timezone());
    return $date ? wp_date(get_option('date_format'), $date->getTimestamp(), wp_timezone()) : $value;
}

function kad_excluded_tool_ids() {
    $raw = get_option('kad_excluded_tool_ids', array());
    if (!is_array($raw)) {
        $raw = preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
    }

    return array_values(array_filter(array_map('absint', $raw)));
}

function kad_approved_manifest() {
    static $records = null;
    if (is_array($records)) {
        return $records;
    }

    $decoded = json_decode((string) file_get_contents(KAD_PLUGIN_DIR . 'data/approved-agents.json'), true);
    $records = array();
    if (!is_array($decoded)) {
        return $records;
    }

    foreach ($decoded as $record) {
        $id = isset($record['id']) ? absint($record['id']) : 0;
        $slug = isset($record['slug']) ? sanitize_title($record['slug']) : '';
        $title = isset($record['title']) ? sanitize_text_field($record['title']) : '';
        $directory_type = isset($record['directory_type']) ? sanitize_key($record['directory_type']) : '';
        $availability = isset($record['availability']) ? sanitize_text_field($record['availability']) : '';
        $audit_verified = isset($record['audit_verified']) ? kad_valid_verification_date($record['audit_verified']) : '';

        if (!$id || !$slug || !$title || !$availability || !$audit_verified || !in_array($directory_type, array('direct_agent', 'direct_agent_preview'), true)) {
            continue;
        }

        $records[] = compact('id', 'slug', 'title', 'directory_type', 'availability', 'audit_verified');
    }

    return $records;
}

function kad_manifest_record_for_post($post) {
    if (!$post instanceof WP_Post) {
        return null;
    }

    $post_slug = sanitize_title($post->post_name);
    foreach (kad_approved_manifest() as $record) {
        if ((int) $record['id'] !== (int) $post->ID && $record['slug'] !== $post_slug) {
            continue;
        }

        if (wp_strip_all_tags($post->post_title) !== $record['title']) {
            return null;
        }

        return $record;
    }

    return null;
}

function kad_record_is_eligible($post_id) {
    if (in_array((int) $post_id, kad_excluded_tool_ids(), true)) {
        return false;
    }

    $post = get_post($post_id);
    $approval = kad_manifest_record_for_post($post);
    if (!$approval) {
        return false;
    }

    $official_url = kad_valid_https_url(kad_get_meta($post_id, 'official_url'));
    $verified_at = kad_valid_verification_date(kad_get_meta($post_id, 'last_verified'));
    $summary = kad_get_meta($post_id, 'what_it_does');
    $status = sanitize_key(kad_get_meta($post_id, 'verification_status'));

    if (!$official_url || !$verified_at || $summary === '') {
        return false;
    }

    if ($status !== '' && !in_array($status, array('verified', 'partially_verified'), true)) {
        return false;
    }

    return (bool) apply_filters('kingy_agent_directory_record_is_eligible', true, $post_id);
}

function kad_term_data($post_id, $taxonomy, $excluded_slugs = array()) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return array();
    }

    $data = array();
    foreach ($terms as $term) {
        if (in_array($term->slug, $excluded_slugs, true)) {
            continue;
        }
        $data[] = array(
            'name' => $term->name,
            'slug' => $term->slug,
        );
    }

    return $data;
}

function kad_get_items() {
    $cached = get_transient(KAD_CACHE_KEY);
    if (is_array($cached)) {
        return $cached;
    }

    $query = new WP_Query(array(
        'post_type' => 'kingy_ai_tool',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'tax_query' => array(
            array(
                'taxonomy' => 'kingy_launch_category',
                'field' => 'term_id',
                'terms' => array(KAD_AGENT_TERM_ID),
            ),
        ),
    ));

    $items = array();
    foreach ($query->posts as $post) {
        if (!kad_record_is_eligible($post->ID)) {
            continue;
        }

        $summary = kad_get_meta($post->ID, 'what_it_does');
        $best_for = kad_get_meta($post->ID, 'best_for');
        $company = kad_get_meta($post->ID, 'company');
        $official_url = kad_valid_https_url(kad_get_meta($post->ID, 'official_url'));
        $verified_at = kad_valid_verification_date(kad_get_meta($post->ID, 'last_verified'));
        $categories = kad_term_data($post->ID, 'kingy_launch_category', array('ai-agents'));
        $audiences = kad_term_data($post->ID, 'kingy_audience');
        $approval = kad_manifest_record_for_post($post);
        if (!$approval) {
            continue;
        }
        $directory_type = $approval['directory_type'];
        $availability = $approval['availability'];

        $items[] = array(
            'id' => (int) $post->ID,
            'name' => get_the_title($post),
            'profile_url' => get_permalink($post),
            'official_url' => $official_url,
            'summary' => $summary,
            'best_for' => $best_for,
            'company' => $company,
            'verified_at' => $verified_at,
            'directory_type' => $directory_type,
            'availability' => $availability,
            'categories' => $categories,
            'audiences' => $audiences,
            'thumbnail_id' => (int) get_post_thumbnail_id($post->ID),
        );
    }

    wp_reset_postdata();
    set_transient(KAD_CACHE_KEY, $items, 6 * HOUR_IN_SECONDS);
    return $items;
}

function kad_flush_directory_cache($post_id = 0) {
    if ($post_id && (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))) {
        return;
    }
    delete_transient(KAD_CACHE_KEY);
}

function kad_flush_directory_cache_for_deleted_post($post_id, $post) {
    if ($post && isset($post->post_type) && $post->post_type === 'kingy_ai_tool') {
        kad_flush_directory_cache();
    }
}

function kad_flush_directory_cache_for_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    unset($terms, $tt_ids, $append, $old_tt_ids);
    if ($taxonomy === 'kingy_launch_category' || $taxonomy === 'kingy_audience') {
        kad_flush_directory_cache($object_id);
    }
}

function kad_join_term_values($terms, $key) {
    if (!$terms) {
        return '';
    }

    return implode('|', array_map(static function ($term) use ($key) {
        return isset($term[$key]) ? (string) $term[$key] : '';
    }, $terms));
}

function kad_unique_filter_terms($items, $field) {
    $found = array();
    foreach ($items as $item) {
        foreach ($item[$field] as $term) {
            $found[$term['slug']] = $term['name'];
        }
    }
    asort($found, SORT_NATURAL | SORT_FLAG_CASE);
    return $found;
}

function kad_score_select($id, $label, $group, $options = null) {
    if ($options === null) {
        $options = array(
            '0' => __('No', 'kingy-agent-directory'),
            '1' => __('Somewhat', 'kingy-agent-directory'),
            '2' => __('Yes', 'kingy-agent-directory'),
        );
    }

    $html = '<div class="kad-field">';
    $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
    $html .= '<select id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" data-score-group="' . esc_attr($group) . '" required>';
    $html .= '<option value="">' . esc_html__('Choose an answer', 'kingy-agent-directory') . '</option>';
    foreach ($options as $value => $option_label) {
        $html .= '<option value="' . esc_attr($value) . '">' . esc_html($option_label) . '</option>';
    }
    $html .= '</select></div>';
    return $html;
}

function kad_render_scorecard() {
    ob_start();
    ?>
    <section class="kad-panel" id="kad-scorecard" role="tabpanel" aria-labelledby="kad-tab-scorecard" data-kad-panel="scorecard">
        <div class="kad-section-heading">
            <p class="kad-eyebrow"><?php esc_html_e('Readiness scorecard', 'kingy-agent-directory'); ?></p>
            <h2><?php esc_html_e('Check whether your workflow is agent-ready', 'kingy-agent-directory'); ?></h2>
            <p><?php esc_html_e('Score the workflow before choosing a vendor. The result identifies the weakest readiness area and a safer first pilot.', 'kingy-agent-directory'); ?></p>
        </div>
        <form class="kad-scorecard" data-kad-scorecard>
            <div class="kad-scorecard-fields">
                <fieldset>
                    <legend><?php esc_html_e('Workflow basics', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <div class="kad-field">
                            <label for="kad-task-name"><?php esc_html_e('Task name', 'kingy-agent-directory'); ?></label>
                            <input id="kad-task-name" name="task_name" type="text" placeholder="<?php esc_attr_e('Example: Draft weekly customer updates', 'kingy-agent-directory'); ?>" required>
                        </div>
                        <div class="kad-field">
                            <label for="kad-task-category"><?php esc_html_e('Task category', 'kingy-agent-directory'); ?></label>
                            <select id="kad-task-category" name="task_category" required>
                                <option value=""><?php esc_html_e('Choose a category', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Coding and development', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Research and analysis', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Sales and outreach', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Marketing and content', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Customer support', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Business operations', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Workflow automation', 'kingy-agent-directory'); ?></option>
                                <option><?php esc_html_e('Other', 'kingy-agent-directory'); ?></option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php esc_html_e('Task clarity', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <?php echo kad_score_select('kad-clear-description', __('Can the task be described clearly in one sentence?', 'kingy-agent-directory'), 'clarity'); ?>
                        <?php echo kad_score_select('kad-measurable-output', __('Are the desired outputs and success criteria measurable?', 'kingy-agent-directory'), 'clarity'); ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php esc_html_e('Repeatability', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <?php echo kad_score_select('kad-frequency', __('How often does this task happen?', 'kingy-agent-directory'), 'repeatability', array('0' => __('Rarely', 'kingy-agent-directory'), '1' => __('Monthly or weekly', 'kingy-agent-directory'), '2' => __('Several times a week or daily', 'kingy-agent-directory'))); ?>
                        <?php echo kad_score_select('kad-repeatable-steps', __('Are the steps mostly repeatable?', 'kingy-agent-directory'), 'repeatability'); ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php esc_html_e('Context and data', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <?php echo kad_score_select('kad-data-access', __('Can the agent access the information it needs?', 'kingy-agent-directory'), 'context'); ?>
                        <?php echo kad_score_select('kad-source-truth', __('Is the source of truth clear and current?', 'kingy-agent-directory'), 'context'); ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php esc_html_e('Tools and permissions', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <?php echo kad_score_select('kad-tool-access', __('Are required tools available through supported access?', 'kingy-agent-directory'), 'tools'); ?>
                        <?php echo kad_score_select('kad-limited-permissions', __('Can the agent work with limited, revocable permissions?', 'kingy-agent-directory'), 'tools'); ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php esc_html_e('Risk and approval', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <?php echo kad_score_select('kad-mistake-cost', __('How manageable is the cost of a mistake?', 'kingy-agent-directory'), 'risk', array('0' => __('High impact or hard to reverse', 'kingy-agent-directory'), '1' => __('Moderate impact', 'kingy-agent-directory'), '2' => __('Low impact and easy to reverse', 'kingy-agent-directory'))); ?>
                        <?php echo kad_score_select('kad-human-approval', __('Can a person approve important actions before they happen?', 'kingy-agent-directory'), 'risk'); ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php esc_html_e('Business value', 'kingy-agent-directory'); ?></legend>
                    <div class="kad-field-grid">
                        <?php echo kad_score_select('kad-business-value', __('Would this save time, reduce cost, increase revenue, or improve quality?', 'kingy-agent-directory'), 'value', array('0' => __('Low value', 'kingy-agent-directory'), '1' => __('Moderate value', 'kingy-agent-directory'), '2' => __('High value', 'kingy-agent-directory'))); ?>
                        <?php echo kad_score_select('kad-owner', __('Is there a clear owner for the workflow and its outcomes?', 'kingy-agent-directory'), 'value'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="kad-scorecard-result" data-kad-score-result aria-live="polite" tabindex="-1">
                <p class="kad-result-kicker"><?php esc_html_e('Your readiness report', 'kingy-agent-directory'); ?></p>
                <h3><?php esc_html_e('Complete the questions to calculate a score.', 'kingy-agent-directory'); ?></h3>
                <p><?php esc_html_e('The score is a planning aid, not a guarantee of product performance or safety.', 'kingy-agent-directory'); ?></p>
            </div>
            <div class="kad-actions">
                <button class="kad-button kad-button-primary" type="submit"><?php esc_html_e('Calculate readiness', 'kingy-agent-directory'); ?></button>
                <button class="kad-button kad-button-secondary" type="reset"><?php esc_html_e('Reset scorecard', 'kingy-agent-directory'); ?></button>
            </div>
            <noscript><p class="kad-notice"><?php esc_html_e('JavaScript is required for the calculator. The agent directory remains available below.', 'kingy-agent-directory'); ?></p></noscript>
        </form>
    </section>
    <?php
    return ob_get_clean();
}

function kad_render_directory_card($item) {
    $category_slugs = kad_join_term_values($item['categories'], 'slug');
    $audience_slugs = kad_join_term_values($item['audiences'], 'slug');
        $search_text = implode(' ', array(
        $item['name'],
        $item['company'],
        $item['summary'],
        kad_join_term_values($item['categories'], 'name'),
        kad_join_term_values($item['audiences'], 'name'),
    ));
    ?>
    <article class="kad-agent-card"
        data-agent-card
        data-agent-name="<?php echo esc_attr(wp_strip_all_tags($item['name'])); ?>"
        data-agent-search="<?php echo esc_attr(strtolower(wp_strip_all_tags($search_text))); ?>"
        data-agent-categories="<?php echo esc_attr($category_slugs); ?>"
        data-agent-audiences="<?php echo esc_attr($audience_slugs); ?>">
        <div class="kad-agent-body">
            <div class="kad-agent-heading">
                <div>
                    <?php if ($item['company']) : ?><p class="kad-agent-company"><?php echo esc_html($item['company']); ?></p><?php endif; ?>
                    <h3><a href="<?php echo esc_url($item['profile_url']); ?>"><?php echo esc_html($item['name']); ?></a></h3>
                </div>
                <time datetime="<?php echo esc_attr($item['verified_at']); ?>"><?php echo esc_html(sprintf(__('Checked %s', 'kingy-agent-directory'), kad_format_verification_date($item['verified_at']))); ?></time>
            </div>
            <?php if ($item['availability']) : ?>
                <p class="kad-availability<?php echo $item['directory_type'] === 'direct_agent_preview' ? ' kad-availability-preview' : ''; ?>"><?php echo esc_html($item['availability']); ?></p>
            <?php endif; ?>
            <?php if ($item['categories']) : ?>
                <div class="kad-badges" aria-label="<?php esc_attr_e('Categories', 'kingy-agent-directory'); ?>">
                    <?php foreach (array_slice($item['categories'], 0, 3) as $category) : ?>
                        <span><?php echo esc_html($category['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="kad-agent-summary"><?php echo esc_html($item['summary']); ?></p>
            <?php if ($item['audiences']) : ?>
                <p class="kad-best-for"><strong><?php esc_html_e('Audience:', 'kingy-agent-directory'); ?></strong> <?php echo esc_html(implode(', ', array_column($item['audiences'], 'name'))); ?></p>
            <?php endif; ?>
            <div class="kad-agent-actions">
                <a class="kad-button kad-button-primary" href="<?php echo esc_url($item['profile_url']); ?>"><?php esc_html_e('View Kingy profile', 'kingy-agent-directory'); ?></a>
                <a class="kad-button kad-button-secondary" href="<?php echo esc_url($item['official_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Official site', 'kingy-agent-directory'); ?></a>
            </div>
        </div>
    </article>
    <?php
}

function kad_render_agent_directory($items) {
    $categories = kad_unique_filter_terms($items, 'categories');
    $audiences = kad_unique_filter_terms($items, 'audiences');
    $count = count($items);
    ob_start();
    ?>
    <section class="kad-panel" id="kad-directory" role="tabpanel" aria-labelledby="kad-tab-directory" data-kad-panel="directory">
        <div class="kad-section-heading kad-directory-heading">
            <div>
                <p class="kad-eyebrow"><?php esc_html_e('Source-checked profiles', 'kingy-agent-directory'); ?></p>
                <h2><?php esc_html_e('Browse verified AI agents', 'kingy-agent-directory'); ?></h2>
                <p><?php esc_html_e('Explore agent products and platforms using structured Kingy profiles backed by official product sources and dated checks.', 'kingy-agent-directory'); ?></p>
            </div>
            <p class="kad-count" data-kad-count data-agent-count aria-live="polite"><?php echo esc_html(sprintf(_n('Showing %1$d of %2$d verified AI agent', 'Showing %1$d of %2$d verified AI agents', $count, 'kingy-agent-directory'), $count, $count)); ?></p>
        </div>

        <div class="kad-disclosure">
            <strong><?php esc_html_e('How to read this directory', 'kingy-agent-directory'); ?></strong>
            <p><?php esc_html_e('A source-checked profile confirms the product identity and cited description. It is not a hands-on Kingy review unless a separate review is explicitly linked.', 'kingy-agent-directory'); ?></p>
        </div>

        <div class="kad-filters" role="search" aria-label="<?php esc_attr_e('Filter AI agents', 'kingy-agent-directory'); ?>">
            <div class="kad-field kad-search-field">
                <label for="kad-agent-search"><?php esc_html_e('Search agents', 'kingy-agent-directory'); ?></label>
                <input id="kad-agent-search" type="search" placeholder="<?php esc_attr_e('Search by product, company, use case, or audience', 'kingy-agent-directory'); ?>" data-kad-search>
            </div>
            <div class="kad-field">
                <label for="kad-agent-category"><?php esc_html_e('Category', 'kingy-agent-directory'); ?></label>
                <select id="kad-agent-category" data-kad-category>
                    <option value=""><?php esc_html_e('All categories', 'kingy-agent-directory'); ?></option>
                    <?php foreach ($categories as $slug => $name) : ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="kad-field">
                <label for="kad-agent-audience"><?php esc_html_e('Audience', 'kingy-agent-directory'); ?></label>
                <select id="kad-agent-audience" data-kad-audience>
                    <option value=""><?php esc_html_e('All audiences', 'kingy-agent-directory'); ?></option>
                    <?php foreach ($audiences as $slug => $name) : ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="kad-field">
                <label for="kad-agent-sort"><?php esc_html_e('Sort', 'kingy-agent-directory'); ?></label>
                <select id="kad-agent-sort" name="agent_sort" data-kad-sort data-sort>
                    <option value="az"><?php esc_html_e('A–Z', 'kingy-agent-directory'); ?></option>
                    <option value="za"><?php esc_html_e('Z–A', 'kingy-agent-directory'); ?></option>
                </select>
            </div>
            <button class="kad-button kad-button-secondary kad-reset-filters" type="button" data-kad-reset><?php esc_html_e('Reset filters', 'kingy-agent-directory'); ?></button>
        </div>

        <?php if ($items) : ?>
            <div class="kad-agent-grid" data-kad-grid>
                <?php foreach ($items as $item) { kad_render_directory_card($item); } ?>
            </div>
            <nav class="kad-pagination" aria-label="<?php esc_attr_e('Agent directory pages', 'kingy-agent-directory'); ?>" data-kad-pagination hidden>
                <button class="kad-button kad-button-secondary" type="button" data-page-previous><?php esc_html_e('Previous', 'kingy-agent-directory'); ?></button>
                <p data-kad-page-status aria-live="polite"></p>
                <button class="kad-button kad-button-secondary" type="button" data-page-next><?php esc_html_e('Next', 'kingy-agent-directory'); ?></button>
            </nav>
            <div class="kad-empty" data-kad-empty hidden>
                <h3><?php esc_html_e('No agents match those filters.', 'kingy-agent-directory'); ?></h3>
                <p><?php esc_html_e('Clear the search or broaden the category and audience selections.', 'kingy-agent-directory'); ?></p>
            </div>
        <?php else : ?>
            <div class="kad-empty">
                <h3><?php esc_html_e('No source-checked profiles are available yet.', 'kingy-agent-directory'); ?></h3>
                <p><?php esc_html_e('Kingy AI will publish records here only after their product identity, official source, description, and verification date are complete.', 'kingy-agent-directory'); ?></p>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kad_render_submission_panel() {
    $submit_url = add_query_arg(array(
        'utm_source' => 'ai_agent_directory',
        'utm_medium' => 'tool',
        'utm_campaign' => 'submit_agent',
        'intent' => 'submit_agent',
    ), home_url('/contact/'));
    $sponsor_url = add_query_arg(array(
        'utm_source' => 'ai_agent_directory',
        'utm_medium' => 'tool',
        'utm_campaign' => 'sponsor_video',
    ), home_url('/sponsor-kingy-ai/'));
    $review_url = add_query_arg(array(
        'utm_source' => 'ai_agent_directory',
        'utm_medium' => 'tool',
        'utm_campaign' => 'request_review',
        'intent' => 'product_review',
    ), home_url('/contact/'));

    ob_start();
    ?>
    <section class="kad-panel" id="kad-submit" role="tabpanel" aria-labelledby="kad-tab-submit" data-kad-panel="submit">
        <div class="kad-section-heading">
            <p class="kad-eyebrow"><?php esc_html_e('For AI companies', 'kingy-agent-directory'); ?></p>
            <h2><?php esc_html_e('Submit an AI agent', 'kingy-agent-directory'); ?></h2>
            <p><?php esc_html_e('Send Kingy AI the official product information needed for directory consideration, or choose a separate review or sponsorship path.', 'kingy-agent-directory'); ?></p>
        </div>
        <div class="kad-commercial-grid">
            <article>
                <h3><?php esc_html_e('Directory submission', 'kingy-agent-directory'); ?></h3>
                <p><?php esc_html_e('Share the official site, documentation, audience, use case, and current availability for editorial consideration.', 'kingy-agent-directory'); ?></p>
                <a class="kad-button kad-button-primary" href="<?php echo esc_url($submit_url); ?>"><?php esc_html_e('Submit your AI agent', 'kingy-agent-directory'); ?></a>
            </article>
            <article>
                <h3><?php esc_html_e('Product review', 'kingy-agent-directory'); ?></h3>
                <p><?php esc_html_e('Request a hands-on editorial review or walkthrough. Directory inclusion does not guarantee review coverage.', 'kingy-agent-directory'); ?></p>
                <a class="kad-button kad-button-primary" href="<?php echo esc_url($review_url); ?>"><?php esc_html_e('Request a product review', 'kingy-agent-directory'); ?></a>
            </article>
            <article>
                <h3><?php esc_html_e('Sponsored video', 'kingy-agent-directory'); ?></h3>
                <p><?php esc_html_e('Explore creator-led launch, education, and product-demonstration campaigns with clear sponsorship disclosure.', 'kingy-agent-directory'); ?></p>
                <a class="kad-button kad-button-primary" href="<?php echo esc_url($sponsor_url); ?>"><?php esc_html_e('Sponsor a Kingy AI video', 'kingy-agent-directory'); ?></a>
            </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kad_render_directory_app() {
    $items = kad_get_items();
    $page_id = get_queried_object_id();
    ob_start();
    ?>
    <div class="kad-app" data-kad-app>
        <?php if ($page_id && has_post_thumbnail($page_id)) : ?>
            <figure class="kad-page-hero">
                <?php echo get_the_post_thumbnail($page_id, 'full', array('loading' => 'eager', 'fetchpriority' => 'high')); ?>
            </figure>
        <?php endif; ?>
        <section class="kad-intro" aria-labelledby="kad-intro-title">
            <p class="kad-eyebrow"><?php esc_html_e('AI agent decision support', 'kingy-agent-directory'); ?></p>
            <h2 id="kad-intro-title"><?php esc_html_e('Choose the workflow first, then compare agents', 'kingy-agent-directory'); ?></h2>
            <p><?php esc_html_e('Use the readiness scorecard to test whether a workflow is suitable for agentic automation, then explore source-checked agent profiles by use case and audience.', 'kingy-agent-directory'); ?></p>
        </section>

        <div class="kad-tabs" role="tablist" aria-label="<?php esc_attr_e('Agent directory tools', 'kingy-agent-directory'); ?>">
            <button id="kad-tab-directory" role="tab" aria-selected="true" aria-controls="kad-directory" tabindex="0" data-kad-tab="directory" type="button"><?php esc_html_e('Directory', 'kingy-agent-directory'); ?></button>
            <button id="kad-tab-scorecard" role="tab" aria-selected="false" aria-controls="kad-scorecard" tabindex="-1" data-kad-tab="scorecard" type="button"><?php esc_html_e('Readiness scorecard', 'kingy-agent-directory'); ?></button>
            <button id="kad-tab-submit" role="tab" aria-selected="false" aria-controls="kad-submit" tabindex="-1" data-kad-tab="submit" type="button"><?php esc_html_e('Submit an agent', 'kingy-agent-directory'); ?></button>
        </div>

        <div class="kad-panels">
            <?php echo kad_render_agent_directory($items); ?>
            <?php echo kad_render_scorecard(); ?>
            <?php echo kad_render_submission_panel(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kad_output_schema() {
    if (!kad_is_directory_page()) {
        return;
    }

    $items = kad_get_items();
    if (!$items) {
        return;
    }

    $list_items = array();
    foreach ($items as $index => $item) {
        $list_items[] = array(
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => wp_strip_all_tags($item['name']),
            'url' => $item['profile_url'],
        );
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        '@id' => home_url('/ai-agent-directory/#collection'),
        'url' => home_url('/ai-agent-directory/'),
        'name' => __('AI Agent Directory & Readiness Scorecard', 'kingy-agent-directory'),
        'description' => kad_meta_description(),
        'mainEntity' => array(
            '@type' => 'ItemList',
            'name' => __('Verified AI agent profiles', 'kingy-agent-directory'),
            'numberOfItems' => count($list_items),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'itemListElement' => $list_items,
        ),
    );

    echo '<script type="application/ld+json" id="kingy-agent-directory-schema">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function kad_redirect_legacy_commercial_routes() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $path = wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH);
    $path = trailingslashit((string) $path);
    $target = '';
    $extra = array();

    if ($path === '/sponsored-video-inquiry/') {
        $target = home_url('/sponsor-kingy-ai/');
    } elseif ($path === '/ai-tool-review/') {
        $target = home_url('/contact/');
        $extra['intent'] = 'product_review';
    }

    if (!$target) {
        return;
    }

    foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $key) {
        if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
            $extra[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
        }
    }

    if ($extra) {
        $target = add_query_arg($extra, $target);
    }

    wp_safe_redirect($target, 301, 'Kingy AI Agent Directory');
    exit;
}
