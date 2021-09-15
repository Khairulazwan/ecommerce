<?php

if (!defined('ABSPATH'))
    exit;

class AWDP_Discount
{

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;
    public $product_lists = false;
    public $awdp_cart_rules = false;
    public $apply_wdp_coupon = false;
    public $pricing_table = [];
    public $productvariations = [];
    public $couponLabel = '';
    public $wdp_discounted_price = [];
    public $wdpCartDicount = [];
    public $wdpCartDiscountValues = [];
    public $awdp_cart_rule_ids = [];
    public $variations = [];
    public $variation_prods = [];
    public $wdpQNitems = [];
    public $actual_price = [];
    private $_active = false;
    private $types = array();
    private $discount_rules = false;
    private $conversion_unit = false;
    private $converted_rate = '';
    private $discounts = array();

    public function __construct()
    {

        $this->types = Array(
            'percent_total_amount' => __('Percentage of cart total amount', 'aco-woo-dynamic-pricing'),
            'percent_product_price' => __('Percentage of product price', 'aco-woo-dynamic-pricing'),
            'fixed_product_price' => __('Fixed price of product price', 'aco-woo-dynamic-pricing'),
            'fixed_cart_amount' => __('Fixed price of cart total amount', 'aco-woo-dynamic-pricing'),
            'cart_quantity' => __('Quantity based discount', 'aco-woo-dynamic-pricing')
        );

    }

    /**
     *
     * Ensures only one instance of AWDP is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main AWDP instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->_active;
    }

    // Cart Price View
    public function cart_discount_items ( $item_price, $cart_item )
    {

        $prod_ID = $cart_item['data']->get_data()['slug'];
        $cart_prod_id = ( $cart_item['variation_id'] == 0 || $cart_item['variation_id'] == '' ) ? $cart_item['product_id'] : $cart_item['variation_id'];
        $QNitems = $this->wdpQNitems;

        if ( $this->converted_rate == '' && $cart_item['data']->get_ID() != '' && array_key_exists ( $prod_ID, $this->wdp_discounted_price ) ) {
            $this->converted_rate = $this->get_con_unit($cart_item['data'], wc_remove_number_precision($this->wdp_discounted_price[$prod_ID]));
        }

        // $converted_rate = $this->converted_rate;
        $converted_rate = 1;

        if ( $this->discount_rules != false && sizeof($this->discount_rules) >= 1 && $this->check_discount($prod_ID) && false == $this->awdp_cart_rules ) {

            $discounted_price = abs(wc_remove_number_precision($this->wdp_discounted_price[$prod_ID]));
            $product_actual_price = isset ( $this->actual_price ) ? ( ( array_key_exists($prod_ID, $this->actual_price) && $this->actual_price[$prod_ID] > 0 ) ? $this->actual_price[$prod_ID] : ( ( $cart_item['data']->get_data()['price'] == 0 && isset($cart_item['data']->price) ) ? $cart_item['data']->price : $cart_item['data']->get_data()['price'] ) ) : ( ( $cart_item['data']->get_data()['price'] == 0 && isset($cart_item['data']->price) ) ? $cart_item['data']->price : $cart_item['data']->get_data()['price'] );

            $quantity = 1; 

            $product = wc_get_product( $cart_prod_id );

            if ($product_actual_price > 0) {
                if (WC()->cart->display_prices_including_tax()) {
                    $product_actual_price = $this->wdp_price_including_tax ( $product, array (
                        'qty' => $quantity,
                        'price' => $product_actual_price,
                    ), $product_actual_price );
                    if( $discounted_price > 0.0000000001 ) {
                        $discounted_price = $this->wdp_price_including_tax ( $product, array (
                            'qty' => $quantity,
                            'price' => $discounted_price,
                        ), $discounted_price );
                    }
                } else {
                    $product_actual_price = $this->wdp_price_excluding_tax ( $product, array (
                        'qty' => $quantity,
                        'price' => $product_actual_price
                    ), $product_actual_price );
                    if( $discounted_price > 0.0000000001 ) {
                        $discounted_price = $this->wdp_price_excluding_tax ( $product, array (
                            'qty' => $quantity,
                            'price' => $discounted_price,
                        ), $discounted_price );
                    }
                }
            } else {
                $product_actual_price = $product_actual_price;
                $discounted_price = $discounted_price;
            }

            if ( ( sizeof($QNitems) > 0 ) && array_search ( $cart_prod_id, array_column ( $QNitems, 'product_id' ) ) !== false ) { 
                
                $qn_index = array_search ( $cart_prod_id, array_column ( $QNitems, 'product_id' ) );
                $qn_price = wc_remove_number_precision ( $QNitems[$qn_index]['discounted_price'] ); 

                if ($qn_price > 0) {
                    if (WC()->cart->display_prices_including_tax()) {
                        // $product_actual_price = $this->wdp_price_including_tax ( $product, array (
                        //     'qty' => $quantity,
                        //     'price' => $product_actual_price,
                        // ), $product_actual_price );
                        $qn_price = $this->wdp_price_including_tax ( $product, array (
                            'qty' => $quantity,
                            'price' => $qn_price,
                        ), $qn_price );
                    } else {
                        // $product_actual_price = $this->wdp_price_excluding_tax ( $product, array (
                        //     'qty' => $quantity,
                        //     'price' => $product_actual_price
                        // ), $product_actual_price );
                        $qn_price = $this->wdp_price_excluding_tax ( $product, array(
                            'qty' => $quantity,
                            'price' => $qn_price
                        ), $qn_price );
                    }
                }

                if( ( $discounted_price > 0.0000000001 ) && ( $discounted_price < $product_actual_price ) ) {
                    $item_price = wc_format_sale_price($discounted_price * $converted_rate, $qn_price * $converted_rate );
                } else {
                    $item_price = wc_format_sale_price($product_actual_price * $converted_rate, $qn_price * $converted_rate );
                }
                
            } else if (($discounted_price > 0.0000000001 && ($discounted_price < $product_actual_price)) || $discounted_price == 0) {

                $item_price = wc_format_sale_price($product_actual_price * $converted_rate, $discounted_price * $converted_rate);

            } else if ($discounted_price > $product_actual_price) {

                $item_price = wc_format_sale_price($product_actual_price * $converted_rate, $discounted_price * $converted_rate);

            }

        }

        return $item_price;

    }

    // Price View HTML
    public function get_product_price_html($item_price, $product)
    {

        if ( $this->discount_rules != false && sizeof($this->discount_rules) >= 1 ) { 

            $prod_ID = $product->get_data()['slug'];

            if ( $this->converted_rate == '' && $product->get_ID() != '' && array_key_exists ( $prod_ID, $this->wdp_discounted_price ) ) {
                $this->converted_rate = $this->get_con_unit($product, wc_remove_number_precision($this->wdp_discounted_price[$prod_ID]));
            }
            // $converted_rate = $this->converted_rate;
            $converted_rate = 1;
            // Variable Product
            if ( $product->is_type( 'variable' ) ) { // Price display for variable product
                $variations = $product->get_available_variations(); 
                if ( $variations ) {
                    if ( array_key_exists ( $prod_ID, $this->variations ) && $this->variations[$prod_ID] ) {
                        $wdp_max_price = $product->get_variation_price( 'max', true );
                        $wdp_min_price = $product->get_variation_price( 'min', true );

                        $wdp_dsc_max_price = max($this->variations[$prod_ID]);
                        $wdp_dsc_min_price = min($this->variations[$prod_ID]);

                        if ( $wdp_min_price == $wdp_dsc_min_price && $wdp_max_price == $wdp_dsc_max_price ) {
                            return $item_price;
                        } else {
                            if ( $wdp_dsc_max_price !== $wdp_dsc_min_price ) {
                                $item_price = '<p class="price"><del><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.wc_format_price_range( $wdp_min_price, $wdp_max_price ).'</del> <ins><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.wc_format_price_range( $wdp_dsc_min_price, $wdp_dsc_max_price ).'</ins></p>';
                            }  else if ( $wdp_dsc_max_price == $wdp_dsc_min_price && $wdp_dsc_min_price < $wdp_min_price ) {
                                $item_price = wc_format_sale_price($wdp_min_price * $converted_rate, $wdp_dsc_min_price * $converted_rate);
                            } else {
                                $item_price = wc_price( $wdp_dsc_min_price );
                            }
                        }
                    }
                    return $item_price;
                }
            }
            // End Check

            if ( $this->discount_rules != false && sizeof($this->discount_rules) >= 1 && $this->check_discount_shop($prod_ID) && false == $this->awdp_cart_rules ) {

                $latest_price = isset ( $this->wdp_discounted_price ) ? ( array_key_exists($prod_ID, $this->wdp_discounted_price) ? abs(wc_remove_number_precision($this->wdp_discounted_price[$prod_ID]) ) : 0 ) : 0;
                $actual_price = isset ( $this->actual_price ) ? ( array_key_exists($prod_ID, $this->actual_price) ?  $this->actual_price[$prod_ID] : $product->get_data()['price'] ) : $product->get_data()['price'];

                $quantity = 1;

                // if ( $actual_price > 0 ) {
                //     if ( get_option('woocommerce_tax_display_shop') == 'incl' ) {
                //         $actual_price = $this->wdp_price_including_tax($product, array(
                //             'qty' => $quantity,
                //             'price' => $actual_price,
                //         ), $actual_price);
                //         if ( $latest_price > 0.0000000001 ) {
                //             $latest_price = $this->wdp_price_including_tax($product, array(
                //                 'qty' => $quantity,
                //                 'price' => $latest_price,
                //             ), $latest_price);
                //         }
                //     } else {
                //         $actual_price = $this->wdp_price_excluding_tax($product, array(
                //             'qty' => $quantity,
                //             'price' => $actual_price
                //         ), $actual_price);
                //         if ( $latest_price > 0.0000000001 ) {
                //             $latest_price = $this->wdp_price_excluding_tax($product, array(
                //                 'qty' => $quantity,
                //                 'price' => $latest_price,
                //             ), $latest_price);
                //         }
                //     }
                // } else {
                //     $actual_price = $actual_price;
                //     $latest_price = $latest_price;
                // }

                if ( $latest_price > 0.0000000001 && ( $latest_price < $actual_price ) ) {

                    $item_price = wc_format_sale_price($actual_price * $converted_rate, $latest_price * $converted_rate);

                } else if ( $latest_price == 0 ) {

                    $item_price = wc_price(0);

                } else if ( $latest_price > $actual_price ) {

                    $item_price = wc_format_sale_price($actual_price * $converted_rate, $latest_price * $converted_rate);

                } else if ( '' === $product->get_price() || 0 == $product->get_price() ) {
                    
                    $item_price = wc_price(0);

                } 
            } else if($product->is_on_sale('edit')) { // Fix: Sale price not displaying when coupon rules are active

                $sale_con_rate = ( $this->converted_rate == '' || $this->converted_rate == null ) ? 1 : $this->converted_rate; // Non numeric value encountered fix

                $actual_sale_price = $product->get_data()['sale_price'];
                $actual_regular_price = $product->get_data()['regular_price'];
                $quantity = 1;

                if ( get_option('woocommerce_tax_display_shop') == 'incl' ) {
                    $actual_sale_price = $this->wdp_price_including_tax($product, array(
                        'qty' => $quantity,
                        'price' => $actual_sale_price,
                    ), $actual_sale_price);
                    $actual_regular_price = $this->wdp_price_including_tax($product, array(
                        'qty' => $quantity,
                        'price' => $actual_regular_price,
                    ), $actual_regular_price);
                } else {
                    $actual_sale_price = $this->wdp_price_excluding_tax($product, array(
                        'qty' => $quantity,
                        'price' => $actual_sale_price
                    ), $actual_sale_price);
                    $actual_regular_price = $this->wdp_price_excluding_tax($product, array(
                        'qty' => $quantity,
                        'price' => $actual_regular_price,
                    ), $actual_regular_price);
                }
                
                if ( isset($actual_sale_price) && $actual_sale_price > 0 && $actual_regular_price > 0 ) { 
                    return wc_format_sale_price( $actual_regular_price * $sale_con_rate, $actual_sale_price * $sale_con_rate );
                }

            }

            return $item_price;

        }

        return $item_price;

    }

    // Currency convertor
    public function get_con_unit( $product, $price = false, $insideloop = false )
    {

        if ( $this->conversion_unit === false && $insideloop === false ) {  

            global $WOOCS; // checking WooCommerce Currency Switcher (WOOCS) is enabled
            $from_currency = get_option('woocommerce_currency');
            $to_currency = get_woocommerce_currency(); 

            if ( $from_currency === $to_currency || $WOOCS !== null ) return 1; 

            $view_price = $product->get_price('view');
            $edit_price = ( $price ) ? $price : $product->get_price('edit');  

            if ( $view_price && $edit_price && $edit_price > 0 && $view_price > 0 ) {
                $this->conversion_unit = $view_price / $edit_price;
            } else {
                $this->conversion_unit = 1;
            } 
            if ( $this->conversion_unit == 1) {
                $this->conversion_unit = apply_filters('wcml_raw_price_amount', 1);
            }

            if ($this->conversion_unit == 1) { // Aelia Currency Switcher
                if(wc_get_price_decimals() == 0 ){
                    $converted_amount = apply_filters('wc_aelia_cs_convert', 1, $from_currency, $to_currency,2);
                }else{
                    $converted_amount = apply_filters('wc_aelia_cs_convert', 1, $from_currency, $to_currency);
                }
                $this->conversion_unit = $converted_amount;
            }

            if ( $this->conversion_unit == 1 && class_exists('WOOMULTI_CURRENCY') ) { // WooCommerce Multi Currency Plugin
                $data = WOOMULTI_CURRENCY_Data::get_ins(); 
                $currency_array = $data->get_list_currencies();
                $rate = (float)$currency_array[$to_currency]['rate']; 
                $this->conversion_unit = $rate;
            }

            if ( $this->conversion_unit == 1 && class_exists('WOOMULTI_CURRENCY_F') ) { // WooCommerce Multi Currency Free Plugin
                $data = WOOMULTI_CURRENCY_F_Data::get_ins(); 
                $currency_array = $data->get_list_currencies();
                $rate = (float)$currency_array[$to_currency]['rate']; 
                $this->conversion_unit = $rate;
            }

            if ($this->conversion_unit == 1 && function_exists('wcpbc_the_zone')) {
                $wcpbc = wcpbc_the_zone();
                $converted_amount = 1;
                if (is_callable($wcpbc, 'get_exchange_rate_price')) {
                    $converted_amount = $wcpbc->get_exchange_rate_price(1);
                }
                $this->conversion_unit = $converted_amount;
            }

            // global $WOOCS;
            // if ($this->conversion_unit == 1 && $WOOCS!==null) {
            //     if (method_exists($WOOCS, 'woocs_exchange_value')) {
            //         $res=$WOOCS->woocs_exchange_value(1);
            //         $this->conversion_unit = $res;
            //     }
            // }

            return $this->conversion_unit;

        } else if ( $this->conversion_unit === false && $insideloop === true ) { // Pricing Table
            
            $from_currency = get_option('woocommerce_currency');
            $to_currency = get_woocommerce_currency(); 

            if ( $from_currency === $to_currency ) return 1;

            $converted_price = $price;
            $unit_price = $product->get_price('edit');

            $this->conversion_unit = $converted_price / $unit_price;

            // if ($this->conversion_unit == 1) { // Aelia Currency Switcher
            //     if(wc_get_price_decimals() == 0 ){
            //         $converted_amount = apply_filters('wc_aelia_cs_convert', 1, $from_currency, $to_currency,2);
            //     }else{
            //         $converted_amount = apply_filters('wc_aelia_cs_convert', 1, $from_currency, $to_currency);
            //     }
            //     $this->conversion_unit = $converted_amount;
            // }

            if ( $this->conversion_unit == 1 && class_exists('WOOMULTI_CURRENCY') ) { // WooCommerce Multi Currency Plugin
                $data = WOOMULTI_CURRENCY_Data::get_ins(); 
                $currency_array = $data->get_list_currencies();
                $rate = (float)$currency_array[$to_currency]['rate']; 
                $this->conversion_unit = $rate;
            }

            if ( $this->conversion_unit == 1 && class_exists('WOOMULTI_CURRENCY_F') ) { // WooCommerce Multi Currency Free Plugin
                $data = WOOMULTI_CURRENCY_F_Data::get_ins(); 
                $currency_array = $data->get_list_currencies();
                $rate = (float)$currency_array[$to_currency]['rate']; 
                $this->conversion_unit = $rate;
            }

            if ($this->conversion_unit == 1 && function_exists('wcpbc_the_zone')) {
                $wcpbc = wcpbc_the_zone();
                $converted_amount = 1;
                if (is_callable($wcpbc, 'get_exchange_rate_price')) {
                    $converted_amount = $wcpbc->get_exchange_rate_price(1);
                }
                $this->conversion_unit = $converted_amount;
            }

            // global $WOOCS;
            // if ($this->conversion_unit == 1 && $WOOCS!==null) {
            //     if (method_exists($WOOCS, 'woocs_exchange_value')) { 
            //         $res=$WOOCS->woocs_exchange_value(1); 
            //         $this->conversion_unit = $res;
            //     }
            // } 

            return $this->conversion_unit;

        } else {

            return $this->conversion_unit;

        }

    }

    // Show Pricing Table
    public function show_pricing_table(){

        $pricing_table = $this->pricing_table;
        $id = get_the_ID();
        if ( is_array($pricing_table) && array_key_exists ( $id, $pricing_table ) ) {
            $pr_tables = $pricing_table[$id];
            foreach ( $pr_tables as $pr_table ) {
                echo $pr_table;
            }
        }

    }

    // Unset Discounts Array
    public function apply_cart_discounts( $cart_object ){

        $rules = $this->awdp_cart_rule_ids;

        if ($this->awdp_cart_rules && $this->validate_discount_rules($cart_object, $rules, ['cart_total_amount', 'cart_total_amount_all_prods', 'cart_items', 'cart_items_all_prods', 'cart_products'])) {
            foreach ($rules as $rule) {
                unset($this->discounts[$rule['id']]);
            }
        }
        // Saving Cart in Session
        WC()->session->__unset( 'WDP_Cart' );
        WC()->session->set( 'WDP_Cart', WC()->cart->get_cart() );

    }
    
    // Discount Check
    public function check_discount($slug)
    {
        $_discounts = array();

        foreach ($this->discounts as $discounts) {
            if ($discounts['discount_type'] == 'percent_product_price' || $discounts['discount_type'] == 'fixed_product_price' || $discounts['discount_type'] == 'cart_quantity') {
                if (array_key_exists('discounts', $discounts)) {
                    if (!array_key_exists('type', $discounts['discounts'])) {
                        foreach ($discounts['discounts'] as $key => $discount) {
                            if (!isset($_discounts[$key])) {
                                $_discounts[$key] = 0.0;
                            }
                            if ($discount != '')
                                $_discounts[$key] += $discount;
                        }
                    }
                }
            }
        }

        if (isset($_discounts[$slug]) && $_discounts[$slug] > 0)
            return true;
        else
            return false;
    }

    public function check_discount_shop($slug)
    {
        $_discounts = array();

        foreach ($this->discounts as $discounts) {
            if ($discounts['discount_type'] == 'percent_product_price' || $discounts['discount_type'] == 'fixed_product_price') {
                if (array_key_exists('discounts', $discounts)) {
                    if (!array_key_exists('type', $discounts['discounts'])) {
                        foreach ($discounts['discounts'] as $key => $discount) {
                            if (!isset($_discounts[$key])) {
                                $_discounts[$key] = 0.0;
                            }
                            if ($discount != '')
                                $_discounts[$key] += $discount;
                        }
                    }
                }
            }
        }

        if (isset($_discounts[$slug]) && $_discounts[$slug] > 0)
            return true;
        else
            return false;
    }

    // Validate Rules
    public function validate_discount_rules($cart_obj, $rule, $rules_to_validate = array(), $item = false, $single = false)
    {

        $list_id = (array_key_exists('product_list', $rule) && $rule['product_list']) ? $rule['product_list'] : '';

        $evel_str = '';
        //  $rules_to_validate = ['cart_total_amount', 'cart_total_amount_all_prods', 'cart_items', 'cart_items_all_prods', 'cart_products'];
        $result = true;// if no rules, the validation must be true

        // Disabling Quantity Rules for Discount Type -> Cart Quantity
        if (array_key_exists('type', $rule) && 'cart_quantity' == $rule['type'] && 'cart_quantity' == $rule['quantity_type']) {
            $qn_flag = true;
        } else {
            $qn_flag = false;
        }

        if ( isset($rule['rules']) && is_array($rule['rules']) && !empty($rule['rules']) ) {

            foreach ( $rule['rules'] as $val ) {

                if ( !empty($val['rules']) && is_array($val['rules']) && count($val['rules']) ) {

                    $evel_str .= '(';
                    $val_rules = array_values ( array_filter( $val['rules'] ) ); // Remove null elements - 3.4.2 fix
                    foreach ($val_rules as $rul) { 
                        $evel_str .= '(';
                        if (in_array($rul['rule']['item'], $rules_to_validate) && $rul['rule']['value'] != '') {
                            if ($this->eval_rule($rul['rule'], $cart_obj, $item, $rule, $list_id, $qn_flag, $single)) { 
                                $evel_str .= ' true ';
                            } else { 
                                $evel_str .= ' false ';
                            }
                        } else {
                            $evel_str .= ' true ';
                        }

                        $evel_str .= ') ' . (($rul['operator'] !== false) ? $rul['operator'] : '') . ' ';
                    }

                    if ( count($val['rules']) > 0 && !empty($val['rules']) ) {
                        preg_match_all('/\(.*\)/', $evel_str, $match);
                        $evel_str = $match[0][0] . ' ';
                    }

                    $evel_str .= ') ' . (($val['operator'] !== false) ? $val['operator'] : '') . ' ';

                }

            }

            if (count($rule['rules']) > 0 && !empty($rule['rules']) && $evel_str != '') {
                preg_match_all('/\(.*\)/', $evel_str, $match);
                $evel_str = $match[0][0] . ' ';
            }

            $evel_str = str_replace(['and', 'or'], ['&&', '||'], strtolower($evel_str));
            
            if ($evel_str !== '') {
                $result = eval('return ' . $evel_str . ';');
            }

        }

        return $result;
    }

    public function eval_rule($rule, $cart_obj, $item = false, $discount_rule, $list_id, $qn_flag, $single = false)
    {

        $product_lists = $this->product_lists;  
        $wdp_cart_totals = $wdp_cart_items = $wdp_cart_quantity = 0;

        // Checking if Product List is Active
        if ( $list_id && $list_id != 'null' && $product_lists[$list_id] && WC()->cart && WC()->cart->get_cart_contents_count() > 0 && ( 'cart_total_amount' == $rule['item'] || 'product_price' == $rule['item'] || 'cart_items' == $rule['item'] || 'cart_products' == $rule['item'] ) ) {

            $applicable_products = $product_lists[$list_id];
            $cart_items = WC()->cart->get_cart();
            if ($cart_items) {
                foreach ($cart_items as $cart_item) {
                    if (in_array($cart_item['product_id'], $applicable_products)) {

                        $product_data = $cart_item['data']->get_data();

                        $wdp_cart_totals = $wdp_cart_totals + $product_data['price'] * $cart_item['quantity'];
                        $wdp_cart_items = $wdp_cart_items + $cart_item['quantity'];
                        $wdp_cart_quantity = $wdp_cart_quantity + 1;

                    }
                }
            }

        } else if ( isset ( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) {

            // Checkout page ajax loading fix 
            $cart_items = is_checkout() ? ( WC()->session->get('WDP_Cart') ? WC()->session->get('WDP_Cart') : WC()->cart->get_cart() ) : WC()->cart->get_cart(); 

            if ($cart_items) {
                foreach ($cart_items as $cart_item) {
                    $product_data = $cart_item['data']->get_data();
                    $wdp_cart_totals = $wdp_cart_totals + $product_data['price'] * $cart_item['quantity'];
                    $wdp_cart_items = $wdp_cart_items + $cart_item['quantity'];
                    $wdp_cart_quantity = $wdp_cart_quantity + 1;
                }
            }

        }

        if ( 'cart_total_amount' == $rule['item'] || 'cart_total_amount_all_prods' == $rule['item'] ) {

            // cart based rule : true
            $this->awdp_cart_rules = true;
            $this->apply_wdp_coupon = true; 
            // $this->awdp_cart_rule_ids[] = $discount_rule;

            // Check if cart is empty
            if ( !isset (WC()->cart) || $wdp_cart_totals == 0 || !did_action('woocommerce_before_calculate_totals') ) 
                return false;

            $item_val = $wdp_cart_totals;
            $rel_val = (float)$rule['value'];

        } else if ( 'product_price' == $rule['item'] ) {

            $this->apply_wdp_coupon = true;

            if ($single)
                $item_val = (float)$item['data']->get_data()['price'];
            else
                $item_val = (float)$item->get_data()['price'];

            $rel_val = (float)$rule['value'];

        } else if ( ( 'cart_items' == $rule['item'] || 'cart_items_all_prods' == $rule['item'] ) && false == $qn_flag ) {

            // cart based rule : true
            $this->awdp_cart_rules = true;
            $this->apply_wdp_coupon = true;
            $this->awdp_cart_rule_ids[] = $discount_rule;

            // Check if cart is empty
            if ( !isset ( WC()->cart ) || $wdp_cart_quantity == 0 || !did_action('woocommerce_before_calculate_totals') ) return false;

            $item_val = $wdp_cart_items;
            $rel_val = (float)$rule['value'];

        } else if ( 'cart_products' == $rule['item'] && false == $qn_flag ) {

            // cart based rule : true
            $this->awdp_cart_rules = true;
            $this->apply_wdp_coupon = true;
            $this->awdp_cart_rule_ids[] = $discount_rule;

            // Check if cart is empty
            if ( !isset ( WC()->cart ) || $wdp_cart_quantity == 0 || !did_action('woocommerce_before_calculate_totals') ) return false;

            $item_val = $wdp_cart_quantity;
            $rel_val = (float)$rule['value'];

        } else {

            return false;

        }

        // if ( $item_val == 0 ) return false; // Divisible by zero error

        switch ($rule['condition']) {
            case 'equal_to':
                if (@abs(($item_val - $rel_val) / $item_val) < 0.00001) {
                    return true;
                }
                break;
            case 'less_than':
                if ($item_val < $rel_val) {
                    return true;
                }
                break;
            case 'less_than_eq':
                if ($item_val < $rel_val || abs(($item_val - $rel_val) / $item_val) < 0.0001) {
                    return true;
                }
                break;
            case 'greater_than': 
                if ($item_val > $rel_val) { 
                    return true;
                }
                break;
            case 'greater_than_eq':
                if ($item_val > $rel_val || abs(($item_val - $rel_val) / $item_val) < 0.0001) {
                    return true;
                }
                break;
        }

        return false;
    }

    // Price
    public function get_product_price($price, $product)
    {
        if ($product) {
            $id = $product->get_id();
            if (isset($this->product_lists[$id])) {
                return $this->product_lists[$id]['price'];
            }
            return $this->calculate_discount($price, $product);
        }
    }

    // Discount Calculation
    public function calculate_discount($price, $product)
    {

        // Resetting discounts before cart totals
        if (did_action('woocommerce_after_cart_table') && !defined('AWDP_CART_LOADED')) {

            define('AWDP_CART_LOADED', true);
            $this->discounts = $this->wdp_discounted_price = [];

        }

        // Load discount rules
        $this->load_rules();

        // Check if discount is active
        if ($this->discount_rules == null)
            return $price; // return product price

        $products_with_discount = [];

        global $woocommerce;
        $awdp_product_id = $product->get_id();
        $awdp_product_slug = $product->get_data()['slug'];
        $this->pricing_table[$awdp_product_id] = [];
        $this->wdp_discounted_price[$awdp_product_slug] = -0.0000000001;
        // $this->awdp_cart_rules = false;
        $this->awdp_cart_rule_ids = [];

        foreach ($this->discount_rules as $k => $rule) {

            // Get Product List
            if ( !$this->get_items_to_apply_discount($product, $rule) ) {
                continue;
            }

            // Check if User if Logged-In
            if ( intval($rule['discount_reg_customers']) === 1 && !is_user_logged_in() ) { 
                continue;
            }

            // Validate Rules
            if( 'cart_quantity' != $rule['type'] ) { // Skipping cart_quantity rule // 
                if ( !$this->validate_discount_rules( $product, $rule, ['cart_total_amount', 'cart_total_amount_all_prods', 'cart_items', 'cart_items_all_prods', 'cart_products'] ) ) {
                    continue;
                }
            }

            // Discounts Default Values
            if ( !isset( $this->discounts[$rule['id']] ) ) { 
                $this->discounts[$rule['id']] = ['label' => $rule['label'], 'discount_type' => $rule['type'], 'discount_remainder' => -1, 'taxable' => false];
            }

            // Actual Price of the Product // added $price - multicurrency / addons conflict - fix
            if ( !isset( $this->actual_price[$product->get_data()['slug']] ) ) {
                $discounted_price = $this->get_individual_discounted_price_in_cents($product, false, true, $price); // sequential
                $pro_actual_price = $this->get_individual_discounted_price_in_cents($product, false, false, $price);

                if ( get_option('woocommerce_calc_taxes') == 'yes' && get_option('woocommerce_prices_include_tax') == 'yes' ) {
                    $discounted_price = $this->get_individual_discounted_price_in_cents($product, true, true, $price); // sequential
                    $pro_actual_price = $this->get_individual_discounted_price_in_cents($product, true, false, $price);
                }

                $product_price = ($rule['sequentially']) ? $discounted_price : $pro_actual_price; // sequential
                $this->actual_price[$product->get_data()['slug']] = wc_remove_number_precision($product_price);
            }

            // if ( class_exists('WC_Aelia_CurrencySwitcher') ) { // Checking if Aelia Currency Plugin is active
            //     $this->actual_price[$product->get_data()['slug']] = $price;
            // }

            // $actual_price -> $price // Fix
            if ( ( defined('WCPA_POST_TYPE') && defined('WCPA_VERSION') ) || function_exists('PPOM') ) { // Checking if Addons Plugin is Active
                $this->actual_price[$product->get_data()['slug']] = $price;
            }

            // Calculating Discount / Apply rules
            if ('percent_product_price' == $rule['type'])
                $this->apply_discount_percent_product_price($rule, $product, $price);
            else if ('fixed_product_price' == $rule['type'])
                $this->apply_discount_fixed_product_price($rule, $product, $price);
            else if ('percent_total_amount' == $rule['type'])
                $this->apply_discount_percent_total_amount($rule, $product, $price);
            else if ('fixed_cart_amount' == $rule['type'])
                $this->apply_discount_fixed_price_total_amount($rule, $product, $price);
            else if ('cart_quantity' == $rule['type'])
                $this->apply_discount_cart_quantity($rule, $product, $price);
                
        }

        $_discounts = array();
        if (false == $this->awdp_cart_rules) {
            foreach ($this->discounts as $discounts) {
                if ($discounts['discount_type'] == 'percent_product_price' || $discounts['discount_type'] == 'fixed_product_price') {
                    if (array_key_exists('discounts', $discounts)) {
                        if (!array_key_exists('type', $discounts['discounts'])) {
                            foreach ($discounts['discounts'] as $key => $discount) {
                                if (!isset($_discounts[$key])) {
                                    $_discounts[$key] = 0.0;
                                }
                                if ($discount != '')
                                    $_discounts[$key] += $discount;
                            }
                        }
                    }
                }
            }
        }

        $itemID = $product->get_ID();

        if ( !class_exists('Wcff') ) { // Cheking if 'WC Fields Factory' is active
            $price = ( array_key_exists ( $product->get_data()['slug'], $this->actual_price ) && $this->actual_price[$product->get_data()['slug']] ) ? $this->actual_price[$product->get_data()['slug']] : $price;
        }

        if (true == $this->awdp_cart_rules || !isset($_discounts[$product->get_data()['slug']])) {
            $latest_price = $price;
        } else if ($price >= wc_remove_number_precision($_discounts[$product->get_data()['slug']])) { 
            if ( defined ( "WDP_QN_SET_".$itemID ) === false ) { 
                $latest_price = $price - wc_remove_number_precision($_discounts[$product->get_data()['slug']]);
                if ( get_option('woocommerce_calc_taxes') == 'yes' && get_option('woocommerce_prices_include_tax') == 'no' && get_option('woocommerce_tax_display_shop') == 'incl' ) { // Get discounted price without tax
                    $latest_price = $this->wdp_price_excluding_tax ( $product, array (
                        'price' => $latest_price,
                        'skipcheck' => true
                    ), $latest_price );
                }
            } else {
                $latest_price = $price;
            }
        } else { 
            $latest_price = 0;
        }

        return $latest_price;

    }

    // Rules
    public function load_rules()
    {

        if ($this->discount_rules === false) {

            // Get wordpress timezone settings
            $gmt_offset = get_option('gmt_offset');
            $timezone_string = get_option('timezone_string');
            if ($timezone_string) {
                $datenow = new DateTime(current_time('mysql'), new DateTimeZone($timezone_string));
            } else {
                $min = 60 * get_option('gmt_offset');
                $sign = $min < 0 ? "-" : "+";
                $absmin = abs($min);
                $tz = sprintf("%s%02d%02d", $sign, $absmin / 60, $absmin % 60);
                $datenow = new DateTime(current_time('mysql'), new DateTimeZone($tz));
            }

            // Converting to UTC+000 (moment isoString timezone)
            $datenow->setTimezone(new DateTimeZone('+000'));
            $datenow = $datenow->format('Y-m-d H:i:s');
            $stop_date = date('Y-m-d H:i:s', strtotime($datenow . ' +1 day'));

            $day = date("l");
            $awdp_discount_args = array(
                'post_type' => AWDP_POST_TYPE,
                'fields' => 'ids',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_key' => 'discount_priority',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'discount_status',
                        'value' => 1,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => 'discount_start_date',
                        'value' => $datenow,
                        'compare' => '<=',
                        'type' => 'DATETIME'
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'discount_type',
                            'value' => 'cart_quantity',
                            'compare' => '='
                        ),
                        array(
                            'key' => 'discount_value',
                            'value' => '',
                            'compare' => '!='
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'discount_end_date',
                            'value' => $datenow,
                            'compare' => '>=',
                            'type' => 'DATETIME'
                        ),
                        array(
                            'key' => 'discount_end_date',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'discount_end_date',
                            'value' => '',
                            'compare' => '=',
                        ),
                    )
                )
            );

            $awdp_discount_rules = get_posts($awdp_discount_args); 
            $discount_rules = $check_rules = array();
            if ( $awdp_discount_rules ) {
                foreach ( $awdp_discount_rules as $awdpID ) {
                    $schedules = unserialize(get_post_meta($awdpID, 'discount_schedules', true));
                    if ( $schedules ) {
                        foreach ( $schedules as $schedule ) {
                            $mn_start_time = date('H:i' , strtotime($schedule['start_date'])); 
                            $mn_end_time = date('H:i' , strtotime($schedule['end_date'])); 
                            $current_time = strtotime(gmdate('H:i'));
                            $awdp_start_date = $schedule['start_date'];
                            $awdp_end_start = $schedule['end_date'] ? $schedule['end_date'] : $stop_date;
                            if ( ( $awdp_start_date <= $datenow ) && ( $awdp_end_start >= $datenow ) && !in_array( $awdpID, $check_rules ) ) {
                                $rule_type = get_post_meta($awdpID, 'discount_type', true);
                                $discount_config = get_post_meta($awdpID, 'discount_config', true);
                                $check_rules[] = $awdpID; // remove repeated entry - single rule
                                $discount_rules[] = array(
                                    'id' => $awdpID,
                                    'priority' => get_post_meta($awdpID, 'discount_priority', true),
                                    'label' => ($discount_config['label'] != '') ? $discount_config['label'] : ( get_option('awdp_fee_label') ? get_option('awdp_fee_label') : get_the_title($awdpID) ),
                                    'discount' => get_post_meta($awdpID, 'discount_value', true),
                                    'inc_tax' => $discount_config['inc_tax'],
                                    'disable_on_sale' => $discount_config['disable_on_sale'],
                                    'discount_reg_customers' => get_post_meta($awdpID, 'discount_reg_customers', true),

                                    'sequentially' => $discount_config['sequentially'],
                                    'product_list' => get_post_meta($awdpID, 'discount_product_list', true),
                                    'rules' => $discount_config['rules'] ? unserialize(base64_decode($discount_config['rules'])) : '',
                                    'type' => $rule_type,
                                    'quantity_rules' => get_post_meta($awdpID, 'discount_quantityranges', true) ? unserialize(get_post_meta($awdpID, 'discount_quantityranges', true)) : '',
                                    'quantity_type' => get_post_meta($awdpID, 'discount_quantity_type', true),
                                    'disc_calc_type' => get_post_meta($awdpID, 'discount_calc_type', true),
                                    'pricing_table' => get_post_meta($awdpID, 'discount_pricing_table', true),
                                    'table_layout' => get_post_meta($awdpID, 'discount_table_layout', true),
                                    'variation_check' => get_post_meta($awdpID, 'discount_variation_check', true)
                                );
                            }
                        }
                    }
                }
            }

            // Moving Cart based rules to least priority
            $cart_rules = [];
            foreach ( $discount_rules as $key => $val ) {
                if ( isset($val) && ( 'cart_quantity' == $val['type'] || 'fixed_cart_amount' == $val['type'] || 'percent_total_amount' == $val['type'] ) ) {
                    $cart_rules[] = $discount_rules[$key];
                    unset($discount_rules[$key]);
                }
            }
            $discount_rules = array_merge($discount_rules, $cart_rules);
            $discount_rules = array_values($discount_rules);

            // Discount rules
            $this->discount_rules = $discount_rules;
        }

    }

    public function get_items_to_apply_discount($product, $rule)
    {

        $items = array();
        global $woocommerce;

        //validate with $rule
        if (!$this->check_in_product_list($product, $rule)) {
            return false;
        }
        if (!$this->validate_discount_rules($product, $rule, ['product_price'], $product)) {
            return false;
        }
        if (isset($rule['disable_on_sale']) && $rule['disable_on_sale'] && $product->is_on_sale('edit')) {
            return false;
        }
        return true;

    }

    public function check_in_product_list($product, $rule)
    {
        if (0 == $rule['product_list']) {
            return true;
        } else {
            $this->set_product_list();
            $pro_id = $product->get_parent_id(); // in case of variation
            if ($pro_id == 0) {
                $pro_id = $product->get_id();
            }
            return isset($this->product_lists[$rule['product_list']]) &&
                in_array($pro_id, $this->product_lists[$rule['product_list']]);
        }
    }

    // Cart Total Discount ///////////////////////////////
    public function set_product_list()
    {

        if (false == $this->product_lists) {

            if (false === ($product_lists = get_transient(AWDP_PRODUCTS_TRANSIENT_KEY))) {
                
                $post_type = AWDP_PRODUCT_LIST;
                global $wpdb;

                $product_lists = array();
                $lists = array_values ( array_diff ( array_filter ( $wpdb->get_col ( $wpdb->prepare ( "
                        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
                        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                        WHERE pm.meta_key = '%s' 
                        AND p.post_status = '%s' 
                        AND p.post_type = '%s'
                        ", 'discount_product_list', 'publish', AWDP_POST_TYPE ) ) ), array("null") ) );

                $post_ids = array_map ( function($value) { return (int)$value; }, $lists );

                foreach ($post_ids as $id) {

                    $list_type = get_post_meta($id, 'list_type', true); 
                    $other_config = get_post_meta($id, 'product_list_config', true) ? get_post_meta($id, 'product_list_config', true) : [];

                    $product_lists[$id] = array();

                    if ( 'dynamic_request' == $list_type ) {

                        $tax_rules = ($other_config['rules']);
                        $tax_rules = ($tax_rules && is_array($tax_rules) && !empty($tax_rules)) ? $tax_rules : false;
                        $excludedProducts = ($other_config['excludedProducts']);
                        $tax_query = [];

                        $args = array(
                            'post_type' => AWDP_WC_PRODUCTS,
                            'fields' => 'ids',
                            'post_status' => 'publish',
                            'posts_per_page' => -1,
                        );

                        if ( $excludedProducts ) {
                            $args['post__not_in'] = $excludedProducts;
                        }

                        if ( false !== $tax_rules ) { 

                            if ( isset($tax_rules[0]['rules']) && is_array($tax_rules[0]['rules']) ) {
                                $selected_tax = array_filter($tax_rules[0]['rules']);
                                if ( ( sizeof ( $selected_tax ) ) > 1 ) {
                                    $tax_query = array(
                                        'relation' => ('or' == strtolower($other_config['taxRelation'])) ? 'OR' : 'AND'
                                    );
                                }
                                foreach ( $selected_tax as $tr ) { 
                                    $taxoperator = ( $tr['rule']['condition'] === 'notin' ) ? 'NOT IN' : 'IN'; 
                                    $tax_query[] = array(
                                        'taxonomy' => $tr['rule']['item'],
                                        'field' => 'term_id',
                                        'terms' => $tr['rule']['value'],
                                        'operator' => $taxoperator
                                    );
                                }
                                $args['tax_query'] = $tax_query;
                            }

                        }

                        $product_lists[$id] = get_posts ( $args );

                    } else {

                        $product_lists[$id] = array_key_exists ( 'selectedProducts', $other_config ) ? ($other_config['selectedProducts']) : [];

                    }

                }

                if ( $product_lists[$id] && class_exists('SitePress') ) { // Get WPML Product ids @@ 3.6.2
                    $wpmlPosts = [];
                    foreach ( $product_lists[$id] as $product_list_id ) { 
                        $transID = apply_filters( 'wpml_object_id', $product_list_id, 'product' );
                        if ( $transID ) {
                            $wpmlPosts[] = $transID;
                        }
                    }
                    $product_lists[$id] = array_values ( array_unique ( array_merge ( $product_lists[$id], $wpmlPosts ) ) );
                }

                set_transient(AWDP_PRODUCTS_TRANSIENT_KEY, $product_lists, 7 * 24 * HOUR_IN_SECONDS);

            }

            $this->product_lists = $product_lists;
            
        }

    }

    public function get_individual_discounted_price_in_cents($item, $include_tax = true, $sequential = false, $price = false)
    {

        // if( $item->is_on_sale ('edit') ) {
        // $price = get_post_meta ( $item->get_id(), '_sale_price', true );
        // } else {
        // $price = get_post_meta ( $item->get_id(), '_regular_price', true );
        // }

        $latest_price = '';
        $excluding_tax = get_option('woocommerce_tax_display_shop');
        $cur_price = $price ? $price : $item->get_data()['price'];
        if ($excluding_tax == 'incl') {
            $price = $this->wdp_price_including_tax ( $item, array(
                'price' => $cur_price,
            ), $cur_price );
        } else {
            $price = $this->wdp_price_excluding_tax ( $item, array(
                'price' => $cur_price,
            ), $cur_price );
        }

        // if($sequential)
        //     return wc_add_number_precision(abs($price - $this->get_discount($item->get_id(), true)));
        // else
        return wc_add_number_precision($price);
    }

    // Quantity based discount /////////////////////////////////

    public function apply_discount_percent_product_price($rule, $item, $price)
    {
        $total_discount = 0;
        $cart_total = 0;
        global $woocommerce;

        $prod_ID = $item->get_data()['slug'];

        // Currency conversion rate
        if( $this->converted_rate == '' && $item->get_ID() != '' ) {
            $this->converted_rate = $this->get_con_unit($item, $price, true);
        }

        // $converted_rate = $this->converted_rate;
        $converted_rate = 1;
        
        // Variable Product
        if ( $item->is_type('variable') && !in_array( $prod_ID, $this->variation_prods ) ) {
            $variations = $item->get_available_variations();
            foreach ( $variations as $variation ) {

                $product_variation_price = ( array_key_exists ( $prod_ID, $this->variations ) && array_key_exists ( $variation['variation_id'], $this->variations[$prod_ID] ) ) ? ( $this->variations[$prod_ID][$variation['variation_id']] ? $this->variations[$prod_ID][$variation['variation_id']] : $variation['display_price'] ) : $variation['display_price'];

                $this->variations[$prod_ID][$variation['variation_id']] = $product_variation_price;
                
            }
            array_push( $this->variation_prods, $prod_ID );
        }

        if (($this->wdp_discounted_price[$prod_ID] != '' && $this->wdp_discounted_price[$prod_ID] > 0) || $this->wdp_discounted_price[$prod_ID] == 0) {
            $product_original_price = $this->wdp_discounted_price[$prod_ID];
        } else if ( $this->actual_price[$prod_ID] > 0 ) {
            $product_original_price = wc_add_number_precision ( $this->actual_price[$prod_ID] );
        } else {
            $product_original_price = wc_add_number_precision ( $price );
        }

        $discount = floor($product_original_price * ($rule['discount'] / 100));
        // $discount = min($discounted_price, $discount);

        if ($product_original_price >= $discount)
            $updated_product_price = $product_original_price - $discount;
        else
            $updated_product_price = 0;


        if ( $item->get_parent_id() !== 0 ) {
            $this->discounts[$rule['id']]['discounts'][$prod_ID] = $discount * $converted_rate;
        } else {
            $this->discounts[$rule['id']]['discounts'][$prod_ID] = $discount;
        }
        // $this->discounts[$rule['id']]['discounts'][$prod_ID] = $discount;
        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];
        $this->wdp_discounted_price[$prod_ID] = $updated_product_price;

    }

    // Product Based Discounts ////////////////////////////////

    public function apply_discount_fixed_product_price($rule, $item, $price)
    {

        $prod_ID = $item->get_data()['slug'];

        // Currency conversion rate
        if( $this->converted_rate == '' && $item->get_ID() != '' ) {
            $this->converted_rate = $this->get_con_unit($item, $price, true);
        }

        // $converted_rate = $this->converted_rate;
        $converted_rate = 1;

        // Variable Product
        if ( $item->is_type('variable') && !in_array( $prod_ID, $this->variation_prods ) ) {
            $variations = $item->get_available_variations();
            foreach ( $variations as $variation ) {

                $product_variation_price = ( array_key_exists ( $prod_ID, $this->variations ) && array_key_exists ( $variation['variation_id'], $this->variations[$prod_ID] ) ) ? ( $this->variations[$prod_ID][$variation['variation_id']] ? $this->variations[$prod_ID][$variation['variation_id']] : $variation['display_price'] ) : $variation['display_price'];

                $this->variations[$prod_ID][$variation['variation_id']] = $product_variation_price;
                
            }
            array_push( $this->variation_prods, $prod_ID );
            
        }

        if (($this->wdp_discounted_price[$prod_ID] != '' && $this->wdp_discounted_price[$prod_ID] > 0) || $this->wdp_discounted_price[$prod_ID] == 0) {
            $product_original_price = $this->wdp_discounted_price[$prod_ID];
        } else if ( $this->actual_price[$prod_ID] > 0 ) {
            $product_original_price = wc_add_number_precision ( $this->actual_price[$prod_ID] );
        } else {
            $product_original_price = wc_add_number_precision ( $price );
        }

        $discount_amount = wc_add_number_precision($rule['discount']);

        if ($product_original_price >= $discount_amount) {
            $updated_product_price = $product_original_price - $discount_amount;
            $single_discount_amount = $discount_amount;
        } else {
            $updated_product_price = 0;
            $single_discount_amount = $product_original_price;
        }

        if ( $item->get_parent_id() !== 0 ) {
            $this->discounts[$rule['id']]['discounts'][$prod_ID] = $single_discount_amount * $converted_rate;
        } else {
            $this->discounts[$rule['id']]['discounts'][$prod_ID] = $single_discount_amount;
        }

        // $this->discounts[$rule['id']]['discounts'][$prod_ID] = $single_discount_amount;
        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];
        $this->wdp_discounted_price[$prod_ID] = $updated_product_price;

    }

    public function apply_discount_percent_total_amount($rule, $item, $price)
    {
        $total_discount = 0;
        $cart_total = 0;

        $prod_ID = $item->get_data()['slug'];
        $this->apply_wdp_coupon = true;

        if (($this->wdp_discounted_price[$prod_ID] != '' && $this->wdp_discounted_price[$prod_ID] > 0) || $this->wdp_discounted_price[$prod_ID] == 0) {
            $price_to_discount = $this->wdp_discounted_price[$prod_ID];
        } else if ( $this->actual_price[$prod_ID] > 0 ) {
            $price_to_discount = wc_add_number_precision ( $this->actual_price[$prod_ID] );
        } else {
            $price_to_discount = wc_add_number_precision ( $price );
        }

        $discount = $price_to_discount * ((int)$rule['discount'] / 100);
        $discount = min($price_to_discount, $discount);

        // Store code and discount amount per item.
        $cart_total = $cart_total + $price_to_discount;
        $total_discount = $total_discount + $discount;

        // $prod_ID = $item->get_data()['slug'];
        // if(!$this->discounts[$rule['id']]['discount_applied'][$prod_ID]) {
        $this->discounts[$rule['id']]['discounts'][$prod_ID] = $discount;

        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

        // $this->discounts[$rule['id']]['discount_applied'][$prod_ID] = true;
        // }

    }

    /////////////////////////////

    public function apply_discount_fixed_price_total_amount($rule, $item, $price)
    {
        $total_discount = 0;
        $cart_total = 0;

        $prod_ID = $item->get_data()['slug'];
        $this->apply_wdp_coupon = true;

        if (($this->wdp_discounted_price[$prod_ID] != '' && $this->wdp_discounted_price[$prod_ID] > 0) || $this->wdp_discounted_price[$prod_ID] == 0) {
            $price_to_discount = $this->wdp_discounted_price[$prod_ID];
        } else if ( $this->actual_price[$prod_ID] > 0 ) {
            $price_to_discount = wc_add_number_precision ( $this->actual_price[$prod_ID] );
        } else {
            $price_to_discount = wc_add_number_precision ( $price );
        }

        $discount = wc_add_number_precision($rule['discount']);
        if ($this->discounts[$rule['id']]['discount_remainder'] >= 0) {
            $discount = $this->discounts[$rule['id']]['discount_remainder'];
        }

        if ( !isset($this->discounts[$rule['id']]['discounts'][$prod_ID]) && $price_to_discount > 0 ) {

            if (intval($price_to_discount) >= $discount && $discount >= 0) {
                $discounted_price = $price_to_discount - $discount;
                $product_discount = $discount;
                $this->discounts[$rule['id']]['discount_remainder'] = 0;
            } else if (intval($price_to_discount) < $discount && $discount >= 0) {
                $discounted_price = $price_to_discount;
                $product_discount = $discounted_price;
                $this->discounts[$rule['id']]['discount_remainder'] = $discount - $price_to_discount;
            } else {
                $product_discount = 0;
            }
            $this->discounts[$rule['id']]['discounts'][$prod_ID] = $product_discount;

            $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

        }

    }

    public function apply_discount_cart_quantity($rule, $item, $price)
    {

        $quantity_rules = $rule['quantity_rules'];
        $quantity_type = $rule['quantity_type'];
        $table_layout = $rule['table_layout'];
        $disc_calc_type = $rule['disc_calc_type'];
        $prod_ID = $item->get_data()['slug'];
        $wdp_item_ID = $item->get_ID();
        $discount = $table = $tr_qn = $tr_pr = '';
        $cart_total = $updated_product_price = 0;
        $value_display = get_option('awdp_table_value') ? get_option('awdp_table_value') : '';
        $value_display_text = get_option('awdp_table_value_text');
        $value_display_text_hide = get_option('awdp_table_value_notext') ? get_option('awdp_table_value_notext') : 0;
        $discount_description = get_option('awdp_discount_description') ? get_option('awdp_discount_description') : '';
        $discount_item_description = get_option('awdp_discount_item_description') ? get_option('awdp_discount_item_description') : '';
        $variation_check = $rule['variation_check'];
        $act_qnty = 0;
        $discount_pt = 0;

        $parent_id = $item->get_parent_id();
        // $item = ( $parent_id == 0 ) ? $item : wc_get_product( $parent_id );

        $wdp_cart_totals = 0;
        $wdp_cart_items = 0;
        $wdp_cart_quantity = 0;
        $wdp_applicable_ids = [];

        $this->apply_wdp_coupon = true;

        // Pricing table texts
        $prcn_text = ( !$value_display_text_hide ) ? ( ( ( $value_display == 'discount_value' || $value_display == 'discount_both' ) && $value_display_text ) ? ' '.$value_display_text : __('% OFF', 'aco-woo-dynamic-pricing') ) : '';
        $fxd_text = ( !$value_display_text_hide ) ? ( ( ( $value_display == 'discount_value' || $value_display == 'discount_both' ) && $value_display_text ) ? ' '.$value_display_text : __(' OFF on cart value', 'aco-woo-dynamic-pricing') ) : '';
        $fxd_text_two = ( !$value_display_text_hide ) ? ( ( ( $value_display == 'discount_value' || $value_display == 'discount_both' ) && $value_display_text ) ? ' '.$value_display_text : __(' OFF', 'aco-woo-dynamic-pricing') ) : '';
        $cart_text = ( !$value_display_text_hide ) ? ( ( ( $value_display == 'discount_value' || $value_display == 'discount_both' ) && $value_display_text ) ? ' '.$value_display_text : __(' will be deducted from cart', 'aco-woo-dynamic-pricing') ) : '';
        // End text

        $product_lists = $this->product_lists;
        $list_id = (array_key_exists('product_list', $rule) && $rule['product_list']) ? $rule['product_list'] : '';

        // Checking if Product List is Active / Cart rules Validation
        if ( $list_id && $list_id != 'null' && $product_lists[$list_id] && isset ( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) {
            $applicable_products = $product_lists[$list_id];
            $cart_items = WC()->cart->get_cart();
            if ($cart_items) {
                foreach ($cart_items as $cart_item) {
                    if (in_array($cart_item['product_id'], $applicable_products)) {
                        if ($this->validate_discount_rules($cart_item, $rule, ['cart_total_amount','cart_total_amount_all_prods', 'product_price'], $cart_item, true)) {
                            $product_data = $cart_item['data']->get_data();

                            $wdp_cart_totals = $wdp_cart_totals + $product_data['price'] * $cart_item['quantity'];
                            $wdp_cart_items = $wdp_cart_items + $cart_item['quantity'];
                            $wdp_cart_quantity = $wdp_cart_quantity + 1;

                            $wdp_applicable_ids[] = $cart_item['data']->get_slug();
                        }
                    }
                }
            }
        } else if ( isset ( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) { // Cart rules Validation
            $cart_items = WC()->cart->get_cart();
            if ($cart_items) {
                foreach ($cart_items as $cart_item) {
                    if ($this->validate_discount_rules($cart_item, $rule, ['cart_total_amount', 'cart_total_amount_all_prods', 'product_price'], $cart_item, true)) {
                        $product_data = $cart_item['data']->get_data();

                        $wdp_cart_totals = $wdp_cart_totals + $product_data['price'] * $cart_item['quantity'];
                        $wdp_cart_items = $wdp_cart_items + $cart_item['quantity'];
                        $wdp_cart_quantity = $wdp_cart_quantity + 1;

                        $wdp_applicable_ids[] = $cart_item['data']->get_slug();
                    }
                }
            }
        } 

        $wdp_applicable_ids = array_values ( array_unique ( $wdp_applicable_ids ) );

        // Get product price
        if(( $this->wdp_discounted_price[$prod_ID] != '' && $this->wdp_discounted_price[$prod_ID] > 0 ) || $this->wdp_discounted_price[$prod_ID] == 0) { 
            $price_to_discount = $this->wdp_discounted_price[$prod_ID];
            // Pricing Table View
            if ( get_option ('woocommerce_calc_taxes') == 'yes' && get_option ('woocommerce_tax_display_shop') == 'incl' ) {
                $pricing_table_price = $this->wdp_price_including_tax ( $item, array ( 'qty' => 1, 'price' => $this->wdp_discounted_price[$prod_ID] ), $this->wdp_discounted_price[$prod_ID] );
            } else {
                $pricing_table_price = $this->wdp_discounted_price[$prod_ID];
            }
        } else { 
            // $price_to_discount = wc_add_number_precision($this->actual_price[$prod_ID]);
            if ( $this->actual_price[$prod_ID] > 0 ) {
                $price_to_discount = wc_add_number_precision ( $this->actual_price[$prod_ID] );
            } else {
                $price_to_discount = wc_add_number_precision ( $price );
            }
            // Pricing Table View
            if ( get_option('woocommerce_calc_taxes') == 'yes' && get_option('woocommerce_tax_display_shop') == 'incl' ) {
                $pricing_table_price = wc_add_number_precision ( $this->wdp_price_including_tax ( $item, array( 'price' => $item->get_data()['price'] ), $item->get_data()['price'] ) );
            } else {
                if ( $this->actual_price[$prod_ID] > 0 ) {
                    $pricing_table_price = wc_add_number_precision ( $this->actual_price[$prod_ID] );
                } else {
                    $pricing_table_price = wc_add_number_precision ( $price );
                }
                // $pricing_table_price = wc_add_number_precision($this->actual_price[$prod_ID]);
            }
        }

        // Sort
        array_multisort(array_column($quantity_rules, "start_range"), SORT_ASC, $quantity_rules);
        $last_key = end($quantity_rules);
        $max_range = $last_key["start_range"];

        // Variable Product
        if ($item->is_type('variable')) {
            $variation_prices = [];
            if ( array_key_exists($prod_ID, $this->variations ) ) {
                $variation_ids = $this->variations[$prod_ID];
                foreach ($variation_ids as $variation_id) {
                    array_push($variation_prices, wc_add_number_precision($variation_id));
                }
            } else {
                $variations = $item->get_available_variations();
                foreach($variations as $variation) {
                    array_push($variation_prices, wc_add_number_precision($variation['display_price']));
                }
            }
        }

        // Labels
        $awdp_pc_title = get_option('awdp_pc_title') ? get_option('awdp_pc_title') : __("Quantity Discounts", "aco-woo-dynamic-pricing");
        $awdp_qn_label = get_option('awdp_qn_label') ? get_option('awdp_qn_label') : __("Quantity", "aco-woo-dynamic-pricing");
        $awdp_pc_label = get_option('awdp_pc_label') ? get_option('awdp_pc_label') : __("Price", "aco-woo-dynamic-pricing");
        $awdp_nw_label = get_option('awdp_new_label') ? get_option('awdp_new_label') : __("Price", "aco-woo-dynamic-pricing"); 

        if ( $quantity_type == 'type_cart' ) { // Quantity Discount

            if ( $table_layout == 'horizontal' ) {
                $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table lay_horzntl"><tbody class="wdp_table_body">';
                $tr_qn = '<tr><td>' . $awdp_qn_label . '*' . '</td>';
                $tr_pr = '<tr><td>' . $awdp_pc_label . '</td>';
                if ( $value_display == 'discount_both' ) {
                    $tr_nw = '<tr><td>' . $awdp_nw_label . '</td>';
                }
            } else {
                if ( $value_display == 'discount_both' ) {
                    $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table"><thead><tr class="wdp_table_head"><td>' . $awdp_qn_label . '*' . '</td><td>' . $awdp_pc_label . '</td><td>' . $awdp_nw_label . '</td></tr></thead><tbody class="wdp_table_body">';
                } else {
                    $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table"><thead><tr class="wdp_table_head"><td>' . $awdp_qn_label . '*' . '</td><td>' . $awdp_pc_label . '</td></tr></thead><tbody class="wdp_table_body">';
                }
            }

            foreach ( $quantity_rules as $quantity_rule ) {

                $discount_val = $quantity_rule['dis_value'];
                $discount_typ = $quantity_rule['dis_type'];
                $discounted_new_price = '';

                // Currency conversion rate
                if( $this->converted_rate == '' && $item->get_ID() != '' ) {
                    $this->converted_rate = $this->get_con_unit($item, $price, true);
                }

                // $converted_rate = $this->converted_rate;
                $converted_rate = 1; // Removing coupon amount - get price changed to $price - calculate total

                // Pricing Table
                if ( $discount_typ == 'percentage' ) {
                    $discount_pt = $pricing_table_price * ((float)$discount_val / 100);
                    $discount_pt = min($pricing_table_price, $discount_pt);
                    // Updated Price
                    $discounted_new_price = (($pricing_table_price - $discount_pt) > 0) ? wc_price ( wc_remove_number_precision ( $pricing_table_price - $discount_pt ) * $converted_rate ) : 0;
                } else if ( $discount_typ == 'fixed' ) {
                    $discount_pt = wc_add_number_precision($discount_val * $converted_rate);
                    // Discount
                    $discounted_new_price = wc_price($discount_val) . $cart_text;
                }

                // $discounted_new_price = (($pricing_table_price - $discount_pt) > 0) ? wc_price ( wc_remove_number_precision ( $pricing_table_price - $discount_pt ) * $converted_rate ) : 0;

                $discounted_new_price_bt = $discounted_new_price;

                if ( $value_display == 'discount_value' || $value_display == 'discount_both' ) {
                    if ($discount_typ == 'percentage') {
                        $discounted_new_price = (float)$discount_val . $prcn_text;
                    } else if ($discount_typ == 'fixed') {
                        $discounted_new_price = wc_price($discount_val) . $fxd_text;
                    }
                }

                // Pricing Table Calculations
                if ( $item->is_type('variable') ) { 

                    // Variation Pricing Table
                    $price_to_discount_max = $variation_prices ? max($variation_prices) : 0;
                    $price_to_discount_min = $variation_prices ? min($variation_prices) : 0;

                    if ( $discount_typ == 'percentage' ) {
                        $discount_max_value = $price_to_discount_max * ((float)$discount_val / 100);
                        $discount_min_value = $price_to_discount_min * ((float)$discount_val / 100);
                        // $discount_max_value = min($price_to_discount, $discount_pt);
                    } else if ($discount_typ == 'fixed') {
                        $discount_max_value = wc_add_number_precision($discount_val);
                        $discount_min_value = wc_add_number_precision($discount_val);
                    }

                    $discounted_new_max_price = (($price_to_discount_max - $discount_max_value) > 0) ? wc_price ( wc_remove_number_precision ( $price_to_discount_max - $discount_max_value ) ) : 0;
                    $discounted_new_min_price = (($price_to_discount_min - $discount_min_value) > 0) ? wc_price ( wc_remove_number_precision ( $price_to_discount_min - $discount_min_value ) ) : 0;

                    if ( $table_layout == 'horizontal' ) {
                        if ( $quantity_rule['start_range'] == $quantity_rule['end_range'] ) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . '</td>';
                        } else if ($quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                        } else {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                        }
                        if ( $value_display == 'discount_value' ) {
                            if ($discount_typ == 'percentage') {
                                $tr_pr .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                            } else if ($discount_typ == 'fixed') {
                                $tr_pr .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                            }
                        } else if ( $value_display == 'discount_both' ) { // Display Both Price and Value
                            if ($discount_typ == 'percentage') {
                                $tr_pr .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                            } else if ($discount_typ == 'fixed') {
                                $tr_pr .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                            }
                            if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                $tr_nw .= '<td>' . $discounted_new_min_price . '</td>';
                            } else {
                                $tr_nw .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                            }
                        } else {
                            if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                $tr_pr .= '<td>' . $discounted_new_min_price . '</td>';
                            } else {
                                $tr_pr .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                            }
                        }
                    } else {
                        if ( $quantity_rule['start_range'] == $quantity_rule['end_range'] ) {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . '</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ( $discount_typ == 'percentage' ) {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                                }
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        } else if ( $quantity_rule['end_range'] ) {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ( $discount_typ == 'percentage' ) {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                                }
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        } else {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text . '</td>';
                                }
                                if ($discounted_new_min_price == $discounted_new_max_price) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ($discounted_new_min_price == $discounted_new_max_price) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        }
                    }

                } else {

                    if ( $table_layout == 'horizontal' ) {
                        if ($quantity_rule['start_range'] == $quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . '</td>';
                        } else if ($quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                        } else {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                        }
                        $tr_pr .= '<td>' . $discounted_new_price . '</td>';
                        if ( $value_display == 'discount_both' ) {
                            $tr_nw .= '<td>' . $discounted_new_price_bt . '</td>';
                        }
                    } else {
                        if ($quantity_rule['start_range'] == $quantity_rule['end_range']) {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . '</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . '</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        } else if ($quantity_rule['end_range']) {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        } else {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' +</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' +</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        }
                    }
                }
                // End PT

                // Cart Calculations
                if ( isset ( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) { 
                
                    if ( $wdp_cart_quantity >= (int)$quantity_rule['start_range'] && $wdp_cart_quantity <= (int)$quantity_rule['end_range'] ) {

                        $discount_amt = 0;
                        if ($discount_typ == 'percentage') {
                            $discount_amt = $wdp_cart_totals * ($discount_val / 100);
                            $this->discounts[$rule['id']]['discounts']['type'] = 'percentage';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else if ($discount_typ == 'fixed') {
                            $discount_amt = $discount_val;
                            $this->discounts[$rule['id']]['discounts']['type'] = 'fixed';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else {
                            $discount_amt = 0;
                            $this->discounts[$rule['id']]['discounts']['type'] = '';
                            $this->discounts[$rule['id']]['discounts']['value'] = 0;
                        }

                        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

                        $disc_act_val = (float)$discount_val;
                        $disc_act_type = $discount_typ;
                        $cart_total = $wdp_cart_totals - $discount_amt;
                        $cart_fee = $discount_amt;

                    } else if ($wdp_cart_quantity >= $max_range) {

                        $discount_amt = 0;
                        if ($discount_typ == 'percentage') {
                            $discount_amt = $wdp_cart_totals * ($discount_val / 100);
                            $this->discounts[$rule['id']]['discounts']['type'] = 'percentage';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else if ($discount_typ == 'fixed') {
                            $discount_amt = $discount_val;
                            $this->discounts[$rule['id']]['discounts']['type'] = 'fixed';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else {
                            $discount_amt = 0;
                            $this->discounts[$rule['id']]['discounts']['type'] = '';
                            $this->discounts[$rule['id']]['discounts']['value'] = 0;
                        }

                        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

                        $disc_act_val = (float)$discount_val;
                        $disc_act_type = $discount_typ;
                        $cart_total = $wdp_cart_totals - $discount_amt;
                        $cart_fee = $discount_amt;

                    }
                } // End Cart Calculations
            } // End Foreach

            if ($table_layout == 'horizontal') {
                $tr_qn .= '</tr>';
                $tr_pr .= '</tr>';
                if ( $value_display == 'discount_both' ) {
                    $tr_nw .= '</tr>';
                    $table .= $tr_qn . $tr_pr . $tr_nw . '</tbody></table>';
                } else {
                    $table .= $tr_qn . $tr_pr . '</tbody></table>';
                }
            } else {
                $table .= '</tbody></table>';
            }

            $table .= $discount_description ? '<p class="wdp_helpText">*'.$discount_description.'</p>' : '<p class="wdp_helpText">*'.$awdp_qn_label.' refers to discounted items (products with discount) individual count on cart.</p></div>';

            // Pricinig Table
            if ($rule['pricing_table'] == 1 && $pricing_table_price > 0) $this->pricing_table[$item->get_id()][$rule['id']] = $table;

            // Discount
            if ($cart_total > 0) {
                $this->wdpCartDicount[$rule['id']] = (float)$discount_amt;
                $this->wdpCartDiscountValues[$rule['id']]['type'] = $disc_act_type; 
                $this->wdpCartDiscountValues[$rule['id']]['value'] = $disc_act_val; 
                $this->wdpCartDiscountValues[$rule['id']]['products'] = $wdp_applicable_ids; 
            }
            // End Cart Quantity

        } else if ( $quantity_type == 'type_item' ) { 
            
            // Total Quantity ////////////////////////////////////////////////////////////////////////
            if ($table_layout == 'horizontal') {
                $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table lay_horzntl"><tbody class="wdp_table_body">';
                $tr_qn = '<tr><td>' . $awdp_qn_label . '</td>';
                $tr_pr = '<tr><td>' . $awdp_pc_label . '</td>';
                if ( $value_display == 'discount_both' ) {
                    $tr_nw = '<tr><td>' . $awdp_nw_label . '</td>';
                }
            } else {
                if ( $value_display == 'discount_both' ) {
                    $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table"><thead><tr class="wdp_table_head"><td>' . $awdp_qn_label . '*' . '</td><td>' . $awdp_pc_label . '</td><td>' . $awdp_nw_label . '</td></tr></thead><tbody class="wdp_table_body">';
                } else {
                    $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table"><thead><tr class="wdp_table_head"><td>' . $awdp_qn_label . '</td><td>' . $awdp_pc_label . '</td></tr></thead><tbody class="wdp_table_body">';
                }
            }

            foreach ($quantity_rules as $quantity_rule) {

                $discount_val = $quantity_rule['dis_value'];
                $discount_typ = $quantity_rule['dis_type'];

                // Currency conversion rate
                if( $this->converted_rate == '' && $item->get_ID() != '' ) {
                    $this->converted_rate = $this->get_con_unit($item, $price, true);
                }

                // $converted_rate = $this->converted_rate;
                $converted_rate = 1; // Removing coupon amount - get price changed to $price - calculate total

                // Pricing Table
                if ($discount_typ == 'percentage') {
                    $discount_pt = $pricing_table_price * ((float)$discount_val / 100);
                    $discount_pt = min($pricing_table_price, $discount_pt);
                    // Updated Price
                    $discounted_new_price = (($pricing_table_price - $discount_pt) > 0) ? wc_price ( wc_remove_number_precision ( $pricing_table_price - $discount_pt ) * $converted_rate ) : 0;
                } else if ($discount_typ == 'fixed') {
                    $discount_pt = wc_add_number_precision($discount_val * $converted_rate);
                    // Discount
                    $discounted_new_price = wc_price($discount_val) . $cart_text;
                }

                $discounted_new_price_bt = $discounted_new_price;

                // $discounted_new_price = (($pricing_table_price - $discount_pt) > 0) ? wc_price ( wc_remove_number_precision ( $pricing_table_price - $discount_pt ) * $converted_rate ) : 0;

                if ( $value_display == 'discount_value' || $value_display == 'discount_both' ) {
                    if ($discount_typ == 'percentage') {
                        $discounted_new_price = (float)$discount_val . $prcn_text;
                    } else if ($discount_typ == 'fixed') {
                        $discounted_new_price = wc_price($discount_val) . $fxd_text_two;
                    }
                }

                // Pricing Table Calculations
                if ($item->is_type('variable')) { 

                    // Variation Pricing Table
                    $price_to_discount_max = $variation_prices ? max($variation_prices) : 0;
                    $price_to_discount_min = $variation_prices ? min($variation_prices) : 0;

                    if ($discount_typ == 'percentage') {
                        $discount_max_value = $price_to_discount_max * ((float)$discount_val / 100);
                        $discount_min_value = $price_to_discount_min * ((float)$discount_val / 100);
                        // $discount_max_value = min($price_to_discount, $discount_pt);
                    } else if ($discount_typ == 'fixed') {
                        $discount_max_value = wc_add_number_precision($discount_val);
                        $discount_min_value = wc_add_number_precision($discount_val);
                    }

                    $discounted_new_max_price = (($price_to_discount_max - $discount_max_value) > 0) ? wc_price ( wc_remove_number_precision ( $price_to_discount_max - $discount_max_value ) ) : 0;
                    $discounted_new_min_price = (($price_to_discount_min - $discount_min_value) > 0) ? wc_price ( wc_remove_number_precision ( $price_to_discount_min - $discount_min_value ) ) : 0;

                    if ($table_layout == 'horizontal') {

                        if ( $quantity_rule['start_range'] == $quantity_rule['end_range'] ) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . '</td>';
                        } else if ($quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                        } else {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                        }
                        if ( $value_display == 'discount_value' ) {
                            if ($discount_typ == 'percentage') {
                                $tr_pr .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                            } else if ($discount_typ == 'fixed') {
                                $tr_pr .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                            }
                        } else if ( $value_display == 'discount_both' ) { // Display Both Price and Value
                            if ($discount_typ == 'percentage') {
                                $tr_pr .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                            } else if ($discount_typ == 'fixed') {
                                $tr_pr .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                            }
                            if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                $tr_nw .= '<td>' . $discounted_new_min_price . '</td>';
                            } else {
                                $tr_nw .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                            }
                        } else {
                            if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                $tr_pr .= '<td>' . $discounted_new_min_price . '</td>';
                            } else {
                                $tr_pr .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                            }
                        }

                    } else {

                        if ( $quantity_rule['start_range'] == $quantity_rule['end_range'] ) {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . '</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ( $discount_typ == 'percentage' ) {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        } else if ( $quantity_rule['end_range'] ) {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ( $discount_typ == 'percentage' ) {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        } else {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                                if ($discounted_new_min_price == $discounted_new_max_price) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ($discounted_new_min_price == $discounted_new_max_price) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        }

                    }

                } else {

                    if ( $table_layout == 'horizontal' ) {
                        if ($quantity_rule['start_range'] == $quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . '</td>';
                        } else if ($quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                        } else {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                        }
                        $tr_pr .= '<td>' . $discounted_new_price . '</td>';
                        if ( $value_display == 'discount_both' ) {
                            $tr_nw .= '<td>' . $discounted_new_price_bt . '</td>';
                        }
                    } else {
                        if ($quantity_rule['start_range'] == $quantity_rule['end_range']) {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . '</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . '</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        } else if ($quantity_rule['end_range']) {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        } else {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' +</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' +</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        }
                    }

                }
                // End PT

                // Cart Calculations
                if ( isset ( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) { 
                
                    if ( $wdp_cart_items >= (int)$quantity_rule['start_range'] && $wdp_cart_items <= (int)$quantity_rule['end_range'] ) {

                        $discount_amt = 0;
                        if ($discount_typ == 'percentage') {
                            $discount_amt = $wdp_cart_totals * ($discount_val / 100);
                            $this->discounts[$rule['id']]['discounts']['type'] = 'percentage';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        // } else if ($discount_typ == 'fixed' && $disc_calc_type == 'type_product') {
                        //     $discount_amt = $discount_val * $wdp_cart_quantity;
                        //     $this->discounts[$rule['id']]['discounts']['type'] = 'fixed';
                        //     $this->discounts[$rule['id']]['discounts']['value'] = $discount_val * $wdp_cart_quantity;

                        } else if ($discount_typ == 'fixed') {
                            $discount_amt = $discount_val;
                            $this->discounts[$rule['id']]['discounts']['type'] = 'fixed';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else {
                            $discount_amt = 0;
                            $this->discounts[$rule['id']]['discounts']['type'] = '';
                            $this->discounts[$rule['id']]['discounts']['value'] = 0;
                        }

                        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

                        $disc_act_val = (float)$discount_val;
                        $disc_act_type = $discount_typ;
                        $cart_total = $wdp_cart_totals - $discount_amt;
                        $cart_fee = $discount_amt;

                    } else if ($wdp_cart_items >= $max_range) {

                        $discount_amt = 0;
                        if ($discount_typ == 'percentage') {
                            $discount_amt = $wdp_cart_totals * ($discount_val / 100);
                            $this->discounts[$rule['id']]['discounts']['type'] = 'percentage';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else if ($discount_typ == 'fixed') {
                            $discount_amt = $discount_val;
                            $this->discounts[$rule['id']]['discounts']['type'] = 'fixed';
                            $this->discounts[$rule['id']]['discounts']['value'] = $discount_val;
                        } else {
                            $discount_amt = 0;
                            $this->discounts[$rule['id']]['discounts']['type'] = '';
                            $this->discounts[$rule['id']]['discounts']['value'] = 0;
                        }

                        $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

                        $disc_act_val = (float)$discount_val;
                        $disc_act_type = $discount_typ;
                        $cart_total = $wdp_cart_totals - $discount_amt;
                        $cart_fee = $discount_amt;

                    }

                } // End Cart Calculations

            } // End Foreach

            if ($table_layout == 'horizontal') {
                $tr_qn .= '</tr>';
                $tr_pr .= '</tr>';
                if ( $value_display == 'discount_both' ) { 
                    $tr_nw .= '</tr>';
                    $table .= $tr_qn . $tr_pr . $tr_nw . '</tbody></table></div>';
                } else {
                    $table .= $tr_qn . $tr_pr . '</tbody></table></div>';
                }
            } else {
                $table .= '</tbody></table></div>';
            }

            $table .= $discount_item_description ? '<p class="wdp_helpText">*'.$discount_item_description.'</p>' : '<p class="wdp_helpText">*'.$awdp_qn_label.' refers to discounted items (products with discount) total quantity on cart.</p></div>';

            // Pricinig Table
            if ($rule['pricing_table'] == 1 && $pricing_table_price > 0) $this->pricing_table[$item->get_id()][$rule['id']] = $table;

            // Discount
            if ($cart_total > 0) {
                $this->wdpCartDicount[$rule['id']] = (float)$discount_amt;
                $this->wdpCartDiscountValues[$rule['id']]['type'] = $disc_act_type; 
                // $this->wdpCartDiscountValues[$rule['id']]['calc'] = $disc_calc_type; 
                $this->wdpCartDiscountValues[$rule['id']]['quantity'] = $wdp_cart_quantity; 
                $this->wdpCartDiscountValues[$rule['id']]['items_count'] = $wdp_cart_items; 
                $this->wdpCartDiscountValues[$rule['id']]['value'] = $disc_act_val; 
                $this->wdpCartDiscountValues[$rule['id']]['products'] = $wdp_applicable_ids; 
            }

            // End Total Cart Quantity ///////////////////////////////////////////////////////////////
        
        } else if ( $quantity_type == 'type_product' ) { // Product Quantity Discount
            
            if (WC()->cart) {
                $cart_contents = WC()->cart->get_cart();
            } else {
                $cart_contents = [];
            }

            $prod_QNT = [];
            $prod_QNTIDs = [];
            $item_id = $item->get_ID();
            $VCheckFlag = false;
            $VDisApplied = false;

            if ($cart_contents) {
                foreach ($cart_contents as $cart_content) { 
                    $cartData = $cart_content['data']->get_data();
                    // $cartPID = $cartData['parent_id'];
                    $cartPSlug = $cartData['slug'];
                    $cart_id = $cart_content['data']->get_ID();
                    // if ($item_id == $cart_id)
                    // if( $cartPID != 0 && $variation_check ) {
                    //     $varProduct = new WC_Product_Variable( $parentID );
                    //     $varIDs = $varProduct->get_children();
                    //     foreach ( $varIDs as $varID ) { 
                    //         $varSlug = get_post_field( 'post_name', $varID ); 
                    //         $act_qnty += $prod_QNT[$varSlug]; 
                    //     }
                    //     $prod_QNT[$cart_content['data']->get_data()['slug']] = $cart_content['quantity'];
                    // } else {
                        $prod_QNT[$cartPSlug] = $cart_content['quantity'];
                        $prod_QNTIDs[$cart_id] = $cart_content['quantity'];
                    // }
                }
            }

            // $variation_check // added @ ver 3.4.5
            if ( $variation_check && array_key_exists ( $prod_ID, $prod_QNT ) ) { 
                $var_pid = wp_get_post_parent_id( $item_id );
                $act_qnty = 0;
                if ( $item->is_type('variable') ) { 
                    $VCheckFlag = true;
                    $varIDs = $this->wdpGetVariations( $item_id, false );
                    foreach ( $varIDs as $varID ) { 
                        if ( array_key_exists ( $varID, $prod_QNTIDs ) ) {
                            $act_qnty += $prod_QNTIDs[$varID]; 
                        }
                    }
                } else if ( $var_pid != 0 ) { 
                    $VCheckFlag = true;
                    $varIDs = $this->wdpGetVariations( $var_pid, false );
                    foreach ( $varIDs as $varID ) { 
                        if ( array_key_exists ( $varID, $prod_QNTIDs ) ) {
                            $act_qnty += $prod_QNTIDs[$varID]; 
                        }
                    }
                } else {
                    $act_qnty = $prod_QNT[$prod_ID];
                }
                $prod_QNT[$prod_ID] = ( $act_qnty > 0 ) ? $act_qnty : $prod_QNT[$prod_ID];
            }
            // End Check

            if ($table_layout == 'horizontal') {
                $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table lay_horzntl"><tbody class="wdp_table_body">';
                $tr_qn = '<tr><td>' . $awdp_qn_label . '</td>';
                $tr_pr = '<tr><td>' . $awdp_pc_label . '</td>';
                if ( $value_display == 'discount_both' ) {
                    $tr_nw = '<tr><td>' . $awdp_nw_label . '</td>';
                }
            } else {
                if ( $value_display == 'discount_both' ) {
                    $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table"><thead><tr class="wdp_table_head"><td>' . $awdp_qn_label . '*' . '</td><td>' . $awdp_pc_label . '</td><td>' . $awdp_nw_label . '</td></tr></thead><tbody class="wdp_table_body">';
                } else {
                    $table = '<div class="wdp_table_outter"><h4>' . $awdp_pc_title . '</h4><table class="wdp_table"><thead><tr class="wdp_table_head"><td>' . $awdp_qn_label . '</td><td>' . $awdp_pc_label . '</td></tr></thead><tbody class="wdp_table_body">';
                }
            }

            foreach ($quantity_rules as $quantity_rule) {
                $discount_val = $quantity_rule['dis_value'];
                $discount_typ = $quantity_rule['dis_type'];
                // $discount = '';

                // if ($item->is_type('variable')) {

                //     $variations = $item->get_available_variations();
                //     foreach($variations as $variation) {
                //         // array_push($variation_prices, wc_add_number_precision($variation['display_price']));
                //     }

                // }

                if (array_key_exists($prod_ID, $prod_QNT)) { 
                    if ($prod_QNT[$prod_ID] >= $quantity_rule['start_range'] && $prod_QNT[$prod_ID] <= $quantity_rule['end_range']) { 
                        if ($discount_typ == 'percentage') { 
                            $discount = $price_to_discount * ((float)$discount_val / 100); 
                            $discount = min($price_to_discount, $discount);
                        } else if ($discount_typ == 'fixed') {
                            $discount = wc_add_number_precision($discount_val);
                        }
                    } else if ($prod_QNT[$prod_ID] >= $max_range) { 
                        $discount_val_max = $last_key['dis_value'];
                        $discount_typ_max = $last_key['dis_type'];
                        if ($discount_typ_max == 'percentage') {
                            $discount = $price_to_discount * ((float)$discount_val_max / 100);
                            $discount = min($price_to_discount, $discount);
                        } else if ($discount_typ_max == 'fixed') {
                            $discount = wc_add_number_precision($discount_val_max);
                        }
                    }
                }

                // Currency conversion rate
                if( $this->converted_rate == '' && $item->get_ID() != '' ) {
                    $this->converted_rate = $this->get_con_unit($item, $price, true);
                }

                // $converted_rate = $this->converted_rate;
                $converted_rate = 1; // Removing coupon amount - get price changed to $price - calculate total

                // Pricing Table Calculations
                if ($item->is_type('variable')) {

                    // Variation Pricing Table
                    $price_to_discount_max = $variation_prices ? max($variation_prices) : 0;
                    $price_to_discount_min = $variation_prices ? min($variation_prices) : 0;

                    if ($discount_typ == 'percentage') {
                        $discount_max_value = $price_to_discount_max * ((float)$discount_val / 100);
                        $discount_min_value = $price_to_discount_min * ((float)$discount_val / 100);
                        // $discount_max_value = min($price_to_discount, $discount_pt);
                    } else if ($discount_typ == 'fixed') {
                        $discount_max_value = wc_add_number_precision($discount_val);
                        $discount_min_value = wc_add_number_precision($discount_val);
                    }

                    $discounted_new_max_price = (($price_to_discount_max - $discount_max_value) > 0) ? wc_price ( wc_remove_number_precision ( $price_to_discount_max - $discount_max_value ) ) : 0;
                    $discounted_new_min_price = (($price_to_discount_min - $discount_min_value) > 0) ? wc_price ( wc_remove_number_precision ( $price_to_discount_min - $discount_min_value ) ) : 0;

                    if ($table_layout == 'horizontal') {

                        if ( $quantity_rule['start_range'] == $quantity_rule['end_range'] ) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . '</td>';
                        } else if ($quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                        } else {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                        }
                        if ( $value_display == 'discount_value' ) {
                            if ($discount_typ == 'percentage') {
                                $tr_pr .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                            } else if ($discount_typ == 'fixed') {
                                $tr_pr .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                            }
                        } else if ( $value_display == 'discount_both' ) { // Display Both Price and Value
                            if ($discount_typ == 'percentage') {
                                $tr_pr .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                            } else if ($discount_typ == 'fixed') {
                                $tr_pr .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                            }
                            if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                $tr_nw .= '<td>' . $discounted_new_min_price . '</td>';
                            } else {
                                $tr_nw .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                            }
                        } else {
                            if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                $tr_pr .= '<td>' . $discounted_new_min_price . '</td>';
                            } else {
                                $tr_pr .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                            }
                        }

                    } else {

                        if ( $quantity_rule['start_range'] == $quantity_rule['end_range'] ) {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . '</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ( $discount_typ == 'percentage' ) {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        } else if ( $quantity_rule['end_range'] ) {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ( $discount_typ == 'percentage' ) {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ( $discounted_new_min_price == $discounted_new_max_price ) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        } else {
                            $table .= '<tr>';
                            $table .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                            if ( $value_display == 'discount_value' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                            } else if ( $value_display == 'discount_both' ) {
                                if ($discount_typ == 'percentage') {
                                    $table .= '<td>' .(float)$discount_val . $prcn_text . '</td>';
                                } else if ($discount_typ == 'fixed') {
                                    $table .= '<td>' . wc_price((float)$discount_val) . $fxd_text_two . '</td>';
                                }
                                if ($discounted_new_min_price == $discounted_new_max_price) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            } else {
                                if ($discounted_new_min_price == $discounted_new_max_price) {
                                    $table .= '<td>' . $discounted_new_min_price . '</td>';
                                } else {
                                    $table .= '<td>' . $discounted_new_min_price . ' - ' . $discounted_new_max_price . '</td>';
                                }
                            }
                            $table .= '</tr>';
                        }

                    }
                    // End

                } else {

                    if ($discount_typ == 'percentage') {
                        $discount_pt = $pricing_table_price * ((float)$discount_val / 100);
                        $discount_pt = min($pricing_table_price, $discount_pt);
                    } else if ($discount_typ == 'fixed') {
                        $discount_pt = wc_add_number_precision($discount_val * $converted_rate);
                    }

                    $discounted_new_price = (($pricing_table_price - $discount_pt) > 0) ? wc_price ( wc_remove_number_precision ( $pricing_table_price - $discount_pt ) * $converted_rate ) : 0;

                    $discounted_new_price_bt = $discounted_new_price;

                    if ( $value_display == 'discount_value' || $value_display == 'discount_both' ) {
                        if ($discount_typ == 'percentage') {
                            $discounted_new_price = (float)$discount_val . $prcn_text;
                        } else if ($discount_typ == 'fixed') {
                            $discounted_new_price =  wc_price((float)$discount_val) . $fxd_text_two;
                        }
                    }

                    if ( $table_layout == 'horizontal' ) {
                        if ($quantity_rule['start_range'] == $quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . '</td>';
                        } else if ($quantity_rule['end_range']) {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td>';
                        } else {
                            $tr_qn .= '<td>' . $quantity_rule['start_range'] . ' +</td>';
                        }
                        $tr_pr .= '<td>' . $discounted_new_price . '</td>';
                        if ( $value_display == 'discount_both' ) {
                            $tr_nw .= '<td>' . $discounted_new_price_bt . '</td>';
                        }
                    } else {
                        if ($quantity_rule['start_range'] == $quantity_rule['end_range']) {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . '</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . '</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        } else if ($quantity_rule['end_range']) {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' - ' . $quantity_rule['end_range'] . '</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        } else {
                            if ( $value_display == 'discount_both' ) {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' +</td><td>' . $discounted_new_price . '</td><td>' . $discounted_new_price_bt . '</td></tr>';
                            } else {
                                $table .= '<tr><td>' . $quantity_rule['start_range'] . ' +</td><td>' . $discounted_new_price . '</td></tr>';
                            }
                        }
                    }

                }
                // End PT
            }

            if ($table_layout == 'horizontal') {
                $tr_qn .= '</tr>';
                $tr_pr .= '</tr>';
                if ( $value_display == 'discount_both' ) { 
                    $tr_nw .= '</tr>';
                    $table .= $tr_qn . $tr_pr . $tr_nw . '</tbody></table></div>';
                } else {
                    $table .= $tr_qn . $tr_pr . '</tbody></table></div>';
                }
            } else {
                $table .= '</tbody></table></div>';
            }

            // Pricinig Table
            if ($rule['pricing_table'] == 1 && $pricing_table_price > 0) $this->pricing_table[$item->get_id()][$rule['id']] = $table;

            if (array_key_exists($prod_ID, $prod_QNT) && $this->validate_discount_rules($item, $rule, ['cart_total_amount', 'cart_total_amount_all_prods', 'product_price'], $item, true)) {

                // if ( $VCheckFlag ) { // Restrict discount to be applied only pnce for variable products
                //     $prod_PrntID = wp_get_post_parent_id ( $item_id );
                //     $prod_PrntID = get_post_field ( 'post_name', $prod_PrntID );
                //     $this->discounts[$rule['id']]['discounts'][$prod_PrntID] = $discount; 
                // } else {
                    $this->discounts[$rule['id']]['discounts'][$prod_ID] = $discount; 
                // }

                $this->discounts[$rule['id']]['taxable'] = $rule['inc_tax'];

                if ( $price_to_discount >= $discount && $discount != '' ) {
                    $updated_product_price = $price_to_discount - $discount;
                } else {
                    $updated_product_price = $price_to_discount;
                }

                // @@ Ver 3.4.5 @@ $VCheckFlag Ver 3.6.0
                // if ( $updated_product_price != $price_to_discount && ( !$VCheckFlag || ( $VCheckFlag && !$VDisApplied ) )) { 
                if ( $updated_product_price != $price_to_discount ) { 
                    // if ( $VCheckFlag ) {
                    //     $this->wdpCartDicount[$rule['id']] = (float)$discount;
                    //     $VDisApplied = true;
                    //     // $this->wdpCartDiscountValues[$rule['id']]['type'] = $disc_act_type; 
                    //     // // $this->wdpCartDiscountValues[$rule['id']]['calc'] = $disc_calc_type; 
                    //     // $this->wdpCartDiscountValues[$rule['id']]['quantity'] = $wdp_cart_quantity; 
                    //     // $this->wdpCartDiscountValues[$rule['id']]['items_count'] = $wdp_cart_items; 
                    //     // $this->wdpCartDiscountValues[$rule['id']]['value'] = $disc_act_val; 
                    //     // $this->wdpCartDiscountValues[$rule['id']]['products'] = $wdp_applicable_ids; 
                    // } else {
                        $wdpIndex = array_search ( $wdp_item_ID, array_column ( $this->wdpQNitems, 'product_id' ) ); 
                        if ( $wdpIndex === false ) {
                            $this->wdpQNitems[$wdpIndex] = array ( 'product_id' => $wdp_item_ID, 'discounted_price' => $updated_product_price );
                        } else {
                            $this->wdpQNitems[] = array ( 'product_id' => $wdp_item_ID, 'discounted_price' => $updated_product_price );
                        }
                    // }
                }

                // $this->wdp_discounted_price[$prod_ID] = $updated_product_price; // changed cart discount to discount coupon

            }

        }

        $this->wdpQNitems = array_values ( array_unique( $this->wdpQNitems , SORT_REGULAR ) );
        

    }

    public function get_discount($key, $in_cents = false)
    {
        $item_discount_totals = $this->get_discounts_by_item($in_cents);
        return isset($item_discount_totals[$key]) ? $item_discount_totals[$key] : 0;
    }

    public function get_discounts_by_item($in_cents = false)
    {
        $discounts = $this->discounts;
        $item_discount_totals = array();

        foreach ($discounts as $item_discounts) {
            if ($item_discounts['discounts']) {
                foreach ($item_discounts['discounts'] as $item_key => $item_discount) {
                    if (!isset($item_discount_totals[$item_key])) {
                        $item_discount_totals[$item_key] = 0.0;
                    }
                    $item_discount_totals[$item_key] += $item_discount;
                }
            }
        }

        return $in_cents ? $item_discount_totals : $item_discount_totals;
    }

    public function addVirtualCoupon($response, $curr_coupon_code)
    {

        if ( $this->discounts && WC()->cart ) {

            global $woocommerce;
            $cart_contents = $woocommerce->cart->get_cart();
            $prod_QNT = [];
            $total = 0;
            $ct_total = $this->wdpCartDicount;
            $ct_discount_values = $this->wdpCartDiscountValues;
            $ct_total_new = 0;
            $ct_cart_price_array = [];
            $converted_rate = $this->converted_rate ? $this->converted_rate : 1;

            $label = get_option('awdp_fee_label') ? get_option('awdp_fee_label') : 'Discount';
            $this->couponLabel = $label;

            foreach ($cart_contents as $cart_content) {
                $prod_QNT[$cart_content['data']->get_data()['slug']] = $cart_content['quantity'];
                $ct_total_new = $ct_total_new + ($cart_content['data']->get_price() * $cart_content['quantity']);
                $ct_cart_price_array[] = array ( 'id' => $cart_content['data']->get_slug(), 'price' => ( $cart_content['data']->get_price() * $cart_content['quantity'] ) );
            }

            foreach ($this->discounts as $ruleid => $discounts) { 

                $discount_type = $discounts['discount_type'];

                // if ( $label == $curr_coupon_code || mb_strtolower($label, 'UTF-8') == mb_strtolower($curr_coupon_code, 'UTF-8') ) {
                // @@ ver 3.3.4 - htmlspecialcharcheck added
                if ( ( $label == $curr_coupon_code ) || ( mb_strtolower($label, 'UTF-8') == mb_strtolower($curr_coupon_code, 'UTF-8') ) || ( preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $label) && mb_strtolower($label, 'UTF-8') == mb_strtolower(htmlspecialchars_decode ($curr_coupon_code), 'UTF-8') ) ) {

                    if ($discount_type == 'percent_total_amount' || $discount_type == 'fixed_cart_amount' || (true == $this->awdp_cart_rules && $discount_type != 'cart_quantity')) {
                        if (array_key_exists('discounts', $discounts)) {
                            foreach ($discounts['discounts'] as $key => $discount) {
                                if ($discount != '') {
                                    // Decimal Round
                                    $decimal_val = $discount - floor($discount);
                                    $discount = ( $decimal_val == 0 ) ? $discount : ( ( $decimal_val > 0.5 ) ? ceil ( $discount ) : floor ( $discount ) ); // version 3.4.2 -> round discount value -> decimal calculation fix
                                    
                                    if ($discount_type == 'fixed_cart_amount') {
                                        $total = $total + (wc_remove_number_precision($discount));
                                    } else if( array_key_exists ( $key, $prod_QNT ) ) {
                                        $total = $total + (wc_remove_number_precision($discount) * $prod_QNT[$key]);
                                    } else {
                                        $total = $total + (wc_remove_number_precision($discount));
                                    }
                                }
                            }
                        }
                    } else if ( $discounts['discount_type'] == 'cart_quantity' ) {
                        if ( isset ( $ct_discount_values[$ruleid] ) && array_key_exists ( 'products', $ct_discount_values[$ruleid] ) ) {
                            $disc_ids = $ct_discount_values[$ruleid]['products'];
                            $ct_total_temp = 0;
                            foreach ( $disc_ids as  $disc_id ) {
                                $ct_index = array_search ( $disc_id, array_column ( $ct_cart_price_array, 'id' ) );
                                $ct_total_temp = $ct_total_temp + $ct_cart_price_array[$ct_index]['price'];
                            }
                            $ct_total_new = $ct_total_temp;
                        } else {
                            $ct_total_new = $ct_total_new; 
                        }
                        if ( isset($discounts['discounts']) && array_key_exists('type', $discounts['discounts']) ) { 
                            $disc_total = 0;
                            if ( @$ct_discount_values[$ruleid]['type'] == 'percentage' ) {
                                // Decimal Round
                                $decimal_val = $ct_total_new - floor($ct_total_new);
                                $ct_total_new = ( $decimal_val == 0 ) ? $ct_total_new : ( ( $decimal_val > 0.5 ) ? ceil ( $ct_total_new ) : floor ( $ct_total_new ) ); // version 3.4.2 -> round discount value -> decimal calculation fix

                                $disc_val = $ct_discount_values[$ruleid]['value'];
                                $disc_total = $ct_total_new * ( $disc_val / 100 );
                            } else if ( @$ct_discount_values[$ruleid]['type'] == 'fixed' ) {
                                $disc_val = $ct_discount_values[$ruleid]['value'];
                                $disc_total = $disc_val;
                                $disc_total = ( $disc_total >=0 ) ? $disc_total : 0;
                            } else {
                                $disc_total = @$ct_total[$ruleid];
                            }
                            // $total = $total + $ct_total[$ruleid];  
                            $total = $total + $disc_total;  
                            // Moved discount on product quantity to cart items
                        } else if (isset($discounts) && array_key_exists('discounts', $discounts)) {
                            foreach ($discounts['discounts'] as $key => $discount) {
                                if ($discount != '') {
                                    // Decimal Round
                                    $decimal_val = $discount - floor($discount);
                                    $discount = ( $decimal_val == 0 ) ? $discount : ( ( $decimal_val > 0.5 ) ? ceil ( $discount ) : floor ( $discount ) ); // version 3.4.2 -> round discount value -> decimal calculation fix

                                    if ($discounts['discount_type'] == 'fixed_cart_amount') {
                                        $total = $total + (wc_remove_number_precision($discount));
                                    } else {
                                        $total = $total + (wc_remove_number_precision($discount) * $prod_QNT[$key]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($total > 0) {

                // if ( $this->converted_rate > 1 && class_exists('WC_Aelia_CurrencySwitcher') ) { // Checking if Aelia Currency Plugin is active
                if ( $converted_rate > 1 ) { // Removing conversion from coupon total
                    $total = $total / $converted_rate;
                } 

                if (!$discount_type) return false;
                $coupon_array = array(
                    'code' => mb_strtolower($label, 'UTF-8'),
                    'id' => 99999999 + rand(1000, 9999),
                    'amount' => $total,
                    'individual_use' => false,
                    'product_ids' => array(),
                    'exclude_product_ids' => array(),
                    'usage_limit' => '',
                    'usage_limit_per_user' => '',
                    'limit_usage_to_x_items' => '',
                    'usage_count' => '',
                    'expiry_date' => '',
                    'apply_before_tax' => 'yes',
                    'free_shipping' => false,
                    'product_categories' => array(),
                    'exclude_product_categories' => array(),
                    'exclude_sale_items' => false,
                    'minimum_amount' => '',
                    'maximum_amount' => '',
                    'customer_email' => '',
                    'discount_type' => $discount_type,
                );

                if ( !WC()->session->get( 'AWDP_CART_NOTICE' ) && get_option( 'awdp_message_status' ) == 1 && !isset( $_POST['update_cart'] ) && !is_checkout() ) { 

                    // wc_clear_notices();  // Clear Woocommerce notices

                    // define('AWDP_CART_NOTICE', true);
                    WC()->session->set( 'AWDP_CART_NOTICE', true ); // Changed to session 3.4.5

                    $notice = (get_option('awdp_message_status') == 1) ? (get_option('awdp_discount_message') ? str_replace('[label]', $label, get_option('awdp_discount_message')) : (('discount' == mb_strtolower($label, 'UTF-8')) ? $label . __(" has been applied!", "aco-woo-dynamic-pricing") : __("Discount '", "aco-woo-dynamic-pricing") . $label . __("' has been applied!", "aco-woo-dynamic-pricing"))) : (('discount' == mb_strtolower($label, 'UTF-8')) ? $label . __(" has been applied!", "aco-woo-dynamic-pricing") : __("Discount '", "aco-woo-dynamic-pricing") . $label . __("' has been applied!", "aco-woo-dynamic-pricing"));

                    if (false === wc_has_notice($notice, "awdpcoupon")) {
                        wc_add_notice($notice, "awdpcoupon");
                    }
                }

                return $coupon_array;
            } 
        }
        return $response;
    }

    // Create virtual coupon

    public function couponLabel($label, $coupon)
    {

        if ($coupon) {
            $coupon_label = $this->couponLabel;
            $code = $coupon->get_code();
            if ($code == $coupon_label || mb_strtolower($code, 'UTF-8') == mb_strtolower($coupon_label, 'UTF-8')) {
                return ucfirst($coupon_label);
            }
        }
        return $label;
    }

    // Change discount coupon label
    public function applyFakeCoupons()
    {

        global $woocommerce;  //apply_filters('woocommerce_applied_coupon');
        $coupon = get_option('awdp_fee_label') ? get_option('awdp_fee_label') : 'Discount';
        $coupon_code = apply_filters('woocommerce_coupon_code', $coupon);
        if ( !in_array($coupon_code, $woocommerce->cart->get_applied_coupons()) && $this->discounts && true == $this->apply_wdp_coupon && WC()->cart ) {
            $coupons_obj = new WC_Coupon($coupon_code);
            $coupons_amount = $coupons_obj->get_amount();
            if ($coupons_amount > 0) {
                $woocommerce->cart->add_discount($coupon_code);
                // wc_clear_notices(); // Clear Woocommerce notices
            }
        } else if ( in_array($coupon_code, $woocommerce->cart->get_applied_coupons()) ) {
            $coupons_obj = new WC_Coupon($coupon_code);
            $coupons_amount = $coupons_obj->get_amount();
            if ($coupons_amount == 0) {
                WC()->cart->remove_coupon($coupon_code);
                //   wc_clear_notices(); // Clear Woocommerce notices
            }
        }

        $applied_coupons = WC()->cart->get_applied_coupons();

        return true;
    }

    // Get variations 
    public function wdpGetVariations ( $productID, $list = false ) {

        if ( $productID ) {
            if ( ( !is_array ( $productID ) && array_key_exists ( $productID, $this->productvariations ) ) || ( $list && array_key_exists ( $list, $this->productvariations ) ) ) {
                return $this->productvariations[$productID];
            } else {
                global $wpdb;
                $productID = is_array ( $productID ) ? implode(',', $productID) : $productID; 

                $PLVariations = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_parent IN ($productID) AND post_type = 'product_variation'");

                if ( $PLVariations ) {
                    if ( !is_array ( $productID ) ) $this->productvariations[$productID] = $PLVariations;
                    else if ( $list ) $this->productvariations[$list] = $PLVariations;

                    return $PLVariations;
                } 
            }
        }
        return false;

    }
    
    //
    public function wdpCartLoop ( $wc, $cart_content, $cart_item_key )
    {

        $QNitems = $this->wdpQNitems;
        $decimalPoints = wc_get_price_decimals();

        $product_id = ( $cart_content['variation_id'] == 0 || $cart_content['variation_id'] == '' ) ? $cart_content['product_id'] : $cart_content['variation_id'];
        $actual_product_id = $cart_content['product_id'];

        if ( ( sizeof($QNitems) > 0 ) && array_search ( $product_id, array_column ( $QNitems, 'product_id' ) ) !== false && !defined ( "WDP_QN_SET_".$product_id ) ) {

            $qn_index = array_search ( $product_id, array_column ( $QNitems, 'product_id' ) );
            // $qn_price = wc_remove_number_precision ( $QNitems[$qn_index]['discounted_price'] );
            $qn_price = round ( wc_remove_number_precision ( $QNitems[$qn_index]['discounted_price'] ), $decimalPoints ); // version 3.4.3 -> price rounded -> decimal calculation fix

            $quantity = $cart_content['quantity'];

            $product = wc_get_product( $actual_product_id ); 

            // @@ 3.6.1
            if ( $qn_price > 0 ) {
                if (WC()->cart->display_prices_including_tax()) {
                    $qn_price = $this->wdp_price_including_tax ( $product, array(
                        'qty' => $quantity,
                        'price' => $qn_price,
                    ), $qn_price );
                } else {
                    $qn_price = $this->wdp_price_excluding_tax ( $product, array(
                        'qty' => $quantity,
                        'price' => $qn_price
                    ), $qn_price );
                }
            }
            // $cart_content['data']->set_price( $qn_price );
            define( "WDP_QN_SET_".$product_id, true );

            // if ( $this->converted_rate ) {
            //     $product_subtotal = wc_price ( $qn_price * $this->converted_rate );
            // } else {
                $product_subtotal = wc_price ( $qn_price );
            // }

            if ( $product->is_taxable() && get_option('woocommerce_tax_display_cart') == 'incl' ) {
                if( !wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
                    $product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
                }
            }

            return $product_subtotal;

        } else {

            return $wc;

        }

    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    // End Coupons

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    protected function apply_discount_remainder($rule, $items_to_apply, $amount)
    {
        $total_discount = 0;

        foreach ($items_to_apply as $item) {
            for ($i = 0; $i < $item->quantity; $i++) {
                // Find out how much price is available to discount for the item.
                $discounted_price = $this->get_discounted_price_in_cents($item);

                // $price_to_discount = (false) ? $discounted_price : $item->price;// check if apply_ sequential

                $discount = min($discounted_price, 1);

                // Store totals.
                $total_discount += $discount;

                // Store code and discount amount per item.
                $this->discounts[$rule['id']]['discounts'][$item->key] += $discount;

                if ($total_discount >= $amount) {
                    break 2;
                }
            }
            if ($total_discount >= $amount) {
                break;
            }
        }

        return $total_discount;
    }

    public function get_discounted_price_in_cents($item, $include_tax = true, $sequential = false)
    {

        $product_actual_price = $item->get_data()['price'];
        $excluding_tax = get_option('woocommerce_tax_display_shop');
        if ($include_tax && $excluding_tax == 'incl') {
            $price = $this->wdp_price_including_tax ( $item, array (
                'price' => $product_actual_price,
            ), $product_actual_price );
        } else {
            $price = $this->wdp_price_excluding_tax ( $item, array (
                'price' => $product_actual_price,
            ), $product_actual_price );
        }

        // if($sequential)
        //     return abs($price - wc_remove_number_precision($this->get_discount($item->get_id(), true)));
        // else
        return $price;
    }

    // Woocommerce functions
    function wdp_price_including_tax( $product, $args = array(), $prodPrice ) {

        $args = wp_parse_args(
            $args,
            array(
                'qty'   => '',
                'price' => '',
            )
        );
    
        $price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $prodPrice;
        $qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;
    
        if ( '' === $price ) {
            return '';
        } elseif ( empty( $qty ) ) {
            return 0.0;
        }
    
        $line_price   = $price * $qty;
        $return_price = $line_price;
    
        if ( $product->is_taxable() ) {
            if ( ! wc_prices_include_tax() ) {
                $tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
                $taxes     = WC_Tax::calc_tax( $line_price, $tax_rates, false );
    
                if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
                    $taxes_total = array_sum( $taxes );
                } else {
                    $taxes_total = array_sum( array_map( 'wc_round_tax_total', $taxes ) );
                }
    
                $return_price = round( $line_price + $taxes_total, wc_get_price_decimals() );
            } else {
                $tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
                $base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
    
                /**
                 * If the customer is excempt from VAT, remove the taxes here.
                 * Either remove the base or the user taxes depending on woocommerce_adjust_non_base_location_prices setting.
                 */
                if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) { // @codingStandardsIgnoreLine.
                    $remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );
    
                    if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
                        $remove_taxes_total = array_sum( $remove_taxes );
                    } else {
                        $remove_taxes_total = array_sum( array_map( 'wc_round_tax_total', $remove_taxes ) );
                    }
    
                    $return_price = round( $line_price - $remove_taxes_total, wc_get_price_decimals() );
    
                    /**
                 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
                 * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
                 * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
                 */
                } elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
                    $base_taxes   = WC_Tax::calc_tax( $line_price, $base_tax_rates, true );
                    $modded_taxes = WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates, false );
    
                    if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
                        $base_taxes_total   = array_sum( $base_taxes );
                        $modded_taxes_total = array_sum( $modded_taxes );
                    } else {
                        $base_taxes_total   = array_sum( array_map( 'wc_round_tax_total', $base_taxes ) );
                        $modded_taxes_total = array_sum( array_map( 'wc_round_tax_total', $modded_taxes ) );
                    }
    
                    $return_price = round( $line_price - $base_taxes_total + $modded_taxes_total, wc_get_price_decimals() );
                }
            }
        }
        return apply_filters( 'woocommerce_get_price_including_tax', $return_price, $qty, $product );
    }
    
    function wdp_price_excluding_tax( $product, $args = array(), $prodPrice ) {
        
        $args = wp_parse_args(
            $args,
            array(
                'qty'   => '',
                'price' => '',
                'skipcheck' => ''
            )
        );
    
        $price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $prodPrice;
        $qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;
        $skipcheck  = '' !== $args['skipcheck'] ? true : false;
    
        if ( '' === $price ) {
            return '';
        } elseif ( empty( $qty ) ) {
            return 0.0;
        }
    
        $line_price = $price * $qty;
    
        if ( ( $product->is_taxable() && wc_prices_include_tax() ) || $skipcheck ) {
            $tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
            $base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
            $remove_taxes   = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );
            $return_price   = $line_price - array_sum( $remove_taxes ); // Unrounded since we're dealing with tax inclusive prices. Matches logic in cart-totals class. @see adjust_non_base_location_price.
        } else {
            $return_price = $line_price;
        }
    
        return apply_filters( 'woocommerce_get_price_excluding_tax', $return_price, $qty, $product );
    }

}
