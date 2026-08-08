<?php
/** Client archive. @package DinizStudio */
get_header();
get_template_part( 'template-parts/archive/cpt', null, array( 'post_type' => 'client' ) );
get_footer();
