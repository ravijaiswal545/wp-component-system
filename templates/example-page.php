<?php
/**
 * Example: a developer-owned template composing components directly.
 *
 * This is the "template code" usage path: a developer building a
 * landing page calls the registry directly and gets the exact same
 * markup and validation an editor would get by placing a shortcode
 * in the block editor.
 *
 * Content-team-owned pages instead use the shortcode form in the
 * block editor, e.g.:
 *
 *   [hero title="Welcome" subtitle="Some subtitle" cta_text="Learn more" cta_url="/about"]
 *   [card_grid post_type="post" count="6" columns="3" category="news"]
 *   [cta_block heading="Ready to start?" button_text="Contact us" button_url="/contact"]
 *
 * Both paths render through the same Component_Registry::render()
 * method, so a fix to one component's markup or logic applies
 * everywhere it's used, regardless of which path placed it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$registry = wp_component_system();

echo $registry->render(
	'hero',
	array(
		'title'    => get_the_title(),
		'subtitle' => get_the_excerpt(),
		'cta_text' => 'Get in touch',
		'cta_url'  => '/contact',
		'align'    => 'center',
	)
);

echo $registry->render(
	'card_grid',
	array(
		'post_type' => 'post',
		'count'     => 6,
		'columns'   => 3,
	)
);

echo $registry->render(
	'cta_block',
	array(
		'heading'     => 'Ready to start?',
		'body'        => 'Reach out and we will follow up within one business day.',
		'button_text' => 'Contact us',
		'button_url'  => '/contact',
		'style'       => 'primary',
	)
);

get_footer();
