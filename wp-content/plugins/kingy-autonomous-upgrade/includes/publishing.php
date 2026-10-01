<?php
if (!defined('ABSPATH')) { exit; }

function kau_parse_youtube_feed($body, $channel_id) {
    if (!preg_match('/^UC[A-Za-z0-9_-]{22}$/', $channel_id) || !is_string($body) || strlen($body) > 2000000 || stripos($body, '<!DOCTYPE') !== false) {
        throw new InvalidArgumentException('Invalid official feed or channel identity.');
    }
    $prior = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET);
        if (!$xml) { throw new InvalidArgumentException('Malformed feed.'); }
        $atom = $xml->children('http://www.w3.org/2005/Atom');
        $youtube = $xml->children('http://www.youtube.com/xml/schemas/2015');
        if ((string) $youtube->channelId !== $channel_id) { throw new InvalidArgumentException('Channel identity mismatch.'); }
        $rows = array();
        foreach ($atom->entry as $entry) {
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            $id = (string) $yt->videoId;
            if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id) || (string) $yt->channelId !== $channel_id) { throw new InvalidArgumentException('Video identity mismatch.'); }
            $date = new DateTimeImmutable((string) $entry->published);
            $published = $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
            if ($published > kau_now()) { throw new InvalidArgumentException('Future publication timestamp.'); }
            $rows[$id] = array('video_id' => $id, 'channel_id' => $channel_id, 'published_at' => $published, 'title' => (string) $entry->title);
        }
        $rows = array_values($rows);
        usort($rows, function ($a, $b) { return strcmp($b['published_at'], $a['published_at']) ?: strcmp($a['video_id'], $b['video_id']); });
        return $rows;
    } finally { libxml_clear_errors(); libxml_use_internal_errors($prior); }
}

function kau_publish_companion($input) {
    global $wpdb;
    $companion = kau_validate_companion($input);
    $identity = get_option('kau_verified_channel', array());
    if (($identity['id'] ?? '') !== $companion['channel_id'] || empty($identity['evidence_reference'])) { throw new RuntimeException('Official channel identity not established.'); }
    if (apply_filters('kau_companion_evidence_check', false, $companion) !== true || apply_filters('kau_companion_technical_check', false, $companion) !== true) {
        kau_exception('companions', $companion['video_id'], 'Companion evidence or rendered technical checks did not pass.');
        throw new RuntimeException('Companion checks required.');
    }
    $lock_name = 'video-' . $companion['video_id']; $token = kau_lock($lock_name);
    if (!$token) { throw new RuntimeException('Companion locked.'); }
    $baseline = null; $baseline_meta = array(); $post_id = 0;
    try {
        $posts = get_posts(array('post_type' => 'kingy_video', 'post_status' => array('publish','draft','pending','private'), 'posts_per_page' => 2,
            'meta_key' => '_kingy_youtube_video_id', 'meta_value' => $companion['video_id']));
        if (count($posts) > 1) { kau_exception('companions', $companion['video_id'], 'Duplicate existing platform IDs require reconciliation.'); throw new RuntimeException('Duplicate companions.'); }
        $body = kau_companion_content($companion);
        $post_id = $posts ? $posts[0]->ID : 0;
        $baseline = $posts ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID=%d",$post_id),ARRAY_A) : null;
        $baseline_meta = $posts ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id=%d",$post_id),ARRAY_A) : array();
        if ($posts) {
            $old = $posts[0]->post_content;
            $pattern = '/<!-- kau-evidence:start -->.*?<!-- kau-evidence:end -->/s';
            $body = preg_match($pattern, $old) ? preg_replace_callback($pattern, function () use ($body) { return $body; }, $old) : $old . "\n" . $body;
        }
        $post = array('post_type' => 'kingy_video', 'post_title' => $companion['title'], 'post_content' => $body, 'post_excerpt' => $companion['summary'], 'post_status' => 'draft');
        if ($post_id) { $post['ID'] = $post_id; }
        $post_id = wp_insert_post($post, true);
        if (is_wp_error($post_id)) { throw new RuntimeException('Companion write failed.'); }
        update_post_meta($post_id, '_kingy_youtube_video_id', $companion['video_id']);
        update_post_meta($post_id, '_kingy_video_publish_date', substr($companion['published_at'], 0, 10));
        update_post_meta($post_id, '_kau_companion_evidence', $companion);
        update_post_meta($post_id, '_kingy_commercial_status', $companion['commercial_status']);
        update_post_meta($post_id, '_kingy_sponsored', in_array($companion['commercial_status'], array('sponsored','mixed'), true));
        $tools = array();
        foreach ($companion['product_ids'] as $identity) {
            $product = kau_product_id($identity);
            if ($product['type'] === 'kingy_ai_tool' && get_post_type($product['id']) === $product['type'] && get_post_status($product['id']) === 'publish') { $tools[] = $product['id']; }
        }
        if (function_exists('kingy_ali_companion_update_tool_relationships')) { kingy_ali_companion_update_tool_relationships($post_id, $tools); }
        // Maintain the existing gates; the technical adapter has to provide the actual rendered QA evidence.
        update_post_meta($post_id, '_kingy_image_qa_approved', true);
        update_post_meta($post_id, '_kingy_editorial_qa_approved', true);
        $result = wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'), true);
        if (is_wp_error($result) || get_post_status($post_id) !== 'publish') { throw new RuntimeException('Existing publication gate held draft.'); }
        return array('id' => $post_id, 'url' => get_permalink($post_id), 'video_id' => $companion['video_id']);
    } catch (Throwable $e) {
        if ($baseline && $post_id) {
            // Restore the existing record on every failure after a write, including a throwing hook.
            unset($baseline['ID']); $wpdb->query('START TRANSACTION');
            $restored = $wpdb->update($wpdb->posts,$baseline,array('ID'=>$post_id)) !== false;
            $restored = $wpdb->delete($wpdb->postmeta,array('post_id'=>$post_id)) !== false && $restored;
            foreach ($baseline_meta as $meta) { unset($meta['meta_id']); $restored = $wpdb->insert($wpdb->postmeta,$meta) !== false && $restored; }
            $wpdb->query($restored ? 'COMMIT' : 'ROLLBACK'); clean_post_cache($post_id);
            if (!$restored) { kau_exception('companions',$companion['video_id'],'Record rollback failed; restore the checkpoint before retrying.'); }
        }
        kau_exception('companions',$companion['video_id'],'Companion write or publication gate failed; existing record recovery attempted when present.');
        throw $e;
    } finally { kau_unlock($lock_name, $token); }
}

function kau_companion_content($c) {
    $html = '<!-- kau-evidence:start --><section class="kau-companion"><h2>Use this video</h2><p>' . esc_html($c['summary']) . '</p><h3>Who it suits</h3><p>' . esc_html($c['suits']) . '</p><h3>Chapters</h3><ul>';
    foreach ($c['chapters'] as $chapter) {
        $html .= '<li><a href="' . esc_url('https://www.youtube.com/watch?v=' . $c['video_id'] . '&t=' . $chapter['seconds'] . 's') . '">' . esc_html(sprintf('%d:%02d', floor($chapter['seconds']/60), $chapter['seconds']%60) . ' ' . $chapter['title']) . '</a></li>';
    }
    $html .= '</ul><h3>Curtis’s recorded verdict</h3><p>' . esc_html($c['verdict']) . '</p><h3>Results and limits</h3><p>' . esc_html($c['limitations']) . '</p><h3>Prompts and settings</h3><pre>' . esc_html($c['prompts']) . '</pre><h3>Commercial disclosure</h3><p>' . esc_html($c['disclosure']) . '</p>';
    if ($c['assets']) {
        $html .= '<h3>Source assets</h3><ul>';
        foreach ($c['assets'] as $asset) { $html .= '<li><a href="' . esc_url($asset['url']) . '">' . esc_html($asset['label']) . '</a></li>'; }
        $html .= '</ul>';
    } else { $html .= '<p>No authorized downloadable source assets were retained for this video.</p>'; }
    return $html . '<p><a href="/make-this/">Plan a connected workflow</a></p></section><!-- kau-evidence:end -->';
}

function kau_render_email($edition, $unsubscribe_url, $preferences_url) {
    kau_url($unsubscribe_url); kau_url($preferences_url);
    if (!is_array($edition) || empty($edition['archive_url'])) { throw new InvalidArgumentException('Published archive URL required.'); }
    kau_url($edition['archive_url']);
    $html = '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html($edition['title']) . '</title><body style="margin:0;background:#f4f3ef;color:#1d2621;font-family:Arial,sans-serif"><main style="max-width:640px;margin:auto;padding:24px"><h1>' . esc_html($edition['title']) . '</h1><p>' . esc_html($edition['date']) . '</p>';
    foreach (($edition['sections'] ?? array()) as $section) {
        $html .= '<h2>' . esc_html($section['title']) . '</h2>';
        foreach ($section['items'] as $item) {
            kau_url($item['url']);
            $html .= '<h3><a href="' . esc_url($item['url']) . '">' . esc_html($item['title']) . '</a></h3><p>' . esc_html($item['summary']) . '</p>';
            if (!empty($item['source_url'])) { $html .= '<p><a href="' . esc_url(kau_url($item['source_url'])) . '">Primary source</a> · Evidence ' . esc_html($item['evidence_date']) . '</p>'; }
        }
    }
    return $html . '<p><a href="' . esc_url($edition['archive_url']) . '">Read on Kingy.ai</a></p><footer><p><a href="' . esc_url($preferences_url) . '">Email preferences</a> · <a href="' . esc_url($unsubscribe_url) . '">Unsubscribe</a></p><p>Kingy AI · ' . esc_html(apply_filters('kau_mail_legal_address', 'Postal address must be supplied by the established provider.')) . '</p></footer></main></body></html>';
}

function kau_build_brief($changes, $extras, $date) {
    $sections = array(); $items = array();
    foreach (array_slice($changes, 0, 3) as $change) {
        $change = kau_validate_change($change);
        $items[] = array('title' => $change['title'], 'summary' => $change['summary'], 'url' => $change['guide_url'] ?: $change['source_url'], 'source_url' => $change['source_url'], 'evidence_date' => $change['verified_at']);
    }
    if ($items) { $sections[] = array('title' => 'Verified developments', 'items' => $items); }
    foreach (array('test' => 'An actual Kingy test','workflow' => 'A workflow to use','stack' => 'A relevant stack change') as $key => $label) {
        if (!empty($extras[$key]) && !empty($extras[$key]['evidence_reference'])) {
            $item = $extras[$key]; $item['summary'] = ($key === 'test' ? 'Test date: ' . kau_text($item['test_date'] ?? '', 40) . '. ' : '') . kau_text($item['summary'] ?? '', 3000);
            $sections[] = array('title' => $label, 'items' => array($item));
        }
    }
    return array('title' => 'The Kingy Brief', 'date' => $date, 'sections' => $sections);
}
