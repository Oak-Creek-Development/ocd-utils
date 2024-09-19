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
	public $slug = '';

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

		// Register the shortcode [ocd_example_component].
		add_shortcode( 'ocd_example_component', array( $this, 'shortcode' ) );
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
	public function shortcode( $atts = array(), $content = '', $tag = 'ocd_example_component' ) {
		$options = ocd_get_options( $this );

		// Set default shortcode attributes, overriding with any provided.
		$atts = shortcode_atts( 
			array(
				'color' => $options['color'], // The default option configured in this file's config function is used here if no settings were ever saved in Admin.
				'class' => '',
			), 
			array_change_key_case( (array)$atts, CASE_LOWER ), 
			$tag 
		);

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
		$shortcode_id = $this->slug .'_' . $shortcode_i;
	
		// Enqueue dependencies, scripts, styles if needed
		$vanilla_tilt_dir = OCD_UTILS_URL . 'node_modules/vanilla-tilt/dist/';
		$vanilla_tilt_version = ocd_nodejs_dependency_version( 'vanilla-tilt' );

		// In this case there is no style, but you can enqueue your styles here if needed.
		//wp_enqueue_style( 'vanilla-tilt', $vanilla_tilt_dir . 'vanilla-tilt.min.css', array(), $vanilla_tilt_version, 'all' );
		//wp_add_inline_style( 'vanilla-tilt', $this->inline_style( $shortcode_id ) );

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
		<script>
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
		return str_replace( array( '<script>', '</script>' ), '', ob_get_clean() );
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
			<h4><?php _e( 'Options', 'ocdutils' ); ?></h4>
			<p><?php _e( 'The above settings are global; they apply uniformly across all instances of this component output.', 'ocdutils' ); ?></p>
			<h4><?php _e( 'Shortcode', 'ocdutils' ); ?></h4>
			<p><?php _e( 'This example provides a shortcode with 2 options:', 'ocdutils' ); ?> <code>[ocd_example_component color="red" class="my-special-class"]</code></p>
			<p><?php _e( 'The shortcode attributes can be used to configure the output on a per-instance basis.', 'ocdutils' ); ?></p>
			<p><?php _e( 'Omit all attributes to output the default configuration specified in the global settings above:', 'ocdutils' ); ?> <code>[ocd_example_component]</code></p>
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
			'label' => __( 'Example', 'ocdutils' ), // Tab name in the settings page.
			'sections' => array(
				array(
					'id' => 'global',
					'label' => __( 'Global Settings for "Example Component"', 'ocdutils' ),
					'fields' => array(
						array(
							'id' => 'color', // Field id must be unique within each component
							'label' => __( 'Box color', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Default color of the box. This is used if no color is specified in the shortcode.', 'ocdutils' ),
							'default' => 'orchid',
							'options' => array(
								''          => __( 'None',      'ocdutils' ),
								'teal'      => __( 'Teal',      'ocdutils' ),
								'purple'    => __( 'Purple',    'ocdutils' ),
								'cyan'      => __( 'Cyan',      'ocdutils' ),
								'magenta'   => __( 'Magenta',   'ocdutils' ),
								'salmon'    => __( 'Salmon',    'ocdutils' ),
								'orchid'    => __( 'Orchid',    'ocdutils' ),
								'violet'    => __( 'Violet',    'ocdutils' ),
								'turquoise' => __( 'Turquoise', 'ocdutils' ),
								'crimson'   => __( 'Crimson',   'ocdutils' ),
							)
						),
						array(
							'id' => 'class', // Field id must be unique within each component
							'label' => __( 'Class name', 'ocdutils' ),
							'type' => 'text',
							'description' => __( 'Adds an extra class name to the output div. It is added in addition to the class name given in the shortcode, if any.', 'ocdutils' ),
						),
					),
				),
				array(
					'id' => 'usage',
					'label' => __( 'Component Usage Instructions', 'ocdutils' ),
					'description' => $this->usage_instructions(),
				),
				array(
					'id' => 'dummy',
					'label' => __( 'Other Settings for "Example Component"', 'ocdutils' ),
					'description' => __( 'These do nothing in this example. This is just to show the field types available.', 'ocdutils' ),
					'fields' => array(
						array(
							'id' => 'number_field_a', // Field id must be unique within each component
							'label' => __( 'Number of Items', 'ocdutils' ),
							'type' => 'number',
							'description' => __( 'Set the number of items you want to show.', 'ocdutils' ),
							'required' => true,
							'class' => 'class-for-field-input-element',
							'min' => '1',
							'max' => '100',
							'step' => 4,
						),
						array(
							'id' => 'radio_a', // Field id must be unique within each component
							'label' => __( 'Favorite Food', 'ocdutils' ),
							'type' => 'radio',
							'description' => __( 'Choose your favorite food.', 'ocdutils' ),
							'default' => 'soup',
							'options' => array(
								'hot_dog'   => __( 'Hot Dog',   'ocdutils' ),
								'hamburger' => __( 'Hamburger', 'ocdutils' ),
								'taco'      => __( 'Taco',      'ocdutils' ),
								'soup'      => __( 'Soup',      'ocdutils' ),
								'spaghetti' => __( 'Spaghetti', 'ocdutils' ),
							),
						),
						array(
							'id' => 'checkboxes_a', // Field id must be unique within each component
							'label' => __( 'Pretty Colors', 'ocdutils' ),
							'type' => 'checkboxes',
							'description' => __( 'Choose the colors you like.', 'ocdutils' ),
							'options' => array(
								'red'   => __( 'Red',   'ocdutils' ),
								'green' => __( 'Green', 'ocdutils' ),
								'blue'  => __( 'Blue',  'ocdutils' ),
							),
						),
						array(
							'id' => 'select_a', // Field id must be unique within each component
							'label' => __( 'Direction', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Which way did he go, George?', 'ocdutils' ),
							'options' => array(
								''          => __( 'Choose a Direction', 'ocdutils' ), // Optional empty value
								'up'        => __( 'Up',                 'ocdutils' ),
								'down'      => __( 'Down',               'ocdutils' ),
								'left'      => __( 'Left',               'ocdutils' ),
								'right'     => __( 'Right',              'ocdutils' ),
								'dont_know' => __( 'I Have No Idea',     'ocdutils' ),
							),
						),
						array(
							'id' => 'text_a', // Field id must be unique within each component
							'label' => __( 'A Required String', 'ocdutils' ),
							'type' => 'text',
							//'description' => __( 'A normal text string.', 'ocdutils' ),
							'required' => true,
						),
						// Additional fields can be defined here.
					),
				),
				// Additional sections can be defined here.
			),
		);
	}
}
endif;

// Instantiate the component class.
$OCD_ExampleComponent = new OCD_ExampleComponent();

?>
