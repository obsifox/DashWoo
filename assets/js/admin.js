/* global DashWooData, wp */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// Live search inside large <select> lists (font catalogue).
		document.querySelectorAll( 'select[data-search="1"]' ).forEach( function ( select ) {
			var box = document.createElement( 'input' );
			box.type = 'search';
			box.placeholder = wp.i18n.__( 'Search…', 'dashwoo' );
			box.className = 'dw-select-search__input';
			select.parentNode.insertBefore( box, select );
			box.addEventListener( 'input', function () {
				var term = box.value.toLowerCase();
				Array.prototype.forEach.call( select.options, function ( option ) {
					option.hidden = option.text.toLowerCase().indexOf( term ) === -1;
				} );
			} );
		} );

		// Async REST helper used by the admin screens.
		window.DashWoo = {
			request: function ( path, method, body ) {
				if ( ! window.wp || ! wp.apiFetch ) {
					return Promise.reject( new Error( 'wp.apiFetch is unavailable' ) );
				}
				return wp.apiFetch( {
					path: '/dashwoo/v1' + path,
					method: method || 'GET',
					data: body,
					headers: { 'X-WP-Nonce': DashWooData.nonce },
				} );
			},
		};
	} );
} )();
