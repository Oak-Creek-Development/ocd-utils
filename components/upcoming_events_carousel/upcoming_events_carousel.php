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
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_UpcomingEvents' ) ) :
class OCD_UpcomingEvents {
	public function __construct() {
		$this->init();
	}

	public function init() {
		add_filter( 'ocdutils_settings_config', array( $this, 'add_settings_page' ) );
		add_shortcode( 'ocd_upcoming_events', array( $this, 'ocd_upcoming_events_shortcode' ) );
	}

	public function add_settings_page( $settings_config ) {
		$settings_config['components'][] = array(
			'slug' => 'upcoming_events_carousel',
			'label' => 'Upcoming Events Carousel',
			'sections' => array(
				array(
					'id' => 'divi_projects_portfolio_section',
					'label' => 'Portfolio Settings',
					'fields' => array(
					array(
						'id' => 'portfolio_items_per_page',
						'label' => 'Items per Page',
						'type' => 'number',
						'description' => 'Set the number of portfolio items per page.',
					),
					),
				),
			),
		);

		return $settings_config;
	}

	public function ocd_upcoming_events_shortcode( $atts ) {
		$no_events_str = '<p>'. __( 'No Upcoming Events', 'ocdutils' ) .'</p>';
	
		// Check if Event Espresso is active
		if ( ! class_exists( 'EE_Registry' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) return '<p>'. __( 'Event Espresso is not active or installed.', 'ocdutils' ) .'</p>';
			return $no_events_str;
		}
	
		// Set default attributes for the shortcode
		$atts = shortcode_atts( array(
			'class'       => '',            // Additional class for the carousel
			'title_tag'   => 'h3',          // HTML tag for event titles
			'limit'       => 7,             // Number of events to display
			'nav_arrows'  => 'true',        // Show navigation arrows
			'nav_bullets' => 'true',        // Show navigation bullets
		), $atts, 'ocd_upcoming_events' );
	
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
		$shortcode_id = 'lmg_events_' . $shortcode_i;
	
		// Query events from Event Espresso if not cached
		$events = EE_Registry::instance()->load_model( 'Event' )->get_all( array(
			array(
				'Datetime.DTT_EVT_end' => array( '>=', current_time( 'mysql' ) ), // Only future events (include events that have already started)
				'status' => 'publish',
			),
			'limit'    => esc_attr( $atts['limit'] ),
			'order_by' => 'Datetime.DTT_EVT_start',
			'order'    => 'ASC',
			'group_by' => 'EVT_ID',
		) );
		if ( 1 > count( $events ) ) return $no_events_str;
	
		// Enqueue dependencies
		$glide_dir = trailingslashit( get_stylesheet_directory_uri() ) . 'node_modules/@glidejs/glide/dist/';
		$glide_version = ocd_nodejs_dependency_version( '@glidejs/glide' );
		wp_enqueue_style( 'glidejs',       $glide_dir . 'css/glide.core.min.css',  array(), $glide_version, 'all' );
		wp_enqueue_style( 'glidejs-theme', $glide_dir . 'css/glide.theme.min.css', array(), $glide_version, 'all' );
		wp_enqueue_script( 'glidejs',      $glide_dir . 'glide.min.js',            array(), $glide_version, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_add_inline_script( 'glidejs', $this->inline_script( $shortcode_id ) );
	
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
				$post_thumbnail = '<img src="'. trailingslashit( get_stylesheet_directory_uri() ) .'lmg-event-placeholder.png" alt="'. $event_name .'" />';
			}
	
			// Build list item HTML
			$list_items .= '<li class="lmg-event-item glide__slide">';
				$list_items .= '<article aria-labelledby="lmg-event-'. $shortcode_i . '-' . $event_i .'-title">';
	
					$list_items .= '<a class="lmg-event-thumbnail" href="'. esc_url( get_permalink( $post_id ) ) .'" title="'. $event_name .'">';
						$list_items .= $post_thumbnail;
					$list_items .= '</a>';
	
					$list_items .= '<'. $atts['title_tag'] .' id="lmg-event-'. $shortcode_i . '-' . $event_i .'-title" class="lmg-event-title">';
						$list_items .= '<a href="'. esc_url( get_permalink( $post_id ) ) .'" title="'. $event_name .'">'. $event_name .'</a>';
					$list_items .= '</'. $atts['title_tag'] .'>';
	
					$list_items .= '<p class="lmg-event-dates">';
						$list_items .= '<time datetime="'. date( 'Y-m-d\TH:i:s', strtotime( $start_date .', '. $date_time->start_time() ) ) .'">'. $start_date .'</time>';
						$list_items .= $start_date === $end_date ? '' : ' - <time datetime="'. date( 'Y-m-d\TH:i:s', strtotime( $end_date .', '. $date_time->end_time() ) ) .'">'. $end_date .'</time>';
					$list_items .= '</p>';
	
					$list_items .= '<p class="lmg-event-description">'. esc_html( get_the_excerpt( $post_id ) ) .'</p>';
	
				$list_items .= '</article>';
			$list_items .= '</li>';
	
			// Build bullets HTML for navigation
			$bullets .= '<button class="glide__bullet" data-glide-dir="='. $event_i .'"><span aria-hidden="true">'. $event_i .'</span></button>';
		}
	
		// Build the final HTML structure for the carousel
		$html .= '<div id="'. $shortcode_id .'" class="lmg_events'. $atts['class'] .'">';
	
			$html .= '<div class="glide__track" data-glide-el="track">';
				$html .= '<ul class="glide__slides">';
					$html .= $list_items;
				$html .= '</ul>';
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

	private function inline_script( $id ) {
		ob_start();
		?>
		<script type="text/javascript">
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
		return ob_get_clean();
	}
}
endif;
$OCD_UpcomingEvents = new OCD_UpcomingEvents();
?>
