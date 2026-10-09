<?php
/** /pages/cookie-policy: the original site's cookie policy, served by the theme at its original URL. */
get_header();
orwo_page_hero( 'Cookie Policy', 'Cookie Policy' );
echo '<article class="article prose" data-reveal>';
orwo_theme_part( 'pages/cookie-policy' );
echo '</article>';
get_footer();
