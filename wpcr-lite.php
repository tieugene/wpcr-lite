<?php
/**
 * @wordpress-plugin
 * Plugin Name: WordPress Custom Repo Lite
 * Plugin URI:  https://github.com/tieugene/wpcr-lite/
 * Description: Allow to use custom WordPress plugins repository
 * Version:     0.0.1
 * Author:      TI_Eugene <ti.eugene@gmail.com>
 * Author URI:  https://www.eap.su
 * License:     GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: languages/
 */

if (defined('WPCRL_URL'))
	require plugin_dir_path( __FILE__ ) . 'includes/class-wpcr-lite.php';
