<?php
get_header();
while ( have_posts() ) :
    the_post();
    orwo_page_hero( get_the_title(), 'ORWO Family' );
    echo '<article class="article prose" data-reveal>';
    the_content();
    echo '</article>';
endwhile;
get_footer();
