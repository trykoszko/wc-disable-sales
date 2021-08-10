<?php

class WC_Disable_Sales
{
    public string $version;

    public function __construct()
    {
        $this->version = WC_DISABLE_SALES_VERSION;
    }

    /**
     * Run plugin
     */
    public function run() : void
    {
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
         * Check if plugin is enabled from settings
         */
        if (!$this->get_option('enable_plugin') == 'yes') {
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

    public function wc_get_settings() : array
    {
        $settings = array(
            'section_title' => array(
                'name'     => __('WC Disable Sales', WC_DISABLE_SALES_TEXTDOMAIN),
                'type'     => 'title',
                'desc'     => __('Allows you to disable sales in given dates.', WC_DISABLE_SALES_TEXTDOMAIN),
                'id'       => 'wc_settings_tab_wc_disable_sales_title'
            ),
            'enable_plugin' => array(
                'name' => __('Enable plugin?', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'checkbox',
                'desc' => __('Select to enable the plugin functionality.', WC_DISABLE_SALES_TEXTDOMAIN),
                'id'   => 'wc_settings_wc_disable_sales_enable_plugin'
            ),
            'date_from' => array(
                'name' => __('Shop will be disabled from...', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'date',
                'id'   => 'wc_settings_wc_disable_sales_date_from'
            ),
            'date_to' => array(
                'name' => __('Shop will be disabled until...', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'date',
                'id'   => 'wc_settings_wc_disable_sales_date_to'
            ),
            'always_show_notice' => array(
                'name' => __('Always show notice?', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'checkbox',
                'desc' => __('Select to enable the shop disabled notice on every page.', WC_DISABLE_SALES_TEXTDOMAIN),
                'id'   => 'wc_settings_wc_disable_sales_always_show_notice'
            ),
            'custom_notice_message' => array(
                'name' => __('Custom notice message', WC_DISABLE_SALES_TEXTDOMAIN),
                'type' => 'text',
                'desc' => __('Use shortcodes below to insert dates. Leave blank to use default. <br/><pre>[date_from]<br />[date_to]</pre>', WC_DISABLE_SALES_TEXTDOMAIN),
                'id'   => 'wc_settings_wc_disable_sales_custom_notice_message',
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
                '<strong>WC Disable Sales</strong> plugin is enabled but needs <strong>WooCommerce</strong> in order to work.',
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
            $this->get_option('show_custom_notice')
                ? str_replace(
                    ['[date_from]', '[date_to]'],
                    [$closed_from, $closed_to],
                    $this->get_option('custom_notice_message')
                )
                : sprintf(
                    __('The shop is closed between %s and %s', WC_DISABLE_SALES_TEXTDOMAIN),
                    $closed_from,
                    $closed_to
                );

        wc_add_notice($notice, 'error');
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
        $now = (new DateTime())->getTimestamp();

        $timestamp_from = (new DateTime($this->get_option('date_from')))->getTimestamp();
        $timestamp_to = (new DateTime($this->get_option('date_to')))->getTimestamp();

        if (($now >= $timestamp_from) && ($now < $timestamp_to)) {
            return true;
        }

        return false;
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
        if (!$this->is_shop_open()) {
            $this->print_closed_store_notice();
        }
    }
}
