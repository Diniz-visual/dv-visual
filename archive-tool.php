<?php
/** Tool archive. @package DinizStudio */
get_header();
get_template_part( 'template-parts/archive/cpt', null, array( 'post_type' => 'tool' ) );
get_footer();
