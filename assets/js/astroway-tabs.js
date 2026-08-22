/*
 * Turns a stacked Astrology Section into tabs.
 *
 * Enhancement only, and deliberately so. The server renders every card in the
 * section, one after another, and that is a complete, readable, indexable page
 * on its own. This script rearranges what is already there; with JavaScript off,
 * or before it runs, or if it throws, the reader loses nothing but the strip of
 * buttons. Nothing here fetches, and no card is built in the browser.
 */
( function () {
	'use strict';

	/**
	 * Drop the words every tab starts with.
	 *
	 * Four horoscopes for one sign are titled "Leo: horoscope for today", "Leo:
	 * horoscope for the week" and so on, and a strip of tabs that all begin with
	 * the same six characters wraps onto three rows to say almost nothing. The
	 * prefix is only removed when every label shares it, it ends at a word
	 * boundary, and nothing is left empty, so a mixed section keeps its titles
	 * whole.
	 */
	function shorten( labels ) {
		if ( labels.length < 2 || labels.some( function ( label ) { return ! label; } ) ) {
			return labels;
		}

		var prefix = labels[ 0 ];
		labels.forEach( function ( label ) {
			var i = 0;
			while ( i < prefix.length && i < label.length && prefix[ i ] === label[ i ] ) {
				i++;
			}
			prefix = prefix.slice( 0, i );
		} );

		// Cut at a word boundary rather than mid-word: three horoscopes for one
		// sign share "Leo: horoscope for ", and cutting there leaves "today",
		// "the week" and "the month", which is what the tabs are for.
		var cut = prefix.lastIndexOf( ' ' ) + 1;
		if ( cut < 3 ) {
			return labels;
		}

		var trimmed = labels.map( function ( label ) { return label.slice( cut ).trim(); } );
		return trimmed.some( function ( label ) { return ! label; } ) ? labels : trimmed;
	}

	var sections = document.querySelectorAll( '.astroway-section[data-astroway-tabs]' );
	if ( ! sections.length ) {
		return;
	}

	Array.prototype.forEach.call( sections, function ( section, index ) {
		var panels = Array.prototype.filter.call( section.children, function ( node ) {
			return 1 === node.nodeType && ! node.classList.contains( 'astroway-tabs' );
		} );

		// One card is not a set of tabs, and a strip with a single button on it
		// is a control that does nothing.
		if ( panels.length < 2 ) {
			return;
		}

		var list = document.createElement( 'div' );
		list.className = 'astroway-tabs';
		list.setAttribute( 'role', 'tablist' );

		var labels = shorten( panels.map( function ( panel ) {
			var heading = panel.querySelector( '.astroway-card__title' );
			return heading ? heading.textContent.trim() : '';
		} ) );

		var tabs = panels.map( function ( panel, i ) {
			var tabId = 'astroway-tab-' + index + '-' + i;

			if ( ! panel.id ) {
				panel.id = 'astroway-panel-' + index + '-' + i;
			}
			panel.setAttribute( 'role', 'tabpanel' );
			panel.setAttribute( 'aria-labelledby', tabId );
			panel.hidden = 0 !== i;

			var tab = document.createElement( 'button' );
			tab.type      = 'button';
			tab.id        = tabId;
			tab.className = 'astroway-tabs__tab';
			tab.setAttribute( 'role', 'tab' );
			tab.setAttribute( 'aria-controls', panel.id );
			tab.setAttribute( 'aria-selected', 0 === i ? 'true' : 'false' );
			tab.tabIndex    = 0 === i ? 0 : -1;
			tab.textContent = labels[ i ] || String( i + 1 );

			list.appendChild( tab );
			return tab;
		} );

		function select( next, focus ) {
			tabs.forEach( function ( tab, i ) {
				var on = i === next;
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				tab.tabIndex   = on ? 0 : -1;
				panels[ i ].hidden = ! on;
			} );
			if ( focus ) {
				tabs[ next ].focus();
			}
		}

		list.addEventListener( 'click', function ( event ) {
			var i = tabs.indexOf( event.target );
			if ( i > -1 ) {
				select( i, false );
			}
		} );

		// Arrow keys move between tabs, which is what a tablist is expected to
		// do; without it the strip is a row of buttons that happens to look like
		// tabs to everyone except the person using a keyboard.
		list.addEventListener( 'keydown', function ( event ) {
			var current = tabs.indexOf( event.target );
			if ( current < 0 ) {
				return;
			}
			var last = tabs.length - 1;
			var next = null;

			if ( 'ArrowRight' === event.key || 'ArrowDown' === event.key ) {
				next = current === last ? 0 : current + 1;
			} else if ( 'ArrowLeft' === event.key || 'ArrowUp' === event.key ) {
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

		section.insertBefore( list, section.firstChild );
		section.classList.add( 'astroway-section--tabbed' );
	} );
}() );
