<?php

class WPCRL_Core {
	public function __construct() {
		error_log("WPCRL_Core.__construct()");
		$this->load_dependencies();
		$plugin_admin = new WPCRL_Admin();
	}
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpcr-lite-updater.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpcr-lite-admin.php';
	}
}
