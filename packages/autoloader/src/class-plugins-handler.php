<?php
/* HEADER */ // phpcs:ignore

/**
 * This class provides information about the current plugin and the site's active plugins.
 */
class Plugins_Handler {

	/**
	 * Returns an array containing the active plugins. If any plugins are activating,
	 * they are included in the array.
	 *
	 * @return Array An array of plugin names as strings.
	 */
	public function get_active_plugins() {
		$active_plugins = array_merge(
			is_multisite()
				? array_keys( get_site_option( 'active_sitewide_plugins', array() ) )
				: array(),
			(array) get_option( 'active_plugins', array() )
		);
		$current_plugin = $this->get_current_plugin();

		if ( ! in_array( $current_plugin, $active_plugins, true ) ) {
			// The current plugin isn't active, so it must be activating. Add it to the list.
			$active_plugins[] = $current_plugin;
		}

		// If the activating plugin is not the only activating plugin, we need to add others too.
		$active_plugins = array_unique( array_merge( $active_plugins, $this->get_activating_plugins() ) );

		return $active_plugins;
	}

	/**
	 * Creates an array containing the paths to the classmap and filemap for the given plugin.
	 * The classmap and filemap filenames are the names of the files generated by Jetpack
	 * Autoloader with versions >=2.0.
	 *
	 * @param String $plugin The plugin string.
	 * @return Array An array containing the paths to the plugin's classmap and filemap.
	 */
	public function create_map_path_array( $plugin ) {
		$plugin_path = plugin_dir_path( trailingslashit( WP_PLUGIN_DIR ) . $plugin );

		return array(
			'class' => trailingslashit( $plugin_path ) . 'vendor/composer/jetpack_autoload_classmap.php',
			'file'  => trailingslashit( $plugin_path ) . 'vendor/composer/jetpack_autoload_filemap.php',
		);
	}

	/**
	 * Returns an array containing the paths to the classmap and filemap for the active plugins.
	 */
	public function get_active_plugins_paths() {
		$active_plugins = $this->get_active_plugins();
		return array_map( array( $this, 'create_map_path_array' ), $active_plugins );
	}

	/**
	 * Checks whether the current plugin is active.
	 *
	 * @return Boolean True if the current plugin is active, else False.
	 */
	public function is_current_plugin_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$current_plugin = $this->get_current_plugin();

		return in_array( $current_plugin, $active_plugins, true );
	}

	/**
	 * Returns the names of activating plugins if the plugins are activating via a request.
	 *
	 * @return Array The array of the activating plugins or empty array.
	 */
	public function get_activating_plugins() {

		 // phpcs:disable WordPress.Security.NonceVerification.Recommended

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : false;
		$nonce  = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : false;

		/**
		 * Note: we're not actually checking the nonce here becase it's too early
		 * in the execution. The pluggable functions are not yet loaded to give
		 * plugins a chance to plug their versions. Therefore we're doing the bare
		 * minimum: checking whether the nonce exists and it's in the right place.
		 * The request will fail later if the nonce doesn't pass the check.
		 */

		// In case of a single plugin activation there will be a plugin slug.
		if ( 'activate' === $action && ! empty( $nonce ) ) {
			return array( wp_unslash( $plugin ) );
		}

		$plugins = isset( $_REQUEST['checked'] ) ? $_REQUEST['checked'] : array();

		// In case of bulk activation there will be an array of plugins.
		if ( 'activate-selected' === $action && ! empty( $nonce ) ) {
			return array_map( 'wp_unslash', $plugins );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return array();

	}

	/**
	 * Returns the name of the current plugin.
	 *
	 * @return String The name of the current plugin.
	 */
	public function get_current_plugin() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$dir  = explode( '/', plugin_basename( __FILE__ ) )[0];
		$file = array_keys( get_plugins( "/$dir" ) )[0];
		return "$dir/$file";
	}
}
