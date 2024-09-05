<?php

/**
 * OCD_ExampleComponent Class.
 *
 * Provides a shortcode with configurable attributes and a settings page in Admin
 */

if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Class OCD_ExampleComponent
 *
 * Provides a shortcode with configurable attributes and a settings page in Admin
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_ExampleComponent' ) ) :
class OCD_ExampleComponent {
	/**
	 * Holds the slug of the component (the base name of the PHP file).
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Holds the default values for the component's fields, used when specific settings are not configured.
	 *
	 * @var array
	 */
	private $defaults = array();

	/**
	 * Holds the current options/settings for this component, retrieved from the database.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor to initialize the component.
	 */
	public function __construct() {
		$this->slug = basename( __FILE__, '.php' ); // Set the slug to the base filename (without .php extension)
		$this->config(); // Load component configuration.

		// Register the shortcode [ocd_example_component].
		add_shortcode( 'ocd_example_component', array( $this, 'ocd_example_component_shortcode' ) );
	}

	/**
	 * Shortcode handler for rendering the component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output of the component.
	 */
	public function ocd_example_component_shortcode( $atts ) {
		$options = $this->get_options();

		// Set default shortcode attributes, overriding with any provided.
		$atts = shortcode_atts( array(
			'color' => $options['color'], // The default option configured in this file's config function is used here if no settings were ever saved in Admin.
			'class' => '',
		), $atts, 'ocd_example_component' );

		// Sanitize and process attributes
		$atts['color'] = sanitize_text_field( $atts['color'] );

		$atts['class'] = sanitize_text_field( strtolower( $atts['class'] ) );
		// If 'class' is specified in the shortcode, prepend a space to separate it from the built-in class.
		if ( ! empty( $atts['class'] ) ) $atts['class'] = ' ' . $atts['class'];
		// If 'class' is defined in $options, append it to the existing class attribute.
		if ( ! empty( $options['class'] ) ) $atts['class'] = $atts['class'] . ' ' . $options['class'];
	
		// Create a unique ID for each instance of the shortcode to avoid conflicts.
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = 'ocd_example_' . $shortcode_i;
	
		// Enqueue dependencies, scripts, styles if needed
		$vanilla_tilt_dir = OCD_UTILS_URL . 'node_modules/vanilla-tilt/dist/';
		$vanilla_tilt_version = ocd_nodejs_dependency_version( 'vanilla-tilt' );
		//wp_enqueue_style( 'vanilla-tilt', $vanilla_tilt_dir . 'vanilla-tilt.min.css', array(), $vanilla_tilt_version, 'all' );
		wp_enqueue_script( 'vanilla-tilt', $vanilla_tilt_dir . 'vanilla-tilt.min.js', array(), $vanilla_tilt_version, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_add_inline_script( 'vanilla-tilt', $this->inline_script( $shortcode_id ) );

		$outer_div_style = 'width: 200px; height: 150px; background-color: '. $atts['color'] .'; transform-style: preserve-3d; transform: perspective(1000px);';
		$inner_div_style = 'position: absolute; width: 50%; height: 50%; top: 50%; left: 50%; transform: translateZ(30px) translateX(-50%) translateY(-50%); box-shadow: 0 0 50px 0 rgba(51, 51, 51, 0.3); background-color: white;';

		// Build the HTML output for the component.
		$html = '';
		$html .= '<div id="'. $shortcode_id .'" class="ocd_example'. $atts['class'] .'" style="'. $outer_div_style .'">';
			$html .= '<div style="'. $inner_div_style .'"></div>';
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
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function(){
				VanillaTilt.init(document.querySelector('#<?php echo esc_js( $id ); ?>'), {
					max: 25,
					speed: 400,
					reverse: true,
					glare: true,
					scale: 1.1,
				});
			});
		</script>
		<?php
		// Keep script tags above just to make the code look nice in the editor, remove script tags before output.
		return str_replace( array( '<script type="text/javascript">', '</script>' ), '', ob_get_clean() );
	}














	
	
	/**
	 * Retrieves the current options/settings from the database.
	 *
	 * @return array The current options for this component.
	 */
	private function get_options() {
		// Fetch options from the database if not already loaded.
		if ( empty( $this->options ) ) {
			$this->options = get_option( $this->slug, array() );
		}

		// Merge any default values with the retrieved options.
		if ( ! empty( $this->defaults ) ) {
			$diff = array_diff_key( $this->defaults, $this->options );
			$this->options = array_merge( $this->options, $diff );
		}

		return $this->options;
	}















	/**
	 * Configures the settings for this component.
	 */
	private function config() {
		// Define the component's configuration, including admin page fields and default settings.
		$config = array(
			'slug' => $this->slug,
			'label' => esc_html( __( 'Example', 'ocdutils' ) ), // Tab name in the settings page.
			'sections' => array(
				array(
					'id' => 'global',
					'label' => esc_html( __( 'Global Settings for "Example Component"', 'ocdutils' ) ),
					'fields' => array(
						array(
							'id' => 'color',
							'label' => 'Box color',
							'type' => 'select',
							'description' => esc_html( __( 'Default color of the box. This is used if no color is specified in the shortcode.', 'ocdutils' ) ),
							'default' => 'orchid',
							'options' => array(
								''             => 'None',
								'teal'         => 'Teal',
								'purple'       => 'Purple',
								'cyan'         => 'Cyan',
								'magenta'      => 'Magenta',
								'salmon'       => 'Salmon',
								'orchid'       => 'Orchid',
								'violet'       => 'Violet',
								'turquoise'    => 'Turquoise',
								'crimson'      => 'Crimson'
							)
						),
						array(
							'id' => 'class',
							'label' => 'Class name',
							'type' => 'text',
							'description' => esc_html( __( 'Adds an extra class name to the output div. It is added in addition to the class name given in the shortcode, if any.', 'ocdutils' ) ),
						),
					),
				),
				array(
					'id' => 'usage',
					'label' => esc_html( __( 'Component Usage Instructions', 'ocdutils' ) ),
					'description' => $this->usage_instructions(),
				),
				array(
					'id' => 'dummy',
					'label' => esc_html( __( 'Other Settings for "Example Component"', 'ocdutils' ) ),
					'description' => esc_html( __( 'Other Settings for "Example Component". These do nothing in this example. This is just to show the field types available.', 'ocdutils' ) ),
					'fields' => array(
						array(
							'id' => 'number_field_a',
							'label' => 'Number of items',
							'type' => 'number',
							'description' => esc_html( __( 'Set the number of items you want to show.', 'ocdutils' ) ),
							'required' => true,
							'class' => 'class-for-field-input-element',
							'min' => '1',
							'max' => '100',
							'step' => 4,
						),
						array(
							'id' => 'checkboxes_a',
							'label' => 'Colors',
							'type' => 'checkboxes',
							'description' => esc_html( __( 'Choose the colors you like.', 'ocdutils' ) ),
							'options' => array(
								'blue' => 'Blue',
								'red' => 'Red',
								'green' => 'Green',
							),
						),
						array(
							'id' => 'select_a',
							'label' => 'Direction',
							'type' => 'select',
							'description' => esc_html( __( 'Which way did he go, George?', 'ocdutils' ) ),
							'options' => array(
								'' => 'No Direction', // Optional empty value
								'up' => 'Up',
								'down' => 'Down',
								'left' => 'Left',
								'right' => 'Right',
								'dont_know' => 'I Have No Idea',
							),
						),
						array(
							'id' => 'text_a',
							'label' => 'Just a string',
							'type' => 'text',
							//'description' => esc_html( __( 'A normal text string.', 'ocdutils' ) ),
							'required' => true,
						),
						// Additional fields can be defined here.
					),
				),
				// Additional sections can be defined here.
			),
		);

		// Parse default values from the configuration and store it to be used later when the Admin settings options are loaded from the database.
		$this->defaults = ocd_parse_config_for_default_values( $config['sections'] );

		// If in the admin area, register this component's settings.
		if ( is_admin() ) {
			add_filter( 'ocdutils_settings_config', function( $settings_config_r ) use ( $config ) { 
				$settings_config_r['components'][] = $config;
				return $settings_config_r;
			} );
		}
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
			<h4>Options</h4>
			<p>The above settings are global; they apply uniformly across all instances of this component output.</p>
			<h4>Shortcode</h4>
			<p>This example provides a shortcode with 2 options: <code>[ocd_example_component color="red" class="my-special-class"]</code></p>
			<p>The shortcode attributes can be used to configure the output on a per-instance basis.</p>
			<p>Omit all attributes to output the default configuration specified in the global settings above: <code>[ocd_example_component]</code></p>
			<br /><br />
		</div>
		<?php
		return ob_get_clean();
	}
}
endif;

// Instantiate the component class.
$OCD_ExampleComponent = new OCD_ExampleComponent();

?>
