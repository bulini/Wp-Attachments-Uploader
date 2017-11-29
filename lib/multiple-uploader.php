<?php
/**
* Classe x metabox allegati corso
* @author Giuseppe
*/
class MultipleUploader {

  public function __construct() {
    $this->run();
  }

  public function run() {
    add_action( 'add_meta_boxes', array( $this, 'uploader_metabox' ) ); // Add the meta box
    add_action( 'save_post', array( $this, 'multiple_uploader_save' ) ); // Save meta box data
  }

  /**
  * metabox.
  */

  public function uploader_metabox() {

    add_meta_box(
      'uploader_metabox'
      ,__( 'Attached Documents', 'multiple_uploader' )
      ,array( $this, 'multiple_uploader_meta_box_content' )
      //dinamically
      ,array('post', 'page')
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
      <li class="cpi-upload" id="">
        <input id="documenti_allegati" name="_uploaded_documents" type="hidden" value="<?php echo $docs; ?>" />
        <p class="cpi-upload-header"><span class="dashicons dashicons-admin-links"></span> Caricamento Allegati</p>
        <input type="button" class="button cpi-button multiple-uploader-button" value="<?php _e( 'Scegli Files', 'tuvali' )?>" />
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

  }

  $metabox = new MultipleUploader();
