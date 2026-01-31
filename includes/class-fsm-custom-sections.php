<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Custom_Sections {

    const OPTION_KEY = 'fsm_custom_sections';

    public static function init() : void {
        // Hook for cache clearing when sections are modified
        add_action( 'fsm_custom_sections_modified', 'fsm_clear_menu_cache' );
    }

    /**
     * Get all custom sections, sorted by position
     */
    public static function get_all_sections() : array {
        $sections = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $sections ) ) {
            $sections = array();
        }

        // Sort by position
        usort( $sections, function( $a, $b ) {
            $pos_a = isset( $a['position'] ) ? intval( $a['position'] ) : 999999;
            $pos_b = isset( $b['position'] ) ? intval( $b['position'] ) : 999999;
            if ( $pos_a === $pos_b ) {
                // If same position, sort by ID
                $id_a = isset( $a['id'] ) ? intval( $a['id'] ) : 0;
                $id_b = isset( $b['id'] ) ? intval( $b['id'] ) : 0;
                return $id_a - $id_b;
            }
            return $pos_a - $pos_b;
        });

        return $sections;
    }

    /**
     * Get enabled custom sections only
     */
    public static function get_enabled_sections() : array {
        $all = self::get_all_sections();
        return array_filter( $all, function( $section ) {
            return ! empty( $section['enabled'] );
        });
    }

    /**
     * Get a single section by ID
     */
    public static function get_section( int $id ) : ?array {
        $sections = self::get_all_sections();
        foreach ( $sections as $section ) {
            if ( isset( $section['id'] ) && intval( $section['id'] ) === $id ) {
                return $section;
            }
        }
        return null;
    }

    /**
     * Create a new custom section
     */
    public static function create_section( array $data ) : int {
        $sections = self::get_all_sections();
        
        // Generate new ID
        $max_id = 0;
        foreach ( $sections as $section ) {
            if ( isset( $section['id'] ) && intval( $section['id'] ) > $max_id ) {
                $max_id = intval( $section['id'] );
            }
        }
        $new_id = $max_id + 1;

        // Build section data
        $new_section = array(
            'id' => $new_id,
            'name' => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
            'subcategories' => isset( $data['subcategories'] ) && is_array( $data['subcategories'] ) 
                ? array_map( 'intval', $data['subcategories'] ) 
                : array(),
            'position' => isset( $data['position'] ) ? intval( $data['position'] ) : 0,
            'default_open' => isset( $data['default_open'] ) ? (bool) $data['default_open'] : false,
            'enabled' => isset( $data['enabled'] ) ? (bool) $data['enabled'] : true,
        );

        $sections[] = $new_section;
        update_option( self::OPTION_KEY, $sections );
        
        do_action( 'fsm_custom_sections_modified' );
        
        return $new_id;
    }

    /**
     * Update an existing section
     */
    public static function update_section( int $id, array $data ) : bool {
        $sections = self::get_all_sections();
        $found = false;

        foreach ( $sections as $index => $section ) {
            if ( isset( $section['id'] ) && intval( $section['id'] ) === $id ) {
                $sections[ $index ] = array(
                    'id' => $id,
                    'name' => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : $section['name'],
                    'subcategories' => isset( $data['subcategories'] ) && is_array( $data['subcategories'] ) 
                        ? array_map( 'intval', $data['subcategories'] ) 
                        : $section['subcategories'],
                    'position' => isset( $data['position'] ) ? intval( $data['position'] ) : $section['position'],
                    'default_open' => isset( $data['default_open'] ) ? (bool) $data['default_open'] : ( isset( $section['default_open'] ) ? $section['default_open'] : false ),
                    'enabled' => isset( $data['enabled'] ) ? (bool) $data['enabled'] : $section['enabled'],
                );
                $found = true;
                break;
            }
        }

        if ( $found ) {
            update_option( self::OPTION_KEY, $sections );
            do_action( 'fsm_custom_sections_modified' );
        }

        return $found;
    }

    /**
     * Delete a section
     */
    public static function delete_section( int $id ) : bool {
        $sections = self::get_all_sections();
        $initial_count = count( $sections );

        $sections = array_filter( $sections, function( $section ) use ( $id ) {
            return ! isset( $section['id'] ) || intval( $section['id'] ) !== $id;
        });

        // Re-index array
        $sections = array_values( $sections );

        if ( count( $sections ) < $initial_count ) {
            update_option( self::OPTION_KEY, $sections );
            do_action( 'fsm_custom_sections_modified' );
            return true;
        }

        return false;
    }

    /**
     * Bulk reorder sections
     * @param array $order_map Array of id => position pairs
     */
    public static function reorder_sections( array $order_map ) : bool {
        $sections = self::get_all_sections();

        foreach ( $sections as $index => $section ) {
            $id = intval( $section['id'] );
            if ( isset( $order_map[ $id ] ) ) {
                $sections[ $index ]['position'] = intval( $order_map[ $id ] );
            }
        }

        update_option( self::OPTION_KEY, $sections );
        do_action( 'fsm_custom_sections_modified' );

        return true;
    }

    /**
     * Toggle enabled status
     */
    public static function toggle_enabled( int $id ) : bool {
        $section = self::get_section( $id );
        if ( ! $section ) {
            return false;
        }

        $new_enabled = empty( $section['enabled'] );
        return self::update_section( $id, array( 'enabled' => $new_enabled ) );
    }
}
