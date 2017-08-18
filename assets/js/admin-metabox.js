/* global ajaxurl */

'use strict';

jQuery( document ).ready(function($){

	// Refresh share counts button.
	$( document ).on( 'click', '#ea-share-count-refresh', function( event ){

		event.preventDefault();

		var $this = $( this ),
			data  = {
				post_id: $this.data( 'postid' ),
				nonce:   $this.data( 'nonce' ),
				action:  'ea_share_refresh'
			};

		// Disable refresh button and change text.
		$this.text( 'Loading share counts...' ).prop( 'disabled',true );

		// AJAX post to fetch updated counts.
		$.post( ajaxurl, data, function( res ) {

			if ( res.success ) {
				$( '#ea-share-count-msg, #ea-share-count-list, #ea-share-count-date, #ea-share-count-empty' ).remove();
				$( '#ea-share-count-metabox .inside' ).prepend( res.data.date ).prepend( res.data.list ).prepend( '<p id="ea-share-count-msg" class="'+res.data.class+'">'+res.data.msg+'</p>' );
			} else {
				$( '#ea-share-count-msg' ).remove();
				$( '#ea-share-count-metabox .inside' ).prepend( '<p id="ea-share-count-msg" class="'+res.data.class+'">'+res.data.msg+'</p>' );
			}

			// Enable refresh button and change text.
			$this.text( 'Refresh Share Counts' ).prop( 'disabled',false );

		}).fail( function( xhr ) {
			console.log( xhr.responseText );
		});
	});
});
