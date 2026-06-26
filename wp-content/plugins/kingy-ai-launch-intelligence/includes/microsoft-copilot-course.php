<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_microsoft_copilot_course', 'kingy_ali_shortcode_microsoft_copilot_course');
add_filter('the_content', 'kingy_ali_maybe_replace_microsoft_copilot_course', 20);
add_filter('wpseo_title', 'kingy_ali_microsoft_copilot_course_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_microsoft_copilot_course_seo_description');
add_filter('document_title_parts', 'kingy_ali_microsoft_copilot_course_document_title');
add_action('wp_head', 'kingy_ali_microsoft_copilot_course_schema');

function kingy_ali_is_microsoft_copilot_course_page() {
    return kingy_ali_is_rendering_page_slug('microsoft-copilot-course');
}

function kingy_ali_maybe_replace_microsoft_copilot_course($content) {
    if (!kingy_ali_is_microsoft_copilot_course_page()) {
        return $content;
    }

    return kingy_ali_shortcode_microsoft_copilot_course();
}

function kingy_ali_microsoft_copilot_course_seo_title($title) {
    if (!kingy_ali_is_microsoft_copilot_course_page()) {
        return $title;
    }

    return __('Microsoft Copilot Course: Zero to Hero, Prompts, Agents, Studio', 'kingy-ai-launch-intelligence');
}

function kingy_ali_microsoft_copilot_course_seo_description($description) {
    if (!kingy_ali_is_microsoft_copilot_course_page()) {
        return $description;
    }

    return __('Learn Microsoft Copilot with a full curriculum, path chooser, readiness checklist, prompt builder, progress tracker, quizzes, ROI calculator, agents, Copilot Studio, and governance.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_microsoft_copilot_course_document_title($parts) {
    if (kingy_ali_is_microsoft_copilot_course_page()) {
        $parts['title'] = __('Microsoft Copilot Zero to Hero', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_microsoft_copilot_course_modules() {
    return array(
        array('id' => 'm00', 'num' => '00', 'title' => 'Course Orientation and Setup', 'track' => 'beginner', 'level' => 'Beginner', 'time' => '35 min', 'outcome' => 'Check your account, license, apps, and safety baseline before learning workflows.', 'capstone' => 'Create your first Copilot safety checklist.', 'lessons' => array('Welcome to Microsoft Copilot Zero to Hero', 'What you need before you start', 'Microsoft account vs work or school account vs Microsoft 365 Copilot license', 'How to check which Copilot features you have', 'How to use this course without getting overwhelmed', 'Your first Copilot safety checklist')),
        array('id' => 'm01', 'num' => '01', 'title' => 'What Microsoft Copilot Is', 'track' => 'beginner', 'level' => 'Beginner', 'time' => '40 min', 'outcome' => 'Understand the Copilot ecosystem, Chat, Microsoft 365 Copilot, app experiences, and common limits.', 'capstone' => 'Build a one-page Copilot ecosystem map.', 'lessons' => array('What is Microsoft Copilot?', 'Copilot Chat vs Microsoft 365 Copilot', 'Copilot vs ChatGPT vs Gemini vs Claude', 'What Copilot can and cannot do', 'How Copilot uses context', 'The Copilot ecosystem map')),
        array('id' => 'm02', 'num' => '02', 'title' => 'AI and Prompting Foundations', 'track' => 'beginner', 'level' => 'Beginner', 'time' => '45 min', 'outcome' => 'Write clear prompts with a repeatable Goal, Context, Expectations, Source structure.', 'capstone' => 'Create your first 25 useful Copilot prompts.', 'lessons' => array('What a prompt is', 'The four-part prompt formula', 'How to give Copilot better context', 'How to ask for format, tone, and length', 'How to iterate instead of starting over', 'Your first 25 useful Copilot prompts')),
        array('id' => 'm03', 'num' => '03', 'title' => 'Advanced Prompting', 'track' => 'beginner', 'level' => 'Intermediate', 'time' => '50 min', 'outcome' => 'Turn one-off prompts into reusable templates, review loops, and verification habits.', 'capstone' => 'Build a personal prompt library.', 'lessons' => array('Prompt patterns for real work', 'How to create reusable prompt templates', 'How to make Copilot ask clarifying questions', 'How to verify Copilot answers', 'How to build a personal prompt library', 'Advanced prompting challenge')),
        array('id' => 'm04', 'num' => '04', 'title' => 'Microsoft 365 Copilot Chat', 'track' => 'office', 'level' => 'Beginner', 'time' => '45 min', 'outcome' => 'Use web, work, and file context intentionally in Copilot Chat.', 'capstone' => 'Build an everyday Copilot Chat workflow.', 'lessons' => array('Getting started with Copilot Chat', 'Web data vs work data vs uploaded files', 'How to ask Copilot Chat for useful answers', 'How to create content with Copilot Chat', 'How to catch up on work with Copilot Chat', 'Copilot Chat workflows you can use every day')),
        array('id' => 'm05', 'num' => '05', 'title' => 'Copilot in Word', 'track' => 'office', 'level' => 'Beginner', 'time' => '45 min', 'outcome' => 'Draft, rewrite, summarize, and structure professional documents.', 'capstone' => 'Build a professional business document.', 'lessons' => array('Getting started with Copilot in Word', 'Drafting documents from scratch', 'Rewriting, editing, and changing tone', 'Summarizing long documents', 'Turning notes into reports, proposals, and SOPs', 'Word capstone')),
        array('id' => 'm06', 'num' => '06', 'title' => 'Copilot in PowerPoint', 'track' => 'office', 'level' => 'Beginner', 'time' => '45 min', 'outcome' => 'Create, refine, and present decks from ideas and documents.', 'capstone' => 'Create a complete business deck.', 'lessons' => array('Getting started with Copilot in PowerPoint', 'Creating a presentation from an idea', 'Creating a presentation from a document', 'Improving slide structure and clarity', 'Speaker notes and presentation prep', 'PowerPoint capstone')),
        array('id' => 'm07', 'num' => '07', 'title' => 'Copilot in Excel Foundations', 'track' => 'excel', 'level' => 'Beginner', 'time' => '55 min', 'outcome' => 'Prepare data, ask spreadsheet questions, and verify formulas.', 'capstone' => 'Clean and explain a dataset.', 'lessons' => array('Getting started with Copilot in Excel', 'How to prepare data so Copilot can understand it', 'Asking questions about a spreadsheet', 'Creating and explaining formulas', 'Sorting, filtering, highlighting, and cleaning data', 'Excel foundations capstone')),
        array('id' => 'm08', 'num' => '08', 'title' => 'Copilot in Excel Advanced and Analyst', 'track' => 'excel', 'level' => 'Intermediate', 'time' => '60 min', 'outcome' => 'Move from spreadsheet questions to analysis plans, trends, outliers, and dashboards.', 'capstone' => 'Create an executive analysis report.', 'lessons' => array('From spreadsheet questions to analysis plans', 'Finding trends, outliers, and patterns', 'Scenario analysis and forecasting prompts', 'Building PivotTables, charts, and dashboards', 'Getting started with Analyst', 'Excel advanced capstone')),
        array('id' => 'm09', 'num' => '09', 'title' => 'Copilot in Outlook', 'track' => 'meetings', 'level' => 'Beginner', 'time' => '45 min', 'outcome' => 'Summarize threads, draft clear replies, and prepare meeting follow-ups.', 'capstone' => 'Build an email productivity system.', 'lessons' => array('Getting started with Copilot in Outlook', 'Summarizing long email threads', 'Drafting better emails faster', 'Tone, clarity, and executive communication', 'Meeting prep and follow-up workflows', 'Outlook capstone')),
        array('id' => 'm10', 'num' => '10', 'title' => 'Copilot in Teams', 'track' => 'meetings', 'level' => 'Beginner', 'time' => '45 min', 'outcome' => 'Use Copilot before, during, and after meetings without losing accountability.', 'capstone' => 'Run a Copilot-powered meeting workflow.', 'lessons' => array('Getting started with Copilot in Teams', 'Catching up on chats and channels', 'Using Copilot during meetings', 'Using Copilot after meetings', 'Action items, decisions, risks, and follow-ups', 'Teams capstone')),
        array('id' => 'm11', 'num' => '11', 'title' => 'OneNote, Loop, and Copilot Notebooks', 'track' => 'office', 'level' => 'Intermediate', 'time' => '45 min', 'outcome' => 'Create reusable project knowledge spaces for research, notes, and planning.', 'capstone' => 'Build a project brain.', 'lessons' => array('Copilot for notes and knowledge work', 'Getting started with Copilot in OneNote', 'Getting started with Copilot Notebooks', 'Adding references, files, links, and chats', 'Using notebooks for projects, research, and planning', 'Notebook capstone')),
        array('id' => 'm12', 'num' => '12', 'title' => 'OneDrive, SharePoint, Files, and Work Data', 'track' => 'governance', 'level' => 'Intermediate', 'time' => '55 min', 'outcome' => 'Organize files and permissions so work-grounded answers are more useful and safer.', 'capstone' => 'Build a Copilot-ready project folder.', 'lessons' => array('Why files and permissions matter', 'OneDrive basics for Copilot users', 'SharePoint basics for Copilot users', 'How to reference files in prompts', 'How to organize work data for better Copilot answers', 'Work data capstone')),
        array('id' => 'm13', 'num' => '13', 'title' => 'Researcher, Analyst, and Microsoft-Built Agents', 'track' => 'agents', 'level' => 'Intermediate', 'time' => '50 min', 'outcome' => 'Choose when to use chat, Researcher, Analyst, or a built-in agent.', 'capstone' => 'Choose the right agent for the job.', 'lessons' => array('What are Microsoft 365 Copilot agents?', 'When to use chat vs an agent', 'Getting started with Researcher', 'Getting started with Analyst', 'Word, Excel, PowerPoint, and other Microsoft-built agents', 'Agent capstone')),
        array('id' => 'm14', 'num' => '14', 'title' => 'Agent Builder', 'track' => 'agents', 'level' => 'Intermediate', 'time' => '55 min', 'outcome' => 'Plan, instruct, test, and improve a lightweight helper agent.', 'capstone' => 'Build a team helper agent.', 'lessons' => array('What an agent is in plain English', 'Good agent ideas vs bad agent ideas', 'Building your first agent', 'Writing better agent instructions', 'Adding knowledge and testing responses', 'Agent Builder capstone')),
        array('id' => 'm15', 'num' => '15', 'title' => 'Copilot Studio Fundamentals', 'track' => 'studio', 'level' => 'Intermediate', 'time' => '60 min', 'outcome' => 'Plan, build, test, and govern a simple Copilot Studio agent.', 'capstone' => 'Build an FAQ agent.', 'lessons' => array('What is Microsoft Copilot Studio?', 'Copilot Studio vs Agent Builder', 'Creating your first Copilot Studio agent', 'Topics, knowledge, and generative answers', 'Testing and improving your agent', 'Copilot Studio capstone')),
        array('id' => 'm16', 'num' => '16', 'title' => 'Advanced Copilot Studio', 'track' => 'studio', 'level' => 'Advanced', 'time' => '70 min', 'outcome' => 'Design better conversations, connect systems, and create improvement loops.', 'capstone' => 'Build a business workflow agent.', 'lessons' => array('Designing agent conversations', 'Knowledge sources and grounded answers', 'Actions, connectors, and external systems', 'Authentication, permissions, and safe access', 'Testing, analytics, and improvement loops', 'Advanced Copilot Studio capstone')),
        array('id' => 'm17', 'num' => '17', 'title' => 'Copilot and Power Automate Workflows', 'track' => 'studio', 'level' => 'Intermediate', 'time' => '60 min', 'outcome' => 'Know when Copilot should assist and when automation should execute.', 'capstone' => 'Build a Copilot-assisted business process.', 'lessons' => array('Automation basics for Copilot users', 'When to use Copilot vs Power Automate', 'Building a simple approval workflow', 'Turning emails, forms, and files into actions', 'Human-in-the-loop automation safety', 'Automation capstone')),
        array('id' => 'm18', 'num' => '18', 'title' => 'Business Playbooks', 'track' => 'business', 'level' => 'Intermediate', 'time' => '60 min', 'outcome' => 'Turn Copilot from a novelty into role-specific operating habits.', 'capstone' => 'Build your personal Copilot workflow system.', 'lessons' => array('Copilot for marketers', 'Copilot for sales teams', 'Copilot for founders and executives', 'Copilot for operations and project management', 'Copilot for HR, finance, and admin work', 'Business playbook capstone')),
        array('id' => 'm19', 'num' => '19', 'title' => 'Admin, Security, Privacy, and Governance', 'track' => 'governance', 'level' => 'Advanced', 'time' => '65 min', 'outcome' => 'Understand access, oversharing, compliance, and safe agent governance.', 'capstone' => 'Create a Copilot readiness checklist.', 'lessons' => array('Copilot security and privacy in plain English', 'Licenses, access, and admin controls', 'Data permissions, SharePoint, OneDrive, and oversharing', 'Microsoft Purview, sensitivity, and compliance basics', 'Safe agent governance', 'Governance capstone')),
        array('id' => 'm20', 'num' => '20', 'title' => 'Adoption, ROI, Capstones, and Expert Certification', 'track' => 'business', 'level' => 'Advanced', 'time' => '70 min', 'outcome' => 'Plan adoption, measure impact, and complete a final practical portfolio project.', 'capstone' => 'Complete the final exam and course checklist.', 'lessons' => array('How to build a personal Copilot operating system', 'How to roll out Copilot to a team', 'How to measure Copilot productivity and ROI', 'How to run a Copilot prompt-a-thon or training session', 'Final capstone project options', 'Final exam and completion checklist')),
    );
}

function kingy_ali_microsoft_copilot_course_schema() {
    if (!kingy_ali_is_microsoft_copilot_course_page()) {
        return;
    }

    $faqs = kingy_ali_microsoft_copilot_course_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Course',
                'name' => 'Microsoft Copilot Zero to Hero',
                'description' => 'A practical Microsoft Copilot course for beginners through advanced business users covering Copilot Chat, Microsoft 365 apps, prompts, agents, Copilot Studio, automation, governance, and adoption.',
                'provider' => array('@type' => 'Organization', 'name' => 'Kingy AI', 'url' => home_url('/')),
                'url' => home_url('/microsoft-copilot-course/'),
                'educationalLevel' => 'Beginner to advanced',
                'teaches' => array('Microsoft Copilot', 'Microsoft 365 Copilot', 'Copilot Chat', 'Copilot Studio', 'Copilot agents', 'AI prompting', 'AI governance'),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array('@type' => 'Answer', 'text' => $faq['answer']),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function kingy_ali_microsoft_copilot_course_faqs() {
    return array(
        array('question' => 'Do I need a Microsoft 365 Copilot license?', 'answer' => 'Not for every lesson. You can learn prompting and many Copilot Chat basics with eligible Copilot access, but work-grounded Microsoft 365 Copilot features, deeper app integrations, meeting recaps, and some agents can require a Microsoft 365 Copilot add-on license and admin enablement.'),
        array('question' => 'What is the difference between Copilot Chat and Microsoft 365 Copilot?', 'answer' => 'Copilot Chat is the chat experience. Depending on your account and license, it may be grounded in web data, uploaded files, limited app data, or broader work data. Microsoft 365 Copilot adds deeper work-grounded capabilities across Microsoft 365 apps and eligible agents.'),
        array('question' => 'Is Copilot Studio the same as Agent Builder?', 'answer' => 'No. Agent Builder is a simpler way to create focused helper agents in Microsoft 365 experiences. Copilot Studio is a more capable low-code platform for designing agents, topics, knowledge, actions, connectors, authentication, and governance.'),
        array('question' => 'Can Copilot see all my company files?', 'answer' => 'Copilot should respect Microsoft 365 permissions, which means it can only use content the signed-in user is allowed to access. The risk is not magic access; it is messy permissions, overshared SharePoint sites, stale files, and unclear sensitivity practices.'),
        array('question' => 'Does Copilot make mistakes?', 'answer' => 'Yes. Treat Copilot outputs as drafts and analysis assistants, not unquestioned truth. Verify numbers, citations, legal or financial claims, customer commitments, and anything that could affect people or money.'),
        array('question' => 'Can beginners use this course without admin access?', 'answer' => 'Yes. Start with orientation, Copilot Chat, prompting, and Office workflows. Admin and governance modules are still useful because they explain why some features may be missing or restricted.'),
        array('question' => 'Why does Copilot in Excel sometimes feel limited?', 'answer' => 'Excel results depend on data cleanliness, table structure, file location, app version, license, and feature rollout. The course teaches data prep, question framing, formula verification, and when to use Analyst or a more manual workflow.'),
        array('question' => 'How should a team roll out Copilot?', 'answer' => 'Start with a small use-case pilot, permission cleanup, a prompt library, role-specific workflows, measurement, and a human review policy. Avoid a broad launch that promises productivity before people know what to do with the tool.'),
        array('question' => 'What should I build as a final project?', 'answer' => 'Pick a real workflow with clear inputs, a repeatable prompt or agent, human approval, and measurable output: meeting follow-up system, Excel reporting workflow, proposal drafting process, research brief, FAQ agent, or team adoption playbook.'),
    );
}

function kingy_ali_microsoft_copilot_prompt_examples() {
    return array(
        array('tag' => 'Copilot Chat', 'title' => 'Work catch-up', 'text' => 'Goal: Help me catch up on [project/team/customer]. Context: Use the relevant chats, emails, meetings, and files I can access from the last [time period]. Expectations: Return decisions, open questions, risks, deadlines, and suggested next actions. Source: Cite or name the source item for each important claim.'),
        array('tag' => 'Word', 'title' => 'Document rewrite', 'text' => 'Goal: Rewrite this document for [audience]. Context: The reader cares about [priorities] and already knows [background]. Expectations: Improve structure, clarity, and tone without adding unsupported claims. Source: Use only the selected text and call out any missing information.'),
        array('tag' => 'Excel', 'title' => 'Analysis plan', 'text' => 'Goal: Analyze this table for useful business insights. Context: The dataset tracks [what it tracks] and the decision is [decision]. Expectations: Identify trends, outliers, possible data quality issues, and three follow-up questions. Source: Reference column names and calculations so I can verify them.'),
        array('tag' => 'PowerPoint', 'title' => 'Executive deck', 'text' => 'Goal: Turn this material into an executive presentation. Context: The audience is [audience] and the desired decision is [decision]. Expectations: Create a logical story arc, slide titles, speaker notes, and evidence needed. Source: Use the attached document and list gaps before drafting.'),
        array('tag' => 'Outlook', 'title' => 'Email thread summary', 'text' => 'Goal: Summarize this email thread and help me respond. Context: I need to protect the relationship and move the work forward. Expectations: Return the issue, stakeholder positions, promised dates, risks, and a concise reply draft. Source: Use only this thread and mark uncertain points.'),
        array('tag' => 'Teams', 'title' => 'Meeting recap', 'text' => 'Goal: Turn this meeting into an action-ready recap. Context: The team needs decisions, owners, and next steps. Expectations: Separate decisions, action items, risks, blockers, and follow-up messages. Source: Use the meeting transcript and call out anything that needs confirmation.'),
        array('tag' => 'Agents', 'title' => 'Agent instructions', 'text' => 'Goal: Draft instructions for an agent that helps with [workflow]. Context: It should use [knowledge sources] and serve [users]. Expectations: Define what the agent should do, refuse, ask for, cite, and escalate. Source: Ground answers in approved sources only and tell users when information is missing.'),
        array('tag' => 'Governance', 'title' => 'Readiness review', 'text' => 'Goal: Review our Copilot readiness for [team]. Context: We use Microsoft 365 for [workflows]. Expectations: Assess licenses, permissions, data hygiene, training, measurement, and risk controls. Source: Base recommendations on our documented setup and flag assumptions.'),
    );
}

function kingy_ali_microsoft_copilot_course_track_label($track) {
    $labels = array(
        'beginner' => 'Beginner',
        'office' => 'Office Productivity',
        'excel' => 'Excel/Data',
        'meetings' => 'Meetings/Email',
        'agents' => 'Agents',
        'studio' => 'Copilot Studio',
        'business' => 'Business Leader',
        'governance' => 'Admin/Governance',
    );

    return isset($labels[$track]) ? $labels[$track] : $track;
}

function kingy_ali_shortcode_microsoft_copilot_course() {
    kingy_ali_enqueue_assets();
    $modules = kingy_ali_microsoft_copilot_course_modules();
    $prompts = kingy_ali_microsoft_copilot_prompt_examples();
    $faqs = kingy_ali_microsoft_copilot_course_faqs();

    ob_start();
    ?>
    <article class="kingy-ali-template kingy-ali-copilot-course" data-kingy-copilot-course>
        <section class="kingy-ali-hero kingy-ali-copilot-hero" id="copilot-top">
            <p class="kingy-ali-kicker"><?php esc_html_e('WordPress-ready course', 'kingy-ai-launch-intelligence'); ?></p>
            <h1><?php esc_html_e('Microsoft Copilot Zero to Hero: From AI Basics to Expert Workflows, Agents, and Automation', 'kingy-ai-launch-intelligence'); ?></h1>
            <p><?php esc_html_e('Learn the Microsoft Copilot ecosystem from first prompt to practical business workflows, agents, Copilot Studio, governance, and capstones. This course is built for people who want useful work habits, not hype.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row">
                <a class="kingy-ali-button" href="#copilot-curriculum"><?php esc_html_e('Start the course', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button kingy-ali-button--secondary" href="<?php echo esc_url(home_url('/microsoft-copilot-course/microsoft-copilot-module-02-ai-and-prompting-foundations/your-first-25-useful-copilot-prompts/')); ?>"><?php esc_html_e('Open the prompt library', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>

        <nav class="kingy-ali-copilot-nav" aria-label="<?php esc_attr_e('Microsoft Copilot course sections', 'kingy-ai-launch-intelligence'); ?>">
            <div class="kingy-ali-copilot-nav__links">
                <a href="#copilot-resources"><?php esc_html_e('Resources', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="#copilot-learn"><?php esc_html_e('Learn', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="#copilot-path"><?php esc_html_e('Path', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="#copilot-curriculum"><?php esc_html_e('Curriculum', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="#copilot-tools"><?php esc_html_e('Tools', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="#copilot-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
            <div class="kingy-ali-copilot-nav__progress" aria-hidden="true"><span data-copilot-scroll-progress></span></div>
        </nav>

        <section class="kingy-ali-content-band kingy-ali-copilot-download" id="copilot-prompt-pack">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Download 100 Microsoft Copilot Prompts for Work', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Keep a copy/paste prompt pack beside the lessons', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use practical starter prompts for Copilot Chat, Word, Excel, PowerPoint, Outlook, Teams, meetings, research, agents, and business workflows.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-cta-row">
                <a class="kingy-ali-button" href="<?php echo esc_url(home_url('/microsoft-copilot-course/microsoft-copilot-module-02-ai-and-prompting-foundations/your-first-25-useful-copilot-prompts/')); ?>"><?php esc_html_e('Open the Prompt Lesson', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button kingy-ali-button--secondary" href="#copilot-curriculum"><?php esc_html_e('Explore the Full Microsoft Copilot Course', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-resources">
            <p class="kingy-ali-kicker"><?php esc_html_e('Use this course with the companion resources', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Companion resources for focused workflows', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('This page is the main learning path. Keep the prompt examples open when you want copy/paste starting points while you move through the modules. For team adoption, start with business rollout notes. For focused workflows, use Excel, meetings and email, or Copilot Studio resources alongside the matching modules.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-codex-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/microsoft-copilot-course/microsoft-copilot-module-02-ai-and-prompting-foundations/your-first-25-useful-copilot-prompts/')); ?>"><strong><?php esc_html_e('Prompt library', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Copy/paste prompts for chat, Office apps, agents, and verification.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/microsoft-copilot-for-business/')); ?>"><strong><?php esc_html_e('Business guide', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Adoption, team workflows, executive use cases, rollout planning, and governance.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/copilot-in-excel-tutorial/')); ?>"><strong><?php esc_html_e('Excel tutorial', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Data prep, formulas, analysis, reporting, dashboards, and verification.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/copilot-teams-outlook-tutorial/')); ?>"><strong><?php esc_html_e('Meetings and email', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Teams meetings, Outlook email, agendas, follow-ups, and action items.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/copilot-studio-for-beginners/')); ?>"><strong><?php esc_html_e('Copilot Studio basics', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Plan, build, test, and govern simple Copilot Studio agents.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-learn">
            <p class="kingy-ali-kicker"><?php esc_html_e('What You Will Learn', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('A practical Copilot operating system for real work', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-tile-grid">
                <div class="kingy-ali-tile"><strong><?php esc_html_e('Copilot Foundations', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Understand Copilot Chat, Microsoft 365 Copilot, work data, web data, licenses, and feature availability.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-tile"><strong><?php esc_html_e('Office Workflows', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use Copilot in Word, Excel, PowerPoint, Outlook, Teams, OneNote, OneDrive, and SharePoint.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-tile"><strong><?php esc_html_e('Agents and Automation', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Plan Agent Builder agents, Copilot Studio agents, and human-in-the-loop automation workflows.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-tile"><strong><?php esc_html_e('Governance and Adoption', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Build safe rollout, readiness, ROI, and capstone plans without overpromising.', 'kingy-ai-launch-intelligence'); ?></span></div>
            </div>
            <div class="kingy-ali-callout">
                <strong><?php esc_html_e('Important availability note:', 'kingy-ai-launch-intelligence'); ?></strong>
                <?php esc_html_e('Copilot availability depends on Microsoft account, license, organization settings, region, app version, tenant configuration, admin controls, and rollout status. Treat this course as workflow training, then verify features in your own environment.', 'kingy-ai-launch-intelligence'); ?>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-path">
            <p class="kingy-ali-kicker"><?php esc_html_e('Suggested Learning Paths', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Find your fastest path through the course', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-copilot-tool kingy-ali-copilot-path-tool">
                <div class="kingy-ali-form-grid">
                    <label><span><?php esc_html_e('Your role', 'kingy-ai-launch-intelligence'); ?></span><select data-copilot-path-field="role"><option value="beginner"><?php esc_html_e('Beginner or student', 'kingy-ai-launch-intelligence'); ?></option><option value="office"><?php esc_html_e('Office power user', 'kingy-ai-launch-intelligence'); ?></option><option value="leader"><?php esc_html_e('Founder or business leader', 'kingy-ai-launch-intelligence'); ?></option><option value="builder"><?php esc_html_e('Agent or automation builder', 'kingy-ai-launch-intelligence'); ?></option><option value="admin"><?php esc_html_e('Admin, IT, or governance owner', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                    <label><span><?php esc_html_e('Primary goal', 'kingy-ai-launch-intelligence'); ?></span><select data-copilot-path-field="goal"><option value="learn"><?php esc_html_e('Learn the basics safely', 'kingy-ai-launch-intelligence'); ?></option><option value="productivity"><?php esc_html_e('Save time in daily work', 'kingy-ai-launch-intelligence'); ?></option><option value="data"><?php esc_html_e('Analyze data and reports', 'kingy-ai-launch-intelligence'); ?></option><option value="meetings"><?php esc_html_e('Improve meetings and email', 'kingy-ai-launch-intelligence'); ?></option><option value="agents"><?php esc_html_e('Build agents or workflows', 'kingy-ai-launch-intelligence'); ?></option><option value="rollout"><?php esc_html_e('Roll out Copilot to a team', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                    <label><span><?php esc_html_e('Current access', 'kingy-ai-launch-intelligence'); ?></span><select data-copilot-path-field="license"><option value="unknown"><?php esc_html_e('Not sure yet', 'kingy-ai-launch-intelligence'); ?></option><option value="chat"><?php esc_html_e('Copilot Chat only', 'kingy-ai-launch-intelligence'); ?></option><option value="m365"><?php esc_html_e('Microsoft 365 Copilot', 'kingy-ai-launch-intelligence'); ?></option><option value="admin"><?php esc_html_e('Admin-enabled tenant', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                </div>
                <div class="kingy-ali-score-panel" data-copilot-path-output aria-live="polite"></div>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-curriculum">
            <p class="kingy-ali-kicker"><?php esc_html_e('Full Curriculum', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Twenty-one modules, filtered by the work you need to do', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-copilot-progress">
                <div><strong data-copilot-progress-label>0%</strong><span><?php esc_html_e(' complete', 'kingy-ai-launch-intelligence'); ?></span></div>
                <progress value="0" max="<?php echo esc_attr(count($modules)); ?>" data-copilot-module-progress></progress>
                <button class="kingy-ali-button kingy-ali-button--secondary" type="button" data-copilot-reset-progress><?php esc_html_e('Reset progress', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
            <div class="kingy-ali-filter-chips" role="list" aria-label="<?php esc_attr_e('Filter curriculum by track', 'kingy-ai-launch-intelligence'); ?>">
                <button class="kingy-ali-filter-chip is-active" type="button" data-copilot-filter="all"><?php esc_html_e('All', 'kingy-ai-launch-intelligence'); ?></button>
                <?php foreach (array('beginner', 'office', 'excel', 'meetings', 'agents', 'studio', 'business', 'governance') as $track) : ?>
                    <button class="kingy-ali-filter-chip" type="button" data-copilot-filter="<?php echo esc_attr($track); ?>"><?php echo esc_html(kingy_ali_microsoft_copilot_course_track_label($track)); ?></button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-copilot-curriculum" data-copilot-curriculum>
                <?php foreach ($modules as $module) : ?>
                    <details class="kingy-ali-copilot-module" data-copilot-module data-track="<?php echo esc_attr($module['track']); ?>" data-module-id="<?php echo esc_attr($module['id']); ?>">
                        <summary>
                            <span class="kingy-ali-copilot-module__num"><?php echo esc_html($module['num']); ?></span>
                            <span><strong><?php echo esc_html($module['title']); ?></strong><small><?php echo esc_html($module['level'] . ' - ' . $module['time'] . ' - ' . kingy_ali_microsoft_copilot_course_track_label($module['track'])); ?></small></span>
                        </summary>
                        <div class="kingy-ali-copilot-module__body">
                            <p><strong><?php esc_html_e('Outcome:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($module['outcome']); ?></p>
                            <ol>
                                <?php foreach ($module['lessons'] as $lesson) : ?>
                                    <li><?php echo esc_html($lesson); ?></li>
                                <?php endforeach; ?>
                            </ol>
                            <p><strong><?php esc_html_e('Capstone:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($module['capstone']); ?></p>
                            <div class="kingy-ali-cta-row">
                                <label class="kingy-ali-copilot-complete"><input type="checkbox" data-copilot-module-check="<?php echo esc_attr($module['id']); ?>"> <?php esc_html_e('Mark module complete', 'kingy-ai-launch-intelligence'); ?></label>
                                <button class="kingy-ali-button kingy-ali-button--secondary" type="button" data-copilot-copy-module><?php esc_html_e('Copy lesson checklist', 'kingy-ai-launch-intelligence'); ?></button>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-tools">
            <p class="kingy-ali-kicker"><?php esc_html_e('Interactive Practice Tools', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Practice before you paste into Copilot', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-content-grid kingy-ali-copilot-tool-grid">
                <div class="kingy-ali-copilot-tool">
                    <h3><?php esc_html_e('Copilot readiness checklist', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Check your environment before you assume a feature is broken.', 'kingy-ai-launch-intelligence'); ?></p>
                    <?php
                    $checks = array('I know whether I am using a personal, work, or school account.', 'I checked whether Microsoft 365 Copilot is assigned to my account.', 'I know which apps have Copilot enabled in my tenant.', 'Important files live in the right OneDrive or SharePoint locations.', 'I reviewed obvious oversharing and stale permissions.', 'I know when to verify, cite, or escalate Copilot output.');
                    foreach ($checks as $index => $check) :
                        ?>
                        <label class="kingy-ali-check-row"><input type="checkbox" data-copilot-readiness-check="<?php echo esc_attr($index); ?>"> <?php echo esc_html($check); ?></label>
                    <?php endforeach; ?>
                    <div class="kingy-ali-score-panel"><strong data-copilot-readiness-score>0/6</strong><span data-copilot-readiness-status><?php esc_html_e('Not started', 'kingy-ai-launch-intelligence'); ?></span></div>
                </div>

                <form class="kingy-ali-copilot-tool" data-copilot-prompt-form>
                    <h3><?php esc_html_e('Prompt builder', 'kingy-ai-launch-intelligence'); ?></h3>
                    <label><span><?php esc_html_e('Goal', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-copilot-prompt-field="goal" placeholder="<?php esc_attr_e('Summarize this customer thread', 'kingy-ai-launch-intelligence'); ?>"></label>
                    <label><span><?php esc_html_e('Context', 'kingy-ai-launch-intelligence'); ?></span><textarea rows="3" data-copilot-prompt-field="context" placeholder="<?php esc_attr_e('I need a concise executive-ready view...', 'kingy-ai-launch-intelligence'); ?>"></textarea></label>
                    <label><span><?php esc_html_e('Expectations', 'kingy-ai-launch-intelligence'); ?></span><textarea rows="3" data-copilot-prompt-field="expectations" placeholder="<?php esc_attr_e('Return decisions, risks, owners, and next actions...', 'kingy-ai-launch-intelligence'); ?>"></textarea></label>
                    <label><span><?php esc_html_e('Source', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-copilot-prompt-field="source" placeholder="<?php esc_attr_e('Use this thread and cite uncertain points', 'kingy-ai-launch-intelligence'); ?>"></label>
                    <textarea rows="7" data-copilot-prompt-output readonly></textarea>
                    <button class="kingy-ali-button" type="submit"><?php esc_html_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?></button>
                </form>

                <form class="kingy-ali-copilot-tool" data-copilot-capstone-form>
                    <h3><?php esc_html_e('Capstone project generator', 'kingy-ai-launch-intelligence'); ?></h3>
                    <label><span><?php esc_html_e('Role', 'kingy-ai-launch-intelligence'); ?></span><select data-copilot-capstone-field="role"><option>Marketing lead</option><option>Sales manager</option><option>Operations owner</option><option>Founder or executive</option><option>HR or finance lead</option><option>IT or governance owner</option></select></label>
                    <label><span><?php esc_html_e('Workflow', 'kingy-ai-launch-intelligence'); ?></span><select data-copilot-capstone-field="workflow"><option>Meeting follow-up system</option><option>Monthly reporting workflow</option><option>Proposal drafting process</option><option>Research brief workflow</option><option>FAQ or support agent</option><option>Team adoption playbook</option></select></label>
                    <label><span><?php esc_html_e('Primary app', 'kingy-ai-launch-intelligence'); ?></span><select data-copilot-capstone-field="app"><option>Copilot Chat</option><option>Word</option><option>Excel</option><option>PowerPoint</option><option>Outlook and Teams</option><option>Copilot Studio</option></select></label>
                    <textarea rows="8" data-copilot-capstone-output readonly></textarea>
                    <button class="kingy-ali-button" type="submit"><?php esc_html_e('Copy project outline', 'kingy-ai-launch-intelligence'); ?></button>
                </form>

                <form class="kingy-ali-copilot-tool" data-copilot-roi-form>
                    <h3><?php esc_html_e('Time-saved ROI estimator', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('A directional estimate for planning. Validate with real before/after workflow data.', 'kingy-ai-launch-intelligence'); ?></p>
                    <label><span><?php esc_html_e('People using Copilot', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="1" value="10" data-copilot-roi-input="people"></label>
                    <label><span><?php esc_html_e('Hours saved per person per week', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" step="0.25" value="1.5" data-copilot-roi-input="hours"></label>
                    <label><span><?php esc_html_e('Average hourly cost', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" value="55" data-copilot-roi-input="rate"></label>
                    <label><span><?php esc_html_e('Monthly license cost per person', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" value="30" data-copilot-roi-input="cost"></label>
                    <div class="kingy-ali-score-panel"><strong data-copilot-roi-output="net">$0</strong><span data-copilot-roi-output="detail"></span></div>
                </form>
            </div>
        </section>

        <section class="kingy-ali-content-band">
            <p class="kingy-ali-kicker"><?php esc_html_e('Copyable Starter Prompts', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Prompt examples mapped to the course', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-grid">
                <?php foreach ($prompts as $index => $prompt) : ?>
                    <div class="kingy-ali-codex-example">
                        <p class="kingy-ali-kicker"><?php echo esc_html($prompt['tag']); ?></p>
                        <h3><?php echo esc_html($prompt['title']); ?></h3>
                        <p id="copilot-prompt-<?php echo esc_attr($index); ?>"><?php echo esc_html($prompt['text']); ?></p>
                        <button class="kingy-ali-button kingy-ali-button--secondary" type="button" data-copilot-copy-text="#copilot-prompt-<?php echo esc_attr($index); ?>"><?php esc_html_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-content-band">
            <p class="kingy-ali-kicker"><?php esc_html_e('Knowledge Checks', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Quick checks before you move on', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-grid">
                <form class="kingy-ali-copilot-quiz" data-copilot-quiz>
                    <h3><?php esc_html_e('License reality check', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('A teammate cannot see work-grounded answers in Copilot Chat. What should you check first?', 'kingy-ai-launch-intelligence'); ?></p>
                    <label><input type="radio" name="q1" value="0"> <?php esc_html_e('Rewrite the prompt with more enthusiasm.', 'kingy-ai-launch-intelligence'); ?></label>
                    <label><input type="radio" name="q1" value="1"> <?php esc_html_e('Account, license, tenant settings, app version, and source permissions.', 'kingy-ai-launch-intelligence'); ?></label>
                    <label><input type="radio" name="q1" value="0"> <?php esc_html_e('Assume Copilot is down globally.', 'kingy-ai-launch-intelligence'); ?></label>
                    <button class="kingy-ali-button" type="submit"><?php esc_html_e('Check answer', 'kingy-ai-launch-intelligence'); ?></button>
                    <p data-copilot-quiz-output aria-live="polite"></p>
                </form>
                <form class="kingy-ali-copilot-quiz" data-copilot-quiz>
                    <h3><?php esc_html_e('Agent safety check', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('What makes a useful first agent project?', 'kingy-ai-launch-intelligence'); ?></p>
                    <label><input type="radio" name="q2" value="0"> <?php esc_html_e('Broad access, vague instructions, and no testing.', 'kingy-ai-launch-intelligence'); ?></label>
                    <label><input type="radio" name="q2" value="1"> <?php esc_html_e('Narrow job, approved knowledge, clear refusals, testing, and human escalation.', 'kingy-ai-launch-intelligence'); ?></label>
                    <label><input type="radio" name="q2" value="0"> <?php esc_html_e('A workflow that changes records without review.', 'kingy-ai-launch-intelligence'); ?></label>
                    <button class="kingy-ali-button" type="submit"><?php esc_html_e('Check answer', 'kingy-ai-launch-intelligence'); ?></button>
                    <p data-copilot-quiz-output aria-live="polite"></p>
                </form>
                <form class="kingy-ali-copilot-quiz" data-copilot-quiz>
                    <h3><?php esc_html_e('Verification check', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('When should you verify Copilot output?', 'kingy-ai-launch-intelligence'); ?></p>
                    <label><input type="radio" name="q3" value="1"> <?php esc_html_e('Whenever numbers, commitments, compliance, people, money, or customer-facing claims are involved.', 'kingy-ai-launch-intelligence'); ?></label>
                    <label><input type="radio" name="q3" value="0"> <?php esc_html_e('Only when Copilot says it is uncertain.', 'kingy-ai-launch-intelligence'); ?></label>
                    <label><input type="radio" name="q3" value="0"> <?php esc_html_e('Never, because Copilot uses Microsoft 365.', 'kingy-ai-launch-intelligence'); ?></label>
                    <button class="kingy-ali-button" type="submit"><?php esc_html_e('Check answer', 'kingy-ai-launch-intelligence'); ?></button>
                    <p data-copilot-quiz-output aria-live="polite"></p>
                </form>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-related">
            <p class="kingy-ali-kicker"><?php esc_html_e('Related Microsoft Copilot resources', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Use these companion pages when you want a focused next step', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-codex-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/microsoft-copilot-course/microsoft-copilot-module-02-ai-and-prompting-foundations/your-first-25-useful-copilot-prompts/')); ?>"><strong><?php esc_html_e('Prompt library', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Large copy/paste collection for chat, Office apps, agents, and verification.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/microsoft-copilot-for-business/')); ?>"><strong><?php esc_html_e('Business guide', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Adoption, rollout planning, executive workflows, and governance.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/copilot-in-excel-tutorial/')); ?>"><strong><?php esc_html_e('Excel tutorial', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Data prep, formulas, dashboards, and spreadsheet verification.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/copilot-teams-outlook-tutorial/')); ?>"><strong><?php esc_html_e('Meetings and email', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Teams, Outlook, agendas, recaps, follow-ups, and action items.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/copilot-studio-for-beginners/')); ?>"><strong><?php esc_html_e('Copilot Studio basics', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Planning, building, testing, and governing simple Studio agents.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section class="kingy-ali-content-band" id="copilot-faq">
            <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Common Microsoft Copilot course questions', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-copilot-faq">
                <?php foreach ($faqs as $faq) : ?>
                    <details class="kingy-ali-copilot-faq__item">
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <p><?php echo esc_html($faq['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-content-band">
            <p class="kingy-ali-kicker"><?php esc_html_e('Continue with Codex Zero to Hero', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('For readers comparing AI assistants', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Codex Zero to Hero covers AI coding agents, GitHub review loops, and deployment workflows for people who want to move from Copilot productivity to AI-assisted software shipping.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row">
                <a class="kingy-ali-button kingy-ali-button--secondary" href="<?php echo esc_url(home_url('/codex-zero-to-hero/')); ?>"><?php esc_html_e('Codex Zero to Hero course', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button kingy-ali-button--secondary" href="<?php echo esc_url(home_url('/codex-zero-to-hero/module-15-codex-for-github-issues-branches-pull-requests-and-reviews/')); ?>"><?php esc_html_e('GitHub Issues, Branches, PRs, and Reviews', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button kingy-ali-button--secondary" href="<?php echo esc_url(home_url('/codex-github-vercel-guide/')); ?>"><?php esc_html_e('Codex, GitHub, and Vercel guide', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>

        <section class="kingy-ali-content-band kingy-ali-copilot-sponsor">
            <p class="kingy-ali-kicker"><?php esc_html_e('For AI founders and marketers', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Want your AI product explained to a large AI-native audience?', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Kingy AI helps AI companies turn complex products into clear, useful YouTube videos that drive awareness, product understanding, demos, clicks, and search visibility.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row">
                <a class="kingy-ali-button" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_coverage')); ?>"><?php esc_html_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button kingy-ali-button--secondary" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
                <a class="kingy-ali-button kingy-ali-button--secondary" href="<?php echo esc_url(home_url('/clients/')); ?>"><?php esc_html_e('See Client Examples', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
    </article>
    <?php

    return ob_get_clean();
}
