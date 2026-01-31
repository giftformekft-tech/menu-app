<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Admin {

    public static function init() : void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
        self::register_ajax();
    }

    public static function menu() : void {
        add_menu_page(
            'Forme Smart Menu',
            'Forme Menu',
            'manage_options',
            'forme-smart-menu',
            array( __CLASS__, 'page' ),
            'dashicons-menu-alt',
            58
        );
        
        // Rename main submenu
        add_submenu_page(
            'forme-smart-menu',
            'Beállítások',
            'Beállítások',
            'manage_options',
            'forme-smart-menu',
            array( __CLASS__, 'page' )
        );

        // Add custom sections submenu
        $hook = add_submenu_page(
            'forme-smart-menu',
            'Kiemelt szekciók',
            'Kiemelt szekciók',
            'manage_options',
            'fsm-custom-sections',
            array( __CLASS__, 'page_custom_sections' )
        );

        add_action( 'load-' . $hook, array( __CLASS__, 'enqueue_custom_section_assets' ) );
    }

    public static function enqueue_custom_section_assets() : void {
        add_action( 'admin_enqueue_scripts', function() {
            wp_enqueue_style( 'fsm-admin-css', FSM_URL . 'assets/css/admin-custom-sections.css', array(), FSM_VERSION );
            
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'fsm-admin-js', FSM_URL . 'assets/js/admin-custom-sections.js', array( 'jquery', 'jquery-ui-sortable' ), FSM_VERSION, true );
            
            wp_localize_script( 'fsm-admin-js', 'fsmAdmin', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'fsm_custom_sections_nonce' ),
                'confirmDelete' => 'Biztosan törölni szeretnéd ezt a szekciót?',
            ) );
        } );
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

        // New: Main Category Appearance
        add_settings_section( 'fsm_main_category_style', 'Főkategória megjelenés', function () {
            echo '<p>Testre szabhatod a főkategória gombok megjelenését (színek, méretek, tipográfia).</p>';
        }, 'forme-smart-menu' );

        self::field_text( 'main_cat_bg_color', 'Háttérszín (hex)', '#0b6ea8', 'fsm_main_category_style' );
        self::field_text( 'main_cat_text_color', 'Szövegszín (hex)', '#ffffff', 'fsm_main_category_style' );
        self::field_text( 'main_cat_icon_bg_color', 'Ikon háttérszín (hex)', 'rgba(255,255,255,0.22)', 'fsm_main_category_style' );
        self::field_text( 'main_cat_icon_text_color', 'Ikon szövegszín (hex)', '#ffffff', 'fsm_main_category_style' );
        self::field_text( 'main_cat_hover_bg_color', 'Hover háttérszín (hex)', 'rgba(0,0,0,0.1)', 'fsm_main_category_style' );
        self::field_text( 'main_cat_hover_text_color', 'Hover szövegszín (hex)', 'inherit', 'fsm_main_category_style' );
        self::field_text( 'main_cat_active_bg_color', 'Kijelölt háttérszín (hex)', '#0b6ea8', 'fsm_main_category_style' );
        self::field_text( 'main_cat_active_text_color', 'Kijelölt szövegszín (hex)', '#ffffff', 'fsm_main_category_style' );
        
        self::field_number_custom( 'main_cat_border_radius', 'Lekerekítés (px)', 14, 0, 30, 'fsm_main_category_style' );
        self::field_number_custom( 'main_cat_padding_v', 'Padding függőleges (px)', 8, 4, 20, 'fsm_main_category_style' );
        self::field_number_custom( 'main_cat_padding_h', 'Padding vízszintes (px)', 14, 4, 30, 'fsm_main_category_style' );
        self::field_number_custom( 'main_cat_icon_size', 'Ikon méret (px)', 36, 24, 48, 'fsm_main_category_style' );
        self::field_number_custom( 'main_cat_icon_radius', 'Ikon lekerekítés (px)', 12, 0, 24, 'fsm_main_category_style' );
        
        self::field_number_custom( 'main_cat_font_size', 'Betűméret (px)', 18, 14, 24, 'fsm_main_category_style' );
        self::field_select_custom( 'main_cat_font_weight', 'Betűvastagság', array(
            '400' => 'Normal (400)',
            '500' => 'Medium (500)',
            '600' => 'Semibold (600)',
            '700' => 'Bold (700)',
            '800' => 'Extra Bold (800)',
            '900' => 'Black (900)',
        ), '900', 'fsm_main_category_style' );

        // New: Subcategory Appearance
        add_settings_section( 'fsm_sub_category_style', 'Alkategória megjelenés', function () {
            echo '<p>Testre szabhatod az alkategória kártyák megjelenését (színek, méretek, tipográfia).</p>';
        }, 'forme-smart-menu' );

        self::field_text( 'chip_bg_color', 'Háttérszín (hex)', '#ffffff', 'fsm_sub_category_style' );
        self::field_text( 'chip_text_color', 'Szövegszín (hex)', 'inherit', 'fsm_sub_category_style' );
        self::field_text( 'chip_border_color', 'Border szín (hex)', 'rgba(0,0,0,0.12)', 'fsm_sub_category_style' );
        self::field_text( 'chip_hover_bg_color', 'Hover háttérszín (hex)', 'rgba(11,110,168,0.06)', 'fsm_sub_category_style' );
        self::field_text( 'chip_hover_border_color', 'Hover border szín (hex)', '#0b6ea8', 'fsm_sub_category_style' );
        
        self::field_number_custom( 'chip_border_radius', 'Lekerekítés (px)', 14, 0, 20, 'fsm_sub_category_style' );
        self::field_number_custom( 'chip_padding_v', 'Padding függőleges (px)', 4, 2, 16, 'fsm_sub_category_style' );
        self::field_number_custom( 'chip_padding_h', 'Padding vízszintes (px)', 10, 4, 20, 'fsm_sub_category_style' );
        self::field_number_custom( 'chip_border_width', 'Border vastagság (px)', 1, 0, 3, 'fsm_sub_category_style' );
        
        self::field_number_custom( 'chip_font_size', 'Betűméret (px)', 14, 12, 18, 'fsm_sub_category_style' );
        self::field_select_custom( 'chip_font_weight', 'Betűvastagság', array(
            '400' => 'Normal (400)',
            '500' => 'Medium (500)',
            '600' => 'Semibold (600)',
            '700' => 'Bold (700)',
            '800' => 'Extra Bold (800)',
            '900' => 'Black (900)',
        ), '800', 'fsm_sub_category_style' );

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

        // New: Main Category Styling
        $out['main_cat_bg_color'] = self::sanitize_color( $input, 'main_cat_bg_color', '#0b6ea8', true );
        $out['main_cat_text_color'] = self::sanitize_color( $input, 'main_cat_text_color', '#ffffff', true );
        $out['main_cat_icon_bg_color'] = self::sanitize_color( $input, 'main_cat_icon_bg_color', 'rgba(255,255,255,0.22)', true );
        $out['main_cat_icon_text_color'] = self::sanitize_color( $input, 'main_cat_icon_text_color', '#ffffff', true );
        $out['main_cat_hover_bg_color'] = self::sanitize_color( $input, 'main_cat_hover_bg_color', 'rgba(0,0,0,0.1)', true );
        $out['main_cat_hover_text_color'] = self::sanitize_color( $input, 'main_cat_hover_text_color', 'inherit', true );
        $out['main_cat_active_bg_color'] = self::sanitize_color( $input, 'main_cat_active_bg_color', '#0b6ea8', true );
        $out['main_cat_active_text_color'] = self::sanitize_color( $input, 'main_cat_active_text_color', '#ffffff', true );
        
        $out['main_cat_border_radius'] = self::sanitize_number( $input, 'main_cat_border_radius', 14, 0, 30 );
        $out['main_cat_padding_v'] = self::sanitize_number( $input, 'main_cat_padding_v', 8, 4, 20 );
        $out['main_cat_padding_h'] = self::sanitize_number( $input, 'main_cat_padding_h', 14, 4, 30 );
        $out['main_cat_icon_size'] = self::sanitize_number( $input, 'main_cat_icon_size', 36, 24, 48 );
        $out['main_cat_icon_radius'] = self::sanitize_number( $input, 'main_cat_icon_radius', 12, 0, 24 );
        
        $out['main_cat_font_size'] = self::sanitize_number( $input, 'main_cat_font_size', 18, 14, 24 );
        $out['main_cat_font_weight'] = self::sanitize_font_weight( $input, 'main_cat_font_weight', '900' );

        // New: Subcategory Styling
        $out['chip_bg_color'] = self::sanitize_color( $input, 'chip_bg_color', '#ffffff', true );
        $out['chip_text_color'] = self::sanitize_color( $input, 'chip_text_color', 'inherit', true );
        $out['chip_border_color'] = self::sanitize_color( $input, 'chip_border_color', 'rgba(0,0,0,0.12)', true );
        $out['chip_hover_bg_color'] = self::sanitize_color( $input, 'chip_hover_bg_color', 'rgba(11,110,168,0.06)', true );
        $out['chip_hover_border_color'] = self::sanitize_color( $input, 'chip_hover_border_color', '#0b6ea8', true );
        
        $out['chip_border_radius'] = self::sanitize_number( $input, 'chip_border_radius', 14, 0, 20 );
        $out['chip_padding_v'] = self::sanitize_number( $input, 'chip_padding_v', 4, 2, 16 );
        $out['chip_padding_h'] = self::sanitize_number( $input, 'chip_padding_h', 10, 4, 20 );
        $out['chip_border_width'] = self::sanitize_number( $input, 'chip_border_width', 1, 0, 3 );
        
        $out['chip_font_size'] = self::sanitize_number( $input, 'chip_font_size', 14, 12, 18 );
        $out['chip_font_weight'] = self::sanitize_font_weight( $input, 'chip_font_weight', '800' );

        // Clear menu cache when settings are saved
        if ( function_exists( 'fsm_clear_menu_cache' ) ) {
            fsm_clear_menu_cache();
        }

        return $out;
    }

    private static function sanitize_color( array $input, string $key, string $default, bool $allow_rgba = false ) : string {
        $value = isset( $input[ $key ] ) ? trim( (string) $input[ $key ] ) : $default;
        
        // Allow inherit and transparent keywords
        if ( in_array( $value, array( 'inherit', 'transparent' ), true ) ) {
            return $value;
        }
        
        // Allow rgba() format if enabled
        if ( $allow_rgba && preg_match( '/^rgba?\s*\(/', $value ) ) {
            return $value;
        }
        
        // Validate hex color
        if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $value ) ) {
            return $value;
        }
        
        return $default;
    }

    private static function sanitize_number( array $input, string $key, int $default, int $min, int $max ) : int {
        $value = isset( $input[ $key ] ) ? intval( $input[ $key ] ) : $default;
        if ( $value < $min ) $value = $min;
        if ( $value > $max ) $value = $max;
        return $value;
    }

    private static function sanitize_font_weight( array $input, string $key, string $default ) : string {
        $value = isset( $input[ $key ] ) ? (string) $input[ $key ] : $default;
        $allowed = array( '400', '500', '600', '700', '800', '900' );
        return in_array( $value, $allowed, true ) ? $value : $default;
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

    private static function field_number_custom( string $key, string $label, int $default, int $min, int $max, string $section ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $default, $min, $max ) {
            $all = FSM_Settings::get_all();
            $val = isset( $all[ $key ] ) ? intval( $all[ $key ] ) : $default;
            echo '<input type="number" name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" />';
        }, 'forme-smart-menu', $section );
    }

    private static function field_select_custom( string $key, string $label, array $choices, string $default, string $section ) : void {
        add_settings_field( $key, esc_html( $label ), function () use ( $key, $choices, $default ) {
            $all = FSM_Settings::get_all();
            $val = isset( $all[ $key ] ) ? (string) $all[ $key ] : $default;
            echo '<select name="' . esc_attr( FSM_Settings::OPTION_KEY ) . '[' . esc_attr( $key ) . ']">';
            foreach ( $choices as $k => $v ) {
                echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
            }
            echo '</select>';
        }, 'forme-smart-menu', $section );
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

        // Style presets
        echo '<hr style="margin: 30px 0;">';
        echo '<h2>Gyors stílus előbeállítások</h2>';
        echo '<p>Kattints valamelyik gombra az összes stílus beállítás azonnali kitöltéséhez:</p>';
        echo '<div style="display: flex; gap: 10px; margin: 15px 0;">';
        echo '<button type="button" class="button button-primary" id="fsm-preset-classic">📘 Klasszikus stílus</button>';
        echo '<button type="button" class="button button-primary" id="fsm-preset-minimal">✨ Minimális stílus</button>';
        echo '</div>';
        echo '<p style="color: #666; font-size: 13px;">⚠️ Ezek a gombok felülírják az összes stílus beállítást! A változtatások mentéséhez görgess le és kattints a "Változtatások mentése" gombra.</p>';

        // JavaScript for presets
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const optionKey = '<?php echo esc_js( FSM_Settings::OPTION_KEY ); ?>';
            
            // Helper to set field value
            function setField(key, value) {
                const field = document.querySelector(`[name="${optionKey}[${key}]"]`);
                if (field) {
                    field.value = value;
                }
            }
            
            // Classic preset (current default)
            document.getElementById('fsm-preset-classic')?.addEventListener('click', function() {
                if (!confirm('Biztosan felülírod az összes stílus beállítást a Klasszikus stílussal?')) return;
                
                // Main category
                setField('main_cat_bg_color', '#0b6ea8');
                setField('main_cat_text_color', '#ffffff');
                setField('main_cat_icon_bg_color', 'rgba(255,255,255,0.22)');
                setField('main_cat_icon_text_color', '#ffffff');
                setField('main_cat_hover_bg_color', 'rgba(0,0,0,0.1)');
                setField('main_cat_hover_text_color', 'inherit');
                setField('main_cat_active_bg_color', '#0b6ea8');
                setField('main_cat_active_text_color', '#ffffff');
                setField('main_cat_border_radius', '14');
                setField('main_cat_padding_v', '8');
                setField('main_cat_padding_h', '14');
                setField('main_cat_icon_size', '36');
                setField('main_cat_icon_radius', '12');
                setField('main_cat_font_size', '18');
                setField('main_cat_font_weight', '900');
                
                // Subcategory
                setField('chip_bg_color', '#ffffff');
                setField('chip_text_color', 'inherit');
                setField('chip_border_color', 'rgba(0,0,0,0.12)');
                setField('chip_hover_bg_color', 'rgba(11,110,168,0.06)');
                setField('chip_hover_border_color', '#0b6ea8');
                setField('chip_border_radius', '14');
                setField('chip_padding_v', '4');
                setField('chip_padding_h', '10');
                setField('chip_border_width', '1');
                setField('chip_font_size', '14');
                setField('chip_font_weight', '800');
                
                alert('✅ Klasszikus stílus beállítások betöltve! Ne felejtsd el menteni.');
            });
            
            // Minimal preset
            document.getElementById('fsm-preset-minimal')?.addEventListener('click', function() {
                if (!confirm('Biztosan felülírod az összes stílus beállítást a Minimális stílussal?')) return;
                
                // Main category - minimal (NO background, just content)
                setField('main_cat_bg_color', 'transparent');
                setField('main_cat_text_color', '#003d5c');
                setField('main_cat_icon_bg_color', 'transparent');
                setField('main_cat_icon_text_color', '#003d5c');
                setField('main_cat_hover_bg_color', 'rgba(11,110,168,0.08)');
                setField('main_cat_hover_text_color', '#003d5c');
                setField('main_cat_active_bg_color', 'rgba(11,110,168,0.12)');
                setField('main_cat_active_text_color', '#003d5c');
                setField('main_cat_border_radius', '0');
                setField('main_cat_padding_v', '12');
                setField('main_cat_padding_h', '8');
                setField('main_cat_icon_size', '28');
                setField('main_cat_icon_radius', '0');
                setField('main_cat_font_size', '16');
                setField('main_cat_font_weight', '700');
                
                // Subcategory - subtle blue bg, dark text
                setField('chip_bg_color', '#f0f4f8');
                setField('chip_text_color', '#003d5c');
                setField('chip_border_color', 'rgba(11,110,168,0.1)');
                setField('chip_hover_bg_color', 'rgba(11,110,168,0.12)');
                setField('chip_hover_border_color', 'rgba(11,110,168,0.3)');
                setField('chip_border_radius', '6');
                setField('chip_padding_v', '6');
                setField('chip_padding_h', '10');
                setField('chip_border_width', '1');
                setField('chip_font_size', '14');
                setField('chip_font_weight', '600');
                
                alert('✅ Minimális stílus beállítások betöltve! Ne felejtsd el menteni.');
            });
        });
        </script>
        <?php
        echo '</div>';
    }

    public static function register_ajax() : void {
        add_action( 'wp_ajax_fsm_reorder_sections', array( __CLASS__, 'ajax_reorder_sections' ) );
        add_action( 'wp_ajax_fsm_toggle_section', array( __CLASS__, 'ajax_toggle_section' ) );
    }

    public static function ajax_reorder_sections() : void {
        check_ajax_referer( 'fsm_custom_sections_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

        $order = isset( $_POST['order'] ) ? $_POST['order'] : array();
        if ( ! is_array( $order ) ) wp_send_json_error( 'Invalid data' );

        // Map index => id to id => position
        $map = array();
        foreach ( $order as $position => $id ) {
            $map[ intval( $id ) ] = intval( $position );
        }

        if ( FSM_Custom_Sections::reorder_sections( $map ) ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Failed to save order' );
        }
    }

    public static function ajax_toggle_section() : void {
        check_ajax_referer( 'fsm_custom_sections_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) wp_send_json_error( 'Invalid ID' );

        if ( FSM_Custom_Sections::toggle_enabled( $id ) ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Failed to toggle' );
        }
    }

    public static function page_custom_sections() : void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Handle Form Submission
        if ( isset( $_POST['fsm_save_section'] ) && check_admin_referer( 'fsm_save_section_action' ) ) {
            $id = isset( $_POST['section_id'] ) ? intval( $_POST['section_id'] ) : 0;
            $data = array(
                'name' => isset( $_POST['section_name'] ) ? sanitize_text_field( $_POST['section_name'] ) : '',
                'subcategories' => isset( $_POST['subcategories'] ) ? array_map( 'intval', $_POST['subcategories'] ) : array(),
                'enabled' => isset( $_POST['section_enabled'] ) ? 1 : 0,
                'position' => isset( $_POST['section_position'] ) ? intval( $_POST['section_position'] ) : 0,
            );

            if ( $id > 0 ) {
                FSM_Custom_Sections::update_section( $id, $data );
                echo '<div class="notice notice-success is-dismissible"><p>Szekció frissítve.</p></div>';
            } else {
                FSM_Custom_Sections::create_section( $data );
                echo '<div class="notice notice-success is-dismissible"><p>Új szekció létrehozva.</p></div>';
            }
        }

        // Handle Delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            check_admin_referer( 'fsm_delete_section' ); // Check nonce from URL
            $id = intval( $_GET['id'] );
            FSM_Custom_Sections::delete_section( $id );
            echo '<div class="notice notice-success is-dismissible"><p>Szekció törölve.</p></div>';
        }

        $all_sections = FSM_Custom_Sections::get_all_sections();
        $is_edit = isset( $_GET['action'] ) && $_GET['action'] === 'edit';
        $edit_id = $is_edit && isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $edit_data = null;

        if ( $edit_id ) {
            $edit_data = FSM_Custom_Sections::get_section( $edit_id );
            if ( ! $edit_data ) {
                $is_edit = false;
                echo '<div class="notice notice-error"><p>Szekció nem található.</p></div>';
            }
        }

        // Get all categories for selector
        $all_cats = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ) );
        ?>
        <div class="wrap fsm-admin-wrap">
            <h1 class="wp-heading-inline">Kiemelt Kategória Szekciók</h1>
            <?php if ( ! $is_edit ) : ?>
                <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => 0 ) ) ); ?>" class="page-title-action">Új hozzáadása</a>
            <?php endif; ?>
            <hr class="wp-header-end">

            <?php if ( $is_edit || ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && $edit_id === 0 ) ) : ?>
                <!-- Edit/Create Form -->
                <div class="fsm-edit-form card">
                    <h2><?php echo $edit_id ? 'Szekció szerkesztése' : 'Új szekció létrehozása'; ?></h2>
                    <form method="post">
                        <?php wp_nonce_field( 'fsm_save_section_action' ); ?>
                        <input type="hidden" name="fsm_save_section" value="1">
                        <input type="hidden" name="section_id" value="<?php echo esc_attr( $edit_id ); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="section_name">Szekció neve</label></th>
                                <td>
                                    <input name="section_name" type="text" id="section_name" value="<?php echo $edit_data ? esc_attr( $edit_data['name'] ) : ''; ?>" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Alkategóriák</label></th>
                                <td>
                                    <div class="fsm-cat-selector">
                                        <?php
                                        $selected_cats = $edit_data && isset( $edit_data['subcategories'] ) ? $edit_data['subcategories'] : array();
                                        if ( ! is_wp_error( $all_cats ) ) {
                                            foreach ( $all_cats as $cat ) {
                                                $is_checked = in_array( intval( $cat->term_id ), $selected_cats, true );
                                                echo '<label class="fsm-cat-checkbox">';
                                                echo '<input type="checkbox" name="subcategories[]" value="' . esc_attr( $cat->term_id ) . '" ' . checked( $is_checked, true, false ) . '> ';
                                                echo esc_html( $cat->name );
                                                echo '</label>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <p class="description">Válaszd ki, mely kategóriák jelenjenek meg ebben a szekcióban.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="section_position">Pozíció</label></th>
                                <td>
                                    <input name="section_position" type="number" id="section_position" value="<?php echo $edit_data ? esc_attr( $edit_data['position'] ) : '0'; ?>" class="small-text">
                                    <p class="description">Sorrend. Az automatikus kategóriák <strong>1000-től</strong> indulnak. <br>Ha eléjük szeretnéd: <strong>0-999</strong>. <br>Ha mögéjük: <strong>2000+</strong>.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Állapot</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="section_enabled" value="1" <?php checked( $edit_data ? $edit_data['enabled'] : true ); ?>>
                                        Engedélyezve
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">Mentés</button>
                            <a href="<?php echo esc_url( remove_query_arg( array( 'action', 'id' ) ) ); ?>" class="button button-secondary">Mégse</a>
                        </p>
                    </form>
                </div>

            <?php else : ?>
                <!-- List View -->
                <div class="fsm-sections-list">
                    <?php if ( empty( $all_sections ) ) : ?>
                        <div class="notice notice-info inline"><p>Még nincs létrehozva kiemelt szekció.</p></div>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"></th> <!-- Drag handle -->
                                    <th>Név</th>
                                    <th>Alkategóriák</th>
                                    <th style="width: 80px;">Pozíció</th>
                                    <th style="width: 80px;">Állapot</th>
                                    <th style="width: 150px;">Műveletek</th>
                                </tr>
                            </thead>
                            <tbody id="fsm-sortable-list">
                                <?php foreach ( $all_sections as $section ) : 
                                    $sub_count = isset( $section['subcategories'] ) ? count( $section['subcategories'] ) : 0;
                                    $is_enabled = ! empty( $section['enabled'] );
                                    $edit_url = add_query_arg( array( 'action' => 'edit', 'id' => $section['id'] ) );
                                    $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $section['id'] ) ), 'fsm_delete_section' );
                                ?>
                                    <tr data-id="<?php echo esc_attr( $section['id'] ); ?>">
                                        <td class="fsm-drag-handle" style="cursor: move; color: #aaa;">☰</td>
                                        <td>
                                            <strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $section['name'] ); ?></a></strong>
                                        </td>
                                        <td><?php echo intval( $sub_count ); ?> db</td>
                                        <td><?php echo intval( $section['position'] ); ?></td>
                                        <td>
                                            <button type="button" class="fsm-toggle-status button button-small <?php echo $is_enabled ? '' : 'button-link-delete'; ?>" data-id="<?php echo esc_attr( $section['id'] ); ?>">
                                                <?php echo $is_enabled ? 'Aktív' : 'Inaktív'; ?>
                                            </button>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">Szerkesztés</a>
                                            <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete fsm-delete-btn">Törlés</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="description">Fogd meg a ☰ ikont a sorrend módosításához. <br><strong>Tipp:</strong> A drag-and-drop 0-tól indítja a sorszámozást (lista eleje). Ha az automatikus kategóriák (1000+) mögé szeretnéd tenni a szekciót, a szerkesztésnél adj meg 2000-nél nagyobb számot.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
