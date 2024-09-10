<?php
/*
Plugin Name: Antimony Woocommerce Custom
Description: A custom plugin for Buy One, Half Off Discount Scheme per Category (or Group of Categories) - go to Settings->Antimony WC Discount to manage features
Version: 1.0
Author: Anthony Ahn
*/

// Render custom options page content (main container)
function render_custom_discount_options() {
    ?>
    <div class="wrap">
        <h1>Antimony Woocommerce Discount Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields('custom_discount_options_group'); ?>
            <?php do_settings_sections('custom_discount_options'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Add custom options page
function custom_discount_options_page() {
    add_options_page(
        'Antimony WooCommerce Discount Settings', // Page title
        'Antimony WC Discount', // Menu title
        'manage_options', // Capability
        'custom_discount_options', // Menu slug
        'render_custom_discount_options' // Callback function to render the page content
    );
}
add_action('admin_menu', 'custom_discount_options_page');

// Add fields to the options page
function custom_discount_settings_init() {
    $num_cat_groups = get_option('num_cat_groups') ? get_option('num_cat_groups') : 2; //Ideally this would be dynamic via AJAX in the future

    //First, do the discount control settings
    add_settings_section(
        'custom_discount_section', // Section ID
        'Discount Settings', // Section title
        '', // Callback function
        'custom_discount_options' // Page slug
    );

    add_settings_field(
        'enable_discount_scheme', // Field ID
        'Enable Discount Scheme', // Field title
        'render_enable_discount_scheme_field', // Callback function
        'custom_discount_options', // Page slug
        'custom_discount_section' // Section ID
    );

    add_settings_field(
        'num_cat_groups', // Field ID
        'How many Category Groups?', // Field title
        'render_num_cat_groups_field', // Callback function
        'custom_discount_options', // Page slug
        'custom_discount_section', // Section ID
        array('num_cat_groups' => $num_cat_groups)
    );

    
    for($i = 1; $i <= $num_cat_groups; $i++){
        add_settings_field(
            'discount_categories_' . $i, // Field ID
            'Discount Group ' . $i . ' Category Slugs (comma separated)', // Field title
            'render_cat_slug_text', // Callback function
            'custom_discount_options', // Page slug
            'custom_discount_section', // Section ID
            array('group_num' => $i) // Pass in group num
        );
    
        add_settings_field(
            'discount_message_' . $i, // Field ID
            'Discount Description ' . $i, // Field title
            'render_custom_discount_message', // Callback function
            'custom_discount_options', // Page slug
            'custom_discount_section', // Section ID
            array('group_num' => $i) // Pass in group num
        );
        register_setting(
            'custom_discount_options_group', // Option group
            'discount_categories_' . $i // Option name
        );
        register_setting(
            'custom_discount_options_group', // Option group
            'discount_message_' . $i // Option name
        );
    }

    add_settings_field(
        'enable_admin_discount', // Field ID
        'Enable Admin Discount (overrides other discounts if logged in as admin)', // Field title
        'render_enable_admin_discount_field', // Callback function
        'custom_discount_options', // Page slug
        'custom_discount_section' // Section ID
    );

    register_setting(
        'custom_discount_options_group', // Option group
        'enable_discount_scheme' // Option name
    );


    register_setting(
        'custom_discount_options_group', // Option group
        'enable_admin_discount' // Option name
    );

    register_setting(
        'custom_discount_options_group', // Option group
        'num_cat_groups' // Option name
    );
 

}
add_action('admin_init', 'custom_discount_settings_init');

// Render enable discount scheme field
function render_enable_discount_scheme_field() {
    $enable_discount_scheme = get_option('enable_discount_scheme');
    ?>
    <label for="enable_discount_scheme">
        <input type="checkbox" id="enable_discount_scheme" name="enable_discount_scheme" value="1" <?php checked(1, $enable_discount_scheme); ?>>
        Enable the discount scheme
    </label>
    <?php
}

function render_num_cat_groups_field($args) {
    $num_groups = $args['num_cat_groups'];
    ?>
    <label for="num_cat_groups">
        <input type="number" id="num_cat_groups" name="num_cat_groups" placeholder="0 - 99" value="<?php echo $num_groups; ?>" min = "1" max="99" step="1">
    </label>
    <?php
}

function render_cat_slug_text($args) {
    $field_num = $args['group_num'];
    $discount_cats = get_option('discount_categories_' . $field_num);
    ?>
    <label for="discount_categories<?php echo "_$field_num"; ?>">
        <input type="text" id="discount_categories<?php echo "_$field_num"; ?>" name="discount_categories<?php echo "_$field_num"; ?>" placeholder="category slugs only" value="<?php echo $discount_cats; ?>">
    </label>
    <?php
}

function render_custom_discount_message($args) {
    $field_num = $args['group_num'];
    $discount_msg = get_option('discount_message_' . $field_num);
    ?>
    <label for="discount_message<?php echo "_$field_num"; ?>">
        <input type="text" id="discount_message<?php echo "_$field_num"; ?>" name="discount_message<?php echo "_$field_num"; ?>" placeholder="Custom Cart/Checkout Message" value="<?php echo $discount_msg; ?>">
    </label>
    <?php
}

// Render enable discount scheme field
function render_enable_admin_discount_field() {
    $enable_admin_discount = get_option('enable_admin_discount');
    ?>
    <label for="enable_admin_discount">
        <input type="checkbox" id="enable_admin_discount" name="enable_admin_discount" value="1" <?php checked(1, $enable_admin_discount); ?>>
        Enable admin discount
    </label>
    <?php
}


// Apply custom category discount based on option value
function apply_custom_category_discount() {
    global $woocommerce;

    $enable_discount_scheme = get_option('enable_discount_scheme');
    $enable_admin_discount  = get_option('enable_admin_discount');

    if($enable_admin_discount ==1 && current_user_can('manage_options')){
        // Admin discount takes precedence as we don't want to lead to a total <= 0
        // Loop through cart items
        $total_discount = 0;
        foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $price = $product->get_price();
            $line_price = $price * $quantity;
            $total_discount += $line_price * .999;
        }

        // Add the total discount to the cart
        if ($total_discount > 0) {
            $woocommerce->cart->add_fee('Admin Discount Enabled 99.9%', -$total_discount);
        }
    } else if($enable_discount_scheme == 1 && get_option('num_cat_groups') > 0){
        // If we can't apply the admin discount, now discount the category groups

        $num_cat_groups = get_option('num_cat_groups'); //How many groups do we have to do this for

        //Each index will correspond to the same index value on the following 3 arrays
        $discount_cats = []; //Array of array of categories in that cat group
        $discount_msgs = []; //Array of messages to display on checkout screen        
        $discount_amts = []; //Array of array of discounts

        for($i = 1; $i <= $num_cat_groups; $i++){
            $discount_message   = filter_var(get_option('discount_message_' . $i), FILTER_DEFAULT, [FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_LOW]);
            $discount_message   = trim($discount_message);
            $discount_cats_str  = trim(str_replace(' ','',filter_var(get_option('discount_categories_' . $i), FILTER_DEFAULT, [FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_LOW])));
            if(!empty($discount_message) && !empty(str_replace(',','',$discount_cats_str))){
                array_push($discount_cats, explode(',',$discount_cats_str));
                array_push($discount_msgs, $discount_message);
                array_push($discount_amts,[]);
            }
        }
        
        //Now that we have the proper datastructures, we can analyze the cart items        
        foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $price = $product->get_price();
            //error_log('here is price' . $price);
            $cat_match = false; //Flags true if cat match found (prevents discount stacking) 
            $matching_idx = -1; //  
            
            //Now, loop through the categories
            $group_idx = 0; //Iterator

            //Prevents item from getting discounted twice
            foreach($discount_cats AS $cat_array){
                if($cat_match == false){
                    foreach($cat_array AS $cat_single){
                        if(has_term($cat_single, 'product_cat', $product->get_id())){
                            $cat_match = true;
                            $matching_idx = $group_idx;
                        }
                    }
                } else {
                    break;
                }
                $group_idx++;
            }
            
            //Now, add to the amounts array if we find a category match
            if($cat_match == true && $matching_idx > -1){
                for($i = 0; $i < (int)$quantity; $i++){
                    array_push($discount_amts[$matching_idx], (float)$price * 0.5); 
                }   
            }
        }
        // error_log('discount cats is ' . print_r($discount_cats,1));
        // error_log('discount msgs is ' . print_r($discount_msgs,1));
        // error_log('discount prices is ' . print_r($discount_amts,1));
        
        //Okay, now we have arrays of discounts... sort highest to lowest and sum up all but the first element
        $group_idx = 0;
        foreach($discount_amts AS $discounts_arr){
            $discount_total = 0;
            //Only apply discounts if there's more than 1 item in cat group
            if(count($discounts_arr) > 1){
                arsort($discounts_arr);
                $discount_total = array_sum($discounts_arr) - $discounts_arr[0];
            }

            if($discount_total > 0){
                $woocommerce->cart->add_fee($discount_msgs[$group_idx], -$discount_total);
            }
            $group_idx++;
        }

    }
}
add_action('woocommerce_cart_calculate_fees', 'apply_custom_category_discount');
