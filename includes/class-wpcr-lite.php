<?php

namespace WPCRL;

function Log( $msg, $lvl ): void {
	if ( defined( 'WPCRL_LOG' ) and ( $lvl <= WPCRL_LOG ) ) {
		if ( is_array( $msg ) || is_object( $msg ) ) {
			error_log( "WPCRL::Log: " . print_r( $msg, true ) );
		} else {
			error_log( "WPCRL::Log: " . $msg );
		}
	}
}

if ( defined( 'WPCRL_URL' ) ) {
	class Core {
		private static ?Core $instance = null;
		private array $registry = array();

		private function __construct() {
			Log( "Core.__construct()", LOG_INFO );
			require_once( __DIR__ . '/class-wpcr-lite-updater.php' );
		}

		public static function get_instance(): Core {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function register_component( string $file ): void {
			Log( "Core.register_party(" . $file . ")", LOG_INFO );
			if ( ! array_key_exists( $file, $this->registry ) ) {
				$this->registry[ $file ] = new Updater( $file );
			} else {
				Log( "Already registered: " . $file, LOG_NOTICE );
			}
		}
	}
} else {
	Log( "WPCRL_URL not defined", LOG_WARNING );
}
