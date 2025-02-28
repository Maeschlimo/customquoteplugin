<?php
/**
 * Admin Functions.
 *
 */

function create_post_to_adds($post_title) {
    $post_id = -1;
    $posted_post_id = null;

    // Setup the author, slug, and title for the post
    $author_id = 1;
    $slug = str_replace(' ','-',strtolower($post_title));
    $title = $post_title;
    $content = 'unread';

    // If the page doesn't already exist, then create it
    $post_exist = get_page_by_title( strtolower( $title ), OBJECT, 'watq-quotes' );
    if( null == $post_exist ) {

        // Set the page ID so that we know the page was created successfully
        $posted_post_id = wp_insert_post(
            array(
                'comment_status'	=>	'closed',
                'ping_status'		=>	'closed',
                'post_author'		=>	$author_id,
                'post_name'		    =>	$slug,
                'post_title'		=>	$title,
                'post_content'      =>  $content,
                'post_status'		=>	'publish',
                'post_type'		    =>	'watq-quotes',
            )
        );
        $unread_quotes = (int)get_option('watq_unread_quotes') + 1;
        update_option('watq_unread_quotes', $unread_quotes);
        return $posted_post_id;

    // Otherwise, we'll stop and set a flag
    } else {
        return $post_exist;
    }
}

function randomPassword() {
    $alphabet = '1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 4; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

function hide_publishing_actions(){
    $post_type = 'watq-quotes';
    global $post;
    if($post->post_type == $post_type){
        echo '
                <style type="text/css">
                    #submitdiv{
                       display:none !important;
                    }
                    #side-sortables.empty-container {
                        border: none !important;
                    }
                    #post-body-content {
                        display:none !important;
                    }
                </style>
            ';
    }
}
add_action('admin_head-post.php', 'hide_publishing_actions');
add_action('admin_head-post-new.php', 'hide_publishing_actions');

add_filter( 'post_row_actions', 'remove_row_actions', 10, 1 );
function remove_row_actions( $actions )
{
    if( get_post_type() === 'watq-quotes' ) {
        unset( $actions['edit'] );
        unset( $actions['view'] );
        unset( $actions['inline hide-if-no-js'] );
    }
    return $actions;
}

add_action( 'admin_head', 'watq_menu_order_count' );
function watq_menu_order_count () {
    global $submenu;
    if ( isset( $submenu['woocommerce'] ) ) {

        // Remove 'WooCommerce' sub menu item
        unset( $submenu['woocommerce'][0] );
		
        // Add count if user has access
        if ( current_user_can( 'manage_woocommerce' ) && ( $order_count = (int) get_option('watq_unread_quotes') ) ) {
            foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
                if ( 0 === strpos( $menu_item[0], _x( 'Added Quotes', 'Admin menu name', WATQ ) ) ) {
                    $submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . $order_count . '"><span class="processing-count">' . $order_count  . '</span></span>';
                    break;
                }
            }
        }
    }
}

add_action( 'wp_trash_post', 'watq_decrease_count_unread_post' );
function watq_decrease_count_unread_post( $postid ){

    global $post_type, $post;
    if ( $post_type == 'watq-quotes' ) {
        if($post->post_content == "unread") {
            if((int)get_option('watq_unread_quotes') > 0) {
                $unread_quotes = (int)get_option('watq_unread_quotes') - 1;
                update_option('watq_unread_quotes', $unread_quotes);
            }
        }
    }
}

// custom columns on quote post.
add_filter('manage_watq-quotes_posts_columns' , 'watq_quote_post_columns');
function watq_quote_post_columns($columns) {
    unset($columns['date']);
    $new_columns = array(
        'status' => __('Status', 'watq'),
        'user' => __('User', 'watq'),
        'date' => __('Date', 'watq'),
    );
    return array_merge($columns, $new_columns);
}

// custom data on custom columns
add_action( 'manage_posts_custom_column' , 'watq_quote_post_column_data', 10, 2 );
function watq_quote_post_column_data( $column, $post_id ) {
    global $post;
    switch ( $column ) {

        case 'status':
            echo $post->post_content;
            break;

        case 'user':
            $post_meta = get_post_meta( $post_id, 'quote_post_data' );
            if(!empty($post_meta[0]['user_id'])) {
                echo __("Registered", 'watq');
            }
            else {
                echo __("Guest", 'watq');
            }

            break;
    }
}