<?php

/**
 * Main Disable Sales for WooCommerce plugin class
 */
class WC_Disable_Sales
{
    public string $version;

    public function __construct()
    {
        $this->version = WC_DISABLE_SALES_VERSION;
        $this->init_hooks();
    }

    /**
     * Get a single option from WordPress Options
     */
    public function get_option($option_name)
    {
        return get_option('wc_settings_wc_disable_sales_' . $option_name);
    }

    /**
     * Run hooks
     */
    public function init_hooks() : void
    {
        /**
         * Check if WooCommerce is active and return if not
         */
        if (!$this->is_wc_active()) {
            add_action('admin_notices', [$this, 'print_wc_inactive_notice']);
            return;
        }

        /**
         * Add WC options page section link
         */
        add_filter('woocommerce_settings_tabs_array', [$this, 'add_wc_options_page_section_link'], 50);

        /**
         * Add WC options page section
         */
        add_filter('woocommerce_settings_tabs_wc_disable_sales', [$this, 'add_wc_options_page_section']);
        add_filter('woocommerce_update_options_wc_disable_sales', [$this, 'update_wc_options_page_settings']);

        /**
         * Check if plugin is enabled from settings
         */
        if (!$this->get_option('enable_plugin') || $this->get_option('enable_plugin') == 'no') {
            return;
        }

        /**
         * Make all products not purchasable
         */
        add_filter('woocommerce_variation_is_purchasable', [$this, 'disable_purchases'], 10, 2);
        add_filter('woocommerce_is_purchasable', [$this, 'disable_purchases'], 10, 2);

        /**
         * Show information that shop is disabled inside checkout & cart
         */
        add_action('woocommerce_check_cart_items', [$this, 'show_shop_disabled_info_notice']);
        add_action('woocommerce_checkout_process', [$this, 'show_shop_disabled_info_notice']);

        /**
         * Show a notice that shop is disabled in given dates
         */
        if ($this->get_option('always_show_notice') == 'yes') {
            add_action('template_redirect', [$this, 'show_shop_disabled_info_notice']);
        }
    }

    /**
     * Determine if WooCommerce plugin is active
     */
    public function is_wc_active()
    {
        return in_array(
            'woocommerce/woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        );
    }

    /**
     * Add WooCommerce options page section link
     */
    public function add_wc_options_page_section_link($sections) : array
    {
        $sections['wc_disable_sales'] = __('Disable Sales', WC_DISABLE_SALES_TEXTDOMAIN);
        return $sections;
    }

    /**
     * Handle getting our custom WC settings
     */
    public function wc_get_settings() : array
    {
        $settings = array(
            'section_title' => array(
                'name' => WC_DISABLE_SALES_PLUGIN_NAME,
                'type' => 'title',
                'desc' => __('Allows you to disable sales in given dates.', WC_DISABLE_SALES_TEXTDOMAIN),
                'id' => 'wc_settings_tab_wc_disable_sales_title'
            ),
            'enable_plugin' => array(
                'name' => __('Enable plugin?', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'checkbox',
                'desc' => __('Select to enable the plugin functionality.', WC_DISABLE_SALES_TEXTDOMAIN),
                'id' => 'wc_settings_wc_disable_sales_enable_plugin'
            ),
            'date_from' => array(
                'name' => __('Shop will be disabled from...', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'date',
                'id' => 'wc_settings_wc_disable_sales_date_from'
            ),
            'date_to' => array(
                'name' => __('Shop will be disabled until...', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'date',
                'id' => 'wc_settings_wc_disable_sales_date_to'
            ),
            'repeat' => [
                'name' => __('Repeat', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'checkbox',
                'desc' => __('Select to turn off sales every year', WC_DISABLE_SALES_TEXTDOMAIN),
                'id' => 'wc_settings_wc_disable_sales_repeat'
            ],
            'always_show_notice' => array(
                'name' => __('Always show notice?', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'checkbox',
                'desc' => __('Select to enable "Shop turned off" notice on every page', WC_DISABLE_SALES_TEXTDOMAIN),
                'id' => 'wc_settings_wc_disable_sales_always_show_notice'
            ),
            'custom_notice_message' => array(
                'name' => __('Custom notice message', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'textarea',
                'desc' => __('Use shortcodes below to insert dates. Leave blank to use default. <br/><pre>[date_from]<br />[date_to]</pre>', WC_DISABLE_SALES_TEXTDOMAIN),
                'id' => 'wc_settings_wc_disable_sales_custom_notice_message',
                'placeholder' => __('Shop is disabled from [date_from] to [date_to].', WC_DISABLE_SALES_TEXTDOMAIN)
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_wc_disable_sales_end'
            )
        );
        return apply_filters('wc_settings_wc_disable_sales_settings', $settings);
    }

    /**
     * Add WooCommerce options page section with plugin settings
     */
    public function add_wc_options_page_section() : void
    {
        woocommerce_admin_fields($this->wc_get_settings());
    }

    /**
     * Handle updating our custom WC settings
     */
    public function update_wc_options_page_settings() : void
    {
        woocommerce_update_options($this->wc_get_settings());
    }

    /**
     * Print a notice saying that WooCommerce is not enabled
     */
    public function print_wc_inactive_notice()
    {
        $wp_plugin_install_url =
            is_multisite()
                ? admin_url('/network/plugin-install.php')
                : admin_url('/plugin-install.php');

        $wp_plugins_url =
            is_multisite()
                ? admin_url('/network/plugins.php')
                : admin_url('/plugins.php');

        $is_wc_installed = $this->is_wc_installed();

        $notice =
            $is_wc_installed
                ? [
                    'notice' => __('<a href="%s">Activate WooCommerce</a>'),
                    'url' => $wp_plugins_url,
                ] : [
                    'notice' => __('<a href="%s">Install WooCommerce</a>'),
                    'url' => $wp_plugin_install_url,
                ];

        printf(
            '<div class="%1$s"><p>%2$s</p><p>%3$s</p></div>',
            esc_attr('notice notice-error'),
            __(
                '<strong>' . WC_DISABLE_SALES_PLUGIN_NAME . '</strong> plugin is enabled but needs <strong>WooCommerce</strong> in order to work.',
                WC_DISABLE_SALES_TEXTDOMAIN
            ),
            sprintf(
                $notice['notice'],
                $notice['url']
            )
        );
    }

    /**
     * Print a notice that shop is currently closed
     */
    private function print_closed_store_notice() : void
    {
        $timestamp_from = (new DateTime($this->get_option('date_from')))->getTimestamp();
        $timestamp_to = (new DateTime($this->get_option('date_to')))->getTimestamp();

        $closed_from = wp_date('j. F', $timestamp_from);
        $closed_to = wp_date('j. F', $timestamp_to);

        $notice =
            (!empty($this->get_option('custom_notice_message')))
                ? str_replace(
                    ['[date_from]', '[date_to]'],
                    [$closed_from, $closed_to],
                    $this->get_option('custom_notice_message')
                )
                : sprintf(
                    __('The shop is closed between %s and %s.', WC_DISABLE_SALES_TEXTDOMAIN),
                    $closed_from,
                    $closed_to
                );

        if (!wc_has_notice($notice, 'error')) {
            wc_add_notice($notice, 'error');
        }
    }

    /**
     * Determine if WooCommerce is installed (not activated) by checking if wc folder exists
     */
    private function is_wc_installed() : bool
    {
        $all_dirs = glob(dirname(plugin_dir_path(__DIR__)) . '/*', GLOB_ONLYDIR);

        if (empty($all_dirs)) return false;

        $installed = array_filter($all_dirs, function ($item) {
            return strpos($item, 'woocommerce') !== -1;
        });

        return !empty($installed);
    }

    /**
     * Determine if shop is currently open
     * Consider our custom datetime frames
     */
    private function is_shop_open() : bool
    {
        $now = new DateTime();
        $now_timestamp = $now->getTimestamp();

        $now_year = $now->format('Y');
        $next_year = $now->modify('+1 year')->format('Y');

        $date_from = new DateTime($this->get_option('date_from'));
        $date_to = new DateTime($this->get_option('date_to'));

        /**
         * Check if the date frame repeats
         */
        if ($this->get_option('repeat') == 'yes') {
            /**
             * Prepare from date
             */
            $year_from = $date_from->format('Y');
            $month_from = $date_from->format('m');
            $day_from = $date_from->format('d');

            /**
             * Prepare to date
             */
            $month_to = $date_to->format('m');
            $day_to = $date_to->format('d');
            $year_to = $date_to->format('Y');

            $from_in_current_year = (new DateTime("$now_year-$month_from-$day_from"))->getTimestamp();

            /**
             * Check if date_to is in the same calendar year as date_from
             */
            $breaks_year = $year_to > $year_from;
            if ($breaks_year) {
                $to_in_next_year = (new DateTime("$next_year-$month_to-$day_to"))->getTimestamp();

                if (($now_timestamp >= $from_in_current_year) && ($now_timestamp < $to_in_next_year)) {
                    return false;
                }
            } else {
                $to_in_current_year = (new DateTime("$now_year-$month_to-$day_to"))->getTimestamp();

                if (($now_timestamp >= $from_in_current_year) && ($now_timestamp < $to_in_current_year)) {
                    return false;
                }
            }
        } else {
            $timestamp_from = $date_from->getTimestamp();
            $timestamp_to = $date_to->getTimestamp();

            if (($now_timestamp >= $timestamp_from) && ($now_timestamp < $timestamp_to)) {
                return false;
            }
        }

        /**
         * Shop is open if no above conditions are met
         */
        return true;
    }

    /**
     * Disable purchases for products
     */
    public function disable_purchases(bool $purchasable, \WC_Product $product) : bool
    {
        if (!$this->is_shop_open()) {
            $purchasable = false;
        }

        return $purchasable;
    }

    /**
     * Shows a notice saying that shop is currently disabled
     * Used for checkout and cart pages
     */
    public function show_shop_disabled_info_notice() : void
    {
        if (!(is_cart() || is_checkout()) && !$this->is_shop_open()) {
            $this->print_closed_store_notice();
        }
    }
}
