<?php
/**
 * Plugin Name:       WC Disable Sales
 * Plugin URI:        https://github.com/trykoszko/wc-disable-sales
 * Description:       Allows to temporarily disable WooCommerce shop abilities in given timeframe
 * Version:           1.0.0
 * Author:            sors Michal Trykoszko
 * Author URI:        https://github.com/trykoszko
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wc-disable-sales
 * Domain Path:       /languages
 */

/**
 * Exit if it's not a WordPress context
 */
if (!defined('ABSPATH')) exit;

/**
 * Define plugin constants
 */
if (!defined('WC_DISABLE_SALES_VERSION')) define('WC_DISABLE_SALES_VERSION', '1.0.0');
if (!defined('WC_DISABLE_SALES_PLUGIN_NAME')) define('WC_DISABLE_SALES_PLUGIN_NAME', 'WC Disable Sales');
if (!defined('WC_DISABLE_SALES_TEXTDOMAIN')) define('WC_DISABLE_SALES_TEXTDOMAIN', 'wc-disable-sales');

/**
 * Initialize plugin
 */
function init_wc_disable_sales()
{
    require_once(__DIR__ . "/src/class-wc-disable-sales.php");

    $plugin = new WC_Disable_Sales();
    $plugin->run();
}
init_wc_disable_sales();
