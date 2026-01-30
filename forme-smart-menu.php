<?php
/**
 * Plugin Name: Forme Smart Menu
 * Description: Headerben gomb, kattintásra jobbról beúszó (off-canvas) WooCommerce kategória menü. Shortcode: [forme_smart_menu_button]
 * Version: 0.3.4
 * Author: Forme
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: forme-smart-menu
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'FSM_VERSION', '0.3.4' );
define( 'FSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'FSM_URL',  plugin_dir_url( __FILE__ ) );

require_once FSM_PATH . 'includes/class-fsm-settings.php';
require_once FSM_PATH . 'includes/class-fsm-renderer.php';
require_once FSM_PATH . 'includes/class-fsm-admin.php';

add_action( 'init', function () {
    add_shortcode( 'forme_smart_menu_button', array( 'FSM_Renderer', 'shortcode_button' ) );
} );

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

FSM_Admin::init();

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
