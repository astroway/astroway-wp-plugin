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

	var CHART_FIELDS = [
		{ name: 'date', type: 'text', label: __( 'Date (YYYY-MM-DD)', 'astroway' ) },
		{ name: 'time', type: 'text', label: __( 'Time (HH:MM)', 'astroway' ) },
		{ name: 'lat',  type: 'text', label: __( 'Latitude (-90 to 90)', 'astroway' ) },
		{ name: 'lon',  type: 'text', label: __( 'Longitude (-180 to 180)', 'astroway' ) },
		{ name: 'name', type: 'text', label: __( 'Person name (optional)', 'astroway' ) },
		{ name: 'tz',   type: 'text', label: __( 'Timezone (Europe/Kyiv or +03:00)', 'astroway' ) },
		LANG_FIELD
	];

	var BLOCKS = {
		'astroway/natal-chart': {
			panel:  __( 'Birth data', 'astroway' ),
			fields: CHART_FIELDS
		},
		'astroway/bodygraph': {
			panel:  __( 'Birth data', 'astroway' ),
			fields: CHART_FIELDS
		},
		'astroway/daily-horoscope': {
			panel:  __( 'Daily horoscope', 'astroway' ),
			fields: [
				{ name: 'sign', type: 'select', label: __( 'Zodiac sign', 'astroway' ), options: SIGN_OPTIONS },
				LANG_FIELD
			]
		},
		'astroway/moon-phase': {
			panel:  __( 'Moon phase', 'astroway' ),
			fields: [
				{ name: 'date', type: 'text', label: __( 'Date (leave blank for today)', 'astroway' ) },
				LANG_FIELD
			]
		},
		'astroway/daily-tarot': {
			panel:  __( 'Daily Tarot', 'astroway' ),
			fields: [
				{ name: 'deck', type: 'select', label: __( 'Deck', 'astroway' ), options: DECK_OPTIONS },
				LANG_FIELD
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
						cfg.fields.map( function ( field ) {
							return buildControl( field, props.attributes, props.setAttributes );
						} )
					)
				),
				el( 'div', blockProps,
					el( Disabled, null,
						el( ServerSideRender, {
							block:      blockName,
							attributes: props.attributes
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
} )( window.wp );
