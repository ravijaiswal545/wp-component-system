<?php
/**
 * Plugin Name: WP Component System (Reference Implementation)
 * Description: Generic reimplementation of a shortcode-bound component pattern used to run one shared component library across a multi-brand WordPress Multisite deployment. See README.md for context; this is a demonstration of the architecture, not the original production code.
 * Version: 1.0.0
 * License: MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/class-component-registry.php';

/**
 * Single shared instance, accessible from theme templates via
 * wp_component_system(). Kept as a simple function rather than a
 * global to avoid polluting global scope and to make the dependency
 * explicit at call sites.
 */
function wp_component_system(): Component_Registry {
	static $registry = null;
	if ( null === $registry ) {
		$registry = new Component_Registry();
	}
	return $registry;
}

add_action(
	'plugins_loaded',
	static function () {
		wp_component_system();
	}
);
