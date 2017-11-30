<?php
/**
* MultiUploader for any post type
* @author Giuseppe
*/
class MultipleUploader {

  public function __construct() {
    $this->run();
  }

  public function run() {
    add_action( 'add_meta_boxes', array( $this, 'uploader_metabox' ) ); // Add the meta box
    add_action( 'save_post', array( $this, 'multiple_uploader_save' ) ); // Save meta box data
    add_action( 'admin_menu', array( $this, 'multiple_uploader_create_menu') );
    add_filter( 'the_content', array( $this, 'my_the_content_filter'),20 );
    add_filter( 'admin_footer_text',  array( $this, 'custom_footer_admin') );
  }

  /**
   * [multiple_uploader_create_menu description]
   * @return [type] [description]
   */
  public function multiple_uploader_create_menu() {
    //create new top-level menu
    add_menu_page('Multiple Uploader Settings', 'Attachments', 'administrator', __FILE__, array($this,'multiple_uploader_settings_page') , 'dashicons-admin-links' );
    add_action( 'admin_init', array( $this, 'register_multiple_uploader_settings' ) );
  }

  /**
   * [register_multiple_uploader_settings description]
   * @return [type] [description]
   */
  function register_multiple_uploader_settings() {
 
    register_setting( 'multiple-uploader-settings-group', '_uploader_display_mode' );
    register_setting( 'multiple-uploader-settings-group', '_uploader_post_types' );
    register_setting( 'multiple-uploader-settings-group', '_uploader_post_widget' );

  }

  public function multiple_uploader_settings_page() { 
    $post_types = get_post_types();
    $enabled_post_types = get_option('_uploader_post_types');
    ?>

    <div class="wrap">
    <h1><span class="dashicons dashicons-admin-links"></span> Multiple Attachments Uploader</h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'multiple-uploader-settings-group' ); ?>
        <?php do_settings_sections( 'multiple-uploader-settings-group' ); ?>
        <table class="form-table">
   
            <tr valign="top">
              <th scope="col"><?php _e( 'Display Mode', 'wp-attachments-uploader' ); ?><p><small>How to show attachments</small></p></th>
              <td>
                <ul>
                  <li><input type="radio" name="_uploader_display_mode" value="shortcode" /><?php _e( 'Only with Shortcode <code>[wp-attachments]</code>', 'wp-attachments-uploader' ); ?></li>     
                  <li><input type="radio" name="_uploader_display_mode" value="before_content" /><?php _e( 'Before Content', 'wp-attachments-uploader' ); ?></li>     
                  <li><input type="radio" name="_uploader_display_mode" value="after_content" /><?php _e( 'After Content', 'wp-attachments-uploader' ); ?></li>     
                </ul>
              </td>
            </tr>

            <tr valign="top">
              <th scope="col">Post Types<p><small>Choose which post types need the attachments box enabled</small></p></th>
              <td>
                <ul>
                <?php
                foreach ( get_post_types(array('public' => true), 'names' ) as $post_type ) { 
                  $selected = in_array($post_type, $enabled_post_types) ? 'checked' : '';
                  ?>
                  <li><input type="checkbox" name="_uploader_post_types[]" value="<?php echo $post_type;?>" <?php echo $selected; ?> /><?php echo $post_type;?></li>
                <?php }
                ?>        
                </ul>
              </td>
            </tr>

            <tr valign="top">
              <th scope="col">Sidebar Widget</th>
              <td>
                <ul>
                  <li><input type="radio" name="_uploader_post_widget" value="1" /> Enabled</li>     
                  <li><input type="radio" name="_uploader_post_widget" value="0" /> Disabled</li>     
                </ul>
              </td>
            </tr>


        </table>
        
        <?php submit_button(); ?>

    </form>
    </div>
  <?php 
  } 

  /**
   * [uploader_metabox description]
   * @return [type] [description]
   */
  public function uploader_metabox() {
    $enabled_post_types = get_option('_uploader_post_types');
    add_meta_box(
      'uploader_metabox'
      ,__( 'Attached Documents', 'wp-attachments-uploader' )
      ,array( $this, 'multiple_uploader_meta_box_content' )
      //dinamically
      ,$enabled_post_types
      ,'advanced'
      ,'high'
    );
  }

  /**
  * Render Meta Box content.
  *
  * @param WP_Post $post The post object.
  */

  public function multiple_uploader_meta_box_content( $post ) {

    wp_nonce_field( basename( __FILE__ ), 'multiple_uploader_nonce' );
    $docs = get_post_meta( $_GET['post'], '_uploaded_documents', true );
    if( !$docs ) $docs = "[]"; 

  ?>

    <ul id="multiple_uploader">
      <li class="multiple-upload" id="">
        <input id="documenti_allegati" name="_uploaded_documents" type="hidden" value="<?php echo $docs; ?>" />
        <p class="multiple-upload-header"><span class="dashicons dashicons-admin-links"></span> Caricamento Allegati</p>
        <input type="button" class="button multiple-button multiple-uploader-button" value="<?php _e( 'Scegli Files', 'tuvali' )?>" />
      </li>
    </ul>

    <ul id="docs-list">

    </ul>

    <script>
    jQuery(document).ready(function($){

      var iconfolder = '<?php echo  UPLOADER_ABSOLUTE_URL.'assets/img/icons/filetypes/'; ?>';
      var custom_uploader;
      var allegati = $('#documenti_allegati').val();
      console.log(allegati);
      _docs = (allegati != '' ? JSON.parse(allegati) : []);


        $('.multiple-uploader-button').click(function(e) {
          e.preventDefault();
          //If the uploader object has already been created, reopen the dialog
          if (custom_uploader) {
            custom_uploader.open();
            return;
          }
          //Extend the wp.media object
          custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Inserisci allegati',
            button: {
              text: 'Inserisci files'
            },
            multiple: true
          });
          custom_uploader.on('select', function() {
            var selection = custom_uploader.state().get('selection');
            selection.map( function( attachment ) {
              attachment = attachment.toJSON();
              _docs.push(attachment.id);

              fileType = attachment.filename.split('.').pop();
              background_icon = iconfolder + fileType + ".png";

              $("#docs-list").append("<li><input data-file-id='"+attachment.id+"' type='button' class='button cpi-button rimuovi-allegato' value='&#215;' /><img src='"+background_icon+"'>" +attachment.url+ "</li>");
              console.log(_docs);
              $("#documenti_allegati").val(JSON.stringify(_docs))
            });
          });
          custom_uploader.open();
        });
        //rimozione
        $('#docs-list').on( "click",'.rimuovi-allegato', function() {
          file_id = $(this).data('file-id');
          _docs = _.without(_docs, file_id);
          console.log( "elimina Documento", file_id );
          $('#documenti_allegati').val( JSON.stringify(_docs));
          $(this).closest('li').remove();
        });

      <?php
      //appendo in js gli attachment presenti
      foreach(json_decode($docs) as $doc) {
        $file_url = wp_get_attachment_url( $doc );
        $filetype = wp_check_filetype( $file_url );
      ?>
        $("#docs-list").append("<li><input data-file-id='<?php echo $doc; ?>' type='button' class='button cpi-button rimuovi-allegato' value='&#215;' /><img src='"+iconfolder+"<?php echo $filetype["ext"]; ?>.png' /><?php echo wp_get_attachment_url($doc); ?></li>");
      <?php } ?>

      });
    </script>
      <?php
    }

    /**
    * Save the meta when the post is saved.
    *
    * @param int $post_id The ID of the post being saved.
    */

    public function multiple_uploader_save( $post_id ) {

      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
      if ( !wp_verify_nonce( $_POST['multiple_uploader_nonce'], basename( __FILE__ ) ) ) return;
      if ( 'page' == $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_page', $post_id ) ) return;
      } else {
        if ( !current_user_can( 'edit_post', $post_id ) ) return;
      }
      $docs = $_POST['_uploaded_documents'];
      update_post_meta( $post_id, '_uploaded_documents', $docs );

    }

    /**
     * Add a icon to the beginning of every post page.
     *
     * @uses is_single()
     */
    public function my_the_content_filter( $content ) {

        if ( is_single() ) {
          // Add image to the beginning of each page
          $aftercontent = $this->display_attachments();
          $fullcontent = $content.$aftercontent;
          
        }

        // Returns the content.
        return $fullcontent;
    }

    /**
     * [display an ul with files]
     * @return [type] [description]
     */
    public function display_attachments() {
      global $post;
      $docs = get_post_meta( $post->ID, '_uploaded_documents', true );
      
      if( $docs ) {

        $html = '<h4>'.__('Download Attachments','wp-attachments-uploader').'</h4>';

        $html.='<ul style="list-style-type: none;">';
        
        foreach(json_decode($docs) as $doc) {
          $file_url = wp_get_attachment_url( $doc );
          $filetype = wp_check_filetype( $file_url );
          $html.='<li><a href="#"><img src="'.UPLOADER_ABSOLUTE_URL.'/assets/img/icons/filetypes/'.$filetype["ext"].'.png" />'.get_the_title( $doc  ).'</a></li>';
         }
      
        $html.='</ul>';

      return $html;    
      } 


    }

    public function custom_footer_admin () {

      echo 'Powered by <a href="http://www.wordpress.org" target="_blank">WordPress</a> | Attachments plugin by <a href="https://www.giuseppesurace.com" target="_blank"><img src="'.UPLOADER_ABSOLUTE_URL.'assets/img/GS_logo.png" width="24" /></a> </p>';
    }
 


  }

  $metabox = new MultipleUploader();
