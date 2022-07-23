<?php
/**
 * Plugin Name: WordPress Custom Repo Lite
 * Description: Allow to use custom WordPress plugins repository
 * Version: 0.0.1
 * Author: TI_Eugene <ti.eugene@gmail.com>
 * Author URI: https://www.eap.su
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wpcr-lite
 */

class WPCRL_Updater {
	private string $file;       // :str - abs path
	private array $plugin;      // :array - ['Version'[, 'PluginURI', 'UpdateURI', ...]
	private string $basename;   // :str - rel slug/slug.php
	private string $slug;
	private string $version;

	//private bool $active;     // ? disable if not active ?
	public function __construct( $file ) {
		error_log( "WPCRL_Updater started for " . $file );
		$this->file     = $file;
		$this->plugin   = get_plugin_data( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->slug     = dirname( $this->basename );  // or current(explode('/', $this->basename))
		$this->version  = $this->plugin['Version'];
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );
		add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );

		return $this;
	}

	public function check_update( $transient ) {
		// slot #1: pre_set_site_transient_update_plugins()
		// just checks whether plugin update available
		error_log( "check_update()" );
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		// Get the remote version
		$remote_meta = $this->get_remote_meta();
		// If a newer version is available, add the update
		error_log( "Versions: this=" . $this->version . ", remote=" . $remote_meta->version );
		if ( version_compare( $this->version, $remote_meta->version, '<' ) ) {
			error_log( "Need update" );
			$response                               = array(
				//'url' => $remote_meta->url,  // this->plugin["PluginURI"]
				'slug'        => $this->slug,  // short
				'package'     => $remote_meta->url,
				'new_version' => $remote_meta->version
			);
			$transient->response[ $this->basename ] = (object) $response;
		}

		return $transient;
	}

	public function check_info( $obj, $action, $arg ) {
		// slot for plugins_api
		error_log( "check_info()" );
		error_log( $action );
		if ( ! empty( $args->slug ) && $arg->slug === $this->slug ) {
			$remote_meta = $this->get_remote_meta();
			$response    = array(
				'slug'          => $this->slug,
				'version'       => $remote_meta->version,  // or 'new_version' => ... ?
				'download_link' => $remote_meta->url,
				'last_updated'  => '2022-07-01',
				'name'          => $this->plugin["Name"],
				'requires'      => '4.0',
				'tested'        => '4.0',
				'downloaded'    => '16384',
				'sections'      => array(
					'Description' => $this->plugin["Description"]
				)
			);

			return (object) $response;
		}

		return $obj;
	}

	private function get_remote_meta(): object|bool {
		error_log( "get_remote_meta()" );
		$request = wp_remote_get( WPCRL_URL . 'plugins/' . $this->slug . '.json' );

		// Check if response is valid
		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			//error_log(@json_encode($request));
			return @json_decode( $request['body'] );
		}

		return false;
	}
}
