/**
 * DashWoo account panel — progressive enhancement.
 *
 * The panel is rendered on the server with real WooCommerce links: without this file
 * (or with JavaScript off) every menu item, every order row and every pagination link
 * still navigates and loads the account page. This script only makes that swap happen
 * inside the content card instead of the whole document.
 *
 * No jQuery, no build step, no external request: the REST endpoint is the site's own.
 *
 * @package DashWoo
 */
( function () {
	'use strict';

	var strings = ( window.DashWooPanel && window.DashWooPanel.strings ) || {};

	/**
	 * Load one view into a panel.
	 *
	 * @param {HTMLElement} panel  The panel root.
	 * @param {string}      view   View id (orders, downloads, view-order...).
	 * @param {number}      order  Order id (0 when not an order view).
	 * @param {Object}      opts   Options: page (paged views), url (URL to push), push.
	 * @return {Promise} Resolves with the response payload.
	 */
	function load( panel, view, order, opts ) {
		opts = opts || {};

		var rest = panel.getAttribute( 'data-dw-rest' );
		var nonce = panel.getAttribute( 'data-dw-nonce' );
		var mode = panel.getAttribute( 'data-dw-mode' );
		var status = panel.querySelector( '[data-dw-panel-status]' );
		var body = panel.querySelector( '[data-dw-panel-body]' );
		var title = panel.querySelector( '[data-dw-panel-title]' );
		var back = panel.querySelector( '[data-dw-panel-back]' );
		var loading = panel.getAttribute( 'data-dw-loading-text' ) || ( strings.loading || 'Loading…' );
		var page = Math.max( 0, parseInt( opts.page || '0', 10 ) || 0 );

		if ( ! rest || ! body ) {
			return Promise.reject( new Error( 'panel not ready' ) );
		}

		// `dw_panel=1` is what Panel::is_ajax() looks for; `dw_page` is the page of a
		// paged view (orders, downloads...).
		var url = rest + ( rest.indexOf( '?' ) === -1 ? '?' : '&' ) +
			'view=' + encodeURIComponent( view ) +
			'&order_id=' + encodeURIComponent( order || 0 ) +
			'&dw_panel=1' +
			( page > 1 ? '&dw_page=' + encodeURIComponent( page ) : '' );

		panel.setAttribute( 'data-dw-loading', '1' );

		if ( status ) {
			status.textContent = loading;
		}

		return window.fetch( url, {
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': nonce,
				Accept: 'application/json'
			}
		} ).then( function ( response ) {
			return response.json();
		} ).then( function ( data ) {
			panel.removeAttribute( 'data-dw-loading' );

			if ( status ) {
				status.textContent = '';
			}

			if ( ! data || typeof data.html !== 'string' ) {
				throw new Error( 'empty' );
			}

			// Modal mode keeps the panel in place and shows the detail on top; the swap
			// mode simply replaces the content card.
			var modal = panel.querySelector( '[data-dw-panel-modal]' );
			var box = panel.querySelector( '[data-dw-panel-modal-body]' );

			if ( 'modal' === mode && modal && box && order > 0 ) {
				box.innerHTML = ( data.title ? '<h3 class="dw-acc__panel-modal-title">' + data.title + '</h3>' : '' ) + data.html;
				modal.hidden = false;
				document.body.classList.add( 'dw-acc-modal-open' );

				return data;
			}

			body.innerHTML = data.html;

			if ( title && data.title ) {
				title.textContent = data.title;
			}

			panel.setAttribute( 'data-dw-view', view );

			if ( back ) {
				if ( data.back ) {
					back.hidden = false;
					back.setAttribute( 'data-dw-href', data.back );
				} else {
					back.hidden = true;
				}
			}

			markActive( panel, view );

			if ( typeof opts.push !== 'false' && '1' === panel.getAttribute( 'data-dw-sync' ) ) {
				var next = opts.url || data.url;

				if ( next ) {
					window.history.pushState( { dwView: view, dwOrder: order, dwPage: page }, '', next );
				}
			}

			body.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );

			return data;
		} ).catch( function ( error ) {
			panel.removeAttribute( 'data-dw-loading' );

			if ( status ) {
				status.textContent = strings.error || 'Could not load this section.';
			}

			throw error;
		} );
	}

	/**
	 * Mark the menu item of a view as active.
	 *
	 * @param {HTMLElement} panel Panel.
	 * @param {string}      view  View id.
	 * @return {void}
	 */
	function markActive( panel, view ) {
		panel.querySelectorAll( '.dw-acc__nav-item' ).forEach( function ( item ) {
			var link = item.querySelector( '.dw-acc__nav-link' );
			var id = link ? link.getAttribute( 'data-dw-view' ) : '';
			var active = id === view || ( 'view-order' === view && 'orders' === id );

			item.classList.toggle( 'is-active', active );

			if ( link ) {
				if ( active ) {
					link.setAttribute( 'aria-current', 'page' );
				} else {
					link.removeAttribute( 'aria-current' );
				}
			}
		} );
	}

	/**
	 * Which view does a link point at? (falls back to matching its href.)
	 *
	 * @param {HTMLElement} panel Panel.
	 * @param {HTMLElement} link  Anchor.
	 * @return {{view: string, order: number}|null}
	 */
	function target( panel, link ) {
		var view = link.getAttribute( 'data-dw-view' );
		var order = parseInt( link.getAttribute( 'data-dw-order' ) || '0', 10 ) || 0;
		var href = link.getAttribute( 'href' ) || '';

		if ( view === 'customer-logout' || link.classList.contains( 'dw-acc__logout-button' ) ) {
			return null;
		}

		// An order row (`data-dw-order`) opens that order in the content card.
		if ( order > 0 ) {
			return { view: 'view-order', order: order, page: 0 };
		}

		if ( view ) {
			return { view: view, order: 0, page: pageOf( href ) };
		}

		// An order link (view-order/<id>/) → the order view with that id.
		var match = href.match( /view-order\/(\d+)/ );

		if ( match ) {
			return { view: 'view-order', order: parseInt( match[ 1 ], 10 ), page: 0 };
		}

		var views = viewMap( panel );
		var best = null;

		// Any other account link → the view whose URL the href starts with. The
		// longest one wins: the account root prefixes every other link, so taking the
		// first match would send "orders/page/2" to the dashboard.
		Object.keys( views ).forEach( function ( id ) {
			var url = views[ id ] && views[ id ].url;

			if ( ! url || href.indexOf( url ) !== 0 ) {
				return;
			}

			// Logging out is never a card swap.
			if ( id === 'customer-logout' ) {
				return;
			}

			if ( ! best || url.length > best.match.length ) {
				best = { view: id, order: 0, page: pageOf( href ), match: url };
			}
		} );

		return best ? { view: best.view, order: best.order, page: best.page } : null;
	}

	/**
	 * The page number a pagination link points at (0 when it is not one).
	 *
	 * Pagination inside the card is rewritten by the server to real account URLs
	 * (`.../orders/page/2/`), so the page is read from the href and sent back as
	 * `dw_page` - the card keeps its place instead of reloading the document.
	 *
	 * @param {string} href Link URL.
	 * @return {number} Page number.
	 */
	function pageOf( href ) {
		var match = ( href || '' ).match( /(?:\/page\/|[?&](?:paged|dw_page)=)(\d+)/ );

		return match ? Math.max( 1, parseInt( match[ 1 ], 10 ) || 1 ) : 0;
	}

	/**
	 * The view map the server embedded (id → url/label).
	 *
	 * @param {HTMLElement} panel Panel.
	 * @return {Object} View map.
	 */
	function viewMap( panel ) {
		try {
			return JSON.parse( panel.getAttribute( 'data-dw-views' ) || '{}' ) || {};
		} catch ( e ) {
			return {};
		}
	}

	/**
	 * Wire one panel.
	 *
	 * @param {HTMLElement} panel Panel root.
	 * @return {void}
	 */
	function init( panel ) {
		if ( '0' === panel.getAttribute( 'data-dw-ajax' ) ) {
			return;
		}

		if ( panel.getAttribute( 'data-dw-ready' ) === '1' ) {
			return;
		}

		panel.setAttribute( 'data-dw-ready', '1' );

		panel.addEventListener( 'click', function ( event ) {
			var close = event.target.closest( '[data-dw-panel-close]' );

			if ( close ) {
				event.preventDefault();
				closeModal( panel );

				return;
			}

			var back = event.target.closest( '[data-dw-panel-back]' );

			if ( back ) {
				event.preventDefault();
				closeModal( panel );

				var backView = back.getAttribute( 'data-dw-target' ) || 'orders';
				load( panel, backView, 0 );

				return;
			}

			var link = event.target.closest( 'a' );

			if ( ! link ) {
				return;
			}

			var which = target( panel, link );

			if ( ! which ) {
				return;
			}

			// Leave modified clicks (new tab, download, ...) to the browser.
			if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.getAttribute( 'target' ) ) {
				return;
			}

			event.preventDefault();
			load( panel, which.view, which.order, { page: which.page, url: link.getAttribute( 'href' ) } );
		} );

		var modal = panel.querySelector( '[data-dw-panel-modal]' );

		if ( modal ) {
			modal.addEventListener( 'click', function ( event ) {
				if ( event.target === modal ) {
					closeModal( panel );
				}
			} );

			document.addEventListener( 'keydown', function ( event ) {
				if ( 'Escape' === event.key && ! modal.hidden ) {
					closeModal( panel );
				}
			} );
		}

		if ( '1' === panel.getAttribute( 'data-dw-sync' ) ) {
			window.addEventListener( 'popstate', function ( event ) {
				var state = event.state || {};
					var known = viewMap( panel );

				if ( state.dwView && known[ state.dwView ] ) {
					load( panel, state.dwView, state.dwOrder || 0, { push: 'false', page: state.dwPage || 0 } );
				}
			} );
		}
	}

	/**
	 * Close the modal (when the panel has one).
	 *
	 * @param {HTMLElement} panel Panel.
	 * @return {void}
	 */
	function closeModal( panel ) {
		var modal = panel.querySelector( '[data-dw-panel-modal]' );

		if ( modal ) {
			modal.hidden = true;
		}

		document.body.classList.remove( 'dw-acc-modal-open' );
	}

	/**
	 * Boot: every panel on the page.
	 *
	 * @return {void}
	 */
	function boot() {
		document.querySelectorAll( '[data-dw-panel]' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// A small public surface: themes and tests can drive the panel directly.
	window.DashWooPanelApp = {
		load: function ( view, order, opts ) {
			var panel = document.querySelector( '[data-dw-panel]' );

			return panel ? load( panel, view, order || 0, opts || {} ) : Promise.reject( new Error( 'no panel' ) );
		},
		boot: boot
	};
}() );
