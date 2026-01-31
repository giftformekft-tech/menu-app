<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Admin {

    public static function init() : void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
    }

    public static function enqueue_admin_assets( string $hook ) : void {
        //  Only load on our settings page
        if ( 'settings_page_forme-smart-menu' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'fsm-admin-ui',
            FSM_URL . 'assets/css/admin-ui.css',
            array(),
            FSM_VERSION
       );

        wp_enqueue_script(
            'fsm-admin-ui',
            FSM_URL . 'assets/js/admin-ui.js',
            array( 'jquery' ),
            FSM_VERSION,
            true
        );
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

        echo '<div class="wrap">';
        echo '<h1>Forme Smart Menu</h1>';
        
        // Tab Navigation
        echo '<div class="fsm-admin-tabs">';
        echo '<ul class="fsm-admin-tabs__nav">';
        echo '<li><a href="#" class="fsm-admin-tabs__link is-active" data-tab="tab-basic">⚙️ Alapbeállítások</a></li>';
        echo '<li><a href="#" class="fsm-admin-tabs__link" data-tab="tab-appearance">🎨 Megjelenés</a></li>';
        echo '<li><a href="#" class="fsm-admin-tabs__link" data-tab="tab-featured">⭐ Kiemelt</a></li>';
        echo '<li><a href="#" class="fsm-admin-tabs__link" data-tab="tab-sections">📌 Egyedi Szekciók</a></li>';
        echo '<li><a href="#" class="fsm-admin-tabs__link" data-tab="tab-order">🔀 Menü Sorrend</a></li>';
        echo '</ul>';
        echo '</div>';
        
        // Main Form
        echo '<form method="post" action="options.php">';
        settings_fields( 'fsm_settings' );
        
        // Tab: Basic Settings
        echo '<div id="tab-basic" class="fsm-admin-tab-content is-active">';
        self::render_tab_basic();
        echo '</div>';
        
        // Tab: Appearance
        echo '<div id="tab-appearance" class="fsm-admin-tab-content">';
        self::render_tab_appearance();
        echo '</div>';
        
        // Tab: Featured
        echo '<div id="tab-featured" class="fsm-admin-tab-content">';
        self::render_tab_featured();
        echo '</div>';
        
        // Tab: Custom Sections
        echo '<div id="tab-sections" class="fsm-admin-tab-content">';
        self::render_tab_sections();
        echo '</div>';
        
        // Tab: Menu Order
        echo '<div id="tab-order" class="fsm-admin-tab-content">';
        self::render_tab_order();
        echo '</div>';
        
        submit_button();
        echo '</form>';
        
        echo '</div>'; // .wrap
    }

    private static function render_tab_basic() : void {
        echo '<div class="fsm-card">';
        echo '<div class="fsm-card__header">';
        echo '<h2 class="fsm-card__title">Általános beállítások</h2>';
        echo '</div>';
        echo '<div class="fsm-card__body">';
        
        do_settings_sections( 'forme-smart-menu' );
        
        echo '</div>';
        echo '</div>';
        
        // Cache management
        echo '<div class="fsm-card">';
        echo '<div class="fsm-card__header">';
        echo '<h2 class="fsm-card__title">Cache kezelés</h2>';
        echo '</div>';
        echo '<div class="fsm-card__body">';
        echo '<p>Ha a menü nem frissül megfelelően, töröld a cache-t:</p>';
        echo '<form method="post" style="margin-top: 15px;">';
        wp_nonce_field( 'fsm_clear_cache_action' );
        echo '<button type="submit" name="fsm_clear_cache" class="button button-secondary">Menü cache törlése</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    private static function render_tab_appearance() : void {
        echo '<div class="fsm-card">';
        echo '<div class="fsm-card__header">';
        echo '<h2 class="fsm-card__title">Gyors stílus előbeállítások</h2>';
        echo '</div>';
        echo '<div class="fsm-card__body">';
        echo '<p>Kattints valamelyik gombra az összes stílus beállítás azonnali kitöltéséhez:</p>';
        echo '<div class="fsm-presets">';
        echo '<button type="button" class="button button-primary fsm-preset-btn" id="fsm-preset-classic">📘 Klasszikus stílus</button>';
        echo '<button type="button" class="button button-primary fsm-preset-btn" id="fsm-preset-minimal">✨ Minimális stílus</button>';
        echo '</div>';
        echo '<p class="fsm-text-muted">⚠️ Ezek a gombok felülírják az összes stílus beállítást! A változtatások mentéséhez görgess le és kattints a "Változtatások mentése" gombra.</p>';
        echo '</div>';
        echo '</div>';
        
        self::render_preset_javascript();
    }

    private static function render_tab_featured() : void {
        echo '<div class="fsm-empty-state">';
        echo '<div class="fsm-empty-state__icon">⭐</div>';
        echo '<p class="fsm-empty-state__text">A kiemelt alkategóriák funkció hamarosan elérhető lesz.</p>';
        echo '</div>';
    }

    private static function render_tab_sections() : void {
        echo '<div class="fsm-empty-state">';
        echo '<div class="fsm-empty-state__icon">📌</div>';
        echo '<p class="fsm-empty-state__text">Az egyedi szekciók funkció hamarosan elérhető lesz.</p>';
        echo '</div>';
    }

    private static function render_tab_order() : void {
        echo '<div class="fsm-empty-state">';
        echo '<div class="fsm-empty-state__icon">🔀</div>';
        echo '<p class="fsm-empty-state__text">A menü sorrend állító hamarosan elérhető lesz.</p>';
        echo '</div>';
    }

    private static function render_preset_javascript() : void {
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
    }
}
