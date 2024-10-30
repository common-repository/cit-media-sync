<?php
/*
Plugin Name: CIT Media Sync
Plugin URI: http://www.collectief-it.nl/
Description: Plugin to add files to the Media Library that were previously uploaded by FTP
Author: Paul Staring
Version: 1.0
*/


/*
 * Init new Sync function
 */
function cit_ms_sync_admin_init() {
	wp_register_script( 'cit-ms-javascript', plugins_url('/cit-ms.js', __FILE__), array('jquery') );
	wp_register_style( 'cit-ms-css', plugins_url('/cit-ms.css', __FILE__) );
}
add_action( 'admin_init', 'cit_ms_sync_admin_init' );


/*
 * Add new Sync function to backend menu
 * Also the JS and CSS that go with it
 */
function cit_ms_sync_add_media_menu(){
	$page = add_media_page( 'CIT Media Sync', 'CIT Media Sync', 'read', 'cit-ms-sync', 'cit_ms_sync_process');
	add_action('admin_print_scripts-' . $page, 'cit_ms_sync_admin_scripts');
	add_action('admin_print_styles-' . $page, 'cit_ms_sync_admin_styles');
}
function cit_ms_sync_admin_scripts() {
	wp_localize_script('cit-ms-javascript', 'cit_ms_intlobj', array(
		'img_folder_closed' => plugins_url('/images/folder.gif', __FILE__),
		'img_folder_open' => plugins_url('/images/folder.open.gif', __FILE__)
	));
	wp_enqueue_script( 'cit-ms-javascript' );
}
function cit_ms_sync_admin_styles() {
	wp_enqueue_style( 'cit-ms-css' );
}
add_action('admin_menu', 'cit_ms_sync_add_media_menu');


/*
 * Show file-tree (table) / handle processing of attaching request
 */
function cit_ms_sync_process() {
	global $blog_id;
	$wp_upload_dir = wp_upload_dir();


	// the processing...
	if (isset($_GET['idx']) && $_GET['idx']=='attach') {
		$filename = urldecode($_GET['file']);
		if(substr($filename,0,1)=='/'){
			$filename = substr($filename, 1, strlen($filename));
		}
		$wp_filetype = wp_check_filetype(basename($filename), null );
		$attachment = array(
			'guid' => $wp_upload_dir['baseurl'] . '/' . _wp_relative_upload_path( $filename ), 
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $wp_upload_dir['basedir'].'/'.$filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		$message = "File attached; new ID: ".$attach_id;
	}//eo GET-var processing


	/* Display */
	$files = cit_ms_get_files($wp_upload_dir['path']);

	echo '<div id="cit_ms_ajax_spinner"><img src="'.admin_url('images/loading.gif').'" /></div>';

	echo '<div class="wrap">'.PHP_EOL;
	echo '<h2>CIT Media Sync</h2>'.PHP_EOL;
	if(isset($message)){
		echo '<div id="message" class="updated below-h2">'.$message.'</div>'.PHP_EOL;
	}

	echo '
<table id="cit_ms_files_table" class="wp-list-table widefat fixed media" cellspacing="0">
  <thead>
    <tr>
      <th scope="col" id="icon" class="manage-column column-icon"></th>
      <th scope="col" id="path" class="manage-column column-path"><span>Path</span><span class="sorting-indicator"></span></th>
      <th scope="col" id="title" class="manage-column column-title"><span>File</span><span class="sorting-indicator"></span></th>
      <th scope="col" id="attach" class="manage-column column-attach"><span>Attach</span></th>
    </tr>
  </thead>
  <tfoot>
    <tr>
      <th scope="col" class="manage-column column-icon"></th>
      <th scope="col" class="manage-column column-path"><span>Path</span></a></th>
      <th scope="col" class="manage-column column-title"><span>File</span></a></th>
      <th scope="col" class="manage-column column-attach"><span>Attach</span></th>
    </tr>
  </tfoot>';
	if(is_array($files) && !empty($files)){
		echo '
	<tbody class="cit-ms-list">'.PHP_EOL;
		echo cit_ms_wrap_files_in_tr($files);
		echo '
	</tbody>';
	} else {
		echo '
	<tbody class="cit-ms-list">
		<tr class="no-items">
			<td class="colspanchange" colspan="4">No unattached files found.</td>
		</tr>
	</tbody>';
	}
	echo PHP_EOL.'</table>'.PHP_EOL;
}//eo function cit_ms_sync_process


/*
 * return files and directories of path
 * @usedby cit_ms_sync_process()
 */
function cit_ms_get_files($path, $checkdb=true){
	// do checks first
	if(!file_exists($path)) return false;
	if(!is_readable($path)) return false;
	if(!is_writable($path)){
		throw new Exception('Warning: path not writable!', 1);
		// return false;
	}

	$filescan = scandir($path);
	$output = false;
	if(is_array($filescan) && !empty($filescan)){
		$dirs = array();
		$files = array();
		foreach($filescan as $file){
			if($file=='.') continue;
			if($file=='..') continue;
			if(is_dir($path.'/'.$file)){
				$info = pathinfo($path.'/'.$file);
				$info['is_dir'] = true;
				$info['is_error'] = false;
				$dirs[] = $info;
			} else {
				if(cit_ms_file_exists_in_db($file, $path)) continue;
				$info = pathinfo($path.'/'.$file);
				$info['is_dir'] = false;
				$info['is_error'] = false;
				$files[] = $info;
			}
		}
		$output = array_merge($dirs, $files);
	}
	return $output;
}//eo function cit_ms_get_files


/*
 * return filename without WP image size info
 * (i.e. filename-90x60.jpg is returned as filename.jpg)
 */
function cit_ms_clip_wp_info($filename, $path){
	$info = pathinfo($path.'/'.$filename);
	$wp_upload_dir = wp_upload_dir();
	$relative_path = str_replace($wp_upload_dir, '', $info['dirname']);
	if(substr($relative_path,0,1)=='/') $relative_path = substr($relative_path, 1);

	$filename = preg_replace('/-\d{1,6}x\d{1,6}$/', '', $info['filename']);
	if(!empty($relative_path)){
		return $relative_path.'/'.$filename.'.'.$info['extension'];
	} else {
		return $filename.'.'.$info['extension'];
	}
}//eo method cit_ms_clip_wp_info


/*
 * Helper function for cit_ms_get_files: check if file exists in media library
 * $filename should be clipped (see function cit_ms_clip_wp_info)
 */
function cit_ms_file_exists_in_db($filename, $path){
	global $wpdb;
	$clipped_filename = cit_ms_clip_wp_info($filename, $path);
	$query = "SELECT COUNT(meta_id) FROM ".$wpdb->prefix."postmeta WHERE meta_key='_wp_attached_file' AND meta_value = %s";
	$exists = $wpdb->get_var( $wpdb->prepare($query, $clipped_filename) );
	return (bool)$exists;
}//eo method fileExistsInDB


/*
 * Wrap files-array in TR and TD tags
 * @usedby cit_ms_sync_process()
 * @usedby cit_ms_ajax_show_folder_content()
 */
function cit_ms_wrap_files_in_tr($files, $indent=2){
	$wp_upload_dir = wp_upload_dir();
	$output = '';

	if( is_array($files) && !empty($files) ){
		foreach($files as $file){
			$relative_path = str_replace($wp_upload_dir, '', $file['dirname']);
			$level = substr_count($relative_path, '/');
			$output .= '<tr>'.PHP_EOL;
			$output .= '	<td>';
			if($file['is_dir']){
				$output .= '<img class="folder level-'.$level.'" data-path="'.$file['dirname'].'/'.$file['filename'].'" data-unfolded="0" src="'.plugins_url('/images/folder.gif', __FILE__).'"/>';
			} elseif(cit_ms_is_image($file['basename'])){
				$output .= '<img class="file level-'.$level.'" data-path="'.$file['dirname'].'/'.$file['filename'].'" src="'.plugins_url('/images/image2.gif', __FILE__).'"/>';
			}
			$output .= '</td>'.PHP_EOL;
			$output .= '	<td>'.$relative_path.'</td>'.PHP_EOL;
			$output .= '	<td>'.$file['filename'].'</td>'.PHP_EOL;
			$output .= '	<td>';
			if(!$file['is_dir'] && !$file['is_error']) $output .= '<a href="?page=cit-ms-sync&idx=attach&file='.urlencode($relative_path.'/'.$file['basename']).'">attach</a>';
			$output .= '</td>'.PHP_EOL;
			$output .= '</tr>'.PHP_EOL;
		}
	}

	return cit_ms_indent($output, $indent);
}//eo function cit_ms_wrap_files_in_tr


function cit_ms_wrap_error_in_tr($message){
	$output = array();
	$output[0]['is_error'] = true;
	$output[0]['is_dir'] = false;
	$output[0]['basename'] = '';
	$output[0]['filename'] = '';
	$output[0]['dirname'] = $message;
	return cit_ms_wrap_files_in_tr($output);
}//eo function cit_ms_wrap_error_in_tr


function cit_ms_indent($input, $indent=0){
	$parts = explode("\n", $input);
	$output = '';
	if(is_array($parts) && !empty($parts)){
		$padding = str_repeat("\t", $indent);
		foreach($parts as $key => $part) $parts[$key] = $padding.$part;
		$output = implode("\n", $parts);
	}
	return $output;
}//eo function cit_ms_indent


/*
 * Check if file is of image type
 * @usedby cit_ms_sync_process()
 */
function cit_ms_is_image($file){
	$arr_file_type = wp_check_filetype(basename($file));
	$uploaded_file_type = $arr_file_type['type'];
	$allowed_file_types = array('image/jpg','image/jpeg','image/gif','image/png');
	return in_array($uploaded_file_type, $allowed_file_types);
}//eo function cit_ms_is_image


/*******************/
/* AJAX-processing */
/*******************/

function cit_ms_ajax_show_folder_content(){
	$path = urldecode($_GET['path']);
	try {	
		$files = cit_ms_get_files($path);
	} catch(Exception $e){
		die( cit_ms_wrap_error_in_tr($e->getMessage()) );
	}
	die(cit_ms_wrap_files_in_tr($files));
}//eo function cit_ms_ajax_show_folder_content
add_action('wp_ajax_cit_ms_show_folder_content', 'cit_ms_ajax_show_folder_content');
