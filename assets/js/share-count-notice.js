/* global ajaxurl, ea_share_count_notice */

'use strict';

jQuery( document ).ready(function($){

	// Save dismiss state.
	$( '.easc-notice.is-dismissible' ).on( 'click', '.notice-dismiss', function( event ) {

		event.preventDefault();

		var noticeKey = $( this ).parent().data( 'key' );

		if ( ! noticeKey ) {
			return;
		}

		$.post( ajaxurl, {
			action: 'ea_share_count_dismissible_notice',
			url:    ajaxurl,
			notice: noticeKey,
			nonce:  ea_share_count_notice.nonce || ''
		});
	});
});
