<?php
/** Team archive. @package DinizStudio */
get_header();
get_template_part( 'template-parts/archive/cpt', null, array( 'post_type' => 'team' ) );
get_footer();
