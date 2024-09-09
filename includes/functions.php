<?php

/**
 * Retrieves the current options/settings from the database.
 *
 * @param object $component The component object for which to fetch the options array.
 * @return array The current options for this component (with defaults in place if option is not set).
 */
if ( ! function_exists( 'ocd_get_options' ) ) {
	function ocd_get_options( $component ) {
		// Fetch options from the database if not already loaded.
		if ( empty( $component->options ) ) {
			$component->options = (array) get_option( $component->slug, array() );
		}

		return $component->options;
	}
}

/**
 * Retrieves the current options/settings from the database.
 *
 * @param object $component The component object for which to fetch the options array.
 * @return array The current options for this component (with defaults in place if option is not set).
 */
if ( ! function_exists( 'ocd_register_settings' ) ) {
	function ocd_register_settings( $config ) {
		if ( ! is_admin() ) return;

		// Add this component's config array to the main config array.
		add_filter( 'ocdutils_settings_config', function( $settings_config_r ) use ( $config ) { 
			$settings_config_r['components'][] = $config;
			return $settings_config_r;
		} );
	}
}

/**
 * Recursively searches parent directories for a package.json file and returns its content.
 *
 * @param string $dir The starting directory for the search.
 * @return string|false The content of package.json if found, otherwise false.
 */
if ( ! function_exists( 'ocd_find_package_json_in_parent_dirs' ) ) {
	function ocd_find_package_json_in_parent_dirs( $dir ) {
		// Ensure the directory path ends with a slash.
		$dir = trailingslashit( $dir );

		// Limit search to within wp plugins directory.
		if ( strpos( realpath( $dir ), realpath( WP_PLUGIN_DIR ) ) !== 0 ) {
			return false;
		}

		while ( true ) {
			// Build the path to package.json in the current directory.
			$package_json_path = $dir . 'package.json';

			// Check if the file is readable.
			if ( is_readable( $package_json_path ) ) {
				$json_content = file_get_contents( $package_json_path );
				if ( false !== $json_content ) {
					return $json_content;
				}
			}

			// Move up to the parent directory and normalize paths for comparison.
			$parent_dir = str_replace( '\\', '/', trailingslashit( dirname( $dir ) ) );
			$dir = str_replace( '\\', '/', trailingslashit( $dir ) );

			// Limit search to within wp plugins directory.
			if ( strpos( realpath( $parent_dir ), realpath( WP_PLUGIN_DIR ) ) !== 0 ) {
				break;
			}

			// Continue searching in the parent directory.
			$dir = trailingslashit( $parent_dir );
		}

		// Return false if package.json was not found.
		return false;
	}
}

/**
 * Retrieves the version of a Node.js dependency from package.json.
 *
 * @param string $package The name of the package to retrieve.
 * @param string|null $project_dir The directory where package.json resides. Defaults to plugin directory.
 * @return string|false The version of the package if found, otherwise false.
 */
if ( ! function_exists( 'ocd_nodejs_dependency_version' ) ) {
	function ocd_nodejs_dependency_version( $package, $project_dir = null ) {
		if ( empty( $package ) ) {
			return false;
		}

		// Attempt to get the version from cached transient.
		$transient_key = 'ocd_' . md5( $package ) . '_version';
		$package_version = get_transient( $transient_key );

		if ( false !== $package_version ) {
			return $package_version; // Return cached version if it exists.
		}

		$json_content = false;
		if ( null !== $project_dir ) {
			$json_content = file_get_contents( trailingslashit( $project_dir ) . 'package.json' );
		}

		// If no valid content was found in the provided directory, search up the directory tree.
		if ( false === $json_content ) {
			$project_dir = plugin_dir_path( __FILE__ );
			$json_content = ocd_find_package_json_in_parent_dirs( $project_dir );
		}

		// If package.json was not found, return false.
		if ( false === $json_content ) {
			return false;
		}

		// Decode the package.json content and handle errors.
		$package_data = json_decode( $json_content, true );
		if ( null === $package_data ) {
			error_log( 'JSON decode error: ' . json_last_error_msg() );
			return false;
		}

		// Check dependencies and devDependencies for the package version.
		if ( isset( $package_data['dependencies'][$package] ) ) {
			$package_version = $package_data['dependencies'][$package];
		} elseif ( isset( $package_data['devDependencies'][$package] ) ) {
			$package_version = $package_data['devDependencies'][$package];
		}

		// Cache the version in a transient if found.
		if ( ! empty( $package_version ) ) {
			set_transient( $transient_key, $package_version, 12 * HOUR_IN_SECONDS );
			return $package_version;
		}

		return false;
	}
}

?>
