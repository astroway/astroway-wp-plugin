/*
 * Behaviour for the 2.0 cards: tabs, filter chips, table scroll hints.
 *
 * Enhancement only. The server prints every panel and every row, the CSS
 * draws the tabbed state once the document carries `astroway-js`, and this
 * file only answers clicks and keys. Nothing is fetched or built here.
 */
( function () {
	'use strict';

	var each = function ( list, fn ) {
		Array.prototype.forEach.call( list, fn );
	};

	function tabs( root ) {
		var list = root.querySelector( '.astroway-tabs__list' );
		if ( ! list ) {
			return;
		}
		var buttons = Array.prototype.slice.call( list.querySelectorAll( '[role="tab"]' ) );

		function select( next, focus ) {
			buttons.forEach( function ( tab, i ) {
				var on    = i === next;
				var panel = document.getElementById( tab.getAttribute( 'aria-controls' ) );
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				tab.tabIndex = on ? 0 : -1;
				if ( panel ) {
					panel.classList.toggle( 'is-selected', on );
				}
			} );
			if ( focus ) {
				buttons[ next ].focus();
			}
			// A table in a panel that was hidden measured nothing to scroll.
			hints( root );
		}

		list.addEventListener( 'click', function ( event ) {
			var i = buttons.indexOf( event.target.closest( '[role="tab"]' ) );
			if ( i > -1 ) {
				select( i, false );
			}
		} );

		list.addEventListener( 'keydown', function ( event ) {
			var current = buttons.indexOf( event.target );
			if ( current < 0 ) {
				return;
			}
			var last    = buttons.length - 1;
			var forward = 'rtl' === getComputedStyle( list ).direction ? 'ArrowLeft' : 'ArrowRight';
			var back    = 'ArrowRight' === forward ? 'ArrowLeft' : 'ArrowRight';
			var next    = null;
			if ( forward === event.key ) {
				next = current === last ? 0 : current + 1;
			} else if ( back === event.key ) {
				next = 0 === current ? last : current - 1;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = last;
			}
			if ( null !== next ) {
				event.preventDefault();
				select( next, true );
			}
		} );
	}

	// Rows carry data-astroway-kind. A disclosure left with none is hidden,
	// and a list left with none shows its data-astroway-empty line.
	function filter( group ) {
		var target = document.getElementById( group.getAttribute( 'data-astroway-filter' ) );
		if ( ! target ) {
			return;
		}
		group.addEventListener( 'click', function ( event ) {
			var chip = event.target.closest( '.astroway-chip' );
			if ( ! chip ) {
				return;
			}
			var value = chip.getAttribute( 'data-astroway-value' );
			each( group.querySelectorAll( '.astroway-chip' ), function ( other ) {
				other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
			} );
			each( target.querySelectorAll( '[data-astroway-kind]' ), function ( row ) {
				row.hidden = 'all' !== value && row.getAttribute( 'data-astroway-kind' ) !== value;
			} );
			each( target.querySelectorAll( '.astroway-aspects' ), function ( rows ) {
				var left  = !! rows.querySelector( '[data-astroway-kind]:not([hidden])' );
				var empty = rows.parentNode.querySelector( '[data-astroway-empty]' );
				var item  = rows.closest( '.astroway-acc__item' );
				if ( item ) {
					item.hidden = ! left;
				} else if ( empty ) {
					empty.hidden = left;
				}
			} );
		} );
	}

	// The wheel: a planet under the pointer or the focus shows its aspects and
	// its position. One tab stop for the whole wheel, arrows between planets.
	// Only on a wheel at least 440px wide: on a phone thirteen planets cannot
	// each get a 44px target, and the grid and positions tabs say the same.
	function wheel( fig ) {
		var svg     = fig.querySelector( 'svg' );
		var planets = Array.prototype.slice.call( fig.querySelectorAll( '.astroway-wheel__planet' ) );
		if ( ! svg || ! planets.length ) {
			return;
		}
		var lines = fig.querySelectorAll( '.astroway-wheel__asp' );
		var tip   = document.createElement( 'div' );
		tip.className = 'astroway-wheel__tip';
		tip.setAttribute( 'aria-hidden', 'true' );
		// The span carries the inverted colour, so the box keeps the page's.
		tip.appendChild( document.createElement( 'span' ) );
		tip.hidden = true;
		fig.appendChild( tip );

		function live( on ) {
			if ( on === fig.classList.contains( 'is-live' ) ) {
				return;
			}
			fig.classList.toggle( 'is-live', on );
			// An image may not hold controls; live, the wheel is a named group of them.
			svg.setAttribute( 'role', on ? 'group' : 'img' );
			planets.forEach( function ( g, i ) {
				if ( on ) {
					g.setAttribute( 'tabindex', 0 === i ? '0' : '-1' );
					g.setAttribute( 'role', 'button' );
					g.setAttribute( 'aria-label', g.getAttribute( 'data-label' ) || '' );
				} else {
					g.removeAttribute( 'tabindex' );
					g.removeAttribute( 'role' );
					g.removeAttribute( 'aria-label' );
				}
			} );
			if ( ! on ) {
				hide();
			}
		}

		function show( g ) {
			if ( ! fig.classList.contains( 'is-live' ) ) {
				return;
			}
			var id     = g.getAttribute( 'data-planet' );
			var linked = {};
			linked[ id ] = true;
			each( lines, function ( line ) {
				var on = line.getAttribute( 'data-a' ) === id || line.getAttribute( 'data-b' ) === id;
				line.classList.toggle( 'is-on', on );
				if ( on ) {
					linked[ line.getAttribute( 'data-a' ) ] = true;
					linked[ line.getAttribute( 'data-b' ) ] = true;
				}
			} );
			planets.forEach( function ( p ) {
				p.classList.toggle( 'is-on', !! linked[ p.getAttribute( 'data-planet' ) ] );
				p.classList.toggle( 'is-current', p === g );
			} );
			fig.classList.add( 'is-focus' );

			tip.firstChild.textContent = g.getAttribute( 'data-label' ) || '';
			tip.hidden                 = ! tip.firstChild.textContent;
			var box = fig.getBoundingClientRect();
			var at  = g.querySelector( '.astroway-wheel__hit' ).getBoundingClientRect();
			var x   = at.left + at.width / 2 - box.left;
			var w   = tip.offsetWidth;
			// Kept inside the figure: a tooltip cut by the card edge helps nobody.
			tip.style.left = Math.max( w / 2, Math.min( box.width - w / 2, x ) ) + 'px';
			tip.style.top  = ( at.top - box.top ) + 'px';
		}

		function hide() {
			fig.classList.remove( 'is-focus' );
			tip.hidden = true;
		}

		planets.forEach( function ( g, i ) {
			g.addEventListener( 'pointerenter', function ( event ) {
				if ( 'mouse' === event.pointerType ) {
					show( g );
				}
			} );
			g.addEventListener( 'pointerleave', function ( event ) {
				if ( 'mouse' === event.pointerType && document.activeElement !== g ) {
					hide();
				}
			} );
			g.addEventListener( 'focus', function () {
				show( g );
			} );
			g.addEventListener( 'blur', hide );
			// A tap on a touch screen has no hover: it focuses, which shows.
			g.addEventListener( 'click', function () {
				if ( fig.classList.contains( 'is-live' ) ) {
					g.focus();
				}
			} );
			g.addEventListener( 'keydown', function ( event ) {
				var next = null;
				if ( 'ArrowRight' === event.key || 'ArrowDown' === event.key ) {
					next = ( i + 1 ) % planets.length;
				} else if ( 'ArrowLeft' === event.key || 'ArrowUp' === event.key ) {
					next = ( i - 1 + planets.length ) % planets.length;
				} else if ( 'Escape' === event.key ) {
					hide();
					return;
				}
				if ( null !== next ) {
					event.preventDefault();
					g.setAttribute( 'tabindex', '-1' );
					planets[ next ].setAttribute( 'tabindex', '0' );
					planets[ next ].focus();
				}
			} );
		} );

		var fit = function () {
			live( fig.getBoundingClientRect().width >= 440 );
		};
		fit();
		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( fit ).observe( fig );
		}
	}

	// Marks the side a table still hides, for the CSS fade, and makes a
	// scroller that scrolls reachable by keyboard.
	function hint( box ) {
		var hidden = box.scrollWidth - box.clientWidth;
		if ( hidden < 2 ) {
			box.removeAttribute( 'data-astroway-more' );
			box.removeAttribute( 'tabindex' );
			return;
		}
		var from  = Math.abs( box.scrollLeft );
		var start = from > 1;
		var end   = from < hidden - 1;
		box.setAttribute( 'data-astroway-more', start && end ? 'both' : ( start ? 'start' : 'end' ) );
		box.tabIndex = 0;
	}

	function hints( scope ) {
		each( scope.querySelectorAll( '.astroway-scroll' ), hint );
	}

	each( document.querySelectorAll( '.astroway-card .astroway-tabs' ), tabs );
	each( document.querySelectorAll( '.astroway-card [data-astroway-filter]' ), filter );
	each( document.querySelectorAll( '.astroway-card .astroway-wheel' ), wheel );

	var boxes = document.querySelectorAll( '.astroway-card .astroway-scroll' );
	each( boxes, function ( box ) {
		hint( box );
		box.addEventListener( 'scroll', function () {
			hint( box );
		}, { passive: true } );
	} );
	if ( boxes.length && 'ResizeObserver' in window ) {
		var observer = new ResizeObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				hint( entry.target );
			} );
		} );
		each( boxes, function ( box ) {
			observer.observe( box );
		} );
	}
}() );
