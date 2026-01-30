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

        $limit = FSM_Settings::get_int( 'child_limit', 6 );

        // Cache key depends on settings + locale
        $cache_key = 'fsm_drawer_' . md5( wp_json_encode( array(
            'limit' => $limit,
            'primary' => $primary,
            'lang'  => function_exists( 'get_locale' ) ? get_locale() : 'na',
        ) ) );

        $inner = get_transient( $cache_key );
        if ( ! is_string( $inner ) || $inner === '' ) {
            $inner = self::build_menu_inner( $limit );
            set_transient( $cache_key, $inner, 12 * HOUR_IN_SECONDS );
        }

        $links_html = self::build_extra_links_html();

        ob_start();
        ?>
        <div class="fsm-overlay" data-fsm-close hidden></div>
        <aside id="fsm-drawer" class="fsm-drawer" role="dialog" aria-modal="true" aria-label="Kategóriák" hidden style="--fsm-primary: <?php echo esc_attr( $primary ); ?>;">
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

    private static function build_menu_inner( int $limit ) : string {
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

            // Skip empty parents to avoid “üres fehér blokkok”
            if ( empty( $children ) ) { continue; }

            $has_more = count( $children ) > $limit;
            $visible_limit = $limit;
            if ( $has_more ) {
                $visible_limit = max( $limit - 1, 0 );
            }
            $shown = array_slice( $children, 0, $visible_limit );
            $rest  = array_slice( $children, $visible_limit );
            $panel_id = 'fsm-panel-' . $parent_id;

            ?>
            <section class="fsm-section" data-parent-id="<?php echo esc_attr( $parent_id ); ?>">
                <button class="fsm-section__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
                    <span class="fsm-section__title"><?php echo esc_html( $parent_term->name ); ?></span>
                    <span class="fsm-section__desc"><?php echo esc_html( self::subline_for_parent( $parent_term ) ); ?></span>
                    <span class="fsm-section__icon" aria-hidden="true">+</span>
                </button>

                <div id="<?php echo esc_attr( $panel_id ); ?>" class="fsm-panel" hidden>
                    <div class="fsm-chips">
                        <?php foreach ( $shown as $child ) : ?>
                            <a class="fsm-chip" href="<?php echo esc_url( get_term_link( $child ) ); ?>">
                                <?php echo esc_html( $child->name ); ?>
                            </a>
                        <?php endforeach; ?>

                        <?php if ( ! empty( $rest ) ) : ?>
                            <?php foreach ( $rest as $child ) : ?>
                                <a class="fsm-chip fsm-chip--extra" href="<?php echo esc_url( get_term_link( $child ) ); ?>">
                                    <?php echo esc_html( $child->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $rest ) ) : ?>
                            <button class="fsm-chip fsm-chip--more" type="button" data-fsm-more>
                                még több <span aria-hidden="true">+</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php
        }
        echo '</div>';
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
