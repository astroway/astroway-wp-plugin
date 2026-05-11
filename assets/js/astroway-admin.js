/* AstroWay admin Settings page JS — jQuery for WP admin compat (no build step). */
( function ( $ ) {
	'use strict';

	$( function () {
		var cfg  = window.astrowayAdmin || {};
		var i18n = cfg.i18n || {};

		function show( $el, html, type ) {
			$el.removeClass( 'notice-info notice-success notice-error' )
				.addClass( 'notice notice-' + type + ' inline' )
				.html( html )
				.show();
		}

		function escapeHtml( str ) {
			return $( '<div>' ).text( String( str == null ? '' : str ) ).html();
		}

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
				var html = '<p>' + bits.join( ' · ' );
				if ( fallback ) {
					html += ' <em>(' + escapeHtml( i18n.fallback || 'limited info' ) + ')</em>';
				}
				html += '</p>';
				show( $status, html, 'success' );
			} ).fail( function () {
				show( $status, '<p>' + escapeHtml( i18n.networkError || 'Network error' ) + '</p>', 'error' );
			} );
		} );

		$( '#aw-test-connection' ).on( 'click', function () {
			var $result = $( '#aw-test-result' );
			$result.text( ' ' + ( i18n.pinging || 'Pinging…' ) );

			$.post( window.ajaxurl, {
				action: 'astroway_ping_health',
				nonce:  cfg.nonce
			} ).done( function ( resp ) {
				var ok = resp && resp.success && resp.data && resp.data.status === 200;
				if ( ok ) {
					$result.html( ' <span style="color:#0a7d3a;">✓ ' + escapeHtml( i18n.healthy || 'API healthy' ) + '</span>' );
				} else {
					$result.html( ' <span style="color:#b32d2e;">✗ ' + escapeHtml( i18n.unreachable || 'API unreachable' ) + '</span>' );
				}
			} ).fail( function () {
				$result.html( ' <span style="color:#b32d2e;">✗ ' + escapeHtml( i18n.unreachable || 'API unreachable' ) + '</span>' );
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
	} );
} )( window.jQuery );
