<?php

class WPCRL_Admin {
	public function __construct() {
		error_log("WPCRL_Admin.__construct()");
		add_action( 'admin_menu', array($this, 'self_add_options_page') );
	}
	public function self_add_options_page() : void {
		error_log("WPCRL_Admin.self_add_options_page()");
		add_options_page(
			'WPCRL settings',  // sittings page <title>
			'WPCRL',  // admin menu title (wp-admin -> settings -> ...)
			'manage_options',
			'wpcrl-settings',
			array ($this, 'settings_page')
		);
	}
	public function settings_page() {
		error_log("WPCRL_Admin.settings_page()");
		echo "<h1>Hello World</h1>";
	}
}
