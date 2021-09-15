<?php

if (!defined('ABSPATH'))
    exit;

class AWDP_Front_End
{

    static $cart_error = array();
    /**
     * The single instance of WordPress_Plugin_Template_Settings.
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;
    public $products = false;
    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;
    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;
    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;
    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    private $discount;
    private $conversion_unit = false;
    /**
     * Check if price has to be display in cart and checkout
     * @var type
     * @var boolean
     * @access private
     * @since 3.4.2
     */
    private $show_price = false;

    function __construct($discount, $file = '', $version = '1.0.0') {

        $this->_version = $version;
        $this->_token = AWDP_TOKEN;
        $this->discount = $discount;
        add_action('init', array($this, 'register_awdp_discounts'));

        if ( $this->awdp_check_woocommerce_active() ) {

            // Apply Fee
            // add_filter('woocommerce_cart_calculate_fees', array( $this, 'cart_apply_discount'), 10, 1 );

            // Cart Item Price
            add_filter('woocommerce_cart_item_price', array($this, 'cart_price_view'), 1000, 3);
            add_filter('woocommerce_cart_item_price_html', array($this, 'cart_price_view'), 1000, 3);

            // Price
            add_filter('woocommerce_product_get_price', array($this, 'get_product_price'), 99, 2 );
            add_filter('woocommerce_product_get_regular_price', array($this, 'get_product_price'), 99, 2 );

            // Change Discount Price HTML View
            add_filter('woocommerce_get_price_html', array($this, 'get_product_price_html'), 100, 2 );

            // Variations
            add_filter('woocommerce_product_variation_get_regular_price', array($this, 'get_product_price'), 100, 2 );
            add_filter('woocommerce_product_variation_get_price', array($this, 'get_product_price'), 100, 2 );

            // Pricing table
            if( false === get_option('awdp_table_position') ){
                $tablePosition = get_option('tableposition');
            } else {
                $tablePosition = get_option('awdp_table_position');
            }

            if ( 'before_product' == $tablePosition ) {
                add_filter( 'woocommerce_before_single_product', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'before_product_summary' == $tablePosition ) {
                add_filter( 'woocommerce_before_single_product_summary', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'in_product_summary' == $tablePosition ) {
                add_filter( 'woocommerce_single_product_summary', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'before_form' == $tablePosition ) {
                add_filter( 'woocommerce_before_add_to_cart_form', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'before_variations_form' == $tablePosition ) {
                add_filter( 'woocommerce_before_variations_form', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'before_button' == $tablePosition ) {
                add_filter( 'woocommerce_before_add_to_cart_button', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'after_button' == $tablePosition ) {
                add_filter( 'woocommerce_after_add_to_cart_button', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'after_variations_form' == $tablePosition ) {
                add_filter( 'woocommerce_after_variations_form', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'after_form' == $tablePosition ) {
                add_filter( 'woocommerce_after_add_to_cart_form', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'meta_start' == $tablePosition ) {
                add_filter( 'woocommerce_product_meta_start', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'meta_end' == $tablePosition ) {
                add_filter( 'woocommerce_product_meta_end', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'after_product_summary' == $tablePosition ) {
                add_filter( 'woocommerce_after_single_product_summary', array($this, 'show_pricing_table'), 98, 3 );
            } else if ( 'after_product' == $tablePosition ) {
                add_filter( 'woocommerce_after_single_product', array($this, 'show_pricing_table'), 98, 3 );
            } else {
                add_filter( 'woocommerce_before_add_to_cart_button', array($this, 'show_pricing_table'), 98, 3 );
            }

            // Apply Cart Rules
            add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_cart_discounts' ), 98, 2 );

            add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'addVirtualCoupon'), 10, 2 );
            add_action( 'woocommerce_after_calculate_totals', array( $this, 'applyFakeCoupons') );
            add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'couponLabel'), 10, 2 );

            add_filter( 'woocommerce_coupon_message', array($this, 'coupon_message'), 10, 3 );
            add_filter( 'woocommerce_coupon_error', array($this, 'coupon_message'), 10, 3 );

            // Adding Frontend Styles
            add_action('wp_footer', array($this, 'awdp_styles'), 25);

            // Cart 
            add_action( 'woocommerce_cart_item_subtotal', array( $this, 'wdpCartLoop' ), 99, 3 );

            // Coupon Not Enabled Warning
            add_action('admin_notices', array($this, 'wdpAdminNotice'));
            
        }
    }

    /**
     * Handling Virtual Coupon
     * @param $cart_obj object
     */

    public function addVirtualCoupon($response, $curr_coupon_code) {

        return $this->discount->addVirtualCoupon($response, $curr_coupon_code);

    }

    public function applyFakeCoupons() {

        return $this->discount->applyFakeCoupons();

    }

    public function couponLabel($label, $coupon) {

        return $this->discount->couponLabel($label, $coupon);

    }

    public function coupon_message($msg, $msg_code, $coupon=null) {
        $awdappliedCode = $coupon->get_code();
        $awdpluginLabel = get_option('awdp_fee_label') ? get_option('awdp_fee_label') : 'Discount';
        if ( $awdappliedCode == $awdpluginLabel || mb_strtolower($awdappliedCode, 'UTF-8') == mb_strtolower($awdpluginLabel, 'UTF-8')) {
            return '';
        }
        return $msg;
    }

    /**
     * Show quantity discount on cart items
     * @param $cart_obj object
    **/
    public function wdpCartLoop ( $wc, $cart_content, $cart_item_key ) {

        return $this->discount->wdpCartLoop( $wc, $cart_content, $cart_item_key );

    }

    /**
     * Admin Notices
    **/
    public function wdpAdminNotice () {

        if ( 'yes' !== get_option( 'woocommerce_enable_coupons' ) ) { ?>
            <div class="error">
                <p><strong><?php echo AWDP_PLUGIN_NAME; ?></strong> uses virtual coupons for applying discounts. For proper working of our plugin, please enable coupons (WooCommerce -> Settings -> Enable coupons).</p>
            </div>
        <?php }

    }

    /**
     * Apply the discount as fee
     * @param $cart_obj object
    **/
    public function cart_price_view( $item_price, $cart_item ) {

        return $this->discount->cart_discount_items( $item_price, $cart_item );

    }

    /**
     * Apply cart discounts
     * @param $cart_obj object
     */
    public function apply_cart_discounts( $cart_object ) {

        return $this->discount->apply_cart_discounts( $cart_object );

    }

    // AWPD Prodcut Price After Discount
    public function get_product_price( $price, $product ) {

        if ( is_admin() ) // disable price change on admin
            return $price;
        else if ( $price != '' )
            return $this->discount->get_product_price( $price, $product );
        else
            return $price;

    }

    // Price HTML Display
    public function get_product_price_html( $price, $product ) {

        return $this->discount->get_product_price_html( $price, $product );

    }

    //Pricing table
    public function show_pricing_table() {

        return $this->discount->show_pricing_table();

    }

    /**
     * Check if woocommerce plugin is active
     */
    public function awdp_check_woocommerce_active() {

        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return true;
        }
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins['woocommerce/woocommerce.php']))
                return true;
        }
        return false;

    }

    public function awdp_styles() {

        $couponLabel = get_option('awdp_fee_label') ? mb_strtolower ( get_option('awdp_fee_label') ) : 'discount';
        // $styleLabel = get_option('awdp_fee_label') ? str_replace(' ', '-', mb_strtolower ( get_option('awdp_fee_label') ) ) : 'discount'; 
        $bordercolor = get_option('awdp_table_border') ? get_option('awdp_table_border') : ''; 
        $tablefontsize = get_option('awdp_tablefontsize') ? ( get_option('awdp_tablefontsize') != '0' ? get_option('awdp_tablefontsize') : '' ) : ''; ?>

        <style> .wdp_table_outter{padding:10px 0;} .wdp_table_outter h4{margin: 10px 0 15px 0;} table.wdp_table{border-top-style:solid; border-top-width:1px !important; border-top-color:<?php if ( $bordercolor == '' ) echo 'inherit'; else echo $bordercolor; ?>; border-right-style:solid; border-right-width:1px !important; border-right-color:<?php if ( $bordercolor == '' ) echo 'inherit'; else echo $bordercolor; ?>;border-collapse: collapse; margin-bottom:0px; <?php if ( $tablefontsize ) { echo 'font-size:'.$tablefontsize.'px'; } ?> } table.wdp_table td{border-bottom-style:solid; border-bottom-width:1px !important; border-bottom-color:<?php if ( $bordercolor == '' ) echo 'inherit'; else echo $bordercolor; ?>; border-left-style:solid; border-left-width:1px !important; border-left-color:<?php if ( $bordercolor == '' ) echo 'inherit'; else echo $bordercolor; ?>; padding:10px 20px !important;} <?php if( $bordercolor != '' ) { ?> table.wdp_table td, table.wdp_table tr { border: 1px solid <?php echo $bordercolor; ?> } <?php } ?>table.wdp_table.lay_horzntl td{padding:10px 15px !important;} a[data-coupon="<?php echo $couponLabel; ?>"]{ display: none; } .wdp_helpText{ font-size: 12px; top: 5px; position: relative; } @media screen and (max-width: 640px) { table.wdp_table.lay_horzntl { width:100%; } table.wdp_table.lay_horzntl tbody.wdp_table_body { width:100%; display:block; } table.wdp_table.lay_horzntl tbody.wdp_table_body tr { display:inline-block; width:50%; box-sizing:border-box; } table.wdp_table.lay_horzntl tbody.wdp_table_body tr td {display: block; text-align:left;}} </style>

    <?php 
    }

    /**
     * AWDP Register Custom post types
     */
    public function register_awdp_discounts() {

        $post_type = AWDP_POST_TYPE;
        $labels = array(
            'name' => __('Pricing Rules', 'aco-woo-dynamic-pricing'),
            'singular_name' => __('Pricing Rule', 'aco-woo-dynamic-pricing'),
            'name_admin_bar' => 'WCPA_Form',
            'add_new' => _x('Add New Product Form', $post_type, 'aco-woo-dynamic-pricing'),
            'add_new_item' => sprintf(__('Add New %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'edit_item' => sprintf(__('Edit %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'new_item' => sprintf(__('New %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'all_items' => sprintf(__('Product Rules', 'aco-woo-dynamic-pricing'), 'Form'),
            'view_item' => sprintf(__('View %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'search_items' => sprintf(__('Search %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'not_found' => sprintf(__('No %s Found', 'aco-woo-dynamic-pricing'), 'Form'),
            'not_found_in_trash' => sprintf(__('No %s Found In Trash', 'aco-woo-dynamic-pricing'), 'Form'),
            'parent_item_colon' => sprintf(__('Parent %s'), 'Form'),
            'menu_name' => 'Custom Product Options'
        );
        $args = array(
            'labels' => apply_filters($post_type . '_labels', $labels),
            'description' => '',
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => false,
            // 'show_in_menu' => 'edit.php?post_type=product',
            'show_in_nav_menus' => false,
            'query_var' => false,
            'can_export' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'rest_base' => $post_type,
            'hierarchical' => false,
            'show_in_rest' => false,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'supports' => array('title'),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-post',
            'taxonomies' => array('product_cat')
        );
        register_post_type($post_type, apply_filters($post_type . '_register_args', $args, $post_type));

        // Product Lists
        $post_type = AWDP_PRODUCT_LIST;
        $labels = array(
            'name' => __('Product Lists', 'aco-woo-dynamic-pricing'),
            'singular_name' => __('Product List', 'aco-woo-dynamic-pricing'),
            'name_admin_bar' => 'WCPA_Form',
            'add_new' => _x('Add New Product List', $post_type, 'aco-woo-dynamic-pricing'),
            'add_new_item' => sprintf(__('Add New %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'edit_item' => sprintf(__('Edit %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'new_item' => sprintf(__('New %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'all_items' => sprintf(__('Product Lists', 'aco-woo-dynamic-pricing'), 'Form'),
            'view_item' => sprintf(__('View %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'search_items' => sprintf(__('Search %s', 'aco-woo-dynamic-pricing'), 'Form'),
            'not_found' => sprintf(__('No %s Found', 'aco-woo-dynamic-pricing'), 'Form'),
            'not_found_in_trash' => sprintf(__('No %s Found In Trash', 'aco-woo-dynamic-pricing'), 'Form'),
            'parent_item_colon' => sprintf(__('Parent %s'), 'Form'),
            'menu_name' => 'Custom Product Options'
        );
        $args = array(
            'labels' => apply_filters($post_type . '_labels', $labels),
            'description' => '',
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => false,
            // 'show_in_menu' => 'edit.php?post_type=product',
            'show_in_nav_menus' => false,
            'query_var' => false,
            'can_export' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'rest_base' => $post_type,
            'hierarchical' => false,
            'show_in_rest' => false,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'supports' => array('title'),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-post',
            'taxonomies' => array('product_cat')
        );
        register_post_type($post_type, apply_filters($post_type . '_register_args', $args, $post_type));

    }

}
