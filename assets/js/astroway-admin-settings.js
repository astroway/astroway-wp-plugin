/* AstroWay admin — Settings page: ping + purge + copy diagnostic. */
( function ( $ ) {
	'use strict';

	$( function () {
		var cfg  = window.astrowayAdmin || {};
		var i18n = cfg.i18n || {};

		$( '#aw-test-connection' ).on( 'click', function () {
			var $result = $( '#aw-test-result' );
			$result.attr( 'class', 'aw-test-result' ).text( i18n.pinging || 'Pinging…' );

			$.post( window.ajaxurl, {
				action: 'astroway_ping_health',
				nonce:  cfg.nonce
			} ).done( function ( resp ) {
				var ok = resp && resp.success && resp.data && resp.data.status === 200;
				if ( ok ) {
					$result.attr( 'class', 'aw-test-result is-success' ).text( '✓ ' + ( i18n.healthy || 'API healthy' ) );
				} else {
					$result.attr( 'class', 'aw-test-result is-error' ).text( '✗ ' + ( i18n.unreachable || 'API unreachable' ) );
				}
			} ).fail( function () {
				$result.attr( 'class', 'aw-test-result is-error' ).text( '✗ ' + ( i18n.unreachable || 'API unreachable' ) );
			} );
		} );

		$( '#aw-purge-cache' ).on( 'click', function () {
			if ( ! window.confirm( i18n.confirmPurge || 'Purge all cached data?' ) ) {
				return;
			}
			$.post( window.ajaxurl, {
				action: 'astroway_purge_cache',
				nonce:  cfg.nonce
			} ).done( function ( resp ) {
				if ( resp && resp.success ) {
					window.alert( ( i18n.purged || 'Cache cleared:' ) + ' ' + ( resp.data.purged || 0 ) );
					window.location.reload();
				}
			} );
		} );

		$( '#aw-copy-diag' ).on( 'click', function () {
			var $btn     = $( this );
			var targetId = $btn.data( 'target' );
			var $ta      = $( '#' + targetId );
			if ( ! $ta.length ) return;
			var text = $ta.val();
			var done = function () {
				var prev = $btn.text();
				$btn.text( ( i18n.copied || 'copied!' ) );
				setTimeout( function () { $btn.text( prev ); }, 1400 );
			};
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( done );
				return;
			}
			$ta.show();
			$ta[ 0 ].select();
			try { document.execCommand( 'copy' ); done(); } catch ( e ) {}
			$ta.hide();
		} );
	} );
} )( window.jQuery );
