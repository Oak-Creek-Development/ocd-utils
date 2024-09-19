<?php
/**
 * OCD_AdminSettings Class.
 *
 * Generates a tabbed admin settings page based on a provided config array containing the details of the sections and fields.
 */

if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Class OCD_AdminSettings
 *
 * Handles the rendering of the admin settings page and manages settings registration.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_AdminSettings' ) ) :

class OCD_AdminSettings {
   /**
    * Holds the configuration array.
    *
    * @var array
    */
	private $config = array();

   /**
    * Holds the current component (tab) configuration.
    *
    * @var array
    */
	private $component = array();

   /**
    * Holds the current options for the current component retrieved from the database.
    *
    * @var array
    */
	private $options = array();

	/**
	 * Constructor for OCD_AdminSettings.
	 *
	 * @param array $config Configuration array for settings.
	 */
	public function __construct( $config ) {
		$this->config = $config;

		// Sanitize the 'tab' query parameter to avoid malicious input
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';

		// Set the current component based on the selected tab, default to the first component
		$this->component = empty( $current_tab ) ? $this->config['components'][0] : $this->get_component_by_slug( $current_tab );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * Retrieves a component configuration by its slug.
	 *
	 * @param string $slug Component slug.
	 * @return array|null Component configuration or null if not found.
	 */
	private function get_component_by_slug( $slug ) {
		foreach ( $this->config['components'] as $component ) {
			if ( $component['slug'] === $slug ) {
				return $component;
			}
		}
		
		return null;
	}

   /**
    * Adds a settings page to the WordPress admin menu.
    *
    * @since 1.0.0
    */
	public function admin_menu() {
		add_options_page(
			$this->config['page_title'], 
			$this->config['menu_title'], 
			$this->config['capability'], // Required capability to access this page
			$this->config['page_slug'], 
			array( $this, 'render_settings_page' ) // Callback to render the settings page
		);
	}

   /**
    * Registers the settings and fields for the plugin.
    *
    * @since 1.0.0
    */
	public function admin_init() {
		if ( ! current_user_can( $this->config['capability'] ) ) {
			return; // Bail if the user doesn't have the required capability
		}
		
      // Register settings for each component.   TODO: probably ok to register only the current component but I'll leave this alone for now.
		if ( isset( $this->config['components'] ) && is_array( $this->config['components'] ) ) {
			foreach ( $this->config['components'] as $component ) {
				if ( empty( $component['slug'] ) ) {
					continue;
				}

				$component_setting_args_r = array( 
					'type' => 'array', 
					'sanitize_callback' => array( $this, 'sanitize_array' ) ,
				);
				if ( ! empty( $component['label']        ) ) $component_setting_args_r['label']        = $component['label'];       // TODO: figure out if I need to do anything to handle this if it is set
				if ( ! empty( $component['description']  ) ) $component_setting_args_r['description']  = $component['description']; // TODO: figure out if I need to do anything to handle this if it is set
				if ( ! empty( $component['show_in_rest'] ) ) $component_setting_args_r['show_in_rest'] = $component['show_in_rest'];
				if ( ! empty( $component['default']      ) ) $component_setting_args_r['default']      = $component['default'];

				register_setting(	
					$component['slug'], 
					$component['slug'], 
					$component_setting_args_r
				);
			}
		}

      // Add sections and fields for the current component
		if ( isset( $this->component['sections'] ) && is_array( $this->component['sections'] ) ) {
			foreach ( $this->component['sections'] as $section ) {
				if ( empty( $section['id'] ) ) {
					continue;
				}

				if ( ! isset( $section['label'] ) ) {
					$section['label'] = esc_html( __( 'Component Settings', 'ocdutils' ) );
				}

				add_settings_section(
					$section['id'],
					esc_html( $section['label'] ),
					function() use ( $section ) { echo isset( $section['description'] ) ? html_entity_decode( esc_html( $section['description'] ) ) : ''; },
					$this->component['slug']
				);

				// Add fields for the current section
				if ( isset( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field ) {
						if ( empty( $field['id'] ) ) {
							continue;
						}

						$field['label_for'] = $field['id'];

						add_settings_field(
							$field['id'],
							$field['label'],
							array( $this, 'render_field_' . $field['type'] ), // Callback to generate the field html
							$this->component['slug'],
							$section['id'],
							$field // Config array passed to the callback
						);
					}
				}
			}
		}
	}

   /**
    * Renders the settings page in the WordPress admin.
    *
    * @since 1.0.0
    */
	public function render_settings_page() {
		if ( ! current_user_can( $this->config['capability'] ) ) {
			return; // Bail if the user doesn't have the required capability
		}

		echo '<div class="wrap">';
		
			echo '<h1>'. esc_html( $this->config['page_title'] ) .'</h1>';

			// Display tabs if there are multiple components
			if ( count( $this->config['components'] ) > 1 ) {
				echo '<h2 class="nav-tab-wrapper">';
					foreach ( $this->config['components'] as $tab ) {
						if ( ! empty( $tab['slug'] ) ) {
							$href = '?page='. esc_attr( $this->config['page_slug'] ) .'&tab='. esc_attr( $tab['slug'] );
							$class = 'nav-tab '. ( $this->component['slug'] == $tab['slug'] ? 'nav-tab-active' : '' );
							
							echo '<a href="'. $href .'" class="'. $class .'">'. esc_html( $tab['label'] ) .'</a>';
						}
					}
				echo '</h2>';
			}

			echo '<form method="post" action="options.php">';

				settings_fields( $this->component['slug'] ); // Output nonce, action, and option_page fields
				do_settings_sections( $this->component['slug'] ); // Output sections and their fields

				if ( $this->component_has_field( $this->component ) ) {
					submit_button(); // Render the submit button
				}

			echo '</form>';

		echo '</div>';
	}

	/**
	 * Determines whether a component's config array contains any fields.
	 *
	 * @return boolean
	 */
	private function component_has_field( $component ) {
		if ( isset( $component['sections'] ) && is_array( $component['sections'] ) ) {
			foreach ( $component['sections'] as $section ) {
				if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field ) {
						if ( isset( $field['id'] ) ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Retrieves the options for the current component.
	 *
	 * @return array Component options.
	 */
	private function get_options() {
		if ( empty( $this->options ) ) {
			$this->options = (array) get_option( $this->component['slug'] );
		}

		return $this->options;
	}

	/**
	 * Retrieves the value for a specific field.
	 *
	 * @param array $field The field configuration array.
	 * @return mixed Field value or default value.
	 */
	private function get_val( $field ) {
      return $this->get_options()[$field['id']] ?? '';
	}
	
	/**
	 * Generates field attributes for input elements.
	 *
	 * @param array $field Field configuration.
	 * @return string Field attributes as a string.
	 */
	private function field_atts( $field ) {
		$atts  = '';
		$atts .= ' id="'. esc_attr( $field['id'] ) .'"';
		$atts .= ' name="'. esc_attr( $this->component['slug'] .'['. $field['id'] .']' ) .'"';
		$atts .= empty( $field['class'] )    ? '' : ' class="'. esc_attr( $field['class'] ) .'"';
		$atts .= empty( $field['required'] ) ? '' : ' required="required"';
		$atts .= empty( $field['min'] )      ? '' : ' min="'. esc_attr( $field['min'] ) .'"';
		$atts .= empty( $field['max'] )      ? '' : ' max="'. esc_attr( $field['max'] ) .'"';
		$atts .= empty( $field['step'] )     ? '' : ' step="'. esc_attr( $field['step'] ) .'"';
	
		return $atts;
	}
	
	/**
	 * Generates input field html for type: text.
	 *
	 * @param array $field Field configuration.
	 * @return string Input field html as a string.
	 */
	public function render_field_text( $field ) {
		$val = $this->get_val( $field );

		$field['class'] = empty( $field['class'] ) ? 'regular-text' : '';

		$atts = $this->field_atts( $field );
		$atts .= empty( $val ) ? '' : ' value="'. esc_html( $val ) .'"';
	
		echo '<input type="text"'. $atts .' />';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. esc_html( $field['description'] ) .'</p>';
	}
	
	/**
	 * Generates input field html for type: number.
	 *
	 * @param array $field Field configuration.
	 * @return string Input field html as a string.
	 */
	public function render_field_number( $field ) {
		$val = $this->get_val( $field );
	
		$field['class'] = trim( 'small-text ' . esc_attr( ( $field['class'] ?? '' ) ) );
	
		$atts = $this->field_atts( $field );
		$atts .= empty( $val ) ? '' : ' value="'. esc_html( $val ) .'"';
	
		echo '<input type="number"'. $atts .' />';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. esc_html( $field['description'] ) .'</p>';
	}
	
	/**
	 * Generates input field html for type: select.
	 *
	 * @param array $field Field configuration.
	 * @return string Input field html as a string.
	 */
	public function render_field_select( $field ) {
		$val = $this->get_val( $field );
	
		$atts = $this->field_atts( $field );
	
		echo '<select'. $atts .'>';
		foreach ( $field['options'] as $k => $v ) {
			echo '<option value="'. esc_attr( $k ) .'" '. selected( $k, $val, false ) .'>'. esc_html( $v ) .'</option>';
		}
		echo '</select>';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. esc_html( $field['description'] ) .'</p>';
	}
	
	/**
	 * Generates input field html for type: checkboxes.
	 *
	 * @param array $field Field configuration.
	 * @return string Input field html as a string.
	 */
	public function render_field_checkboxes( $field ) {
		$val = $this->get_val( $field );

		echo '<fieldset><legend class="screen-reader-text"><span>'. esc_html( $field['label'] ) .'</span></legend>';
			foreach ( $field['options'] as $k => $v ) {
				$name = $this->component['slug'] .'['. $field['id'] .']';
				$id = $name . $k;
				$checked = is_array( $val ) && in_array( $k, $val ) ? ' checked="checked"' : '';

				echo '<label for="'. esc_attr( $id ) .'">';
					echo '<input type="checkbox" name="'. esc_attr( $name ) .'[]" id="'. esc_attr( $id ) .'" value="'. esc_attr( $k ) .'"'. $checked .' />';
				echo esc_html( $v ) . '</label><br />';
			}
			if ( ! empty( $field['description'] ) ) echo '<p class="description">'. esc_html( $field['description'] ) .'</p>';
      echo '</fieldset>';
	}
	
	/**
	 * Generates input field html for type: radio.
	 *
	 * @param array $field Field configuration.
	 * @return string Input field html as a string.
	 */
	public function render_field_radio( $field ) {
		$val = $this->get_val( $field );
	
      echo '<fieldset><legend class="screen-reader-text"><span>'. esc_html( $field['label'] ) .'</span></legend>';
			echo '<p>';
				foreach ( $field['options'] as $k => $v ) {
					$name = $this->component['slug'] .'['. $field['id'] .']';
					$required = empty( $field['required'] ) ? '' : ' required="required"';
					$checked = isset( $val ) && $k === $val ? ' checked="checked"' : '';

					echo '<label>';
						echo '<input type="radio" name="'. esc_attr( $name ) .'" value="'. esc_attr( $k ) .'"'. $checked . $required .' />';
					echo esc_html( $v ) . '</label><br />';
				}
			echo '</p>';
			if ( ! empty( $field['description'] ) ) echo '<p class="description">'. esc_html( $field['description'] ) .'</p>';
      echo '</fieldset>';
	}

	/**
	 * Sanitize array values before saving.
	 *
	 * @param array $data Array of settings data.
	 * @return array Sanitized data.
	 */
	public function sanitize_array( $r ) {
		if ( empty( $_POST['option_page'] ) ) {
			return $r;
		}

		// Ensure the input is an array
		if ( ! is_array( $r ) ) {
			return $r;
		}

		$fields = array();
		foreach ( $this->config['components'] as $component ) {
			if ( $component['slug'] == $_POST['option_page'] && isset( $component['fields'] ) ) {
				$fields = $component['fields'];
				break;
			}
		}

		// Sanitize each element in the array
		foreach ( $r as $k => $v ) {
			foreach ( $fields as $field ) {
				if ( $k == $field['id'] ) {
					if ( 'number' == $field['type'] ) {
						if ( is_numeric( $v ) ) {
							$r[$k] = strpos( $v, '.' ) === false ? intval( $v ) : floatval( $v );
						} else {
							$r[$k] = 0;
						}
					} elseif ( 'select' == $field['type'] ) {
						$allowed_values = array_keys( $field['options'] ) ?: array();
						if ( in_array( $v, $allowed_values ) ) {
							$r[$k] = $v;
						} else {
							$r[$k] = '';
						}
					} elseif ( 'checkboxes' == $field['type'] ) {
						$r[$k] = array();
						$allowed_values = array_keys( $field['options'] ) ?: array();
						foreach ( $v as $val ) {
							if ( in_array( $val, $allowed_values ) ) {
								$r[$k][] = $val;
							}
						}
					} else {
						$r[$k] = sanitize_text_field( $v );
					}

					break;
				}
			}
		}

		return $r;
	}
}

endif;

?>
