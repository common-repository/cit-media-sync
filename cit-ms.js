jQuery(document).ready(function($){
	$('img.folder').click( cit_ms_folder_click );
	$('img.folder').dblclick( function(){return false;} );
});


var cit_ms_modal_set = false;


function cit_ms_folder_click(eventObj){
	// if first time, define size of modal
	if(!cit_ms_modal_set){
		jQuery('#cit_ms_ajax_spinner').css('height', jQuery('#wpbody').css('height'));
		jQuery('#cit_ms_ajax_spinner').css('width', jQuery('#wpbody').css('width'));
		cit_ms_modal_set = true;
	}
	// action, show spinner
	jQuery('#cit_ms_ajax_spinner').show();

	var elem = eventObj.target;
	if(elem.dataset.unfolded=='0'){
		// mark clicked element as unfolded dir (and block double requests)
		elem.dataset.unfolded = 1;

		jQuery.ajax({
			url: 'admin-ajax.php',
			data: {
				action: 'cit_ms_show_folder_content',
				path: encodeURI( elem.dataset.path )
			},
			success: function(result, textStatus){
				// change folder icon
				elem.src = cit_ms_intlobj.img_folder_open;

				// add result (new table rows) after clicked element
				var rowIndex = elem.parentElement.parentElement.rowIndex;
				jQuery('#cit_ms_files_table > tbody > tr:nth-child(' + rowIndex + ')').after(result);

				// remove old click events
				jQuery('img.folder').unbind('click');
				// give each folder-icon a click event handler
				jQuery('img.folder').click( cit_ms_folder_click );
				// ready, hide spinner
				jQuery('#cit_ms_ajax_spinner').hide();
			}
		});
	} else {
		// remove all underlying rows
		cit_ms_remove_row_by_path(elem.dataset.path);
		// mark clicked element as folded dir again
		elem.dataset.unfolded = 0;
		// change folder icon
		elem.src = cit_ms_intlobj.img_folder_closed;
		// ready, hide spinner
		jQuery('#cit_ms_ajax_spinner').hide();
	}
}//eo function cit_ms_folder_click


function cit_ms_remove_row_by_path(searchPath){
	searchPath += '/';
	var rows = jQuery('#cit_ms_files_table img');
	for(var i=0; i<rows.length; i++){
		if(searchPath==rows[i].dataset.path.substring(0,searchPath.length)){
			jQuery(rows[i].parentElement.parentElement).remove();
		}
	}
}//eo function cit_ms_remove_row_by_path

