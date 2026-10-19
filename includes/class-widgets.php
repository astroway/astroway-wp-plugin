<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One render path for every page builder: the shortcode's own callback.
 *
 * Calling it directly rather than through a shortcode string keeps a quote or
 * a bracket typed into a field from breaking the tag.
 *
 * @since 2.5.0
 */
final class Widgets {

	/** Blocks whose shortcode is not `astroway_` plus the slug. */
	private const TAGS = [
		'natal-chart' => 'astroway_natal',
		'daily-tarot' => 'astroway_tarot_card',
	];

	public static function tag( string $slug ): string {
		return self::TAGS[ $slug ] ?? 'astroway_' . str_replace( '-', '_', $slug );
	}

	/**
	 * The card for a widget, as its shortcode would print it.
	 *
	 * @param string $slug Block slug, e.g. `daily-horoscope`.
	 * @param array  $atts Builder settings; blanks and keys the widget does not know are dropped.
	 */
	public static function render( string $slug, array $atts ): string {
		$widget = WidgetManifest::widget( $slug );
		$tag    = self::tag( $slug );
		$cb     = $GLOBALS['shortcode_tags'][ $tag ] ?? null;
		if ( null === $widget || ! is_callable( $cb ) ) {
			return '';
		}
		$known = array_merge( array_column( $widget['fields'], 'name' ), $widget['hidden'] );
		$clean = [];
		foreach ( $atts as $key => $value ) {
			if ( in_array( $key, $known, true ) && is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				$clean[ $key ] = (string) $value;
			}
		}
		// The filters do_shortcode runs: the look is pushed and popped there.
		$pre = apply_filters( 'pre_do_shortcode_tag', false, $tag, $clean, [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's hook, run as do_shortcode runs it.
		if ( false !== $pre ) {
			return (string) $pre;
		}
		$out = call_user_func( $cb, $clean, '', $tag );
		return (string) apply_filters( 'do_shortcode_tag', $out, $tag, $clean, [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's hook, run as do_shortcode runs it.
	}
}
