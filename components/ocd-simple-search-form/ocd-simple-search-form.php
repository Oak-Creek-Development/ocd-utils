<?php

/**
 * OCD_SimpleSearchForm Class.
 *
 * Provides a shortcode with configurable attributes and a settings page in Admin
 */

if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Class OCD_SimpleSearchForm
 *
 * Provides a shortcode with configurable attributes and a settings page in Admin
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_SimpleSearchForm' ) ) :
class OCD_SimpleSearchForm {
	/**
	 * Holds the slug of the component (the base name of the PHP file).
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * This component's version.
	 *
	 * @var string
	 */
	private $version = '1.0.1';

	/**
	 * Holds the array of values for the component's config and settings fields.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Holds the current options/settings for this component, retrieved from the database.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Constructor to initialize the component.
	 */
	public function __construct() {
		$this->slug = basename( __FILE__, '.php' ); // Set the slug to the base filename (without .php extension)
		$this->config(); // Load component configuration.

		// Register the shortcode [ocd_simple_search_form].
		add_shortcode( 'ocd_simple_search_form', array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode handler for rendering the component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * 
	 * @return string HTML output of the component.
	 */
	public function shortcode( $atts = array(), $content = '', $tag = 'ocd_simple_search_form' ) {
		$options = ocd_get_options( $this );

		// Set default shortcode attributes, overriding with any provided.
		$atts = shortcode_atts( 
			array(
				'shape'            => $options['shape'],
				'color_background' => $options['color_background'],
				'color_text'       => $options['color_text'],
				'color_border'     => $options['color_border'],
				'color_button'     => $options['color_button'],
				'color_icon'       => $options['color_icon'],
				'font_size'        => $options['font_size'],
				'max_width'        => $options['max_width'],
				'min_width'        => $options['min_width'],
				'class'            => '',
			), 
			array_change_key_case( (array)$atts, CASE_LOWER ), 
			$tag 
		);

		// Sanitize and process attributes
		$atts['color_background'] = $this->format_color_input( $atts['color_background'] );
		$atts['color_text']       = $this->format_color_input( $atts['color_text'] );
		$atts['color_border']     = $this->format_color_input( $atts['color_border'] );
		$atts['color_button']     = $this->format_color_input( $atts['color_button'] );
		$atts['color_icon']       = $this->format_color_input( $atts['color_icon'] );

		$atts['font_size'] = $this->format_number_input( $atts['font_size'] );
		$atts['max_width'] = $this->format_number_input( $atts['max_width'] );
		$atts['min_width'] = $this->format_number_input( $atts['min_width'] );

		$atts['class'] = sanitize_text_field( strtolower( $atts['class'] ) );
		// If 'class' is specified in the shortcode, prepend a space to separate it from the built-in class.
		if ( ! empty( $atts['class'] ) ) $atts['class'] = ' ' . $atts['class'];
		// If 'class' is defined in $options, append it to the existing class attribute.
		if ( ! empty( $options['class'] ) ) $atts['class'] = $atts['class'] . ' ' . $options['class'];
	
		// Create a unique ID for each instance of the shortcode to avoid conflicts.
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = $this->slug .'-' . $shortcode_i;
	
		wp_enqueue_style( 
			$this->slug, 
			OCD_UTILS_URL .'components/'. $this->slug .'/'. $this->slug .'.min.css', 
			array(), 
			$this->version, 
			'all' 
		);

		ob_start();
		?>
		<style>
			<?php echo '.' . $this->slug; ?> {
				--ocdssf-border-radius: <?php echo $this->calc_border_radius( $atts['shape'], $atts['font_size'] ); ?>;
				--ocdssf-color-background: <?php echo $atts['color_background']; ?>;
				--ocdssf-color-text: <?php echo $atts['color_text']; ?>;
				--ocdssf-color-border: <?php echo $atts['color_border']; ?>;
				--ocdssf-color-button: <?php echo $atts['color_button']; ?>;
				--ocdssf-color-icon: <?php echo $atts['color_icon']; ?>;
				--ocdssf-font-size: <?php echo ! empty( $atts['font_size'] ) ? $atts['font_size'] . 'px' : '1em'; ?>;
				--ocdssf-max-width: <?php echo ! empty( $atts['max_width'] ) ? $atts['max_width'] . 'px' : '100%'; ?>;
				--ocdssf-min-width: <?php echo ! empty( $atts['min_width'] ) ? $atts['min_width'] . 'px' : '50px'; ?>;
			}
		</style>
		<?php
		$style_vars = ob_get_clean();

		$home_url = esc_url( home_url( '/' ) );
		$search_query = get_search_query();

		// Build the HTML output for the component.
		$html = '';
		$html .= $style_vars;
		$html .= '<form role="search" method="get" id="'. $shortcode_id .'" class="search-form '. $this->slug . $atts['class'] .'" action="' . $home_url . '">';
			$html .= '<label for="search-field" class="sr-only">'. __( 'Search for:', 'ocdutils' ) .'</label>';
			$html .= '<input name="s" type="search" id="search-field" class="search-field" placeholder="'. __( 'Search …', 'ocdutils' ) .'" value="' . esc_attr( $search_query ) . '" />';
			$html .= '<button type="submit" class="search-submit" aria-label="'. __( 'Search', 'ocdutils' ) .'">';
				$html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="presentation"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->';
					$html .= '<path d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6 .1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z"/>';
				$html .= '</svg>';
			$html .= '</button>';
		$html .= '</form>';

		return $html;
	}

	/**
	 * Formats a number that was inputted by the user.
	 *
	 * @param string $num The number to format.
	 * @return string The formatted number.
	 */
	private function format_number_input( $num ) {
		$num = preg_replace( '/\D/', '', $num );

		return (string) $num;
	}

	/**
	 * Formats a color that was inputted by the user.
	 *
	 * @param string $num The color to format.
	 * @return string The formatted color.
	 */
	private function format_color_input( $color ) {
		$color = sanitize_text_field( trim( $color ) );
		$color = '#' . ltrim( $color, '#' );

		return (string) $color;
	}

	/**
	 * Calculates the amount of rounding needed based on the font size and the user selection
	 *
	 * @param string $shape The chosen shape.
	 * @param int $font_size The size.
	 * @return string The formatted CSS border-radius value.
	 */
	private function calc_border_radius( $shape, $font_size ) {
		//Maybe calculate this based on font size, but it's prob not necesary
		// $font_size = preg_replace( '/\D/', '', $font_size );
		// if ( empty( $font_size ) ) {
		// 	$font_size = 12;
		// } else {
		// 	$font_size = (int) $font_size;
		// }

		switch ( $shape ) {
			case 'round':
				$border_radius = '100px';
				break;

			case 'slightly-rounded':
				$border_radius = '4px';
				break;

			case 'moderately-rounded':
				$border_radius = '8px';
				break;

			case 'square':
			default:
				$border_radius = '0';
				break;
		}

		return $border_radius;
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
			<h4><?php _e( 'Shortcode', 'ocdutils' ); ?></h4>
			<p><?php _e( 'This shortcode has the following options:', 'ocdutils' ); ?> <code>[ocd_simple_search_form shape="moderately-rounded" color_background="#ffffff" color_text="black" color_border="gray" color_button="#cccccc" color_icon="white" font_size="12" max_width="200" min_width="50px" class="my-special-class"]</code> <br />The color attributes can be a hex color or a css named color.</p>
			<p><?php _e( 'The shortcode attributes can be used to configure the output on a per-instance basis.', 'ocdutils' ); ?></p>
			<p><?php _e( 'Omit all attributes to output the default configuration specified in the global settings above:', 'ocdutils' ); ?> <code>[ocd_simple_search_form]</code></p>
			<p><?php _e( 'Size attributes are in pixels. Color attributes are hex values. The shape parameter accepts "square", "slightly-rounded", "moderately-rounded", or "round"', 'ocdutils' ); ?> <code>[ocd_simple_search_form]</code></p>
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
		/**************************************/
		// Don't use esc_html(), esc_attr(), etc. here.
		// Labels, descriptions, attributes, etc. will be escaped before output.
		// Only use __(), _e(), etc. for translatable strings.
		/**************************************/
		return array(
			'slug' => $this->slug,
			'label' => __( 'Simple Search Form', 'ocdutils' ), // Tab name in the settings page.
			'sections' => array(
				array(
					'id' => 'global',
					'label' => __( 'Global Settings for "Simple Search Form"', 'ocdutils' ),
					'fields' => array(
						array(
							'id' => 'shape',
							'label' => __( 'Shape', 'ocdutils' ),
							'type' => 'select',
							'default' => 'square',
							'options' => array(
								'square'             => __( 'Square',             'ocdutils' ),
								'slightly-rounded'   => __( 'Slightly Rounded',   'ocdutils' ),
								'moderately-rounded' => __( 'Moderately Rounded', 'ocdutils' ),
								'round'              => __( 'Round',              'ocdutils' ),
							)
						),
						array(
							'id' => 'color_background',
							'label' => __( 'Background Color', 'ocdutils' ),
							'type' => 'color',
							'default' => '#ffffff',
						),
						array(
							'id' => 'color_text',
							'label' => __( 'Text Color', 'ocdutils' ),
							'type' => 'color',
							'default' => '#222222',
						),
						array(
							'id' => 'color_border',
							'label' => __( 'Border Color', 'ocdutils' ),
							'type' => 'color',
							'default' => '#cccccc',
						),
						array(
							'id' => 'color_button',
							'label' => __( 'Button Background Color', 'ocdutils' ),
							'type' => 'color',
							'default' => '#dddddd',
						),
						array(
							'id' => 'color_icon',
							'label' => __( 'Button Icon Color', 'ocdutils' ),
							'type' => 'color',
							'default' => '#222222',
						),
						array(
							'id' => 'max_width',
							'label' => __( 'Max Width', 'ocdutils' ),
						 	'description' => __( 'Width in pixels. Leave blank for 100%.', 'ocdutils' ),
							'type' => 'number',
							'min' => '1',
						),
						array(
							'id' => 'min_width',
							'label' => __( 'Min Width', 'ocdutils' ),
						 	'description' => __( 'Width in pixels. Leave blank for fully responsive.', 'ocdutils' ),
							'type' => 'number',
							'min' => '1',
						),
						array(
							'id' => 'font_size',
							'label' => __( 'Font Size', 'ocdutils' ),
						 	'description' => __( 'Size in pixels.', 'ocdutils' ),
							'type' => 'number',
							'min' => '1',
							'default' => '12',
						),
						array(
							'id' => 'class',
							'label' => __( 'Class name', 'ocdutils' ),
							'type' => 'text',
						),
					),
				),
				array(
					'id' => 'usage',
					'label' => __( 'Usage Instructions', 'ocdutils' ),
					'description' => $this->usage_instructions(),
				),
			),
		);
	}
}
endif;

// Instantiate the component class.
$OCD_SimpleSearchForm = new OCD_SimpleSearchForm();

?>
