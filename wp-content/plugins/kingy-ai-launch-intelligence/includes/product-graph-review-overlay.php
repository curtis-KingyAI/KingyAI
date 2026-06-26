<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'kingy_ali_pg_review_overlay_force_download', 1);
add_action('admin_init', 'kingy_ali_pg_review_overlay_force_health_download', 1);

function kingy_ali_pg_review_overlay_force_download() {
    if (!is_admin() || !isset($_GET['page'], $_GET['kingy_pg_review_overlay_download']) || sanitize_key(wp_unslash($_GET['page'])) !== 'kingy-ali-product-graph') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph review metadata.', 'kingy-ai-launch-intelligence'));
    }

    $format = sanitize_key(wp_unslash($_GET['kingy_pg_review_overlay_download']));
    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph review overlay export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_review_overlay_download_' . $format);

    $rows = kingy_ali_pg_review_overlay_force_flat_records();
    $filename = 'kingy-product-graph-review-overlay-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('row_type', 'review_row_id', 'review_state', 'reviewer_notes', 'reviewer_user_id', 'reviewer_login', 'reviewer_display_name', 'reviewed_at_utc', 'source_artifact_id'));
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'storage' => 'wordpress_option:kingy_ali_product_graph_review_overlay',
            'records_count' => count($rows),
            'records' => $rows,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_pg_review_overlay_force_health_download() {
    if (!is_admin() || !isset($_GET['page']) || (!isset($_GET['kingy_pg_health_export']) && !isset($_GET['kingy_pg_health_download'])) || sanitize_key(wp_unslash($_GET['page'])) !== 'kingy-ali-product-graph') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph health data.', 'kingy-ai-launch-intelligence'));
    }

    $format = isset($_GET['kingy_pg_health_export']) ? sanitize_key(wp_unslash($_GET['kingy_pg_health_export'])) : sanitize_key(wp_unslash($_GET['kingy_pg_health_download']));
    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph health export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_health_download_' . $format);

    $health = kingy_ali_pg_review_overlay_health_data();
    $filename = 'kingy-product-graph-health-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('issue_type', 'priority', 'row_type', 'row_id', 'node_type', 'edge_type', 'review_state', 'title', 'detail'));
        foreach ($health['relationship_qa_queue'] as $row) {
            fputcsv($out, array($row['issue_type'], $row['priority'], $row['row_type'], $row['row_id'], $row['node_type'], $row['edge_type'], $row['review_state'], $row['title'], $row['detail']));
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_health',
            'source' => 'existing_product_graph_review_datasets',
            'health' => $health,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_pg_review_overlay_force_flat_records() {
    $overlay = get_option('kingy_ali_product_graph_review_overlay', array());
    $rows = array();

    if (!is_array($overlay)) {
        return $rows;
    }

    foreach ($overlay as $row_type => $records) {
        if (!is_array($records)) {
            continue;
        }
        foreach ($records as $row_id => $record) {
            if (!is_array($record)) {
                continue;
            }
            $rows[] = array(
                'row_type' => sanitize_key(isset($record['row_type']) ? $record['row_type'] : $row_type),
                'review_row_id' => sanitize_text_field(isset($record['review_row_id']) ? $record['review_row_id'] : $row_id),
                'review_state' => sanitize_key(isset($record['review_state']) ? $record['review_state'] : 'unreviewed'),
                'reviewer_notes' => sanitize_textarea_field(isset($record['reviewer_notes']) ? $record['reviewer_notes'] : ''),
                'reviewer_user_id' => absint(isset($record['reviewer_user_id']) ? $record['reviewer_user_id'] : 0),
                'reviewer_login' => sanitize_user(isset($record['reviewer_login']) ? $record['reviewer_login'] : '', true),
                'reviewer_display_name' => sanitize_text_field(isset($record['reviewer_display_name']) ? $record['reviewer_display_name'] : ''),
                'reviewed_at_utc' => sanitize_text_field(isset($record['reviewed_at_utc']) ? $record['reviewed_at_utc'] : ''),
                'source_artifact_id' => sanitize_file_name(isset($record['source_artifact_id']) ? $record['source_artifact_id'] : ''),
            );
        }
    }

    return $rows;
}

if (function_exists('kingy_ali_product_graph_maybe_save_review_state')) {
    return;
}

add_action('admin_init', 'kingy_ali_pg_review_overlay_maybe_save');
add_action('admin_init', 'kingy_ali_pg_review_overlay_maybe_download');
add_action('admin_notices', 'kingy_ali_pg_review_overlay_notice');
add_action('admin_footer', 'kingy_ali_pg_review_overlay_footer');

function kingy_ali_pg_review_overlay_option_name() {
    return 'kingy_ali_product_graph_review_overlay';
}

function kingy_ali_pg_review_overlay_states() {
    return array(
        'unreviewed' => __('Unreviewed', 'kingy-ai-launch-intelligence'),
        'accepted' => __('Accepted', 'kingy-ai-launch-intelligence'),
        'rejected' => __('Rejected', 'kingy-ai-launch-intelligence'),
        'needs_source' => __('Needs source', 'kingy-ai-launch-intelligence'),
        'needs_refresh' => __('Needs refresh', 'kingy-ai-launch-intelligence'),
        'needs_canonical_review' => __('Needs canonical review', 'kingy-ai-launch-intelligence'),
        'model_inventory_blocked' => __('Model inventory blocked', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_pg_review_overlay_tab_types() {
    return array(
        'nodes' => 'node',
        'edges' => 'edge',
        'resolver' => 'resolver',
        'unresolved_queue' => 'unresolved_queue',
        'model_inventory' => 'model_inventory',
    );
}

function kingy_ali_pg_review_overlay_dataset_files() {
    $prefix = '2026-06-17-product-graph-review-';

    return array(
        'nodes' => $prefix . 'nodes.json',
        'edges' => $prefix . 'edges.json',
        'resolver' => $prefix . 'resolver.json',
        'unresolved_queue' => $prefix . 'unresolved_queue.json',
        'model_inventory' => $prefix . 'model_inventory.json',
    );
}

function kingy_ali_pg_review_overlay_reports_dir() {
    $workspace_root = dirname(dirname(dirname(KINGY_ALI_PLUGIN_DIR)));
    return trailingslashit($workspace_root) . 'kingy-ai-launch-system/data/reports';
}

function kingy_ali_pg_review_overlay_read_dataset($key) {
    $files = kingy_ali_pg_review_overlay_dataset_files();
    if (!isset($files[$key])) {
        return array();
    }

    $base = realpath(kingy_ali_pg_review_overlay_reports_dir());
    if (!$base || !is_dir($base)) {
        return array();
    }

    $path = realpath(trailingslashit($base) . $files[$key]);
    if (!$path || !is_file($path) || strpos($path, trailingslashit($base)) !== 0) {
        return array();
    }

    $contents = file_get_contents($path);
    if (!is_string($contents) || $contents === '') {
        return array();
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : array();
}

function kingy_ali_pg_review_overlay_row_value($row, $key, $default = '') {
    if (!is_array($row) || !array_key_exists($key, $row)) {
        return $default;
    }

    $value = $row[$key];
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_array($value)) {
        $parts = array();
        foreach ($value as $item) {
            $parts[] = is_scalar($item) ? (string) $item : wp_json_encode($item);
        }
        return implode(', ', $parts);
    }
    if (is_scalar($value)) {
        return (string) $value;
    }

    return $default;
}

function kingy_ali_pg_review_overlay_count(&$counts, $key) {
    $key = sanitize_text_field((string) $key);
    if ($key === '') {
        $key = '(blank)';
    }
    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }
    $counts[$key]++;
}

function kingy_ali_pg_review_overlay_get($key, $max_length = 191) {
    if (!is_array($_GET) || !isset($_GET[$key]) || !is_scalar($_GET[$key])) {
        return '';
    }

    return kingy_ali_pg_review_overlay_limit_text(sanitize_text_field((string) wp_unslash($_GET[$key])), $max_length);
}

function kingy_ali_pg_review_overlay_post($key, $max_length = 191) {
    if (!is_array($_POST) || !isset($_POST[$key]) || !is_scalar($_POST[$key])) {
        return '';
    }

    return kingy_ali_pg_review_overlay_limit_text(sanitize_text_field((string) wp_unslash($_POST[$key])), $max_length);
}

function kingy_ali_pg_review_overlay_limit_text($value, $max_length) {
    $max_length = absint($max_length);
    if ($max_length > 0 && function_exists('mb_strlen') && mb_strlen($value) > $max_length) {
        return mb_substr($value, 0, $max_length);
    }

    return $max_length > 0 && strlen($value) > $max_length ? substr($value, 0, $max_length) : $value;
}

function kingy_ali_pg_review_overlay_row_id($value) {
    return kingy_ali_pg_review_overlay_limit_text(sanitize_text_field((string) wp_unslash($value)), 500);
}

function kingy_ali_pg_review_overlay_notes($value) {
    return kingy_ali_pg_review_overlay_limit_text(sanitize_textarea_field((string) wp_unslash($value)), 1000);
}

function kingy_ali_pg_review_overlay_records() {
    $overlay = get_option(kingy_ali_pg_review_overlay_option_name(), array());
    return is_array($overlay) ? $overlay : array();
}

function kingy_ali_pg_review_overlay_record($row_type, $row_id) {
    $overlay = kingy_ali_pg_review_overlay_records();
    $row_type = sanitize_key($row_type);
    $row_id = kingy_ali_pg_review_overlay_row_id($row_id);

    return isset($overlay[$row_type][$row_id]) && is_array($overlay[$row_type][$row_id])
        ? $overlay[$row_type][$row_id]
        : array();
}

function kingy_ali_pg_review_overlay_is_page() {
    return is_admin() && kingy_ali_pg_review_overlay_get('page', 80) === 'kingy-ali-product-graph';
}

function kingy_ali_pg_review_overlay_maybe_save() {
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
    if ($method !== 'POST' || kingy_ali_pg_review_overlay_post('kingy_pg_review_action', 80) !== 'save_review_state') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to save Product Graph review metadata.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_review_state', 'kingy_pg_review_nonce');

    $row_type = sanitize_key(kingy_ali_pg_review_overlay_post('kingy_pg_row_type', 80));
    $row_id = kingy_ali_pg_review_overlay_row_id(kingy_ali_pg_review_overlay_post('kingy_pg_review_row_id', 500));
    $review_state = sanitize_key(kingy_ali_pg_review_overlay_post('kingy_pg_review_state', 80));
    $notes = kingy_ali_pg_review_overlay_notes(isset($_POST['kingy_pg_reviewer_notes']) ? $_POST['kingy_pg_reviewer_notes'] : '');
    $source_artifact_id = sanitize_file_name(kingy_ali_pg_review_overlay_post('kingy_pg_source_artifact_id', 191));

    $tab = kingy_ali_pg_review_overlay_tab_for_type($row_type);
    $states = kingy_ali_pg_review_overlay_states();

    if (!$tab || $row_id === '' || !isset($states[$review_state]) || !kingy_ali_pg_review_overlay_row_exists($tab, $row_id)) {
        kingy_ali_pg_review_overlay_redirect($tab ? $tab : 'summary', false);
    }

    $user = wp_get_current_user();
    $overlay = kingy_ali_pg_review_overlay_records();
    if (!isset($overlay[$row_type]) || !is_array($overlay[$row_type])) {
        $overlay[$row_type] = array();
    }

    $overlay[$row_type][$row_id] = array(
        'row_type' => $row_type,
        'review_row_id' => $row_id,
        'review_state' => $review_state,
        'reviewer_notes' => $notes,
        'reviewer_user_id' => absint($user->ID),
        'reviewer_login' => sanitize_user($user->user_login, true),
        'reviewer_display_name' => sanitize_text_field($user->display_name),
        'reviewed_at_utc' => gmdate('c'),
        'source_artifact_id' => $source_artifact_id,
    );

    update_option(kingy_ali_pg_review_overlay_option_name(), $overlay, false);
    kingy_ali_pg_review_overlay_redirect($tab, true);
}

function kingy_ali_pg_review_overlay_maybe_download() {
    if (!kingy_ali_pg_review_overlay_is_page()) {
        return;
    }

    $format = kingy_ali_pg_review_overlay_get('kingy_pg_review_overlay_download', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph review metadata.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph review overlay export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_review_overlay_download_' . $format);

    $rows = kingy_ali_pg_review_overlay_flat_records();
    $filename = 'kingy-product-graph-review-overlay-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('row_type', 'review_row_id', 'review_state', 'reviewer_notes', 'reviewer_user_id', 'reviewer_login', 'reviewer_display_name', 'reviewed_at_utc', 'source_artifact_id'));
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'storage' => 'wordpress_option:' . kingy_ali_pg_review_overlay_option_name(),
            'records_count' => count($rows),
            'records' => $rows,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_pg_review_overlay_notice() {
    if (!kingy_ali_pg_review_overlay_is_page()) {
        return;
    }

    $saved = kingy_ali_pg_review_overlay_get('kingy_pg_review_saved', 10);
    if ($saved === '') {
        return;
    }

    $message = $saved === '1'
        ? __('Product Graph review metadata saved. No graph artifact or WordPress content was changed.', 'kingy-ai-launch-intelligence')
        : __('Product Graph review metadata was not saved because the row, state, or nonce was invalid.', 'kingy-ai-launch-intelligence');
    $class = $saved === '1' ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';

    echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
}

function kingy_ali_pg_review_overlay_footer() {
    if (!kingy_ali_pg_review_overlay_is_page() || !current_user_can('manage_options')) {
        return;
    }

    $records = kingy_ali_pg_review_overlay_records();
    $flat_records = kingy_ali_pg_review_overlay_flat_records();
    $data = array(
        'nonce' => wp_create_nonce('kingy_ali_product_graph_review_state'),
        'actionUrl' => add_query_arg(array('page' => 'kingy-ali-product-graph'), admin_url('admin.php')),
        'downloadLinks' => kingy_ali_pg_review_overlay_download_links(),
        'healthData' => kingy_ali_pg_review_overlay_health_data(),
        'healthDownloadLinks' => kingy_ali_pg_review_overlay_health_download_links(),
        'records' => $records,
        'flatRecords' => $flat_records,
        'recordCount' => count($flat_records),
        'summary' => kingy_ali_pg_review_overlay_summary($flat_records),
        'states' => kingy_ali_pg_review_overlay_states(),
        'sourceArtifacts' => kingy_ali_pg_review_overlay_dataset_files(),
        'tabTypes' => kingy_ali_pg_review_overlay_tab_types(),
    );
    ?>
    <script>
    (function () {
        var config = <?php echo wp_json_encode($data); ?>;
        var params = new URLSearchParams(window.location.search);
        var tab = params.get('kingy_pg_tab') || 'summary';
        var tabTypes = config.tabTypes || {};
        var sourceArtifacts = config.sourceArtifacts || {};

        function text(value) {
            return value == null ? '' : String(value);
        }

        function appendText(parent, tag, value, className) {
            var element = document.createElement(tag);
            if (className) {
                element.className = className;
            }
            element.textContent = text(value);
            parent.appendChild(element);
            return element;
        }

        function currentUrl(extra) {
            var args = new URLSearchParams(extra || {});
            args.set('page', 'kingy-ali-product-graph');
            return config.actionUrl.split('?')[0] + '?' + args.toString();
        }

        function rowIdFor(tabName, cells) {
            var first = cells[0] ? cells[0].innerText.trim() : '';
            if (tabName === 'nodes') {
                return first ? 'node-review:' + first : '';
            }
            if (tabName === 'edges') {
                return first ? 'edge-review:' + first : '';
            }
            if (tabName === 'resolver') {
                return first ? 'url_resolver:' + first : '';
            }
            if (tabName === 'unresolved_queue') {
                return first ? 'unresolved_queue:' + first : '';
            }
            if (tabName === 'model_inventory') {
                return 'model-inventory:kingy_ai_model';
            }
            return '';
        }

        function overlayRecord(rowType, rowId) {
            return config.records && config.records[rowType] && config.records[rowType][rowId]
                ? config.records[rowType][rowId]
                : {};
        }

        function addHidden(form, name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value || '';
            form.appendChild(input);
        }

        function addReviewControls() {
            var rowType = tabTypes[tab];
            if (!rowType) {
                return;
            }

            var table = document.querySelector('table.widefat');
            if (!table || table.dataset.kingyPgOverlay === '1') {
                return;
            }
            table.dataset.kingyPgOverlay = '1';

            var headerRow = table.querySelector('thead tr');
            if (headerRow) {
                var th = document.createElement('th');
                th.scope = 'col';
                th.textContent = 'Review Overlay';
                headerRow.appendChild(th);
            }

            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var cells = Array.prototype.slice.call(tr.querySelectorAll('td'));
                var rowId = rowIdFor(tab, cells);
                var record = overlayRecord(rowType, rowId);
                var state = record.review_state || (cells[5] ? cells[5].innerText.trim() : '') || 'unreviewed';
                var notes = record.reviewer_notes || '';
                var td = document.createElement('td');
                var form = document.createElement('form');
                form.method = 'post';
                form.action = config.actionUrl + '&kingy_pg_tab=' + encodeURIComponent(tab);
                form.style.minWidth = '260px';

                addHidden(form, 'kingy_pg_review_nonce', config.nonce);
                addHidden(form, 'kingy_pg_review_action', 'save_review_state');
                addHidden(form, 'kingy_pg_row_type', rowType);
                addHidden(form, 'kingy_pg_review_row_id', rowId);
                addHidden(form, 'kingy_pg_source_artifact_id', sourceArtifacts[tab] || '');

                var code = document.createElement('code');
                code.textContent = rowId || 'missing-review-row-id';
                form.appendChild(code);

                var select = document.createElement('select');
                select.name = 'kingy_pg_review_state';
                select.style.display = 'block';
                select.style.margin = '6px 0';
                Object.keys(config.states || {}).forEach(function (key) {
                    var option = document.createElement('option');
                    option.value = key;
                    option.textContent = config.states[key];
                    if (key === state) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                form.appendChild(select);

                var textarea = document.createElement('textarea');
                textarea.name = 'kingy_pg_reviewer_notes';
                textarea.rows = 2;
                textarea.placeholder = 'Reviewer note';
                textarea.style.width = '100%';
                textarea.style.maxWidth = '360px';
                textarea.value = text(notes);
                form.appendChild(textarea);

                var button = document.createElement('button');
                button.type = 'submit';
                button.className = 'button button-small';
                button.style.display = 'block';
                button.style.marginTop = '6px';
                button.textContent = 'Save Review State';
                form.appendChild(button);

                if (record.reviewed_at_utc) {
                    var saved = document.createElement('p');
                    saved.style.margin = '6px 0 0';
                    var small = document.createElement('small');
                    small.textContent = 'Saved by ' + (record.reviewer_display_name || 'unknown reviewer') + ' at ' + record.reviewed_at_utc + ' UTC.';
                    saved.appendChild(small);
                    form.appendChild(saved);
                }

                td.appendChild(form);
            tr.appendChild(td);
            });
        }

        function addReviewOverlayNavTab() {
            var nav = document.querySelector('.nav-tab-wrapper');
            if (!nav || document.getElementById('kingy-pg-review-overlay-nav-tab')) {
                return;
            }

            var health = document.createElement('a');
            health.id = 'kingy-pg-graph-health-nav-tab';
            health.className = 'nav-tab';
            health.href = currentUrl({ kingy_pg_tab: 'graph_health' });
            health.textContent = 'Graph Health';
            if (tab === 'graph_health') {
                nav.querySelectorAll('.nav-tab-active').forEach(function (active) {
                    active.classList.remove('nav-tab-active');
                });
                health.classList.add('nav-tab-active');
            }
            nav.appendChild(health);

            var link = document.createElement('a');
            link.id = 'kingy-pg-review-overlay-nav-tab';
            link.className = 'nav-tab';
            link.href = currentUrl({ kingy_pg_tab: 'review_overlay' });
            link.textContent = 'Review Overlay';
            if (tab === 'review_overlay') {
                nav.querySelectorAll('.nav-tab-active').forEach(function (active) {
                    active.classList.remove('nav-tab-active');
                });
                link.classList.add('nav-tab-active');
            }
            nav.appendChild(link);
        }

        function addReviewOverlaySummary() {
            var wrap = document.querySelector('.kingy-ali-product-graph-review') || document.querySelector('.wrap');
            if (!wrap || document.getElementById('kingy-pg-review-overlay-summary')) {
                return;
            }

            var section = document.createElement('div');
            section.id = 'kingy-pg-review-overlay-summary';
            appendText(section, 'h3', 'Review Overlay Summary');

            var summary = config.summary || {};
            var p = document.createElement('p');
            p.appendChild(document.createTextNode('Saved review records: ' + Number(summary.total_saved_review_records || 0)));
            p.appendChild(document.createElement('br'));
            p.appendChild(document.createTextNode('Latest reviewed timestamp: ' + (summary.latest_reviewed_at_utc || 'No saved review metadata yet.')));
            section.appendChild(p);

            function tableFromCounts(title, counts) {
                appendText(section, 'h4', title);
                var table = document.createElement('table');
                table.className = 'widefat striped';
                var tbody = document.createElement('tbody');
                Object.keys(counts || {}).forEach(function (key) {
                    var tr = document.createElement('tr');
                    appendText(tr, 'th', key.replace(/_/g, ' '));
                    appendText(tr, 'td', counts[key]);
                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                section.appendChild(table);
            }

            tableFromCounts('Counts by State', summary.counts_by_state || {});
            tableFromCounts('Counts by Row Type', summary.counts_by_row_type || {});

            var open = document.createElement('a');
            open.className = 'button';
            open.href = currentUrl({ kingy_pg_tab: 'review_overlay' });
            open.textContent = 'Open Review Overlay';
            section.appendChild(open);

            var marker = document.getElementById('kingy-pg-review-overlay-export');
            if (marker) {
                marker.parentNode.insertBefore(section, marker);
                return;
            }

            var nav = document.querySelector('.nav-tab-wrapper');
            if (nav && nav.nextSibling) {
                nav.parentNode.insertBefore(section, nav.nextSibling);
            } else {
                wrap.appendChild(section);
            }
        }

        function overlayRows() {
            var rows = Array.isArray(config.flatRecords) ? config.flatRecords.slice() : [];
            var params = new URLSearchParams(window.location.search);
            var wantedState = params.get('kingy_pg_overlay_state') || '';
            var wantedType = params.get('kingy_pg_overlay_type') || '';
            var search = (params.get('kingy_pg_overlay_search') || '').toLowerCase();

            return rows.filter(function (row) {
                if (wantedState && row.review_state !== wantedState) {
                    return false;
                }
                if (wantedType && row.row_type !== wantedType) {
                    return false;
                }
                if (search) {
                    var haystack = [
                        row.review_row_id,
                        row.reviewer_notes,
                        row.reviewer_login,
                        row.reviewer_display_name,
                        row.source_artifact_id
                    ].map(text).join(' ').toLowerCase();
                    if (haystack.indexOf(search) === -1) {
                        return false;
                    }
                }
                return true;
            });
        }

        function renderReviewOverlayTab() {
            if (tab !== 'review_overlay') {
                return;
            }

            var nav = document.querySelector('.nav-tab-wrapper');
            var wrap = document.querySelector('.kingy-ali-product-graph-review') || document.querySelector('.wrap');
            if (!nav || !wrap || document.getElementById('kingy-pg-review-overlay-tab')) {
                return;
            }

            while (nav.nextSibling) {
                nav.parentNode.removeChild(nav.nextSibling);
            }

            var section = document.createElement('div');
            section.id = 'kingy-pg-review-overlay-tab';
            appendText(section, 'h2', 'Review Overlay');
            appendText(section, 'p', 'This tab lists reviewer metadata only. Graph artifacts and WordPress content remain unchanged.');

            var params = new URLSearchParams(window.location.search);
            var form = document.createElement('form');
            form.method = 'get';
            form.action = config.actionUrl.split('?')[0];
            form.style.margin = '16px 0';
            [
                ['page', 'kingy-ali-product-graph'],
                ['kingy_pg_tab', 'review_overlay']
            ].forEach(function (pair) {
                addHidden(form, pair[0], pair[1]);
            });

            var stateSelect = document.createElement('select');
            stateSelect.name = 'kingy_pg_overlay_state';
            var anyState = document.createElement('option');
            anyState.value = '';
            anyState.textContent = 'Any state';
            stateSelect.appendChild(anyState);
            Object.keys(config.states || {}).forEach(function (key) {
                var option = document.createElement('option');
                option.value = key;
                option.textContent = config.states[key];
                if (params.get('kingy_pg_overlay_state') === key) {
                    option.selected = true;
                }
                stateSelect.appendChild(option);
            });
            form.appendChild(stateSelect);

            var typeSelect = document.createElement('select');
            typeSelect.name = 'kingy_pg_overlay_type';
            typeSelect.style.marginLeft = '8px';
            var anyType = document.createElement('option');
            anyType.value = '';
            anyType.textContent = 'Any row type';
            typeSelect.appendChild(anyType);
            Object.keys(tabTypes).forEach(function (tabName) {
                var type = tabTypes[tabName];
                var option = document.createElement('option');
                option.value = type;
                option.textContent = type;
                if (params.get('kingy_pg_overlay_type') === type) {
                    option.selected = true;
                }
                typeSelect.appendChild(option);
            });
            form.appendChild(typeSelect);

            var search = document.createElement('input');
            search.type = 'search';
            search.name = 'kingy_pg_overlay_search';
            search.placeholder = 'Search row ID, reviewer, or note';
            search.value = params.get('kingy_pg_overlay_search') || '';
            search.style.marginLeft = '8px';
            form.appendChild(search);

            var button = document.createElement('button');
            button.className = 'button';
            button.type = 'submit';
            button.textContent = 'Filter';
            form.appendChild(button);

            var clear = document.createElement('a');
            clear.className = 'button';
            clear.href = currentUrl({ kingy_pg_tab: 'review_overlay' });
            clear.textContent = 'Clear';
            clear.style.marginLeft = '4px';
            form.appendChild(clear);
            section.appendChild(form);

            var rows = overlayRows();
            appendText(section, 'p', 'Showing up to 100 of ' + rows.length + ' saved review records.');

            if (!rows.length) {
                appendText(section, 'p', 'No saved review metadata matches the current filters.');
                wrap.appendChild(section);
                return;
            }

            var table = document.createElement('table');
            table.className = 'widefat striped';
            var thead = document.createElement('thead');
            var hr = document.createElement('tr');
            ['Row Type', 'Source Row ID', 'Review State', 'Reviewer', 'Reviewed Timestamp', 'Reviewer Note', 'Source Artifact'].forEach(function (heading) {
                appendText(hr, 'th', heading);
            });
            thead.appendChild(hr);
            table.appendChild(thead);

            var tbody = document.createElement('tbody');
            rows.slice(0, 100).forEach(function (row) {
                var tr = document.createElement('tr');
                appendText(tr, 'td', row.row_type);
                appendText(tr, 'td', row.review_row_id);
                appendText(tr, 'td', row.review_state);
                appendText(tr, 'td', row.reviewer_display_name || row.reviewer_login);
                appendText(tr, 'td', row.reviewed_at_utc);
                appendText(tr, 'td', row.reviewer_notes);
                appendText(tr, 'td', row.source_artifact_id);
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            section.appendChild(table);
            wrap.appendChild(section);
        }

        function countTable(title, counts, parent) {
            appendText(parent, 'h3', title);
            var table = document.createElement('table');
            table.className = 'widefat striped';
            var tbody = document.createElement('tbody');
            Object.keys(counts || {}).forEach(function (key) {
                var tr = document.createElement('tr');
                appendText(tr, 'th', key.replace(/_/g, ' '));
                appendText(tr, 'td', counts[key]);
                tbody.appendChild(tr);
            });
            if (!tbody.children.length) {
                var empty = document.createElement('tr');
                appendText(empty, 'td', 'No data available.');
                tbody.appendChild(empty);
            }
            table.appendChild(tbody);
            parent.appendChild(table);
        }

        function healthRows() {
            var rows = config.healthData && Array.isArray(config.healthData.relationship_qa_queue)
                ? config.healthData.relationship_qa_queue.slice()
                : [];
            var params = new URLSearchParams(window.location.search);
            var wantedIssue = params.get('kingy_pg_health_issue_type') || '';
            var wantedNode = params.get('kingy_pg_health_node_type') || '';
            var wantedEdge = params.get('kingy_pg_health_edge_type') || '';
            var wantedState = params.get('kingy_pg_health_review_state') || '';
            var search = (params.get('kingy_pg_health_search') || '').toLowerCase();

            return rows.filter(function (row) {
                if (wantedIssue && row.issue_type !== wantedIssue) {
                    return false;
                }
                if (wantedNode && row.node_type !== wantedNode) {
                    return false;
                }
                if (wantedEdge && row.edge_type !== wantedEdge) {
                    return false;
                }
                if (wantedState && row.review_state !== wantedState) {
                    return false;
                }
                if (search) {
                    var haystack = [row.issue_type, row.row_id, row.title, row.detail].map(text).join(' ').toLowerCase();
                    if (haystack.indexOf(search) === -1) {
                        return false;
                    }
                }
                return true;
            });
        }

        function uniqueField(rows, field) {
            var seen = {};
            rows.forEach(function (row) {
                if (row[field]) {
                    seen[row[field]] = true;
                }
            });
            return Object.keys(seen).sort();
        }

        function appendSelect(form, name, values, selected, label) {
            var select = document.createElement('select');
            select.name = name;
            select.style.marginLeft = '8px';
            var any = document.createElement('option');
            any.value = '';
            any.textContent = label || 'Any';
            select.appendChild(any);
            values.forEach(function (value) {
                var option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (selected === value) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            form.appendChild(select);
        }

        function renderGraphHealthTab() {
            if (tab !== 'graph_health') {
                return;
            }

            var nav = document.querySelector('.nav-tab-wrapper');
            var wrap = document.querySelector('.kingy-ali-product-graph-review') || document.querySelector('.wrap');
            if (!nav || !wrap || document.getElementById('kingy-pg-graph-health-tab')) {
                return;
            }

            while (nav.nextSibling) {
                nav.parentNode.removeChild(nav.nextSibling);
            }

            var health = config.healthData || {};
            var metrics = health.metrics || {};
            var allRows = Array.isArray(health.relationship_qa_queue) ? health.relationship_qa_queue : [];
            var rows = healthRows();
            var params = new URLSearchParams(window.location.search);
            var section = document.createElement('div');
            section.id = 'kingy-pg-graph-health-tab';
            appendText(section, 'h2', 'Graph Health');
            appendText(section, 'p', 'Read-only relationship QA computed from the current Product Graph review datasets and reviewer overlay. No graph artifacts or WordPress content are changed from this tab.');

            countTable('Health Metrics', metrics, section);
            countTable('Node Types', health.node_kind_counts || {}, section);
            countTable('Edge Types', health.edge_type_counts || {}, section);
            countTable('Edge Confidence', health.edge_confidence_counts || {}, section);
            countTable('URL-backed Edge Resolution', health.url_backed_edge_status_counts || {}, section);
            countTable('Reviewer Overlay Counts', health.review_overlay_summary && health.review_overlay_summary.counts_by_state ? health.review_overlay_summary.counts_by_state : {}, section);

            appendText(section, 'h3', 'Graph Health Export');
            var exportList = document.createElement('ul');
            Object.keys(config.healthDownloadLinks || {}).forEach(function (format) {
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.href = config.healthDownloadLinks[format];
                a.textContent = 'Download graph health ' + format.toUpperCase();
                li.appendChild(a);
                exportList.appendChild(li);
            });
            section.appendChild(exportList);

            appendText(section, 'h3', 'Relationship QA Queue');
            appendText(section, 'p', 'Rows are advisory only. Use the review overlay to record editorial notes; this queue does not auto-fix edges, add links, or mutate graph source files.');

            var form = document.createElement('form');
            form.method = 'get';
            form.action = config.actionUrl.split('?')[0];
            form.style.margin = '16px 0';
            [['page', 'kingy-ali-product-graph'], ['kingy_pg_tab', 'graph_health']].forEach(function (pair) {
                addHidden(form, pair[0], pair[1]);
            });
            appendSelect(form, 'kingy_pg_health_issue_type', uniqueField(allRows, 'issue_type'), params.get('kingy_pg_health_issue_type') || '', 'Any issue type');
            appendSelect(form, 'kingy_pg_health_node_type', uniqueField(allRows, 'node_type'), params.get('kingy_pg_health_node_type') || '', 'Any node type');
            appendSelect(form, 'kingy_pg_health_edge_type', uniqueField(allRows, 'edge_type'), params.get('kingy_pg_health_edge_type') || '', 'Any edge type');
            appendSelect(form, 'kingy_pg_health_review_state', Object.keys(config.states || {}), params.get('kingy_pg_health_review_state') || '', 'Any review state');
            var search = document.createElement('input');
            search.type = 'search';
            search.name = 'kingy_pg_health_search';
            search.placeholder = 'Search issue, row, title, detail';
            search.value = params.get('kingy_pg_health_search') || '';
            search.style.marginLeft = '8px';
            form.appendChild(search);
            var button = document.createElement('button');
            button.className = 'button';
            button.type = 'submit';
            button.textContent = 'Filter';
            form.appendChild(button);
            var clear = document.createElement('a');
            clear.className = 'button';
            clear.href = currentUrl({ kingy_pg_tab: 'graph_health' });
            clear.textContent = 'Clear';
            clear.style.marginLeft = '4px';
            form.appendChild(clear);
            appendText(form, 'span', ' Showing up to 100 of ' + rows.length + ' filtered QA rows from ' + allRows.length + ' total.');
            section.appendChild(form);

            var table = document.createElement('table');
            table.className = 'widefat striped';
            var thead = document.createElement('thead');
            var hr = document.createElement('tr');
            ['Issue Type', 'Priority', 'Row Type', 'Row ID', 'Node Type', 'Edge Type', 'Review State', 'Title', 'Detail'].forEach(function (heading) {
                appendText(hr, 'th', heading);
            });
            thead.appendChild(hr);
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            rows.slice(0, 100).forEach(function (row) {
                var tr = document.createElement('tr');
                ['issue_type', 'priority', 'row_type', 'row_id', 'node_type', 'edge_type', 'review_state', 'title', 'detail'].forEach(function (field) {
                    appendText(tr, 'td', row[field]);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            section.appendChild(table);
            wrap.appendChild(section);
        }

        function addSummaryExportLinks() {
            if (tab !== 'summary' || !config.downloadLinks) {
                return;
            }
            var wrap = document.querySelector('.kingy-ali-product-graph-review') || document.querySelector('.wrap');
            if (!wrap || document.getElementById('kingy-pg-review-overlay-export')) {
                return;
            }
            var section = document.createElement('div');
            section.id = 'kingy-pg-review-overlay-export';
            section.innerHTML = '<h3>Review Overlay Export</h3><p>Reviewer metadata is stored separately from graph artifacts. Current overlay records: ' + Number(config.recordCount || 0) + '.</p>';
            var list = document.createElement('ul');
            Object.keys(config.downloadLinks).forEach(function (format) {
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.href = config.downloadLinks[format];
                a.textContent = 'Download review overlay ' + format.toUpperCase();
                li.appendChild(a);
                list.appendChild(li);
            });
            section.appendChild(list);
            wrap.appendChild(section);
        }

        addReviewOverlayNavTab();
        addReviewControls();
        addReviewOverlaySummary();
        renderReviewOverlayTab();
        renderGraphHealthTab();
        addSummaryExportLinks();
    })();
    </script>
    <?php
}

function kingy_ali_pg_review_overlay_download_links() {
    $links = array();
    foreach (array('json', 'csv') as $format) {
        $links[$format] = html_entity_decode(wp_nonce_url(
            add_query_arg(
                array(
                    'page' => 'kingy-ali-product-graph',
                    'kingy_pg_review_overlay_download' => $format,
                ),
                admin_url('admin.php')
            ),
            'kingy_ali_product_graph_review_overlay_download_' . $format
        ), ENT_QUOTES, 'UTF-8');
    }

    return $links;
}

function kingy_ali_pg_review_overlay_health_download_links() {
    $links = array();
    foreach (array('json', 'csv') as $format) {
        $links[$format] = html_entity_decode(wp_nonce_url(
            add_query_arg(
                array(
                    'page' => 'kingy-ali-product-graph',
                    'kingy_pg_health_export' => $format,
                ),
                admin_url('admin.php')
            ),
            'kingy_ali_product_graph_health_download_' . $format
        ), ENT_QUOTES, 'UTF-8');
    }

    return $links;
}

function kingy_ali_pg_review_overlay_flat_records() {
    $overlay = kingy_ali_pg_review_overlay_records();
    $rows = array();

    foreach ($overlay as $row_type => $records) {
        if (!is_array($records)) {
            continue;
        }
        foreach ($records as $row_id => $record) {
            if (!is_array($record)) {
                continue;
            }
            $rows[] = array(
                'row_type' => sanitize_key(isset($record['row_type']) ? $record['row_type'] : $row_type),
                'review_row_id' => kingy_ali_pg_review_overlay_row_id(isset($record['review_row_id']) ? $record['review_row_id'] : $row_id),
                'review_state' => sanitize_key(isset($record['review_state']) ? $record['review_state'] : 'unreviewed'),
                'reviewer_notes' => kingy_ali_pg_review_overlay_notes(isset($record['reviewer_notes']) ? $record['reviewer_notes'] : ''),
                'reviewer_user_id' => absint(isset($record['reviewer_user_id']) ? $record['reviewer_user_id'] : 0),
                'reviewer_login' => sanitize_user(isset($record['reviewer_login']) ? $record['reviewer_login'] : '', true),
                'reviewer_display_name' => sanitize_text_field(isset($record['reviewer_display_name']) ? $record['reviewer_display_name'] : ''),
                'reviewed_at_utc' => sanitize_text_field(isset($record['reviewed_at_utc']) ? $record['reviewed_at_utc'] : ''),
                'source_artifact_id' => sanitize_file_name(isset($record['source_artifact_id']) ? $record['source_artifact_id'] : ''),
            );
        }
    }

    return $rows;
}

function kingy_ali_pg_review_overlay_summary($rows = null) {
    if (!is_array($rows)) {
        $rows = kingy_ali_pg_review_overlay_flat_records();
    }

    $counts_by_state = array();
    foreach (kingy_ali_pg_review_overlay_states() as $state => $label) {
        $counts_by_state[$state] = 0;
    }

    $counts_by_type = array();
    foreach (kingy_ali_pg_review_overlay_tab_types() as $row_type) {
        $counts_by_type[$row_type] = 0;
    }

    $latest_reviewed_at = '';
    foreach ($rows as $row) {
        $state = isset($row['review_state']) && isset($counts_by_state[$row['review_state']]) ? $row['review_state'] : 'unreviewed';
        $row_type = isset($row['row_type']) ? sanitize_key($row['row_type']) : '';
        $reviewed_at = isset($row['reviewed_at_utc']) ? sanitize_text_field($row['reviewed_at_utc']) : '';

        $counts_by_state[$state]++;
        if ($row_type !== '') {
            if (!isset($counts_by_type[$row_type])) {
                $counts_by_type[$row_type] = 0;
            }
            $counts_by_type[$row_type]++;
        }
        if ($reviewed_at !== '' && ($latest_reviewed_at === '' || strcmp($reviewed_at, $latest_reviewed_at) > 0)) {
            $latest_reviewed_at = $reviewed_at;
        }
    }

    return array(
        'total_saved_review_records' => count($rows),
        'latest_reviewed_at_utc' => $latest_reviewed_at,
        'counts_by_state' => $counts_by_state,
        'counts_by_row_type' => $counts_by_type,
    );
}

function kingy_ali_pg_review_overlay_health_queue_row($issue_type, $priority, $row_type, $row_id, $node_type, $edge_type, $review_state, $title, $detail) {
    return array(
        'issue_type' => sanitize_key($issue_type),
        'priority' => sanitize_key($priority),
        'row_type' => sanitize_key($row_type),
        'row_id' => kingy_ali_pg_review_overlay_row_id($row_id),
        'node_type' => sanitize_key($node_type),
        'edge_type' => sanitize_key($edge_type),
        'review_state' => sanitize_key($review_state),
        'title' => sanitize_text_field((string) $title),
        'detail' => sanitize_text_field((string) $detail),
    );
}

function kingy_ali_pg_review_overlay_health_data() {
    $nodes = kingy_ali_pg_review_overlay_read_dataset('nodes');
    $edges = kingy_ali_pg_review_overlay_read_dataset('edges');
    $resolver = kingy_ali_pg_review_overlay_read_dataset('resolver');
    $unresolved_queue = kingy_ali_pg_review_overlay_read_dataset('unresolved_queue');
    $model_inventory = kingy_ali_pg_review_overlay_read_dataset('model_inventory');
    $overlay_rows = kingy_ali_pg_review_overlay_flat_records();

    $node_keys = array();
    $source_keys = array();
    $target_keys = array();
    $node_kind_counts = array();
    $edge_type_counts = array();
    $edge_confidence_counts = array();
    $edge_status_counts = array();
    $url_backed_edge_status_counts = array();
    $edge_signatures = array();
    $missing_source_edges = array();
    $missing_target_edges = array();
    $duplicate_edge_candidates = array();
    $orphan_nodes = array();
    $nodes_without_outgoing = array();
    $relationship_queue = array();

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_pg_review_overlay_row_value($node, 'node_key');
        if ($node_key !== '') {
            $node_keys[$node_key] = $node;
        }
        kingy_ali_pg_review_overlay_count($node_kind_counts, kingy_ali_pg_review_overlay_row_value($node, 'node_kind'));
    }

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        $source = kingy_ali_pg_review_overlay_row_value($edge, 'source');
        $target = kingy_ali_pg_review_overlay_row_value($edge, 'target');
        $edge_type = kingy_ali_pg_review_overlay_row_value($edge, 'edge_type');
        $confidence = kingy_ali_pg_review_overlay_row_value($edge, 'confidence_class', kingy_ali_pg_review_overlay_row_value($edge, 'confidence'));

        kingy_ali_pg_review_overlay_count($edge_type_counts, $edge_type);
        kingy_ali_pg_review_overlay_count($edge_confidence_counts, $confidence);
        kingy_ali_pg_review_overlay_count($edge_status_counts, kingy_ali_pg_review_overlay_row_value($edge, 'status'));

        if ($source !== '') {
            $source_keys[$source] = true;
            if (!isset($node_keys[$source])) {
                $missing_source_edges[] = $edge;
            }
        }
        if ($target !== '') {
            $target_keys[$target] = true;
            if (!isset($node_keys[$target])) {
                $missing_target_edges[] = $edge;
            }
        }

        $signature = implode('|', array($source, $target, $edge_type, kingy_ali_pg_review_overlay_row_value($edge, 'field'), kingy_ali_pg_review_overlay_row_value($edge, 'value')));
        if (!isset($edge_signatures[$signature])) {
            $edge_signatures[$signature] = array();
        }
        $edge_signatures[$signature][] = $edge;

        if (strtolower($confidence) === 'url-backed' || stripos($edge_type, 'related_') === 0 || kingy_ali_pg_review_overlay_row_value($edge, 'canonical_resolution_status') !== '') {
            $resolution = kingy_ali_pg_review_overlay_row_value($edge, 'canonical_resolution_status');
            if ($resolution === '') {
                $resolution = kingy_ali_pg_review_overlay_row_value($edge, 'target_url') !== '' ? 'url_present_no_canonical_status' : 'missing_url_resolution';
            }
            kingy_ali_pg_review_overlay_count($url_backed_edge_status_counts, $resolution);
        }
    }

    foreach ($edge_signatures as $signature => $signature_edges) {
        if (count($signature_edges) > 1) {
            $duplicate_edge_candidates[] = array('signature' => $signature, 'count' => count($signature_edges), 'edges' => $signature_edges);
        }
    }

    foreach ($node_keys as $node_key => $node) {
        if (!isset($source_keys[$node_key]) && !isset($target_keys[$node_key])) {
            $orphan_nodes[] = $node;
        }
        if (!isset($source_keys[$node_key])) {
            $nodes_without_outgoing[] = $node;
        }
    }

    foreach (array_slice($missing_source_edges, 0, 100) as $edge) {
        $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('missing_source_node', 'high', 'edge', kingy_ali_pg_review_overlay_row_value($edge, 'review_row_id', kingy_ali_pg_review_overlay_row_value($edge, 'edge_id')), kingy_ali_pg_review_overlay_row_value($edge, 'source_node_kind'), kingy_ali_pg_review_overlay_row_value($edge, 'edge_type'), kingy_ali_pg_review_overlay_row_value($edge, 'review_state', 'unreviewed'), kingy_ali_pg_review_overlay_row_value($edge, 'source_title'), 'Edge source is not present in the loaded node map.');
    }
    foreach (array_slice($missing_target_edges, 0, 100) as $edge) {
        $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('missing_target_node', 'high', 'edge', kingy_ali_pg_review_overlay_row_value($edge, 'review_row_id', kingy_ali_pg_review_overlay_row_value($edge, 'edge_id')), kingy_ali_pg_review_overlay_row_value($edge, 'target_node_kind'), kingy_ali_pg_review_overlay_row_value($edge, 'edge_type'), kingy_ali_pg_review_overlay_row_value($edge, 'review_state', 'unreviewed'), kingy_ali_pg_review_overlay_row_value($edge, 'target_title'), 'Edge target is not present in the loaded node map.');
    }
    foreach (array_slice($duplicate_edge_candidates, 0, 100) as $candidate) {
        $first_edge = isset($candidate['edges'][0]) && is_array($candidate['edges'][0]) ? $candidate['edges'][0] : array();
        $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('duplicate_edge_candidate', 'medium', 'edge', kingy_ali_pg_review_overlay_row_value($first_edge, 'review_row_id', kingy_ali_pg_review_overlay_row_value($first_edge, 'edge_id')), kingy_ali_pg_review_overlay_row_value($first_edge, 'source_node_kind'), kingy_ali_pg_review_overlay_row_value($first_edge, 'edge_type'), kingy_ali_pg_review_overlay_row_value($first_edge, 'review_state', 'unreviewed'), kingy_ali_pg_review_overlay_row_value($first_edge, 'source_title'), sprintf('Duplicate signature appears %d times.', absint($candidate['count'])));
    }
    foreach (array_slice($orphan_nodes, 0, 100) as $node) {
        $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('orphan_node', 'medium', 'node', kingy_ali_pg_review_overlay_row_value($node, 'review_row_id', kingy_ali_pg_review_overlay_row_value($node, 'node_key')), kingy_ali_pg_review_overlay_row_value($node, 'node_kind'), '', kingy_ali_pg_review_overlay_row_value($node, 'review_state', 'unreviewed'), kingy_ali_pg_review_overlay_row_value($node, 'title'), 'Node has no incoming or outgoing loaded graph edges.');
    }
    foreach (array_slice($nodes_without_outgoing, 0, 100) as $node) {
        $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('no_useful_outgoing_edges', 'low', 'node', kingy_ali_pg_review_overlay_row_value($node, 'review_row_id', kingy_ali_pg_review_overlay_row_value($node, 'node_key')), kingy_ali_pg_review_overlay_row_value($node, 'node_kind'), '', kingy_ali_pg_review_overlay_row_value($node, 'review_state', 'unreviewed'), kingy_ali_pg_review_overlay_row_value($node, 'title'), 'Node has no outgoing loaded graph edges.');
    }
    foreach (array_slice($unresolved_queue, 0, 100) as $row) {
        if (is_array($row)) {
            $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('unresolved_url_backed_edge', 'medium', 'unresolved_queue', kingy_ali_pg_review_overlay_row_value($row, 'review_row_id', kingy_ali_pg_review_overlay_row_value($row, 'normalized_url')), kingy_ali_pg_review_overlay_row_value($row, 'node_kind', 'internal_url'), '', kingy_ali_pg_review_overlay_row_value($row, 'review_state', 'unreviewed'), kingy_ali_pg_review_overlay_row_value($row, 'title', kingy_ali_pg_review_overlay_row_value($row, 'normalized_url')), kingy_ali_pg_review_overlay_row_value($row, 'review_note', 'Internal URL remains review-only.'));
        }
    }

    $model_inventory_status = isset($model_inventory[0]) && is_array($model_inventory[0]) ? kingy_ali_pg_review_overlay_row_value($model_inventory[0], 'status', 'unknown') : 'unknown';
    if ($model_inventory_status === 'model_inventory_gap') {
        $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('model_inventory_blocker', 'high', 'model_inventory', 'model-inventory:kingy_ai_model', 'kingy_ai_model', '', 'model_inventory_blocked', 'kingy_ai_model inventory', 'No loaded kingy_ai_model records are available in the current graph review dataset.');
    }
    foreach ($overlay_rows as $row) {
        $state = isset($row['review_state']) ? $row['review_state'] : '';
        if (in_array($state, array('needs_source', 'needs_refresh', 'needs_canonical_review'), true)) {
            $relationship_queue[] = kingy_ali_pg_review_overlay_health_queue_row('review_state_followup', 'medium', isset($row['row_type']) ? $row['row_type'] : '', isset($row['review_row_id']) ? $row['review_row_id'] : '', '', '', $state, isset($row['source_artifact_id']) ? $row['source_artifact_id'] : '', isset($row['reviewer_notes']) ? $row['reviewer_notes'] : '');
        }
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'metrics' => array(
            'total_nodes' => count($nodes),
            'total_edges' => count($edges),
            'total_resolved_urls' => count($resolver),
            'unresolved_queue_count' => count($unresolved_queue),
            'orphan_nodes' => count($orphan_nodes),
            'missing_source_nodes' => count($missing_source_edges),
            'missing_target_nodes' => count($missing_target_edges),
            'duplicate_edge_groups' => count($duplicate_edge_candidates),
            'nodes_without_useful_outgoing_edges' => count($nodes_without_outgoing),
            'model_inventory_status' => $model_inventory_status,
        ),
        'node_kind_counts' => $node_kind_counts,
        'edge_type_counts' => $edge_type_counts,
        'edge_confidence_counts' => $edge_confidence_counts,
        'edge_status_counts' => $edge_status_counts,
        'url_backed_edge_status_counts' => $url_backed_edge_status_counts,
        'review_overlay_summary' => kingy_ali_pg_review_overlay_summary($overlay_rows),
        'relationship_qa_queue' => $relationship_queue,
    );
}

function kingy_ali_pg_review_overlay_tab_for_type($row_type) {
    $tabs = kingy_ali_pg_review_overlay_tab_types();
    foreach ($tabs as $tab => $type) {
        if ($type === $row_type) {
            return $tab;
        }
    }

    return '';
}

function kingy_ali_pg_review_overlay_row_exists($tab, $row_id) {
    $files = kingy_ali_pg_review_overlay_dataset_files();
    if (!isset($files[$tab])) {
        return false;
    }

    $path = trailingslashit(kingy_ali_pg_review_overlay_reports_dir()) . $files[$tab];
    $base = realpath(kingy_ali_pg_review_overlay_reports_dir());
    $real = realpath($path);
    if (!$base || !$real || strpos($real, trailingslashit($base)) !== 0 || !is_file($real)) {
        return false;
    }

    $decoded = json_decode((string) file_get_contents($real), true);
    if (!is_array($decoded)) {
        return false;
    }

    foreach ($decoded as $row) {
        if (is_array($row) && isset($row['review_row_id']) && kingy_ali_pg_review_overlay_row_id($row['review_row_id']) === $row_id) {
            return true;
        }
    }

    return false;
}

function kingy_ali_pg_review_overlay_redirect($tab, $saved) {
    wp_safe_redirect(
        add_query_arg(
            array(
                'page' => 'kingy-ali-product-graph',
                'kingy_pg_tab' => sanitize_key($tab),
                'kingy_pg_review_saved' => $saved ? '1' : '0',
            ),
            admin_url('admin.php')
        )
    );
    exit;
}
