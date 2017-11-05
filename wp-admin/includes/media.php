<?php
/**
 * WordPress Administration Media API.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Defines the default media upload tabs
 *
 * @since 2.5.0
 *
 * @return array default tabs
 */
function media_upload_tabs() {
	$_default_tabs = array(
		'type' => __('From Computer'), // handler action suffix => tab text
		'type_url' => __('From URL'),
		'gallery' => __('Gallery'),
		'library' => __('Media Library')
	);

	/**
	 * Filters the available tabs in the legacy (pre-3.5.0) media popup.
	 *
	 * @since 2.5.0
	 *
	 * @param array $_default_tabs An array of media tabs.
	 */
	return apply_filters( 'media_upload_tabs', $_default_tabs );
}

/**
 * Adds the gallery tab back to the tabs array if post has image attachments
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $tabs
 * @return array $tabs with gallery if post has image attachment
 */
function update_gallery_tab($tabs) {
	global $wpdb;

	if ( !isset($_REQUEST['post_id']) ) {
		unset($tabs['gallery']);
		return $tabs;
	}

	$post_id = intval($_REQUEST['post_id']);

	if ( $post_id )
		$attachments = intval( $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent = %d", $post_id ) ) );

	if ( empty($attachments) ) {
		unset($tabs['gallery']);
		return $tabs;
	}

	$tabs['gallery'] = sprintf(__('Gallery (%s)'), "<span id='attachments-count'>$attachments</span>");

	return $tabs;
}

/**
 * Outputs the legacy media upload tabs UI.
 *
 * @since 2.5.0
 *
 * @global string $redir_tab
 */
function the_media_upload_tabs() {
	global $redir_tab;
	$tabs = media_upload_tabs();
	$default = 'type';

	if ( !empty($tabs) ) {
		echo "<ul id='sidemenu'>\n";
		if ( isset($redir_tab) && array_key_exists($redir_tab, $tabs) ) {
			$current = $redir_tab;
		} elseif ( isset($_GET['tab']) && array_key_exists($_GET['tab'], $tabs) ) {
			$current = $_GET['tab'];
		} else {
			/** This filter is documented in wp-admin/media-upload.php */
			$current = apply_filters( 'media_upload_default_tab', $default );
		}

		foreach ( $tabs as $callback => $text ) {
			$class = '';

			if ( $current == $callback )
				$class = " class='current'";

			$href = add_query_arg(array('tab' => $callback, 's' => false, 'paged' => false, 'post_mime_type' => false, 'm' => false));
			$link = "<a href='" . esc_url($href) . "'$class>$text</a>";
			echo "\t<li id='" . esc_attr("tab-$callback") . "'>$link</li>\n";
		}
		echo "</ul>\n";
	}
}

/**
 * Retrieves the image HTML to send to the editor.
 *
 * @since 2.5.0
 *
 * @param int          $id      Image attachment id.
 * @param string       $caption Image caption.
 * @param string       $title   Image title attribute.
 * @param string       $align   Image CSS alignment property.
 * @param string       $url     Optional. Image src URL. Default empty.
 * @param bool|string  $rel     Optional. Value for rel attribute or whether to add a default value. Default false.
 * @param string|array $size    Optional. Image size. Accepts any valid image size, or an array of width
 *                              and height values in pixels (in that order). Default 'medium'.
 * @param string       $alt     Optional. Image alt attribute. Default empty.
 * @return string The HTML output to insert into the editor.
 */
function get_image_send_to_editor( $id, $caption, $title, $align, $url = '', $rel = false, $size = 'medium', $alt = '' ) {

	$html = get_image_tag( $id, $alt, '', $align, $size );

	if ( $rel ) {
		if ( is_string( $rel ) ) {
			$rel = ' rel="' . esc_attr( $rel ) . '"';
		} else {
			$rel = ' rel="attachment wp-att-' . intval( $id ) . '"';
		}
	} else {
		$rel = '';
	}

	if ( $url )
		$html = '<a href="' . esc_attr( $url ) . '"' . $rel . '>' . $html . '</a>';

	/**
	 * Filters the image HTML markup to send to the editor when inserting an image.
	 *
	 * @since 2.5.0
	 *
	 * @param string       $html    The image HTML markup to send.
	 * @param int          $id      The attachment id.
	 * @param string       $caption The image caption.
	 * @param string       $title   The image title.
	 * @param string       $align   The image alignment.
	 * @param string       $url     The image source URL.
	 * @param string|array $size    Size of image. Image size or array of width and height values
	 *                              (in that order). Default 'medium'.
	 * @param string       $alt     The image alternative, or alt, text.
	 */
	$html = apply_filters( 'image_send_to_editor', $html, $id, $caption, $title, $align, $url, $size, $alt );

	return $html;
}

/**
 * Adds image shortcode with caption to editor
 *
 * @since 2.6.0
 *
 * @param string $html
 * @param integer $id
 * @param string $caption image caption
 * @param string $title image title attribute
 * @param string $align image css alignment property
 * @param string $url image src url
 * @param string $size image size (thumbnail, medium, large, full or added with add_image_size() )
 * @param string $alt image alt attribute
 * @return string
 */
function image_add_caption( $html, $id, $caption, $title, $align, $url, $size, $alt = '' ) {

	/**
	 * Filters the caption text.
	 *
	 * Note: If the caption text is empty, the caption shortcode will not be appended
	 * to the image HTML when inserted into the editor.
	 *
	 * Passing an empty value also prevents the {@see 'image_add_caption_shortcode'}
	 * Filters from being evaluated at the end of image_add_caption().
	 *
	 * @since 4.1.0
	 *
	 * @param string $caption The original caption text.
	 * @param int    $id      The attachment ID.
	 */
	$caption = apply_filters( 'image_add_caption_text', $caption, $id );

	/**
	 * Filters whether to disable captions.
	 *
	 * Prevents image captions from being appended to image HTML when inserted into the editor.
	 *
	 * @since 2.6.0
	 *
	 * @param bool $bool Whether to disable appending captions. Returning true to the filter
	 *                   will disable captions. Default empty string.
	 */
	if ( empty($caption) || apply_filters( 'disable_captions', '' ) )
		return $html;

	$id = ( 0 < (int) $id ) ? 'attachment_' . $id : '';

	if ( ! preg_match( '/width=["\']([0-9]+)/', $html, $matches ) )
		return $html;

	$width = $matches[1];

	$caption = str_replace( array("\r\n", "\r"), "\n", $caption);
	$caption = preg_replace_callback( '/<[a-zA-Z0-9]+(?: [^<>]+>)*/', '_cleanup_image_add_caption', $caption );

	// Convert any remaining line breaks to <br>.
	$caption = preg_replace( '/[ \n\t]*\n[ \t]*/', '<br />', $caption );

	$html = preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\s?/', '$1', $html );
	if ( empty($align) )
		$align = 'none';

	$shcode = '[caption id="' . $id . '" align="align' . $align	. '" width="' . $width . '"]' . $html . ' ' . $caption . '[/caption]';

	/**
	 * Filters the image HTML markup including the caption shortcode.
	 *
	 * @since 2.6.0
	 *
	 * @param string $shcode The image HTML markup with caption shortcode.
	 * @param string $html   The image HTML markup.
	 */
	return apply_filters( 'image_add_caption_shortcode', $shcode, $html );
}

/**
 * Private preg_replace callback used in image_add_caption()
 *
 * @access private
 * @since 3.4.0
 */
function _cleanup_image_add_caption( $matches ) {
	// Remove any line breaks from inside the tags.
	return preg_replace( '/[\r\n\t]+/', ' ', $matches[0] );
}

/**
 * Adds image html to editor
 *
 * @since 2.5.0
 *
 * @param string $html
 */
function media_send_to_editor($html) {
?>
<script type="text/javascript">
var win = window.dialogArguments || opener || parent || top;
win.send_to_editor( <?php echo wp_json_encode( $html ); ?> );
</script>
<?php
	exit;
}

/**
 * Save a file submitted from a POST request and create an attachment post for it.
 *
 * @since 2.5.0
 *
 * @param string $file_id   Index of the `$_FILES` array that the file was sent. Required.
 * @param int    $post_id   The post ID of a post to attach the media item to. Required, but can
 *                          be set to 0, creating a media item that has no relationship to a post.
 * @param array  $post_data Overwrite some of the attachment. Optional.
 * @param array  $overrides Override the wp_handle_upload() behavior. Optional.
 * @return int|WP_Error ID of the attachment or a WP_Error object on failure.
 */
function media_handle_upload($file_id, $post_id, $post_data = array(), $overrides = array( 'test_form' => false )) {

	$time = current_time('mysql');
	if ( $post = get_post($post_id) ) {
		if ( substr( $post->post_date, 0, 4 ) > 0 )
			$time = $post->post_date;
	}

	$file = wp_handle_upload($_FILES[$file_id], $overrides, $time);

	if ( isset($file['error']) )
		return new WP_Error( 'upload_error', $file['error'] );

	$name = $_FILES[$file_id]['name'];
	$ext  = pathinfo( $name, PATHINFO_EXTENSION );
	$name = wp_basename( $name, ".$ext" );

	$url = $file['url'];
	$type = $file['type'];
	$file = $file['file'];
	$title = sanitize_text_field( $name );
	$content = '';
	$excerpt = '';

	if ( preg_match( '#^audio#', $type ) ) {
		$meta = wp_read_audio_metadata( $file );

		if ( ! empty( $meta['title'] ) ) {
			$title = $meta['title'];
		}

		if ( ! empty( $title ) ) {

			if ( ! empty( $meta['album'] ) && ! empty( $meta['artist'] ) ) {
				/* translators: 1: audio track title, 2: album title, 3: artist name */
				$content .= sprintf( __( '"%1$s" from %2$s by %3$s.' ), $title, $meta['album'], $meta['artist'] );
			} elseif ( ! empty( $meta['album'] ) ) {
				/* translators: 1: audio track title, 2: album title */
				$content .= sprintf( __( '"%1$s" from %2$s.' ), $title, $meta['album'] );
			} elseif ( ! empty( $meta['artist'] ) ) {
				/* translators: 1: audio track title, 2: artist name */
				$content .= sprintf( __( '"%1$s" by %2$s.' ), $title, $meta['artist'] );
			} else {
				/* translators: 1: audio track title */
				$content .= sprintf( __( '"%s".' ), $title );
			}

		} elseif ( ! empty( $meta['album'] ) ) {

			if ( ! empty( $meta['artist'] ) ) {
				/* translators: 1: audio album title, 2: artist name */
				$content .= sprintf( __( '%1$s by %2$s.' ), $meta['album'], $meta['artist'] );
			} else {
				$content .= $meta['album'] . '.';
			}

		} elseif ( ! empty( $meta['artist'] ) ) {

			$content .= $meta['artist'] . '.';

		}

		if ( ! empty( $meta['year'] ) ) {
			/* translators: Audio file track information. 1: Year of audio track release */
			$content .= ' ' . sprintf( __( 'Released: %d.' ), $meta['year'] );
		}

		if ( ! empty( $meta['track_number'] ) ) {
			$track_number = explode( '/', $meta['track_number'] );
			if ( isset( $track_number[1] ) ) {
				/* translators: Audio file track information. 1: Audio track number, 2: Total audio tracks */
				$content .= ' ' . sprintf( __( 'Track %1$s of %2$s.' ), number_format_i18n( $track_number[0] ), number_format_i18n( $track_number[1] ) );
			} else {
				/* translators: Audio file track information. 1: Audio track number */
				$content .= ' ' . sprintf( __( 'Track %1$s.' ), number_format_i18n( $track_number[0] ) );
			}
		}

		if ( ! empty( $meta['genre'] ) ) {
			/* translators: Audio file genre information. 1: Audio genre name */
			$content .= ' ' . sprintf( __( 'Genre: %s.' ), $meta['genre'] );
		}

	// Use image exif/iptc data for title and caption defaults if possible.
	} elseif ( 0 === strpos( $type, 'image/' ) && $image_meta = @wp_read_image_metadata( $file ) ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = $image_meta['title'];
		}

		if ( trim( $image_meta['caption'] ) ) {
			$excerpt = $image_meta['caption'];
		}
	}

	// Construct the attachment array
	$attachment = array_merge( array(
		'post_mime_type' => $type,
		'guid' => $url,
		'post_parent' => $post_id,
		'post_title' => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
	), $post_data );

	// This should never be set as it would then overwrite an existing attachment.
	unset( $attachment['ID'] );

	// Save the data
	$id = wp_insert_attachment($attachment, $file, $post_id);
	if ( !is_wp_error($id) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	}

	return $id;

}

/**
 * Handles a side-loaded file in the same way as an uploaded file is handled by media_handle_upload().
 *
 * @since 2.6.0
 *
 * @param array  $file_array Array similar to a `$_FILES` upload array.
 * @param int    $post_id    The post ID the media is associated with.
 * @param string $desc       Optional. Description of the side-loaded file. Default null.
 * @param array  $post_data  Optional. Post data to override. Default empty array.
 * @return int|object The ID of the attachment or a WP_Error on failure.
 */
function media_handle_sideload( $file_array, $post_id, $desc = null, $post_data = array() ) {
	$overrides = array('test_form'=>false);

	$time = current_time( 'mysql' );
	if ( $post = get_post( $post_id ) ) {
		if ( substr( $post->post_date, 0, 4 ) > 0 )
			$time = $post->post_date;
	}

	$file = wp_handle_sideload( $file_array, $overrides, $time );
	if ( isset($file['error']) )
		return new WP_Error( 'upload_error', $file['error'] );

	$url = $file['url'];
	$type = $file['type'];
	$file = $file['file'];
	$title = preg_replace('/\.[^.]+$/', '', basename($file));
	$content = '';

	// Use image exif/iptc data for title and caption defaults if possible.
	if ( $image_meta = @wp_read_image_metadata($file) ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
			$title = $image_meta['title'];
		if ( trim( $image_meta['caption'] ) )
			$content = $image_meta['caption'];
	}

	if ( isset( $desc ) )
		$title = $desc;

	// Construct the attachment array.
	$attachment = array_merge( array(
		'post_mime_type' => $type,
		'guid' => $url,
		'post_parent' => $post_id,
		'post_title' => $title,
		'post_content' => $content,
	), $post_data );

	// This should never be set as it would then overwrite an existing attachment.
	unset( $attachment['ID'] );

	// Save the attachment metadata
	$id = wp_insert_attachment($attachment, $file, $post_id);
	if ( !is_wp_error($id) )
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

	return $id;
}

/**
 * Adds the iframe to display content for the media upload page
 *
 * @since 2.5.0
 *
 * @global int $body_id
 *
 * @param string|callable $content_func
 */
function wp_iframe($content_func /* ... */) {
	_wp_admin_html_begin();
?>
<title><?php bloginfo('name') ?> &rsaquo; <?php _e('Uploads'); ?> &#8212; <?php _e('WordPress'); ?></title>
<?php

wp_enqueue_style( 'colors' );
// Check callback name for 'media'
if ( ( is_array( $content_func ) && ! empty( $content_func[1] ) && 0 === strpos( (string) $content_func[1], 'media' ) )
	|| ( ! is_array( $content_func ) && 0 === strpos( $content_func, 'media' ) ) )
	wp_enqueue_style( 'deprecated-media' );
wp_enqueue_style( 'ie' );
?>
<script type="text/javascript">
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>', pagenow = 'media-upload-popup', adminpage = 'media-upload-popup',
isRtl = <?php echo (int) is_rtl(); ?>;
</script>
<?php
	/** This action is documented in wp-admin/admin-header.php */
	do_action( 'admin_enqueue_scripts', 'media-upload-popup' );

	/**
	 * Fires when admin styles enqueued for the legacy (pre-3.5.0) media upload popup are printed.
	 *
	 * @since 2.9.0
	 */
	do_action( 'admin_print_styles-media-upload-popup' );

	/** This action is documented in wp-admin/admin-header.php */
	do_action( 'admin_print_styles' );

	/**
	 * Fires when admin scripts enqueued for the legacy (pre-3.5.0) media upload popup are printed.
	 *
	 * @since 2.9.0
	 */
	do_action( 'admin_print_scripts-media-upload-popup' );

	/** This action is documented in wp-admin/admin-header.php */
	do_action( 'admin_print_scripts' );

	/**
	 * Fires when scripts enqueued for the admin header for the legacy (pre-3.5.0)
	 * media upload popup are printed.
	 *
	 * @since 2.9.0
	 */
	do_action( 'admin_head-media-upload-popup' );

	/** This action is documented in wp-admin/admin-header.php */
	do_action( 'admin_head' );

if ( is_s