/* global Choices */

'use strict';

jQuery( document ).ready(function($){

	// Conditional logic.
	$( '#easc-settings-form' ).conditions( [
		// Sharecount.
		{
			conditions: {
				element:	'#easc-setting-count_source',
				type:		'value',
				operator:	'=',
				condition:  'sharedcount'
			},
			actions: {
				if: {
					element:	'#easc-setting-row-sharedcount_key, #easc-setting-row-twitter_counts',
					action:		'show'
				}, else : {
					element:	'#easc-setting-row-sharedcount_key, #easc-setting-row-twitter_counts',
					action:		'hide'
				}
			},
			effect: 'appear'
		},
		// Native counts.
		{
			conditions: {
				element:	'#easc-setting-count_source',
				type:		'value',
				operator:	'=',
				condition:  'native'
			},
			actions: {
				if: {
					element:	'#easc-setting-row-service, #easc-setting-row-fb_access_token',
					action:		'show'
				},
				else: {
					element:	'#easc-setting-row-service, #easc-setting-row-fb_access_token',
					action:		'hide'
				}
			},
			effect: 'appear'
		},
		// Both SharedCounts and Native counts.
		{
			conditions: {
				element:	'#easc-setting-count_source',
				type:		'value',
				operator:	'array',
				condition:  [ 'native', 'sharedcount' ],
			},
			actions: {
				if: {
					element:	'#easc-setting-row-total_only, #easc-setting-row-hide_empty',
					action:		'show'
				},
				else: {
					element:	'#easc-setting-row-total_only, #easc-setting-row-hide_empty',
					action:		'hide'
				}
			},
			effect: 'appear'
		},
		// Google reCAPTCHA.
		{
			conditions: {
				element:	'#easc-setting-included_services',
				type:		'value',
				operator:	'array',
				condition:  [ 'email' ],
			},
			actions: {
				if: {
					element:	'#easc-setting-row-recaptcha, #easc-setting-row-recaptcha_site_key, #easc-setting-row-recaptcha_secret_key',
					action:		'show'
				},
				else: {
					element:	'#easc-setting-row-recaptcha, #easc-setting-row-recaptcha_site_key, #easc-setting-row-recaptcha_secret_key',
					action:		'hide'
				}
			},
			effect: 'appear'
		}
	] );

	// Service selctor.
	new Choices( $( '.share-count-services' )[0], {
		searchEnabled:    true,
		removeItemButton: true,
		placeholderValue: 'Select services...',
		shouldSort:       false
	} );
});
