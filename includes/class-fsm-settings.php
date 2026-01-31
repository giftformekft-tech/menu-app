<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Settings {

    const OPTION_KEY = 'fsm_options';

    public static function get_all() : array {
        $opt = get_option( self::OPTION_KEY, array() );
        return is_array( $opt ) ? $opt : array();
    }

    public static function get_bool( string $key, bool $default = false ) : bool {
        $all = self::get_all();
        if ( ! array_key_exists( $key, $all ) ) return $default;
        return (bool) $all[ $key ];
    }

    public static function get_int( string $key, int $default = 0 ) : int {
        $all = self::get_all();
        if ( ! array_key_exists( $key, $all ) ) return $default;
        return intval( $all[ $key ] );
    }

    public static function get_string( string $key, string $default = '' ) : string {
        $all = self::get_all();
        if ( ! array_key_exists( $key, $all ) ) return $default;
        return is_string( $all[ $key ] ) ? $all[ $key ] : $default;
    }

    public static function update( array $new ) : void {
        update_option( self::OPTION_KEY, $new );
    }
}
