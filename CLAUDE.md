# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A minimal, generic reference implementation of a shortcode-bound component
pattern for WordPress Multisite: one shared component library exposed to
editors as shortcodes and to theme templates as PHP method calls, so
multiple brand sites can differ in content/configuration without
reimplementing (or branching) the same rendering logic per site. It is
explicitly a demonstration/reconstruction, not the original production
codebase, and is not a maintained plugin — see README.md for the full
rationale (one-theme-per-brand vs. monolithic-theme-with-branching, and
why this pattern is a middle path).

Stack: PHP 8+, WordPress hooks/shortcode API. No build step, no
package manager, no test suite, no linter config — the entire codebase is
three files (`wp-component-system.php`, `inc/class-component-registry.php`,
`templates/example-page.php`). There are no commands to build, lint, or
test; this plugin only runs meaningfully inside a WordPress install (it
`exit`s immediately if `ABSPATH` is undefined).

## Architecture

**Single dual-path rendering rule: everything goes through
`Component_Registry::render( $slug, $atts )`.** This is the one thing to
preserve when touching this code:

- `wp-component-system.php` is the plugin bootstrap. It requires the
  registry class and exposes a single shared instance via the
  `wp_component_system()` function (lazily instantiated, memoized in a
  static local var — deliberately not a global, to keep the dependency
  explicit at call sites). The instance is created on `plugins_loaded`.
- `inc/class-component-registry.php` (`Component_Registry`) is the entire
  system:
  - `register_defaults()` declares each component as a slug → `{label,
    defaults}` entry. `defaults` is the full, authoritative list of
    attributes that component accepts — this is the boundary editors
    operate within (deliberately not arbitrary markup/HTML control).
  - `bind_shortcodes()` registers a WordPress shortcode per component slug,
    all pointing at the same `render_shortcode()` callback.
  - `render_shortcode( $atts, $content, $tag )` is the shortcode entry
    point. WordPress shortcode callbacks aren't told which tag invoked
    them when shared, so `$tag` is used to recover it, `shortcode_atts()`
    merges/validates against that component's defaults, then it delegates
    to `render()`.
  - `render( string $slug, array $atts = [] )` is the entry point theme
    templates call directly (`$registry->render( 'hero', [...] )`). It
    merges `$atts` against defaults via `wp_parse_args()` and dispatches to
    `render_{slug}()`.
  - Both entry paths converge on the same `render_{slug}()` private method
    — this is what guarantees a shortcode placed by an editor and a
    template call written by a developer produce identical markup, and
    that a fix to one component applies everywhere it's used.
  - Each `render_{slug}()` method re-validates/sanitizes every attribute
    itself (`sanitize_text_field`, `esc_html`, `esc_url`, `esc_attr`,
    whitelisting against a fixed set of allowed values e.g.
    `align: left|center`, clamping numeric ranges e.g. card `count`) rather
    than trusting the merged atts array — attributes are never assumed
    safe just because they came through `shortcode_atts()`/`wp_parse_args()`.
- `templates/example-page.php` demonstrates the developer-owned template
  path: it calls `wp_component_system()->render(...)` directly for each
  component. The file's docblock also documents the equivalent
  editor-facing shortcode syntax for the same components — keep both in
  sync if component attributes change.

## Adding or changing a component

To add a new component (`my_component`), you need all three of:
1. An entry in `register_defaults()` with its label and full defaults array
   (this array *is* the attribute contract — nothing outside it should be
   trusted or rendered).
2. A corresponding `render_my_component( array $atts ): string` private
   method that sanitizes/whitelists every attribute before output and
   returns markup (existing methods use `ob_start()`/`ob_get_clean()`
   with inline PHP templating — follow that style).
3. No separate shortcode registration step needed — `bind_shortcodes()`
   iterates `$this->components` automatically, so any slug added to
   `register_defaults()` is registered as a shortcode for free.

When changing an existing component's accepted attributes, update the
`defaults` array, the corresponding `render_*` method, and the example
usage/docblock in `templates/example-page.php` together.
