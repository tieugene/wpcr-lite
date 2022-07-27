<?php

namespace WPCRL;

/**
 * Updating component type
 */
class ComponentType {
	public const Plugin = 1;
	public const Theme = 2;
}

/**
 * Component updater - creates and handles required hooks for given component (plugin/theme)
 */
class Updater {
	/**
	 * @var string Full path to plugin main file or theme's 'function.php'
	 */
	private string $file;
	/**
	 * @var string Folder name of the component
	 */
	private string $slug;
	/**
	 * @var string 'Folder/main_file.php' for plugin / 'folder' for theme
	 */
	private string $basename;
	/**
	 * @var object Component local metadata
	 */
	private object $component;
	/**
	 * @var int Component type (plugin/theme)
	 */
	private int $ctype;
	/**
	 * @var string Current component version
	 */
	private string $version;

	//private bool $active;     // TODO: ? disable if not active ? (issue #4)

	/**
	 * @param string $file Full path to plugin main file or theme's 'function.php'
	 */
	public function __construct( string $file ) {
		$this->file = $file;
		$this->slug = basename( dirname( $file ) );
		Log( "Updater.__construct(" . $this->slug . ")", LOG_INFO );
		if ( str_starts_with( $file, WP_PLUGIN_DIR ) ) {
			$this->basename  = plugin_basename( $this->file );
			$this->component = (object) get_plugin_data( $this->file );
			$this->ctype     = ComponentType::Plugin;
			add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );
			add_filter( 'plugins_api', array( &$this, 'on_component_api' ), 10, 3 );
		} elseif ( str_starts_with( $file, get_theme_root() ) ) {
			$this->basename  = $this->slug;
			$this->component = wp_get_theme( $this->slug );
			$this->ctype     = ComponentType::Theme;
			add_filter( 'pre_set_site_transient_update_themes', array( &$this, 'check_update' ) );
			add_filter( 'themes_api', array( &$this, 'on_component_api' ), 10, 3 );
		} else {
			Log( "Unknown component: " . $file, LOG_ERR );

			return $this;
		}
		$this->version = $this->component->Version;
		Log( "It is " . $this->suffix(), LOG_DEBUG );

		return $this;
	}

	/**
	 * @return string URL suffix depending on component type
	 */
	private function suffix(): string {
		static $suffix_name = array( ComponentType::Plugin => 'plugins', ComponentType::Theme => 'themes' );

		return $suffix_name[ $this->ctype ];
	}

	/**
	 * Hooks 'pre_set_site_transient_update_plugins' / 'pre_set_site_transient_update_themes'
	 *
	 * @param mixed $transient Transient
	 *
	 * @return mixed Updated (or not) input transient
	 */
	public function check_update( mixed $transient ): mixed {
		// slot #1: pre_set_site_transient_update_<component>s()
		// just checks whether component update available
		Log( "Updater.check_update(" . $this->slug . ")", LOG_INFO );
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		Log( "Transient:", LOG_DEBUG );
		Log( $transient, LOG_DEBUG );
		$remote_meta = $this->get_remote_meta();
		if ( is_null( $remote_meta ) ) {
			return $transient;
		}
		Log( "Versions: this=" . $this->version . ", remote=" . $remote_meta->version, LOG_INFO );
		if ( version_compare( $this->version, $remote_meta->version, '<' ) ) {
			Log( "Need update: " . $this->slug . " " . $this->version . " => " . $remote_meta->version, LOG_NOTICE );
			if ( $this->ctype == ComponentType::Plugin ) {
				$response = (object) array(
					'id'           => $this->basename,
					'slug'         => $this->slug,
					'plugin'       => $this->basename,
					'new_version'  => $remote_meta->version,
					'package'      => $remote_meta->url,
					'url'          => '',
					'icons'        => array(),
					'banners'      => array(),
					'banners_rtl'  => array(),
					'tested'       => '',
					'requires_php' => '',
					//'compatibility' => new stdClass()
				);
			} else {
				$response = array(
					//'slug'        => $this->slug,
					'theme'        => $this->basename,
					'new_version'  => $remote_meta->version,
					'package'      => $remote_meta->url,
					'url'          => '',
					'requires'     => '',
					'requires_php' => ''
				);
			}
			// TODO: response or no_update?
			$transient->response[ $this->basename ] = $response;
		}

		return $transient;
	}

	/**
	 * Hooks 'plugins_api' / 'themes_api'
	 *
	 * @param bool $result Default result to return
	 * @param string $action What meta-info wanted
	 * @param object $arg Component meta-info from remote source
	 *
	 * @return bool|array|object Component update metadata (if success) or default (if not)
	 */
	public function on_component_api( bool $result, string $action, object $arg ): bool|array|object {
		// plugin: action=plugin_information, slug=cat-tiles
		Log( "Updater.on_component_api(): action=" . $action . ", slug=" . $arg->slug, LOG_INFO );
		Log( "Arg:", LOG_DEBUG );
		Log( $arg, LOG_DEBUG );
		if ( ! empty( $args->slug ) && $arg->slug === $this->slug ) {
			$remote_meta = $this->get_remote_meta();
			if ( is_null( $remote_meta ) ) {
				return $result;
			}
			$response = array(
				'slug'          => $this->slug,
				'version'       => $remote_meta->version,  // or 'new_version' => ... ?
				'download_link' => $remote_meta->url,
				'name'          => $this->component->Name
				/*'last_updated'  => '2022-07-01',
				'requires'      => '4.0',
				'tested'        => '4.0',
				'downloaded'    => '16384',
				'sections'      => array(
					'Description' => $this->party->Description
				)*/
			);
			if ( $this->ctype == ComponentType::Plugin ) {
				$response = (object) $response;
			}

			return $response;
		}

		return $result;
	}

	/**
	 * Get updating metadata from custom repo.
	 *
	 * @return object|null Updating metadata
	 */
	private function get_remote_meta(): ?object {  // TODO: object|void
		Log( "Updater.get_remote_meta(" . $this->slug . ")", LOG_INFO );
		// TODO: parametrized URL (issue #25)
		$url = path_join( path_join( WPCRL_URL, $this->suffix() ), $this->slug . '.json' );
		Log( "Get " . $url, LOG_DEBUG );
		$request = wp_remote_get( $url );

		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			$response = @json_decode( $request['body'] );
			Log( "Response:", LOG_DEBUG );
			Log( $response, LOG_DEBUG );
			if ( property_exists( $response, 'version' ) and property_exists( $response, 'url' ) ) {
				return $response;
			} else {
				Log( "Bad formed meta", LOG_WARNING );

				return null;
			}
		} else {
			Log( "URL for " . $this->slug . " oops: " . $url, LOG_NOTICE );
		}

		return null;
	}
}