<?php
namespace AcrossWP_Model_Selector\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Define the internationalization functionality
 *
 * @package    AcrossWP_Model_Selector
 * @subpackage AcrossWP_Model_Selector/includes
 */
class I18n {

	/**
	 * Actually load the plugin textdomain on `init`
	 */
	public function do_load_textdomain() {
		load_plugin_textdomain(
			'acrosswp-model-selector',
			false,
			plugin_basename( dirname( \ACWP_MODEL_SELECTOR_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
