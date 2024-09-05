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
 * Manages the admin settings page and handles the registration of settings.
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

   public function __construct( $config ) {
		$this->config = $config;

      if ( empty( $_GET['tab'] ) ) {
			$this->component = $this->config['components'][0]; // Default to the first component
		} else {
			foreach ( $this->config['components'] as $component ) {
				if ( $component['slug'] != $_GET['tab'] ) continue;
				$this->component = $component;
				break;
			}
		}

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
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
		if ( ! current_user_can( $this->config['capability'] ) ) return; // Bail if the user doesn't have the required capability
		
      // Register settings for each component
		foreach ( $this->config['components'] as $component ) {
			if ( empty( $component['slug'] ) ) continue;

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

      // Add settings sections and fields
		foreach ( $this->component['sections'] as $section ) {
			if ( empty( $section['id'] ) ) continue;

			add_settings_section(
				$section['id'],
				$section['label'],
				function() use ( $section ) { echo isset( $section['description'] ) ? $section['description'] : ''; },
				$this->component['slug']
			);

			if ( isset( $section['fields'] ) ) {
				foreach ( $section['fields'] as $field ) {
					if ( empty( $field['id'] ) ) continue;

					$field['label_for'] = $field['id'];

					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this, 'render_field_' . $field['type'] ), // Callback to generate the field
						$this->component['slug'],
						$section['id'],
						$field // Config array passed to the callback
					);
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
		if ( ! current_user_can( $this->config['capability'] ) ) return; // Bail if the user doesn't have the required capability

		?>
		<div class="wrap">
			<h1><?php echo $this->config['page_title']; ?></h1>
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $this->config['components'] as $tab ) {
					if ( empty( $tab['slug'] ) ) continue;
					echo '<a href="?page='. $this->config['page_slug'] .'&tab='. $tab['slug'] .'" class="nav-tab'. ( $this->component['slug'] == $tab['slug'] ? ' nav-tab-active' : '' ) .'">'. $tab['label'] .'</a>';
				}
				?>
			</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->component['slug'] ); // Output nonce, action, and option_page fields
				do_settings_sections( $this->component['slug'] ); // Output sections and their fields
				submit_button(); // Render the submit button
				?>
			</form>
		</div>
		<?php
	}

   private function get_option() {
		if ( empty( $this->options ) ) {
         $this->options = (array) get_option( $this->component['slug'] );
      }
		return $this->options;
	}

	private function get_val( $field ) {
      return $this->get_option()[$field['id']] ?? '';
	}
	
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
	
	public function render_field_text( $field ) {
		$val = $this->get_val( $field );
		if ( empty( $val ) && ! empty( $field['default'] ) ) $val = $field['default'];

		$atts = $this->field_atts( $field );
		$atts .= empty( $val ) ? '' : ' value="'. esc_html( $val ) .'"';
	
		echo '<input type="text"'. $atts .' />';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. $field['description'] .'</p>';
	}
	
	public function render_field_number( $field ) {
		$val = $this->get_val( $field );
		if ( empty( $val ) && ! empty( $field['default'] ) ) $val = $field['default'];
	
		$field['class'] = trim( 'small-text ' . ( $field['class'] ?? '' ) );
	
		$atts = $this->field_atts( $field );
		$atts .= empty( $val ) ? '' : ' value="'. esc_html( $val ) .'"';
	
		echo '<input type="number"'. $atts .' />';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. $field['description'] .'</p>';
	}
	
	public function render_field_select( $field ) {
		$val = $this->get_val( $field );
		if ( empty( $val ) && isset( $field['default'] ) && in_array( $field['default'], array_keys( $field['options'] ) ) ) $val = $field['default'];
	
		$atts = $this->field_atts( $field );
	
		echo '<select'. $atts .'>';
		foreach ( $field['options'] as $k => $v ) {
			echo '<option value="'. $k .'" '. selected( $k, $val, false ) .'>'. $v .'</option>';
		}
		echo '</select>';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. $field['description'] .'</p>';
	}
	
	public function render_field_checkboxes( $field ) {
		$val = $this->get_val( $field );
		if ( empty( $val ) && isset( $field['default'] ) && in_array( $field['default'], array_keys( $field['options'] ) ) ) $val = $field['default'];
	
      echo '<fieldset><legend class="screen-reader-text"><span>'. $field['label'] .'</span></legend>';
		foreach ( $field['options'] as $k => $v ) {
         $name = $this->component['slug'] .'['. $field['id'] .']';
         $id = $name . $k;
         $checked = is_array( $val ) && in_array( $k, $val ) ? ' checked="checked"' : '';

			echo '<label for="'. $id .'">';
			   echo '<input type="checkbox" name="'. $name .'[]" id="'. $id .'" value="'. $k .'"'. $checked .' />';
			echo $v . '</label><br />';
		}
      echo '</fieldset>';

		if ( ! empty( $field['description'] ) ) echo '<p class="description">'. $field['description'] .'</p>';
	}

   public function sanitize_array( $r ) {
      if ( empty( $_POST['option_page'] ) ) return $r;

      // Ensure the input is an array
      if ( ! is_array( $r ) ) return $r;

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
