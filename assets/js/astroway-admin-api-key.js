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
	} );
} )( window.jQuery );
