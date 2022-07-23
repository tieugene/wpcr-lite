<?php
/**
 * @wordpress-plugin
 * Plugin Name: WordPress Custom Repo Lite
 * Plugin URI:  https://github.com/tieugene/wpcr-lite/issues/5
 * Description: Allow to use custom WordPress plugins repository
 * Version:     0.0.1
 * Author:      TI_Eugene <ti.eugene@gmail.com>
 * Author URI:  https://www.eap.su
 * License:     GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: languages/
 */

require plugin_dir_path( __FILE__ ) . 'includes/class-wpcr-lite.php';
function run_wpcr_lite() : void{

	$plugin = new WPCRL_Core();

}
run_wpcr_lite();
