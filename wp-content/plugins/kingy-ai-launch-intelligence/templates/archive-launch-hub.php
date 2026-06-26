<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template">
    <?php echo do_shortcode('[kingy_launch_hub]'); ?>
</main>
<?php
get_footer();
