<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template kingy-ali-model-template">
    <?php echo do_shortcode('[kingy_ai_model_directory]'); ?>
</main>
<?php
get_footer();
