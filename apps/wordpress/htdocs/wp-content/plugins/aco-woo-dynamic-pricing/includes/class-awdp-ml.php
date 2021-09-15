<?php

if (!defined('ABSPATH'))
    exit;

class WCPA_Ml
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
    public $default_lang;
    public $current_lang;
    private $_active = false;

    public function __construct()
    {

        if (class_exists('SitePress')) {
            $this->_active = 'wpml';
            $this->default_lang = apply_filters('wpml_default_language', NULL);
            $this->current_lang = apply_filters('wpml_current_language', NULL);
        } else if (defined('POLYLANG_VERSION')) {
            $this->_active = 'polylang';
            $this->default_lang = pll_default_language();
            $this->current_lang = pll_current_language();
        }
    }

    /**
     *
     *
     * Ensures only one instance of WCPA is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main WCPA instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    public function is_active()
    {
        return $this->_active !== false;
    }

    public function is_default_lan()
    {
        return ($this->current_lang === $this->default_lang);
    }

    public function default_language()
    {
        return $this->default_lang;
    }

    public function current_language()
    {
        return $this->current_lang;
    }

    public function is_base_lang()
    {
        if ($this->_active === 'wpml') {
            $my_default_lang = apply_filters('wpml_default_language', NULL);
            $my_current_lang = apply_filters('wpml_current_language', NULL);
            return $my_default_lang == $my_current_lang;
        } else if ($this->_active === 'polylang') {
            $my_default_lang = pll_default_language();
            $my_current_lang = pll_current_language();
            return $my_default_lang == $my_current_lang;
        } else {
            return false;
        }
    }

    public function lang_object_ids($object_id, $type)
    {
        if (is_array($object_id)) {
            $translated_object_ids = array();
            foreach ($object_id as $id) {
//
                if ($this->_active === 'wpml') {
                    $translated_object_ids[] = apply_filters('wpml_object_id', $id, $type, true);
                } else if ($this->_active === 'polylang') {
                    $p_id = pll_get_post($id);
                    if ($p_id) {
                        $translated_object_ids[] = $p_id;
                    } else {
                        $translated_object_ids[] = $id;
                    }
                }
            }
            return array_unique($translated_object_ids);
        } else {
            if ($this->_active === 'wpml') {
                return apply_filters('wpml_object_id', $object_id, $type, true);
            } else if ($this->_active === 'polylang') {
                $p_id = pll_get_post($object_id);
                if ($p_id) {
                    return $p_id;
                } else {
                    return $object_id;
                }
            }
        }
    }

    public function settings_to_wpml()
    {
        //   WCPA_SETTINGS_KEY

        $settings = [
            'options_total_label' => 'Options Price Label',
            'options_product_label' => 'Product Price Label',
            'total_label' => 'Total Label',
            'add_to_cart_text' => 'Add to cart button text',
        ];
        //WMPL
        /**
         * register strings for translation
         */
        if (function_exists('icl_register_string')) {
            foreach ($settings as $k => $v) {
                icl_register_string(WCPA_TEXT_DOMAIN, false, wcpa_get_option($k));
            }
        }
        if (function_exists('pll_register_string')) {
            foreach ($settings as $k => $v) {
                pll_register_string(WCPA_TEXT_DOMAIN, wcpa_get_option($k));
            }
        }


        //\WMPL
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

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

}
