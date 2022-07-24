<?php

class WPCRL_Core {
	private static ?WPCRL_Core $instance = null;
	private array $registry = array();
	public static function get_instance() : WPCRL_Core
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	private function __construct() {
		error_log("WPCRL_Core.__construct()");
		// TODO: replace with path construct
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpcr-lite-updater.php';
	}
	public function register_plugin(string $file) : void {
		error_log("WPCRL_Core.register_plugin()");
		// self::get_instance();
		if (!array_key_exists($file, $this->registry)) {
			$this->registry[$file] = new WPCRL_Updater($file);
		} else
			error_log("Already registered: " . $file);
	}
}
