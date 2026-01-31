<?php
/**
 * Plugin Name: Forme Smart Menu
 * Description: Headerben gomb, kattintásra jobbról beúszó (off-canvas) WooCommerce kategória menü. Shortcode: [forme_smart_menu_button]
 * Version: 0.5.0
 * Author: Forme
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: forme-smart-menu
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'FSM_VERSION', '0.5.0' );
define( 'FSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'FSM_URL',  plugin_dir_url( __FILE__ ) );

require_once FSM_PATH . 'includes/class-fsm-settings.php';
require_once FSM_PATH . 'includes/class-fsm-renderer.php';
require_once FSM_PATH . 'includes/class-fsm-admin.php';
require_once FSM_PATH . 'includes/class-fsm-category-meta.php';

add_action( 'init', function () {
    add_shortcode( 'forme_smart_menu_button', array( 'FSM_Renderer', 'shortcode_button' ) );
} );

FSM_Admin::init();
FSM_Category_Meta::init();

add_action( 'wp_enqueue_scripts', function () {
    wp_register_style( 'fsm-menu', FSM_URL . 'assets/css/menu.css', array(), FSM_VERSION );
    wp_register_script( 'fsm-menu', FSM_URL . 'assets/js/menu.js', array(), FSM_VERSION, true );

    wp_enqueue_style( 'fsm-menu' );
    // Set primary color as CSS variable
    $primary = FSM_Settings::get_string( 'primary_color', '#0b6ea8' );
    if ( ! preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $primary ) ) { $primary = '#0b6ea8'; }
    wp_add_inline_style( 'fsm-menu', ':root{--fsm-primary:' . $primary . ';}' );
    wp_enqueue_script( 'fsm-menu' );
} );

// Body classes for drawer side (mobile/desktop)
add_filter( 'body_class', function ( $classes ) {
    $mobile  = FSM_Settings::get_string( 'drawer_side_mobile', 'right' );
    $desktop = FSM_Settings::get_string( 'drawer_side_desktop', 'right' );
    $mobile  = ( $mobile === 'left' ) ? 'left' : 'right';
    $desktop = ( $desktop === 'left' ) ? 'left' : 'right';
    $classes[] = 'fsm-mobile-' . $mobile;
    $classes[] = 'fsm-desktop-' . $desktop;
    return $classes;
}, 20 );

// Optional Astra integration (menu OFF)
add_action( 'after_setup_theme', function () {
    if ( ! FSM_Settings::get_bool( 'disable_astra_menu', false ) ) {
        return;
    }
    add_filter( 'astra_primary_menu_disable', '__return_true' );
    add_filter( 'astra_mobile_menu_disable', '__return_true' );
}, 20 );

// Render drawer once in footer
add_action( 'wp_footer', function () {
    echo FSM_Renderer::render_drawer_once();
}, 20 );

// Clear menu cache on plugin upgrade
add_action( 'upgrader_process_complete', function ( $upgrader, $options ) {
    if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
        fsm_clear_menu_cache();
    }
}, 10, 2 );

// Clear cache helper function
function fsm_clear_menu_cache() {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fsm_drawer_%' OR option_name LIKE '_transient_timeout_fsm_drawer_%'" );
}

// Clear cache on plugin activation
register_activation_hook( __FILE__, 'fsm_clear_menu_cache' );
