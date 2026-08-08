<?php
/**
 * Title: Conteúdo recente
 * Slug: diniz-studio/posts-grid
 * Categories: dv-content, posts
 */
?>
<!-- wp:group {"align":"full","className":"dv-journal","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull dv-journal">
  <!-- wp:group {"align":"wide","className":"dv-heading-row","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
  <div class="wp-block-group alignwide dv-heading-row"><div><p class="dv-kicker">Journal</p><h2 class="wp-block-heading has-xl-font-size">Ideias para marcas<br>em movimento.</h2></div><p><a class="dv-text-link" href="/blog/">Ver todos os artigos <span>↗</span></a></p></div><!-- /wp:group -->
  <!-- wp:query {"queryId":2,"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date"},"align":"wide"} -->
  <div class="wp-block-query alignwide">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
      <!-- wp:group {"className":"dv-post-card","layout":{"type":"constrained"}} -->
      <div class="wp-block-group dv-post-card">
        <!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /-->
        <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-group"><!-- wp:post-terms {"term":"category","fontSize":"xs"} /--><!-- wp:post-date {"fontSize":"xs","textColor":"muted"} /--></div><!-- /wp:group -->
        <!-- wp:post-title {"isLink":true,"fontSize":"l"} /-->
        <!-- wp:post-excerpt {"moreText":"Ler artigo ↗","excerptLength":18} /-->
      </div><!-- /wp:group -->
    <!-- /wp:post-template -->
  </div><!-- /wp:query -->
</div><!-- /wp:group -->
