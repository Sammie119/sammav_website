<?php
/**
 * Title: Contact form and details
 * Slug: sammav/contact-main
 * Categories: sammav
 * Inserter: yes
 */
?>
<!-- wp:group {"align":"full","className":"sv-section sv-contact","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sv-section sv-contact"><!-- wp:columns {"align":"wide","className":"sv-contact__layout","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide sv-contact__layout"><!-- wp:column {"width":"62%"} -->
<div class="wp-block-column" style="flex-basis:62%"><!-- wp:heading -->
<h2 class="wp-block-heading">Send us a message</h2>
<!-- /wp:heading -->

<!-- wp:sammav/contact-form /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"38%"} -->
<div class="wp-block-column" style="flex-basis:38%"><!-- wp:group {"tagName":"aside","className":"sv-card sv-contact-card","layout":{"type":"default"}} -->
<aside class="wp-block-group sv-card sv-contact-card"><!-- wp:heading -->
<h2 class="wp-block-heading">Contact details</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"sv-detail","layout":{"type":"default"}} -->
<div class="wp-block-group sv-detail"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Phone</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sv-contact-line"} -->
<p class="sv-contact-line">[sammav field="phone" link="tel" icon="phone"]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sv-detail","layout":{"type":"default"}} -->
<div class="wp-block-group sv-detail"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Email</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sv-contact-line"} -->
<p class="sv-contact-line">[sammav field="email" link="mailto" icon="mail"]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sv-detail","layout":{"type":"default"}} -->
<div class="wp-block-group sv-detail"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Office</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sv-contact-line"} -->
<p class="sv-contact-line">[sammav field="address" icon="pin"]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sv-detail","layout":{"type":"default"}} -->
<div class="wp-block-group sv-detail"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Business hours</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sv-contact-line"} -->
<p class="sv-contact-line">[sammav field="hours" icon="clock"]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sv-detail","layout":{"type":"default"}} -->
<div class="wp-block-group sv-detail"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">WhatsApp</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sv-contact-line"} -->
<p class="sv-contact-line">[sammav field="whatsapp" link="whatsapp" icon="whatsapp"]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[sammav_social]
<!-- /wp:shortcode --></aside>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
<!-- wp:group {"align":"full","className":"sv-map-section","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull sv-map-section"><!-- wp:shortcode -->
[sammav field="map_query" link="map"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
