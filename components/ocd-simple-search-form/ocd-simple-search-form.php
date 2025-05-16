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
				'color_background' => $options['color_background'],
				'color_text'       => $options['color_text'],
				'color_border'     => $options['color_border'],
				'color_button'     => $options['color_button'],
				'color_icon'       => $options['color_icon'],
				'class'            => '',
			), 
			array_change_key_case( (array)$atts, CASE_LOWER ), 
			$tag 
		);

		// Sanitize and process attributes
		$atts['color_background'] = sanitize_text_field( $atts['color_background'] );
		$atts['color_text']       = sanitize_text_field( $atts['color_text'] );
		$atts['color_border']     = sanitize_text_field( $atts['color_border'] );
		$atts['color_button']     = sanitize_text_field( $atts['color_button'] );
		$atts['color_icon']       = sanitize_text_field( $atts['color_icon'] );

		$atts['class'] = sanitize_text_field( strtolower( $atts['class'] ) );
		// If 'class' is specified in the shortcode, prepend a space to separate it from the built-in class.
		if ( ! empty( $atts['class'] ) ) $atts['class'] = ' ' . $atts['class'];
		// If 'class' is defined in $options, append it to the existing class attribute.
		if ( ! empty( $options['class'] ) ) $atts['class'] = $atts['class'] . ' ' . $options['class'];
	
		// Create a unique ID for each instance of the shortcode to avoid conflicts.
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = $this->slug .'-' . $shortcode_i;
	
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 
			$this->slug, 
			OCD_UTILS_URL .'components/'. $this->slug .'/'. $this->slug .'.min.css', 
			array(), 
			$this->version, 
			'all' 
		);
		wp_add_inline_style( $this->slug, $this->inline_style( $shortcode_id ) );
		wp_enqueue_style( $this->slug );

		$home_url = esc_url( home_url( '/' ) );
		$search_query = get_search_query();

		// Build the HTML output for the component.
		$html = '';
		$html .= '<form role="search" method="get" id="'. $shortcode_id .'" class="search-form '. $this->slug . $atts['class'] .'" action="' . $home_url . '">';
			$html .= '<label for="search-field" class="sr-only">'. __( 'Search for:', 'ocdutils' ) .'</label>';
			$html .= '<input name="s" type="search" id="search-field" class="search-field" placeholder="'. __( 'Search …', 'ocdutils' ) .'" value="' . esc_attr( $search_query ) . '" />';
			$html .= '<button type="submit" class="search-submit dashicons dashicons-search" aria-label="'. __( 'Search', 'ocdutils' ) .'"></button>';
		$html .= '</form>';

		return $html;
	}

	/**
	 * Generates the inline CSS for the component.
	 *
	 * @param string $id The unique ID of the component instance.
	 * @return string The inline CSS code.
	 */
	private function inline_style( $id ) {
		ob_start();
		?>
		<style>
			form {
				color: red;
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
			<h4><?php _e( 'Shortcode', 'ocdutils' ); ?></h4>
			<p><?php _e( 'This shortcode has 6 options:', 'ocdutils' ); ?> <code>[ocd_simple_search_form color_background="#ffffff" color_text="black" color_border="gray" color_button="#cccccc" color_icon="white" class="my-special-class"]</code> <br />The color attributes can be a hex color or a css named color.</p>
			<p><?php _e( 'The shortcode attributes can be used to configure the output on a per-instance basis.', 'ocdutils' ); ?></p>
			<p><?php _e( 'Omit all attributes to output the default configuration specified in the global settings above:', 'ocdutils' ); ?> <code>[ocd_simple_search_form]</code></p>
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
						// array(
						// 	'id' => 'icon',
						// 	'label' => __( 'Icon', 'ocdutils' ),
						// 	'description' => __( '', 'ocdutils' ),
						// 	'type' => 'text',
						// 	'default' => 'dashicons-search',
						// ),
						array(
							'id' => 'font_size',
							'label' => __( 'Font Size', 'ocdutils' ),
							'type' => 'number',
							'min' => '1',
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
