<?php 
/*
	Plugin Name: OCD Utils
	Plugin URI: https://www.oakcreekdev.com/
	Description: Provides a collection of useful stuff for WordPress.
	Author: Oak Creek Development
	Author URI: https://www.oakcreekdev.com/
	Requires at least: 6.0
	Tested up to: 6.6.1
	Stable tag: 1.0.1
	Version: 1.0.1
	Requires PHP: 7.4
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

/**
 * Class OCD_Utils
 *
 * Main plugin class for OCD Utils.
 */
if ( ! class_exists( 'OCD_Utils' ) ) :

class OCD_Utils {
	/**
	 * Holds the slug of the component (the base name of the PHP file).
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Holds the plugin options from the database.
	 *
	 * @var array
	 */
	public $options = array();
	
	/**
	 * Constructor.
	 * Initializes constants and loads the main plugin components.
	 */
	public function __construct() {
		$this->slug = basename( __FILE__, '.php' ); // Set the slug to the base filename (without .php extension)
		$this->define_constants();
		require_once( OCD_UTILS_DIR . 'includes/functions.php' );

		add_action( 'init', array( $this, 'config' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}
	
	/**
	 * Adds a link to the settings page on the plugins page.
	 */
	public function add_settings_link( $links ) {
		// Define the URL for the settings page
		$settings_link = '<a href="'. OCD_UTILS_SETTINGS_PAGE_LINK .'">'. __( 'Settings', 'ocdutils' ) .'</a>';

		// Add the settings link to the beginning of the list
		array_unshift( $links, $settings_link );

		return $links;
	}
	
	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		if ( ! defined( 'OCD_UTILS_NAME'               ) ) define( 'OCD_UTILS_NAME',               'OCD Utils'                                    );
		if ( ! defined( 'OCD_UTILS_DIR'                ) ) define( 'OCD_UTILS_DIR',                trailingslashit( plugin_dir_path( __FILE__ ) ) );
		if ( ! defined( 'OCD_UTILS_URL'                ) ) define( 'OCD_UTILS_URL',                trailingslashit( plugin_dir_url( __FILE__ )  ) );
		if ( ! defined( 'OCD_UTILS_SETTINGS_PAGE_LINK' ) ) define( 'OCD_UTILS_SETTINGS_PAGE_LINK', 'options-general.php?page=ocdutils'            );
	}

	/**
	 * Setup the plugin components and settings configs.
	 */
	public function config() {
		$options = ocd_get_options( $this );

		// Configuration array for plugin settings
		$settings_config_r = array( 
			'page_slug' => 'ocdutils', 
			'capability' => 'manage_options', 
			'page_title' => OCD_UTILS_NAME .' '. esc_html( __( 'Settings', 'ocdutils' ) ), 
			'menu_title' => OCD_UTILS_NAME, 
			'options' => $options, 
			'components' => array(), 
		);

		// Add the component config for this self and main general admin settings page tab
		$settings_config_r['components'][] = array(
			'slug' => $this->slug,
			'label' => esc_html( __( 'General', 'ocdutils' ) ), 
			'sections' => array(
				array(
					'id' => 'utils',
					'label' => esc_html( __( 'Utilities', 'ocdutils' ) ), 
					'fields' => array(
						array(
							'id' => 'utilities_active', 
							'label' => esc_html( __( 'Choose Utilities', 'ocdutils' ) ), 
							'description' => esc_html( __( 'Select the utilities that will be active on this website.', 'ocdutils' ) ), 
							'type' => 'checkboxes', 
							'options' => array(
								'frontend-link' => esc_html( __( 'Show slug and "Frontend" link (for Divi) in Admin list.', 'ocdutils' ) ), 
								'subpages-list' => esc_html( __( 'Show a list of sub-pages if the page content is empty.', 'ocdutils' ) ), 
							), 
							
						), 
					), 
				)
			), 
		);

		// *** SPECIAL SECTION to add component chooser -- NOT A NORMAL PART OF THE SETTINGS API
		array_unshift( $settings_config_r['components'][0]['sections'], array(
			'id' => 'active_components', 
			'label' => esc_html( __( 'Active Components', 'ocdutils' ) ), 
			'fields' => array(
				array(
					'id' => 'components_active', 
					'label' => esc_html( __( 'Choose Components', 'ocdutils' ) ), 
					'description' => esc_html( __( 'Select the components that will be in use on this website.', 'ocdutils' ) ), 
					'type' => 'checkboxes', 

					// This is where you register all the components ***** ONLY HERE
					// To add a component: 
					//  1. Create a file at: ./components/ocd-my-slug/ocd-my-slug.php  ***** IMPORTANT: Don't forget to use the "ocd-" prefix for folder and file names
					//                        you can copy/paste the ocd-example-component folder then change the names
					//  2. Add it to this array e.g.: 'ocd-my-slug' => 'My Component Name',
					//  3. If the component needs to have an Admin settings page tab, add a settings config array in your component file (see ocd-example-component file)
					'options' => array(
						//'ocd-example-component' => 'Example Component',  // Uncomment this only to see the example. Don't leave it uncommented in production. // DO NOT REMOVE
						'ocd-simple-search-form' => 'Simple Search Form', 
						'ocd-upcoming-events-carousel' => 'Upcoming Events Carousel', 
						'ocd-filter-portfolio' => 'Filterable Portfolio with Modals', 
					), 
				), 
			), 
		) );

		// Load component files based on saved settings or POSTed form data
		if ( ! empty( $options['components_active'] ) || isset( $_POST[$this->slug]['components_active'] ) ) {
			// This code runs before the setting is updated in the database, 
			// so we want to manually add this to the $options array so the chosen component files will be loaded now instead of waititng until the next page load.
			$options['components_active'] = $options['components_active'] ?? array();
			if ( isset( $_POST[$this->slug]['components_active'] ) ) {
				$options['components_active'] = array_merge( $options['components_active'], (array) $_POST[$this->slug]['components_active'] );
			}

			foreach ( $options['components_active'] as $component ) {
				// Check if the example component is commented out above or not. Only want to show the example component when it is deliberately uncommented in this file.
				if ( 'ocd-example-component' === $component && ! array_key_exists( 'ocd-example-component', $settings_config_r['components'][0]['sections'][0]['fields'][0]['options'] ) ) {
					continue;
				}

				$file_path = OCD_UTILS_DIR . 'components/'. $component .'/'. $component .'.php';

				if ( is_readable( $file_path ) ) {
					require_once( $file_path );
				} else {
					// Display an admin notice if the component file is missing
					add_action( 'admin_notices', function() use ( $component ) {
						printf(
							'<div class="notice notice-error"><p>%s</p></div>',
							esc_html( sprintf( __( "%s: The component file for '%s' is missing or not readable.", 'ocdutils' ), OCD_UTILS_NAME, $component ) )
						);
					} );
				}
			}
		}

		if ( ! empty( $options['utilities_active'] ) ) {
			foreach ( $options['utilities_active'] as $utilitiy ) {
				$file_path = OCD_UTILS_DIR . 'utilities/'. $utilitiy .'/'. $utilitiy .'.php';

				if ( is_readable( $file_path ) ) {
					require_once( $file_path );
				} else {
					// Display an admin notice if the utility file is missing
					add_action( 'admin_notices', function() use ( $utilitiy ) {
						printf(
							'<div class="notice notice-error"><p>%s</p></div>',
							esc_html( sprintf( __( "%s: The utility file for '%s' is missing or not readable.", 'ocdutils' ), OCD_UTILS_NAME, $utilitiy ) )
						);
					} );
				}
			}
		}

		if ( is_admin() ) {
			// Allow components to modify the settings config via this filter
			$settings_config_r = apply_filters( 'ocdutils_settings_config', $settings_config_r );

			// Adding/updating component options in the database based on which ones are selected to be active
			if ( isset( $_POST[$this->slug]['components_active'] ) && isset( $settings_config_r['components'] ) && is_array( $settings_config_r['components'] ) ) {
				foreach ( $settings_config_r['components'] as $component ) {

					$component_defaults = array();

					if ( isset( $component['sections'] ) && is_array( $component['sections'] ) ) {
						foreach ( $component['sections'] as $section ) {
							if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
								foreach ( $section['fields'] as $field ) {
									if ( isset( $field['default'], $field['id'] ) ) {
										if ( 'checkboxes' == $field['type'] ) {
											$field['default'] = (array) $field['default'];
										}

										$component_defaults[$field['id']] = $field['default'];
									}
								}
							}
						}
					}

					// If a component is newly activated (its settings array doesn't yet exist in the options table), add its default settings to the options table
					if ( ! empty( $component_defaults ) ) {
						if ( $component['slug'] === 'ocd-example-component' ) {
							add_option( $component['slug'], $component_defaults, null, false ); // Set fourth param to false so this one isn't autoloaded
						} else {
							add_option( $component['slug'], $component_defaults ); // Let wordpress determine if it should be autoloaded.
						}
					}

				}
			}

			// Load the admin settings handler
			require_once( OCD_UTILS_DIR . 'includes/class-ocd-settings.php' );
			$OCD_AdminSettings = new OCD_AdminSettings( $settings_config_r );
		}
	}
}

// Instantiate the main class.
$OCD_Utils = new OCD_Utils();

endif;

?>
