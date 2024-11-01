/**
 * Handle: shopp-improved-product-edit
 * Version: 0.0.1
 * Deps: wp-lists
 * Enqueue: true
 */

jQuery(document).ready( function() {
	// Custom Fields
	jQuery('#input-list').wpList( {
		addAfter: function( xml, s ) {
			if ( jQuery.isFunction( autosave_update_post_ID ) ) {
				//autosave_update_post_ID(s.parsed.responses[0].supplemental.postid);
			}
		},
		addBefore: function( s ) {
			s.response = 'input-ajax-response';
			s.data += '&product_id=' + jQuery('input[name=id]').val();
			return s;
		},
		delBefore: function( s ) {
			s.data.product_id = jQuery('input[name=id]').val();
			return s;
		}
	});
});
