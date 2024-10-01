<?php

/**
 * OCD_UpcomingEvents Class.
 *
 * Generates a carousel of upcoming events.
 */

if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Class OCD_UpcomingEvents
 *
 * Generates a carousel of upcoming events.
 * Provides a shortcode with configurable attributes and a settings page in Admin.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_UpcomingEvents' ) ) :
class OCD_UpcomingEvents {
	/**
	 * Holds the slug of the component (the base name of the PHP file).
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Holds the array of values for the component's config and settings fields.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Constructor to initialize the component.
	 */
	public function __construct() {
		$this->slug = basename( __FILE__, '.php' ); // Set the slug to the base filename (without .php extension)
		$this->config(); // Load component configuration.

		// Register the shortcode [ocd_upcoming_events_carousel].
		add_shortcode( 'ocd_upcoming_events_carousel', array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode handler for rendering the component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode string.
	 * 
	 * @return string HTML output of the component.
	 */
	public function shortcode( $atts = array(), $content = '', $tag = 'ocd_upcoming_events_carousel' ) {
		$no_events_str = '<p>'. esc_html( __( 'No Upcoming Events', 'ocdutils' ) ) .'</p>';

		// Check if Event Espresso is active
		if ( ! class_exists( 'EE_Registry' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) return '<p>'. __( 'Event Espresso is not active or installed.', 'ocdutils' ) .'</p>';
			return $no_events_str;
		}

		// Set default attributes for the shortcode
		$atts = shortcode_atts( 
			array(
				'class'       => '',     // Additional class for the carousel
				'title_tag'   => 'h3',   // HTML tag for event titles
				'limit'       => 7,      // Number of events to display
				'nav_arrows'  => 'true', // Show navigation arrows
				'nav_bullets' => 'true', // Show navigation bullets
			), 
			array_change_key_case( (array)$atts, CASE_LOWER ), 
			$tag 
		);

		// Sanitize and process attributes
		$atts['class'] = sanitize_text_field( $atts['class'] );
		if ( ! empty( $atts['class'] ) ) $atts['class'] = ' ' . $atts['class'];

		$atts['title_tag'] = sanitize_text_field( $atts['title_tag'] );

		// Convert nav arrows and bullets attributes to boolean
		$show_nav_arrows = filter_var( sanitize_text_field( $atts['nav_arrows'] ), FILTER_VALIDATE_BOOLEAN );
		$show_nav_bullets = filter_var( sanitize_text_field( $atts['nav_bullets'] ), FILTER_VALIDATE_BOOLEAN );

		// Unique ID for each carousel instance
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = $this->slug .'-' . $shortcode_i;

		// Query events from Event Espresso if not cached
		$events = EE_Registry::instance()->load_model( 'Event' )->get_all( array(
			array(
				'Datetime.DTT_EVT_end' => array( '>=', current_time( 'mysql' ) ), // Only future events (include events that have already started)
				'status' => 'publish',
			),
			'limit'    => intval( $atts['limit'] ),
			'order_by' => 'Datetime.DTT_EVT_start',
			'order'    => 'ASC',
			'group_by' => 'EVT_ID',
		) );
		if ( 1 > count( $events ) ) return $no_events_str;

		// Enqueue dependencies
		$glide_dir = OCD_UTILS_URL . 'node_modules/@glidejs/glide/dist/';
		$glide_version = ocd_nodejs_dependency_version( '@glidejs/glide' );
		
		wp_enqueue_style( 'glidejs',       $glide_dir . 'css/glide.core.min.css',  array(), $glide_version, 'all' );
		wp_enqueue_style( 'glidejs-theme', $glide_dir . 'css/glide.theme.min.css', array(), $glide_version, 'all' );
		wp_add_inline_style( 'glidejs-theme', $this->inline_style( $shortcode_id ) );

		wp_enqueue_script( 'glidejs',      $glide_dir . 'glide.min.js',            array(), $glide_version, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_add_inline_script( 'glidejs', $this->inline_script( $shortcode_id ) );

		$slides = '';
		$bullets = '';
		$event_i = -1;
		foreach ( $events as $post_id => $event ) {
			if ( ! $event instanceof EE_Event ) continue;
			$event_i++;

			$event_name = esc_html( $event->name() );
			$date_time  = $event->first_datetime();
			$start_date = $date_time->start_date( 'M. d, Y' );
			$end_date   = $date_time->end_date( 'M. d, Y' );

			$post_thumbnail = get_the_post_thumbnail( $post_id, 'medium', array( 'loading' => 'lazy' ) );
			if ( empty( $post_thumbnail ) ) {
				$post_thumbnail = '<img src="'. OCD_UTILS_URL .'components/'. $this->slug .'/event-placeholder.png" alt="'. $event_name .'" />';
			}

			// Build list item HTML
			$slides .= '<article class="ocd-event-item glide__slide" aria-labelledby="ocd-event-'. $shortcode_i . '-' . $event_i .'-title">';

				$slides .= '<a class="ocd-event-thumbnail" href="'. esc_url( get_permalink( $post_id ) ) .'" title="'. $event_name .'">';
					$slides .= $post_thumbnail;
				$slides .= '</a>';

				$slides .= '<'. $atts['title_tag'] .' id="ocd-event-'. $shortcode_i . '-' . $event_i .'-title" class="ocd-event-title">';
					$slides .= '<a href="'. esc_url( get_permalink( $post_id ) ) .'" title="'. $event_name .'">'. $event_name .'</a>';
				$slides .= '</'. $atts['title_tag'] .'>';

				$slides .= '<p class="ocd-event-dates">';
					$slides .= '<time datetime="'. date( 'Y-m-d\TH:i:s', strtotime( $start_date .', '. $date_time->start_time() ) ) .'">'. $start_date .'</time>';
					$slides .= $start_date === $end_date ? '' : ' - <time datetime="'. date( 'Y-m-d\TH:i:s', strtotime( $end_date .', '. $date_time->end_time() ) ) .'">'. $end_date .'</time>';
				$slides .= '</p>';

				$slides .= '<p class="ocd-event-description">'. esc_html( get_the_excerpt( $post_id ) ) .'</p>';

			$slides .= '</article>';

			// Build bullets HTML for navigation
			$bullets .= '<button class="glide__bullet" data-glide-dir="='. $event_i .'"><span aria-hidden="true">'. $event_i .'</span></button>';
		}

		// Build the final HTML structure for the carousel
		$html  = '';
		$html .= '<div id="'. $shortcode_id .'" class="'. $this->slug . $atts['class'] .'">';

			$html .= '<div class="glide__track" data-glide-el="track">';
				$html .= '<div class="glide__slides">';
					$html .= $slides;
				$html .= '</div>';
			$html .= '</div>';

			// Add navigation arrows if enabled
			if ( $show_nav_arrows ) {
				$html .= '<div class="glide__arrows" data-glide-el="controls">';
					$html .= '<button class="glide__arrow glide__arrow--left" data-glide-dir="<">';
						$html .= '<span aria-hidden="true">prev</span>';
						$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M0 12l10.975 11 2.848-2.828-6.176-6.176H24v-3.992H7.646l6.176-6.176L10.975 1 0 12z"></path></svg>';
					$html .= '</button>';
					$html .= '<button class="glide__arrow glide__arrow--right" data-glide-dir=">">';
						$html .= '<span aria-hidden="true">next</span>';
						$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"></path></svg>';
					$html .= '</button>';
				$html .= '</div>';
			}

			// Add navigation bullets if enabled
			if ( $show_nav_bullets ) {
				$html .= '<div class="glide__bullets" data-glide-el="controls[nav]">';
					$html .= $bullets;
				$html .= '</div>';
			}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Generates the inline JavaScript for the component.
	 *
	 * @param string $id The unique ID of the component instance.
	 * @return string The inline JavaScript code.
	 */
	private function inline_script( $id ) {
		ob_start();
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function(){
				new Glide('#<?php echo esc_js( $id ); ?>', {
					type: 'carousel',
					perView: 5,
					focusAt: 'center',
					gap: 15,
					autoplay: 4000,
					keyboard: false,
					peek: 70,
					breakpoints: {
						1200: {
							perView: 4,
							peek: 0,
						},
						980: {
							perView: 3,
							peek: 50,
						},
						680: {
							perView: 2,
							peek: 0,
						},
					},
				}).mount();
			});
		</script>
		<?php
		// Keep script tags above just to make the code look nice in the editor, remove script tags before output.
		return str_replace( array( '<script>', '</script>' ), '', ob_get_clean() );
	}

	/**
	 * Generates the inline CSS for the component.
	 *
	 * @param string $id The unique ID of the component instance.
	 * @return string The inline CSS code.
	 */
	private function inline_style( $id ) {
		$class = '.'.$this->slug;
		ob_start();
		?>
		<style>
			<?php echo $class; ?> .glide__track {
				border-radius: 6px;
			}

			<?php echo $class; ?> .glide__bullets {
				bottom: -1em;
			}

			<?php echo $class; ?> .glide__bullet {
				background-color: #1d2844dd;
				box-shadow: 0 .25em .5em 0 #0004;
			}

			<?php echo $class; ?> .glide__bullet:hover,
			<?php echo $class; ?> .glide__bullet--active {
				background-color: #000;
			}

			<?php echo $class; ?> .glide__arrow {
				top: 24%;
				background-color: #fffd;
				border-color: #fffb;
				box-shadow: .15em .25em .5em 0 #0004;
			}

			<?php echo $class; ?> .glide__arrow:hover {
				background-color: #fffe;
			}

			<?php echo $class; ?> .glide__arrow svg {
				fill: #1d2844;
			}

			<?php echo $class; ?> .glide__arrow span[aria-hidden="true"],
			<?php echo $class; ?> .glide__bullet span[aria-hidden="true"] {
				display: none;
			}

			<?php echo $class; ?> .ocd-event-thumbnail {
				display: block;
				position: relative;
				width: 100%;
				padding-top: 56.25%;
				overflow: hidden;
				margin-bottom: 12px;
				border-radius: 6px;
				background-color: #ebebeb;
				box-shadow: .15em .25em .5em 0 #0004;
			}

			<?php echo $class; ?> .ocd-event-thumbnail > img {
				position: absolute;
				width: 100%;
				height: 100%;
				top: 0;
				left: 0;
				bottom: 0;
				right: 0;
				object-fit: cover;
				object-position: center;
			}

			<?php echo $class; ?> .ocd-event-title {
				font-size: 1em;
				text-rendering: optimizeLegibility;
				padding-bottom: 0;
			}

			<?php echo $class; ?> .ocd-event-dates {
				font-size: .5em;
				color: #8a1912;
				padding: 4px 0;
				line-height: 1.3em;
			}

			<?php echo $class; ?> .ocd-event-dates time {
				white-space: nowrap;
			}

			<?php echo $class; ?> .ocd-event-description {
				font-size: .6em;
				line-height: 1.3em;
			}
		</style>
		<?php
		// Keep style tags above just to make the code look nice in the editor, remove style tags before output.
		return str_replace( array( '<style>', '</style>' ), '', ob_get_clean() );
	}




















	/**
	 * Configures the settings for this component.
	 */
	private function config() {
		$this->config = $this->define_config_r();

		// Register this component's settings.
		ocd_register_settings( $this->config );
	}

	/**
	 * Generates the usage instructions for the settings page.
	 *
	 * @return string HTML for usage instructions.
	 */
	private function usage_instructions() {
		ob_start();
		?>
		<div>
			<h4><?php _e( 'Attributes', 'ocdutils' ); ?></h4>
			<ul>
				<li><strong>class</strong>: 
					<?php _e( 'Adds an additional class to the wrapper div for your custom styles.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code><?php echo $this->slug; ?></code>
				</li>
				<li><strong>title_tag</strong>: 
					<?php _e( 'Choose the HTML element to be used for the event titles. h1, h2, h3, h4, h5, h6, p, span, etc.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>h3</code>
				</li>
				<li><strong>limit</strong>: 
					<?php _e( 'Number of events to pull from the database.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>7</code>
				</li>
				<li><strong>nav_arrows</strong>: 
					<?php _e( 'Include the "prev" and "next" navigation arrows in the carousel.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>true</code>
				</li>
				<li><strong>nav_bullets</strong>: 
					<?php _e( 'Include the navigation dots at the bottom of the carousel.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>true</code>
				</li>
			</ul>
			<h4><?php _e( 'Examples', 'ocdutils' ); ?></h4>
			<p><code>[ocd_upcoming_events_carousel]</code> <?php _e( 'All default settings.', 'ocdutils' ) ?></p>
			<p><code>[ocd_upcoming_events_carousel class="my-custom-class" title_tag="h2" limit="7" nav_arrows="false" nav_bullets="true"]</code></p>
			<p><code>[ocd_upcoming_events_carousel title_tag="h3" limit="21" nav_bullets="false"]</code></p>
			<p><?php _e( 'Use all attributes, or none, or mix-and-match. Any attributes omitted from the shortcode will use the default value.', 'ocdutils' ); ?></p>
			<br /><br />
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the config array.
	 *
	 * @return array The component's config values and settings fields.
	 */
	private function define_config_r() {
		return array(
			'slug' => $this->slug,
			'label' => __( 'Events Carousel', 'ocdutils' ), // Tab name in the settings page.
			'sections' => array(
				array(
					'id' => 'usage',
					'label' => __( 'Shortcocde Instructions', 'ocdutils' ),
					'description' => $this->usage_instructions(),
				),
				// Additional sections can be defined here.
			),
		);
	}
}
endif;

// Instantiate the component class.
$OCD_UpcomingEvents = new OCD_UpcomingEvents();

?>
