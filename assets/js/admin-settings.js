'use strict';

jQuery( document ).ready(function($){

	// Initialize select2 for service selection.
	$( '.share-count-services' ).select2({
		placeholder: 'Select service(s)',
		allowClear:  true
	});

	// Hack, will likely be removed.
	$( '.share-count-services' ).on( 'change', function(){
		var data = $(this).select2(' data' );
		var array = [];
		$.each( data, function( index, val ) {
			array[ index ] = val.id;
		});
		array.join( ',' );
		$( '.share-count-services-raw' ).val( array );
	});

	// Will likely be refactored.
	$( '.ea-share-count-services-check' ).change( function() {
		var $this = $( this ),
			key   = $this.data( 'key' );
		if ( $this.is( ':checked' ) ) {
			$( '#ea-service-note-'+key ).show();
		} else {
			$( '#ea-service-note-'+key ).hide();
		}
	});
});
