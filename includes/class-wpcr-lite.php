<?php

namespace WPCRL;

/**
 * Logger
 *
 * @param array|object|string $msg Log message
 * @param int $lvl Logging level (built-in PHP's)
 *
 * @return void
 */
function Log( array|object|string $msg, int $lvl ): void {
	if ( defined( 'WPCRL_LOG' ) and ( $lvl <= WPCRL_LOG ) ) {
		if ( is_array( $msg ) || is_object( $msg ) ) {
			error_log( "WPCRL::Log: " . print_r( $msg, true ) );
		} else {
			error_log( "WPCRL::Log: " . $msg );
		}
	}
}

if ( defined( 'WPCRL_URL' ) ) {  // no constant - no job

	/**
	 * Singleton to register custom component updaters.
	 */
	class Core {
		/**
		 * @var Core|null Singleton itself
		 */
		private static ?Core $instance = null;
		/**
		 * @var array Already registered components
		 */
		private array $registry = array();

		private function __construct() {  // just for inner usage
			Log( "Core.__construct()", LOG_INFO );
			require_once( __DIR__ . '/class-wpcr-lite-updater.php' );
		}

		/**
		 * @return Core Single instance
		 */
		public static function get_instance(): Core {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Creates new Updater for given component if it is not exists.
		 *
		 * @param string $file Full path to plugin main file or theme's 'function.php'
		 *
		 * @return void
		 */
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
