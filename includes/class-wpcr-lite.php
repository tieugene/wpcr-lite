<?php

namespace WPCRL;

class Core {
	private static ?Core $instance = null;
	private array $registry = array();
	public static function get_instance() : Core
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	private function __construct() {
		error_log("WPCRL::Core.__construct()");
		require_once ( __DIR__ . '/class-wpcr-lite-updater.php');
	}
	public function register_component(string $file) : void {
		error_log("WPCRL::Core.register_party(" . $file . ")");
		if (!array_key_exists($file, $this->registry)) {
			$this->registry[$file] = new Updater($file);
		} else
			error_log("Already registered: " . $file);
	}
}
