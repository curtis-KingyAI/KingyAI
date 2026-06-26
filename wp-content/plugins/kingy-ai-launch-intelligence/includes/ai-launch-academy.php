<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_ai_launch_academy', 'kingy_ali_shortcode_ai_launch_academy');
add_action('wp_head', 'kingy_ali_ai_launch_academy_meta_description', 6);
add_action('wp_head', 'kingy_ali_ai_launch_academy_schema', 8);
add_filter('body_class', 'kingy_ali_ai_launch_academy_body_class', 20);

function kingy_ali_ai_launch_academy_base_path() {
    return 'ai-launch-academy';
}

function kingy_ali_ai_launch_academy_page_slug($path) {
    $parts = explode('/', trim((string) $path, '/'));
    return sanitize_title(end($parts));
}

function kingy_ali_ai_launch_academy_lessons() {
    static $lessons = null;

    if ($lessons !== null) {
        return $lessons;
    }

    $lessons = array(
        array(
            'number' => 1,
            'slug' => 'lesson-1-what-is-ai-launch-intelligence',
            'module' => 'Module 1: How to Read an AI Launch',
            'title' => 'What Is AI Launch Intelligence?',
            'time' => '25 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Create a Launch Snapshot that separates what happened from what it means.',
            'intro' => 'AI launch intelligence is the discipline of turning new AI product noise into a practical decision. News tells you what happened. Launch intelligence tells you what to do with it.',
            'sections' => array(
                array(
                    'heading' => 'What counts as an AI launch?',
                    'body' => array(
                        'A launch can be a new AI tool, model release, agent, feature, startup, open-weight project, API, research demo, pricing change, public beta, funding announcement, or major product update.',
                        'The first job is to identify the launch type. A working SaaS tool should not be judged like a research paper. A funding announcement should not be treated like a product people can use today.',
                    ),
                    'items' => array('Product launch', 'Feature launch', 'Model launch', 'Agent launch', 'Open-source or open-weight release', 'Research demo', 'Funding or company announcement'),
                ),
                array(
                    'heading' => 'Why this matters',
                    'body' => array(
                        'Most people react to launches emotionally: excitement, skepticism, fear of missing out, or dismissal. Analysts slow the reaction down long enough to check the facts.',
                        'Speed still matters. The goal is not to wait six months. The goal is to evaluate quickly without confusing claims, demos, and real availability.',
                    ),
                ),
                array(
                    'heading' => 'Example pattern, not a real launch',
                    'body' => array(
                        'Example: A startup announces an AI research assistant for sales teams. Before calling it useful, you would check whether it is live, who can access it, what sources it uses, what it costs, and whether it solves a narrow workflow better than existing tools.',
                    ),
                ),
            ),
            'tip' => 'A launch is not automatically important because the company says it is. Look for availability, pricing, demo quality, user value, and comparison against existing tools.',
            'red_flag' => 'If the only evidence is a vague social post and a waitlist page, treat the launch as unverified until better sources exist.',
            'exercise' => 'Pick one recent launch from the Kingy AI launch database. Answer: What launched? Who launched it? When did it launch? What category is it in? Is it available now? Who is it for? Why might it matter?',
            'deliverable' => 'Launch Snapshot',
            'quiz' => array(
                array('question' => 'A company announces a funding round but no usable product update. Is that a product launch?', 'answer' => 'No. It is a company or funding announcement unless it includes a new product, feature, model, API, or availability change.'),
                array('question' => 'What is the difference between AI news and AI launch intelligence?', 'answer' => 'AI news reports what happened. AI launch intelligence helps decide whether the launch is real, useful, testable, worth covering, worth buying, or safe to ignore.'),
                array('question' => 'Why should a research demo be judged differently from a live SaaS app?', 'answer' => 'A research demo may prove a capability, but a SaaS app needs access, reliability, onboarding, pricing, support, and real user fit.'),
            ),
            'do_now' => 'Open one Kingy launch page and write a seven-line Launch Snapshot before reading any outside opinions.',
            'links' => array('ai-launches', 'ai-launches/today', 'ai-courses'),
        ),
        array(
            'number' => 2,
            'slug' => 'lesson-2-how-to-read-an-ai-launch',
            'module' => 'Module 1: How to Read an AI Launch',
            'title' => 'The Anatomy of an AI Launch',
            'time' => '30 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Fill in an AI Launch Anatomy Card for one launch.',
            'intro' => 'Every launch has a small set of facts that decide whether it is worth more attention. If those facts are missing, your verdict should stay cautious.',
            'sections' => array(
                array(
                    'heading' => 'The core anatomy',
                    'body' => array('A useful launch brief should make the basics obvious. If you cannot identify the source, category, access model, target user, and pricing, you do not have enough signal yet.'),
                    'items' => array('Launch date', 'Company or founder', 'Product category', 'Official announcement', 'Product homepage', 'Pricing', 'Free plan or trial', 'API availability', 'Open-source or open-weight status', 'Waitlist vs live product', 'Demo quality', 'Docs', 'Use cases', 'Integrations', 'Traction signals', 'Comparison targets'),
                ),
                array(
                    'heading' => 'Kingy Launch Anatomy Checklist',
                    'body' => array('Use this checklist when a launch page feels exciting but incomplete. The blank fields are often more revealing than the filled fields.'),
                    'items' => array('Name', 'Company', 'Launch date', 'Category', 'Official source', 'Product URL', 'Pricing', 'Free plan', 'API', 'Open-source/open-weight', 'Demo', 'Target user', 'Main use case', 'Best alternative', 'Why it matters', 'What feels unproven', 'Kingy verdict'),
                ),
                array(
                    'heading' => 'How to read gaps',
                    'body' => array('Missing pricing does not automatically mean a product is bad. It means the buyer decision is incomplete. Missing docs may be fine for a consumer app but risky for a developer tool.'),
                ),
            ),
            'tip' => 'The anatomy card protects you from rating a launch on vibes. It forces the same questions across tools, models, agents, and startups.',
            'red_flag' => 'If a product claims broad enterprise readiness but has no docs, no pricing, no security notes, and no clear buyer, slow down.',
            'exercise' => 'Take one launch from the database and fill in every field of the anatomy checklist. Mark unknown fields as Unknown rather than guessing.',
            'deliverable' => 'AI Launch Anatomy Card',
            'quiz' => array(
                array('question' => 'Should you invent pricing if a page does not show it?', 'answer' => 'No. Mark pricing as unknown, unclear, waitlist-only, sales-led, or not published.'),
                array('question' => 'Which field helps you avoid evaluating a tool in isolation?', 'answer' => 'Best alternative. Every launch should be compared against an existing workflow or competitor.'),
                array('question' => 'Why does API availability matter?', 'answer' => 'It changes who can use the product, how deeply it can be integrated, and whether developers can build on top of it.'),
            ),
            'do_now' => 'Create one anatomy card and leave unknown fields blank instead of filling them with assumptions.',
            'links' => array('ai-launches', 'ai-tools', 'ai-companies'),
        ),
        array(
            'number' => 3,
            'slug' => 'lesson-3-ai-launch-categories',
            'module' => 'Module 1: How to Read an AI Launch',
            'title' => 'Understanding AI Launch Categories',
            'time' => '30 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Build a Launch Category Map for ten launches.',
            'intro' => 'Category is the first lens for fair judgment. You cannot test an AI video model the same way you test a coding agent, and you cannot compare a local model to a cloud-only productivity app without adjusting expectations.',
            'sections' => array(
                array(
                    'heading' => 'Common AI launch categories',
                    'body' => array('Use the smallest accurate category. A broad label like AI platform is usually less useful than AI coding assistant, AI search tool, or open-weight model.'),
                    'items' => array('AI agents', 'AI coding tools', 'AI video tools', 'AI image tools', 'AI search tools', 'AI research tools', 'Productivity tools', 'Voice/audio tools', 'AI browsers', 'AI automation tools', 'AI customer support tools', 'Model releases', 'Open-weight/local models', 'AI infrastructure', 'Developer tools', 'AI hardware', 'Business/enterprise AI', 'Consumer AI apps'),
                ),
                array(
                    'heading' => 'Why category changes the test',
                    'body' => array(
                        'An AI video launch needs motion quality, prompt following, visual consistency, usage limits, and commercial usefulness.',
                        'An AI coding tool needs repo understanding, test discipline, diff quality, setup clarity, and ability to avoid unrelated changes.',
                        'A model release needs modalities, reasoning, coding, context, API access, pricing, geography, and deployment options.',
                    ),
                ),
            ),
            'tip' => 'When a product spans multiple categories, name the primary user job first. Category should help testing, not win a taxonomy argument.',
            'red_flag' => 'A launch that refuses to name its category may be hiding unclear positioning.',
            'exercise' => 'Classify ten launches. For each one, add one sentence explaining why the category fits.',
            'deliverable' => 'Launch Category Map',
            'quiz' => array(
                array('question' => 'Why should an AI video tool not be judged like an AI coding agent?', 'answer' => 'The success criteria are different. Video needs visual coherence and motion; coding needs codebase understanding, tests, and safe edits.'),
                array('question' => 'What should you do when a product fits two categories?', 'answer' => 'Pick the category that matches the main user job, then note the secondary category.'),
                array('question' => 'What does category help you choose?', 'answer' => 'The right testing framework, comparison set, buyer expectations, and verdict language.'),
            ),
            'do_now' => 'Label ten launches with one primary category and one test you would run for each.',
            'links' => array('ai-launches/ai-agents', 'ai-launches/ai-coding-tools', 'ai-launches/ai-video-tools', 'ai-launches/open-weight-models'),
        ),
        array(
            'number' => 4,
            'slug' => 'lesson-4-source-verification',
            'module' => 'Module 2: How to Separate Real AI Products From Launch Hype',
            'title' => 'Source Verification: How to Know If a Launch Is Real',
            'time' => '35 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Write a Source Verification Note with at least three sources.',
            'intro' => 'A launch is only as strong as its sources. Verification does not mean distrusting everything. It means knowing which claims are backed by official or credible evidence.',
            'sections' => array(
                array(
                    'heading' => 'Sources to check',
                    'body' => array('Start with official sources, then use third-party pages to confirm context. Do not treat an AI-generated summary as a source of truth.'),
                    'items' => array('Official website', 'Official blog', 'Product documentation', 'GitHub repo', 'Hugging Face page', 'Product Hunt page', 'App store listing', 'Chrome extension listing', 'Pricing page', 'Changelog', 'Founder/company social posts', 'Demo videos', 'Press releases', 'Launch posts', 'Waitlists', 'API docs'),
                ),
                array(
                    'heading' => 'Source quality table',
                    'body' => array('High: official product page, official docs, official pricing page, active GitHub or Hugging Face repo. Medium: founder/company announcement, Product Hunt, demo video. Low: random aggregator page, unattributed social comments, AI-generated summary.'),
                ),
                array(
                    'heading' => 'What to capture',
                    'body' => array('Record the source URL, what it proves, what it does not prove, and the date you checked it. A pricing page proves access terms better than a launch tweet. A docs page proves developer readiness better than a landing page headline.'),
                ),
            ),
            'tip' => 'Use sources to answer specific questions. One source rarely proves everything.',
            'red_flag' => 'If every claim traces back to the same unsourced summary, the launch is not verified.',
            'exercise' => 'Verify one launch using at least three sources. Label each source as high, medium, or low quality.',
            'deliverable' => 'Source Verification Note',
            'quiz' => array(
                array('question' => 'Is a random aggregator page a high-quality source?', 'answer' => 'No. It can help discovery, but it should not be the proof for pricing, availability, or claims.'),
                array('question' => 'What source is usually best for pricing?', 'answer' => 'The official pricing page or official docs. If neither exists, mark pricing as unclear.'),
                array('question' => 'Why should you record the date you checked a source?', 'answer' => 'AI products change quickly. A launch can move from waitlist to live, change pricing, or remove a feature.'),
            ),
            'do_now' => 'Find three sources for one launch and write what each source proves.',
            'links' => array('ai-launches', 'ai-companies', 'ai-tools'),
        ),
        array(
            'number' => 5,
            'slug' => 'lesson-5-ai-launch-hype-signals',
            'module' => 'Module 2: How to Separate Real AI Products From Launch Hype',
            'title' => 'The AI Launch Hype Detector',
            'time' => '35 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Create a green, yellow, and red signal scorecard.',
            'intro' => 'Hype is not always fraud. Sometimes it is rushed positioning, vague writing, or a real product presented with language too broad to evaluate. Your job is to separate proof from pressure.',
            'sections' => array(
                array(
                    'heading' => 'Common hype phrases',
                    'body' => array('Treat these phrases as prompts for verification, not automatic disqualification. Ask what the product can actually do today.'),
                    'items' => array('Fully autonomous', 'Revolutionary', 'Replaces your team', 'AI employee', 'One-click', 'Enterprise-ready', 'Agentic', 'Production-ready', 'No-code', 'Private', 'Secure', 'Open-source', 'Free forever', 'Beats GPT/Claude/Gemini', 'The last tool you will ever need'),
                ),
                array(
                    'heading' => 'The hype questions',
                    'body' => array('Can I test it? Can I see a demo? Can I understand the use case? Can I find pricing? Can I compare it to something? Can I verify the claim? Does it solve one specific problem? Is the language clear or vague?'),
                ),
                array(
                    'heading' => 'Signal categories',
                    'body' => array('Green signals: working product, clear pricing, clear demo, clear target user, specific use case, docs, active updates, credible team, possible comparison. Yellow signals: waitlist only, unclear pricing, vague claims, limited demo, no docs, broad target audience. Red signals: no working product, fake-looking demo, no official source, unclear company, impossible claims, no way to test.'),
                ),
            ),
            'tip' => 'A strong launch makes the user, job, access path, and proof easy to see.',
            'red_flag' => 'If a product claims to be fully autonomous but has no demo, no pricing, no docs, and no clear use case, treat it as unverified until proven otherwise.',
            'exercise' => 'Review three AI launch pages and label green, yellow, and red signals.',
            'deliverable' => 'AI Hype Detector Scorecard',
            'quiz' => array(
                array('question' => 'Does hype always mean a product is fake?', 'answer' => 'No. It means the claims need stronger proof before you repeat, buy, or recommend them.'),
                array('question' => 'Name one green signal.', 'answer' => 'Clear pricing, a working product, a usable demo, docs, a specific use case, or a clear target user.'),
                array('question' => 'What should you do with an impossible claim?', 'answer' => 'Ask for evidence and mark the claim unverified until a credible source proves it.'),
            ),
            'do_now' => 'Take one launch page and highlight three proof-backed claims and three claims that need evidence.',
            'links' => array('ai-launch-scorecard', 'ai-launches/launch-visibility-score', 'ai-launches'),
        ),
        array(
            'number' => 6,
            'slug' => 'lesson-6-pricing-access-and-availability',
            'module' => 'Module 2: How to Separate Real AI Products From Launch Hype',
            'title' => 'Pricing, Access, Licensing, and Availability',
            'time' => '30 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Build an Access and Pricing Clarity Table.',
            'intro' => 'A launch is not useful until people understand whether they can access it, what it costs, and what they are allowed to do with it.',
            'sections' => array(
                array(
                    'heading' => 'Access models to recognize',
                    'body' => array('Availability decides whether a launch can be tested now or only watched. Be precise.'),
                    'items' => array('Free plan', 'Free trial', 'Paid plan', 'Usage-based pricing', 'API pricing', 'Credits', 'Rate limits', 'Waitlist', 'Invite-only access', 'Beta access', 'Open-source', 'Open-weight', 'Commercial license', 'Personal-use license', 'Enterprise plan', 'Local deployment', 'Cloud-only tools'),
                ),
                array(
                    'heading' => 'Important distinctions',
                    'body' => array('A free plan is ongoing limited access. A free trial is temporary. Open-source means code license matters. Open-weight means model weights may be available but the code, license, or commercial rights can still be limited. API available means builders can integrate; product only means users interact through the app.'),
                ),
                array(
                    'heading' => 'Buyer and creator impact',
                    'body' => array('Buyers need cost, risk, and availability. Creators need test access and demo permission. Founders need to make these obvious or serious evaluators move on.'),
                ),
            ),
            'tip' => 'Do not call something free unless you know whether it is a plan, a trial, a credit grant, an open license, or a temporary launch offer.',
            'red_flag' => 'No pricing and no access path is not buyer-ready, even if the demo looks impressive.',
            'exercise' => 'Evaluate the access model for five launches. Mark each as live, waitlist, beta, API, open-weight, open-source, trial, paid, unclear, or enterprise-only.',
            'deliverable' => 'Access and Pricing Clarity Table',
            'quiz' => array(
                array('question' => 'Is open-weight the same as open-source?', 'answer' => 'No. Open-weight usually refers to model weights. Open-source refers to code license. Both require license review.'),
                array('question' => 'Why does waitlist-only matter?', 'answer' => 'It limits immediate testing and makes the verdict more speculative.'),
                array('question' => 'What should you write when pricing is missing?', 'answer' => 'Write unclear, not published, waitlist-only, sales-led, or unknown. Do not guess.'),
            ),
            'do_now' => 'Pick five launches and add one access verdict for each.',
            'links' => array('ai-tools', 'ai-models', 'compare-ai-models'),
        ),
        array(
            'number' => 7,
            'slug' => 'lesson-7-30-minute-ai-tool-test',
            'module' => 'Module 3: How to Test a New AI Tool in 30 Minutes',
            'title' => 'The 30-Minute AI Tool Test',
            'time' => '40 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Run a timed tool test and choose a practical verdict.',
            'intro' => 'You do not need a perfect lab test to learn whether a tool deserves more time. You need a repeatable first pass that catches obvious value, friction, and risk.',
            'sections' => array(
                array(
                    'heading' => 'The timed framework',
                    'body' => array('Minute 0-5: read the launch. Minute 5-10: open the product. Minute 10-15: run a simple task. Minute 15-25: run a hard task. Minute 25-30: decide.'),
                ),
                array(
                    'heading' => 'What to test',
                    'body' => array('Use one realistic task from your work, not a toy prompt. Then push once with a harder edge case. A tool that only works on perfect inputs may still be interesting, but the verdict should say so.'),
                ),
                array(
                    'heading' => 'Verdict options',
                    'body' => array('Choose a decision label instead of a vague score.'),
                    'items' => array('Ignore', 'Save for later', 'Test again', 'Use personally', 'Recommend to a client', 'Cover on YouTube', 'Write about on Kingy', 'Compare against alternatives', 'Avoid for now'),
                ),
            ),
            'tip' => 'A 30-minute test is a filter, not a final procurement process.',
            'red_flag' => 'If the product cannot complete a simple task and gives no clear path to fix the problem, do not let the hard task distract you.',
            'exercise' => 'Perform a 30-minute test on one AI tool. Use a timer and record what happened at each stage.',
            'deliverable' => '30-Minute AI Tool Test Sheet',
            'quiz' => array(
                array('question' => 'Why run both a simple task and a hard task?', 'answer' => 'The simple task checks basic usability. The hard task shows limits, failure modes, and whether the tool deserves deeper testing.'),
                array('question' => 'Should a 30-minute test decide enterprise adoption?', 'answer' => 'No. It decides whether deeper testing is worth doing.'),
                array('question' => 'What is better than saying interesting?', 'answer' => 'A decision label such as save for later, use personally, recommend, compare, or avoid for now.'),
            ),
            'do_now' => 'Set a timer and run one launch through the five-stage test.',
            'links' => array('ai-launches', 'ai-tools', 'ai-launch-academy/resources'),
        ),
        array(
            'number' => 8,
            'slug' => 'lesson-8-category-testing-frameworks',
            'module' => 'Module 3: How to Test a New AI Tool in 30 Minutes',
            'title' => 'Category-Specific Testing Frameworks',
            'time' => '45 minutes',
            'difficulty' => 'Intermediate',
            'outcome' => 'Choose the right test for one AI launch category.',
            'intro' => 'A fair test matches the category. The same launch can look weak under the wrong test and useful under the right one.',
            'sections' => array(
                array('heading' => 'AI agent test', 'body' => array('Check whether it can complete a multi-step task, ask smart follow-up questions, use tools, recover from errors, show its work, remember context, and behave safely with sensitive tasks.')),
                array('heading' => 'AI coding tool test', 'body' => array('Check whether it understands the existing codebase, fixes bugs, explains changes, writes tests, avoids unrelated files, and gives output a non-technical user can follow.')),
                array('heading' => 'AI video and image tool test', 'body' => array('For video, check coherence, faces, hands, motion, camera instructions, character preservation, commercial usefulness, generation time, and usage limits. For image, check sharpness, style following, text handling, identity/product preservation, variations, editing precision, and artifacts.')),
                array('heading' => 'AI search, research, and model test', 'body' => array('For research, check citations, real sources, date handling, source comparison, accuracy, and fact versus interpretation. For models, check modalities, reasoning, coding, context, API access, pricing, geography, agents, creator fit, enterprise fit, and open/closed status.')),
                array('heading' => 'Open-weight/local model test', 'body' => array('Check local runtime, hardware needs, license, commercial permission, closed-model comparison, quantizations, simple runtimes like Ollama or LM Studio, and whether it is good enough for the task.')),
            ),
            'tip' => 'The best category test creates evidence you can reuse in a buyer note, creator brief, or founder feedback note.',
            'red_flag' => 'Do not test an agent only by chatting with it. The question is whether it can act, recover, and finish a workflow.',
            'exercise' => 'Choose one category and run the matching category test.',
            'deliverable' => 'Category Test Report',
            'quiz' => array(
                array('question' => 'What is the core question for an AI agent?', 'answer' => 'Can it complete a multi-step task safely and recover when something goes wrong?'),
                array('question' => 'What should a research tool provide?', 'answer' => 'Real sources, citations, date clarity, accurate summaries, and separation between facts and interpretation.'),
                array('question' => 'Why check license for open-weight models?', 'answer' => 'Availability of weights does not automatically mean commercial use is allowed.'),
            ),
            'do_now' => 'Pick one category and write the five test questions you will use next time.',
            'links' => array('ai-launches/ai-agents', 'ai-launches/ai-coding-tools', 'ai-launches/ai-video-tools', 'ai-models'),
        ),
        array(
            'number' => 9,
            'slug' => 'lesson-9-ai-launch-comparisons',
            'module' => 'Module 3: How to Test a New AI Tool in 30 Minutes',
            'title' => 'Comparing a New AI Tool Against Alternatives',
            'time' => '35 minutes',
            'difficulty' => 'Intermediate',
            'outcome' => 'Create an AI Launch Battle Card.',
            'intro' => 'A tool is rarely good or bad in isolation. The real question is whether it beats a current workflow, a direct competitor, a cheaper option, or a more mature product for a specific user.',
            'sections' => array(
                array(
                    'heading' => 'Comparison types',
                    'body' => array('Use multiple comparison angles when the market is unclear.'),
                    'items' => array('Direct competitor', 'Indirect competitor', 'Old workflow vs new workflow', 'Free alternative vs paid alternative', 'Open-source alternative vs closed product', 'Simple tool vs advanced tool', 'General model vs specialized product'),
                ),
                array(
                    'heading' => 'Battle card fields',
                    'body' => array('Tool, alternative, category, pricing, free plan, best for, weakness, ease of use, output quality, speed, integrations, API, privacy, best user, final verdict.'),
                ),
                array(
                    'heading' => 'How to avoid fake precision',
                    'body' => array('If you did not test a field, say unknown. A battle card should clarify decisions, not pretend to be a benchmark.'),
                ),
            ),
            'tip' => 'A useful comparison names the user and job. The best tool for a founder demo may not be the best tool for a regulated enterprise workflow.',
            'red_flag' => 'If the only comparison is against a strawman version of the old way, the launch case is weak.',
            'exercise' => 'Compare one new launch against two alternatives. Include one direct alternative and one old-workflow alternative.',
            'deliverable' => 'AI Launch Battle Card',
            'quiz' => array(
                array('question' => 'Why compare against the old workflow?', 'answer' => 'Because buyers often ask whether they need another tool at all, not just which tool in a category is best.'),
                array('question' => 'What should you write for untested fields?', 'answer' => 'Unknown or not tested. Do not manufacture precision.'),
                array('question' => 'What makes a comparison useful?', 'answer' => 'A clear user, job, criteria, evidence, and final verdict.'),
            ),
            'do_now' => 'Create one battle card with two alternatives and one final verdict.',
            'links' => array('compare-ai-models', 'ai-tools', 'ai-launch-academy/resources'),
        ),
        array(
            'number' => 10,
            'slug' => 'lesson-10-build-your-weekly-ai-stack',
            'module' => 'Module 4: How to Build Your Weekly AI Stack',
            'title' => 'The Weekly AI Stack System',
            'time' => '35 minutes',
            'difficulty' => 'Beginner',
            'outcome' => 'Build a weekly shortlist of tools worth testing.',
            'intro' => 'You cannot test every AI launch. A weekly stack turns the launch stream into a manageable shortlist that covers the major categories without drowning you.',
            'sections' => array(
                array(
                    'heading' => 'The weekly mix',
                    'body' => array('Choose one launch from each bucket so your learning stays balanced.'),
                    'items' => array('1 AI coding tool', '1 AI agent or automation product', '1 AI video or image tool', '1 AI research/search tool', '1 AI productivity/business tool', '1 model release or infrastructure update', '1 wild experimental tool'),
                ),
                array(
                    'heading' => 'Decision questions',
                    'body' => array('For each tool ask: What does it replace? What does it improve? Who should use it? Is it free to test? Is it better than my current workflow? Is it worth learning now? Should I save it, use it, or ignore it?'),
                ),
                array(
                    'heading' => 'Verdict labels',
                    'body' => array('Use this week, test later, watchlist, client recommendation, content idea, ignore, too early, needs proof.'),
                ),
            ),
            'tip' => 'Your weekly stack is a learning filter. It should make you sharper each week, not create another unread queue.',
            'red_flag' => 'If every tool goes on watchlist, you are avoiding decisions.',
            'exercise' => 'Build a first weekly AI stack from the Kingy launch database.',
            'deliverable' => 'Weekly AI Stack Plan',
            'quiz' => array(
                array('question' => 'Why include one wild experimental tool?', 'answer' => 'It keeps you exposed to frontier ideas without letting experiments dominate your whole week.'),
                array('question' => 'What is the danger of saving everything?', 'answer' => 'The stack becomes a backlog instead of a decision system.'),
                array('question' => 'What should each weekly pick answer?', 'answer' => 'What it replaces, who it helps, whether it is testable, and what verdict it deserves.'),
            ),
            'do_now' => 'Choose seven launches and assign one verdict label to each.',
            'links' => array('ai-launches/this-week', 'ai-launches/today', 'ai-launch-academy/resources'),
        ),
        array(
            'number' => 11,
            'slug' => 'lesson-11-launches-into-content-business-workflows',
            'module' => 'Module 4: How to Build Your Weekly AI Stack',
            'title' => 'Turning Launches Into Content, Business Ideas, and Workflows',
            'time' => '40 minutes',
            'difficulty' => 'Intermediate',
            'outcome' => 'Turn one launch into a content idea, workflow idea, and adoption idea.',
            'intro' => 'Launches are raw material. The same launch can become a creator video, founder positioning lesson, consultant recommendation, or small business workflow.',
            'sections' => array(
                array('heading' => 'For creators', 'body' => array('Launches can become YouTube videos, Shorts, tutorials, comparison videos, I tested videos, best tools this week videos, newsletters, blog posts, and social posts.')),
                array('heading' => 'For founders', 'body' => array('Launch analysis can improve positioning, demos, landing pages, pricing clarity, creator pitches, comparison pages, and launch campaigns.')),
                array('heading' => 'For consultants', 'body' => array('Launches can become client recommendations, workflow audits, automation ideas, tool adoption plans, AI stack reviews, and training sessions.')),
                array('heading' => 'For small businesses', 'body' => array('Launches can point to time-saving workflows, cheaper software alternatives, content systems, support tools, admin automation, and marketing experiments.')),
            ),
            'tip' => 'The strongest content angle usually has a before/after, a visual demo, a surprising use case, or a real comparison.',
            'red_flag' => 'Do not turn a launch into advice before verifying access, pricing, and whether the tool can do the job.',
            'exercise' => 'Pick one launch and create one content idea, one workflow idea, and one business or adoption idea.',
            'deliverable' => 'Launch-to-Workflow Brief',
            'quiz' => array(
                array('question' => 'What makes an AI launch video easier to understand?', 'answer' => 'A clear before/after, visual demo, surprising use case, or strong comparison angle.'),
                array('question' => 'How can founders use launch analysis?', 'answer' => 'To improve positioning, demos, pricing clarity, landing pages, creator pitches, and launch campaigns.'),
                array('question' => 'What should consultants avoid?', 'answer' => 'Recommending tools before checking fit, pricing, access, data risk, and workflow value.'),
            ),
            'do_now' => 'Write one launch-to-workflow brief with three separate ideas.',
            'links' => array('ai-launches/creator-coverage-ai-launches', 'ai-launches/launch-visibility-score', 'ai-sponsored-video-roi-calculator'),
        ),
        array(
            'number' => 12,
            'slug' => 'lesson-12-ai-launch-analyst-workflow',
            'module' => 'Module 5: How to Become an AI Launch Analyst',
            'title' => 'The AI Launch Analyst Workflow',
            'time' => '45 minutes',
            'difficulty' => 'Intermediate',
            'outcome' => 'Complete a full AI Launch Analyst Brief.',
            'intro' => 'An AI Launch Analyst tracks new AI products, verifies them, tests them, compares them, and turns them into useful decisions.',
            'sections' => array(
                array(
                    'heading' => 'The Kingy workflow',
                    'body' => array('Use the same sequence every time so your judgment improves with repetition.'),
                    'items' => array('Find the launch', 'Verify the source', 'Classify the product', 'Check access and pricing', 'Identify the target user', 'Test the product', 'Compare alternatives', 'Identify hype signals', 'Identify practical use cases', 'Decide the verdict', 'Create a useful brief', 'Update your stack or recommendation list'),
                ),
                array(
                    'heading' => 'Analyst output types',
                    'body' => array('Your work can become a launch snapshot, tool test report, comparison card, buyer recommendation, creator brief, founder feedback note, weekly AI stack update, or full Kingy-style launch verdict.'),
                ),
                array(
                    'heading' => 'What good judgment looks like',
                    'body' => array('A good analyst is fast, skeptical, specific, and useful. They do not need to be cynical. They need to know what is proven, what is unclear, and what decision the reader should make next.'),
                ),
            ),
            'tip' => 'Good analysts do not just collect launches. They produce decisions people can act on.',
            'red_flag' => 'If your brief does not end in a verdict, it is a summary, not analysis.',
            'exercise' => 'Complete a full analysis of one AI launch.',
            'deliverable' => 'AI Launch Analyst Brief',
            'quiz' => array(
                array('question' => 'What is the role of an AI Launch Analyst?', 'answer' => 'To track, verify, evaluate, test, compare, and brief AI launches so people can make better decisions.'),
                array('question' => 'What makes a launch brief useful?', 'answer' => 'Clear facts, verified sources, category fit, test results, comparison, hype signals, use cases, and a final verdict.'),
                array('question' => 'What is the final course project?', 'answer' => 'A full AI Launch Analysis Brief built from a real or clearly marked example launch.'),
            ),
            'do_now' => 'Start the capstone brief and fill in every field you can verify.',
            'links' => array('ai-launch-academy/capstone', 'ai-launch-academy/certification', 'ai-launches'),
        ),
    );

    return $lessons;
}

function kingy_ali_ai_launch_academy_modules() {
    return array(
        array('title' => 'Module 1: How to Read an AI Launch', 'summary' => 'Turn a launch page into a clear snapshot: what launched, who made it, which category it belongs in, who it is for, and what still needs proof.', 'practice' => 'You finish with a Launch Snapshot and an anatomy card instead of a loose impression.', 'lessons' => array(1, 2, 3)),
        array('title' => 'Module 2: How to Separate Real AI Products From Launch Hype', 'summary' => 'Check official sources, pricing, access, licensing, demos, docs, and hype signals before repeating a claim or recommending a tool.', 'practice' => 'You finish with a source note and green, yellow, and red signal scorecard.', 'lessons' => array(4, 5, 6)),
        array('title' => 'Module 3: How to Test a New AI Tool in 30 Minutes', 'summary' => 'Run a short test against the product category, compare it with an alternative, and decide whether the tool deserves more time.', 'practice' => 'You finish with a timed test sheet and a comparison battle card.', 'lessons' => array(7, 8, 9)),
        array('title' => 'Module 4: How to Build Your Weekly AI Stack', 'summary' => 'Convert the launch stream into a weekly shortlist: use now, test later, watch, ignore, or turn into content and workflow ideas.', 'practice' => 'You finish with a weekly AI stack planner and launch-to-content notes.', 'lessons' => array(10, 11)),
        array('title' => 'Module 5: How to Become an AI Launch Analyst', 'summary' => 'Combine verification, testing, alternatives, hype signals, buyer usefulness, and demo potential into one practical recommendation.', 'practice' => 'You finish with a complete AI Launch Analyst Brief.', 'lessons' => array(12)),
    );
}

function kingy_ali_ai_launch_academy_sample_brief() {
    return array(
        array('label' => 'Launch', 'value' => 'Example AI research assistant for sales teams'),
        array('label' => 'Category', 'value' => 'AI research and workflow assistant'),
        array('label' => 'Source status', 'value' => 'Official product page found. Pricing and docs still need checking. Treat broad claims as unverified.'),
        array('label' => '30-minute test', 'value' => 'Test one real account-research task, one messy source task, and one export/share task. Compare against current CRM research workflow.'),
        array('label' => 'Hype signals', 'value' => 'Green: narrow use case and demo. Yellow: unclear pricing. Red: any claim that it fully replaces sales research.'),
        array('label' => 'Verdict', 'value' => 'Test later if access is available. Do not recommend to a team until pricing, data handling, and repeatability are verified.'),
    );
}

function kingy_ali_ai_launch_academy_workflow_steps() {
    return array(
        array('title' => '1. Verify the source', 'body' => 'Find the official page, docs, pricing, changelog, repo, demo, or launch post. Record what each source proves and what it does not prove.'),
        array('title' => '2. Check the hype', 'body' => 'Separate specific claims from vague language. Mark green, yellow, and red signals before you let the headline shape the verdict.'),
        array('title' => '3. Run the 30-minute test', 'body' => 'Try one simple task, one harder task, and one comparison against the current workflow or closest alternative.'),
        array('title' => '4. Make the recommendation', 'body' => 'End with a plain decision: use, test, watch, ignore, cover, buy, save, or recommend, plus the reason.'),
    );
}

function kingy_ali_ai_launch_academy_lesson_by_number($number) {
    foreach (kingy_ali_ai_launch_academy_lessons() as $lesson) {
        if ((int) $lesson['number'] === (int) $number) {
            return $lesson;
        }
    }

    return array();
}

function kingy_ali_ai_launch_academy_lesson_by_slug($slug) {
    foreach (kingy_ali_ai_launch_academy_lessons() as $lesson) {
        if ($lesson['slug'] === $slug) {
            return $lesson;
        }
    }

    return array();
}

function kingy_ali_ai_launch_academy_pages() {
    static $pages = null;

    if ($pages !== null) {
        return $pages;
    }

    $base = kingy_ali_ai_launch_academy_base_path();
    $pages = array(
        'landing' => array(
            'path' => $base,
            'title' => 'AI Launch Academy',
            'seo_title' => 'AI Launch Academy: Learn How to Find, Test, and Evaluate New AI Tools',
            'description' => 'Learn how to find, evaluate, test, compare, and use new AI tools, agents, models, apps, and startups with the Kingy AI Launch Academy.',
            'type' => 'Course',
            'page' => 'landing',
        ),
        'capstone' => array(
            'path' => $base . '/capstone',
            'title' => 'Evaluate a Real AI Launch',
            'seo_title' => 'AI Launch Academy Capstone: Evaluate a Real AI Launch',
            'description' => 'Complete the AI Launch Academy capstone by evaluating a real AI tool, app, model, startup, or product launch with the Kingy framework.',
            'type' => 'WebPage',
            'page' => 'capstone',
        ),
        'resources' => array(
            'path' => $base . '/resources',
            'title' => 'AI Launch Academy Resources',
            'seo_title' => 'AI Launch Academy Resources: Templates and Checklists',
            'description' => 'Copy practical AI launch templates, checklists, battle cards, stack planners, and analyst brief formats from AI Launch Academy.',
            'type' => 'CollectionPage',
            'page' => 'resources',
        ),
        'checklists' => array(
            'path' => $base . '/checklists',
            'title' => 'AI Launch Academy Checklists',
            'seo_title' => 'AI Launch Academy Checklists for Evaluating New AI Tools',
            'description' => 'Use quick AI launch checklists for source verification, hype detection, pricing clarity, tool testing, founder readiness, and buyer adoption.',
            'type' => 'CollectionPage',
            'page' => 'checklists',
        ),
        'certification' => array(
            'path' => $base . '/certification',
            'title' => 'Kingy Certified AI Launch Analyst',
            'seo_title' => 'Kingy Certified AI Launch Analyst: Certification Path',
            'description' => 'Learn the lightweight Kingy Certified AI Launch Analyst path for finding, verifying, testing, comparing, and briefing AI product launches.',
            'type' => 'WebPage',
            'page' => 'certification',
        ),
    );

    foreach (kingy_ali_ai_launch_academy_lessons() as $lesson) {
        $pages[$lesson['slug']] = array(
            'path' => $base . '/' . $lesson['slug'],
            'title' => sprintf('Lesson %d: %s', (int) $lesson['number'], $lesson['title']),
            'seo_title' => sprintf('Lesson %d: %s | AI Launch Academy', (int) $lesson['number'], $lesson['title']),
            'description' => sprintf('AI Launch Academy lesson %d outcome: %s Deliverable: %s.', (int) $lesson['number'], $lesson['outcome'], $lesson['deliverable']),
            'type' => 'LearningResource',
            'page' => $lesson['slug'],
        );
    }

    return $pages;
}

function kingy_ali_ai_launch_academy_shortcode_block($page_key) {
    $shortcode = '[kingy_ai_launch_academy page="' . esc_attr($page_key) . '"]';
    if (function_exists('kingy_ali_shortcode_block')) {
        return kingy_ali_shortcode_block($shortcode);
    }

    return '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->';
}

function kingy_ali_ai_launch_academy_recommended_pages() {
    $recommended = array();

    foreach (kingy_ali_ai_launch_academy_pages() as $key => $page) {
        $recommended['ai_launch_academy_' . sanitize_key($key)] = array(
            'path' => $page['path'],
            'title' => $page['title'],
            'content' => kingy_ali_ai_launch_academy_shortcode_block($page['page']),
        );
    }

    return $recommended;
}

function kingy_ali_ai_launch_academy_install_managed_pages($repair_managed_pages = false) {
    $results = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    );

    foreach (kingy_ali_ai_launch_academy_recommended_pages() as $key => $page) {
        $path = trim((string) $page['path'], '/');
        $existing = function_exists('kingy_ali_find_page_by_path') ? kingy_ali_find_page_by_path($path) : get_page_by_path($path, OBJECT, 'page');
        $managed_key = function_exists('kingy_ali_meta_key') ? kingy_ali_meta_key('managed_page') : '_kingy_ali_managed_page';
        $page_key = function_exists('kingy_ali_meta_key') ? kingy_ali_meta_key('page_key') : '_kingy_ali_page_key';
        $post_status = isset($page['post_status']) ? sanitize_key($page['post_status']) : 'publish';
        if (!in_array($post_status, array('publish', 'draft'), true)) {
            $post_status = 'publish';
        }

        if ($existing) {
            $is_managed = get_post_meta($existing->ID, $managed_key, true);
            if (!$is_managed) {
                $results['skipped']++;
                continue;
            }

            if ($repair_managed_pages) {
                $updated_page_id = wp_update_post(
                    array(
                        'ID' => $existing->ID,
                        'post_status' => $post_status,
                        'post_title' => $page['title'],
                        'post_content' => $page['content'],
                    ),
                    true
                );

                if (is_wp_error($updated_page_id)) {
                    $results['skipped']++;
                    continue;
                }
                $results['updated']++;
            } else {
                $results['skipped']++;
            }

            update_post_meta($existing->ID, $managed_key, '1');
            update_post_meta($existing->ID, $page_key, sanitize_key($key));
            continue;
        }

        $parent_id = function_exists('kingy_ali_parent_page_id_for_path') ? kingy_ali_parent_page_id_for_path($path) : 0;
        $slug_parts = explode('/', $path);
        $slug = end($slug_parts);
        $managed_page = function_exists('kingy_ali_find_managed_page_by_key') ? kingy_ali_find_managed_page_by_key($key) : null;

        if ($managed_page) {
            $updated_page_id = wp_update_post(
                array(
                    'ID' => $managed_page->ID,
                    'post_status' => $post_status,
                    'post_title' => $page['title'],
                    'post_name' => sanitize_title($slug),
                    'post_parent' => $parent_id,
                    'post_content' => $page['content'],
                ),
                true
            );

            if (is_wp_error($updated_page_id)) {
                $results['skipped']++;
                continue;
            }

            update_post_meta($managed_page->ID, $managed_key, '1');
            update_post_meta($managed_page->ID, $page_key, sanitize_key($key));
            $results['updated']++;
            continue;
        }

        $post_id = wp_insert_post(
            array(
                'post_type' => 'page',
                'post_status' => $post_status,
                'post_title' => $page['title'],
                'post_name' => sanitize_title($slug),
                'post_parent' => $parent_id,
                'post_content' => $page['content'],
                'meta_input' => array(
                    $managed_key => '1',
                    $page_key => sanitize_key($key),
                ),
            ),
            true
        );

        if (is_wp_error($post_id)) {
            $results['skipped']++;
            continue;
        }

        $results['created']++;
    }

    return $results;
}

function kingy_ali_ai_launch_academy_related_pages_meta() {
    $meta = array();

    foreach (kingy_ali_ai_launch_academy_pages() as $page) {
        $meta[$page['path']] = array(
            'title' => $page['seo_title'],
            'description' => $page['description'],
            'url' => home_url('/' . trim($page['path'], '/') . '/'),
            'type' => $page['type'] === 'Course' ? 'WebPage' : $page['type'],
        );
    }

    return $meta;
}

function kingy_ali_ai_launch_academy_current_page_key() {
    if (is_admin() || !is_page()) {
        return '';
    }

    $post_id = get_queried_object_id();
    $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';
    if ($path === '') {
        return '';
    }

    foreach (kingy_ali_ai_launch_academy_pages() as $key => $page) {
        if ($path === $page['path']) {
            return $key;
        }
    }

    return '';
}

function kingy_ali_ai_launch_academy_is_current_page() {
    return kingy_ali_ai_launch_academy_current_page_key() !== '';
}

function kingy_ali_ai_launch_academy_body_class($classes) {
    if (!is_array($classes)) {
        $classes = array();
    }

    if (kingy_ali_ai_launch_academy_is_current_page()) {
        $classes[] = 'kingy-ali-ai-launch-academy-page';
    }

    return array_values(array_unique($classes));
}

function kingy_ali_ai_launch_academy_known_internal_paths() {
    $paths = array('ai-launches', 'ai-launches/today', 'ai-launches/this-week', 'ai-tools', 'ai-companies', 'ai-models', 'compare-ai-models', 'ai-courses', 'ai-launch-scorecard', 'ai-launches/submit', 'ai-launches/launch-visibility-score', 'ai-launches/ai-agents', 'ai-launches/ai-coding-tools', 'ai-launches/ai-video-tools', 'ai-launches/ai-image-tools', 'ai-launches/open-weight-models', 'ai-launches/ai-search-research-tools', 'ai-launches/creator-coverage-ai-launches', 'ai-sponsored-video-roi-calculator');

    foreach (kingy_ali_ai_launch_academy_pages() as $page) {
        $paths[] = $page['path'];
    }

    return array_values(array_unique($paths));
}

function kingy_ali_ai_launch_academy_internal_url($path, $fallback = '') {
    $path = trim((string) $path, '/');
    if ($path === '') {
        return '';
    }

    $page = function_exists('get_page_by_path') ? get_page_by_path($path, OBJECT, 'page') : null;
    if ($page) {
        return $page->post_status === 'publish' ? home_url('/' . $path . '/') : '';
    }

    if (in_array($path, kingy_ali_ai_launch_academy_known_internal_paths(), true)) {
        return home_url('/' . $path . '/');
    }

    return $fallback ? home_url('/' . trim($fallback, '/') . '/') : '';
}

function kingy_ali_ai_launch_academy_link_items($paths) {
    $labels = array(
        'ai-launches' => array('AI Launch Intelligence hub', 'Browse the launch intelligence layer.'),
        'ai-launches/today' => array('Daily AI Launch Radar', 'Use fresh launches for practice.'),
        'ai-launches/this-week' => array('Weekly AI launches', 'Build your weekly stack.'),
        'ai-tools' => array('AI Tool Directory', 'Compare tool profiles.'),
        'ai-companies' => array('AI Company Directory', 'Check company context.'),
        'ai-models' => array('AI Model Releases', 'Review model profiles and access notes.'),
        'compare-ai-models' => array('Compare AI Models', 'Compare model capabilities and caveats.'),
        'ai-courses' => array('AI Courses', 'Browse Kingy AI learning paths.'),
        'ai-launch-scorecard' => array('AI Launch Scorecard', 'Score launch readiness.'),
        'ai-launches/submit' => array('Submit an AI Launch', 'Send a product for Kingy review.'),
        'ai-launches/launch-visibility-score' => array('Launch Visibility Score Calculator', 'Check how easy a launch is to understand.'),
        'ai-launches/ai-agents' => array('AI Agents', 'Browse AI agent launches.'),
        'ai-launches/ai-coding-tools' => array('AI Coding Tools', 'Browse coding tool launches.'),
        'ai-launches/ai-video-tools' => array('AI Video Tools', 'Browse video tool launches.'),
        'ai-launches/ai-image-tools' => array('AI Image Tools', 'Browse image tool launches.'),
        'ai-launches/open-weight-models' => array('Open-Weight Models', 'Browse open-weight model launches.'),
        'ai-launches/ai-search-research-tools' => array('AI Search and Research Tools', 'Browse search and research launches.'),
        'ai-launches/creator-coverage-ai-launches' => array('Creator-Ready Launches', 'Find launches with coverage potential.'),
        'ai-sponsored-video-roi-calculator' => array('Sponsored Video ROI Calculator', 'Model creator campaign economics.'),
        'ai-launch-academy/resources' => array('Academy Resources', 'Copy templates and checklists.'),
        'ai-launch-academy/capstone' => array('Capstone', 'Build your final launch analysis.'),
        'ai-launch-academy/certification' => array('Certification Path', 'Review the analyst standard.'),
    );

    $items = array();
    foreach ($paths as $path) {
        $path = trim((string) $path, '/');
        $url = kingy_ali_ai_launch_academy_internal_url($path);
        if (!$url || empty($labels[$path])) {
            continue;
        }
        $items[] = array('url' => $url, 'label' => $labels[$path][0], 'description' => $labels[$path][1]);
    }

    return $items;
}

function kingy_ali_ai_launch_academy_newsletter_url() {
    return kingy_ali_ai_launch_academy_internal_url('subscribe');
}

function kingy_ali_ai_launch_academy_contact_url() {
    $configured_url = function_exists('get_option') ? get_option('kingy_ali_contact_url', '') : '';
    if ($configured_url && function_exists('kingy_ali_sanitize_public_cta_url')) {
        $url = kingy_ali_sanitize_public_cta_url($configured_url);
        if ($url) {
            return $url;
        }
    }

    return kingy_ali_ai_launch_academy_internal_url('contact');
}

function kingy_ali_shortcode_ai_launch_academy($atts = array()) {
    if (function_exists('kingy_ali_enqueue_assets')) {
        kingy_ali_enqueue_assets();
    }

    $atts = shortcode_atts(array('page' => ''), is_array($atts) ? $atts : array(), 'kingy_ai_launch_academy');
    $page = sanitize_key((string) $atts['page']);
    if ($page === '') {
        $page = kingy_ali_ai_launch_academy_current_page_key();
    }
    if ($page === '' || $page === 'landing') {
        return kingy_ali_render_ai_launch_academy_landing();
    }

    if ($page === 'capstone') {
        return kingy_ali_render_ai_launch_academy_capstone();
    }
    if ($page === 'resources') {
        return kingy_ali_render_ai_launch_academy_resources(false);
    }
    if ($page === 'checklists') {
        return kingy_ali_render_ai_launch_academy_resources(true);
    }
    if ($page === 'certification') {
        return kingy_ali_render_ai_launch_academy_certification();
    }

    $lesson = kingy_ali_ai_launch_academy_lesson_by_slug($page);
    if ($lesson) {
        return kingy_ali_render_ai_launch_academy_lesson($lesson);
    }

    return kingy_ali_render_ai_launch_academy_landing();
}

function kingy_ali_ai_launch_academy_breadcrumb($current_label) {
    ob_start();
    ?>
    <nav class="kingy-ali-launch-academy-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'kingy-ai-launch-intelligence'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'kingy-ai-launch-intelligence'); ?></a>
        <span aria-hidden="true">/</span>
        <a href="<?php echo esc_url(home_url('/ai-launch-academy/')); ?>"><?php esc_html_e('AI Launch Academy', 'kingy-ai-launch-intelligence'); ?></a>
        <?php if ($current_label) : ?>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html($current_label); ?></span>
        <?php endif; ?>
    </nav>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_cta_links($surface = 'academy') {
    $newsletter_url = kingy_ali_ai_launch_academy_newsletter_url();
    $contact_url = kingy_ali_ai_launch_academy_contact_url();

    ob_start();
    ?>
    <section class="kingy-ali-launch-academy-cta-grid" aria-label="<?php esc_attr_e('AI Launch Academy next steps', 'kingy-ai-launch-intelligence'); ?>">
        <div class="kingy-ali-launch-academy-cta">
            <p class="kingy-ali-kicker"><?php esc_html_e('Launch Radar', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Want new AI launches to practice on every week?', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Subscribe to the Kingy AI Launch Radar and get the latest AI tools, model releases, agents, coding tools, video tools, and startup launches delivered to your inbox.', 'kingy-ai-launch-intelligence'); ?></p>
            <?php if ($newsletter_url) : ?>
                <div class="kingy-ali-cta-row"><a data-kingy-ali-track="clicked_newsletter_cta" data-event-label="<?php esc_attr_e('Join Launch Radar from AI Launch Academy', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url($newsletter_url); ?>"><?php esc_html_e('Subscribe to Launch Radar', 'kingy-ai-launch-intelligence'); ?></a></div>
            <?php endif; ?>
        </div>
        <div class="kingy-ali-launch-academy-cta">
            <p class="kingy-ali-kicker"><?php esc_html_e('Founder path', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Are you launching an AI product?', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('The same framework in this course is how serious buyers, creators, and analysts think about new AI tools. If you want Kingy AI to evaluate your launch, create a demo-led article, or produce a dedicated YouTube video, learn more about working with Kingy AI.', 'kingy-ai-launch-intelligence'); ?></p>
            <?php if ($contact_url) : ?>
                <div class="kingy-ali-cta-row"><a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from AI Launch Academy', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Work with Kingy AI', 'kingy-ai-launch-intelligence'); ?></a></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_required_quiz_score() {
    return 3;
}

function kingy_ali_ai_launch_academy_quiz_distractors($lesson, $index) {
    $pool = array(
        'Assume the launch is credible because the headline sounds confident.',
        'Repeat the company claim without checking official sources.',
        'Treat missing pricing as proof that the product is premium.',
        'Skip comparison because every AI tool should be judged on its own.',
        'Use a social summary as the only source of truth.',
        'Mark every new product as a must-use tool until proven otherwise.',
        'Ignore access, pricing, and licensing until after recommending it.',
        'Choose the broadest possible category so the launch sounds bigger.',
        'Call the launch fake whenever the marketing language feels excited.',
        'Publish a verdict before checking whether the product can be tested.',
        'Use precise scores for fields you did not verify.',
        'Save every launch to a watchlist instead of making a decision.',
    );

    $offset = (((int) $lesson['number'] * 3) + ((int) $index * 2)) % count($pool);
    return array($pool[$offset], $pool[($offset + 5) % count($pool)]);
}

function kingy_ali_ai_launch_academy_quiz_choices($lesson, $quiz, $index) {
    if (!empty($quiz['choices']) && is_array($quiz['choices'])) {
        return array(
            'choices' => array_values($quiz['choices']),
            'correct' => isset($quiz['correct']) ? (int) $quiz['correct'] : 0,
        );
    }

    $choices = kingy_ali_ai_launch_academy_quiz_distractors($lesson, $index);
    $correct_index = (((int) $lesson['number']) + ((int) $index)) % 3;
    array_splice($choices, $correct_index, 0, array($quiz['answer']));

    return array(
        'choices' => $choices,
        'correct' => $correct_index,
    );
}

function kingy_ali_ai_launch_academy_render_dashboard() {
    $total = count(kingy_ali_ai_launch_academy_lessons());

    ob_start();
    ?>
    <section id="dashboard" class="kingy-ali-academy-section kingy-ali-launch-academy-dashboard" data-academy-dashboard>
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Learner dashboard', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Track your AI Launch Academy progress in this browser', 'kingy-ai-launch-intelligence'); ?></h2>
        </div>
        <div class="kingy-ali-launch-academy-dashboard-grid">
            <div>
                <span data-academy-dashboard-lessons>0</span>
                <strong><?php echo esc_html(sprintf('/ %d lessons complete', $total)); ?></strong>
            </div>
            <div>
                <span data-academy-dashboard-quizzes>0</span>
                <strong><?php echo esc_html(sprintf('/ %d quizzes passed', $total)); ?></strong>
            </div>
            <div>
                <span data-academy-dashboard-capstone><?php esc_html_e('Not complete', 'kingy-ai-launch-intelligence'); ?></span>
                <strong><?php esc_html_e('capstone status', 'kingy-ai-launch-intelligence'); ?></strong>
            </div>
            <div>
                <span data-academy-dashboard-certificate><?php esc_html_e('Locked', 'kingy-ai-launch-intelligence'); ?></span>
                <strong><?php esc_html_e('certificate status', 'kingy-ai-launch-intelligence'); ?></strong>
            </div>
        </div>
        <progress max="<?php echo esc_attr($total); ?>" value="0" data-academy-dashboard-progress></progress>
        <div class="kingy-ali-launch-academy-dashboard-next">
            <p data-academy-dashboard-next-text><?php esc_html_e('Start with Lesson 1 and create your first Launch Snapshot before moving into source checks, testing, and the capstone.', 'kingy-ai-launch-intelligence'); ?></p>
            <a href="<?php echo esc_url(home_url('/ai-launch-academy/lesson-1-what-is-ai-launch-intelligence/')); ?>" data-academy-dashboard-next-link><?php esc_html_e('Start Lesson 1', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
        <noscript><p><?php esc_html_e('This dashboard updates with browser storage when JavaScript is enabled. The full course remains readable without JavaScript.', 'kingy-ai-launch-intelligence'); ?></p></noscript>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_render_quiz($lesson) {
    $lesson_number = (int) $lesson['number'];
    $pass_score = kingy_ali_ai_launch_academy_required_quiz_score();

    ob_start();
    ?>
    <section class="kingy-ali-academy-section kingy-ali-launch-academy-quiz" data-academy-quiz data-academy-lesson-number="<?php echo esc_attr($lesson_number); ?>" data-academy-quiz-pass-score="<?php echo esc_attr($pass_score); ?>">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Short quiz', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Check your judgment', 'kingy-ai-launch-intelligence'); ?></h2>
        </div>
        <?php foreach ($lesson['quiz'] as $index => $quiz) : ?>
            <?php $choice_data = kingy_ali_ai_launch_academy_quiz_choices($lesson, $quiz, $index); ?>
            <fieldset class="kingy-ali-launch-academy-quiz-question" data-academy-quiz-question="<?php echo esc_attr($index); ?>" data-academy-quiz-correct="<?php echo esc_attr($choice_data['correct']); ?>">
                <legend><?php echo esc_html($quiz['question']); ?></legend>
                <div class="kingy-ali-launch-academy-quiz-choices">
                    <?php foreach ($choice_data['choices'] as $choice_index => $choice) : ?>
                        <?php $input_id = 'academy-quiz-' . $lesson_number . '-' . $index . '-' . $choice_index; ?>
                        <label for="<?php echo esc_attr($input_id); ?>">
                            <input id="<?php echo esc_attr($input_id); ?>" type="radio" name="<?php echo esc_attr('academy_quiz_' . $lesson_number . '_' . $index); ?>" value="<?php echo esc_attr($choice_index); ?>" data-academy-quiz-choice>
                            <span><?php echo esc_html($choice); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <details class="kingy-ali-launch-academy-quiz-answer">
                    <summary><?php esc_html_e('Readable answer', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php echo esc_html($quiz['answer']); ?></p>
                </details>
            </fieldset>
        <?php endforeach; ?>
        <div class="kingy-ali-launch-academy-quiz-actions">
            <button type="button" data-academy-quiz-submit><?php esc_html_e('Check Quiz', 'kingy-ai-launch-intelligence'); ?></button>
            <p data-academy-quiz-result><?php esc_html_e('Choose an answer for each question, then check your score.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <noscript><p><?php esc_html_e('Quiz answers are visible in the readable answer sections. Interactive scoring uses browser storage when JavaScript is enabled.', 'kingy-ai-launch-intelligence'); ?></p></noscript>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_template_filename($title) {
    return sanitize_title($title) . '.txt';
}

function kingy_ali_render_ai_launch_academy_landing() {
    $lessons = kingy_ali_ai_launch_academy_lessons();
    $module_map = kingy_ali_ai_launch_academy_modules();
    $audiences = array(
        array('title' => 'Founders', 'body' => 'Understand how buyers, creators, and analysts will judge your launch page, demo, pricing, and proof.'),
        array('title' => 'Marketers', 'body' => 'Turn launch noise into sharper positioning, comparison pages, campaign angles, and useful market context.'),
        array('title' => 'YouTubers and creators', 'body' => 'Find tools with strong demo potential, before/after stories, and comparison angles worth covering.'),
        array('title' => 'AI consultants', 'body' => 'Build a repeatable evaluation process for client recommendations, workflow audits, and stack reviews.'),
        array('title' => 'Small business owners', 'body' => 'Decide which AI tools are worth testing for real workflows without chasing every trend.'),
        array('title' => 'Product managers', 'body' => 'Classify launches, compare alternatives, and identify category movement before roadmaps get stale.'),
        array('title' => 'Startup employees', 'body' => 'Track competitors, pricing moves, product categories, and launch signals with better judgment.'),
        array('title' => 'Curious beginners', 'body' => 'Learn how to evaluate AI tools without needing a technical background or hype vocabulary.'),
    );

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-launch-academy" data-kingy-ai-launch-academy data-academy-total-lessons="<?php echo esc_attr(count($lessons)); ?>">
        <header class="kingy-ali-academy-hero kingy-ali-launch-academy-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI launch intelligence training', 'kingy-ai-launch-intelligence'); ?></p>
                <h1><?php esc_html_e('AI Launch Academy', 'kingy-ai-launch-intelligence'); ?></h1>
                <p class="kingy-ali-launch-academy-headline"><?php esc_html_e('Learn how to find, evaluate, test, and use new AI tools before everyone else.', 'kingy-ai-launch-intelligence'); ?></p>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('AI Launch Academy is a practical Kingy AI course for founders, marketers, creators, consultants, product teams, and curious non-technical people who want to understand what is launching in AI and what actually matters.', 'kingy-ai-launch-intelligence'); ?></p>
                <p class="kingy-ali-launch-academy-tagline"><?php esc_html_e('Stop drowning in AI launches. Learn how to find, test, and use the ones that actually matter.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="<?php echo esc_url(home_url('/ai-launch-academy/lesson-1-what-is-ai-launch-intelligence/')); ?>"><?php esc_html_e('Start Lesson 1: Build a Launch Snapshot', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Find a Launch to Practice On', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-launch-academy-hero-panel" aria-label="<?php esc_attr_e('Course outcome', 'kingy-ai-launch-intelligence'); ?>">
                <strong><?php esc_html_e('By the end', 'kingy-ai-launch-intelligence'); ?></strong>
                <p><?php esc_html_e('You should be able to look at any new AI tool, model, app, agent, startup, or product launch and decide what launched, whether it is real, who it is for, how to test it, and whether it is worth using, covering, buying, building with, recommending, saving, or ignoring.', 'kingy-ai-launch-intelligence'); ?></p>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav kingy-ali-launch-academy-nav" aria-label="<?php esc_attr_e('AI Launch Academy sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#sample-brief"><?php esc_html_e('Sample', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#workflow"><?php esc_html_e('Workflow', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#dashboard"><?php esc_html_e('Dashboard', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#why"><?php esc_html_e('Why', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#learn"><?php esc_html_e('Learn', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#audience"><?php esc_html_e('Who', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#curriculum"><?php esc_html_e('Curriculum', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#resources"><?php esc_html_e('Resources', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="sample-brief" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Sample outcome', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('A launch brief should end in a decision, not a pile of notes', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <p><?php esc_html_e('This is the shape of the practical output you build toward. It is clearly marked as an example, not a claim about a real launch.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-practical-grid">
                <?php foreach (kingy_ali_ai_launch_academy_sample_brief() as $brief_item) : ?>
                    <article class="kingy-ali-practical-card">
                        <h3><?php echo esc_html($brief_item['label']); ?></h3>
                        <p><?php echo esc_html($brief_item['value']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-callout"><strong><?php esc_html_e('Course habit:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('Every lesson pushes toward a concrete artifact: a snapshot, source note, hype scorecard, test sheet, battle card, weekly stack, or final analyst brief.', 'kingy-ai-launch-intelligence'); ?></div>
        </section>

        <section id="workflow" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('How the workflow works', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Four passes from launch page to recommendation', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-launch-academy-module-grid">
                <?php foreach (kingy_ali_ai_launch_academy_workflow_steps() as $step) : ?>
                    <article class="kingy-ali-launch-academy-module">
                        <h3><?php echo esc_html($step['title']); ?></h3>
                        <p><?php echo esc_html($step['body']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php echo kingy_ali_ai_launch_academy_render_dashboard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <section id="why" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Why this course exists', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('AI launches are happening every day. Most people do not need more AI noise.', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <p><?php esc_html_e('New agents, video tools, coding assistants, model releases, open-source projects, productivity apps, wrappers, and hype cycles arrive constantly. The launch database tells people what launched. AI Launch Academy teaches people how to think about what launched.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-callout"><strong><?php esc_html_e('Core promise:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('Find, verify, evaluate, test, compare, and use AI launches before everyone else without confusing hype for evidence.', 'kingy-ai-launch-intelligence'); ?></div>
        </section>

        <section id="learn" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('What you will learn', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('The Kingy system for reading the AI launch economy', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-launch-academy-check-grid">
                <?php foreach (array('Read an AI launch page', 'Verify official sources', 'Identify product category', 'Understand pricing and access', 'Test a tool in 30 minutes', 'Separate useful products from hype', 'Compare tools against alternatives', 'Build a weekly AI stack', 'Create launch briefs', 'Evaluate demo potential', 'Decide whether to use, cover, buy, save, ignore, or recommend a tool') as $item) : ?>
                    <div><span aria-hidden="true">OK</span><?php echo esc_html($item); ?></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="audience" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Who this course is for', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Built for busy people who need better AI launch judgment', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-practical-grid">
                <?php foreach ($audiences as $audience) : ?>
                    <article class="kingy-ali-practical-card">
                        <h3><?php echo esc_html($audience['title']); ?></h3>
                        <p><?php echo esc_html($audience['body']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="curriculum" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Course curriculum', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Five modules, twelve lessons, one practical analyst workflow', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-launch-academy-module-grid">
                <?php foreach ($module_map as $module) : ?>
                    <article class="kingy-ali-launch-academy-module">
                        <h3><?php echo esc_html($module['title']); ?></h3>
                        <p><?php echo esc_html($module['summary']); ?></p>
                        <?php if (!empty($module['practice'])) : ?>
                            <p><strong><?php esc_html_e('Practice output:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($module['practice']); ?></p>
                        <?php endif; ?>
                        <ol>
                            <?php foreach ($module['lessons'] as $lesson_number) : ?>
                                <?php $lesson = kingy_ali_ai_launch_academy_lesson_by_number($lesson_number); ?>
                                <li><a href="<?php echo esc_url(home_url('/ai-launch-academy/' . $lesson['slug'] . '/')); ?>"><?php echo esc_html(sprintf('Lesson %d: %s', (int) $lesson['number'], $lesson['title'])); ?></a></li>
                            <?php endforeach; ?>
                        </ol>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Final outcome', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Complete the course by creating a full AI Launch Analysis Brief', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <p><?php esc_html_e('The capstone asks you to evaluate a real AI launch with source verification, access notes, testing evidence, hype signals, alternatives, and a final recommendation. Generic examples are allowed only when clearly marked as examples, not real launches.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row"><a href="<?php echo esc_url(home_url('/ai-launch-academy/capstone/')); ?>"><?php esc_html_e('Open the Capstone', 'kingy-ai-launch-intelligence'); ?></a></div>
        </section>

        <section id="resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Resource library', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Templates and checklists included', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach (kingy_ali_ai_launch_academy_templates() as $template) : ?>
                    <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai-launch-academy/resources/#' . sanitize_title($template['title']))); ?>"><strong><?php echo esc_html($template['title']); ?></strong><span><?php echo esc_html($template['summary']); ?></span></a>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-cta-row">
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/resources/')); ?>"><?php esc_html_e('Open Resource Library', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/checklists/')); ?>"><?php esc_html_e('Open Checklists', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Optional certification', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Kingy Certified AI Launch Analyst', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <p><?php esc_html_e('The lightweight certification path sets a practical standard: complete the lessons, quizzes, capstone, and final AI Launch Analyst Brief. The Phase 2 certificate is generated in your browser so the course can stay useful without account or database writes.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row"><a href="<?php echo esc_url(home_url('/ai-launch-academy/certification/')); ?>"><?php esc_html_e('View Certification Path', 'kingy-ai-launch-intelligence'); ?></a></div>
        </section>

        <?php echo kingy_ali_ai_launch_academy_cta_links('academy_landing'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <section class="kingy-ali-link-panel">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Related Kingy links', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Use the course with live Kingy AI launch intelligence', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <?php echo kingy_ali_ai_launch_academy_render_link_grid(kingy_ali_ai_launch_academy_link_items(array('ai-launches', 'ai-launches/today', 'ai-tools', 'ai-companies', 'ai-launch-scorecard', 'ai-launches/launch-visibility-score', 'ai-courses'))); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </section>

        <section id="faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('AI Launch Academy questions', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <?php foreach (kingy_ali_ai_launch_academy_faqs() as $faq) : ?>
                    <details>
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <p><?php echo esc_html($faq['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_templates() {
    return array(
        array('title' => 'AI Launch Snapshot Template', 'summary' => 'A fast first-pass summary for one launch.', 'kind' => 'template', 'body' => "Product name:\nCompany/founder:\nLaunch date:\nCategory:\nOfficial source:\nProduct URL:\nWhat launched:\nWho it is for:\nAvailability:\nPricing/access:\nWhy it might matter:\nWhat feels unproven:\nNext action:"),
        array('title' => 'AI Launch Anatomy Checklist', 'summary' => 'The standard Kingy anatomy fields.', 'kind' => 'checklist', 'body' => "Name:\nCompany:\nLaunch date:\nCategory:\nOfficial source:\nProduct URL:\nPricing:\nFree plan:\nAPI:\nOpen-source/open-weight:\nDemo:\nTarget user:\nMain use case:\nBest alternative:\nWhy it matters:\nWhat feels unproven:\nKingy verdict:"),
        array('title' => 'Source Verification Checklist', 'summary' => 'A source quality note for launch claims.', 'kind' => 'checklist', 'body' => "Official product page:\nOfficial docs:\nOfficial pricing page:\nGitHub/Hugging Face repo:\nFounder/company announcement:\nProduct Hunt or app store page:\nDemo video:\nThird-party context:\nClaims verified:\nClaims still unverified:\nDate checked:"),
        array('title' => 'AI Hype Detector', 'summary' => 'Green, yellow, and red launch signals.', 'kind' => 'checklist', 'body' => "Green signals:\n- Working product:\n- Clear pricing:\n- Clear demo:\n- Clear target user:\n- Specific use case:\n\nYellow signals:\n- Waitlist only:\n- Unclear pricing:\n- Vague claims:\n- Limited demo:\n\nRed signals:\n- No official source:\n- No working product:\n- No way to test:\n- Impossible claims:\n\nVerdict:"),
        array('title' => '30-Minute AI Tool Test Sheet', 'summary' => 'A timed test for one new tool.', 'kind' => 'template', 'body' => "Tool:\nTest date:\nMinute 0-5: What did the launch page claim?\nMinute 5-10: Could I access the product?\nMinute 10-15: Simple task result:\nMinute 15-25: Hard task result:\nMinute 25-30: Decision:\nVerdict label:\nWould I test again?\nNotes:"),
        array('title' => 'AI Launch Battle Card', 'summary' => 'Compare a launch against alternatives.', 'kind' => 'template', 'body' => "Tool:\nAlternative:\nCategory:\nPricing:\nFree plan:\nBest for:\nWeakness:\nEase of use:\nOutput quality:\nSpeed:\nIntegrations:\nAPI:\nPrivacy:\nBest user:\nFinal verdict:"),
        array('title' => 'Weekly AI Stack Planner', 'summary' => 'A weekly shortlist across launch categories.', 'kind' => 'template', 'body' => "Week of:\nAI coding tool:\nAI agent/automation product:\nAI video or image tool:\nAI research/search tool:\nAI productivity/business tool:\nModel or infrastructure update:\nWild experimental tool:\nUse this week:\nTest later:\nWatchlist:\nIgnore:\nMain lesson from the week:"),
        array('title' => 'Creator Coverage Checklist', 'summary' => 'Check whether a launch has demo or content potential.', 'kind' => 'checklist', 'body' => "Clear visual demo:\nBefore/after story:\nSurprising use case:\nComparison angle:\nAccessible test account:\nClear pricing/access:\nPermission-safe footage:\nAudience fit:\nRisks or caveats:\nBest content angle:"),
        array('title' => 'Founder Launch Readiness Checklist', 'summary' => 'See how analysts and creators will read a launch.', 'kind' => 'checklist', 'body' => "Who is it for?\nWhat does it do?\nHow do people try it?\nWhat does it cost?\nIs the demo clear?\nAre docs available?\nWhat is the best comparison?\nWhat claim needs proof?\nWhat should creators show?\nWhat should buyers trust?"),
        array('title' => 'Buyer Adoption Checklist', 'summary' => 'Decide whether a tool deserves business use.', 'kind' => 'checklist', 'body' => "Workflow improved:\nCurrent tool replaced:\nPricing clear:\nData/privacy reviewed:\nAccess available:\nTeam setup effort:\nSupport/docs:\nIntegration needs:\nRisk level:\nDecision: use, test, watch, or ignore."),
        array('title' => 'AI Launch Analyst Brief Template', 'summary' => 'The full final analyst output.', 'kind' => 'template', 'body' => "Product name:\nCompany/founder:\nLaunch date:\nOfficial source:\nProduct URL:\nCategory:\nWhat launched:\nWhat it does:\nWho it is for:\nPricing:\nFree plan/trial:\nAPI availability:\nOpen-source/open-weight status:\nDemo quality:\nReal use case:\nCompetitors/alternatives:\n30-minute test result:\nGreen signals:\nYellow signals:\nRed signals:\nYouTube/demo potential:\nBuyer usefulness:\nWhat feels unproven:\nFinal verdict:\nRecommendation:"),
    );
}

function kingy_ali_render_ai_launch_academy_resources($checklists_only = false) {
    $templates = array_filter(
        kingy_ali_ai_launch_academy_templates(),
        function ($template) use ($checklists_only) {
            return !$checklists_only || $template['kind'] === 'checklist';
        }
    );
    $heading = $checklists_only ? 'AI Launch Academy Checklists' : 'AI Launch Academy Resources';
    $lede = $checklists_only ? 'Quick checklists for source verification, hype detection, pricing clarity, creator coverage, founder readiness, and buyer adoption.' : 'Copy-ready templates and checklists for evaluating, testing, comparing, and briefing AI launches.';

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-launch-academy kingy-ali-launch-academy-resources" data-kingy-ai-launch-academy>
        <?php echo kingy_ali_ai_launch_academy_breadcrumb($checklists_only ? 'Checklists' : 'Resources'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <header class="kingy-ali-academy-hero">
            <p class="kingy-ali-kicker"><?php echo esc_html($checklists_only ? 'Quick access' : 'Resource library'); ?></p>
            <h1><?php echo esc_html($heading); ?></h1>
            <p class="kingy-ali-academy-lede"><?php echo esc_html($lede); ?></p>
        </header>
        <section class="kingy-ali-launch-academy-template-grid">
            <?php foreach ($templates as $index => $template) : ?>
                <?php $id = 'academy-template-' . sanitize_title($template['title']); ?>
                <article class="kingy-ali-copy-prompt kingy-ali-launch-academy-template" id="<?php echo esc_attr(sanitize_title($template['title'])); ?>">
                    <div>
                        <p class="kingy-ali-kicker"><?php echo esc_html($template['kind']); ?></p>
                        <h2><?php echo esc_html($template['title']); ?></h2>
                        <p><?php echo esc_html($template['summary']); ?></p>
                    </div>
                    <pre><code id="<?php echo esc_attr($id); ?>"><?php echo esc_html($template['body']); ?></code></pre>
                    <div class="kingy-ali-launch-academy-template-actions">
                        <button type="button" data-academy-copy-target="#<?php echo esc_attr($id); ?>" data-academy-template-title="<?php echo esc_attr($template['title']); ?>"><?php esc_html_e('Copy Template', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="button" data-academy-download-target="#<?php echo esc_attr($id); ?>" data-academy-download-filename="<?php echo esc_attr(kingy_ali_ai_launch_academy_template_filename($template['title'])); ?>" data-academy-template-title="<?php echo esc_attr($template['title']); ?>"><?php esc_html_e('Download TXT', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <section class="kingy-ali-link-panel">
            <div class="kingy-ali-cta-row">
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/')); ?>"><?php esc_html_e('Back to Course', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/capstone/')); ?>"><?php esc_html_e('Use the Capstone Template', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_ai_launch_academy_lesson($lesson) {
    $total = count(kingy_ali_ai_launch_academy_lessons());
    $number = (int) $lesson['number'];
    $prev = $number > 1 ? kingy_ali_ai_launch_academy_lesson_by_number($number - 1) : array();
    $next = $number < $total ? kingy_ali_ai_launch_academy_lesson_by_number($number + 1) : array();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-launch-academy kingy-ali-launch-academy-lesson" data-kingy-ai-launch-academy data-academy-total-lessons="<?php echo esc_attr($total); ?>" data-academy-lesson-number="<?php echo esc_attr($number); ?>">
        <?php echo kingy_ali_ai_launch_academy_breadcrumb(sprintf('Lesson %d', $number)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <header class="kingy-ali-academy-hero kingy-ali-launch-academy-lesson-hero">
            <div>
                <p class="kingy-ali-kicker"><?php echo esc_html(sprintf('Lesson %d of %d', $number, $total)); ?></p>
                <h1><?php echo esc_html($lesson['title']); ?></h1>
                <p class="kingy-ali-academy-lede"><?php echo esc_html($lesson['intro']); ?></p>
                <div class="kingy-ali-hero-meta">
                    <span><?php echo esc_html($lesson['module']); ?></span>
                    <span><?php echo esc_html($lesson['time']); ?></span>
                    <span><?php echo esc_html($lesson['difficulty']); ?></span>
                    <span><?php echo esc_html('Outcome: ' . $lesson['outcome']); ?></span>
                </div>
            </div>
        </header>

        <section class="kingy-ali-launch-academy-progress" data-academy-progress>
            <div>
                <strong><span data-academy-progress-count>0</span> / <?php echo esc_html($total); ?></strong>
                <span><?php esc_html_e(' lessons complete', 'kingy-ai-launch-intelligence'); ?></span>
            </div>
            <progress max="<?php echo esc_attr($total); ?>" value="0" data-academy-progress-bar></progress>
            <div class="kingy-ali-launch-academy-lesson-checks">
                <?php foreach (array('reading' => 'I completed the reading', 'exercise' => 'I completed the exercise', 'quiz' => 'I completed the quiz', 'notes' => 'I saved my notes') as $key => $label) : ?>
                    <label><input type="checkbox" data-academy-lesson-check="<?php echo esc_attr($key); ?>"> <?php echo esc_html($label); ?></label>
                <?php endforeach; ?>
            </div>
            <button type="button" data-academy-mark-complete><?php esc_html_e('Mark Lesson Complete', 'kingy-ai-launch-intelligence'); ?></button>
            <noscript><p><?php esc_html_e('Progress saving uses browser storage. The lesson remains fully readable without JavaScript.', 'kingy-ai-launch-intelligence'); ?></p></noscript>
        </section>

        <?php foreach ($lesson['sections'] as $section) : ?>
            <section class="kingy-ali-academy-section">
                <div class="kingy-ali-section-heading">
                    <h2><?php echo esc_html($section['heading']); ?></h2>
                </div>
                <?php foreach ($section['body'] as $paragraph) : ?>
                    <p><?php echo esc_html($paragraph); ?></p>
                <?php endforeach; ?>
                <?php if (!empty($section['items'])) : ?>
                    <ul class="kingy-ali-launch-academy-list">
                        <?php foreach ($section['items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <section class="kingy-ali-launch-academy-callouts">
            <div class="kingy-ali-callout kingy-ali-callout--tip"><strong><?php esc_html_e('Kingy Tip:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($lesson['tip']); ?></div>
            <?php if (!empty($lesson['red_flag'])) : ?>
                <div class="kingy-ali-callout kingy-ali-callout--red"><strong><?php esc_html_e('Red Flag:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($lesson['red_flag']); ?></div>
            <?php endif; ?>
        </section>

        <section class="kingy-ali-academy-section kingy-ali-launch-academy-action">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Try this', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Exercise', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <p><?php echo esc_html($lesson['exercise']); ?></p>
            <h3><?php esc_html_e('Deliverable', 'kingy-ai-launch-intelligence'); ?></h3>
            <p><?php echo esc_html($lesson['deliverable']); ?></p>
        </section>

        <?php echo kingy_ali_ai_launch_academy_render_quiz($lesson); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <section class="kingy-ali-academy-section kingy-ali-launch-academy-do-now">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Do this now', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php echo esc_html($lesson['do_now']); ?></h2>
            </div>
        </section>

        <nav class="kingy-ali-launch-academy-lesson-nav" aria-label="<?php esc_attr_e('Lesson navigation', 'kingy-ai-launch-intelligence'); ?>">
            <?php if ($prev) : ?>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/' . $prev['slug'] . '/')); ?>"><?php echo esc_html('Previous: Lesson ' . (int) $prev['number']); ?></a>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/')); ?>"><?php esc_html_e('Back to Course Overview', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <?php if ($next) : ?>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/' . $next['slug'] . '/')); ?>"><?php echo esc_html('Next: Lesson ' . (int) $next['number']); ?></a>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/capstone/')); ?>"><?php esc_html_e('Next: Capstone', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </nav>

        <section class="kingy-ali-link-panel">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Related Kingy links', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Practice with real Kingy AI surfaces', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <?php echo kingy_ali_ai_launch_academy_render_link_grid(kingy_ali_ai_launch_academy_link_items($lesson['links'])); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </section>

        <?php echo kingy_ali_ai_launch_academy_cta_links('academy_lesson_' . $number); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_ai_launch_academy_capstone() {
    $sections = array('Product name', 'Company/founder', 'Launch date', 'Official source', 'Product URL', 'Category', 'What launched', 'What it does', 'Who it is for', 'Pricing', 'Free plan/trial', 'API availability', 'Open-source/open-weight status', 'Demo quality', 'Real use case', 'Competitors/alternatives', '30-minute test result', 'Green signals', 'Yellow signals', 'Red signals', 'YouTube/demo potential', 'Buyer usefulness', 'What feels unproven', 'Final verdict', 'Recommendation');
    $verdicts = array('Ignore for now', 'Watchlist', 'Worth testing', 'Useful for a specific audience', 'Strong creator/demo candidate', 'Buyer-ready', 'Promising but unproven', 'Overhyped', 'Needs more proof', 'Potentially category-defining');

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-launch-academy" data-kingy-ai-launch-academy data-academy-total-lessons="<?php echo esc_attr(count(kingy_ali_ai_launch_academy_lessons())); ?>">
        <?php echo kingy_ali_ai_launch_academy_breadcrumb('Capstone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <header class="kingy-ali-academy-hero">
            <p class="kingy-ali-kicker"><?php esc_html_e('Capstone', 'kingy-ai-launch-intelligence'); ?></p>
            <h1><?php esc_html_e('Evaluate a Real AI Launch', 'kingy-ai-launch-intelligence'); ?></h1>
            <p class="kingy-ali-academy-lede"><?php esc_html_e('Choose one new AI tool, app, model, startup, or product launch and create a full launch evaluation. Use a real launch only when you can verify it from official or credible sources. Otherwise mark the work as an example.', 'kingy-ai-launch-intelligence'); ?></p>
        </header>
        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><h2><?php esc_html_e('Required sections', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <ol class="kingy-ali-launch-academy-two-col-list">
                <?php foreach ($sections as $section) : ?>
                    <li><?php echo esc_html($section); ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><h2><?php esc_html_e('Final verdict options', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-launch-academy-verdicts">
                <?php foreach ($verdicts as $verdict) : ?>
                    <span><?php echo esc_html($verdict); ?></span>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="kingy-ali-copy-prompt kingy-ali-launch-academy-template">
            <?php $capstone_id = 'academy-capstone-template'; ?>
            <h2><?php esc_html_e('Copy the capstone brief template', 'kingy-ai-launch-intelligence'); ?></h2>
            <pre><code id="<?php echo esc_attr($capstone_id); ?>"><?php echo esc_html(kingy_ali_ai_launch_academy_templates()[10]['body']); ?></code></pre>
            <div class="kingy-ali-launch-academy-template-actions">
                <button type="button" data-academy-copy-target="#<?php echo esc_attr($capstone_id); ?>" data-academy-template-title="<?php esc_attr_e('AI Launch Analyst Brief Template', 'kingy-ai-launch-intelligence'); ?>"><?php esc_html_e('Copy Template', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-academy-download-target="#<?php echo esc_attr($capstone_id); ?>" data-academy-download-filename="<?php echo esc_attr(kingy_ali_ai_launch_academy_template_filename('AI Launch Analyst Brief Template')); ?>" data-academy-template-title="<?php esc_attr_e('AI Launch Analyst Brief Template', 'kingy-ai-launch-intelligence'); ?>"><?php esc_html_e('Download TXT', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>
        <section class="kingy-ali-academy-section kingy-ali-launch-academy-capstone-tracker" data-academy-capstone>
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Capstone tracker', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Mark your analyst brief complete', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-launch-academy-lesson-checks">
                <?php foreach (array('verified_sources' => 'I verified official or credible sources', 'tested_launch' => 'I tested or clearly marked test limits', 'compared_alternatives' => 'I compared alternatives or old workflows', 'final_verdict' => 'I wrote a final verdict and recommendation') as $key => $label) : ?>
                    <label><input type="checkbox" data-academy-capstone-check="<?php echo esc_attr($key); ?>"> <?php echo esc_html($label); ?></label>
                <?php endforeach; ?>
            </div>
            <button type="button" data-academy-mark-capstone><?php esc_html_e('Mark Capstone Complete', 'kingy-ai-launch-intelligence'); ?></button>
            <p data-academy-capstone-status><?php esc_html_e('Complete each capstone check to unlock the certificate generator.', 'kingy-ai-launch-intelligence'); ?></p>
            <noscript><p><?php esc_html_e('The capstone tracker saves in browser storage when JavaScript is enabled. The template and requirements remain readable without JavaScript.', 'kingy-ai-launch-intelligence'); ?></p></noscript>
        </section>
        <section class="kingy-ali-link-panel">
            <div class="kingy-ali-cta-row">
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/resources/')); ?>"><?php esc_html_e('Open Resource Library', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/certification/')); ?>"><?php esc_html_e('Review Certification Path', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_ai_launch_academy_certification() {
    $total = count(kingy_ali_ai_launch_academy_lessons());
    $requirements = array(
        'lessons' => 'Complete all 12 lessons',
        'quizzes' => 'Pass all 12 lesson quizzes',
        'capstone' => 'Complete the capstone tracker',
    );

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-launch-academy" data-kingy-ai-launch-academy data-academy-total-lessons="<?php echo esc_attr($total); ?>">
        <?php echo kingy_ali_ai_launch_academy_breadcrumb('Certification'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <header class="kingy-ali-academy-hero">
            <p class="kingy-ali-kicker"><?php esc_html_e('Client-side certificate path', 'kingy-ai-launch-intelligence'); ?></p>
            <h1><?php esc_html_e('Kingy Certified AI Launch Analyst', 'kingy-ai-launch-intelligence'); ?></h1>
            <p class="kingy-ali-academy-lede"><?php esc_html_e('Generate a local, printable certificate after completing the lessons, quizzes, and capstone. The name stays in browser storage and is not sent to Kingy AI.', 'kingy-ai-launch-intelligence'); ?></p>
        </header>
        <section class="kingy-ali-academy-section kingy-ali-launch-academy-certificate" data-academy-certificate>
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Certificate generator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Unlock your printable analyst certificate', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <ul class="kingy-ali-launch-academy-list kingy-ali-launch-academy-requirements">
                <?php foreach ($requirements as $key => $requirement) : ?>
                    <li data-academy-certificate-requirement="<?php echo esc_attr($key); ?>"><?php echo esc_html($requirement); ?></li>
                <?php endforeach; ?>
            </ul>
            <p data-academy-certificate-status><?php esc_html_e('Finish the requirements above to unlock certificate generation.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-launch-academy-certificate-form">
                <label for="academy-certificate-name"><?php esc_html_e('Display name for certificate', 'kingy-ai-launch-intelligence'); ?></label>
                <input id="academy-certificate-name" type="text" maxlength="80" autocomplete="name" placeholder="<?php esc_attr_e('Your name', 'kingy-ai-launch-intelligence'); ?>" data-academy-certificate-name>
                <button type="button" data-academy-certificate-generate><?php esc_html_e('Generate Certificate', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-academy-certificate-print><?php esc_html_e('Print Certificate', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
            <div class="kingy-ali-launch-academy-certificate-preview" data-academy-certificate-preview>
                <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Certified AI Launch Analyst', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Awarded to', 'kingy-ai-launch-intelligence'); ?></p>
                <strong data-academy-certificate-student><?php esc_html_e('Your Name', 'kingy-ai-launch-intelligence'); ?></strong>
                <p><?php esc_html_e('For completing AI Launch Academy and producing an AI Launch Analyst Brief using the Kingy framework.', 'kingy-ai-launch-intelligence'); ?></p>
                <span data-academy-certificate-date><?php echo esc_html(date_i18n(get_option('date_format'))); ?></span>
            </div>
            <noscript><p><?php esc_html_e('Certificate generation uses browser storage and JavaScript. The requirements remain readable without JavaScript.', 'kingy-ai-launch-intelligence'); ?></p></noscript>
            <div class="kingy-ali-cta-row">
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/lesson-1-what-is-ai-launch-intelligence/')); ?>"><?php esc_html_e('Start Lesson 1', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/ai-launch-academy/capstone/')); ?>"><?php esc_html_e('Open Capstone', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_render_link_grid($items) {
    if (empty($items)) {
        return '';
    }

    ob_start();
    ?>
    <div class="kingy-ali-resource-grid">
        <?php foreach ($items as $item) : ?>
            <a class="kingy-ali-codex-resource" href="<?php echo esc_url($item['url']); ?>"><strong><?php echo esc_html($item['label']); ?></strong><span><?php echo esc_html($item['description']); ?></span></a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_launch_academy_faqs() {
    return array(
        array('question' => 'Is AI Launch Academy a generic learn AI course?', 'answer' => 'No. It is focused on launch intelligence: finding, verifying, evaluating, testing, comparing, and deciding what to do with new AI launches.'),
        array('question' => 'Do I need to be technical?', 'answer' => 'No. The course is written for busy non-technical people as well as founders, marketers, creators, consultants, and product teams.'),
        array('question' => 'Does the course use fake launch claims?', 'answer' => 'No. Real examples should come from verified Kingy data or credible sources. Generic examples must be clearly marked as examples.'),
        array('question' => 'What is the final project?', 'answer' => 'A full AI Launch Analysis Brief that verifies a launch, tests it, compares it, flags hype signals, and ends with a practical verdict.'),
    );
}

function kingy_ali_ai_launch_academy_meta_description() {
    $page_key = kingy_ali_ai_launch_academy_current_page_key();
    if ($page_key === '') {
        return;
    }

    $pages = kingy_ali_ai_launch_academy_pages();
    if (empty($pages[$page_key]['description'])) {
        return;
    }

    echo '<meta name="description" content="' . esc_attr($pages[$page_key]['description']) . '">' . "\n";
}

function kingy_ali_ai_launch_academy_schema() {
    $page_key = kingy_ali_ai_launch_academy_current_page_key();
    if ($page_key === '') {
        return;
    }

    $pages = kingy_ali_ai_launch_academy_pages();
    if (empty($pages[$page_key])) {
        return;
    }

    $page = $pages[$page_key];
    $breadcrumb_items = array(
        array('@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')),
        array('@type' => 'ListItem', 'position' => 2, 'name' => 'AI Launch Academy', 'item' => home_url('/ai-launch-academy/')),
    );

    if ($page_key !== 'landing') {
        $breadcrumb_items[] = array('@type' => 'ListItem', 'position' => 3, 'name' => $page['title'], 'item' => home_url('/' . trim($page['path'], '/') . '/'));
    }

    $graph = array(
        array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumb_items,
        ),
    );

    if ($page_key === 'landing') {
        $graph[] = array(
            '@type' => 'Course',
            'name' => 'AI Launch Academy',
            'description' => 'A practical Kingy AI course for finding, verifying, evaluating, testing, comparing, and using new AI tools, models, agents, apps, and startups.',
            'provider' => array('@type' => 'Organization', 'name' => 'Kingy AI', 'url' => home_url('/')),
            'url' => home_url('/ai-launch-academy/'),
            'educationalLevel' => 'Beginner to intermediate',
            'teaches' => array('AI launch intelligence', 'AI tool evaluation', 'AI source verification', 'AI tool testing', 'AI product comparison'),
            'hasPart' => array_map(
                function ($lesson) {
                    return array(
                        '@type' => 'LearningResource',
                        'name' => sprintf('Lesson %d: %s', (int) $lesson['number'], $lesson['title']),
                        'position' => (int) $lesson['number'],
                        'url' => home_url('/ai-launch-academy/' . $lesson['slug'] . '/'),
                    );
                },
                kingy_ali_ai_launch_academy_lessons()
            ),
        );
    }

    if ($page['type'] === 'LearningResource') {
        $lesson = kingy_ali_ai_launch_academy_lesson_by_slug($page['page']);
        $resource = array(
            '@type' => 'LearningResource',
            'name' => $page['title'],
            'description' => $page['description'],
            'url' => home_url('/' . trim($page['path'], '/') . '/'),
            'learningResourceType' => 'Lesson',
            'educationalLevel' => isset($lesson['difficulty']) ? $lesson['difficulty'] : 'Beginner',
            'isPartOf' => array(
                '@type' => 'Course',
                'name' => 'AI Launch Academy',
                'url' => home_url('/ai-launch-academy/'),
            ),
        );

        if (!empty($lesson['number'])) {
            $resource['position'] = (int) $lesson['number'];
        }

        if (!empty($lesson['outcome'])) {
            $resource['teaches'] = $lesson['outcome'];
        }

        $graph[] = $resource;
    }

    $schema = array('@context' => 'https://schema.org', '@graph' => $graph);
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
