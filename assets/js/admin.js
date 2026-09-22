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

		// Repeaters: add, remove, move. Rows are plain form fields, so the server
		// never has to know that the browser reordered anything - it reads the
		// `dw[<field>][<index>][<column>]` names in the order they arrive.
		document.querySelectorAll( '[data-dw-repeater]' ).forEach( function ( repeater ) {
			var table = repeater.querySelector( 'tbody' );

			// Every form field of a row. Selected by name in JavaScript, not by an
			// attribute selector: `[name^="dw["]` is valid CSS but some parsers (and
			// the test DOM) quietly match nothing with a bracket inside the value.
			function fields( row ) {
				return Array.prototype.filter.call(
					row.querySelectorAll( 'input, textarea, select' ),
					function ( input ) {
						return 0 === input.name.indexOf( 'dw[' );
					}
				);
			}

			function renumber() {
				Array.prototype.forEach.call( table.rows, function ( row, index ) {
					fields( row ).forEach( function ( input ) {
						// Only the row index changes: the field key and the column stay
						// exactly as the schema wrote them.
						input.name = input.name.replace( /^dw\[([^\]]+)\]\[\d+\]/, 'dw[$1][' + index + ']' );
					} );
				} );
			}

			function lastRow() {
				return table.rows[ table.rows.length - 1 ];
			}

			repeater.addEventListener( 'click', function ( event ) {
				var button = event.target.closest( 'button' );
				var row    = event.target.closest( 'tr' );

				if ( ! button || ! row ) {
					return;
				}

				if ( button.classList.contains( 'dw-repeater__remove' ) ) {
					row.remove();
					renumber();
					return;
				}

				if ( button.classList.contains( 'dw-repeater__up' ) && row.previousElementSibling ) {
					table.insertBefore( row, row.previousElementSibling );
					renumber();
					return;
				}

				if ( button.classList.contains( 'dw-repeater__down' ) && row.nextElementSibling ) {
					table.insertBefore( row.nextElementSibling, row );
					renumber();
					return;
				}

				if ( button.classList.contains( 'dw-repeater__add' ) ) {
					var last = lastRow();

					if ( ! last ) {
						return;
					}

					var clone = last.cloneNode( true );

					clone.querySelectorAll( 'input, textarea' ).forEach( function ( input ) {
						if ( 'checkbox' === input.type ) {
							input.checked = false;
						} else {
							input.value = '';
						}
					} );
					clone.querySelectorAll( 'select' ).forEach( function ( select ) {
						select.selectedIndex = 0;
					} );

					table.appendChild( clone );
					renumber();
				}
			} );
		} );

		// Repeaters grow from the table itself; the "add" button lives after it.
		document.querySelectorAll( '[data-dw-repeater]' ).forEach( function ( repeater ) {
			if ( repeater.querySelector( '.dw-repeater__add' ) ) {
				return;
			}

			var add = document.createElement( 'button' );
			add.type = 'button';
			add.className = 'button dw-repeater__add';
			add.textContent = DashWooData.strings.addRow;
			repeater.appendChild( add );
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
