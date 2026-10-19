<?php
namespace AstroWay\WPPlugin\Builders;

use AstroWay\WPPlugin\WidgetManifest;
use AstroWay\WPPlugin\Widgets;

if ( ! defined( 'ABSPATH' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * One class for all the widgets: the slug arrives in the constructor
 * arguments, which Elementor hands to every instance it makes of a type.
 *
 * @since 2.5.0
 */
class ElementorWidget extends \Elementor\Widget_Base {

	/** @var string */
	private $astroway_slug = '';

	/**
	 * @param array      $data Element data; empty for the type itself.
	 * @param array|null $args `astroway_slug` names the widget.
	 */
	public function __construct( $data = [], $args = null ) {
		$this->astroway_slug = is_array( $args ) ? (string) ( $args['astroway_slug'] ?? '' ) : '';
		parent::__construct( $data, $args );
	}

	private function spec(): array {
		return WidgetManifest::widget( $this->astroway_slug ) ?? [
			'title'    => '',
			'keywords' => [],
			'panel'    => '',
			'fields'   => [],
		];
	}

	public function get_name() {
		return Elementor::name_for( $this->astroway_slug );
	}

	public function get_title() {
		return $this->spec()['title'];
	}

	public function get_icon() {
		return 'eicon-star';
	}

	public function get_categories() {
		return [ 'astroway' ];
	}

	public function get_keywords() {
		return array_values( array_unique( array_merge( [ 'astroway', 'astrology' ], $this->spec()['keywords'] ) ) );
	}

	// Elementor caches widget HTML for a day; today's horoscope must not outlive today.
	public function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$spec = $this->spec();
		$this->start_controls_section(
			'astroway_content',
			[
				'label' => $spec['panel'],
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		foreach ( $spec['fields'] as $f ) {
			$control = [
				'label'   => $f['label'],
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			];
			if ( 'select' === $f['type'] ) {
				$control['type']    = \Elementor\Controls_Manager::SELECT;
				$control['options'] = $f['options'];
				$control['default'] = (string) array_key_first( $f['options'] );
			}
			$this->add_control( $f['name'], $control );
		}
		$this->end_controls_section();
	}

	protected function render(): void {
		echo Widgets::render( $this->astroway_slug, (array) $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the card escapes its own output.
	}
}
