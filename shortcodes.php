<?php
// ini_set("display_errors",true);
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function watq_get_quote($atts) {
	
    extract(shortcode_atts(array(
        'quote_elem' => isset($_COOKIE['_quotes_elem']) ? $_COOKIE['_quotes_elem'] : '',
    ), $atts));
    if(!isset($quote_elem)) {
        $quote_elem = '';
    }

    ?>
    <div class="woocommerce quote">
    <?php
    if(isset($_GET['msg']) && isset($_GET['type'])){
		$class="error";
		if($_GET['type'] == "success"){
			$class="success";
		}
		echo "<div class='msg ".$class."'>".$_GET['msg']."</div>";
	}
    if(!empty($quote_elem)) {
        if(count(json_decode(stripslashes($quote_elem))) > 0) {
            ?>
            <div class="quote_data_wrapper">
                <form method="post" id="watq_send_quote_form_wrapper" action="<?php echo get_the_permalink(); ?>">
                    <table class="shop_table cart" cellpadding="0">
                        
                        <thead>
                            <tr>
                                <th class="product-remove"><?php echo __('remove', WATQ); ?></th>
                                <th class="product-thumbnail"><?php echo __('Image', WATQ); ?></th>
                                <th class="product-name"><?php echo __('Product', WATQ); ?></th>
                                <th class="product-price"><?php echo __('Price', WATQ); ?></th>
                                <th class="product-quantity"><?php echo __('Quantity', WATQ); ?></th>
                                <th class="product-subtotal"><?php echo __('Total', WATQ); ?></th>
                            </tr>
                        </thead>
                       <tbody>
					   <?php
                            $cookie_data = json_decode(stripslashes($quote_elem), true);
							
                            if(is_array($cookie_data)) {
                                global $woocommerce;
                                $gett = null;
                                $whole_quote_sub_total = null;
								// echo "<pre>";
								//print_r($cookie_data);die;
								//$index = 0;
                                $subtotal = 0;
                                foreach($cookie_data as $data) { 
									// echo "<pre>";
									//print_r($data);die;
								$product_obj = '';
								if($data['product_type'] == 'simple') {
									$product_obj = wc_get_product($data['product_id']);
								}
								elseif($data['product_type'] == "variation") {
									$product_variations = new WC_Product_Variable( $data['product_id'] );
									$product_obj = $product_variations->get_available_variations();
									if(empty((array)$product_obj)){
										$product_obj = wc_get_product($data['product_id']);
									}
								}
								$price_currency = watq_get_product_price($data['product_id'],$data['product_variation_id'], $data['product_type']);
								$id = 'product_id';
								$image = 'product_image';
								$title = 'product_title';
								$price2 = 'product_price';
								$quantity_id = 'product_quantity';
								$type = 'product_type';
								$variation_id = 'variation_id';
								$total_price = 'sub_total';
								$product_variation = 'product_variation';
								$length_mm = 'length_mm';
								
							?>
							<tr>
							     
								<td class="product-remove" data-delete-id="" id="product_<?php echo $data['product_id']; ?>" 
								<?php if (isset($data['length_mm'])): ?> data-length="<?php echo esc_attr($data['length_mm']); ?>"<?php endif; ?>
								><span>X<span></td>
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $id; ?>]" class="" value="<?php echo $data['product_id']; ?>" />
                                <td class="product-image"><a href="<?php echo get_permalink($data['product_id']); ?>" ><img src="<?php echo $data['product_image']; ?>" /></a></td>
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $image; ?>]" class="" value="<?php echo $data['product_image']; ?>" />
								
                                <td class="product-title"><a href="<?php echo get_permalink($data['product_id']); ?>" ><?php echo $data['product_title']; ?> <?php echo isset($data['length_mm']) ? '<span class="cust_length_mm">-' .'<span>'. $data['length_mm'] .'</span>'. 'mm</span>' : ''; ?></a> <br>
								
								<?php
								$product = wc_get_product ( $data['product_id'] );
								
								if ( $product->is_type( 'variable' ) ){
									$variation = wc_get_product($data['product_variation_id']);
									
									$varation_name = $variation->get_variation_attributes();
									//print_r($varation_name);
									if(!empty($varation_name)){
										foreach ($varation_name as $key => $value) { 
										   echo '<b>' . str_replace('attribute_pa_', '', $key) . ':</b> ' . $value . '<br>';
										}
									}
								}
								?>
								</td>
                                <?php
                                    $price = get_post_meta($data['product_variation_id'], '_regular_price', true);
                                    $length_mm = isset($data['length_mm']) ? floatval($data['length_mm']) : 1;
                                    if ($length_mm <= 0) {
                                        $length_mm = 1; 
                                    }
                                    $total_price = $price * $length_mm;
                                    $total_price = floor($total_price * 100) / 100;
                                    $total_price = number_format($total_price, 2);
                                ?>
                                
                                <?php
                                    // if (isset($data['length_mm']) && floatval($data['length_mm']) > 0) {
                                    // $price = get_post_meta($data['product_variation_id'], '_regular_price', true);
                                    // $length_mm = floatval($data['length_mm']);
                                    // $total_price = $price * $length_mm;
                                    // }
                                ?>

								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $title; ?>]" class="" value="<?php echo $data['product_title']; ?>" />
                                <!--<td class="product-price"><?php //echo get_woocommerce_currency_symbol(); echo $price = get_post_meta($data['product_variation_id'], '_regular_price', true); ?></td>-->
                                <td class="product-price"><?php echo get_woocommerce_currency_symbol(); echo $total_price; ?></td>

								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $price2; ?>]" class="" value="<?php echo esc_html($price_currency['price']); ?>" />
								<td class="product-quantity">
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][product_quantity]" class="" value="<?php echo $data['product_quantity'] ?>" />
								<?php echo $data['product_quantity']; ?></td>
								<?php
									// $product_sub_total = $woocommerce->cart->get_product_subtotal( $product_obj, $data['product_quantity']);
									$currency = get_woocommerce_currency_symbol();
									// $price_with_currency = strrchr($product_sub_total,$currency);

									// $price_num = str_replace($currency, '', $price_with_currency);
									
                                    $total_price1 = $price * $length_mm;
                                    
                                    //$total_price1 = floor($total_price1 * 100) / 100;
                                    
									$product_id = $data['product_id'];
									$product = wc_get_product($product_id);
									$quantity = (int)$data['product_quantity'];

									$sale_price = get_post_meta($data['product_variation_id'], '_regular_price', true);
									$single_price = $sale_price*$quantity;
									$gett += $sale_price*$quantity;
								
									$total=$total_price1*$quantity; 
									$subtotal+=$total_price1*$quantity;
									$total = floor($total * 100) / 100;
									$total = number_format($total, 2);
									
								?>
								<!--<td><?php //echo $currency.$single_price;?></td>-->
								<td><?php echo $currency.$total;?></td>
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $total_price; ?>]" class="" value="<?php echo $single_price; ?>" />
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $quantity_id; ?>]" class="" value="<?php echo $data['product_quantity']; ?>" />
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $type; ?>]" class="" value="<?php echo $data['product_type']; ?>" />
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $variation_id; ?>]" class="variation_id" value="<?php echo $data['product_variation_id']; ?>" />
								
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php /*echo $length_mm;*/echo 'length_mm'; ?>]" class="" value="<?php echo $data['length_mm']; ?>" />
								
								<input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo 'total_price'; ?>]" class="" value="<?php echo $total_price; ?>" />


								<input type="hidden" name="quote_total" class="quote_total" value="<?php echo $whole_quote_sub_total; ?>" />
							</tr>
							<?php 
								}
							?>
					   </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td colspan="2" style="text-align:center;"><?php echo __('Sub Total', WATQ); ?></td>
                                    <td class="product-price">
                                        <?php echo wc_price($subtotal); ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        <div id="_send_quote_popup" style="display:none;">
                            <?php
                            if(is_user_logged_in()) {
                                ?>
                                <div class="_send_quote_form_wrapper">
                                    <label>
                                        <?php echo __('Write comma seprate email addresses.', WATQ); ?>
                                    </label>
                                    <?php
                                    $current_user = wp_get_current_user();
                                    $user_email = $current_user->user_email;
                                    ?>
                                    <input type="text" name="_to_send_email" id="_to_send_email" value="<?php echo $user_email; ?>">
                                    <button class="button" id="send_trigger" ><?php echo __('Send', WATQ); ?></button>
                                </div>
                                </div>
                                <a href="#TB_inline?width=350&height=250&inlineId=_send_quote_popup" id="_send_quote_email_" class="thickbox"><?php echo __('Send', WATQ); ?></a>
                                <?php
                            }
                            else {
                                if((boolean)get_option( 'wc_settings_allow_guest_user' )) {
                                    ?>
                                    <div class="_send_quote_form_wrapper">
                                        <label>
                                            <?php echo __('Write comma seprate email addresses.', WATQ); ?>
                                        </label>

                                        <input type="text" name="_to_send_email" id="_to_send_email" value="">
                                        <button class="button" id="send_trigger" ><?php echo __('Send', WATQ); ?></button>
                                    </div>
                                </div>
                                <a href="#TB_inline?width=350&height=250&inlineId=_send_quote_popup" id="_send_quote_email_" class="thickbox"><?php echo __('Send', WATQ); ?></a>
                                    <?php
                                }
                                else {
                                    ?>
                                    </div>
                                    <a href="<?php echo get_permalink(get_page_by_path('my-account')).'?rq=login'; ?>" id="_send_quote_email_"><?php echo __('Send', WATQ); ?></a>
                                    <?php
                                }
                            }
                            ?>
                        <input type="hidden" name="_to_send_email" class="_to_send_email" value="" />
                        <input type="hidden" name="action" value="send_quote" />
                        <input type="submit" value="email quote" class="_submit" />
                    </form>
                </div>

                <div class="_quoteall_buttons_wrapper">
                <?php
                if(get_option( 'wc_settings_quote_to_cart_select' ) == "true") { 
                    ?>
                        <form method="post" id="_add_quote_to_cart" action="<?php echo get_the_permalink(); ?>">
                            <?php
                            foreach($cookie_data as $index =>$data_c) {
                            $id = 'product_id';
                            $quantity = 'product_quantity';
                            $type = 'product_type';
                            $variation_id = 'variation_id';
                            $variation_attr = 'variation_attr';
                            $length_mm = 'length_mm';
                            
                            $index1= 'index';
                            ?>
                                    
                            <!--<input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $id; ?>]" class="" value="<?php echo $data_c['product_id']; ?>" />-->
                            <!--<input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $quantity; ?>]" class="" value="<?php echo $data_c['product_quantity']; ?>" />-->
                            <!--<input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $type; ?>]" class="" value="<?php echo $data_c['product_type']; ?>" />-->
                            <!--<input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $variation_id; ?>]" class="" value="<?php echo $data_c['product_variation_id']; ?>" />-->
                            <!--<input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $variation_attr; ?>]" class="" value="<?php echo $data_c['variations_attr'] != "" ? esc_html(json_encode($data_c['variations_attr'])) : "" ?>" />-->
                            
                            <!--<input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $length_mm; ?>]" class="" value="<?php echo $data_c['length_mm']; ?>" />-->
                            
                            
                            <input type="hidden" name="data[<?php echo $index; ?>][<?php echo $id; ?>]" class="" value="<?php echo $data_c['product_id']; ?>" />
                            <input type="hidden" name="data[<?php echo $index; ?>][<?php echo $quantity; ?>]" class="" value="<?php echo $data_c['product_quantity']; ?>" />
                            <input type="hidden" name="data[<?php echo $index; ?>][<?php echo $type; ?>]" class="" value="<?php echo $data_c['product_type']; ?>" />
                            <input type="hidden" name="data[<?php echo $index; ?>][<?php echo $variation_id; ?>]" class="" value="<?php echo $data_c['product_variation_id']; ?>" />
                            <input type="hidden" name="data[<?php echo $index; ?>][<?php echo $variation_attr; ?>]" class="" value="<?php echo $data_c['variations_attr'] != "" ? esc_html(json_encode($data_c['variations_attr'])) : "" ?>" />
                            
                            <input type="hidden" name="data[<?php echo $index; ?>][<?php echo $length_mm; ?>]" class="" value="<?php echo $data_c['length_mm']; ?>" />
                            
                            <?php 
                            } 
                            ?>
                            <input type="hidden" name="action" value="add_to_cart_q">
                            <input type="submit" value="<?php echo __('Add to Cart', WATQ); ?>" class="_submit button" />
                        </form>
                    <?php 
                }
                ?>
                    <form method="post" id="clear_quotes" action="<?php echo get_the_permalink(); ?>">
                        <input type="hidden" name="action" value="_clear_quotes" />
                        <input type="submit" value="<?php echo __('Empty Quote', WATQ); ?>" class="_submit button" />
                    </form>
                    <button id="_email_quote_trigger" class="button"><?php echo __('Email', WATQ); ?></button>
                    
                </div>
                <?php
                
            }
        }
    }
    else {
        ?>
        <p><?php echo __('Your Current Quote is empty', WATQ); ?></P>
        <a href="<?php echo get_permalink(get_page_by_path('shop')); ?>" class="return_shop_quote" style="margin-top: 5px;"><?php echo __('Return To Shop', WATQ); ?></a>
        <?php
    }
    ?>
    </div>
    <?php
}
add_shortcode('_quote', 'watq_get_quote');