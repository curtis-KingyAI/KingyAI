<?php
if (!defined('ABSPATH')) { exit; }

/** Call inside the maintained sponsor form; this creates no second inquiry route. */
function kau_sponsor_detail_fields() {
    return '<fieldset class="kau-sponsor-details"><legend>Campaign details (optional)</legend><label for="kau-sponsor-format">Requested format</label><select name="kau_campaign_format" id="kau-sponsor-format"><option value="">Discuss a recommendation</option><option value="dedicated">Dedicated product story</option><option value="launch">Connected launch package</option><option value="category">Category authority campaign</option></select><label for="kau-sponsor-budget">Budget</label><input id="kau-sponsor-budget" name="kau_budget" type="number" min="0" max="1000000" step="0.01"><label for="kau-sponsor-currency">Currency</label><select id="kau-sponsor-currency" name="kau_currency"><option>USD</option><option>CAD</option><option>EUR</option><option>GBP</option></select><label for="kau-sponsor-rights">Usage-right needs</label><textarea id="kau-sponsor-rights" name="kau_rights" maxlength="2000" placeholder="For example: organic reposting, paid media, clips, geography and duration."></textarea></fieldset>';
}

function kau_validate_sponsor_details($input) {
    if (!is_array($input)) { throw new InvalidArgumentException('Invalid sponsor request.'); }
    $out = array('format' => kau_text($input['kau_campaign_format'] ?? '', 20), 'currency' => kau_text($input['kau_currency'] ?? 'USD', 3), 'rights' => kau_text($input['kau_rights'] ?? '', 2000), 'budget' => null);
    if (!in_array($out['format'], array('','dedicated','launch','category'), true) || !in_array($out['currency'], array('USD','CAD','EUR','GBP'), true)) { throw new InvalidArgumentException('Unknown format or currency.'); }
    $budget = $input['kau_budget'] ?? '';
    if ($budget !== '') {
        if (!is_scalar($budget) || !is_numeric($budget) || (float) $budget < 0 || (float) $budget > 1000000) { throw new InvalidArgumentException('Invalid budget.'); }
        $out['budget'] = (float) $budget;
    }
    $out['rights'] = sanitize_textarea_field($out['rights']);
    return $out;
}

/** After the established handler passes consent, spam and validation, enrich its durable row. */
function kau_store_sponsor_details($inquiry_id, $details) {
    if (apply_filters('kau_existing_inquiry_is_valid', false, $inquiry_id) !== true) { throw new RuntimeException('Established durable inquiry identity required.'); }
    if (!update_post_meta($inquiry_id, '_kau_sponsor_details', $details) && get_post_meta($inquiry_id, '_kau_sponsor_details', true) !== $details) { throw new RuntimeException('Sponsor detail storage failed.'); }
    return true;
}
