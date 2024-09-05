<?php 
/*
	Plugin Name: OCD Utils
	Plugin URI: https://www.oakcreekdev.com/
	Description: Provides a collection of useful stuff for WordPress.
	Author: Jeremy Kozan
	Author URI: https://www.oakcreekdev.com/
	Requires at least: 5.1
	Tested up to: 5.9
	Stable tag: 1.0.1
	Version: 1.0.1
	Requires PHP: 7.1
	Text Domain: ocdutils
	Domain Path: /languages
	License: GPL v2 or later
*/

/*
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 
	2 of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	with this program. If not, visit: https://www.gnu.org/licenses/
	
	Copyright 2024 Oak Creek Development. All rights reserved.
*/

if ( ! defined( 'ABSPATH' ) ) die();

if ( ! class_exists( 'OCD_Utils' ) ) :
class OCD_Utils {
	private $options = array();
	
	public function __construct() {
		$this->constants();
		require_once( OCD_UTILS_DIR . 'includes/functions.php' );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	
	private function constants() {
		if ( ! defined( 'OCD_UTILS_NAME' ) ) define( 'OCD_UTILS_NAME', 'OCD Utils' );
		if ( ! defined( 'OCD_UTILS_DIR' ) ) define( 'OCD_UTILS_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		if ( ! defined( 'OCD_UTILS_URL' ) ) define( 'OCD_UTILS_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	}

	private function get_options() {
		if ( empty( $this->options ) ) $this->options = get_option( 'ocd_utils_settings', array() );
		return $this->options;
	}

	public function init() {
		$options = $this->get_options();

		$settings_config_r = array( 
			'page_slug' => 'ocdutils', 
			'capability' => 'manage_options', 
			'page_title' => OCD_UTILS_NAME .' '. esc_html( __( 'Settings', 'ocdutils' ) ), 
			'menu_title' => OCD_UTILS_NAME, 
			'options' => $options, 
			'components' => array(), 
		);

		$settings_config_r['components'][] = array(
			'slug' => 'ocd_utils_settings', 
			'label' => esc_html( __( 'General', 'ocdutils' ) ), 
			'sections' => array(), 
		);

		// *** SPECIAL SECTION to add component chooser -- NOT A NORMAL PART OF THE SETTINGS API
		array_unshift( $settings_config_r['components'][0]['sections'], array(
			'id' => 'active_components', 
			'label' => esc_html( __( 'Active Components', 'ocdutils' ) ), 
			'fields' => array(
				array(
					'id' => 'active_components', 
					'label' => esc_html( __( 'Components', 'ocdutils' ) ), 
					'description' => esc_html( __( 'Choose the ones you want to use.', 'ocdutils' ) ), 
					'type' => 'checkboxes', 
					'options' => array(
						// This is where you register all the components -- ONLY HERE
						// To add a component: 
						//  1. Create a file at ./components/my_slug/my_slug.php  NOTE: Only use underscores in filename (use ocd_example_component file as a starter)
						//  2. Add to this array e.g.: 'my_slug' => 'My Component Name',
						//  3. If the component needs to have an Admin settings page tab, add a settings config array in your component file (see ocd_example_component file)
						'example_component' => 'Example Component', 
						'upcoming_events_carousel' => 'Upcoming Events Carousel', 
						'divi_projects_portfolio' => 'Divi Projects Portfolio', 
					), 
				), 
			), 
		) );

		if ( ! empty( $options['active_components'] ) ) {
			foreach ( $options['active_components'] as $component ) {
				if ( 'example_component' === $component ) {
					if ( ! in_array( 'example_component', array_keys( $settings_config_r['components'][0]['sections'][0]['fields'][0]['options'] ) ) ) {
						continue;
					}
				}

				$file_path = OCD_UTILS_DIR . 'components/'. $component .'/'. $component .'.php';

				if ( is_readable( $file_path ) ) {
					require_once( $file_path );
				} else {
					add_action( 'admin_notices', function() use ( $component ) {
						echo '<div class="notice notice-error"><p>';
						echo esc_html( "OCD Utils: The component file for '$component' is missing or not readable." );
						echo '</p></div>';
					});
				}
			}
		}

		if ( is_admin() ) {
			// Allow components to modify the settings config via this filter
			$settings_config_r = apply_filters( 'ocdutils_settings_config', $settings_config_r );

			require_once( OCD_UTILS_DIR . 'includes/class-ocd-settings.php' );
			$OCD_AdminSettings = new OCD_AdminSettings( $settings_config_r );
		}
	}

	public function admin_settings() {
		
	}
	
}
$OCD_Utils = new OCD_Utils();
endif;
?>
