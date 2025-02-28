<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action('wp_loaded', 'watq_add_cart_to_quote',20);
function watq_add_cart_to_quote () {

    if($_POST) {
        if(isset($_POST['action'])) {
            if($_POST['action'] == 'from_cart_to_quote') {

                $expire = '';
                $quote_exist = array();
                $array_updated = array();
                if(is_array($_POST['product'])) { 
                    foreach($_POST['product'] as $product) {
                        $product_variation_attr = null;
                        $product_id = intval($product['product_id']);
                        $product_image = sanitize_text_field($product['product_image']);
                        $product_title = sanitize_text_field($product['product_title']);
                        $product_quantity = intval($product['product_quantity']);
                        

                        $product_type = sanitize_text_field($product['product_type']);
                        
                        $length_mm = intval($product['length_mm']);
                        
                          //print_r($length_mm); die;
                        if(array_key_exists('product_variations', $product)) {
                            $variation_data = json_decode(stripslashes($product['product_variations']), true);
                            $var_attr = array();
                            foreach($variation_data as $var_key=>$var_value) {
                                $var_attr[] = array($var_key,$var_value);
                            }

                            $variation_attr_array = watq_get_product_variations($var_attr);
                            $product_variation_attr = $variation_attr_array;
                        }
                        else {
                            $product_variation_attr = '';
                        }
                        if(array_key_exists('product_variation_id', $product)) {
                            $product_variation_id = intval($product['product_variation_id']);
                        }


                        $expire = time()+3600*24*100;

                        $set_array = array(
                            "product_id" => $product_id,
                            "product_image" => $product_image,
                            "product_title" => $product_title,
                            "product_quantity" => $product_quantity,
                            "product_type" => $product_type,
                            "variations_attr" => ((isset($product_variation_attr) && !empty($product_variation_attr)) ?  $product_variation_attr : ''),
                            "product_variation_id" => ((isset($product_variation_id) && !empty($product_variation_id)) ?  $product_variation_id : ''),
                            //"length_mm" => $length_mm,
                        );
                    
                    if (isset($product['length_mm']) && !empty($product['length_mm'])) {
                        $set_array["length_mm"] = $length_mm;
                    }

                        $updated_checked = watq_quote_exists_on_cart($set_array["product_variation_id"], $set_array["product_quantity"], $length_mm);

                        if(!empty($updated_checked) && $updated_checked !==  false) {
                            $quote_exist[] = $updated_checked['product_variation_id'];
                            $array_updated[] = $updated_checked;
                        }
                        else {
                            $array_updated[] = $set_array;
                        }
                    }
                }
                if((boolean)get_option('wc_settings_empty_quote_to_cart')) {
                    watq_remove_product_from_cart();
                }

                $result_id = setcookie('_quotes_elem', json_encode($array_updated), $expire, COOKIEPATH, COOKIE_DOMAIN, false);
                echo '<META HTTP-EQUIV="refresh" content="0;URL='.get_permalink(get_page_by_path('quote')).'">';
                exit();
            }
        }
    }
}

// function that empty cart.
function watq_remove_product_from_cart() {

    $WC = WC();
    $result = array();
    foreach ( $WC->cart->get_cart() as $cart_item_key => $cart_item ) {
        $result[] = $WC->cart->set_quantity( $cart_item_key, - 1, true  );
    }
}

// if products exists in quote or not.
function watq_quote_exists_on_cart($product_id, $quantity) {

    $cookie_data = isset($_COOKIE['_quotes_elem']) ? $_COOKIE['_quotes_elem'] : '';
    $update_param = array();
    if (!empty($cookie_data)) {
        $exists_quote = json_decode(stripslashes($cookie_data), true);
        $update_quote = null;
        if(is_array($exists_quote)) {
            foreach ($exists_quote as $quote) {
                if(in_array($product_id, $quote)) {
                    $update_param = array(
                        "product_id" => $quote["product_id"],
                        "product_image" => $quote["product_image"],
                        "product_title" => $quote["product_title"],
                        "product_quantity" => $quote["product_quantity"] + $quantity,
                        "product_type" => isset($quote["product_type"]) ? $quote["product_type"] : '',
                        "variations_attr" => (array_key_exists('variations_attr', $quote) ? $quote["variations_attr"] : ''),
                        "product_variation_id" => (array_key_exists('product_variation_id', $quote) ? $quote["product_variation_id"] : $quote["product_id"]),
                        
                        "length_mm" => isset($quote["length_mm"]) ? $quote["length_mm"] : '',
                    );
                }
            }
        }
        return $update_param;
    }
    else {
        return false;
    }

}

// add rest of the product for cookie.
function _complete_array_for_cookie($updated_products, $array_updated) {
    $cookie_data = isset($_COOKIE['_quotes_elem']) ? $_COOKIE['_quotes_elem'] : '';
    $update_param = array();
    if (!empty($cookie_data)) {
        $exists_quote = json_decode(stripslashes($cookie_data), true);
        $update_quote = null;
        if(is_array($exists_quote)) {
            foreach ($exists_quote as $quote) {
                if(!in_array($quote['product_variation_id'], $updated_products)) {
                    $update_param = array(
                        "product_id" => $quote["product_id"],
                        "product_image" => $quote["product_image"],
                        "product_title" => $quote["product_title"],
                        "product_quantity" => $quote["product_quantity"],
                        "product_type" => isset($quote["product_type"]) ? $quote["product_type"] : '',
                        "variations_attr" => (array_key_exists('variations_attr', $quote) ? $quote["variations_attr"] : ''),
                        "product_variation_id" => (array_key_exists('product_variation_id', $quote) ? $quote["product_variation_id"] : $quote["product_id"]),
                        
                       "length_mm" => isset($quote["length_mm"]) ? $quote["length_mm"] : '',
                    );
                    array_push($array_updated, $update_param);
                }
            }
        }
        return $array_updated;
    }
}