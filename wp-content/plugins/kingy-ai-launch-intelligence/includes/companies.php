<?php

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_find_or_create_company($company_name, $args = array()) {
    $company_name = sanitize_text_field(kingy_ali_company_text_value($company_name));
    if ($company_name === '') {
        return 0;
    }

    $company_summary = isset($args['company_summary']) ? kingy_ali_company_text_value($args['company_summary']) : '';
    $existing = get_page_by_path(sanitize_title($company_name), OBJECT, 'kingy_ai_company');
    if ($existing) {
        kingy_ali_update_company_from_args($existing->ID, $args);
        return (int) $existing->ID;
    }

    $existing_by_title = get_posts(
        array(
            'post_type' => 'kingy_ai_company',
            'post_status' => 'any',
            'title' => $company_name,
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'suppress_filters' => true,
        )
    );
    if (!empty($existing_by_title)) {
        kingy_ali_update_company_from_args((int) $existing_by_title[0], $args);
        return (int) $existing_by_title[0];
    }

    $post_id = wp_insert_post(
        array(
            'post_type' => 'kingy_ai_company',
            'post_status' => isset($args['post_status']) ? sanitize_key($args['post_status']) : 'draft',
            'post_title' => $company_name,
            'post_name' => sanitize_title($company_name),
            'post_content' => $company_summary !== '' ? sanitize_textarea_field($company_summary) : '',
            'post_excerpt' => $company_summary !== '' ? wp_trim_words(sanitize_textarea_field($company_summary), 30) : '',
        ),
        true
    );

    if (is_wp_error($post_id)) {
        return 0;
    }

    kingy_ali_update_company_from_args($post_id, $args);
    return (int) $post_id;
}

function kingy_ali_update_company_from_args($company_id, $args) {
    if (!empty($args['post_status']) && $args['post_status'] === 'publish' && get_post_status($company_id) !== 'publish') {
        wp_update_post(
            array(
                'ID' => absint($company_id),
                'post_status' => 'publish',
            )
        );
    }

    $field_map = array(
        'official_url',
        'company_summary',
        'ai_evidence',
        'buyer_notes',
        'founder_team',
        'funding',
        'contact_url',
        'founder_contact_email',
        'outreach_status',
        'sponsor_fit_score_internal',
        'budget_likelihood_internal',
        'internal_notes',
        'sources',
        'source_notes',
        'verification_status',
        'last_verified',
    );

    $fields = kingy_ali_company_meta_fields();
    foreach ($field_map as $key) {
        if (!isset($args[$key]) || $args[$key] === '') {
            continue;
        }

        $field = isset($fields[$key]) ? $fields[$key] : array('type' => 'text');
        $value = kingy_ali_sanitize_meta_value($args[$key], $field);
        if ($value === '' || $value === null) {
            continue;
        }

        update_post_meta($company_id, kingy_ali_meta_key($key), $value);
    }

    if (!empty($args['category'])) {
        kingy_ali_set_company_terms($company_id, $args['category'], 'kingy_launch_category');
    }

    if (!empty($args['audience'])) {
        kingy_ali_set_company_terms($company_id, $args['audience'], 'kingy_audience');
    }
}

function kingy_ali_company_meta_text($post_id, $key, $default = '') {
    return kingy_ali_company_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_company_text_value($value, $default = '') {
    if (function_exists('kingy_ali_public_profile_text')) {
        return kingy_ali_public_profile_text($value, $default);
    }

    if (!is_scalar($value)) {
        return is_scalar($default) ? (string) $default : '';
    }

    $value = trim((string) $value);
    if ($value === '' && is_scalar($default)) {
        return (string) $default;
    }

    return $value;
}

function kingy_ali_company_number_value($value) {
    if (function_exists('kingy_ali_public_profile_number')) {
        return kingy_ali_public_profile_number($value);
    }

    return is_scalar($value) ? (float) $value : 0.0;
}

function kingy_ali_seed_company_directory_profiles() {
    $seed_version = '2026-06-14-v3';
    if (get_option('kingy_ali_company_directory_seed_version', '') === $seed_version) {
        return 0;
    }

    $lock_key = 'kingy_ali_company_directory_seed_lock_' . sanitize_key($seed_version);
    $lock_started = (int) get_option($lock_key, 0);
    if (!add_option($lock_key, (string) time(), '', false)) {
        if ($lock_started <= 0 || $lock_started > time() - 15 * MINUTE_IN_SECONDS) {
            return 0;
        }

        delete_option($lock_key);
        if (!add_option($lock_key, (string) time(), '', false)) {
            return 0;
        }
    }

    $created = 0;
    $verified_on = current_time('Y-m-d');
    foreach (kingy_ali_company_directory_seed_records() as $record) {
        $name = isset($record['name']) ? kingy_ali_company_text_value($record['name']) : '';
        if ($name === '') {
            continue;
        }

        $company_id = kingy_ali_find_or_create_company($name, kingy_ali_company_directory_seed_record_args($record, $verified_on));

        if ($company_id) {
            $created++;
        }
    }

    update_option('kingy_ali_company_directory_seed_version', $seed_version, false);
    delete_option($lock_key);
    return $created;
}

function kingy_ali_company_directory_seed_record_args($record, $verified_on) {
    $record = is_array($record) ? $record : array();
    $name = isset($record['name']) ? kingy_ali_company_text_value($record['name']) : '';
    $summary = isset($record['summary']) ? kingy_ali_company_text_value($record['summary']) : '';
    $official_url = isset($record['official_url']) ? kingy_ali_company_text_value($record['official_url']) : '';
    $category = isset($record['category']) && is_array($record['category']) ? $record['category'] : array();
    $audience = isset($record['audience']) && is_array($record['audience']) ? $record['audience'] : array();
    $category_text = $category ? implode(', ', array_map('kingy_ali_company_text_value', $category)) : __('AI products', 'kingy-ai-launch-intelligence');
    $audience_text = $audience ? implode(', ', array_map('kingy_ali_company_text_value', $audience)) : __('AI buyers and builders', 'kingy-ai-launch-intelligence');

    $sources = isset($record['sources']) ? kingy_ali_company_text_value($record['sources']) : '';
    if ($sources === '' && $official_url !== '') {
        $sources = sprintf(__('Official AI product or company source - %s', 'kingy-ai-launch-intelligence'), $official_url);
    }

    $ai_evidence = isset($record['ai_evidence']) ? kingy_ali_company_text_value($record['ai_evidence']) : '';
    if ($ai_evidence === '') {
        $ai_evidence = sprintf(
            __('Kingy AI includes %1$s because its public product surface and category fit show active AI work in %2$s. The profile is intentionally scoped to AI products, model infrastructure, agents, coding tools, automation, generative media, robotics, or other AI-native workflows rather than generic company coverage.', 'kingy-ai-launch-intelligence'),
            $name,
            $category_text
        );
    }

    $buyer_notes = isset($record['buyer_notes']) ? kingy_ali_company_text_value($record['buyer_notes']) : '';
    if ($buyer_notes === '') {
        $buyer_notes = sprintf(
            __('Useful review angles for %1$s include product maturity, current official documentation, deployment model, pricing clarity, enterprise readiness, and whether %2$s can verify the claimed workflow through demos, docs, model cards, API pages, or launch records.', 'kingy-ai-launch-intelligence'),
            $name,
            $audience_text
        );
    }

    $source_notes = isset($record['source_notes']) ? kingy_ali_company_text_value($record['source_notes']) : '';
    if ($source_notes === '') {
        $source_notes = sprintf(
            __('Seeded directory record reviewed against official public web sources on %s. Treat funding and founder fields as editorial gaps unless a linked source states them clearly.', 'kingy-ai-launch-intelligence'),
            $verified_on
        );
    }

    return array(
        'post_status' => 'publish',
        'official_url' => $official_url,
        'company_summary' => $summary,
        'ai_evidence' => $ai_evidence,
        'buyer_notes' => $buyer_notes,
        'funding' => isset($record['funding']) ? $record['funding'] : __('Unknown', 'kingy-ai-launch-intelligence'),
        'sources' => $sources,
        'source_notes' => $source_notes,
        'verification_status' => isset($record['verification_status']) ? $record['verification_status'] : 'verified',
        'last_verified' => $verified_on,
        'category' => $category,
        'audience' => $audience,
    );
}

function kingy_ali_company_sources_from_related_post($post_id, $fallback_url = '', $fallback_label = '') {
    $post_id = absint($post_id);
    $lines = array();
    if ($post_id && function_exists('kingy_ali_public_source_links')) {
        foreach (kingy_ali_public_source_links($post_id) as $source) {
            if (empty($source['url'])) {
                continue;
            }

            $label = !empty($source['label']) ? $source['label'] : __('Source', 'kingy-ai-launch-intelligence');
            $lines[] = sanitize_text_field($label) . ' - ' . esc_url_raw($source['url']);
        }
    }

    $fallback_url = kingy_ali_company_text_value($fallback_url);
    if (!$lines && $fallback_url !== '') {
        $lines[] = sanitize_text_field($fallback_label ? $fallback_label : __('Official source', 'kingy-ai-launch-intelligence')) . ' - ' . esc_url_raw($fallback_url);
    }

    return implode("\n", array_values(array_unique(array_filter($lines))));
}

function kingy_ali_backfill_company_profile_evidence() {
    $backfill_version = '2026-06-14-v1';
    if (get_option('kingy_ali_company_profile_evidence_backfill_version', '') === $backfill_version) {
        return 0;
    }

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_company',
            'post_status' => array('publish', 'draft', 'pending'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => kingy_ali_meta_key('ai_evidence'),
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => kingy_ali_meta_key('ai_evidence'),
                    'value' => '',
                    'compare' => '=',
                ),
                array(
                    'key' => kingy_ali_meta_key('source_notes'),
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => kingy_ali_meta_key('source_notes'),
                    'value' => '',
                    'compare' => '=',
                ),
                array(
                    'key' => kingy_ali_meta_key('verification_status'),
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => kingy_ali_meta_key('verification_status'),
                    'value' => '',
                    'compare' => '=',
                ),
            ),
        )
    );

    $updated = 0;
    foreach ($query->posts as $company_id) {
        $company_id = absint($company_id);
        if (!$company_id) {
            continue;
        }

        $launch_id = kingy_ali_company_first_related_post_id($company_id, 'kingy_ai_launch');
        $tool_id = kingy_ali_company_first_related_post_id($company_id, 'kingy_ai_tool');
        if (!$launch_id && !$tool_id) {
            continue;
        }

        $source_id = $launch_id ? $launch_id : $tool_id;
        $source_type = $launch_id ? 'launch' : 'tool';
        $source_title = get_the_title($source_id);
        $source_summary = $launch_id
            ? kingy_ali_company_meta_text($source_id, 'what_launched', $source_title)
            : kingy_ali_company_meta_text($source_id, 'what_it_does', $source_title);
        $source_url = kingy_ali_company_meta_text($source_id, 'official_url');
        $sources = kingy_ali_company_sources_from_related_post(
            $source_id,
            $source_url,
            $launch_id ? __('Official launch source', 'kingy-ai-launch-intelligence') : __('Official tool source', 'kingy-ai-launch-intelligence')
        );

        $updates = array();
        if (kingy_ali_company_meta_text($company_id, 'ai_evidence') === '') {
            $updates['ai_evidence'] = sprintf(
                __('This company profile is backed by the linked AI %1$s record "%2$s": %3$s', 'kingy-ai-launch-intelligence'),
                $source_type,
                $source_title,
                $source_summary
            );
        }
        if (kingy_ali_company_meta_text($company_id, 'buyer_notes') === '') {
            $updates['buyer_notes'] = __('Use the linked launch/tool graph to check product fit, source quality, pricing clarity, demo availability, API/model access, and whether the AI workflow is current enough for buying, writing, comparison, or creator-coverage decisions.', 'kingy-ai-launch-intelligence');
        }
        if (kingy_ali_company_meta_text($company_id, 'sources') === '' && $sources !== '') {
            $updates['sources'] = $sources;
        }
        if (kingy_ali_company_meta_text($company_id, 'source_notes') === '') {
            $updates['source_notes'] = sprintf(
                __('Company context backfilled from the linked AI %1$s record "%2$s"; verify claims against attached official source links before relying on this profile.', 'kingy-ai-launch-intelligence'),
                $source_type,
                $source_title
            );
        }
        if (kingy_ali_company_meta_text($company_id, 'verification_status') === '') {
            $updates['verification_status'] = $launch_id ? kingy_ali_company_meta_text($source_id, 'verification_status', 'partially_verified') : 'partially_verified';
        }
        if (kingy_ali_company_meta_text($company_id, 'last_verified') === '') {
            $updates['last_verified'] = kingy_ali_company_meta_text($source_id, 'last_verified', current_time('Y-m-d'));
        }

        foreach ($updates as $key => $value) {
            $field = kingy_ali_company_meta_fields();
            $field = isset($field[$key]) ? $field[$key] : array('type' => 'text');
            $value = kingy_ali_sanitize_meta_value($value, $field);
            if ($value !== '' && $value !== null) {
                update_post_meta($company_id, kingy_ali_meta_key($key), $value);
            }
        }

        if ($updates) {
            $updated++;
        }
    }

    update_option('kingy_ali_company_profile_evidence_backfill_version', $backfill_version, false);
    return $updated;
}

function kingy_ali_company_first_related_post_id($company_id, $post_type) {
    $query_args = array(
        'post_type' => sanitize_key($post_type),
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'orderby' => $post_type === 'kingy_ai_launch' ? 'meta_value' : 'title',
        'order' => $post_type === 'kingy_ai_launch' ? 'DESC' : 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('related_company_id'),
                'value' => absint($company_id),
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        ),
    );
    if ($post_type === 'kingy_ai_launch') {
        $query_args['meta_key'] = kingy_ali_meta_key('launch_date');
    }

    $query = new WP_Query($query_args);

    return !empty($query->posts[0]) ? absint($query->posts[0]) : 0;
}

function kingy_ali_company_directory_seed_records() {
    return array(
        array('name' => 'Hugging Face', 'official_url' => 'https://huggingface.co/', 'summary' => 'Hugging Face is an AI platform company focused on open model hosting, datasets, spaces, inference, and collaboration for machine learning teams.', 'category' => array('AI Models', 'AI Developer Tools', 'Open-Source AI'), 'audience' => array('Developers', 'AI Engineers', 'Researchers')),
        array('name' => 'NVIDIA', 'official_url' => 'https://www.nvidia.com/en-us/ai/', 'summary' => 'NVIDIA is a computing company whose GPUs, software platforms, model services, and AI infrastructure are central to modern model training and deployment.', 'category' => array('AI Infrastructure', 'AI Hardware', 'AI Models'), 'audience' => array('AI Engineers', 'Enterprise IT', 'Researchers')),
        array('name' => 'Databricks', 'official_url' => 'https://www.databricks.com/product/machine-learning', 'summary' => 'Databricks builds data and AI infrastructure for analytics, data engineering, machine learning, governance, and enterprise AI application development.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('data teams', 'AI Platform Teams', 'Enterprises')),
        array('name' => 'CoreWeave', 'official_url' => 'https://www.coreweave.com/', 'summary' => 'CoreWeave is a specialized cloud provider focused on GPU infrastructure for AI training, inference, rendering, and compute-intensive workloads.', 'category' => array('AI Infrastructure', 'AI Hardware'), 'audience' => array('AI Engineers', 'AI Platform Teams', 'Startups')),
        array('name' => 'Cerebras', 'official_url' => 'https://www.cerebras.ai/', 'summary' => 'Cerebras builds AI compute systems and model infrastructure designed for large-scale training, inference, and high-performance AI workloads.', 'category' => array('AI Infrastructure', 'AI Hardware', 'AI Models'), 'audience' => array('Researchers', 'AI Engineers', 'Enterprise IT')),
        array('name' => 'Groq', 'official_url' => 'https://groq.com/', 'summary' => 'Groq builds AI inference infrastructure around its language processing unit systems and hosted services for low-latency model serving.', 'category' => array('AI Infrastructure', 'AI Models'), 'audience' => array('Developers', 'AI Engineers', 'AI Platform Teams')),
        array('name' => 'Together AI', 'official_url' => 'https://www.together.ai/', 'summary' => 'Together AI provides AI cloud infrastructure, inference APIs, fine-tuning, and services for teams building with open and custom models.', 'category' => array('AI Infrastructure', 'AI Open-Weight Models', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Engineers', 'Startups')),
        array('name' => 'Fireworks AI', 'official_url' => 'https://fireworks.ai/', 'summary' => 'Fireworks AI provides model inference, fine-tuning, and developer infrastructure for teams building production applications with generative AI.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Platform Teams', 'Startups')),
        array('name' => 'Anyscale', 'official_url' => 'https://www.anyscale.com/', 'summary' => 'Anyscale builds infrastructure around Ray for distributed AI, machine learning, data processing, model serving, and scalable Python workloads.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Engineers', 'data teams')),
        array('name' => 'Modal Labs', 'official_url' => 'https://modal.com/', 'summary' => 'Modal Labs provides serverless infrastructure for AI, data, batch jobs, and GPU workloads with a developer-oriented Python workflow.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Engineers', 'Startups')),
        array('name' => 'Baseten', 'official_url' => 'https://www.baseten.co/', 'summary' => 'Baseten provides infrastructure for deploying, serving, and monitoring machine learning models and AI applications in production.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('AI Engineers', 'AI Platform Teams', 'Developers')),
        array('name' => 'Runpod', 'official_url' => 'https://www.runpod.io/', 'summary' => 'Runpod provides cloud GPU infrastructure for AI development, training, inference, and scalable compute workflows.', 'category' => array('AI Infrastructure', 'AI Hardware'), 'audience' => array('Developers', 'AI Engineers', 'Researchers')),
        array('name' => 'Lambda', 'official_url' => 'https://lambdalabs.com/', 'summary' => 'Lambda provides GPU cloud, workstations, servers, and AI infrastructure for machine learning researchers and engineering teams.', 'category' => array('AI Infrastructure', 'AI Hardware'), 'audience' => array('AI Engineers', 'Researchers', 'Enterprise IT')),
        array('name' => 'Crusoe', 'official_url' => 'https://crusoe.ai/', 'summary' => 'Crusoe provides cloud infrastructure and energy-aware compute capacity for AI, high-performance computing, and GPU workloads.', 'category' => array('AI Infrastructure', 'AI Hardware'), 'audience' => array('AI Platform Teams', 'Enterprise IT', 'AI Engineers')),
        array('name' => 'Scale AI', 'official_url' => 'https://scale.com/', 'summary' => 'Scale AI provides data, evaluation, labeling, and model support infrastructure for organizations building and deploying AI systems.', 'category' => array('AI Infrastructure', 'AI Models'), 'audience' => array('AI Engineers', 'Enterprises', 'public-sector teams')),
        array('name' => 'Labelbox', 'official_url' => 'https://labelbox.com/', 'summary' => 'Labelbox provides data labeling, curation, model evaluation, and AI data workflows for teams improving machine learning systems.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('data teams', 'AI Engineers', 'Researchers')),
        array('name' => 'Weights & Biases', 'official_url' => 'https://wandb.ai/', 'summary' => 'Weights & Biases provides experiment tracking, model evaluation, observability, and MLOps workflows for machine learning teams.', 'category' => array('AI Developer Tools', 'AI Infrastructure'), 'audience' => array('AI Engineers', 'Researchers', 'data teams')),
        array('name' => 'LangChain', 'official_url' => 'https://www.langchain.com/', 'summary' => 'LangChain builds developer tools and infrastructure for applications that use large language models, agents, retrieval, and observability.', 'category' => array('AI Agents', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Engineers', 'Agent developers')),
        array('name' => 'LlamaIndex', 'official_url' => 'https://www.llamaindex.ai/', 'summary' => 'LlamaIndex builds data frameworks and tools for connecting private or domain-specific data to LLM applications and agent workflows.', 'category' => array('AI Developer Tools', 'AI Search Tools', 'AI Agents'), 'audience' => array('Developers', 'AI Engineers', 'data teams')),
        array('name' => 'Pinecone', 'official_url' => 'https://www.pinecone.io/', 'summary' => 'Pinecone provides vector database infrastructure for AI search, retrieval-augmented generation, recommendations, and semantic applications.', 'category' => array('AI Infrastructure', 'AI Search Tools'), 'audience' => array('Developers', 'AI Engineers', 'data teams')),
        array('name' => 'Weaviate', 'official_url' => 'https://weaviate.io/', 'summary' => 'Weaviate builds an open-source vector database and AI-native data platform for semantic search and retrieval workflows.', 'category' => array('AI Infrastructure', 'AI Search Tools', 'Open-Source AI'), 'audience' => array('Developers', 'AI Engineers', 'Open Source Maintainers')),
        array('name' => 'Chroma', 'official_url' => 'https://www.trychroma.com/', 'summary' => 'Chroma builds an AI-native open-source embedding database for developers creating retrieval, memory, and semantic search applications.', 'category' => array('AI Infrastructure', 'AI Search Tools', 'Open-Source AI'), 'audience' => array('Developers', 'AI Engineers', 'AI App Builders')),
        array('name' => 'Qdrant', 'official_url' => 'https://qdrant.tech/', 'summary' => 'Qdrant builds vector search infrastructure for semantic search, recommendation, retrieval, and AI application memory.', 'category' => array('AI Infrastructure', 'AI Search Tools', 'Open-Source AI'), 'audience' => array('Developers', 'AI Engineers', 'data teams')),
        array('name' => 'Zilliz', 'official_url' => 'https://zilliz.com/', 'summary' => 'Zilliz provides vector database infrastructure around Milvus for AI search, retrieval, recommendations, and production vector workloads.', 'category' => array('AI Infrastructure', 'AI Search Tools', 'Open-Source AI'), 'audience' => array('Developers', 'AI Engineers', 'data teams')),
        array('name' => 'Glean', 'official_url' => 'https://www.glean.com/', 'summary' => 'Glean builds enterprise search and AI assistant products that connect workplace knowledge, applications, and company data.', 'category' => array('AI Search Tools', 'AI Productivity Tools', 'AI Agents'), 'audience' => array('Enterprise IT', 'Enterprise workspaces', 'Teams')),
        array('name' => 'Sierra', 'official_url' => 'https://sierra.ai/', 'summary' => 'Sierra builds conversational AI agents for customer experience, service workflows, and business operations.', 'category' => array('AI Agents', 'AI Automation Tools'), 'audience' => array('service', 'Enterprise sales', 'operations groups')),
        array('name' => 'Harvey', 'official_url' => 'https://www.harvey.ai/', 'summary' => 'Harvey builds AI tools for legal and professional services workflows, including research, drafting, analysis, and firm-specific knowledge work.', 'category' => array('AI Productivity Tools', 'AI Research Tools'), 'audience' => array('Enterprise and business users', 'analysts', 'Teams')),
        array('name' => 'Hebbia', 'official_url' => 'https://www.hebbia.ai/', 'summary' => 'Hebbia builds AI research and analysis tools for knowledge work across documents, deals, diligence, and complex enterprise workflows.', 'category' => array('AI Research Tools', 'AI Productivity Tools'), 'audience' => array('analysts', 'finance teams', 'Enterprise and business users')),
        array('name' => 'Abridge', 'official_url' => 'https://www.abridge.com/', 'summary' => 'Abridge builds healthcare AI products for clinical documentation, care conversations, and medical workflow support.', 'category' => array('AI Voice/Audio Tools', 'AI Productivity Tools'), 'audience' => array('Enterprise and business users', 'operators', 'Teams')),
        array('name' => 'Ambience Healthcare', 'official_url' => 'https://www.ambiencehealthcare.com/', 'summary' => 'Ambience Healthcare builds AI documentation and workflow tools for clinicians and healthcare organizations.', 'category' => array('AI Voice/Audio Tools', 'AI Productivity Tools'), 'audience' => array('Enterprise and business users', 'operators', 'Teams')),
        array('name' => 'Hippocratic AI', 'official_url' => 'https://www.hippocraticai.com/', 'summary' => 'Hippocratic AI builds healthcare-focused generative AI agents for patient-facing and clinical support workflows.', 'category' => array('AI Agents', 'AI Voice/Audio Tools'), 'audience' => array('Enterprise and business users', 'service', 'operators')),
        array('name' => 'Nabla', 'official_url' => 'https://www.nabla.com/', 'summary' => 'Nabla builds AI assistant and ambient documentation products for clinicians, medical groups, and healthcare teams.', 'category' => array('AI Voice/Audio Tools', 'AI Productivity Tools'), 'audience' => array('Enterprise and business users', 'Teams', 'operators')),
        array('name' => 'Synthesia', 'official_url' => 'https://www.synthesia.io/', 'summary' => 'Synthesia builds AI video generation products for avatar-led videos, training content, localization, internal communications, and business video workflows.', 'category' => array('AI Video Tools', 'AI Productivity Tools'), 'audience' => array('Video Creators', 'Marketers', 'Enterprise and business users')),
        array('name' => 'HeyGen', 'official_url' => 'https://www.heygen.com/', 'summary' => 'HeyGen builds AI video, avatar, translation, and personalized video tools for creators, marketers, sales teams, and business communication.', 'category' => array('AI Video Tools', 'AI Voice/Audio Tools'), 'audience' => array('Creators', 'Marketers', 'Sales Teams')),
        array('name' => 'D-ID', 'official_url' => 'https://www.d-id.com/', 'summary' => 'D-ID builds AI video and digital human tools for presenters, avatars, agents, and personalized communication experiences.', 'category' => array('AI Video Tools', 'AI Agents'), 'audience' => array('Creators', 'Marketers', 'Enterprise and business users')),
        array('name' => 'Tavus', 'official_url' => 'https://www.tavus.io/', 'summary' => 'Tavus builds AI video generation and conversational video products for personalized messages, digital replicas, and customer-facing video experiences.', 'category' => array('AI Video Tools', 'AI Agents'), 'audience' => array('Sales Teams', 'Marketers', 'Product Teams')),
        array('name' => 'Pika', 'official_url' => 'https://pika.art/', 'summary' => 'Pika builds AI video generation tools for creating, editing, and iterating on short-form generative video.', 'category' => array('AI Video Tools'), 'audience' => array('Creators', 'Video Creators', 'AI Artists')),
        array('name' => 'Midjourney', 'official_url' => 'https://www.midjourney.com/', 'summary' => 'Midjourney builds generative image tools used by artists, designers, creators, and visual teams for AI-assisted image creation.', 'category' => array('AI Image Tools'), 'audience' => array('AI Artists', 'Designers', 'Creators')),
        array('name' => 'Krea AI', 'official_url' => 'https://www.krea.ai/', 'summary' => 'Krea AI builds image and video creation tools for real-time visual generation, design exploration, and creative production workflows.', 'category' => array('AI Image Tools', 'AI Video Tools'), 'audience' => array('Designers', 'AI Artists', 'Creators')),
        array('name' => 'Leonardo AI', 'official_url' => 'https://leonardo.ai/', 'summary' => 'Leonardo AI builds AI image, design, and creative production tools for game assets, marketing visuals, concept art, and visual workflows.', 'category' => array('AI Image Tools'), 'audience' => array('Designers', 'AI Artists', 'Creators')),
        array('name' => 'Canva', 'official_url' => 'https://www.canva.com/magic/', 'summary' => 'Canva builds visual communication, design, and AI-assisted content creation tools for teams, creators, marketers, educators, and businesses.', 'category' => array('AI Image Tools', 'AI Productivity Tools', 'AI Marketing Tools'), 'audience' => array('Designers', 'Marketers', 'Small Business Owners')),
        array('name' => 'Adobe', 'official_url' => 'https://www.adobe.com/products/firefly.html', 'summary' => 'Adobe builds creative, document, marketing, and generative AI products across design, imaging, video, PDF, analytics, and content workflows.', 'category' => array('AI Image Tools', 'AI Video Tools', 'AI Marketing Tools'), 'audience' => array('Designers', 'Creators', 'Enterprise and business users')),
        array('name' => 'Figma', 'official_url' => 'https://www.figma.com/ai/', 'summary' => 'Figma builds collaborative design and product development software with AI-assisted workflows for interface design, prototyping, and team collaboration.', 'category' => array('AI Productivity Tools', 'AI Image Tools'), 'audience' => array('Designers', 'Product Teams', 'Frontend Developers')),
        array('name' => 'Notion', 'official_url' => 'https://www.notion.com/product/ai', 'summary' => 'Notion builds workspace software with AI features for notes, docs, knowledge management, project work, search, and team productivity.', 'category' => array('AI Productivity Tools', 'AI Writing Tools'), 'audience' => array('Teams', 'Operators', 'Small Businesses')),
        array('name' => 'Grammarly', 'official_url' => 'https://www.grammarly.com/ai', 'summary' => 'Grammarly builds AI writing, editing, communication, and productivity tools for individuals, teams, and enterprise workflows.', 'category' => array('AI Writing Tools', 'AI Productivity Tools'), 'audience' => array('Writers', 'Students', 'Enterprise and business users')),
        array('name' => 'Writer', 'official_url' => 'https://writer.com/', 'summary' => 'Writer builds enterprise generative AI products for writing, agents, brand governance, knowledge work, and business process automation.', 'category' => array('AI Writing Tools', 'AI Agents', 'AI Productivity Tools'), 'audience' => array('Enterprises', 'Marketers', 'Teams')),
        array('name' => 'Jasper', 'official_url' => 'https://www.jasper.ai/', 'summary' => 'Jasper builds AI marketing and content generation software for brand, campaign, copywriting, and marketing workflow teams.', 'category' => array('AI Marketing Tools', 'AI Writing Tools'), 'audience' => array('Marketers', 'Creators', 'Small Businesses')),
        array('name' => 'Copy.ai', 'official_url' => 'https://www.copy.ai/', 'summary' => 'Copy.ai builds AI workflow and go-to-market automation tools for sales, marketing, content, and operations teams.', 'category' => array('AI Marketing Tools', 'AI Automation Tools', 'AI Writing Tools'), 'audience' => array('Marketers', 'Sales Teams', 'operations groups')),
        array('name' => 'Typeface', 'official_url' => 'https://www.typeface.ai/', 'summary' => 'Typeface builds enterprise AI content tools for brand-safe marketing, campaign assets, product content, and creative workflows.', 'category' => array('AI Marketing Tools', 'AI Writing Tools', 'AI Image Tools'), 'audience' => array('Marketers', 'Enterprise and business users', 'Creators')),
        array('name' => 'Gamma', 'official_url' => 'https://gamma.app/', 'summary' => 'Gamma builds AI-assisted presentation, document, and web page creation tools for business storytelling and fast content drafting.', 'category' => array('AI Productivity Tools', 'AI Writing Tools'), 'audience' => array('Product Teams', 'Marketers', 'Small Businesses')),
        array('name' => 'Character.AI', 'official_url' => 'https://character.ai/', 'summary' => 'Character.AI builds consumer AI character and conversation products for interactive chat, roleplay, entertainment, and assistant experiences.', 'category' => array('AI Agents', 'AI Productivity Tools'), 'audience' => array('Consumers', 'Creators', 'Students')),
        array('name' => 'Inflection AI', 'official_url' => 'https://inflection.ai/', 'summary' => 'Inflection AI builds conversational AI products and model technology for personal assistants, enterprise assistants, and human-computer interaction.', 'category' => array('AI Agents', 'AI Models'), 'audience' => array('Enterprise and business users', 'Consumers', 'Teams')),
        array('name' => 'AI21 Labs', 'official_url' => 'https://www.ai21.com/', 'summary' => 'AI21 Labs builds foundation models and enterprise AI products for language understanding, writing, reasoning, and business workflows.', 'category' => array('AI Models', 'AI Writing Tools', 'Foundation Models'), 'audience' => array('Developers', 'Enterprises', 'Writers')),
        array('name' => 'Aleph Alpha', 'official_url' => 'https://aleph-alpha.com/', 'summary' => 'Aleph Alpha builds sovereign and enterprise AI models, tooling, and services with a focus on business and public-sector deployments.', 'category' => array('AI Models', 'Foundation Models', 'AI Infrastructure'), 'audience' => array('Enterprises', 'public-sector teams', 'AI Platform Teams')),
        array('name' => 'Liquid AI', 'official_url' => 'https://www.liquid.ai/', 'summary' => 'Liquid AI builds foundation model technology and AI systems with an emphasis on efficient, capable models for enterprise and developer use.', 'category' => array('AI Models', 'Foundation Models'), 'audience' => array('Developers', 'AI Engineers', 'Enterprise and business users')),
        array('name' => 'Sakana AI', 'official_url' => 'https://sakana.ai/', 'summary' => 'Sakana AI is an AI research and model company focused on nature-inspired intelligence, model development, and research-driven AI systems.', 'category' => array('AI Models', 'AI Research Tools'), 'audience' => array('Researchers', 'AI Engineers', 'AI Platform Teams')),
        array('name' => '01.AI', 'official_url' => 'https://www.01.ai/', 'summary' => '01.AI builds large language models and AI products, including open-weight model work and assistant-oriented AI applications.', 'category' => array('AI Models', 'AI Open-Weight Models'), 'audience' => array('Developers', 'AI Engineers', 'Researchers')),
        array('name' => 'Poolside', 'official_url' => 'https://poolside.ai/', 'summary' => 'Poolside builds AI systems for software development, code generation, and engineering workflows.', 'category' => array('AI Coding Tools', 'AI Developer Tools', 'AI Agents'), 'audience' => array('Developers', 'Software teams', 'AI Engineers')),
        array('name' => 'Magic', 'official_url' => 'https://magic.dev/', 'summary' => 'Magic builds AI systems for software engineering, code assistance, and long-context developer workflows.', 'category' => array('AI Coding Tools', 'AI Developer Tools', 'AI Agents'), 'audience' => array('Developers', 'Software teams', 'AI Engineers')),
        array('name' => 'Tabnine', 'official_url' => 'https://www.tabnine.com/', 'summary' => 'Tabnine builds AI code assistant tools for code completion, developer productivity, and enterprise software engineering workflows.', 'category' => array('AI Coding Tools', 'AI Developer Tools'), 'audience' => array('Developers', 'Software teams', 'Enterprise IT')),
        array('name' => 'Sourcegraph', 'official_url' => 'https://sourcegraph.com/', 'summary' => 'Sourcegraph builds code search, code intelligence, and AI coding tools for understanding and changing large codebases.', 'category' => array('AI Coding Tools', 'AI Developer Tools', 'AI Search Tools'), 'audience' => array('Developers', 'Software teams', 'Engineering Teams')),
        array('name' => 'JetBrains', 'official_url' => 'https://www.jetbrains.com/ai/', 'summary' => 'JetBrains builds developer tools, IDEs, and AI-assisted coding features for software teams and individual developers.', 'category' => array('AI Coding Tools', 'AI Developer Tools'), 'audience' => array('Developers', 'Software teams', 'Engineering Teams')),
        array('name' => 'Vercel', 'official_url' => 'https://vercel.com/ai', 'summary' => 'Vercel builds frontend cloud, deployment, AI application, and developer workflow tools for web teams shipping modern applications.', 'category' => array('AI Developer Tools', 'AI Coding Tools', 'AI Infrastructure'), 'audience' => array('Frontend Developers', 'Developers', 'Product Teams')),
        array('name' => 'Dust', 'official_url' => 'https://dust.tt/', 'summary' => 'Dust builds AI assistants and agent workflows for teams connecting company knowledge, tools, and business processes.', 'category' => array('AI Agents', 'AI Automation Tools', 'AI Productivity Tools'), 'audience' => array('Teams', 'Enterprise workspaces', 'Operators')),
        array('name' => 'Zapier', 'official_url' => 'https://zapier.com/ai', 'summary' => 'Zapier builds automation and AI workflow tools that connect apps, data, agents, and business processes without heavy custom engineering.', 'category' => array('AI Automation Tools', 'AI Agents', 'AI Productivity Tools'), 'audience' => array('Operators', 'Small Businesses', 'Automation Teams')),
        array('name' => 'Make', 'official_url' => 'https://www.make.com/en/ai-automation', 'summary' => 'Make builds visual automation software for connecting applications, data flows, AI steps, and business operations.', 'category' => array('AI Automation Tools', 'AI Productivity Tools'), 'audience' => array('Automation Teams', 'Operators', 'Small Businesses')),
        array('name' => 'n8n', 'official_url' => 'https://n8n.io/', 'summary' => 'n8n builds workflow automation software with self-hosted and cloud options for connecting apps, APIs, AI steps, and operations.', 'category' => array('AI Automation Tools', 'AI Developer Tools', 'Open-Source AI'), 'audience' => array('Developers', 'Automation Teams', 'Operators')),
        array('name' => 'Moveworks', 'official_url' => 'https://www.moveworks.com/', 'summary' => 'Moveworks builds enterprise AI assistants and automation products for employee support, IT, HR, knowledge, and service workflows.', 'category' => array('AI Agents', 'AI Automation Tools', 'AI Productivity Tools'), 'audience' => array('Enterprise IT', 'service', 'Teams')),
        array('name' => 'ServiceNow', 'official_url' => 'https://www.servicenow.com/products/ai-agents.html', 'summary' => 'ServiceNow builds enterprise workflow, automation, service management, and AI agent products for large organizations.', 'category' => array('AI Agents', 'AI Automation Tools', 'AI Productivity Tools'), 'audience' => array('Enterprise IT', 'Enterprise and business users', 'service')),
        array('name' => 'Palantir', 'official_url' => 'https://www.palantir.com/platforms/aip/', 'summary' => 'Palantir builds data, analytics, operations, and AI platforms for enterprises, governments, defense, and complex operational environments.', 'category' => array('AI Infrastructure', 'AI Automation Tools', 'AI Productivity Tools'), 'audience' => array('Enterprises', 'public-sector teams', 'operators')),
        array('name' => 'UiPath', 'official_url' => 'https://www.uipath.com/ai', 'summary' => 'UiPath builds automation, robotic process automation, agentic automation, and AI workflow products for enterprise operations.', 'category' => array('AI Automation Tools', 'AI Agents'), 'audience' => array('Automation Teams', 'Enterprise and business users', 'operations groups')),
        array('name' => 'Automation Anywhere', 'official_url' => 'https://www.automationanywhere.com/', 'summary' => 'Automation Anywhere builds automation, RPA, process orchestration, and AI agent products for enterprise business workflows.', 'category' => array('AI Automation Tools', 'AI Agents'), 'audience' => array('Automation Teams', 'Enterprises', 'operations groups')),
        array('name' => 'C3 AI', 'official_url' => 'https://c3.ai/', 'summary' => 'C3 AI builds enterprise AI application software and platforms for industries using predictive analytics, generative AI, and operational AI systems.', 'category' => array('AI Automation Tools', 'AI Infrastructure'), 'audience' => array('Enterprises', 'Enterprise IT', 'operators')),
        array('name' => 'DataRobot', 'official_url' => 'https://www.datarobot.com/', 'summary' => 'DataRobot builds enterprise AI platforms for machine learning, generative AI, model governance, deployment, and AI operations.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('data teams', 'Enterprise IT', 'AI Engineers')),
        array('name' => 'H2O.ai', 'official_url' => 'https://h2o.ai/', 'summary' => 'H2O.ai builds open-source and enterprise AI platforms for machine learning, generative AI, predictive modeling, and data science workflows.', 'category' => array('AI Infrastructure', 'AI Developer Tools', 'Open-Source AI'), 'audience' => array('data teams', 'AI Engineers', 'Enterprises')),
        array('name' => 'Snowflake', 'official_url' => 'https://www.snowflake.com/en/product/features/cortex/', 'summary' => 'Snowflake builds data cloud, analytics, application, and AI infrastructure for teams working with enterprise data and AI workloads.', 'category' => array('AI Infrastructure', 'AI Developer Tools'), 'audience' => array('data teams', 'Enterprises', 'AI Platform Teams')),
        array('name' => 'Oracle', 'official_url' => 'https://www.oracle.com/artificial-intelligence/', 'summary' => 'Oracle builds cloud, database, enterprise software, and AI infrastructure products for business applications, data platforms, and model workloads.', 'category' => array('AI Infrastructure', 'AI Productivity Tools'), 'audience' => array('Enterprises', 'Enterprise IT', 'AI Platform Teams')),
        array('name' => 'IBM', 'official_url' => 'https://www.ibm.com/watsonx', 'summary' => 'IBM builds enterprise AI, data, automation, and governance products, including the watsonx platform for model development and business AI workflows.', 'category' => array('AI Infrastructure', 'AI Models', 'AI Automation Tools'), 'audience' => array('Enterprises', 'Enterprise IT', 'public-sector teams')),
        array('name' => 'SAP', 'official_url' => 'https://www.sap.com/products/artificial-intelligence.html', 'summary' => 'SAP builds enterprise business software with AI features across finance, supply chain, HR, procurement, analytics, and operations workflows.', 'category' => array('AI Productivity Tools', 'AI Automation Tools'), 'audience' => array('Enterprises', 'finance teams', 'operations groups')),
        array('name' => 'Atlassian', 'official_url' => 'https://www.atlassian.com/software/rovo', 'summary' => 'Atlassian builds collaboration, software development, service management, and AI-assisted teamwork products for engineering and business teams.', 'category' => array('AI Productivity Tools', 'AI Developer Tools'), 'audience' => array('Software teams', 'Enterprise IT', 'Teams')),
        array('name' => 'Asana', 'official_url' => 'https://asana.com/product/ai', 'summary' => 'Asana builds work management and AI-assisted planning tools for coordinating projects, goals, team workflows, and operational execution.', 'category' => array('AI Productivity Tools', 'AI Automation Tools'), 'audience' => array('Product Teams', 'Operations Teams', 'Teams')),
        array('name' => 'ClickUp', 'official_url' => 'https://clickup.com/ai', 'summary' => 'ClickUp builds productivity, project management, document, and AI-assisted work management tools for teams and businesses.', 'category' => array('AI Productivity Tools', 'AI Automation Tools'), 'audience' => array('Teams', 'Small Businesses', 'Operators')),
        array('name' => 'Airtable', 'official_url' => 'https://www.airtable.com/product/ai', 'summary' => 'Airtable builds app platform, database, workflow, and AI tools for teams creating operational systems without heavy engineering.', 'category' => array('AI Productivity Tools', 'AI Automation Tools', 'AI App Builders'), 'audience' => array('Operators', 'Product Teams', 'Small Businesses')),
        array('name' => 'Descript', 'official_url' => 'https://www.descript.com/', 'summary' => 'Descript builds AI-assisted audio and video editing tools for creators, podcasters, marketers, educators, and production teams.', 'category' => array('AI Video Tools', 'AI Voice/Audio Tools'), 'audience' => array('Creators', 'Video Creators', 'Marketers')),
        array('name' => 'Deepgram', 'official_url' => 'https://deepgram.com/', 'summary' => 'Deepgram builds voice AI APIs and speech technology for transcription, speech recognition, text-to-speech, and real-time audio applications.', 'category' => array('AI Voice/Audio Tools', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Engineers', 'Product Teams')),
        array('name' => 'AssemblyAI', 'official_url' => 'https://www.assemblyai.com/', 'summary' => 'AssemblyAI builds speech AI models and APIs for transcription, audio intelligence, summarization, and voice application workflows.', 'category' => array('AI Voice/Audio Tools', 'AI Developer Tools'), 'audience' => array('Developers', 'AI Engineers', 'Product Teams')),
        array('name' => 'SoundHound AI', 'official_url' => 'https://www.soundhound.com/', 'summary' => 'SoundHound AI builds voice AI, conversational intelligence, and speech-enabled products for automotive, restaurant, device, and enterprise use cases.', 'category' => array('AI Voice/Audio Tools', 'AI Agents'), 'audience' => array('Enterprise and business users', 'Product Teams', 'service')),
        array('name' => 'Suno', 'official_url' => 'https://suno.com/', 'summary' => 'Suno builds AI music generation tools for creating songs, vocals, instrumentals, and music ideas from prompts.', 'category' => array('AI Music Tools', 'AI Voice/Audio Tools'), 'audience' => array('Creators', 'AI Artists', 'Consumers')),
        array('name' => 'Udio', 'official_url' => 'https://www.udio.com/', 'summary' => 'Udio builds AI music generation tools for creating, extending, and exploring songs and audio ideas.', 'category' => array('AI Music Tools', 'AI Voice/Audio Tools'), 'audience' => array('Creators', 'AI Artists', 'Consumers')),
        array('name' => 'Vapi', 'official_url' => 'https://vapi.ai/', 'summary' => 'Vapi builds developer infrastructure for voice AI agents, phone calling, speech workflows, and conversational voice applications.', 'category' => array('AI Voice/Audio Tools', 'AI Agents', 'AI Developer Tools'), 'audience' => array('Developers', 'Agent developers', 'Product Teams')),
        array('name' => 'Retell AI', 'official_url' => 'https://www.retellai.com/', 'summary' => 'Retell AI builds voice agent infrastructure for phone calls, customer conversations, and real-time AI voice workflows.', 'category' => array('AI Voice/Audio Tools', 'AI Agents'), 'audience' => array('Developers', 'Sales Teams', 'service')),
        array('name' => 'Bland AI', 'official_url' => 'https://www.bland.ai/', 'summary' => 'Bland AI builds AI phone calling and voice agent products for automating outbound and inbound conversation workflows.', 'category' => array('AI Voice/Audio Tools', 'AI Agents', 'AI Automation Tools'), 'audience' => array('Sales Teams', 'service', 'Operators')),
        array('name' => 'OpenRouter', 'official_url' => 'https://openrouter.ai/', 'summary' => 'OpenRouter provides a model routing and API layer for accessing multiple AI models through a unified developer interface.', 'category' => array('AI Developer Tools', 'AI Infrastructure', 'AI Models'), 'audience' => array('Developers', 'AI Engineers', 'AI App Builders')),
        array('name' => 'Replicate', 'official_url' => 'https://replicate.com/', 'summary' => 'Replicate provides hosted model APIs and infrastructure for running open and commercial AI models in applications.', 'category' => array('AI Developer Tools', 'AI Infrastructure', 'AI Open-Weight Models'), 'audience' => array('Developers', 'AI Engineers', 'AI App Builders')),
        array('name' => 'Fal', 'official_url' => 'https://fal.ai/', 'summary' => 'Fal provides generative media APIs and AI inference infrastructure for image, video, audio, and creative AI workflows.', 'category' => array('AI Image Tools', 'AI Video Tools', 'AI Developer Tools'), 'audience' => array('Developers', 'Creators', 'AI App Builders')),
        array('name' => 'Cloudflare', 'official_url' => 'https://www.cloudflare.com/developer-platform/products/workers-ai/', 'summary' => 'Cloudflare builds internet infrastructure, developer platforms, security products, and AI deployment services for web and application teams.', 'category' => array('AI Infrastructure', 'AI Developer Tools', 'AI Security Tools'), 'audience' => array('Developers', 'Enterprise IT', 'AI Platform Teams')),
        array('name' => 'Intercom', 'official_url' => 'https://www.intercom.com/fin', 'summary' => 'Intercom builds customer service, support automation, help desk, and AI agent products for customer-facing teams.', 'category' => array('AI Agents', 'AI Automation Tools'), 'audience' => array('service', 'Sales Teams', 'Small Businesses')),
        array('name' => 'Decagon', 'official_url' => 'https://decagon.ai/', 'summary' => 'Decagon builds AI agents for customer support, service automation, and enterprise customer experience workflows.', 'category' => array('AI Agents', 'AI Automation Tools'), 'audience' => array('service', 'Enterprise and business users', 'Operations Teams')),
        array('name' => 'Ada', 'official_url' => 'https://www.ada.cx/', 'summary' => 'Ada builds AI customer service automation and agent products for support teams and customer experience operations.', 'category' => array('AI Agents', 'AI Automation Tools'), 'audience' => array('service', 'Small Businesses', 'Enterprises')),
        array('name' => 'Cresta', 'official_url' => 'https://cresta.com/', 'summary' => 'Cresta builds AI products for contact centers, sales conversations, customer service, agent coaching, and operational intelligence.', 'category' => array('AI Agents', 'AI Voice/Audio Tools', 'AI Automation Tools'), 'audience' => array('Sales Teams', 'service', 'Enterprise and business users')),
        array('name' => 'Observe.AI', 'official_url' => 'https://www.observe.ai/', 'summary' => 'Observe.AI builds conversation intelligence and contact center AI products for support, quality, coaching, and customer operations.', 'category' => array('AI Voice/Audio Tools', 'AI Automation Tools'), 'audience' => array('service', 'Sales Teams', 'Enterprise and business users')),
        array('name' => 'Forethought', 'official_url' => 'https://forethought.ai/', 'summary' => 'Forethought builds AI support automation and customer service products for resolving issues, assisting agents, and improving service operations.', 'category' => array('AI Agents', 'AI Automation Tools'), 'audience' => array('service', 'Enterprise and business users', 'Operations Teams')),
        array('name' => 'Figure AI', 'official_url' => 'https://www.figure.ai/', 'summary' => 'Figure AI builds humanoid robot systems and AI robotics technology for general-purpose physical work.', 'category' => array('AI Robotics', 'AI Hardware'), 'audience' => array('Researchers', 'Enterprise and business users', 'operators')),
        array('name' => 'Physical Intelligence', 'official_url' => 'https://www.physicalintelligence.company/', 'summary' => 'Physical Intelligence develops AI systems for robots and physical-world tasks, with a focus on general-purpose robotic intelligence.', 'category' => array('AI Robotics', 'AI Models'), 'audience' => array('Researchers', 'AI Engineers', 'operators')),
        array('name' => 'Skild AI', 'official_url' => 'https://www.skild.ai/', 'summary' => 'Skild AI develops foundation models and AI systems for robotics and physical-world automation.', 'category' => array('AI Robotics', 'AI Models'), 'audience' => array('Researchers', 'AI Engineers', 'operators')),
        array('name' => 'Covariant', 'official_url' => 'https://covariant.ai/', 'summary' => 'Covariant builds AI robotics technology for warehouse automation, robotic manipulation, and logistics workflows.', 'category' => array('AI Robotics', 'AI Automation Tools'), 'audience' => array('operators', 'Enterprise and business users', 'AI Engineers')),
        array('name' => 'Anduril', 'official_url' => 'https://www.anduril.com/', 'summary' => 'Anduril builds defense technology, autonomous systems, sensing platforms, and AI-enabled products for national security and operational environments.', 'category' => array('AI Robotics', 'AI Hardware', 'AI Infrastructure'), 'audience' => array('public-sector teams', 'Enterprise and business users', 'operators')),
        array('name' => 'Applied Intuition', 'official_url' => 'https://www.appliedintuition.com/', 'summary' => 'Applied Intuition builds software and simulation infrastructure for autonomous vehicles, robotics, and AI-enabled physical systems.', 'category' => array('AI Robotics', 'AI Developer Tools'), 'audience' => array('AI Engineers', 'Product Teams', 'Enterprise and business users')),
        array('name' => 'Wayve', 'official_url' => 'https://wayve.ai/', 'summary' => 'Wayve develops embodied AI and autonomous driving technology for vehicles and physical-world mobility systems.', 'category' => array('AI Robotics', 'AI Models'), 'audience' => array('AI Engineers', 'Researchers', 'Enterprise and business users')),
        array('name' => 'Waabi', 'official_url' => 'https://waabi.ai/', 'summary' => 'Waabi develops AI systems, simulation technology, and autonomous trucking products for transportation and logistics.', 'category' => array('AI Robotics', 'AI Models'), 'audience' => array('AI Engineers', 'operators', 'Enterprise and business users')),
    );
}

function kingy_ali_link_launch_to_company($launch_id, $company_id) {
    $launch_id = absint($launch_id);
    $company_id = absint($company_id);
    if (!$launch_id || !$company_id) {
        return;
    }
    if (get_post_type($launch_id) !== 'kingy_ai_launch' || get_post_type($company_id) !== 'kingy_ai_company') {
        return;
    }

    update_post_meta($launch_id, kingy_ali_meta_key('related_company_id'), $company_id);
    if (get_post_status($launch_id) === 'publish' && get_post_status($company_id) !== 'publish') {
        wp_update_post(
            array(
                'ID' => $company_id,
                'post_status' => 'publish',
            )
        );
    }
}

function kingy_ali_link_tool_to_company($tool_id, $company_id) {
    $tool_id = absint($tool_id);
    $company_id = absint($company_id);
    if (!$tool_id || !$company_id) {
        return;
    }
    if (get_post_type($tool_id) !== 'kingy_ai_tool' || get_post_type($company_id) !== 'kingy_ai_company') {
        return;
    }

    update_post_meta($tool_id, kingy_ali_meta_key('related_company_id'), $company_id);
    if (get_post_status($tool_id) === 'publish' && get_post_status($company_id) !== 'publish') {
        wp_update_post(
            array(
                'ID' => $company_id,
                'post_status' => 'publish',
            )
        );
    }
}

function kingy_ali_sync_company_from_launch($launch_id) {
    $launch_id = absint($launch_id);
    $company_name = kingy_ali_company_meta_text($launch_id, 'company');
    if (!$launch_id || $company_name === '') {
        return 0;
    }

    $category_terms = get_the_terms($launch_id, 'kingy_launch_category');
    $audience_terms = get_the_terms($launch_id, 'kingy_audience');

    $company_id = kingy_ali_find_or_create_company(
        $company_name,
        array(
            'post_status' => get_post_status($launch_id) === 'publish' ? 'publish' : 'draft',
            'official_url' => kingy_ali_company_meta_text($launch_id, 'official_url'),
            'company_summary' => kingy_ali_company_meta_text($launch_id, 'what_launched'),
            'ai_evidence' => sprintf(
                __('This company profile is backed by a linked AI launch record: %s', 'kingy-ai-launch-intelligence'),
                kingy_ali_company_meta_text($launch_id, 'what_launched', get_the_title($launch_id))
            ),
            'buyer_notes' => sprintf(
                __('Review the linked launch for audience fit, pricing, demo quality, and source trail. Current audience note: %s', 'kingy-ai-launch-intelligence'),
                kingy_ali_company_meta_text($launch_id, 'who_it_is_for', __('Not specified', 'kingy-ai-launch-intelligence'))
            ),
            'founder_team' => kingy_ali_company_meta_text($launch_id, 'founder_team'),
            'funding' => kingy_ali_company_meta_text($launch_id, 'funding'),
            'founder_contact_email' => kingy_ali_company_meta_text($launch_id, 'founder_contact_email'),
            'outreach_status' => kingy_ali_company_meta_text($launch_id, 'outreach_status'),
            'sponsor_fit_score_internal' => kingy_ali_company_meta_text($launch_id, 'sponsor_fit_score_internal'),
            'budget_likelihood_internal' => kingy_ali_company_meta_text($launch_id, 'budget_likelihood_internal'),
            'sources' => kingy_ali_company_sources_from_related_post($launch_id, kingy_ali_company_meta_text($launch_id, 'official_url'), __('Official launch source', 'kingy-ai-launch-intelligence')),
            'source_notes' => sprintf(
                __('Company context synced from the linked launch record "%s"; verify product claims against the launch source links before relying on this profile.', 'kingy-ai-launch-intelligence'),
                get_the_title($launch_id)
            ),
            'verification_status' => kingy_ali_company_meta_text($launch_id, 'verification_status', 'partially_verified'),
            'last_verified' => kingy_ali_company_meta_text($launch_id, 'last_verified'),
            'category' => kingy_ali_company_term_slugs($category_terms),
            'audience' => kingy_ali_company_term_slugs($audience_terms),
        )
    );

    if ($company_id) {
        kingy_ali_link_launch_to_company($launch_id, $company_id);
        kingy_ali_sync_derived_attributes($company_id);
    }

    return $company_id;
}

function kingy_ali_sync_company_from_tool($tool_id) {
    $tool_id = absint($tool_id);
    $company_name = kingy_ali_company_meta_text($tool_id, 'company');
    if (!$tool_id || $company_name === '') {
        return 0;
    }

    $category_terms = get_the_terms($tool_id, 'kingy_launch_category');
    $audience_terms = get_the_terms($tool_id, 'kingy_audience');

    $company_id = kingy_ali_find_or_create_company(
        $company_name,
        array(
            'post_status' => get_post_status($tool_id) === 'publish' ? 'publish' : 'draft',
            'official_url' => kingy_ali_company_meta_text($tool_id, 'official_url'),
            'company_summary' => kingy_ali_company_meta_text($tool_id, 'what_it_does'),
            'ai_evidence' => sprintf(
                __('This company profile is backed by a linked AI tool record: %s', 'kingy-ai-launch-intelligence'),
                kingy_ali_company_meta_text($tool_id, 'what_it_does', get_the_title($tool_id))
            ),
            'buyer_notes' => sprintf(
                __('Review the linked tool profile for product fit, pricing, API availability, alternatives, and launch history. Current best-fit note: %s', 'kingy-ai-launch-intelligence'),
                kingy_ali_company_meta_text($tool_id, 'best_for', __('Not specified', 'kingy-ai-launch-intelligence'))
            ),
            'sources' => kingy_ali_company_sources_from_related_post($tool_id, kingy_ali_company_meta_text($tool_id, 'official_url'), __('Official tool source', 'kingy-ai-launch-intelligence')),
            'source_notes' => sprintf(
                __('Company context synced from the linked tool profile "%s"; verify product claims against the tool source links before relying on this profile.', 'kingy-ai-launch-intelligence'),
                get_the_title($tool_id)
            ),
            'verification_status' => 'partially_verified',
            'last_verified' => kingy_ali_company_meta_text($tool_id, 'last_verified'),
            'category' => kingy_ali_company_term_slugs($category_terms),
            'audience' => kingy_ali_company_term_slugs($audience_terms),
        )
    );

    if ($company_id) {
        kingy_ali_link_tool_to_company($tool_id, $company_id);
        kingy_ali_sync_derived_attributes($company_id);
    }

    return $company_id;
}

function kingy_ali_set_company_terms($company_id, $terms, $taxonomy) {
    if (!is_array($terms)) {
        $terms = array_filter(array_map('trim', explode(',', (string) $terms)));
    }

    $slugs = array();
    foreach ($terms as $term) {
        $slug = sanitize_title($term);
        if ($slug === '') {
            continue;
        }

        if (!term_exists($slug, $taxonomy)) {
            wp_insert_term($term, $taxonomy, array('slug' => $slug));
        }
        $slugs[] = $slug;
    }

    if ($slugs) {
        wp_set_object_terms($company_id, $slugs, $taxonomy, false);
    }
}

function kingy_ali_company_term_slugs($terms) {
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return wp_list_pluck($terms, 'slug');
}

function kingy_ali_query_company_launches($company_id, $limit = 10) {
    $limit = absint($limit);
    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'meta_key' => kingy_ali_meta_key('launch_date'),
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('related_company_id'),
                'value' => absint($company_id),
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        ),
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    return kingy_ali_run_public_filtered_query($query_args, $limit, 'kingy_ali_public_query_accepts_index_ready_post');
}

function kingy_ali_query_company_tools($company_id, $limit = 12) {
    $limit = absint($limit);
    $query_args = array(
        'post_type' => 'kingy_ai_tool',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('related_company_id'),
                'value' => absint($company_id),
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        ),
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    return kingy_ali_run_public_filtered_query($query_args, $limit, 'kingy_ali_public_query_accepts_index_ready_post');
}

function kingy_ali_company_related_count($company_id, $post_type) {
    static $cache = array();

    $company_id = absint($company_id);
    $post_type = sanitize_key($post_type);
    $cache_key = $company_id . ':' . $post_type;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key('related_company_id'),
                    'value' => $company_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ),
            ),
        )
    );

    $cache[$cache_key] = (int) $query->found_posts;
    return $cache[$cache_key];
}

function kingy_ali_company_public_related_count($company_id, $post_type) {
    static $cache = array();

    $company_id = absint($company_id);
    $post_type = sanitize_key($post_type);
    $cache_key = $company_id . ':' . $post_type;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $query_args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size(0),
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('related_company_id'),
                'value' => $company_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        ),
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);
    $query = kingy_ali_run_public_filtered_query($query_args, 0, 'kingy_ali_public_query_accepts_index_ready_post');

    $cache[$cache_key] = (int) $query->post_count;
    return $cache[$cache_key];
}

function kingy_ali_company_creator_coverage_signal_slugs() {
    return array(
        'creator-coverage-candidate',
        'sponsor-candidate',
        'strong-demo',
        'video-demo-available',
        'creator-friendly',
        'business-friendly',
        'developer-friendly',
        'product-hunt-traction',
        'funding-announced',
    );
}

function kingy_ali_company_has_creator_coverage_signal($company_id) {
    $company_id = absint($company_id);
    if (!$company_id || get_post_type($company_id) !== 'kingy_ai_company') {
        return false;
    }

    if (
        kingy_ali_has_any_term_slug($company_id, 'kingy_tool_attribute', kingy_ali_company_creator_coverage_signal_slugs())
        || kingy_ali_company_number_value(kingy_ali_get_meta($company_id, 'sponsor_fit_score_internal')) >= 7
        || kingy_ali_company_meta_text($company_id, 'outreach_status') === 'sponsor_candidate'
        || kingy_ali_company_meta_text($company_id, 'budget_likelihood_internal') === 'high'
    ) {
        return true;
    }

    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size(1),
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => kingy_ali_meta_key('related_company_id'),
                'value' => $company_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
            array(
                'relation' => 'OR',
                array(
                    'key' => kingy_ali_meta_key('youtube_score'),
                    'value' => 7,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => kingy_ali_meta_key('demo_quality_score'),
                    'value' => 7,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => kingy_ali_meta_key('sponsor_fit_score_internal'),
                    'value' => 7,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => kingy_ali_meta_key('creator_coverage_interest'),
                    'value' => 'yes',
                    'compare' => '=',
                ),
                array(
                    'key' => kingy_ali_meta_key('sponsorship_interest'),
                    'value' => 'yes',
                    'compare' => '=',
                ),
                array(
                    'key' => kingy_ali_meta_key('youtube_interest'),
                    'value' => 'yes',
                    'compare' => '=',
                ),
            ),
        ),
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);
    $query = kingy_ali_run_public_filtered_query($query_args, 1, 'kingy_ali_public_query_accepts_index_ready_post');

    return $query->post_count > 0;
}
