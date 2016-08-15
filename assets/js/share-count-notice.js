jQuery(document).ready(function($){

	// Save dismiss state
	$( '.easc-notice.is-dismissible' ).on('click', '.notice-dismiss', function ( event ) {
		event.preventDefault();
		var $this = $(this);
		if( ! $this.parent().data( 'key' ) ){
			return;
		}
		$.post( ajaxurl, {
			action: "ea_share_count_dismissible_notice",
			url: ajaxurl,
			notice: $this.parent().data( 'key' ),
			nonce: ea_share_count_notice.nonce || ''
		});

	});

});