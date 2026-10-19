/* AstroWay Gutenberg block editor UI. Hand-rolled vanilla JS using wp.* globals — no build step. */
( function ( wp ) {
	'use strict';

	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var TextControl       = wp.components.TextControl;
	var SelectControl     = wp.components.SelectControl;
	var PanelBody         = wp.components.PanelBody;
	var Disabled          = wp.components.Disabled;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps     = wp.blockEditor.useBlockProps;
	var ServerSideRender  = wp.serverSideRender;
	var __                = wp.i18n.__;

	var SIGN_OPTIONS = [
		{ label: __( 'Default (today\'s featured sign)', 'astroway' ), value: '' },
		{ label: __( 'Aries', 'astroway' ),       value: 'aries' },
		{ label: __( 'Taurus', 'astroway' ),      value: 'taurus' },
		{ label: __( 'Gemini', 'astroway' ),      value: 'gemini' },
		{ label: __( 'Cancer', 'astroway' ),      value: 'cancer' },
		{ label: __( 'Leo', 'astroway' ),         value: 'leo' },
		{ label: __( 'Virgo', 'astroway' ),       value: 'virgo' },
		{ label: __( 'Libra', 'astroway' ),       value: 'libra' },
		{ label: __( 'Scorpio', 'astroway' ),     value: 'scorpio' },
		{ label: __( 'Sagittarius', 'astroway' ), value: 'sagittarius' },
		{ label: __( 'Capricorn', 'astroway' ),   value: 'capricorn' },
		{ label: __( 'Aquarius', 'astroway' ),    value: 'aquarius' },
		{ label: __( 'Pisces', 'astroway' ),      value: 'pisces' }
	];

	var DECK_OPTIONS = [
		{ label: __( 'Rider-Waite-Smith', 'astroway' ), value: 'rider-waite' },
		{ label: __( 'Marseille', 'astroway' ),         value: 'marseille' },
		{ label: __( 'Lenormand', 'astroway' ),         value: 'lenormand' }
	];

	// Language dropdown — 21 api-supported locales (Accept-Language Phase 6, api v2.30.0).
	// Native script labels per WP Polyglots convention; empty value = use site default locale.
	// A horoscope with no sign asks the visitor, so the blank choice says that.
	var HORO_SIGN_OPTIONS = [ { label: __( 'Visitor picks the sign', 'astroway' ), value: '' } ].concat( SIGN_OPTIONS.slice( 1 ) );

	var LANG_OPTIONS = [
		{ label: __( 'Site default', 'astroway' ), value: '' },
		{ label: 'English (en)',           value: 'en' },
		{ label: 'Українська (uk)',        value: 'uk' },
		{ label: 'Deutsch (de)',           value: 'de' },
		{ label: 'Русский (ru)',           value: 'ru' },
		{ label: 'Polski (pl)',            value: 'pl' },
		{ label: 'Español (es)',           value: 'es' },
		{ label: 'Português (pt)',         value: 'pt' },
		{ label: 'Français (fr)',          value: 'fr' },
		{ label: 'Italiano (it)',          value: 'it' },
		{ label: 'Nederlands (nl)',        value: 'nl' },
		{ label: 'Čeština (cs)',           value: 'cs' },
		{ label: 'Română (ro)',            value: 'ro' },
		{ label: 'Magyar (hu)',            value: 'hu' },
		{ label: 'Ελληνικά (el)',          value: 'el' },
		{ label: 'Türkçe (tr)',            value: 'tr' },
		{ label: 'العربية (ar)',           value: 'ar' },
		{ label: 'हिन्दी (hi)',             value: 'hi' },
		{ label: '日本語 (ja)',             value: 'ja' },
		{ label: '한국어 (ko)',             value: 'ko' },
		{ label: 'Tiếng Việt (vi)',        value: 'vi' },
		{ label: 'Bahasa Indonesia (id)',  value: 'id' }
	];

	var LANG_FIELD = { name: 'lang', type: 'select', label: __( 'Language', 'astroway' ), options: LANG_OPTIONS };

	// Blank follows the section around the block, then the site setting.
	var LOOK_OPTIONS = [
		{ label: __( 'Site default', 'astroway' ),  value: '' },
		{ label: __( 'Instrument', 'astroway' ),    value: 'instrument' },
		{ label: __( 'Night sky', 'astroway' ),     value: 'night' },
		{ label: __( 'Theme colours', 'astroway' ), value: 'native' }
	];
	var LOOK_FIELD = { name: 'look', type: 'select', label: __( 'Style', 'astroway' ), options: LOOK_OPTIONS };

	// Panels and fields come from PHP, the same manifest Elementor, Beaver and
	// WPBakery read, so the four editors cannot drift apart. Style is added here,
	// because the generated blocks below take it too.
	var BLOCKS  = {};
	var WIDGETS = window.astrowayWidgets || {};
	Object.keys( WIDGETS ).forEach( function ( slug ) {
		BLOCKS[ 'astroway/' + slug ] = { panel: WIDGETS[ slug ].panel, fields: WIDGETS[ slug ].fields || [] };
	} );

	function buildControl( field, attrs, setAttributes ) {
		var common = {
			key:      field.name,
			label:    field.label,
			value:    attrs[ field.name ] || '',
			onChange: function ( value ) {
				var update = {};
				update[ field.name ] = value;
				setAttributes( update );
			}
		};

		if ( 'select' === field.type ) {
			common.options = field.options;
			return el( SelectControl, common );
		}
		return el( TextControl, common );
	}

	/**
	 * Attributes as the front end will see them, with the section's sign and
	 * language filled into whatever the block left blank.
	 *
	 * ServerSideRender renders through the REST endpoint, which knows nothing
	 * about block context, so a horoscope inside a Leo section previewed as a
	 * horoscope with no sign at all. PHP already resolves this the same way; this
	 * only makes the editor agree with the page. The block's own attributes are
	 * not touched, so removing it from the section restores its own blank.
	 */
	function previewAttributes( attrs, context ) {
		var merged = Object.assign( {}, attrs );
		[ 'sign', 'lang', 'look' ].forEach( function ( name ) {
			var inherited = context && context[ 'astroway/' + name ];
			if ( inherited && ! merged[ name ] ) {
				merged[ name ] = inherited;
			}
		} );
		return merged;
	}

	function makeEdit( blockName ) {
		var cfg = BLOCKS[ blockName ];
		return function Edit( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: cfg.panel, initialOpen: true },
						cfg.fields.concat( [ LOOK_FIELD ] ).map( function ( field ) {
							return buildControl( field, props.attributes, props.setAttributes );
						} )
					)
				),
				el( 'div', blockProps,
					el( Disabled, null,
						el( ServerSideRender, {
							block:      blockName,
							attributes: previewAttributes( props.attributes, props.context )
						} )
					)
				)
			);
		};
	}

	Object.keys( BLOCKS ).forEach( function ( blockName ) {
		wp.blocks.registerBlockType( blockName, {
			edit: makeEdit( blockName ),
			save: function () { return null; }
		} );
	} );

	// The generated blocks: roughly seven hundred endpoints the plugin covers
	// without a hand-built card for each. PHP registers them and inlines the list
	// (name, title, fields) before this script; here they get the same preview and
	// the same inspector as everything else, built from their attribute names.
	// A required field is marked, because the preview is a note about the missing
	// attribute until it is filled and that should not read as a fault.
	( window.astrowayGeneratedBlocks || [] ).forEach( function ( entry ) {
		var name   = entry[ 0 ];
		var title  = entry[ 1 ];
		var fields = entry[ 2 ] || [];

		if ( wp.blocks.getBlockType( name ) ) {
			return;
		}

		wp.blocks.registerBlockType( name, {
			edit: function Edit( props ) {
				var blockProps = useBlockProps();
				var controls   = fields.map( function ( field ) {
					var attr = field[ 0 ];
					return el( TextControl, {
						key:      attr,
						label:    field[ 1 ] ? attr + ' *' : attr,
						value:    props.attributes[ attr ] || '',
						onChange: function ( value ) {
							var update = {};
							update[ attr ] = value;
							props.setAttributes( update );
						}
					} );
				} );
				controls.push( buildControl( LANG_FIELD, props.attributes, props.setAttributes ) );
				controls.push( buildControl( LOOK_FIELD, props.attributes, props.setAttributes ) );

				return el(
					Fragment,
					null,
					el( InspectorControls, null,
						el( PanelBody, { title: title, initialOpen: true }, controls )
					),
					el( 'div', blockProps,
						el( Disabled, null,
							el( ServerSideRender, {
								block:      name,
								attributes: previewAttributes( props.attributes, props.context )
							} )
						)
					)
				);
			},
			save: function () { return null; }
		} );
	} );

	// The container is the one block that is not server-rendered in the editor:
	// it holds other blocks, so it shows them, and the sign it sets reaches them
	// through block context rather than through a preview request.
	var InnerBlocks = wp.blockEditor.InnerBlocks;

	wp.blocks.registerBlockType( 'astroway/astrology-section', {
		edit: function Edit( props ) {
			var blockProps = useBlockProps( { className: 'astroway-section' } );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Section', 'astroway' ), initialOpen: true },
						el( SelectControl, {
							key:      'sign',
							label:    __( 'Zodiac sign for blocks inside', 'astroway' ),
							value:    props.attributes.sign || '',
							options:  SIGN_OPTIONS,
							onChange: function ( value ) { props.setAttributes( { sign: value } ); }
						} ),
						el( SelectControl, {
							key:      'lang',
							label:    __( 'Language for blocks inside', 'astroway' ),
							value:    props.attributes.lang || '',
							options:  LANG_OPTIONS,
							onChange: function ( value ) { props.setAttributes( { lang: value } ); }
						} ),
						el( SelectControl, {
							key:      'look',
							label:    __( 'Style for blocks inside', 'astroway' ),
							value:    props.attributes.look || '',
							options:  LOOK_OPTIONS,
							onChange: function ( value ) { props.setAttributes( { look: value } ); }
						} ),
						el( SelectControl, {
							key:      'layout',
							label:    __( 'Layout on the page', 'astroway' ),
							value:    props.attributes.layout || 'stack',
							options:  [
								{ label: __( 'One after another', 'astroway' ), value: 'stack' },
								{ label: __( 'Tabs', 'astroway' ), value: 'tabs' }
							],
							help:     __( 'Tabs need JavaScript. Without it the cards stay stacked, so nothing is lost.', 'astroway' ),
							onChange: function ( value ) { props.setAttributes( { layout: value } ); }
						} )
					)
				),
				el( 'div', blockProps, el( InnerBlocks, null ) )
			);
		},
		// Inner blocks have to be serialised into the post, so this one saves
		// their markup even though the wrapper itself is rendered in PHP.
		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
