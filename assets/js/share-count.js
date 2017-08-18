/* global ajaxurl, easc */

'use strict';

jQuery( document ).ready(function($){

	var easc_id,
		easc_nonce;

	// Share button click.
	$( document ).on( 'click', '.ea-share-count-button[target="_blank"]:not(.no-js)', function( event ) {

		event.preventDefault();

		var window_size = '',
			url         = this.href,
			domain      = url.split("/")[2];

		switch ( domain ) {
			case 'www.facebook.com':
				window_size = 'width=585,height=368';
				break;
			case 'twitter.com':
				window_size = 'width=585,height=261';
				break;
			case 'plus.google.com':
				window_size = 'width=517,height=511';
				break;
			case 'pinterest.com':
				window_size = 'width=750,height=550';
				break;
			default:
				window_size = 'width=585,height=515';
		}
		window.open( url, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,' + window_size );

		$( this ).trigger( 'ea-share-click' );
	});

	// Email share button, opens email modal.
	$( document ).on( 'click', 'a[href*="#ea-share-count-email"]', function( event ) {

		event.preventDefault();

		// Show modal and focus on first field.
		$('#easc-modal-wrap').fadeIn();
		$('#easc-modal-recipient').focus();

		// Set data needed to send.
		easc_id    = $( this ).data( 'postid' );
		easc_nonce = $( this ).data( 'nonce' );
	});

	// Close email modal.
	$( document ).on( 'click', '#easc-modal-close', function( event ) {

		event.preventDefault();

		// Close modal and hide text indicating email was sent for future emails.
		$( '#easc-modal-wrap' ).fadeOut();
		$( '#easc-modal-sent' ).hide();
	});

	// Submit email share via email modal.
	$( document ).on( 'click', '#easc-modal-submit', function( event ) {

		event.preventDefault();

		var empty       = false,
			$this       = $(this),
			$recipient  = $('#easc-modal-recipient'),
			$name       = $('#easc-modal-name'),
			$email      = $('#easc-modal-email'),
			$validation = $('#easc-modal-validation'),
			data        = {
				action:    'easc_email',
				postid:     easc_id,
				recipient:  $recipient.val(),
				name:       $name.val(),
				email:      $email.val(),
				validation: $validation.val(),
				nonce:      easc_nonce
			};

		// Check if any of the required fields are empty.
		$( $recipient, $name, $email ).each(function() {
			if ( ! $(this).val() || $(this).val() === '' ) {
				empty = true;
			}
		});

		// If an empty field was found, alert user and stop.
		if ( empty ) {
			alert( 'Please complete out all 3 fields to email this article.' );
			return;
		}

		// Disable submit to prevent duplicates.
		$( this ).prop( 'disabled', true );

		// AJAX post.
		$.post( easc.url, data, function( res ) {

			if ( res.success ){
				console.log( 'Article successfully shared.' );
			}

			// Hide modal.
			$( '#easc-modal-sent' ).fadeIn();

			// Clear values for future shares.
			$( $recipient, $name, $email ).val( '' );

			// Enable submit button.
			$this.prop( 'disabled', false );

			// Temporarily show success message.
			setTimeout( function(){
				$( '#easc-modal-wrap, #easc-modal-sent' ).fadeOut();
			}, 2000 );

		}).fail( function( xhr ) {
			console.log( xhr.responseText );
		});
	});
});
