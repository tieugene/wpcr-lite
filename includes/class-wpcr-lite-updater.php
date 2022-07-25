<?php

namespace WPCRL;

class ComponentType {
	public const Plugin = 1;
	public const Theme = 2;
}

class Updater {
	private string $file;       // abs path
	private string $slug;       // short (dirname)
	private string $basename;   // rel slug/slug.php
	private object $component;  // ['Version'[, 'PluginURI', 'UpdateURI', ...]
	private int $ctype;
	private string $version;
	private static array $suffix = array( ComponentType::Plugin => 'plugins', ComponentType::Theme => 'themes' );

	//private bool $active;     // ? disable if not active ?
	public function __construct( $file ) {
		$this->file = $file;
		$this->slug = basename( dirname( $file ) );
		Log( "Updater.__construct(" . $this->slug . ")", LOG_INFO);
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
			Log( "Unknown component: " . $file , LOG_ERR);
		}
		$this->version = $this->component->Version;
		Log( "It is " . self::$suffix[ $this->ctype ], LOG_DEBUG);

		return $this;
	}

	public function check_update( $transient ) {
		// slot #1: pre_set_site_transient_update_<component>s()
		// just checks whether component update available
		Log( "Updater.check_update(" . $this->slug . ")", LOG_INFO);
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		Log("Transient:", LOG_DEBUG);
		Log($transient, LOG_DEBUG);
		$remote_meta = $this->get_remote_meta();
		if ( is_null( $remote_meta ) ) {
			return $transient;
		}
		Log( "Versions: this=" . $this->version . ", remote=" . $remote_meta->version, LOG_INFO);
		if ( version_compare( $this->version, $remote_meta->version, '<' ) ) {
			Log( "Need update", LOG_INFO);
			if ( $this->ctype == ComponentType::Plugin ) {
				$response = (object) array(
					'id'           => $this->basename,
					'slug'         => $this->slug,
					'plugin'       => $this->basename,
					'new_version'  => $remote_meta->version,
					'url'          => '',
					'package'      => $remote_meta->url,
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
					'url'          => '',
					'package'      => $remote_meta->url,
					'requires'     => '',
					'requires_php' => ''
				);
			}
			// TODO: response or no_update?
			$transient->response[ $this->basename ] = $response;
		}

		return $transient;
	}

	public function on_component_api( $obj, $action, $arg ) {
		// plugin: action=plugin_information, slug=cat-tiles
		Log( "Updater.on_component_api(): action=" . $action . ", slug=" . $arg->slug, LOG_INFO);
		Log("Arg:", LOG_DEBUG);
		Log($arg, LOG_DEBUG);
		if ( ! empty( $args->slug ) && $arg->slug === $this->slug ) {
			$remote_meta = $this->get_remote_meta();
			if ( is_null( $remote_meta ) ) {
				return $obj;
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

		return $obj;
	}

	private function get_remote_meta(): ?object {
		Log( "Updater.get_remote_meta(" . $this->slug . ")", LOG_INFO);
		$url = path_join( path_join( WPCRL_URL, self::$suffix[ $this->ctype ] ), $this->slug . '.json' );
		Log("Get " . $url, LOG_DEBUG);
		$request = wp_remote_get( $url );

		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			$response= @json_decode( $request['body'] );
			Log("Response:", LOG_DEBUG);
			Log($response, LOG_DEBUG);
			return $response;
		} else
			Log("URL not available: " . $url, LOG_WARNING);

		return null;  // TODO: return void
	}
}