<?php
/**
 * OCD_ExampleComponent Class.
 *
 * Generates a tab on the settings page. Doesn't do anything on the website frontend.
 */

if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Class OCD_UpcomingEvents
 *
 * Generates a carousel of upcoming events.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_ExampleComponent' ) ) :
class OCD_ExampleComponent {
	/**
	 * Holds the slug of the component.
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Holds the current options for the current component retrieved from the database.
	 *
	 * @var array
	 */
	private $options = array();

	public function __construct() {
		$this->slug = basename( __FILE__, '.php' );

		if ( is_admin() ) {
			add_filter( 'ocdutils_settings_config', array( $this, 'add_settings_page' ) );
		}

		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_shortcode( 'ocd_example_component', array( $this, 'ocd_example_component_shortcode' ) );
	}
	
	private function get_options() {
		if ( empty( $this->options ) ) $this->options = get_option( $this->slug, array() );
		return $this->options;
	}

	public function ocd_example_component_shortcode( $atts ) {
		//phpinfo();
		$options = $this->get_options();

		// Set default attributes for the shortcode
		$atts = shortcode_atts( array(
			'color' => $options['color'] ?? 'blue',
			'class' => '',
		), $atts, 'ocd_example_component' );

		// Sanitize and process attributes
		$atts['color'] = sanitize_text_field( $atts['color'] );

		$atts['class'] = sanitize_text_field( $atts['class'] );
		// If 'class' is specified in the shortcode, prepend a space to separate it from the built-in class
		if ( ! empty( $atts['class'] ) ) $atts['class'] = ' ' . $atts['class'];
		// If 'class' is defined in $options, append it to the existing class attribute
		if ( ! empty( $options['class'] ) ) $atts['class'] = $atts['class'] . ' ' . $options['class'];
	
		// Unique ID for each carousel instance
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = 'ocd_example_' . $shortcode_i;
	
		// Enqueue dependencies
		$vanilla_tilt_dir = OCD_UTILS_URL . 'node_modules/vanilla-tilt/dist/';
		$vanilla_tilt_version = ocd_nodejs_dependency_version( 'vanilla-tilt' );
		//wp_enqueue_style( 'vanilla-tilt', $vanilla_tilt_dir . 'vanilla-tilt.min.css', array(), $vanilla_tilt_version, 'all' );
		wp_enqueue_script( 'vanilla-tilt', $vanilla_tilt_dir . 'vanilla-tilt.min.js', array(), $vanilla_tilt_version, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_add_inline_script( 'vanilla-tilt', $this->inline_script( $shortcode_id ) );

		$outer_div_style = 'width: 200px; height: 150px; background-color: '. $atts['color'] .'; transform-style: preserve-3d; transform: perspective(1000px);';
		$inner_div_style = 'position: absolute; width: 50%; height: 50%; top: 50%; left: 50%; transform: translateZ(30px) translateX(-50%) translateY(-50%); box-shadow: 0 0 50px 0 rgba(51, 51, 51, 0.3); background-color: white;';

		$html = '';
		$html .= '<div id="'. $shortcode_id .'" class="ocd_example'. $atts['class'] .'" style="'. $outer_div_style .'">';
			$html .= '<div style="'. $inner_div_style .'"></div>';
		$html .= '</div>';

		return $html;
	}

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
		return ob_get_clean();
	}

	public function add_settings_page( $settings_config_r ) {
		// Add your fields here
		$settings_config_r['components'][] = array(
			'slug' => $this->slug,
			'label' => esc_html( __( 'Example', 'ocdutils' ) ), // Tab name
			'sections' => array(
				array(
					'id' => 'global',
					'label' => esc_html( __( 'Global Settings for "Example Component"', 'ocdutils' ) ),
					'fields' => array(
						array(
							'id' => 'color',
							'label' => 'Box color',
							'type' => 'select',
							'description' => esc_html( __( 'Default color of the box.', 'ocdutils' ) ),
							// TODO: This only populates the default in the settings page. It doesn't actually apply as a default setting in the output until after the settings are saved
							'default' => 'lime',
							'options' => array(
								'teal'         => 'Teal',
								'purple'       => 'Purple',
								'orange'       => 'Orange',
								'pink'         => 'Pink',
								'yellow'       => 'Yellow',
								'cyan'         => 'Cyan',
								'magenta'      => 'Magenta',
								'lime'         => 'Lime',
								'coral'        => 'Coral',
								'salmon'       => 'Salmon',
								'gold'         => 'Gold',
								'orchid'       => 'Orchid',
								'violet'       => 'Violet',
								'indigo'       => 'Indigo',
								'turquoise'    => 'Turquoise',
								'crimson'      => 'Crimson'
							)
						),
						array(
							'id' => 'class',
							'label' => 'Class name',
							'type' => 'text',
							'description' => esc_html( __( 'Adds a custom class name to the output div.', 'ocdutils' ) ),
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
							'label' => 'Which things',
							'type' => 'checkboxes',
							'description' => esc_html( __( 'Choose the things you want.', 'ocdutils' ) ),
							'options' => array(
								'black' => 'Black',
								'white' => 'White',
								'blue' => 'Blue',
								'red' => 'Red',
								'green' => 'Green',
								'teal' => 'Teal',
								'silver' => 'Silver',
							),
						),
					),
				),
			),
		);

		return $settings_config_r;
	}

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
		</div>
		<?php
		return ob_get_clean();
	}
}
endif;
$OCD_ExampleComponent = new OCD_ExampleComponent();
?>
