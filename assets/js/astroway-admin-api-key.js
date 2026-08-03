/* AstroWay admin — API Key page: verify-key handler. */
( function ( $ ) {
	'use strict';

	$( function () {
		var cfg  = window.astrowayAdmin || {};
		var i18n = cfg.i18n || {};

		function show( $el, html, type ) {
			var cls = 'aw-result';
			if ( type === 'success' ) cls += ' is-success';
			else if ( type === 'error' ) cls += ' is-error';
			$el.attr( 'class', cls ).html( html ).show();
		}

		function escapeHtml( str ) {
			return $( '<div>' ).text( String( str == null ? '' : str ) ).html();
		}

		/* Copy buttons in the getting-started steps. Same markup and behaviour
		   as the Shortcodes page; execCommand is the fallback for plain http. */
		$( '.aw-sc-code' ).on( 'click', function () {
			var btn  = this;
			var code = btn.getAttribute( 'data-copy' );
			if ( ! code ) return;

			var $label = $( btn ).find( '.aw-sc-action-text' );
			var prev   = $label.text();

			function done() {
				btn.classList.add( 'is-copied' );
				$label.text( i18n.copied || 'copied!' );
				setTimeout( function () {
					btn.classList.remove( 'is-copied' );
					$label.text( prev );
				}, 1400 );
			}

			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( code ).then( done );
				return;
			}
			var ta = document.createElement( 'textarea' );
			ta.value = code;
			ta.style.position = 'absolute';
			ta.style.left = '-9999px';
			document.body.appendChild( ta );
			ta.select();
			try { document.execCommand( 'copy' ); done(); } catch ( e ) {}
			document.body.removeChild( ta );
		} );

		$( '#aw-verify-key' ).on( 'click', function () {
			var $status = $( '#aw-key-status' );
			var key     = ( $( '#aw-api-key' ).val() || '' ).trim();
			if ( ! key || key.indexOf( 'aw_' ) !== 0 ) {
				show( $status, '<p>' + escapeHtml( i18n.invalidKey || 'Invalid key' ) + '</p>', 'error' );
				return;
			}
			show( $status, '<p>' + escapeHtml( i18n.verifying || 'Verifying…' ) + '</p>', 'info' );

			$.post( window.ajaxurl, {
				action: 'astroway_verify_key',
				nonce:  cfg.nonce
			} ).done( function ( resp ) {
				if ( ! resp || ! resp.success ) {
					var msg = ( resp && resp.data && resp.data.message ) || ( i18n.networkError || 'Error' );
					show( $status, '<p>' + escapeHtml( msg ) + '</p>', 'error' );
					return;
				}
				var payload  = resp.data || {};
				var d        = ( payload.data && payload.data.data ) || {};
				var fallback = payload.fallback;
				var bits     = [];

				if ( d.plan ) {
					bits.push( '<strong>' + escapeHtml( i18n.plan || 'Plan' ) + ':</strong> ' + escapeHtml( d.plan ) );
				}
				if ( d.credits_used_this_period != null ) {
					var creditsTotal = d.credits_total_this_period ? ' / ' + escapeHtml( d.credits_total_this_period ) : '';
					bits.push( '<strong>' + escapeHtml( i18n.creditsUsed || 'Used' ) + ':</strong> ' + escapeHtml( d.credits_used_this_period ) + creditsTotal );
				}
				if ( d.rate_limit_per_min ) {
					bits.push( '<strong>' + escapeHtml( i18n.rateLimit || 'Rate' ) + ':</strong> ' + escapeHtml( d.rate_limit_per_min ) + '/min' );
				}
				if ( d.domain ) {
					bits.push( '<strong>' + escapeHtml( i18n.domain || 'Bound' ) + ':</strong> ' + escapeHtml( d.domain ) );
				}
				var html;
				if ( bits.length ) {
					html = '<p>' + bits.join( ' · ' );
					if ( fallback ) {
						html += ' <em>(' + escapeHtml( i18n.fallback || 'limited info' ) + ')</em>';
					}
					html += '</p>';
				} else {
					// 200 with no usage fields — confirm validity rather than render an empty panel.
					html = '<p>' + escapeHtml( i18n.keyValid || 'Key verified.' ) + '</p>';
				}
				show( $status, html, 'success' );
			} ).fail( function () {
				show( $status, '<p>' + escapeHtml( i18n.networkError || 'Network error' ) + '</p>', 'error' );
			} );
		} );
	} );
} )( window.jQuery );
