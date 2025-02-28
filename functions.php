<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
define("WATQ", "watq");

add_action('init', 'watq__require_files');
function watq__require_files() {
    require_once('admin/class_watq_meta_boxes.php');
    require_once('admin/admin_functions.php');
    require_once('shortcodes.php');

}

add_action('init', '_watq_unread_quotes');
function _watq_unread_quotes() {
    $waqt_unread_quotes = get_option('watq_unread_quotes');
    if($waqt_unread_quotes === false or (int)$waqt_unread_quotes < 0) {
        update_option('watq_unread_quotes', 0);
    }
}

add_filter( 'woocommerce_get_settings_pages', 'waqt_add_settings_page' );
function waqt_add_settings_page( $settings ) {
    $settings[] = include('admin/class_setting_page.php');
    return $settings;
}

add_action('wp_enqueue_scripts', 'quote_scripts');

function quote_scripts() {
    add_thickbox();

    // Register and enqueue JavaScript
    wp_register_script(
        'quote-script-js',
        plugin_dir_url(__FILE__) . 'js/quote.js',
        array('jquery'),
        null,
        true
    );

    // Localize the script with plugin-related data and nonce for security
    wp_localize_script('quote-script-js', 'plugin_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('cart_to_quote_nonce'), // Add nonce for AJAX security
    ));

    wp_enqueue_script('quote-script-js');

    // Enqueue CSS
    wp_enqueue_style(
        'quote-style',
        plugin_dir_url(__FILE__) . 'css/quote.css'
    );
}


add_action('admin_enqueue_scripts','watq_admin_scripts');
function watq_admin_scripts() {
    wp_enqueue_style('quote-style', plugins_url( '/css/watq_admin.css', __FILE__ ));
}


add_action('init', 'watq_quote_submission_');
function watq_quote_submission_() {
    if ($_POST) {
        if (isset($_POST['action']) && $_POST['action'] == "quote_submission_") {

            $product_id = intval($_POST['product_id']);
            $product_image = sanitize_text_field($_POST['product_image']);
            $product_title = sanitize_text_field($_POST['product_title']);
            $product_quantity = intval($_POST['product_quantity']);

            if (isset($_POST['length_mm']) && !empty($_POST['length_mm'])) {
                $length_mm = intval($_POST['length_mm']);
            }

            // Assign $product_type at the top
            $product_type = sanitize_text_field($_POST['product_type'] ?? '');


            // Ensure variation is selected for variable products
            if ($product_type === 'variable') {
                if (!isset($_POST['variation_id']) || empty(trim($_POST['variation_id']))) {
                    wc_add_notice(__('Please select a valid product variation before adding to quote.', WATQ), 'error');
                    return;
                }
            }

            if (array_key_exists('variations_attr', $_POST)) {
                $product_variations = new WC_Product_Variable($product_id);
                $variation_attr_array = $product_variations->get_available_variations();

                $variation_data = array($_POST['variations_attr']);
                $variation_attr_array = watq_get_product_variations($variation_data);
                $product_variation_attr = $variation_attr_array;
            } else {
                $product_variation_attr = '';
            }

            if (array_key_exists('variation_id', $_POST)) {
                $product_variation_id = intval($_POST['variation_id']);
            }

            // Prevent adding to quote if variation is required but missing
            if ($product_type === 'variable' && !$product_variation_id) {
                wc_add_notice(__('Please select a valid product variation before adding to quote.', WATQ), 'error');
                return;
            }

            $expire = time() + 3600 * 24 * 100;

            $set_array = array(
                "product_id" => $product_id,
                "product_image" => $product_image,
                "product_title" => $product_title,
                "product_quantity" => $product_quantity,
                "product_type" => $product_type,
                "variations_attr" => $product_variation_attr,
                "product_variation_id" => ((isset($product_variation_id) && !empty($product_variation_id)) ? $product_variation_id : $product_id),
            );

            if (isset($_POST['length_mm']) && !empty($_POST['length_mm'])) {
                $length_mm = intval($_POST['length_mm']);
                $set_array["length_mm"] = $length_mm;
            }

            $updated_checked = watq_quote_exists_($set_array["product_variation_id"], $set_array["product_quantity"], $length_mm);
            if ($updated_checked !== false) {
                if (!$updated_checked[0]) {
                    $update_quote = $updated_checked[1];
                    $update_quote[] = $set_array;
                } else {
                    $update_quote = $updated_checked[1];
                }
            } else {
                $update_quote = array($set_array);
            }

            $result_id = setcookie('_quotes_elem', json_encode($update_quote), $expire, COOKIEPATH, COOKIE_DOMAIN, false);

            if ($result_id) {
                $message = "<div class='_quote_message_'>" . $product_title . " " . __('has been added to your quote', WATQ) . " <a href='" . get_permalink(get_page_by_path('quote')) . "'>" . __('View Quote', WATQ) . "</a></div>";
                wc_add_notice($message, $notice_type = 'success');
            } else {
                $message = "<div class='_quote_message_'>" . __('Please try again. ', WATQ) . "</div>";
                wc_add_notice($message, $notice_type = 'error');
            }
        }
    }
}

function watq_quote_exists_($product_id, $quantity, $length_mm = null) {
    $cookie_data = isset($_COOKIE['_quotes_elem']) ? $_COOKIE['_quotes_elem'] : '';
    $return = false;

    if (!empty($cookie_data)) {
		// die("OK");
        $exists_quote = json_decode(stripslashes($cookie_data), true);
        $unique_num = 0;
        $update_quote = null;
        $increase_exists = false;

        if(is_array($exists_quote)) {
            foreach ($exists_quote as $quote) {
                $increase_count = false;
                //if(in_array($product_id, $quote)) {
                if($product_id == $quote['product_id'] && ($length_mm === null || $length_mm == $quote['length_mm'])) {
                    $increase_count = true;
                    $increase_exists = $increase_count;
                }
                $update_param = array(
                    "product_id" => $quote["product_id"],
                    "product_image" => $quote["product_image"],
                    "product_title" => $quote["product_title"],
                    "product_quantity" => ($increase_count) ? $quote["product_quantity"] + $quantity : $quote["product_quantity"],
                    "product_type" => isset($quote["product_type"]) ? $quote["product_type"] : '',
                    "variations_attr" => (array_key_exists('variations_attr', $quote) ? $quote["variations_attr"] : ''),
                    "product_variation_id" => (array_key_exists('product_variation_id', $quote) ? $quote["product_variation_id"] : $quote["product_id"]),
                    "length_mm" => isset($quote["length_mm"]) ? $quote["length_mm"] : '', 
                );

                if(!empty($update_quote)) {
                    $update_quote[] = $update_param;
                }
                else {
                    $update_quote = array($update_param);
                }
                $unique_num++;
            }
        }

        if($increase_exists) {
            $return = true;
        }


        if($return) {
            return array(true,$update_quote );
        }
        else {
            return array(false,$update_quote);
        }
    }
    else {
		// die("DSDS");
        return false;
    }

}

add_filter('woocommerce_login_redirect', 'watq_quote_login_redirect');
function watq_quote_login_redirect( $redirect ) {
    if(array_key_exists('rq', $_GET) && $_GET['rq'] == 'login') {
        $redirect = get_permalink(get_page_by_path('quote'));
    }
    return $redirect;
}

add_filter('woocommerce_registration_redirect', 'watq_quote_register_redirect');
function watq_quote_register_redirect( $redirect ) {
    if(array_key_exists('rq', $_GET) && ($_GET['rq'] == 'login')) {
        $redirect = get_permalink(get_page_by_path('quote'));
        return $redirect;
    }
}

add_action('init','watq_plugin_setup');
function watq_plugin_setup() {
    if (is_admin()){
        $page_array = array('quote');
        for($a=0; $a<1; $a++) {
            $blog_page_title = ucfirst(str_replace('-',' ',$page_array[$a]));
            $blog_page_content = '[_'.$page_array[$a].']';
            $blog_page_check = get_page_by_title($blog_page_title);
            $blog_page = array(
                'post_type' => 'page',
                'post_title' => $blog_page_title,
                'post_content' => $blog_page_content,
                'post_status' => 'publish',
                'post_author' => 1,
                'post_slug' => 'quote'
            );
            if(!isset($blog_page_check->ID)){
                $blog_page_id = wp_insert_post($blog_page);
            }
        }
    }
}

// function watq_product_exists_in_cart($item_loop) {
//     global $woocommerce;
//     foreach($woocommerce->cart->get_cart() as $cart_item_key => $cart_item){
//         $cart_variation = null;
//         $item_id = null;
//         if($item_loop['product_type'] == "variation") {
//             $cart_variation = $cart_item['variation_id'];
//             $item_id = $item_loop['variation_id'];
//         }
//         elseif($item_loop['product_type'] == "simple") {
//             $cart_variation = $cart_item['product_id'];
//             $item_id = $item_loop['product_id'];
//         }
//         if($cart_variation == $item_id) {
//             $woocommerce->cart->set_quantity( $cart_item_key, $cart_item['quantity']+$item_loop['product_quantity'], true  );
//             return true;
//         }
//         else {
//             return false;
//         }
//     }

// }

function watq_product_exists_in_cart($item_loop) {
    global $woocommerce;
    foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) {
        $cart_product_id = $cart_item['product_id'];
        $cart_variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : null;
        $item_product_id = $item_loop['product_id'];
        $item_variation_id = isset($item_loop['variation_id']) ? $item_loop['variation_id'] : null;
        $item_length_mm = isset($item_loop['length_mm']) ? $item_loop['length_mm'] : null;
        
        if ($cart_product_id == $item_product_id && $cart_variation_id == $item_variation_id) {
            // Check if length_mm matches or is not set
            if (!isset($cart_item['length_mm']) || $cart_item['length_mm'] == $item_length_mm) {
                $woocommerce->cart->set_quantity($cart_item_key, $cart_item['quantity'] + $item_loop['product_quantity'], true);
                return true;
            }
        }
    }
    return false;
}

add_action('wp_loaded', 'watq_convert_to_cart');
function watq_convert_to_cart() { 
    
    if($_POST) {
        if(isset($_POST['action'])) {
            if(!empty($_POST['action']) && $_POST['action'] == "add_to_cart_q") {
                $submit_data = array_map('watq_validate_array', $_POST['data']);
                
                $items_for_cart = array();
                foreach($submit_data as $sub_data) {
                    
                    $items_for_cart[] = $sub_data;
                }
                global $woocommerce;
                $cart_num = 0;
				$itemtype = "";
                foreach($items_for_cart as $item) {
					if($item['product_id'] != $item['variation_id']){
						$itemtype = 'variation';
					}else{
						// die("OK");
						$itemtype = 'simple';
					}
					// echo "<pre>";
					//print_r($items_for_cart);die;
					
                    if($itemtype == 'variable' || $itemtype == 'variation') {
                        $variation_data = json_decode(stripslashes($item['variation_attr']), true);
                        $variations_available_ = $variation_data;

                        $variation_arr = array();
                        if (is_array($variations_available_) || is_object($variations_available_)) {
                            foreach ($variations_available_ as $key_variation_ => $val_variation_) {
                                $variation_arr[$key_variation_] = $val_variation_;
                            }
                        }
                        
                        
                        $cart_result = watq_product_exists_in_cart($item);
                        if(!$cart_result) {
                           
                        $woocommerce->cart->add_to_cart($item['product_id'], $item['product_quantity'],$item['variation_id'], $variation_arr);
                            
                        }
                    }
                    elseif($itemtype == 'simple') {
						// die("OK");
						
					 $length_mm = array(
                         'length_mm' => $item['length_mm'] 
                        );
                     
                     
                        $cart_result = watq_product_exists_in_cart($item);
                        
                        if(!$cart_result) {
                            
                            $woocommerce->cart->add_to_cart($item['product_id'], $item['product_quantity'],null, null, $length_mm);
                          
                        }
                        
                    }

                    $cart_num++;
                }
                $empty_quote_to_cart = (boolean)get_option('wc_settings_empty_quote_to_cart');
                if($empty_quote_to_cart) {
                    setcookie('_quotes_elem', '', time()-3600, COOKIEPATH, COOKIE_DOMAIN, false);
                }
                echo '<META HTTP-EQUIV="refresh" content="0;URL='.get_permalink(get_page_by_path('cart')).'">';
                exit();
            }
        }
    }
}

function watq_validate_array($val) {
    
    foreach($val as $key=>$key_val){ 
        if($key == "product_id" || $key == "product_quantity" || $key == "variation_id"  ) {
            $val[$key] = intval($key_val);
        }
        else {
            $val[$key] = sanitize_text_field($key_val);
        }
        
    }
    return $val;
}

add_action('woocommerce_before_my_account', 'watq_show_saved_quotes');
function watq_show_saved_quotes() {

    $user_last = get_user_meta( get_current_user_id(), 'watq_quote_whishlist', true);

    if(!empty($user_last)) {
        ?>
        <div class="" id="waqt_user_quote_detail">
            <form action="<?php the_permalink(); ?>" method="post">
                <input type="hidden" name="action" value="clear_saved_quotes" />
                <input type="submit" class="button delete_saved_quotes" value="<?php echo __('Delete Saved Quotes', WATQ); ?>" />
            </form>
            <div class="product_details">
                <h2><?php echo __("My Quotes", WATQ); ?></h2>
                <div class="_product_detail">
                    <div class="_table_div_wrapper">
                        <div class="_table_heading_wrapper">
                            <div class="_tab_col_heading image"><?php echo __('Image', WATQ ); ?></div>
                            <div class="_tab_col_heading title"><?php echo __('Title', WATQ ); ?></div>
                            <div class="_tab_col_heading price"><?php echo __('Price', WATQ ); ?></div>
                            <div class="_tab_col_heading quantity"><?php echo __('Quantity', WATQ ); ?></div>
                            <div class="_tab_col_heading total_price"><?php echo __('Total Price', WATQ ); ?></div>
                        </div>
                        <div class="_table_body_wrapper">
                        <?php
                        foreach($user_last as $user_meta_key=>$user_meta) {
                            ?>
                            <div class="_table_content_wrapper">
                                <div class="_table_accordian_tab" data-tab="<?php echo strtolower(str_replace(' ','',$user_meta_key)); ?>">
                                    <div class="_info_wrapper">
                                        <?php
                                        echo "<span class='_quote_info'>".$user_meta_key."</span>";
                                        echo "<span class='_quote_info'>".__('Quote Time : ', WATQ). $user_meta['quote_general_data']['time']."</span>";
                                        echo "<span class='_quote_info'>".__('Quote Date : ', WATQ). $user_meta['quote_general_data']['date']."</span>";
                                        echo "<span class='_quote_info'>".__('Sent To : ', WATQ). $user_meta['quote_general_data']['sent_to']."</span>";
                                        ?>
                                    </div>
                                    <div class="_tab_menu_option">
                                        <div class="_first_line"></div>
                                        <div class="_second_line"></div>
                                        <div class="_third_line"></div>
                                    </div>
                                </div>
                                <div class="_tab_accordian_panel" data-panel="<?php echo strtolower(str_replace(' ','',$user_meta_key)); ?>">
                            <?php
                            $whole_quote_sub_total = null;
                            foreach($user_meta as $u_meta_key=>$u_meta) {
                                if($u_meta_key == "quote_data") {
                                    foreach($u_meta as $meta_data) {
                                        ?>
                                        <div class="_tab_panel_items">
                                            <div class="_tab_col image">
                                                <a href="<?php the_permalink($meta_data['product_id']); ?>">
                                                    <img src="<?php echo $meta_data['product_image']; ?>" alt="">
                                                </a>
                                            </div>
                                            <div class="_tab_col title">
                                                <a href="<?php the_permalink($meta_data['product_id']); ?>">
                                                    <?php echo $meta_data['product_title']; ?>
                                                </a>
                                                <?php
                                                if ($meta_data['product_type'] == "variation") {
                                                    echo $meta_data['product_variation'];
                                                }
                                                ?>
                                            </div>
                                            <div class="_tab_col price">
                                                <?php echo wc_price($meta_data['product_price']); ?>
                                            </div>
                                            <div class="_tab_col quantity">
                                                <?php echo $meta_data['product_quantity']; ?>
                                            </div>
                                            <div class="_tab_col total_price">
                                                <?php echo $meta_data['sub_total']; ?>
                                            </div>
                                            <?php $whole_quote_sub_total = $meta_data['quote_total']; ?>
                                        <input type="hidden" name="product_id" class="product_id" value="<?php echo $meta_data['product_id']; ?>">
                                        <input type="hidden" name="product_type" class="product_type" value="<?php echo $meta_data['product_type']; ?>">
                                        <input type="hidden" name="variation_id" class="variation_id" value="<?php echo $meta_data['variation_id']; ?>">
                                        </div>
                                        <?php
                                    }
                                }
                            }
                            ?>
                                    <div class="items_sub_total">
                                        <div class="_tab_col image"></div>
                                        <div class="_tab_col title"></div>
                                        <div class="_tab_col price"></div>
                                        <div class="_tab_col quantity">
                                            <?php echo "<strong>".__('Sub Total', WATQ)."</strong>"; ?>
                                        </div>
                                        <div class="_tab_col total_price">
                                            <?php echo "<strong>".wc_price($whole_quote_sub_total)."</strong>"; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    else {
        echo "<h2>". __('My Quotes', WATQ). "</h2>";
        echo "<p>". __('No Quotes Found', WATQ). "</p>";
    }
}

function product_exists_in_meta($unique_id, $current_products) {
    $response = array('status'=>false);
    if(is_array($current_products)) {
        foreach($current_products as $current_product) {
            if(in_array($unique_id,$current_product)){
                $response['status'] = true;
                $response['quantity'] = $current_product['product_quantity'];
                $response['variation_id'] = $unique_id;
            }
        }
    }
    return $response;
}

function product_exists_in_list($unique_id, $current_products) {
    $response = false;
    if(is_array($current_products)) {
        foreach($current_products as $current_product) {
            if(in_array($unique_id,$current_product)){
                $response = true;
            }
        }
    }
    return $response;
}


add_action('init', 'watq__send_email');
function watq__send_email() {
    if($_POST) {
        if(array_key_exists('action', $_POST) && !empty($_POST['action']) && $_POST['action'] == "send_quote") {
            
            $submit_data = array_map('watq_validate_array', $_POST['data']);
            
            //print_r($_POST['data']); die;
            
            $validate_email = explode(',', $_POST['_to_send_email']);
            $sanitize_email = array();
            $validation_result = true;
            foreach($validate_email as $vali_email) {

                $validated_email = sanitize_email($vali_email);
                $validated = (!empty($validated_email) ? true : false);
                if($validated) {
                    $sanitize_email[] = $validated_email;
                }
                else {
                    $validation_result = false;
                    break;
                }
            }

            $validated_all_emails = implode(",", $sanitize_email);

            if($validation_result) {
                $to_send = str_replace(' ', '', $validated_all_emails);

                $attachments = array();
                $before_quote = get_option('wc_settings_quote_email_before_message');
                if (!empty($before_quote)) {
                    $message = $before_quote;
                } else {
                    $message = '';
                }
                $message .= '<div style="width:90%;margin:0 auto;border: 1px solid #e5e5e5;">';
                $message .= '<table style="width: 100%;border-collapse: collapse;">';
                $message .= '<thead>';
                $message .= '<tr style="border-bottom: 1px solid #e5e5e5;">';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Image', WATQ);
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Title', WATQ);
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Price', WATQ);
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Quantity', WATQ);
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;padding:10px;">';
                $message .= __('Total', WATQ);
                $message .= '</th>';
                $message .= '</tr>';
                $message .= '</thead>';
                $message .= '<tbody>';
                $quote_post = array();
                $gett = null;
                
                $total_sum = 0;
                
                foreach ($submit_data as $sub_data) {
				// echo "<pre>";
				//print_r($submit_data);die;
				$quote_post[] = array('product_id' => $sub_data['product_id'], 'product_image' => $sub_data['product_image'], 'product_title' => $sub_data['product_title'], 'product_price' => $sub_data['product_price'], 'product_quantity' => $sub_data['product_quantity'], 'product_type' => $sub_data['product_type'], 'variation_id' => $sub_data['variation_id'], 'sub_total' => $sub_data['sub_total'], 'quote_total' => $_POST['quote_total'], 'length_mm' => $_POST['length_mm'], 'total_price' => $_POST['total_price']);
                 
                 
                 $total_price = isset($sub_data['total_price']) ? $sub_data['total_price'] : 0;
                 $quantity = isset($sub_data['product_quantity']) ? $sub_data['product_quantity'] : 0;
                 $calculated_total = $total_price * $quantity;
                 
                 $total_sum += $calculated_total;
                 
                    $message .= '<tr style="border-bottom: 1px solid #e5e5e5;">';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                    $message .= '<a href="' . $sub_data['product_image'] . '" ><img src="' . $sub_data['product_image'] . '" width="100" /></a>';
                    $message .= '</td>';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                    $message .= $sub_data['product_title'];
                    
                    if (isset($sub_data['length_mm']) && !empty($sub_data['length_mm'])) {
                        $message .= ' - ' . $sub_data['length_mm'] . 'mm';
                    }
                           $product = wc_get_product ( $sub_data['product_id'] );

                    if ( $product->is_type( 'variable' ) ){

                        $variation = wc_get_product($sub_data['variation_id']);
                        $varation_name = $variation->get_variation_attributes();
                        foreach ($varation_name as $key => $value) {
                          '<br><b>' . str_replace('attribute_pa_', '', $key) . ':</b> ' . $value . '<br>';

                        }
                    }

                    $message .= '</td>';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                    if (isset($sub_data['length_mm']) && !empty($sub_data['length_mm'])) {
                        $message .= wc_price($sub_data['total_price']);
                        //$message .= wc_price($sub_data['product_price']);
                    }
                    else{
                    $message .= wc_price($sub_data['product_price']);
                    }
                    $message .= '</td>';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                    $message .= $sub_data['product_quantity'];
                    $message .= '</td>';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;">';
                    if (isset($sub_data['length_mm']) && !empty($sub_data['length_mm'])) {
                        $message .= wc_price($calculated_total);
                        //$message .= $sub_data['sub_total'];
                    }
                    else{
                       $message .= wc_price($calculated_total); 
                       //$message .= $sub_data['sub_total'];
                    }
                    $message .= '</td>';
                    $message .= '</tr>';
                    $product_id = $sub_data['product_id'];
                    $product = wc_get_product($product_id);
                    $quantity = (int)$sub_data['product_quantity'];

                    $sale_price = get_post_meta($sub_data['variation_id'], '_regular_price', true);
                    $gett += $sale_price*$quantity;


                }
                $message .= '</tbody>';
                $message .= '<tfoot>';
                $message .= '<tr>';
                $message .= '<td></td>';
                $message .= '<td></td>';
                $message .= '<td></td>';
                $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-left:1px solid #e5e5e5;">' . __('Sub Total', WATQ) . '</td>';
                //$message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-left:1px solid #e5e5e5;">'. wc_price($gett). '</td>';
                $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-left:1px solid #e5e5e5;">'. wc_price($total_sum). '</td>';
                $message .= '</tr>';
                $message .= '</tfoot>';
                $message .= '</table>';
                $message .= '</div>';
                $after_quote = get_option('wc_settings_quote_email_after_message');
                if (!empty($after_quote)) {
                    $message .= $after_quote;
                }

                $admin_email = null;
                $quote_admin_email = get_option('wc_settings_quote_admin_email');
                if ($quote_admin_email != '') {
                    $admin_email = $quote_admin_email;
                } else {
                    $admin_email = get_option('admin_email');
                }

                $current_user_id = '';
                if (is_user_logged_in()) {
                    $current_user_id = get_current_user_id();
                }
                $quotes_send_to = $to_send;
				$srit_Zdfa =   watq_save_quote_post_meta($quote_post, $current_user_id, $quotes_send_to);
                $site_title = get_bloginfo('name');

                $admin_email = get_option('admin_email');
                $headers = array('Content-Type: text/html; charset=UTF-8','From: '.$site_title.' <'.$admin_email.'>' );

                $quote_email_title = get_option('wc_settings_quote_email_subject');
                $email_title = (!empty($quote_email_title) ? $quote_email_title : __('Quote', WATQ));


                if (wp_mail($to_send, $email_title, $message, $headers, $attachments)) {

                    $remove_quote_after_email = (boolean)get_option('wc_settings_empty_quote_after_email');
                    if ($remove_quote_after_email) {
                        setcookie('_quotes_elem', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false);
                    }
                    $message .= '<p>' . __('Quote has been sent to', WATQ) . ' ' . str_replace(',', ', ', $to_send) . '</p>';
                    wp_mail($admin_email, __('Quote Enquiry', WATQ), $message, $headers, $attachments);

                    $success_message = null;
                    $quote_success_email = get_option('wc_settings_quote_success_email');
                    if ($quote_success_email != '') {
                        $success_message = $quote_success_email;
                    } else {
                        $success_message = __('Check Your Mail', WATQ);
                    }

                    $_woo_message = array('status' => 'success', 'message' => $success_message);
                    setcookie('_woo_message', json_encode($_woo_message), time() + 3600, COOKIEPATH, COOKIE_DOMAIN, false);
					$message = $success_message;
                    wc_add_notice( $message, $notice_type = 'success' );
					$Message = urlencode($success_message);
					header("Location:".site_url('/quote/?'."msg=".$Message."&type=success"));
                    // echo '<META HTTP-EQUIV="refresh" content="0;URL=' . get_permalink(get_the_ID()) . '">';
                    exit();
                } else {
                    $error_message = null;
                    $quote_error_email = get_option('wc_settings_quote_error_email');
                    if ($quote_error_email != '') {
                        $error_message = $quote_error_email;
                    } else {
                        $error_message = __('Try Again', WATQ);
                    }
                    $_woo_message = array('status' => 'error', 'message' => $error_message);
                    setcookie('_woo_message', json_encode($_woo_message), time() + 3600, COOKIEPATH, COOKIE_DOMAIN, false);
					$message = $error_message;
                    wc_add_notice( $message, $notice_type = 'error' );
					$Message = urlencode($error_message);
					header("Location:".site_url('/quote/?'."msg=".$Message."&type=error"));
                    // echo '<META HTTP-EQUIV="refresh" content="0;URL=' . get_permalink(get_the_ID()) . '">';
                    exit();
                }
            }
            else {
                $error_message = null;
                $quote_error_user_email = get_option('wc_settings_error_email_user_input');
                if ($quote_error_user_email != '') {
                    $error_message = $quote_error_user_email;
                } else {
                    $error_message = __('Try Again', WATQ);
                } 
                $_woo_message = array('status' => 'error', 'message' => $error_message);
                setcookie('_woo_message', json_encode($_woo_message), time() + 3600, COOKIEPATH, COOKIE_DOMAIN, false);

                echo '<META HTTP-EQUIV="refresh" content="0;URL=' . get_permalink(get_the_ID()) . '">';
                exit();
            }
        }
    }
}

add_action('init', 'watq_get_woo_message',20);
function watq_get_woo_message(){

    $_woo_message_cookie = isset($_COOKIE['_woo_message']) ? $_COOKIE['_woo_message'] : '';
    if(!empty($_woo_message_cookie)) {
        $_woo_cookie_data = json_decode(stripslashes($_woo_message_cookie), true);

        $message = "<div class='_quote_message_'>".$_woo_cookie_data['message']."</div>";

        $notices[$_woo_cookie_data['status']][] = apply_filters( 'woocommerce_add_' . $_woo_cookie_data['status'], $message );

        WC()->session->set( 'wc_notices', $notices );

        setcookie('_woo_message', '', time()-3600, COOKIEPATH, COOKIE_DOMAIN, false);
    }
}

function watq_get_product_variations($variation_array, $html=false) {

    $available_variations = $variation_array;

    $result = null;
    if($html) {
        $result = '<dl class="variation _quote_variations">';
        if (is_array($available_variations) || is_object($available_variations)) {
            foreach ( $available_variations as $av_key=>$av_value){
                $to_replace = array('attribute_pa_', ':');
                $with_replace = array('', '');
                $result .= '<dt class="variation-' . str_replace('attribute_pa_', '', $av_key) . '">' . ucfirst(str_replace($to_replace, $with_replace, $av_key)) . ' </dt>';
                $result .= '<dd class="variation-' . str_replace('attribute_pa_', '', $av_key) . '"> <p>' . $av_value . '</p></dd>';
            }
        }
        $result .= '</dl>';
    }
    else {
        $result = array();
        if (is_array($available_variations) || is_object($available_variations)) {
            foreach ($available_variations as $av_key => $av_value) {
                $result[$av_value[0]] = $av_value[1];
            }
        }
    }
     return $result;
}

function watq_get_product_price($product_id,$variation_id, $product_type='simple') {
// print_r($variation_id);die;
    $price = array();
    $temp = null;
    if($product_type == 'simple') {
        $current_product_ = wc_get_product($variation_id);
        $temp = $current_product_->get_price_html();
    }
    else {
		if($product_id != $variation_id){
			// die("hello");
			$current_product = new WC_Product_Variation( $variation_id );
			$temp = $current_product->get_price_html();
		}else{
			// die("sdsds");
			$current_product_ = wc_get_product($variation_id);
			$temp = $current_product_->get_price_html();
		}
        
    }

    $currency = get_woocommerce_currency_symbol();
    $price_with_currency = strrchr($temp,$currency);
    $price_num = str_replace($currency, '', $price_with_currency);

    $price['formated_price'] = $price_with_currency;
    $price['price'] = str_replace(',','',$price_num);

    return $price;
}

add_action('wp_footer', '_quote_compatible_notices');
function _quote_compatible_notices() {
    if(is_page('quote')) { ?>
    <script>
        jQuery(document).ready(
            function($) {
                $('body').addClass('woocommerce');
            }
        );
    </script>
    <?php }
}

add_action('init', 'watq_empty_quote_table');
function watq_empty_quote_table() {
    if($_POST) {
        if(array_key_exists('action', $_POST) && $_POST['action'] == '_clear_quotes') {
            setcookie('_quotes_elem', '', time()-3600, COOKIEPATH, COOKIE_DOMAIN, false);
            echo '<META HTTP-EQUIV="refresh" content="0;URL='.get_permalink(get_the_ID()).'">';
            exit();
        }
        elseif(array_key_exists('action', $_POST) && $_POST['action'] == 'clear_saved_quotes') {
            delete_user_meta(get_current_user_id(), 'watq_quote_whishlist');
            echo '<META HTTP-EQUIV="refresh" content="0;URL='.get_permalink(get_the_ID()).'">';
            exit();
        }
    }
}

add_action('wp_footer', 'watq_hide_add_to_cart_button');
function watq_hide_add_to_cart_button() {
    if(get_option('wc_settings_add_to_cart_on_detail_page') == "false") {
    ?>
    <style type="text/css">
        .single-product .single_add_to_cart_button ,
        .single-product button.single_add_to_cart_button ,
        .single-product a.single_add_to_cart_button {
            display:none !important;
        }
    </style>
    <?php
    }
    if(get_option('wc_settings_add_to_cart_global') == "false") {
    ?>
    <style type="text/css">
        .add_to_cart_button ,
        button.add_to_cart_button ,
        a.add_to_cart_button {
            display:none !important;
        }
    </style>
    <?php
    }
}

/**
 * custom post type for Quote admin.
 */
add_action( 'init', '_watq_added_quotes_posts',9 );
function _watq_added_quotes_posts() {
    $labels = array(
        'name'               => _x( 'Added Quotes', 'post type general name' ),
        'singular_name'      => _x( 'Added Quote', 'post type singular name' ),
        'add_new'            => _x( 'Add New', 'book' ),
        'add_new_item'       => __( 'Add New Quote', WATQ ),
        'edit_item'          => __( 'Edit Quote', WATQ ),
        'new_item'           => __( 'New Quote', WATQ ),
        'all_items'          => __( 'Added Quotes', WATQ ),
        'view_item'          => __( 'View Quote', WATQ ),
        'search_items'       => __( 'Search Quote', WATQ ),
        'not_found'          => __( 'No Quote found', WATQ ),
        'not_found_in_trash' => __( 'No Quote found in the Trash', WATQ ),
        'parent_item_colon'  => '',
        'menu_name'          => 'Quotes'
    );

    $args = array(

        'labels'        => $labels,
        'description'   => 'Added Quotes',
        'public'        => false,
        'show_in_menu'  => 'woocommerce',
        'show_ui'       => true,
        'capabilities' => array(
            'create_posts' => (is_multisite() ? 'do_not_allow' : false),
        ),
        'map_meta_cap' => true,
        'supports'      => array( '' ),
        'has_archive'   => true,
    );
    register_post_type( 'watq-quotes', $args );
}

/**
 * custom post type for Quote admin.
 */
function watq_save_quote_post_meta($quote_data, $user_id, $to_send) {
    $quote_number = randomPassword();

    $quote_post = array();
    $quote_post['sent_to'] = str_replace(',', ',<br /> ', $to_send);

    $quote_post['user_id'] = $user_id;
    $quote_post['quote_data'] = $quote_data;
    $post_id = create_post_to_adds('Quote #'.$quote_number);

    $quote_post['quote_general_data'] = array('time' => get_the_time('',$post_id), 'date' => date('d-M-Y'));
    if(!empty($user_id)) {
        $saved_wishlist = (array) get_user_meta( $user_id, 'watq_quote_whishlist', true);

        $saved_wishlist_info = array('quote_general_data' => array('time' => get_the_time('',$post_id), 'date' => date('d-M-Y'), 'sent_to' => str_replace(',', ', ', $to_send)), 'quote_data' => $quote_data);
        $array_index = 'Quote #'.$quote_number;

        $saved_wishlist[$array_index] = $saved_wishlist_info;
        update_user_meta($user_id, 'watq_quote_whishlist', $saved_wishlist);
    }

    update_post_meta($post_id,'quote_post_data',$quote_post);
}
add_action('wp_ajax_quote_remove', 'watq_quote_ajax_callback');
add_action('wp_ajax_nopriv_quote_remove', 'watq_quote_ajax_callback');

function watq_quote_ajax_callback() {
    if (!isset($_POST['product_id'])) {
        wp_send_json_error("Missing product ID.");
        wp_die();
    }

    $product_id = sanitize_text_field($_POST['product_id']);
    $length_mm = isset($_POST['length_mm']) ? sanitize_text_field($_POST['length_mm']) : '';

    $cookie_data = isset($_COOKIE['_quotes_elem']) ? stripslashes($_COOKIE['_quotes_elem']) : '';
    $updated_quote = [];

    if (!empty($cookie_data)) {
        $exists_quote = json_decode($cookie_data, true);

        if (is_array($exists_quote)) {
            foreach ($exists_quote as $quote_item) {
                // Keep items that do not match the one being removed
                if ($quote_item['product_id'] != $product_id || ($length_mm && $quote_item['length_mm'] != $length_mm)) {
                    $updated_quote[] = $quote_item;
                }
            }
        }

        // Update or delete the cookie
        $expire = !empty($updated_quote) ? time() + 3600 * 24 * 100 : time() - 3600;
        setcookie('_quotes_elem', json_encode($updated_quote), $expire, COOKIEPATH, COOKIE_DOMAIN, false);

        wp_send_json_success("Item removed.");
    } else {
        wp_send_json_error("No items found in the quote.");
    }

    wp_die();
}

function watq_from_cart_to_quote() {
    $cart = WC()->cart->get_cart();
    $quote_items = [];

    foreach ($cart as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();

        $quote_items[] = [
            'product_id'        => $product_id,
            'product_image'     => wp_get_attachment_url($product->get_image_id()),
            'product_title'     => $product->get_name(),
            'product_quantity'  => $cart_item['quantity'],
            'product_type'      => $product->get_type(),
            'length_mm'         => get_post_meta($product_id, 'length_mm', true),
            'product_variation_id' => $cart_item['variation_id'] ?? null,
            'product_variations' => $cart_item['variation'] ?? [],
        ];
    }

    return $quote_items;
}
// Add "Build a Quote" button after checkout
add_action('woocommerce_proceed_to_checkout', 'watq_add_quote_button', 20);

function watq_add_quote_button() {
    if (get_option('wc_settings_quote_on_cart_select') !== "true") {
        return; // Exit if setting is disabled
    }

    echo '<div class="quote-request-wrapper" style="margin-top:10px;">
        <form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">
            ' . wp_nonce_field('cart_to_quote_nonce', '_cart_to_quote_nonce', true, false) . '
            <input type="hidden" name="action" value="from_cart_to_quote" />
            <button type="submit" name="_cart_to_quote" class="button cart_to_quote_submit">
                ' . esc_html__('Build a Quote', 'WATQ') . '
            </button>
        </form>
    </div>';
}

function handle_quote_submission() {
    check_ajax_referer('cart_to_quote_nonce', 'security');

    session_start();

    if (isset($_POST['product']) && is_array($_POST['product'])) {
        $quote_data = array_map('sanitize_text_field', $_POST['product']);
        $expire = time() + 3600 * 24 * 100; // 100 days

        setcookie('_quotes_elem', json_encode($quote_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $expire, COOKIEPATH, COOKIE_DOMAIN, false, true);

        alert('✅ Cookie should be set: ' . json_encode($quote_data));
    } else {
        alert('❌ No product data received.');
    }

    wp_send_json_success([
        'message' => 'Quote created successfully!',
        'redirect_url' => site_url('/quote')
    ]);
}

add_action('wp_ajax_handle_quote_submission', 'handle_quote_submission');
add_action('wp_ajax_nopriv_handle_quote_submission', 'handle_quote_submission'); // Allow non-logged-in users

add_action('admin_post_from_cart_to_quote', 'watq_process_cart_to_quote');
add_action('admin_post_nopriv_from_cart_to_quote', 'watq_process_cart_to_quote'); // Allow non-logged-in users

function watq_process_cart_to_quote() {
    // Verify the nonce
    if (!isset($_POST['_cart_to_quote_nonce']) || !wp_verify_nonce($_POST['_cart_to_quote_nonce'], 'cart_to_quote_nonce')) {
        wp_die(__('Security check failed', 'WATQ'));
    }

    // Process the cart and save the quote to a cookie
    if (isset($_POST['product']) && is_array($_POST['product'])) {
        $quote_data = array_map('sanitize_text_field', $_POST['product']);
        $expire = time() + 3600 * 24 * 100; // 100 days
    
        setcookie('_quotes_elem', json_encode($quote_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $expire, COOKIEPATH, COOKIE_DOMAIN, false, true);
    
        error_log('✅ Cookie set: ' . json_encode($quote_data));
    }
    

    // Redirect to the quote summary page
    wp_redirect(site_url('/quote'));
    exit;
}

