<?php
/**
 * Plugin Name:     Wp Attachments Uploader
 * Plugin URI:      https://www.giuseppesurace.com
 * Description:     Useful metabox to upload each kind of attachments to your post and pages and custom post types.
 * Author:          bulini
 * Author URI:      https://www.giuseppesurace.com
 * Text Domain:     wp-attachments-uploader
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wp_Attachments_Uploader
 */


define('UPLOADER_ABSOLUTE_URL', plugin_dir_url( __FILE__ ));

load_plugin_textdomain('wp-attachments-uploader', false, basename( dirname( __FILE__ ) ) . '/languages' );

require_once('lib/class-multiple-uploader.php');
