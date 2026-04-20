<?php
namespace AcrossWP_Model_Selector\Admin\Partials;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the admin menu and settings page for AcrossWP Model Selector.
 *
 * @since      0.0.1
 * @package    AcrossWP_Model_Selector\Admin\Partials
 */
class Menu {

	const OPTION_KEY        = 'acwp_model_selector_preferences';
	const LEGACY_OPTION_KEY = 'aiam_model_preferences';
	const PAGE_SLUG         = 'acrosswp-model-selector';

	/**
	 * Capability types shown on the settings page.
	 *
	 * @var array<string, string>
	 */
	private static $capabilities = array(
		'text_generation'  => 'Text Generation',
		'image_generation' => 'Image Generation',
		'vision'           => 'Vision / Multimodal',
	);

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.1
	 * @var      string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.1
	 * @var      string
	 */
	private $version;

	/**
	 * @param string $plugin_name The plugin slug.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/** Adds the Settings sub-menu page. */
	public function add_menu(): void {
		add_options_page(
			__( 'AcrossWP Model Selector', 'acrosswp-model-selector' ),
			__( 'AcrossWP Model Selector', 'acrosswp-model-selector' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			2
		);
	}

	/** Registers the settings option (must fire on init to support REST API saves). */
	public function register_settings(): void {
		$this->migrate_legacy_preferences();

		register_setting(
			'acwp_model_selector_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'object',
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'text_generation'  => array(
								'type'    => 'string',
								'default' => '',
							),
							'image_generation' => array(
								'type'    => 'string',
								'default' => '',
							),
							'vision'           => array(
								'type'    => 'string',
								'default' => '',
							),
						),
						'additionalProperties' => false,
					),
				),
				'sanitize_callback' => array( $this, 'sanitize_preferences' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitizes model preferences before saving.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, string>
	 */
	public function sanitize_preferences( $input ): array {
		$clean = array();
		if ( ! is_array( $input ) ) {
			return $clean;
		}
		foreach ( array_keys( self::$capabilities ) as $cap_key ) {
			if ( empty( $input[ $cap_key ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $input[ $cap_key ] ) );
			if ( false === strpos( $value, '::' ) ) {
				continue;
			}
			list( $provider, $model ) = explode( '::', $value, 2 );
			$provider                 = sanitize_key( $provider );
			$model                    = sanitize_text_field( $model );
			if ( $provider && $model ) {
				$clean[ $cap_key ] = $provider . '::' . $model;
			}
		}
		return $clean;
	}

	/**
	 * Migrates stored preferences from the legacy option key once.
	 */
	private function migrate_legacy_preferences(): void {
		if ( false !== get_option( self::OPTION_KEY, false ) ) {
			return;
		}

		$legacy_preferences = get_option( self::LEGACY_OPTION_KEY, false );
		if ( false === $legacy_preferences ) {
			return;
		}

		update_option( self::OPTION_KEY, $legacy_preferences );
	}

	/** Renders the settings page — the React app mounts into #acwpms-settings-root. */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'acrosswp-model-selector' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="description"><?php esc_html_e( 'Choose the preferred AI model for each capability type. These selections override the WordPress defaults.', 'acrosswp-model-selector' ); ?></p>
			<div id="acwpms-settings-root"></div>
		</div>
		<?php
	}
}
