/*
 * Listens to the frames the plugin renders.
 *
 * Every page under api.astroway.info/v1/embed/ posts its own height to the
 * parent, because a frame cannot be measured across origins. When the visitor's
 * hourly quota is spent the api answers with a card about API keys instead and
 * posts `rate-limited`. A visitor must never be shown that card: they cannot act
 * on it, and the site owner who can is not the one looking.
 *
 * A message is trusted only when it comes from one of our frames and from the
 * origin that frame was pointed at, which the server wrote into its src from
 * ASTROWAY_API_BASE, so https://api.astroway.info on every build. A frame that
 * navigated elsewhere, or a message from any other window, is ignored.
 *
 * Without this script nothing breaks: the frame keeps the height in its
 * attribute, and the limit card shows as it always did.
 */
( function () {
	'use strict';

	var MAX_HEIGHT = 4000;

	function frameOf( source ) {
		var frames = document.querySelectorAll( 'iframe.astroway-embed__iframe' );
		for ( var i = 0; i < frames.length; i++ ) {
			if ( frames[ i ].contentWindow === source ) {
				return frames[ i ];
			}
		}
		return null;
	}

	function originOf( frame ) {
		try {
			return new URL( frame.getAttribute( 'src' ), window.location.href ).origin;
		} catch ( e ) {
			return '';
		}
	}

	// Collapse for visitors. An administrator keeps the note the server put
	// next to the frame, so the owner still learns why the widget is gone.
	function collapse( frame ) {
		var wrap = frame.parentNode;
		var note = wrap && wrap.querySelector( '.astroway-embed__note' );
		if ( ! wrap ) {
			return;
		}
		if ( note ) {
			frame.remove();
			note.hidden = false;
			wrap.classList.add( 'astroway-embed--unavailable' );
			return;
		}
		wrap.hidden = true;
	}

	function handle( event ) {
		var data = event.data;
		if ( ! data || 'object' !== typeof data || 'astroway-embed' !== data.source ) {
			return;
		}
		var frame = frameOf( event.source );
		if ( ! frame || event.origin !== originOf( frame ) ) {
			return;
		}

		if ( 'rate-limited' === data.event || 'error' === data.event ) {
			collapse( frame );
			return;
		}

		var height = Math.ceil( Number( data.height ) );
		if ( height > 0 && height <= MAX_HEIGHT ) {
			frame.style.height = height + 'px';
			frame.parentNode.classList.add( 'astroway-embed--fit' );
		}
	}

	// Messages that arrived before this deferred file ran were kept by the
	// inline catcher printed ahead of it; a frame served from cache can post
	// before the page has finished parsing.
	var early = window.astrowayEmbedQueue || [];
	window.astrowayEmbedQueue = null;
	early.forEach( handle );
	window.addEventListener( 'message', handle );
}() );
