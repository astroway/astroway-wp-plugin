/*
 * Tarot spreads: cards that lie face down until turned, and the reading of
 * the spread once they are all open.
 *
 * Enhancement only. The server prints every card face up with its text; the
 * stylesheet lays them face down once the document carries `astroway-js`.
 */
( function () {
	'use strict';

	var each = function ( list, fn ) {
		Array.prototype.forEach.call( list, fn );
	};

	function spread( card ) {
		var root = card.closest( '.astroway-card' );
		if ( ! root ) {
			return;
		}
		var list    = root.querySelector( '.astroway-spread--reveal' );
		var reading = root.querySelector( '.astroway-tarot__reading' );
		var allBtn  = root.querySelector( '[data-astroway-open-all]' );

		function open( li ) {
			if ( li.classList.contains( 'is-open' ) ) {
				return;
			}
			li.classList.add( 'is-open' );
			var btn = li.querySelector( '.astroway-tarot__flip' );
			if ( btn ) {
				btn.setAttribute( 'aria-pressed', 'true' );
				var name = li.querySelector( '.astroway-spread__name' );
				if ( name ) {
					btn.setAttribute( 'aria-label', name.textContent );
				}
			}
			var slot = root.querySelector( '.astroway-cross__slot[data-astroway-card="' + li.getAttribute( 'data-astroway-card' ) + '"]' );
			if ( slot ) {
				slot.classList.add( 'is-open' );
			}
			check();
		}

		function check() {
			var closed = list ? list.querySelectorAll( '.astroway-spread__card:not(.is-open)' ).length : 0;
			if ( 0 === closed ) {
				if ( allBtn ) {
					allBtn.closest( '.astroway-tarot__open-all' ).hidden = true;
				}
				ask();
			}
		}

		function ask() {
			if ( ! reading || reading.getAttribute( 'data-astroway-asked' ) ) {
				return;
			}
			// Read on the server already (the api's spread of the day): only shown.
			if ( reading.hasAttribute( 'data-astroway-ready' ) ) {
				reading.setAttribute( 'data-astroway-asked', '1' );
				reading.hidden = false;
				return;
			}
			reading.setAttribute( 'data-astroway-asked', '1' );
			reading.setAttribute( 'aria-busy', 'true' );
			reading.innerHTML = '<p class="astroway-tarot__wait"></p>';
			reading.firstChild.textContent = reading.getAttribute( 'data-astroway-wait' );
			send( 0 );
		}

		// "wait" means another visitor's request is reading these cards right now.
		function send( tries ) {
			var headers = { 'Content-Type': 'application/json' };
			var nonce   = reading.getAttribute( 'data-astroway-nonce' );
			if ( nonce ) {
				headers[ 'X-WP-Nonce' ] = nonce;
			}
			window.fetch( reading.getAttribute( 'data-astroway-endpoint' ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: headers,
				body: JSON.stringify( {
					widget: reading.getAttribute( 'data-astroway-reading' ),
					seed: parseInt( reading.getAttribute( 'data-astroway-seed' ), 10 ) || 0,
					lang: reading.getAttribute( 'data-astroway-lang' ),
					art: reading.getAttribute( 'data-astroway-art' )
				} )
			} ).then( function ( r ) {
				return r.json();
			} ).then( function ( out ) {
				if ( out && 'wait' === out.code && tries < 5 ) {
					window.setTimeout( function () { send( tries + 1 ); }, 4000 );
					return;
				}
				reading.removeAttribute( 'aria-busy' );
				if ( out && out.ok && out.html ) {
					// Server-rendered and escaped there, like every card.
					reading.innerHTML = out.html;
					return;
				}
				fail( out && out.note );
			} ).catch( function () {
				reading.removeAttribute( 'aria-busy' );
				fail( '' );
			} );
		}

		function fail( note ) {
			reading.innerHTML = '<p class="astroway-tarot__failed"></p>';
			reading.firstChild.textContent = note || reading.getAttribute( 'data-astroway-failed' );
		}

		if ( list ) {
			each( list.querySelectorAll( '.astroway-tarot__flip' ), function ( btn ) {
				btn.addEventListener( 'click', function () {
					open( btn.closest( '.astroway-spread__card' ) );
				} );
			} );
			if ( allBtn ) {
				allBtn.addEventListener( 'click', function () {
					each( list.querySelectorAll( '.astroway-spread__card' ), open );
				} );
			}
		} else if ( reading ) {
			// Face up from the start: the reading is asked for right away.
			ask();
		}
	}

	function init() {
		each( document.querySelectorAll( '.astroway-spread--reveal, .astroway-tarot__reading' ), function ( el ) {
			var root = el.closest( '.astroway-card' );
			if ( root && ! root.hasAttribute( 'data-astroway-tarot' ) ) {
				root.setAttribute( 'data-astroway-tarot', '1' );
				spread( el );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
