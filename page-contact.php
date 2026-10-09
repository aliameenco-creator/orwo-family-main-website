<?php
/**
 * Contact page: the original ORWO Family email form plus contact details. Used for a page with slug
 * "contact", and also served at /contact when no such page has been created.
 */
get_header();
$orwo_contact_page = get_page_by_path( 'contact' );
$orwo_contact_page = ( $orwo_contact_page && 'publish' === $orwo_contact_page->post_status ) ? $orwo_contact_page : null;
orwo_page_hero( $orwo_contact_page ? get_the_title( $orwo_contact_page ) : 'Contact Us', 'Contact' );
$orwo_intro = $orwo_contact_page ? trim( apply_filters( 'the_content', $orwo_contact_page->post_content ) ) : '';
?>
<section class="section contact-section"><div class="wrap contact-grid">
    <div class="contact-info" data-reveal>
        <span class="eyebrow">CONTACT US</span>
        <h2>Come write the next chapter with us.</h2>
        <?php if ( $orwo_intro ) : ?><div class="prose"><?php echo $orwo_intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered post content. ?></div><?php endif; ?>
        <dl class="contact-list">
            <div><dt>Email</dt><dd><a href="mailto:hello@orwo.family">hello@orwo.family</a></dd></div>
            <div><dt>Online shop</dt><dd><a href="https://www.orwo.shop" target="_blank" rel="noopener">www.orwo.shop ↗</a></dd></div>
        </dl>
        <?php echo orwo_social_links( 'socials big' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme markup. ?>
    </div>
    <div class="contact-form-card" data-reveal><?php orwo_contact_form(); ?></div>
</div></section>
<?php
get_footer();
