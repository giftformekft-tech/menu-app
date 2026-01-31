<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Renderer {

    private static $drawer_rendered = false;

    public static function shortcode_button( $atts = array(), $content = '' ) : string {
        $label = FSM_Settings::get_string( 'button_label', 'Kategóriák' );
        $icon_only = FSM_Settings::get_bool( 'button_icon_only', false );

        $label_html = $icon_only ? '' : '<span class="fsm-btn__label">' . esc_html( $label ) . '</span>';

        return '<button type="button" class="fsm-btn" data-fsm-open aria-haspopup="dialog" aria-controls="fsm-drawer" aria-expanded="false">'
            . '<span class="fsm-btn__icon" aria-hidden="true">☰</span>'
            . $label_html
            . '</button>';
    }

    public static function render_drawer_once() : string {
        if ( self::$drawer_rendered ) return '';
        self::$drawer_rendered = true;

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return '<!-- FSM: product_cat taxonomy missing -->';
        }

        $primary = FSM_Settings::get_string( 'primary_color', '#0b6ea8' );
        if ( ! preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $primary ) ) {
            $primary = '#0b6ea8';
        }

        // Feature 5: Separate limits for mobile/desktop
        $limit_mobile = FSM_Settings::get_int( 'child_limit_mobile', 6 );
        $limit_desktop = FSM_Settings::get_int( 'child_limit_desktop', 9 );
        
        // Feature 3: Grid columns
        $grid_mobile = FSM_Settings::get_int( 'grid_columns_mobile', 2 );
        $grid_desktop = FSM_Settings::get_int( 'grid_columns_desktop', 3 );
        
        // Feature 4: More button colors
        $more_bg = FSM_Settings::get_string( 'more_button_bg_color', 'transparent' );
        $more_text = FSM_Settings::get_string( 'more_button_text_color', 'inherit' );

        // New: All customizable styling settings
        $main_cat_bg = FSM_Settings::get_string( 'main_cat_bg_color', '#0b6ea8' );
        $main_cat_text = FSM_Settings::get_string( 'main_cat_text_color', '#ffffff' );
        $main_cat_icon_bg = FSM_Settings::get_string( 'main_cat_icon_bg_color', 'rgba(255,255,255,0.22)' );
        $main_cat_icon_text = FSM_Settings::get_string( 'main_cat_icon_text_color', '#ffffff' );
        $main_cat_hover_bg = FSM_Settings::get_string( 'main_cat_hover_bg_color', 'rgba(0,0,0,0.1)' );
        $main_cat_hover_text = FSM_Settings::get_string( 'main_cat_hover_text_color', 'inherit' );
        $main_cat_active_bg = FSM_Settings::get_string( 'main_cat_active_bg_color', '#0b6ea8' );
        $main_cat_active_text = FSM_Settings::get_string( 'main_cat_active_text_color', '#ffffff' );
        $main_cat_radius = FSM_Settings::get_int( 'main_cat_border_radius', 14 );
        $main_cat_pad_v = FSM_Settings::get_int( 'main_cat_padding_v', 8 );
        $main_cat_pad_h = FSM_Settings::get_int( 'main_cat_padding_h', 14 );
        $main_cat_icon_size = FSM_Settings::get_int( 'main_cat_icon_size', 36 );
        $main_cat_icon_radius = FSM_Settings::get_int( 'main_cat_icon_radius', 12 );
        $main_cat_font_size = FSM_Settings::get_int( 'main_cat_font_size', 18 );
        $main_cat_font_weight = FSM_Settings::get_string( 'main_cat_font_weight', '900' );

        $chip_bg = FSM_Settings::get_string( 'chip_bg_color', '#ffffff' );
        $chip_text = FSM_Settings::get_string( 'chip_text_color', 'inherit' );
        $chip_border = FSM_Settings::get_string( 'chip_border_color', 'rgba(0,0,0,0.12)' );
        $chip_hover_bg = FSM_Settings::get_string( 'chip_hover_bg_color', 'rgba(11,110,168,0.06)' );
        $chip_hover_border = FSM_Settings::get_string( 'chip_hover_border_color', '#0b6ea8' );
        $chip_radius = FSM_Settings::get_int( 'chip_border_radius', 14 );
        $chip_pad_v = FSM_Settings::get_int( 'chip_padding_v', 4 );
        $chip_pad_h = FSM_Settings::get_int( 'chip_padding_h', 10 );
        $chip_border_width = FSM_Settings::get_int( 'chip_border_width', 1 );
        $chip_font_size = FSM_Settings::get_int( 'chip_font_size', 14 );
        $chip_font_weight = FSM_Settings::get_string( 'chip_font_weight', '800' );

        // Cache key depends on settings + locale
        $cache_key = 'fsm_drawer_' . md5( wp_json_encode( array(
            'limit_mobile' => $limit_mobile,
            'limit_desktop' => $limit_desktop,
            'primary' => $primary,
            'lang'  => function_exists( 'get_locale' ) ? get_locale() : 'na',
            'show_desc' => FSM_Settings::get_bool( 'show_descriptions', true ),
            'style_v' => '0.5.0', // Increment when adding new style settings
        ) ) );

        $inner = get_transient( $cache_key );
        if ( ! is_string( $inner ) || $inner === '' ) {
            $inner = self::build_menu_inner( $limit_mobile, $limit_desktop );
            set_transient( $cache_key, $inner, 12 * HOUR_IN_SECONDS );
        }

        $links_html = self::build_extra_links_html();
        
        // Build inline CSS with all custom properties
        $inline_style = sprintf(
            '--fsm-primary: %s; --fsm-grid-mobile: %d; --fsm-grid-desktop: %d; --fsm-more-bg: %s; --fsm-more-color: %s; ' .
            '--fsm-main-bg: %s; --fsm-main-text: %s; --fsm-main-icon-bg: %s; --fsm-main-icon-text: %s; ' .
            '--fsm-main-hover-bg: %s; --fsm-main-hover-text: %s; ' .
            '--fsm-main-active-bg: %s; --fsm-main-active-text: %s; ' .
            '--fsm-main-radius: %dpx; ' .
            '--fsm-main-pad-v: %dpx; --fsm-main-pad-h: %dpx; ' .
            '--fsm-main-icon-size: %dpx; --fsm-main-icon-radius: %dpx; ' .
            '--fsm-main-font-size: %dpx; --fsm-main-font-weight: %s; ' .
            '--fsm-chip-bg: %s; --fsm-chip-text: %s; --fsm-chip-border: %s; ' .
            '--fsm-chip-hover-bg: %s; --fsm-chip-hover-border: %s; ' .
            '--fsm-chip-radius: %dpx; --fsm-chip-pad-v: %dpx; --fsm-chip-pad-h: %dpx; ' .
            '--fsm-chip-border-width: %dpx; --fsm-chip-font-size: %dpx; --fsm-chip-font-weight: %s;',
            esc_attr( $primary ),
            intval( $grid_mobile ),
            intval( $grid_desktop ),
            esc_attr( $more_bg ),
            esc_attr( $more_text ),
            esc_attr( $main_cat_bg ),
            esc_attr( $main_cat_text ),
            esc_attr( $main_cat_icon_bg ),
            esc_attr( $main_cat_icon_text ),
            esc_attr( $main_cat_hover_bg ),
            esc_attr( $main_cat_hover_text ),
            esc_attr( $main_cat_active_bg ),
            esc_attr( $main_cat_active_text ),
            intval( $main_cat_radius ),
            intval( $main_cat_pad_v ),
            intval( $main_cat_pad_h ),
            intval( $main_cat_icon_size ),
            intval( $main_cat_icon_radius ),
            intval( $main_cat_font_size ),
            esc_attr( $main_cat_font_weight ),
            esc_attr( $chip_bg ),
            esc_attr( $chip_text ),
            esc_attr( $chip_border ),
            esc_attr( $chip_hover_bg ),
            esc_attr( $chip_hover_border ),
            intval( $chip_radius ),
            intval( $chip_pad_v ),
            intval( $chip_pad_h ),
            intval( $chip_border_width ),
            intval( $chip_font_size ),
            esc_attr( $chip_font_weight )
        );

        ob_start();
        ?>
        <div class="fsm-overlay" data-fsm-close hidden></div>
        <aside id="fsm-drawer" class="fsm-drawer" role="dialog" aria-modal="true" aria-label="Kategóriák" hidden style="<?php echo $inline_style; ?>">
            <div class="fsm-drawer__head">
                <div class="fsm-drawer__title">Kategóriák</div>
                <button type="button" class="fsm-drawer__close" data-fsm-close aria-label="Bezárás">✕</button>
            </div>
            <div class="fsm-drawer__body">
                <?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $links_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </aside>
        <?php
        return ob_get_clean();
    }

    private static function build_extra_links_html() : string {
        $raw = FSM_Settings::get_string( 'extra_links', '' );
        $raw = trim( (string) $raw );
        if ( $raw === '' ) {
            return '';
        }

        $title = FSM_Settings::get_string( 'extra_links_title', 'Információk' );
        $lines = preg_split( "/\r\n|\r|\n/", $raw );
        $items = array();

        foreach ( $lines as $line ) {
            $line = trim( (string) $line );
            if ( $line === '' ) continue;

            $label = '';
            $url   = '';

            if ( strpos( $line, '|' ) !== false ) {
                $parts = array_map( 'trim', explode( '|', $line, 2 ) );
                $label = $parts[0] ?? '';
                $url   = $parts[1] ?? '';
            } else {
                // Allow "Felirat: URL" too
                if ( strpos( $line, ':' ) !== false ) {
                    $parts = array_map( 'trim', explode( ':', $line, 2 ) );
                    if ( isset( $parts[1] ) && preg_match( '#^https?://|^/#', $parts[1] ) ) {
                        $label = $parts[0];
                        $url   = $parts[1];
                    }
                }
                if ( $url === '' ) {
                    $url = $line;
                    $label = $line;
                }
            }

            $label = trim( (string) $label );
            $url   = trim( (string) $url );
            if ( $label === '' || $url === '' ) continue;

            // Accept relative URLs
            if ( strpos( $url, 'http://' ) !== 0 && strpos( $url, 'https://' ) !== 0 && strpos( $url, '/' ) === 0 ) {
                $url = home_url( $url );
            }

            $url = esc_url( $url );
            if ( $url === '' ) continue;

            $items[] = array(
                'label' => sanitize_text_field( $label ),
                'url'   => $url,
            );
        }

        if ( empty( $items ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="fsm-links">
            <div class="fsm-links__title"><?php echo esc_html( $title ); ?></div>
            <nav class="fsm-links__list" aria-label="<?php echo esc_attr( $title ); ?>">
                <?php foreach ( $items as $it ) : ?>
                    <a class="fsm-link" href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['label'] ); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function build_menu_inner( int $limit_mobile, int $limit_desktop ) : string {
        $parents = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $parents ) || empty( $parents ) ) {
            return '<!-- FSM: no parent categories -->';
        }

        $show_descriptions = FSM_Settings::get_bool( 'show_descriptions', true );

        ob_start();
        echo '<div class="fsm-section-list">';
        foreach ( $parents as $parent_term ) {
            $parent_id = intval( $parent_term->term_id );

            $children = get_terms( array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $parent_id,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );
            if ( is_wp_error( $children ) ) { $children = array(); }

            // Skip empty parents
            if ( empty( $children ) ) { continue; }

            $panel_id = 'fsm-panel-' . $parent_id;
            
            // Feature 2: Category icon
            $icon_url = '';
            if ( class_exists( 'FSM_Category_Meta' ) ) {
                $icon_url = FSM_Category_Meta::get_category_icon( $parent_id );
            }
            
            ?>
            <section class="fsm-section" data-parent-id="<?php echo esc_attr( $parent_id ); ?>">
                <button class="fsm-section__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
                    <?php if ( $icon_url ) : ?>
                        <img class="fsm-section__icon-img" src="<?php echo esc_url( $icon_url ); ?>" alt="" />
                    <?php endif; ?>
                    <span class="fsm-section__title"><?php echo esc_html( $parent_term->name ); ?></span>
                    <?php if ( $show_descriptions ) : ?>
                        <span class="fsm-section__desc"><?php echo esc_html( self::subline_for_parent( $parent_term ) ); ?></span>
                    <?php endif; ?>
                    <span class="fsm-section__icon" aria-hidden="true">+</span>
                </button>

                <div id="<?php echo esc_attr( $panel_id ); ?>" class="fsm-panel" hidden>
                    <?php echo self::render_chips( $children, $limit_mobile, $limit_desktop ); ?>
                </div>
            </section>
            <?php
        }
        echo '</div>';
        return ob_get_clean();
    }

    private static function render_chips( array $children, int $limit_mobile, int $limit_desktop ) : string {
        $total = count( $children );
        $max_limit = max( $limit_mobile, $limit_desktop );
        
        // Determine how many cards we need for each variant
        $has_more_mobile = $total > $limit_mobile;
        $has_more_desktop = $total > $limit_desktop;
        
        $visible_mobile = $has_more_mobile ? max( $limit_mobile - 1, 0 ) : $limit_mobile;
        $visible_desktop = $has_more_desktop ? max( $limit_desktop - 1, 0 ) : $limit_desktop;
        
        ob_start();
        ?>
        <div class="fsm-chips">
            <?php
            // Render all children with appropriate classes
            foreach ( $children as $index => $child ) :
                $classes = array( 'fsm-chip' );
                
                // Determine visibility for mobile
                if ( $index >= $visible_mobile && $has_more_mobile ) {
                    $classes[] = 'fsm-chip--extra';
                    $classes[] = 'fsm-chip--mobile-extra';
                }
                
                // Determine visibility for desktop
                if ( $index >= $visible_desktop && $has_more_desktop ) {
                    if ( ! in_array( 'fsm-chip--extra', $classes ) ) {
                        $classes[] = 'fsm-chip--extra';
                    }
                    $classes[] = 'fsm-chip--desktop-extra';
                }
                
                ?>
                <a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="<?php echo esc_url( get_term_link( $child ) ); ?>">
                    <?php echo esc_html( $child->name ); ?>
                </a>
            <?php endforeach; ?>
            
            <?php if ( $has_more_mobile || $has_more_desktop ) : ?>
                <button class="fsm-chip fsm-chip--more" type="button" data-fsm-more>
                    még több <span aria-hidden="true">+</span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function subline_for_parent( $term ) : string {
        $desc = isset( $term->description ) ? wp_strip_all_tags( $term->description ) : '';
        $desc = trim( $desc );
        if ( $desc !== '' ) {
            if ( function_exists( 'mb_substr' ) ) {
                $desc = mb_substr( $desc, 0, 80 );
            } else {
                $desc = substr( $desc, 0, 80 );
            }
            return $desc;
        }
        return 'Nézd meg a témákat';
    }
}
