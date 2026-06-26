<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wpseo_metadesc', 'kingy_ali_codex_wpseo_meta_description', 20);
add_filter('wpseo_opengraph_desc', 'kingy_ali_codex_wpseo_social_description', 20);
add_filter('wpseo_twitter_description', 'kingy_ali_codex_wpseo_social_description', 20);
add_filter('the_content', 'kingy_ali_fix_codex_legacy_static_links', 23);
add_filter('the_content', 'kingy_ali_normalize_codex_support_hero_h1', 24);
add_filter('the_content', 'kingy_ali_fix_codex_zero_to_hero_module_links', 25);
add_filter('the_content', 'kingy_ali_add_codex_internal_link_cluster', 26);

function kingy_ali_is_codex_zero_to_hero_page() {
    return function_exists('kingy_ali_is_rendering_page_slug') && kingy_ali_is_rendering_page_slug('codex-zero-to-hero');
}

function kingy_ali_codex_slug_to_key_map() {
    return array(
        'codex-zero-to-hero' => 'course',
        'start-here-codex-for-beginners' => 'start_here',
        'codex-course-for-beginners' => 'beginner_course',
        'codex-for-beginners' => 'beginner_guide',
        'codex-prompt-library' => 'prompt_library',
        'codex-prompt-builder' => 'prompt_builder',
        'codex-glossary' => 'glossary',
        'codex-goal-prompts' => 'goal_prompts',
        'codex-for-wordpress' => 'wordpress',
        'codex-github-vercel-guide' => 'github_vercel',
        'codex-cli-guide' => 'cli',
        'codex-cloud-tasks-guide' => 'cloud_tasks',
        'codex-agent-workflows' => 'agent_workflows',
    );
}

function kingy_ali_codex_page_key_from_queried_object() {
    if (is_admin() || !is_singular('page')) {
        return '';
    }

    $post_id = (int) get_queried_object_id();
    if (!$post_id) {
        return '';
    }

    $page = get_post($post_id);
    if (!$page || $page->post_type !== 'page') {
        return '';
    }

    $slug = (string) $page->post_name;
    $path = trim((string) get_page_uri($post_id), '/');
    $slug_to_key = kingy_ali_codex_slug_to_key_map();

    if (isset($slug_to_key[$slug])) {
        return $slug_to_key[$slug];
    }

    if (strpos($path, 'codex-zero-to-hero/') === 0) {
        return 'course_child';
    }

    return '';
}

function kingy_ali_codex_meta_description_map() {
    return array(
        'beginner_course' => __('Learn how to use OpenAI Codex as a beginner with practical prompts, WordPress Custom HTML tools, project ideas, debugging, review, and safe publishing habits.', 'kingy-ai-launch-intelligence'),
        'beginner_guide' => __('A beginner-friendly Codex guide covering plain-English concepts, safe prompts, review habits, first projects, and practical AI coding workflows.', 'kingy-ai-launch-intelligence'),
        'glossary' => __('Look up 200+ beginner-friendly Codex, coding, GitHub, Vercel, WordPress, web development, QA, security, and AI agent terms.', 'kingy-ai-launch-intelligence'),
        'wordpress' => __('Use Codex for WordPress pages, scoped Custom HTML blocks, calculators, SEO tools, safety checks, QA, and owner-friendly publishing.', 'kingy-ai-launch-intelligence'),
        'github_vercel' => __('Learn how Codex, GitHub, and Vercel fit together for issues, branches, pull requests, preview deployments, QA, and rollbacks.', 'kingy-ai-launch-intelligence'),
        'cli' => __('Use the Codex CLI for local AI coding workflows with safe approvals, sandbox awareness, scripted tasks, testing, and rollback notes.', 'kingy-ai-launch-intelligence'),
        'cloud_tasks' => __('Plan Codex cloud and parallel tasks with clear scopes, status updates, merge review, conflict avoidance, and safer long-running workflows.', 'kingy-ai-launch-intelligence'),
        'agent_workflows' => __('Learn Codex agent workflows with skills, MCP, subagents, evaluation loops, safety boundaries, human approval, and production-readiness habits.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_codex_wpseo_meta_description($description) {
    if (trim((string) $description) !== '') {
        return $description;
    }

    $current_key = kingy_ali_codex_page_key_from_queried_object();
    $descriptions = kingy_ali_codex_meta_description_map();

    return isset($descriptions[$current_key]) ? $descriptions[$current_key] : $description;
}

function kingy_ali_codex_wpseo_social_description($description) {
    if (trim((string) $description) !== '') {
        return $description;
    }

    return kingy_ali_codex_wpseo_meta_description($description);
}

function kingy_ali_fix_codex_legacy_static_links($content) {
    $current_key = kingy_ali_codex_current_cluster_key();
    if ($current_key === '' || stripos($content, '.html') === false) {
        return $content;
    }

    $legacy_links = array(
        'codex-course-hub.html' => home_url('/codex-zero-to-hero/'),
        'start-here-codex-for-beginners.html' => home_url('/codex-zero-to-hero/start-here-codex-for-beginners/'),
        'codex-prompt-library.html' => home_url('/codex-prompt-library/'),
        'codex-glossary.html' => home_url('/codex-glossary/'),
        'codex-learning-path.html' => home_url('/codex-learning-path/'),
    );

    foreach ($legacy_links as $legacy_href => $live_url) {
        $content = preg_replace(
            '~href=(["\'])' . preg_quote($legacy_href, '~') . '\1~i',
            'href="' . esc_url($live_url) . '"',
            $content
        );
    }

    return $content;
}

function kingy_ali_normalize_codex_support_hero_h1($content) {
    $current_key = kingy_ali_codex_current_cluster_key();
    $support_pages_with_theme_title = array(
        'prompt_library',
        'glossary',
        'wordpress',
        'github_vercel',
        'cli',
        'cloud_tasks',
        'agent_workflows',
    );

    if (!in_array($current_key, $support_pages_with_theme_title, true) || strpos($content, 'kc-hero') === false || stripos($content, '<h1') === false) {
        return $content;
    }

    $content = preg_replace_callback(
        '~<section\b[^>]*class=(["\'])(?=[^"\']*\bkc-hero\b)[^"\']*\1[^>]*>[\s\S]*?</section>~i',
        function ($section_matches) {
            return preg_replace(
                '~<h1\b([^>]*)>([\s\S]*?)</h1>~i',
                '<h2$1 class="kc-seo-hero-title">$2</h2>',
                $section_matches[0],
                1
            );
        },
        $content,
        1
    );

    if (strpos($content, 'kingy-codex-seo-hero-title-fix') !== false) {
        return $content;
    }

    return '<style id="kingy-codex-seo-hero-title-fix">.kingy-codex-course .kc-hero .kc-seo-hero-title{color:#fff;font-size:clamp(2.5rem,6vw,5.4rem);font-weight:900;line-height:.98;margin:0 0 20px;max-width:920px}.kingy-codex-course .kc-hero .kc-seo-hero-title+*{margin-top:0}@media(max-width:860px){.kingy-codex-course .kc-hero .kc-seo-hero-title{font-size:clamp(2rem,11vw,3.4rem)}}</style>' . $content;
}

function kingy_ali_fix_codex_zero_to_hero_module_links($content) {
    if (!kingy_ali_is_codex_zero_to_hero_page() || strpos($content, 'kingy-codex-course') === false) {
        return $content;
    }

    $content = preg_replace_callback(
        '~href=(["\'])(?:https://kingy\.ai/codex-zero-to-hero/)?module-(\d{2})[^"\']*?(?:\.html|/)\1~i',
        function ($matches) {
            $module_number = preg_replace('/[^0-9]/', '', $matches[2]);
            $module_id = 'codex-module-' . $module_number;

            return 'href="#' . esc_attr($module_id) . '" data-kc-module-toggle aria-expanded="false" aria-controls="' . esc_attr($module_id) . '-panel"';
        },
        $content
    );

    if (strpos($content, 'kingy-codex-module-hotfix') !== false) {
        return $content;
    }

    return $content . kingy_ali_codex_zero_to_hero_module_hotfix_markup();
}

function kingy_ali_codex_internal_link_assets() {
    return array(
        'course' => array(
            'label' => __('Codex Zero to Hero course', 'kingy-ai-launch-intelligence'),
            'description' => __('Use the main course as the pillar path for Codex, GitHub, Vercel, CLI, cloud tasks, and agent workflows.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-zero-to-hero/'),
        ),
        'start_here' => array(
            'label' => __('Start with the beginner path', 'kingy-ai-launch-intelligence'),
            'description' => __('Begin the Zero to Hero sequence with the first plain-English orientation page.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-zero-to-hero/start-here-codex-for-beginners/'),
        ),
        'beginner_course' => array(
            'label' => __('OpenAI Codex Course for Beginners', 'kingy-ai-launch-intelligence'),
            'description' => __('Practice scoped prompts, review loops, WordPress-safe tools, debugging, and beginner projects.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-course-for-beginners/'),
        ),
        'beginner_guide' => array(
            'label' => __('Codex beginner guide', 'kingy-ai-launch-intelligence'),
            'description' => __('Learn the core mental model before moving into deeper course modules.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-for-beginners/'),
        ),
        'prompt_library' => array(
            'label' => __('Codex prompt library', 'kingy-ai-launch-intelligence'),
            'description' => __('Copy reusable prompts for planning, building, debugging, QA, and shipping work.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-prompt-library/'),
        ),
        'prompt_builder' => array(
            'label' => __('Codex Prompt Builder', 'kingy-ai-launch-intelligence'),
            'description' => __('Turn a rough task into a scoped implementation prompt with constraints and verification.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/'),
        ),
        'glossary' => array(
            'label' => __('Codex glossary', 'kingy-ai-launch-intelligence'),
            'description' => __('Look up Codex, GitHub, Vercel, WordPress, web, safety, and agent workflow terms.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-glossary/'),
        ),
        'goal_prompts' => array(
            'label' => __('Codex /goal prompt examples', 'kingy-ai-launch-intelligence'),
            'description' => __('Use durable task prompts when Codex needs to keep working against clear done criteria.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-goal-prompts/'),
        ),
        'wordpress' => array(
            'label' => __('Use Codex with WordPress', 'kingy-ai-launch-intelligence'),
            'description' => __('Build safer Custom HTML blocks, calculators, prompt libraries, and page tools.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-for-wordpress/'),
        ),
        'github_vercel' => array(
            'label' => __('Codex with GitHub and Vercel', 'kingy-ai-launch-intelligence'),
            'description' => __('Connect repo review, branches, pull requests, preview deployments, and rollback habits.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-github-vercel-guide/'),
        ),
        'cli' => array(
            'label' => __('Codex CLI guide', 'kingy-ai-launch-intelligence'),
            'description' => __('Learn local terminal workflows, config, model choices, commands, and safer repo work.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-cli-guide/'),
        ),
        'cloud_tasks' => array(
            'label' => __('Codex cloud tasks guide', 'kingy-ai-launch-intelligence'),
            'description' => __('Plan longer-running tasks, parallel work, review gates, and completion checks.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-cloud-tasks-guide/'),
        ),
        'agent_workflows' => array(
            'label' => __('Codex agent workflows', 'kingy-ai-launch-intelligence'),
            'description' => __('Move from single prompts to repeatable workflows with skills, MCP, subagents, and QA loops.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-agent-workflows/'),
        ),
    );
}

function kingy_ali_codex_current_cluster_key() {
    if (is_admin() || !is_singular('page')) {
        return '';
    }

    if (!in_the_loop() || !is_main_query()) {
        return '';
    }

    return kingy_ali_codex_page_key_from_queried_object();
}

function kingy_ali_codex_cluster_related_keys($current_key) {
    if ($current_key === 'course') {
        return array(
            'start_here',
            'beginner_course',
            'beginner_guide',
            'prompt_library',
            'glossary',
            'goal_prompts',
            'wordpress',
            'github_vercel',
            'cli',
            'cloud_tasks',
            'agent_workflows',
            'prompt_builder',
        );
    }

    $map = array(
        'start_here' => array('course', 'beginner_guide', 'beginner_course', 'prompt_library', 'glossary'),
        'beginner_course' => array('course', 'start_here', 'beginner_guide', 'prompt_library', 'wordpress', 'glossary'),
        'beginner_guide' => array('course', 'start_here', 'beginner_course', 'prompt_library', 'glossary'),
        'prompt_library' => array('course', 'prompt_builder', 'goal_prompts', 'wordpress', 'glossary'),
        'prompt_builder' => array('course', 'prompt_library', 'goal_prompts', 'wordpress', 'github_vercel'),
        'glossary' => array('course', 'start_here', 'beginner_guide', 'cli', 'agent_workflows'),
        'goal_prompts' => array('course', 'prompt_library', 'agent_workflows', 'cloud_tasks', 'cli'),
        'wordpress' => array('course', 'prompt_library', 'prompt_builder', 'glossary', 'github_vercel'),
        'github_vercel' => array('course', 'cli', 'cloud_tasks', 'agent_workflows', 'glossary'),
        'cli' => array('course', 'github_vercel', 'cloud_tasks', 'goal_prompts', 'glossary'),
        'cloud_tasks' => array('course', 'cli', 'goal_prompts', 'agent_workflows', 'github_vercel'),
        'agent_workflows' => array('course', 'goal_prompts', 'cloud_tasks', 'cli', 'glossary'),
        'course_child' => array('course', 'prompt_library', 'glossary', 'goal_prompts', 'github_vercel', 'agent_workflows'),
    );

    return isset($map[$current_key]) ? $map[$current_key] : array();
}

function kingy_ali_render_codex_internal_link_cluster($current_key) {
    $assets = kingy_ali_codex_internal_link_assets();
    $related_keys = kingy_ali_codex_cluster_related_keys($current_key);

    if (!$related_keys) {
        return '';
    }

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-codex-internal-link-cluster" data-kingy-codex-cluster aria-labelledby="kingy-codex-related-resources-title">
        <p class="kingy-ali-kicker"><?php esc_html_e('Codex learning hub', 'kingy-ai-launch-intelligence'); ?></p>
        <h2 id="kingy-codex-related-resources-title"><?php esc_html_e('Related Codex resources', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php echo $current_key === 'course' ? esc_html__('Use these supporting guides and tools when you need a focused reference while working through Codex Zero to Hero.', 'kingy-ai-launch-intelligence') : esc_html__('Use the main Codex course as the pillar path, then move into the most relevant supporting resource for your next task.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-codex-resource-grid">
            <?php foreach ($related_keys as $key) : ?>
                <?php if (empty($assets[$key]['url'])) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url($assets[$key]['url']); ?>" data-kingy-ali-track="clicked_codex_internal_resource" data-event-label="<?php echo esc_attr($assets[$key]['label']); ?>" data-event-surface="codex_internal_link_cluster">
                    <strong><?php echo esc_html($assets[$key]['label']); ?></strong>
                    <span><?php echo esc_html($assets[$key]['description']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_add_codex_internal_link_cluster($content) {
    $current_key = kingy_ali_codex_current_cluster_key();
    if ($current_key === '' || strpos($content, 'kingy-codex-internal-link-cluster') !== false) {
        return $content;
    }

    return $content . kingy_ali_render_codex_internal_link_cluster($current_key);
}

function kingy_ali_codex_zero_to_hero_module_hotfix_markup() {
    ob_start();
    ?>
    <style id="kingy-codex-module-hotfix">
    .kingy-codex-course .kc-module-card.is-kc-collapsed .kc-module-card__body[hidden] {
        display: none !important;
    }
    .kingy-codex-course .kc-module-card__body {
        margin-top: 12px;
    }
    .kingy-codex-course .kc-module-card[data-kc-module-ready="1"] .kc-button {
        cursor: pointer;
        margin-top: 10px;
    }
    .kingy-codex-course .kc-continue-learning {
        background: rgba(124, 244, 189, 0.08);
        border: 1px solid rgba(124, 244, 189, 0.22);
        border-radius: 8px;
        margin-top: 14px;
        padding: 14px;
    }
    .kingy-codex-course .kc-continue-learning h4 {
        color: #ffffff;
        font-size: 1rem;
        margin: 0 0 8px;
    }
    .kingy-codex-course .kc-continue-learning p {
        color: var(--kc-muted, #b9c3d6);
        margin: 0 0 10px;
    }
    .kingy-codex-course .kc-continue-learning ul {
        display: grid;
        gap: 8px;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .kingy-codex-course .kc-continue-learning a {
        color: var(--kc-accent-3, #7cf4bd);
        font-weight: 800;
    }
    </style>
    <script>
    (function () {
        var relatedResources = {
            course: {
                label: "Codex Zero to Hero course",
                url: "<?php echo esc_js(home_url('/codex-zero-to-hero/')); ?>"
            },
            start: {
                label: "Start Here: Codex for Beginners",
                url: "<?php echo esc_js(home_url('/codex-zero-to-hero/start-here-codex-for-beginners/')); ?>"
            },
            beginner: {
                label: "Codex beginner guide",
                url: "<?php echo esc_js(home_url('/codex-for-beginners/')); ?>"
            },
            prompts: {
                label: "Codex prompt library",
                url: "<?php echo esc_js(home_url('/codex-prompt-library/')); ?>"
            },
            glossary: {
                label: "Codex glossary",
                url: "<?php echo esc_js(home_url('/codex-glossary/')); ?>"
            },
            goal: {
                label: "Codex /goal prompt examples",
                url: "<?php echo esc_js(home_url('/codex-goal-prompts/')); ?>"
            },
            wordpress: {
                label: "Codex for WordPress",
                url: "<?php echo esc_js(home_url('/codex-for-wordpress/')); ?>"
            },
            github: {
                label: "GitHub and Vercel workflow guide",
                url: "<?php echo esc_js(home_url('/codex-github-vercel-guide/')); ?>"
            },
            cli: {
                label: "Codex CLI guide",
                url: "<?php echo esc_js(home_url('/codex-cli-guide/')); ?>"
            },
            cloud: {
                label: "Codex cloud tasks guide",
                url: "<?php echo esc_js(home_url('/codex-cloud-tasks-guide/')); ?>"
            },
            workflows: {
                label: "Codex agent workflows",
                url: "<?php echo esc_js(home_url('/codex-agent-workflows/')); ?>"
            }
        };

        function moduleRelatedKeys(title) {
            var normalized = (title || "").toLowerCase();

            if (normalized.indexOf("wordpress") !== -1 || normalized.indexOf("landing") !== -1) {
                return ["wordpress", "prompts", "glossary"];
            }

            if (normalized.indexOf("github") !== -1 || normalized.indexOf("vercel") !== -1 || normalized.indexOf("deployment") !== -1) {
                return ["github", "cli", "cloud"];
            }

            if (normalized.indexOf("cli") !== -1 || normalized.indexOf("terminal") !== -1 || normalized.indexOf("config") !== -1) {
                return ["cli", "goal", "cloud"];
            }

            if (normalized.indexOf("cloud") !== -1 || normalized.indexOf("parallel") !== -1 || normalized.indexOf("long-running") !== -1) {
                return ["cloud", "goal", "workflows"];
            }

            if (normalized.indexOf("agent") !== -1 || normalized.indexOf("mcp") !== -1 || normalized.indexOf("skill") !== -1 || normalized.indexOf("workflow") !== -1) {
                return ["workflows", "goal", "glossary"];
            }

            if (normalized.indexOf("prompt") !== -1 || normalized.indexOf("goal") !== -1) {
                return ["prompts", "goal", "glossary"];
            }

            return ["start", "beginner", "glossary"];
        }

        function addContinueLearning(card, panel) {
            if (!card || !panel || panel.querySelector(".kc-continue-learning")) {
                return;
            }

            var title = card.querySelector("h3");
            var keys = moduleRelatedKeys(title ? title.textContent : "");
            var block = document.createElement("div");
            var heading = document.createElement("h4");
            var intro = document.createElement("p");
            var list = document.createElement("ul");

            block.className = "kc-continue-learning";
            heading.textContent = "Continue learning";
            intro.textContent = "Use these related Codex resources when this module turns into a real build or review task.";

            keys.forEach(function (key) {
                var resource = relatedResources[key];
                var item;
                var link;

                if (!resource || !resource.url) {
                    return;
                }

                item = document.createElement("li");
                link = document.createElement("a");
                link.href = resource.url;
                link.textContent = resource.label;
                item.appendChild(link);
                list.appendChild(item);
            });

            if (!list.children.length) {
                return;
            }

            block.appendChild(heading);
            block.appendChild(intro);
            block.appendChild(list);
            panel.appendChild(block);
        }

        function moduleNumberFromCard(card, fallback) {
            var pill = card.querySelector('.kc-pill');
            var match = pill && pill.textContent ? pill.textContent.match(/Module\s*(\d{2})/i) : null;
            return match ? match[1] : String(fallback).padStart(2, '0');
        }

        function toggleModule(card, button, panel, shouldOpen) {
            var isOpen = typeof shouldOpen === 'boolean' ? shouldOpen : button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            button.textContent = isOpen ? 'Close module' : 'Open module';
            card.classList.toggle('is-kc-expanded', isOpen);
            card.classList.toggle('is-kc-collapsed', !isOpen);
            panel.hidden = !isOpen;
        }

        function initCodexModules() {
            var root = document.querySelector('.kingy-codex-course');
            if (!root) {
                return;
            }

            Array.prototype.slice.call(root.querySelectorAll('.kc-module-card')).forEach(function (card, index) {
                if (card.getAttribute('data-kc-module-ready') === '1') {
                    return;
                }

                var moduleNumber = moduleNumberFromCard(card, index);
                var moduleId = 'codex-module-' + moduleNumber;
                var panelId = moduleId + '-panel';
                var button = card.querySelector('[data-kc-module-toggle]') || card.querySelector('a.kc-button[href*="module-"]') || card.querySelector('a.kc-button[href^="#codex-module-"]');
                var title = card.querySelector('h3');
                var pill = card.querySelector('.kc-pill');
                var panel = document.createElement('div');

                if (!button) {
                    return;
                }

                card.id = moduleId;
                card.setAttribute('data-kc-module-ready', '1');
                panel.id = panelId;
                panel.className = 'kc-module-card__body';

                Array.prototype.slice.call(card.children).forEach(function (child) {
                    if (child === pill || child === title || child === button) {
                        return;
                    }

                    panel.appendChild(child);
                });

                button.setAttribute('href', '#' + moduleId);
                button.setAttribute('role', 'button');
                button.setAttribute('aria-controls', panelId);
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('data-kc-module-toggle', '');

                card.insertBefore(panel, button);
                addContinueLearning(card, panel);
                toggleModule(card, button, panel, false);

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    toggleModule(card, button, panel);
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCodexModules);
        } else {
            initCodexModules();
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
