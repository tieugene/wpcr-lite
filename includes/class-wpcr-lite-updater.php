<?php

enum PartType {
	case Plugin;
	case Theme;
}

class WPCRL_Updater {
	private string $file;       // abs path
	private string $slug;       // short (dirname)
	private string $basename;   // rel slug/slug.php
	private object $party;      // ['Version'[, 'PluginURI', 'UpdateURI', ...]
	private PartType $ptype;
	private string $version;
	private string $suffix;     // remote repo dir; TODO: remove this

	//private bool $active;     // ? disable if not active ?
	public function __construct( $file ) {
		$this->file = $file;
		$this->slug = basename( dirname( $file ) );
		if ( str_starts_with( $file, WP_PLUGIN_DIR ) ) {
			$this->basename = plugin_basename( $this->file );
			$this->party    = (object) get_plugin_data( $this->file );
			$this->ptype    = PartType::Plugin;
			$this->suffix   = 'plugins';
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'on_plugins_api' ), 10, 3 );
		} elseif ( str_starts_with( $file, get_theme_root() ) ) {
			$this->basename = $this->slug;
			$this->party    = wp_get_theme( $this->slug );
			$this->ptype    = PartType::Theme;
			$this->suffix   = 'themes';
			add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_update' ) );
			add_filter( 'themes_api', array( $this, 'on_plugins_api' ), 10, 3 );
		} else {
			error_log( "It is something strange" );
		}
		$this->version = $this->party->Version;
		error_log( "WPCRL_Updater.__construct(" . $this->slug . " v. " . $this->version . "), " . $this->suffix );

		return $this;
	}

	public function check_update( $transient ) {
		// slot #1: pre_set_site_transient_update_plugins()
		// just checks whether plugin update available
		error_log( "WPCRL_Updater.check_update() for " . $this->slug );
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		// error_log( @json_encode( $transient, JSON_PRETTY_PRINT ) );
		$remote_meta = $this->get_remote_meta();
		if ( is_null( $remote_meta ) )
			return $transient;
		// If a newer version is available, add the update
		error_log( "Versions: this=" . $this->version . ", remote=" . $remote_meta->version );
		if ( version_compare( $this->version, $remote_meta->version, '<' ) ) {
			error_log( "Need update" );
			if ($this->ptype == PartType::Plugin)
				$party = 'plugin';
			else
				$party = 'theme';
			$response = array(  // TODO: id, url, requires, requires_php, tested
				'slug'        => $this->slug,
				$party        => $this->basename,
				'new_version' => $remote_meta->version,
				'package'     => $remote_meta->url
			);
			if ( $this->ptype == PartType::Plugin )  // obj 4 plugin, array 4 theme
				$response = (object) $response;
			$transient->response[ $this->basename ] = $response;
		}

		return $transient;
	}

	public function on_plugins_api( $obj, $action, $arg ) {
		// action=plugin_information, slug=cat-tiles
		error_log( "WPCRL_Updater.on_plugins_api(): action=" . $action . ", slug=" . $arg->slug );
		// error_log( @json_encode( $arg, JSON_PRETTY_PRINT ) );
		if ( ! empty( $args->slug ) && $arg->slug === $this->slug ) {
			$remote_meta = $this->get_remote_meta();
			if ( is_null( $remote_meta ) )
				return $obj;
			$response = array(
				'slug'          => $this->slug,
				'version'       => $remote_meta->version,  // or 'new_version' => ... ?
				'download_link' => $remote_meta->url,
				'name'          => $this->party->Name
				/*'last_updated'  => '2022-07-01',
				'requires'      => '4.0',
				'tested'        => '4.0',
				'downloaded'    => '16384',
				'sections'      => array(
					'Description' => $this->party->Description
				)*/
			);
			if ( $this->ptype == PartType::Plugin )
				$response = (object) $response;

			return $response;
		}

		return $obj;
	}

	private function get_remote_meta(): ?object {
		error_log( "WPCRL_Updater.get_remote_meta() for " . $this->slug );
		// TODO: join path
		$request = wp_remote_get( WPCRL_URL . $this->suffix . '/' . $this->slug . '.json' );

		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 )
			return @json_decode( $request['body'] );

		return null;  // TODO: return void
	}
}