<?php
if (!defined('ABSPATH')) { exit; }

function kau_table($name) { global $wpdb; return $wpdb->prefix . 'kau_' . $name; }
function kau_now() { return gmdate('Y-m-d\TH:i:s\Z'); }

function kau_migrate() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $collate = $wpdb->get_charset_collate();
    $definitions = array(
        'changes' => "id bigint unsigned NOT NULL AUTO_INCREMENT,
            event_key varchar(150) NOT NULL,
            product_id varchar(80) NOT NULL,
            payload longtext NOT NULL,
            verified_at varchar(20) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_key (event_key),
            KEY product_id (product_id)",
        'jobs' => "id bigint unsigned NOT NULL AUTO_INCREMENT,
            slot_key varchar(191) NOT NULL,
            name varchar(80) NOT NULL,
            status varchar(32) NOT NULL,
            attempts int NOT NULL DEFAULT 0,
            next_attempt bigint NOT NULL DEFAULT 0,
            started_at varchar(20) NOT NULL,
            completed_at varchar(20) NOT NULL DEFAULT '',
            detail longtext NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slot_key (slot_key)",
        'locks' => "name varchar(80) NOT NULL,
            token varchar(64) NOT NULL,
            expires bigint NOT NULL,
            PRIMARY KEY  (name)",
        'exceptions' => "id bigint unsigned NOT NULL AUTO_INCREMENT,
            item_key varchar(191) NOT NULL,
            stream varchar(80) NOT NULL,
            reason text NOT NULL,
            last_seen varchar(20) NOT NULL,
            occurrences int NOT NULL DEFAULT 1,
            resolved tinyint NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY item_key (item_key)",
        'projects' => "id varchar(64) NOT NULL,
            owner_id bigint unsigned NOT NULL,
            payload longtext NOT NULL,
            revision int NOT NULL DEFAULT 1,
            updated_at varchar(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY owner_id (owner_id)",
        'outbox' => "id bigint unsigned NOT NULL AUTO_INCREMENT,
            delivery_key varchar(191) NOT NULL,
            recipient_ref varchar(191) NOT NULL,
            stream varchar(32) NOT NULL,
            expires_at varchar(20) NOT NULL,
            payload longtext NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'queued',
            provider_id varchar(191) NOT NULL DEFAULT '',
            updated_at varchar(20) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY delivery_key (delivery_key)"
    );
    foreach ($definitions as $name => $definition) {
        dbDelta('CREATE TABLE ' . kau_table($name) . " (\n" . $definition . "\n) ENGINE=InnoDB $collate;");
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like(kau_table($name)))) !== kau_table($name)) {
            throw new RuntimeException('Additive table creation failed: ' . $name);
        }
    }
    update_option('kau_schema_version', '1', false);
}

function kau_exception($stream, $key, $reason) {
    global $wpdb;
    // Do not put provider errors, emails, credentials, payloads or personal data in this queue.
    $wpdb->query($wpdb->prepare('INSERT INTO ' . kau_table('exceptions') .
        ' (item_key,stream,reason,last_seen) VALUES (%s,%s,%s,%s) ON DUPLICATE KEY UPDATE reason=VALUES(reason), last_seen=VALUES(last_seen), occurrences=occurrences+1, resolved=0',
        substr($stream . ':' . hash('sha256', $key), 0, 191), $stream, substr($reason, 0, 1000), kau_now()));
}

function kau_lock($name, $ttl = 300) {
    global $wpdb;
    $token = bin2hex(random_bytes(24)); $now = time();
    $wpdb->query($wpdb->prepare('INSERT INTO ' . kau_table('locks') .
        ' (name,token,expires) VALUES (%s,%s,%d) ON DUPLICATE KEY UPDATE token=IF(expires<%d,VALUES(token),token), expires=IF(expires<%d,VALUES(expires),expires)',
        $name, $token, $now + $ttl, $now, $now));
    return $wpdb->get_var($wpdb->prepare('SELECT token FROM ' . kau_table('locks') . ' WHERE name=%s', $name)) === $token ? $token : false;
}
function kau_unlock($name, $token) {
    global $wpdb;
    $wpdb->query($wpdb->prepare('DELETE FROM ' . kau_table('locks') . ' WHERE name=%s AND token=%s', $name, $token));
}

/** Verify without mutating canonical facts, suitable for test-mode source checking. */
function kau_check_canonical_change($input) {
    $change = kau_validate_change($input); $product = kau_product_id($change['product_id']);
    if (get_post_type($product['id']) !== $product['type'] || get_post_status($product['id']) !== 'publish') {
        throw new InvalidArgumentException('Product must already exist and be public.');
    }
    $verification = apply_filters('kau_verify_canonical_change', null, $change);
    if (!is_array($verification) || ($verification['supported'] ?? false) !== true || ($verification['official'] ?? false) !== true
        || ($verification['source_id'] ?? '') !== $change['source_id'] || ($verification['source_sha256'] ?? '') !== $change['source_sha256']
        || ($verification['product_id'] ?? '') !== $change['product_id']) {
        throw new RuntimeException('Canonical evidence check failed.');
    }
    return $change;
}

/** The existing source engine owns verification. This table is a material-change projection. */
function kau_admit_change($input) {
    global $wpdb;
    try{$change=kau_check_canonical_change($input);}catch(Throwable $e){kau_exception('changes',is_array($input)?($input['event_key']??'malformed'):'malformed','Canonical source association, retained quotation or verification is unavailable.');throw $e;}
    $product=kau_product_id($change['product_id']);
    $token = kau_lock('product-' . $product['id']);
    if (!$token) { throw new RuntimeException('Product is locked; retry later.'); }
    try {
        $existing = $wpdb->get_var($wpdb->prepare('SELECT payload FROM ' . kau_table('changes') . ' WHERE event_key=%s', $change['event_key']));
        $payload = wp_json_encode($change);
        if ($existing) {
            if ($existing !== $payload) { kau_exception('changes', $change['event_key'], 'Conflicting evidence for an existing event; retained event was not replaced.'); throw new RuntimeException('Event conflict.'); }
            return array('created' => false, 'event_key' => $change['event_key']);
        }
        $wpdb->query('START TRANSACTION');
        if (!$wpdb->insert(kau_table('changes'), array('event_key' => $change['event_key'], 'product_id' => $change['product_id'], 'payload' => $payload, 'verified_at' => $change['verified_at']))) {
            throw new RuntimeException('Change could not be stored.');
        }
        // Read all surfaces from this same record; never rewrite historical companion snapshots.
        if ($wpdb->query('COMMIT') === false) { throw new RuntimeException('Commit failed.'); }
        do_action('kau_verified_product_change', $change);
        return array('created' => true, 'event_key' => $change['event_key']);
    } catch (Throwable $e) { $wpdb->query('ROLLBACK'); throw $e; }
    finally { kau_unlock('product-' . $product['id'], $token); }
}

function kau_changes($product_ids = array(), $limit = 100) {
    global $wpdb;
    $sql = 'SELECT payload FROM ' . kau_table('changes'); $params = array();
    if ($product_ids) {
        foreach ($product_ids as $id) { kau_product_id($id); }
        $sql .= ' WHERE product_id IN (' . implode(',', array_fill(0, count($product_ids), '%s')) . ')'; $params = $product_ids;
    }
    $sql .= ' ORDER BY verified_at DESC,id DESC LIMIT %d'; $params[] = min(100, max(1, (int) $limit));
    return array_values(array_filter(array_map(function ($row) { return json_decode($row, true); }, $wpdb->get_col($wpdb->prepare($sql, $params)))));
}

function kau_catalog($search = '', $identities = array()) {
    $query = array('post_type' => array('kingy_ai_tool','kingy_ai_model'), 'post_status' => 'publish', 'posts_per_page' => 100, 's' => $search, 'orderby' => 'title', 'order' => 'ASC');
    if ($identities) { $query['post__in'] = array_map(function ($id) { return kau_product_id($id)['id']; }, $identities); }
    $posts = get_posts($query);
    $out = array();
    foreach ($posts as $post) {
        $id = 'wp:' . $post->post_type . ':' . $post->ID; $price = null;
        foreach (kau_changes(array($id), 100) as $event) {
            if ($event['kind'] === 'price') { $price = array_merge($event['price'], array('source_url' => $event['source_url'], 'verified_at' => $event['verified_at'], 'event_key' => $event['event_key'])); break; }
        }
        $out[] = array('id' => $id, 'name' => get_the_title($post), 'url' => get_permalink($post), 'price' => $price,
            'coverage' => apply_filters('kau_product_monitoring_coverage', array('status' => 'unconfirmed', 'verified_at' => null), $id));
    }
    return $out;
}

function kau_operations_status() {
    global $wpdb;
    $ready = get_option('kau_schema_version') === '1';
    return array('version' => KAU_VERSION, 'enabled' => get_option('kau_feature_flags', array()), 'schema_ready' => $ready,
        'timezone' => 'America/Vancouver', 'observed_at' => kau_now(),
        'jobs' => $ready ? $wpdb->get_results('SELECT name,status,attempts,started_at,completed_at,detail FROM ' . kau_table('jobs') . ' ORDER BY id DESC LIMIT 50', ARRAY_A) : array(),
        'exceptions' => $ready ? $wpdb->get_results('SELECT stream,reason,last_seen,occurrences FROM ' . kau_table('exceptions') . ' WHERE resolved=0 ORDER BY id DESC LIMIT 50', ARRAY_A) : array(),
        'email_counts' => $ready ? $wpdb->get_results('SELECT stream,status,COUNT(*) AS count FROM ' . kau_table('outbox') . ' GROUP BY stream,status', ARRAY_A) : array(),
        'scheduler_registration' => get_option('kau_scheduler_registration', 'not_installed'),
        'analytics_baseline' => get_option('kau_analytics_baseline', 'baseline unavailable'));
}
