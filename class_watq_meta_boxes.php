<?php
/**
 * Metaboxes for admin.
 */
Class WATQ_MetaBoxes {

    /**
     *  Constructor
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'watq_register_metaboxes' ), 10 );
        add_action( 'add_meta_boxes', array( $this, 'watq_get_metabox_value' ), 10 );
        add_action( 'admin_enqueue_scripts', array( $this, 'watq_update_unread_quotes' ), 10 );
    }

    /**
     *  It will register metaboxes
     */
    public function watq_register_metaboxes() {

        add_meta_box( 'watq_quote_data', __( "Quote Products", WATQ ), array( $this, 'watq_quote_product_return' ), 'watq-quotes', 'normal', 'high' );
        add_meta_box( 'watq_quote_info', __( "Quote Detail", WATQ ), array( $this, 'watq_quote_info_return' ), 'watq-quotes', 'side', 'high' );
    }

    /**
     *  It will output info metaboxes
     */
    public function watq_quote_info_return() {
        wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field('watq_quote_info', 'meta-box-info-nonce', false );
        global $post;
        ?>
        <div class="" id="waqt_quote_detail">
            <div class="quote_details">
                <h2><?php echo __(get_the_title(), WATQ)." ". __("Details", WATQ); ?> </h2>
                <div class="_general_detail">
                    <?php
                        $post_meta = get_post_meta( get_the_ID(), 'quote_post_data' );

                        $quote_time = $post_meta[0]['quote_general_data']['time'];
                        $quote_date = $post_meta[0]['quote_general_data']['date'];
                        echo "<ul>";
                        echo "<li><strong>".__('Quote Time: ',WATQ)."</strong>".$quote_time."</li>";
                        echo "<li><strong>".__('Quote Date: ',WATQ)."</strong>".$quote_date."</li>";
                        echo "<li><strong>".__('Status: ',WATQ)."</strong>".$post->post_content."</li>";
                        echo "</ul>";
                    ?>
                </div>
                <h2><?php echo __("User Detail", WATQ); ?></h2>
                <div class="_user_detail">
                    <?php
                        $post_meta = get_post_meta( get_the_ID(), 'quote_post_data' );
                        if(!empty($post_meta[0]['user_id'])) {
                        $user_detail = get_userdata($post_meta[0]['user_id']);
                        $display_name = $user_detail->display_name;
                        $email = $user_detail->user_email;
                        }
                        echo "<ul>";
                        if(isset($display_name) && isset($email)) {
                            echo "<li><strong>".__('User Name: ',WATQ)."</strong>".$display_name."</li>";
                            echo "<li><strong>".__('User Email: ',WATQ)."</strong>".$email."</li>";
                            echo "<li><strong>".__('User Status: ',WATQ)."</strong>".__('Registered', WATQ)."</li>";
                        }
                        else {
                            echo "<li><strong>".__('User Status: ',WATQ)."</strong>".__('Guest', WATQ)."</li>";
                        }
                        echo "</ul>";
                    ?>
                </div>
                <h2><?php echo __("Sent To:", WATQ); ?></h2>
                <div class="_user_detail">
                    <?php
                        $post_meta = get_post_meta( get_the_ID(), 'quote_post_data' );
                        $sent_to = $post_meta[0]['sent_to'];
                        echo "<ul>";
                        echo "<li>".$sent_to."</li>";
                        echo "</ul>";
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     *  It will output Product metaboxes
     */
    public function watq_quote_product_return() {
        wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field('watq_quote_product', 'meta-box-product-nonce', false );

        ?>
        <div class="" id="waqt_quote_detail">
            <div class="product_details">
                <h2><?php echo __("Quote Items", WATQ); ?></h2>
                <div class="_product_detail">
                    <?php
                    $whole_quote_sub_total = null;
                    ?>
                    <table>
                        <thead>
                        <tr>
                            <th><?php echo __('Image', WATQ ); ?></th>
                            <th><?php echo __('Title', WATQ ); ?></th>
                            <th><?php echo __('Price', WATQ ); ?></th>
                            <th><?php echo __('Quantity', WATQ ); ?></th>
                            <th><?php echo __('Total Price', WATQ ); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $post_meta = get_post_meta( get_the_ID(), 'quote_post_data' );
                        $product_details = $post_meta;
                        $gett = null;

                        foreach($product_details[0]['quote_data'] as $product_detail) {

                            ?>
                            <tr>
                                <td class="product_image">
                                    <a href="<?php the_permalink($product_detail['product_id']); ?>">
                                        <img src="<?php echo $product_detail['product_image']; ?>" alt="">
                                    </a>
                                </td>
                                <td class="product_title">
                                    <a href="<?php the_permalink($product_detail['product_id']); ?>">
                                        <?php echo $product_detail['product_title']; ?>
                                    </a> <br>
                                    <?php
                                    $product = wc_get_product ( $product_detail['product_id'] );

                                  if ( $product->is_type( 'variable' ) ){
                                            $variation = wc_get_product($product_detail['variation_id']);
                                           $varation_name = $variation->get_variation_attributes();

                                           foreach ($varation_name as $key => $value) {
                                               echo '<b>' . str_replace('attribute_pa_', '', $key) . ':</b> ' . $value . '<br>';

                                       }
                                        }
                                    ?>
                                </td>
                                <td class="product_price">
                                    <?php echo wc_price($product_detail['product_price']); ?>
                                </td>
                                <td class="product_quantity">
                                    <?php echo $product_detail['product_quantity']; ?>
                                </td>
                                <td class="total_price">
                                    <?php echo $product_detail['sub_total'];
                                    ?>
                                </td>
                            </tr>
                            <?php $whole_quote_sub_total = $product_detail['quote_total']; ?>
                            <input type="hidden" name="product_id" class="product_id" value="<?php echo $product_detail['product_id']; ?>">
                            <input type="hidden" name="product_type" class="product_type" value="<?php echo $product_detail['product_type']; ?>">
                            <input type="hidden" name="variation_id" class="variation_id" value="<?php echo $product_detail['variation_id']; ?>">

                            <?php
                            $product_id = $product_detail['product_id'];
                            $product = wc_get_product($product_id);
                            $quantity = (int)$product_detail['product_quantity'];

                            $sale_price = $product->get_price();
                            $gett += $sale_price*$quantity;


                       }
                       ?>
                       </tbody>
                       <tfoot>
                           <tr>
                               <td></td>
                               <td></td>
                               <td colspan="2"><?php echo __('Sub Total', WATQ); ?></td>
                               <td><?php echo get_woocommerce_currency_symbol(). $gett; //wc_price($whole_quote_sub_total); ?> </td>
                           </tr>
                       </tfoot>
                   </table>
               </div>
           </div>
       </div>
       <?php
   }

   /**
    *  Get MetaBox Value
    */
    public function watq_get_metabox_value($value) {

        global $post;

        $custom_field = get_post_meta( $post->ID, $value, true );
        if ( !empty( $custom_field ) )
            return is_array( $custom_field ) ? stripslashes_deep( $custom_field ) : stripslashes( wp_kses_decode_entities( $custom_field ) );

        return false;
    }

    /**
     *  Update Unread Quotes
     */
    public function watq_update_unread_quotes() {
        global $post,$pagenow ;
        if(is_object($post) && isset($pagenow)) {
            if( $post->post_type == "watq-quotes" && $pagenow == "post.php" ) {
                if((int)get_option('watq_unread_quotes') > 0 && $post->post_content == 'unread' ) {
                    $unread_quotes = (int)get_option('watq_unread_quotes') -1;
                    update_option('watq_unread_quotes', $unread_quotes);

                    $change_status = array(
                        'ID'           => $post->ID,
                        'post_content' => 'read',
                    );

                    wp_update_post( $change_status );
                }
            }
        }
    }
}

new WATQ_MetaBoxes();