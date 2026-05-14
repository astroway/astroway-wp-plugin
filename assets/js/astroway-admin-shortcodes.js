/* AstroWay admin — Shortcodes page: copy-to-clipboard. Vanilla, no jQuery. */
( function () {
	'use strict';

	function ready( fn ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', fn );
		} else {
			fn();
		}
	}

	ready( function () {
		var cfg  = window.astrowayAdmin || {};
		var i18n = cfg.i18n || {};

		function copyText( text, done ) {
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( done );
				return;
			}
			var ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.style.position = 'absolute';
			ta.style.left = '-9999px';
			document.body.appendChild( ta );
			ta.select();
			try { document.execCommand( 'copy' ); done(); } catch ( e ) {}
			document.body.removeChild( ta );
		}

		document.querySelectorAll( '.aw-sc-code' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var code = btn.getAttribute( 'data-copy' );
				if ( ! code ) return;
				var label = btn.querySelector( '.aw-sc-action-text' );
				var prev  = label ? label.textContent : '';
				copyText( code, function () {
					btn.classList.add( 'is-copied' );
					if ( label ) label.textContent = i18n.copied || 'copied!';
					setTimeout( function () {
						btn.classList.remove( 'is-copied' );
						if ( label ) label.textContent = prev;
					}, 1400 );
				} );
			} );
		} );

		/* City → lat / lon / tz helper. DOM-only, never innerHTML with user data. */
		var input     = document.getElementById( 'aw-city-search' );
		var searchBtn = document.getElementById( 'aw-city-search-btn' );
		var results   = document.getElementById( 'aw-city-results' );

		if ( ! input || ! results ) return;

		var inflight = null;

		function clearResults() {
			while ( results.firstChild ) results.removeChild( results.firstChild );
		}

		function el( tag, cls, text ) {
			var n = document.createElement( tag );
			if ( cls )  n.className   = cls;
			if ( text ) n.textContent = text;
			return n;
		}

		function statusLine( text, kind ) {
			clearResults();
			var p = el( 'p', 'aw-city-status' + ( kind === 'error' ? ' is-error' : '' ), text );
			results.appendChild( p );
		}

		function renderResults( items ) {
			clearResults();
			if ( ! items.length ) {
				results.appendChild( el( 'p', 'aw-city-status', i18n.noResults || 'No cities found.' ) );
				return;
			}
			results.appendChild( el( 'p', 'aw-city-status', i18n.pickCity || 'Pick a city' ) );

			var list = el( 'ul', 'aw-city-list' );
			items.forEach( function ( c ) {
				if ( typeof c.latitude !== 'number' || typeof c.longitude !== 'number' || ! c.timezone ) {
					return;
				}
				var display = c.name_en || c.name || '?';
				if ( c.country ) display += ', ' + String( c.country ).toUpperCase();
				var lat = c.latitude.toFixed( 4 );
				var lon = c.longitude.toFixed( 4 );
				var snippet = 'lat="' + lat + '" lon="' + lon + '" tz="' + c.timezone + '"';

				var li      = el( 'li', 'aw-city-item' );
				var pickBtn = el( 'button', 'aw-city-pick' );
				pickBtn.setAttribute( 'type', 'button' );
				pickBtn.setAttribute( 'data-copy', snippet );

				pickBtn.appendChild( el( 'span', 'aw-city-name', display ) );

				var meta = el( 'span', 'aw-city-meta' );
				meta.appendChild( el( 'code', null, lat + ', ' + lon ) );
				meta.appendChild( document.createTextNode( ' · ' ) );
				meta.appendChild( el( 'code', null, c.timezone ) );
				pickBtn.appendChild( meta );

				pickBtn.appendChild( el( 'span', 'aw-city-copy-hint', i18n.copied || 'copy snippet' ) );

				pickBtn.addEventListener( 'click', function () {
					copyText( snippet, function () {
						pickBtn.classList.add( 'is-copied' );
						setTimeout( function () { pickBtn.classList.remove( 'is-copied' ); }, 1400 );
					} );
				} );

				li.appendChild( pickBtn );
				list.appendChild( li );
			} );
			results.appendChild( list );
		}

		function doSearch() {
			var q = input.value.trim();
			if ( q.length < 2 ) {
				statusLine( i18n.minChars || 'Type at least 2 characters.', 'info' );
				return;
			}
			statusLine( i18n.searching || 'Searching…', 'info' );

			if ( inflight ) inflight.abort();
			inflight = new AbortController();

			var url = cfg.ajaxUrl + '?action=astroway_atlas_search'
				+ '&nonce=' + encodeURIComponent( cfg.nonce )
				+ '&q=' + encodeURIComponent( q );

			fetch( url, { signal: inflight.signal } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( resp ) {
					if ( ! resp.success ) {
						statusLine( ( resp.data && resp.data.message ) || i18n.searchError, 'error' );
						return;
					}
					renderResults( ( resp.data && resp.data.results ) || [] );
				} )
				.catch( function ( e ) {
					if ( e.name === 'AbortError' ) return;
					statusLine( i18n.searchError || 'Search error.', 'error' );
				} );
		}

		if ( searchBtn ) searchBtn.addEventListener( 'click', doSearch );
		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) { e.preventDefault(); doSearch(); }
		} );
	} );
} )();
