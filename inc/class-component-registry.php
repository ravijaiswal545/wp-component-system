<?php
/**
 * Component_Registry
 *
 * Registers a small set of reusable content components as both:
 *   1. Shortcodes, so content editors can compose pages in the block editor
 *      without developer involvement, and
 *   2. Plain PHP methods, so theme templates can render the exact same
 *      components with the exact same markup and logic.
 *
 * The intent is a single source of truth per component. A fix or visual
 * change to the "card grid" component applies everywhere it's used,
 * whether that use is an editor-placed shortcode on brand A or a
 * developer-written template call on brand B.
 *
 * Every component validates and sanitizes its own attributes rather than
 * trusting shortcode input directly. Attributes editors can set are
 * deliberately constrained: this is a bounded set of options, not
 * arbitrary markup control. That boundary is the actual design decision
 * in this pattern, not the shortcode mechanism itself.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

class Component_Registry {

	/**
	 * @var array<string, array{label: string, defaults: array}>
	 */
	private array $components = array();

	public function __construct() {
		$this->register_defaults();
		$this->bind_shortcodes();
	}

	/**
	 * Define the built-in component set. Each entry maps a component
	 * slug to a label (for editor UI / documentation) and a defaults
	 * array describing every attribute the component accepts.
	 */
	private function register_defaults(): void {
		$this->components = array(
			'hero'      => array(
				'label'    => 'Hero Banner',
				'defaults' => array(
					'title'    => '',
					'subtitle' => '',
					'image_id' => 0,
					'cta_text' => '',
					'cta_url'  => '',
					'align'    => 'left', // left|center
				),
			),
			'cta_block' => array(
				'label'    => 'Call to Action Block',
				'defaults' => array(
					'heading' => '',
					'body'    => '',
					'button_text' => 'Learn more',
					'button_url'  => '#',
					'style'   => 'primary', // primary|secondary
				),
			),
			'card_grid' => array(
				'label'    => 'Card Grid',
				'defaults' => array(
					'post_type' => 'post',
					'count'     => 3,
					'columns'   => 3, // 2|3|4
					'category'  => '',
				),
			),
		);
	}

	/**
	 * Bind each registered component to a WordPress shortcode of the
	 * same slug, e.g. [hero title="..."].
	 */
	private function bind_shortcodes(): void {
		foreach ( array_keys( $this->components ) as $slug ) {
			add_shortcode( $slug, array( $this, 'render_shortcode' ) );
		}
	}

	/**
	 * Shortcode callback dispatcher. WordPress doesn't tell a shared
	 * callback which shortcode tag invoked it, so we recover the tag
	 * from the third argument and route to the matching render method.
	 */
	public function render_shortcode( $atts, $content, $tag ): string {
		if ( ! isset( $this->components[ $tag ] ) ) {
			return '';
		}
		$atts = shortcode_atts( $this->components[ $tag ]['defaults'], $atts, $tag );
		return $this->render( $tag, $atts );
	}

	/**
	 * Render a component by slug. This is the method theme templates
	 * call directly, e.g. `echo $registry->render( 'hero', [...] );`,
	 * so template code and editor-placed shortcodes always go through
	 * the same rendering path.
	 */
	public function render( string $slug, array $atts = array() ): string {
		if ( ! isset( $this->components[ $slug ] ) ) {
			return '';
		}
		$atts   = wp_parse_args( $atts, $this->components[ $slug ]['defaults'] );
		$method = 'render_' . $slug;
		if ( ! method_exists( $this, $method ) ) {
			return '';
		}
		return $this->$method( $atts );
	}

	private function render_hero( array $atts ): string {
		$title    = sanitize_text_field( $atts['title'] );
		$subtitle = sanitize_text_field( $atts['subtitle'] );
		$align    = in_array( $atts['align'], array( 'left', 'center' ), true ) ? $atts['align'] : 'left';
		$image    = $atts['image_id'] ? wp_get_attachment_image( (int) $atts['image_id'], 'large' ) : '';

		ob_start();
		?>
		<section class="component-hero component-hero--<?php echo esc_attr( $align ); ?>">
			<?php if ( $image ) : ?>
				<div class="component-hero__media"><?php echo $image; // phpcs:ignore -- already escaped by core ?></div>
			<?php endif; ?>
			<div class="component-hero__content">
				<?php if ( $title ) : ?><h1><?php echo esc_html( $title ); ?></h1><?php endif; ?>
				<?php if ( $subtitle ) : ?><p><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
				<?php if ( $atts['cta_text'] && $atts['cta_url'] ) : ?>
					<a class="component-hero__cta" href="<?php echo esc_url( $atts['cta_url'] ); ?>">
						<?php echo esc_html( $atts['cta_text'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private function render_cta_block( array $atts ): string {
		$style = in_array( $atts['style'], array( 'primary', 'secondary' ), true ) ? $atts['style'] : 'primary';

		ob_start();
		?>
		<div class="component-cta component-cta--<?php echo esc_attr( $style ); ?>">
			<?php if ( $atts['heading'] ) : ?><h2><?php echo esc_html( $atts['heading'] ); ?></h2><?php endif; ?>
			<?php if ( $atts['body'] ) : ?><p><?php echo esc_html( $atts['body'] ); ?></p><?php endif; ?>
			<a href="<?php echo esc_url( $atts['button_url'] ); ?>" class="component-cta__button">
				<?php echo esc_html( $atts['button_text'] ); ?>
			</a>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_card_grid( array $atts ): string {
		$columns = in_array( (int) $atts['columns'], array( 2, 3, 4 ), true ) ? (int) $atts['columns'] : 3;

		$query_args = array(
			'post_type'      => sanitize_key( $atts['post_type'] ),
			'posts_per_page' => max( 1, min( 12, (int) $atts['count'] ) ),
			'no_found_rows'  => true,
		);
		if ( ! empty( $atts['category'] ) ) {
			$query_args['category_name'] = sanitize_title( $atts['category'] );
		}

		$query = new WP_Query( $query_args );
		ob_start();
		?>
		<div class="component-card-grid component-card-grid--cols-<?php echo esc_attr( (string) $columns ); ?>">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<article class="component-card">
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="component-card__media">
							<?php the_post_thumbnail( 'medium' ); ?>
						</a>
					<?php endif; ?>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
