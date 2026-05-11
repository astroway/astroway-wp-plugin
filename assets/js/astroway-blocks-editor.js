/* AstroWay Gutenberg block editor UI. Hand-rolled vanilla JS using wp.* globals — no build step. */
( function ( wp ) {
	'use strict';

	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var TextControl       = wp.components.TextControl;
	var SelectControl     = wp.components.SelectControl;
	var PanelBody         = wp.components.PanelBody;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var ServerSideRender  = wp.serverSideRender;
	var __                = wp.i18n.__;

	var SIGN_OPTIONS = [
		{ label: __( 'Default (today\'s featured sign)', 'astroway-wp-plugin' ), value: '' },
		{ label: __( 'Aries', 'astroway-wp-plugin' ),       value: 'aries' },
		{ label: __( 'Taurus', 'astroway-wp-plugin' ),      value: 'taurus' },
		{ label: __( 'Gemini', 'astroway-wp-plugin' ),      value: 'gemini' },
		{ label: __( 'Cancer', 'astroway-wp-plugin' ),      value: 'cancer' },
		{ label: __( 'Leo', 'astroway-wp-plugin' ),         value: 'leo' },
		{ label: __( 'Virgo', 'astroway-wp-plugin' ),       value: 'virgo' },
		{ label: __( 'Libra', 'astroway-wp-plugin' ),       value: 'libra' },
		{ label: __( 'Scorpio', 'astroway-wp-plugin' ),     value: 'scorpio' },
		{ label: __( 'Sagittarius', 'astroway-wp-plugin' ), value: 'sagittarius' },
		{ label: __( 'Capricorn', 'astroway-wp-plugin' ),   value: 'capricorn' },
		{ label: __( 'Aquarius', 'astroway-wp-plugin' ),    value: 'aquarius' },
		{ label: __( 'Pisces', 'astroway-wp-plugin' ),      value: 'pisces' }
	];

	var DECK_OPTIONS = [
		{ label: __( 'Rider-Waite-Smith', 'astroway-wp-plugin' ), value: 'rider-waite' },
		{ label: __( 'Marseille', 'astroway-wp-plugin' ),         value: 'marseille' },
		{ label: __( 'Lenormand', 'astroway-wp-plugin' ),         value: 'lenormand' }
	];

	var CHART_FIELDS = [
		{ name: 'date', type: 'text', label: __( 'Date (YYYY-MM-DD)', 'astroway-wp-plugin' ) },
		{ name: 'time', type: 'text', label: __( 'Time (HH:MM)', 'astroway-wp-plugin' ) },
		{ name: 'lat',  type: 'text', label: __( 'Latitude (-90 to 90)', 'astroway-wp-plugin' ) },
		{ name: 'lon',  type: 'text', label: __( 'Longitude (-180 to 180)', 'astroway-wp-plugin' ) },
		{ name: 'name', type: 'text', label: __( 'Person name (optional)', 'astroway-wp-plugin' ) },
		{ name: 'tz',   type: 'text', label: __( 'Timezone (Europe/Kyiv or +03:00)', 'astroway-wp-plugin' ) }
	];

	var BLOCKS = {
		'astroway/natal-chart': {
			panel:  __( 'Birth data', 'astroway-wp-plugin' ),
			fields: CHART_FIELDS
		},
		'astroway/bodygraph': {
			panel:  __( 'Birth data', 'astroway-wp-plugin' ),
			fields: CHART_FIELDS
		},
		'astroway/daily-horoscope': {
			panel:  __( 'Daily horoscope', 'astroway-wp-plugin' ),
			fields: [
				{ name: 'sign', type: 'select', label: __( 'Zodiac sign', 'astroway-wp-plugin' ), options: SIGN_OPTIONS }
			]
		},
		'astroway/moon-phase': {
			panel:  __( 'Moon phase', 'astroway-wp-plugin' ),
			fields: [
				{ name: 'date', type: 'text', label: __( 'Date (leave blank for today)', 'astroway-wp-plugin' ) }
			]
		},
		'astroway/tarot-daily': {
			panel:  __( 'Daily Tarot', 'astroway-wp-plugin' ),
			fields: [
				{ name: 'deck', type: 'select', label: __( 'Deck', 'astroway-wp-plugin' ), options: DECK_OPTIONS }
			]
		}
	};

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

	function makeEdit( blockName ) {
		var cfg = BLOCKS[ blockName ];
		return function Edit( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: cfg.panel, initialOpen: true },
						cfg.fields.map( function ( field ) {
							return buildControl( field, props.attributes, props.setAttributes );
						} )
					)
				),
				el( ServerSideRender, {
					block:      blockName,
					attributes: props.attributes
				} )
			);
		};
	}

	Object.keys( BLOCKS ).forEach( function ( blockName ) {
		wp.blocks.registerBlockType( blockName, {
			edit: makeEdit( blockName ),
			save: function () { return null; }
		} );
	} );
} )( window.wp );
