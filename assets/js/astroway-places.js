/* Suggestions under the city field while the visitor types. The form works
   without them: the server looks the city up again on submit and asks which
   one when the name fits several. */
( function () {
	'use strict';
	var fields = document.querySelectorAll( 'input[data-astroway-places]' );
	Array.prototype.forEach.call( fields, function ( input, n ) {
		var list = document.createElement( 'datalist' );
		var timer = 0;
		var last = '';
		list.id = 'astroway-places-' + n;
		input.setAttribute( 'list', list.id );
		input.parentNode.appendChild( list );
		input.addEventListener( 'input', function () {
			var q = input.value.trim();
			clearTimeout( timer );
			if ( q.length < 3 || q === last ) {
				return;
			}
			timer = setTimeout( function () {
				last = q;
				var url = input.getAttribute( 'data-astroway-places' ) + ( input.getAttribute( 'data-astroway-places' ).indexOf( '?' ) < 0 ? '?' : '&' ) + 'q=' + encodeURIComponent( q );
				fetch( url, { credentials: 'omit' } ).then( function ( r ) {
					return r.ok ? r.json() : { places: [] };
				} ).then( function ( data ) {
					if ( input.value.trim() !== q ) {
						return;
					}
					list.textContent = '';
					( data.places || [] ).forEach( function ( label ) {
						var o = document.createElement( 'option' );
						o.value = label;
						list.appendChild( o );
					} );
				} ).catch( function () {} );
			}, 300 );
		} );
	} );
}() );
