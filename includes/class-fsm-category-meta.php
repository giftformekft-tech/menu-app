<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FSM_Category_Meta {

    public static function init() : void {
        add_action( 'product_cat_add_form_fields', array( __CLASS__, 'add_form_fields' ) );
        add_action( 'product_cat_edit_form_fields', array( __CLASS__, 'edit_form_fields' ), 10, 1 );
        add_action( 'created_product_cat', array( __CLASS__, 'save_fields' ) );
        add_action( 'edited_product_cat', array( __CLASS__, 'save_fields' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_media' ) );
    }

    public static function enqueue_media() : void {
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'edit-product_cat' ) {
            wp_enqueue_media();
        }
    }

    public static function add_form_fields() : void {
        ?>
        <div class="form-field">
            <label for="fsm_category_icon">Kategória ikon (PNG)</label>
            <input type="hidden" id="fsm_category_icon" name="fsm_category_icon" value="" />
            <div id="fsm_icon_preview" style="margin: 10px 0;"></div>
            <button type="button" class="button" id="fsm_upload_icon_button">Ikon feltöltése</button>
            <button type="button" class="button" id="fsm_remove_icon_button" style="display:none;">Ikon eltávolítása</button>
            <p class="description">Kis PNG ikon, amely a kategória neve előtt jelenik meg (max. 48x48px ajánlott).</p>
        </div>
        <script>
        jQuery(document).ready(function($){
            var mediaUploader;
            $('#fsm_upload_icon_button').on('click', function(e){
                e.preventDefault();
                if(mediaUploader){ mediaUploader.open(); return; }
                mediaUploader = wp.media({
                    title: 'Válassz ikont',
                    button: { text: 'Ikon használata' },
                    multiple: false,
                    library: { type: 'image' }
                });
                mediaUploader.on('select', function(){
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#fsm_category_icon').val(attachment.id);
                    $('#fsm_icon_preview').html('<img src="'+attachment.url+'" style="max-width:80px;max-height:80px;" />');
                    $('#fsm_remove_icon_button').show();
                });
                mediaUploader.open();
            });
            $('#fsm_remove_icon_button').on('click', function(e){
                e.preventDefault();
                $('#fsm_category_icon').val('');
                $('#fsm_icon_preview').html('');
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public static function edit_form_fields( $term ) : void {
        $icon_id = get_term_meta( $term->term_id, 'fsm_category_icon', true );
        $icon_url = '';
        if ( $icon_id ) {
            $icon_url = wp_get_attachment_url( $icon_id );
        }
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="fsm_category_icon">Kategória ikon (PNG)</label>
            </th>
            <td>
                <input type="hidden" id="fsm_category_icon" name="fsm_category_icon" value="<?php echo esc_attr( $icon_id ); ?>" />
                <div id="fsm_icon_preview" style="margin: 10px 0;">
                    <?php if ( $icon_url ) : ?>
                        <img src="<?php echo esc_url( $icon_url ); ?>" style="max-width:80px;max-height:80px;" />
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="fsm_upload_icon_button">Ikon feltöltése</button>
                <button type="button" class="button" id="fsm_remove_icon_button" style="<?php echo $icon_url ? '' : 'display:none;'; ?>">Ikon eltávolítása</button>
                <p class="description">Kis PNG ikon, amely a kategória neve előtt jelenik meg (max. 48x48px ajánlott).</p>
            </td>
        </tr>
        <script>
        jQuery(document).ready(function($){
            var mediaUploader;
            $('#fsm_upload_icon_button').on('click', function(e){
                e.preventDefault();
                if(mediaUploader){ mediaUploader.open(); return; }
                mediaUploader = wp.media({
                    title: 'Válassz ikont',
                    button: { text: 'Ikon használata' },
                    multiple: false,
                    library: { type: 'image' }
                });
                mediaUploader.on('select', function(){
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#fsm_category_icon').val(attachment.id);
                    $('#fsm_icon_preview').html('<img src="'+attachment.url+'" style="max-width:80px;max-height:80px;" />');
                    $('#fsm_remove_icon_button').show();
                });
                mediaUploader.open();
            });
            $('#fsm_remove_icon_button').on('click', function(e){
                e.preventDefault();
                $('#fsm_category_icon').val('');
                $('#fsm_icon_preview').html('');
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public static function save_fields( $term_id ) : void {
        if ( isset( $_POST['fsm_category_icon'] ) ) {
            $icon_id = intval( $_POST['fsm_category_icon'] );
            if ( $icon_id > 0 ) {
                update_term_meta( $term_id, 'fsm_category_icon', $icon_id );
            } else {
                delete_term_meta( $term_id, 'fsm_category_icon' );
            }
        }
    }

    public static function get_category_icon( $term_id ) : string {
        $icon_id = get_term_meta( $term_id, 'fsm_category_icon', true );
        if ( $icon_id ) {
            $icon_url = wp_get_attachment_url( $icon_id );
            if ( $icon_url ) {
                return $icon_url;
            }
        }
        return '';
    }
}
