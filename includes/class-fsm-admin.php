<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Admin {

    public static function init() : void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
    }

    public static function menu() : void {
        add_options_page(
            'Forme Smart Menu',
            'Forme Smart Menu',
            'manage_options',
            'forme-smart-menu',
            array( __CLASS__, 'page' )
        );
    }

    public static function register() : void {
        register_setting( 'fsm_settings', FSM_Settings::OPTION_KEY, array(
            'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
        ) );

        add_settings_section( 'fsm_main', 'Alap beállítások', function () {
            echo '<p>Headerbe tedd: <code>[forme_smart_menu_button]</code>. A menü panel automatikusan a footerbe kerül (nem szabad shortcode-olni).</p>';
        }, 'forme-smart-menu' );

        self::field_checkbox( 'disable_astra_menu', 'Astra menü kikapcsolása (Primary + Mobile)', 'Az Astra gyári menü nem fog megjelenni.' );
        self::field_text( 'button_label', 'Gomb felirat', 'Kategóriák' );
        self::field_checkbox( 'button_icon_only', 'Csak ikon (felirat nélkül)', 'Mobilon/ikon sávban szebb.' );
        self::field_text( 'primary_color', 'Fő szín (hex)', '#0b6ea8' );
        
        // Feature 1: Description toggle
        self::field_checkbox( 'show_descriptions', 'Főkategória leírások megjelenítése', 'Kategória név alatt megjelenik a leírás.' );
        
        // Feature 5: Child limits (mobile/desktop)
        self::field_number( 'child_limit_mobile', 'Alkategóriák száma (mobilon)', 6, 1, 24 );
        self::field_number( 'child_limit_desktop', 'Alkategóriák száma (PC-n)', 9, 1, 24 );
        
        // Feature 3: Grid columns
        self::field_number( 'grid_columns_mobile', 'Oszlopok száma (mobil)', 2, 1, 3 );
        self::field_number( 'grid_columns_desktop', 'Oszlopok száma (PC)', 3, 1, 4 );
        
        // Feature 4: More button colors
        self::field_text( 'more_button_bg_color', '"Még több" gomb háttérszín (hex)', 'transparent' );
        self::field_text( 'more_button_text_color', '"Még több" gomb betűszín (hex)', 'inherit' );

        self::field_select( 'drawer_side_mobile', 'Drawer iránya mobilon', array(
            'right' => 'Jobbról',
            'left'  => 'Balról',
        ), 'right' );

        self::field_select( 'drawer_side_desktop', 'Drawer iránya PC-n', array(
            'right' => 'Jobbról',
            'left'  => 'Balról',
        ), 'right' );

        add_settings_section( 'fsm_links', 'Drawer alján: információs linkek', function () {
            echo '<p>Itt tudsz a kategóriák alatt megjelenő oldallinkeket megadni (pl. Rólunk, Kapcsolat, GYIK). Egy sor = egy link. Formátum: <code>Felirat | URL</code>. Példa: <code>Rólunk | /rolunk/</code></p>';
        }, 'forme-smart-menu' );

        self::field_text( 'extra_links_title', 'Szekció címe', 'Információk', 'fsm_links' );
        self::field_textarea( 'extra_links', 'Linkek (soronként)', "Rólunk | /rolunk/\nKapcsolat | /kapcsolat/\nGYIK | /gyik/", 'fsm_links' );
    }

    public static function sanitize_options( $input ) : array {
        $out = array();
        $input = is_array( $input ) ? $input : array();

        $out['disable_astra_menu'] = ! empty( $input['disable_astra_menu'] ) ? 1 : 0;
        $out['button_icon_only']   = ! empty( $input['button_icon_only'] ) ? 1 : 0;
        $out['show_descriptions']  = ! empty( $input['show_descriptions'] ) ? 1 : 0;

        $out['button_label'] = isset( $input['button_label'] ) ? sanitize_text_field( $input['button_label'] ) : 'Kategóriák';

        $primary = isset( $input['primary_color'] ) ? trim( (string) $input['primary_color'] ) : '#0b6ea8';
        $out['primary_color'] = preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $primary ) ? $primary : '#0b6ea8';

        // Feature 5: Child limits (mobile/desktop)
        $limit_mobile = isset( $input['child_limit_mobile'] ) ? intval( $input['child_limit_mobile'] ) : 6;
        if ( $limit_mobile < 1 ) $limit_mobile = 1;
        if ( $limit_mobile > 24 ) $limit_mobile = 24;
        $out['child_limit_mobile'] = $limit_mobile;

        $limit_desktop = isset( $input['child_limit_desktop'] ) ? intval( $input['child_limit_desktop'] ) : 9;
        if ( $limit_desktop < 1 ) $limit_desktop = 1;
        if ( $limit_desktop > 24 ) $limit_desktop = 24;
        $out['child_limit_desktop'] = $limit_desktop;

        // Feature 3: Grid columns
        $grid_mobile = isset( $input['grid_columns_mobile'] ) ? intval( $input['grid_columns_mobile'] ) : 2;
        if ( $grid_mobile < 1 ) $grid_mobile = 1;
        if ( $grid_mobile > 3 ) $grid_mobile = 3;
        $out['grid_columns_mobile'] = $grid_mobile;

        $grid_desktop = isset( $input['grid_columns_desktop'] ) ? intval( $input['grid_columns_desktop'] ) : 3;
        if ( $grid_desktop < 1 ) $grid_desktop = 1;
        if ( $grid_desktop > 4 ) $grid_desktop = 4;
        $out['grid_columns_desktop'] = $grid_desktop;

        // Feature 4: More button colors
        $more_bg = isset( $input['more_button_bg_color'] ) ? trim( (string) $input['more_button_bg_color'] ) : 'transparent';
        $more_text = isset( $input['more_button_text_color'] ) ? trim( (string) $input['more_button_text_color'] ) : 'inherit';
        
        // Allow transparent and inherit keywords
        if ( $more_bg !== 'transparent' && ! preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $more_bg ) ) {
            $more_bg = 'transparent';
        }
        if ( $more_text !== 'inherit' && ! preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $more_text ) ) {
            $more_text = 'inherit';
        }
        $out['more_button_bg_color'] = $more_bg;
        $out['more_button_text_color'] = $more_text;

        $mobile  = isset( $input['drawer_side_mobile'] ) ? (string) $input['drawer_side_mobile'] : 'right';
        $desktop = isset( $input['drawer_side_desktop'] ) ? (string) $input['drawer_side_desktop'] : 'right';
        $out['drawer_side_mobile']  = ( $mobile === 'left' ) ? 'left' : 'right';
        $out['drawer_side_desktop'] = ( $desktop === 'left' ) ? 'left' : 'right';

        $out['extra_links_title'] = isset( $input['extra_links_title'] ) ? sanitize_text_field( $input['extra_links_title'] ) : 'Információk';

        // Keep raw textarea but strip tags; parsing happens at render.
        $links_raw = isset( $input['extra_links'] ) ? (string) $input['extra_links'] : '';
        $links_raw = wp_strip_all_tags( $links_raw );
        $out['extra_links'] = trim( $links_raw );

        // Clear menu cache when settings are saved
        if ( function_exists( 'fsm_clear_menu_cache' ) ) {
            fsm_clear_menu_cache();
        }

        return $out;
    }

    private static function field_checkbox( string $key, string $label, string $desc = '' ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $desc ) {
            $all = FSM_Settings::get_all();
            $val = ! empty( $all[ $key ] ) ? 1 : 0;
            echo '<label><input type="checkbox" name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="1" ' . checked( 1, $val, false ) . ' /> ';
            echo esc_html( $desc ?: $label );
            echo '</label>';
        }, 'forme-smart-menu', 'fsm_main' );
    }

    private static function field_text( string $key, string $label, string $placeholder = '', string $section = 'fsm_main' ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $placeholder ) {
            $all = FSM_Settings::get_all();
            $val = isset( $all[ $key ] ) ? (string) $all[ $key ] : '';
            echo '<input type="text" style="min-width:320px" name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
        }, 'forme-smart-menu', $section );
    }

    private static function field_textarea( string $key, string $label, string $placeholder = '', string $section = 'fsm_main' ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $placeholder ) {
            $all = FSM_Settings::get_all();
            $val = isset( $all[ $key ] ) ? (string) $all[ $key ] : '';
            echo '<textarea name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']" rows="6" style="width: min(720px, 100%);" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $val ) . '</textarea>';
        }, 'forme-smart-menu', $section );
    }

    private static function field_number( string $key, string $label, int $default, int $min, int $max ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $default, $min, $max ) {
            $all = FSM_Settings::get_all();
            $val = isset( $all[ $key ] ) ? intval( $all[ $key ] ) : $default;
            echo '<input type="number" name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" />';
        }, 'forme-smart-menu', 'fsm_main' );
    }

    private static function field_select( string $key, string $label, array $choices, string $default ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $choices, $default ) {
            $all = FSM_Settings::get_all();
            $val = isset( $all[ $key ] ) ? (string) $all[ $key ] : $default;
            echo '<select name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']">';
            foreach ( $choices as $k => $v ) {
                echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
            }
            echo '</select>';
        }, 'forme-smart-menu', 'fsm_main' );
    }

    public static function page() : void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Handle manual cache clear
        if ( isset( $_POST['fsm_clear_cache'] ) && check_admin_referer( 'fsm_clear_cache_action' ) ) {
            if ( function_exists( 'fsm_clear_menu_cache' ) ) {
                fsm_clear_menu_cache();
            }
            echo '<div class="notice notice-success"><p>Menü cache törölve!</p></div>';
        }

        echo '<div class="wrap"><h1>Forme Smart Menu</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'fsm_settings' );
        do_settings_sections( 'forme-smart-menu' );
        submit_button();
        echo '</form>';

        // Manual cache clear button
        echo '<hr style="margin: 30px 0;">';
        echo '<h2>Cache kezelés</h2>';
        echo '<p>Ha a menü nem frissül megfelelően, töröld a cache-t:</p>';
        echo '<form method="post">';
        wp_nonce_field( 'fsm_clear_cache_action' );
        echo '<button type="submit" name="fsm_clear_cache" class="button button-secondary">Menü cache törlése</button>';
        echo '</form>';

        echo '</div>';
    }
}
