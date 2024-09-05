<?php

if ( ! function_exists( 'ocd_find_package_json_in_parent_dirs' ) ) {
	function ocd_find_package_json_in_parent_dirs( $dir ) {
		$dir = trailingslashit( $dir );

		while ( true ) {
			// Try to read package.json in the current directory
			$package_json_path = $dir . 'package.json';

			if ( is_readable( $package_json_path ) ) {
				$json_content = file_get_contents( $package_json_path );
				if ( false !== $json_content ) return $json_content; // Successfully read the package.json file
			}

			// If we are at the filesystem root, stop
			$parent_dir = dirname( $dir );

			// Normalize both directories to use forward slashes for comparison
			$dir = str_replace( '\\', '/', trailingslashit( $dir ) );
			$parent_dir = str_replace( '\\', '/', trailingslashit( $parent_dir ) );

			if ( $parent_dir === $dir ) break; // We've reached the root directory

			// Move up to the parent directory
			$dir = trailingslashit( $parent_dir );
		}

		// If no package.json was found, return false
		return false;
	}
}

if ( ! function_exists( 'ocd_nodejs_dependency_version' ) ) {
	function ocd_nodejs_dependency_version( $package, $project_dir = null ) {
		if ( empty( $package ) ) return false;

		// Try to get the cached version from a transient
		$transient_key = 'lmg_' . md5( $package ) . '_version';
		$package_version = get_transient( $transient_key );

		if ( false !== $package_version ) {
			// Return cached version if it exists
			return $package_version;
		}

		$json_content = false;
		if ( null !== $project_dir ) {
			$json_content = file_get_contents( trailingslashit( $project_dir ) . 'package.json' );
		}

		if ( false === $json_content ){
			$project_dir = plugin_dir_path( __FILE__ );
			$json_content = ocd_find_package_json_in_parent_dirs( $project_dir );
		}

		if ( false === $json_content ) return false;

		$package_data = json_decode( $json_content, true );
		if ( null === $package_data ) {
			error_log( 'JSON decode error: ' . json_last_error_msg() );
			return false;
		}

		// Check dependencies and devDependencies
		if ( isset( $package_data['dependencies'][$package] ) ) {
			$package_version = $package_data['dependencies'][$package];
		} elseif ( isset( $package_data['devDependencies'][$package] ) ) {
			$package_version = $package_data['devDependencies'][$package];
		}

		if ( ! empty( $package_version ) ) {
			// Cache the version in a transient for 12 hours
			set_transient( $transient_key, $package_version, 12 * HOUR_IN_SECONDS );
			return $package_version;
		}

		return false;
	}
}

?>
