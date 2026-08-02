# wp-component-system

A minimal, generic reimplementation of a shortcode-bound component pattern
I built and ran in production across a multi-brand WordPress Multisite
deployment (8 brand sites, one shared codebase). This repo strips out all
client-specific code, content, and branding; it's a demonstration of the
architecture, not a copy of the original.

## The problem it solves

On a Multisite network with several distinct brands, you can end up with
either:

1. **One theme per brand.** Consistent visual identity per brand, but
   shared content patterns (hero banners, CTA blocks, staff/profile
   grids, listing pages) get reimplemented slightly differently on each
   site, and a fix to one doesn't propagate to the others.
2. **One monolithic theme with branching logic.** Shared code, but the
   branching logic grows unmanageable as brand count increases, and a
   change for one brand risks regressing another.

This pattern is a middle path: **one shared component library, exposed
to editors as shortcodes**, so brand sites differ in content and
configuration but not in underlying logic.

## How it works

- `inc/class-component-registry.php` registers a small set of components
  (hero, CTA block, card grid) as both shortcodes (for editors) and PHP
  functions (for theme templates), so the same rendering logic is
  reachable from either the block editor or template code.
- Each component takes a defined, validated set of attributes. Editors
  get flexibility within those bounds, not arbitrary HTML/CSS control.
  That boundary was the actual hard part of the original system: too
  much freedom produced inconsistent, hard-to-maintain pages; too little
  meant filing a dev ticket for every content change. This is a starting
  point for that boundary, not a finished answer, it took a few rounds
  of watching real editor usage in production to tune.
- `templates/example-page.php` shows a template composing components
  directly (for developer-controlled pages) alongside the shortcode
  syntax editors would use in the block editor (for content-team-owned
  pages).

## What this is not

This is not the original production code. Client-specific components,
brand configuration, and any content from the original deployment have
been removed. It's a reconstruction of the pattern for demonstration.

## Stack

PHP 8+, WordPress hooks/shortcode API. No build step required.

## Status

Reference implementation for discussion purposes. Not a maintained
plugin.
