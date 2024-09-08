<?php
/*
Plugin Name: Antimony Woocommerce Custom
Description: A custom plugin for Woods & Poole, go to Settings->Antimony Settings to manage features
Version: 1.0
Author: Anthony Ahn
*/

// Render custom options page content (main container)
function render_custom_discount_options() {
    ?>
    <div class="wrap">
        <h1>Antimony Custom Settings</h1>
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
        'Antimony Custom Settings', // Page title
        'Antimony Settings', // Menu title
        'manage_options', // Capability
        'custom_discount_options', // Menu slug
        'render_custom_discount_options' // Callback function to render the page content
    );
}
add_action('admin_menu', 'custom_discount_options_page');

// Add fields to the options page
function custom_discount_settings_init() {
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
        'discount_categories', // Field ID
        'Categories to Discount (comma separated)', // Field title
        'render_cat_slug_text', // Callback function
        'custom_discount_options', // Page slug
        'custom_discount_section' // Section ID
    );

    add_settings_field(
        'discount_message', // Field ID
        'Discount Description', // Field title
        'render_custom_discount_message', // Callback function
        'custom_discount_options', // Page slug
        'custom_discount_section' // Section ID
    );

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
        'discount_categories' // Option name
    );
    register_setting(
        'custom_discount_options_group', // Option group
        'discount_message' // Option name
    );
    register_setting(
        'custom_discount_options_group', // Option group
        'enable_admin_discount' // Option name
    );

    //Now, do the enable tax exemption thing
    add_settings_section(
        'custom_tax_exempt_section', // Section ID
        'Tax Exemption Enable', // Section title
        '', // Callback function
        'custom_discount_options' // Page slug
    );

    add_settings_field(
        'enable_dc_tax_exempt', // Field ID
        'Enable DC Tax Exemption', // Field title
        'render_enable_dc_tax_exempt_field', // Callback function
        'custom_discount_options', // Page slug
        'custom_tax_exempt_section'
    ); 

    register_setting(
        'custom_discount_options_group', // Option group
        'enable_dc_tax_exempt' // Option name
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

function render_cat_slug_text() {
    $discount_cats = get_option('discount_categories');
    ?>
    <label for="enable_discount_scheme">
        <input type="text" id="discount_categories" name="discount_categories" placeholder="category slugs only" value="<?php echo $discount_cats; ?>">
    </label>
    <?php
}

function render_custom_discount_message() {
    $discount_msg = get_option('discount_message');
    ?>
    <label for="enable_discount_scheme">
        <input type="text" id="discount_message" name="discount_message" placeholder="Custom Cart/Checkout Message" value="<?php echo $discount_msg; ?>">
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

// Render enable discount scheme field
function render_enable_dc_tax_exempt_field() {
    $enable_dc_tax_exempt = get_option('enable_dc_tax_exempt');
    ?>
    <label for="enable_dc_tax_exempt">
        <input type="checkbox" id="enable_dc_tax_exempt" name="enable_dc_tax_exempt" value="1" <?php checked(1, $enable_dc_tax_exempt); ?>>
        Enable DC Tax Exemption
    </label>
    <?php
}

// Apply custom category discount based on option value
function apply_custom_category_discount() {
    global $woocommerce;

    $enable_discount_scheme = get_option('enable_discount_scheme');
    $enable_admin_discount = get_option('enable_admin_discount');
    $discount_cats_string = get_option('discount_categories');
    $discount_message = get_option('discount_message','Bulk Discount on Data Pamphlets and Desktop Data Files');

    $discount_cats = explode(',',trim($discount_cats_string));

    $category_counts = [];
    $category_discounts = [];
    $eligible_categories = [];

    foreach($discount_cats AS $cat){
        if(trim($cat) != ''){
            $category_counts[$cat] = 0;
            $category_discounts[$cat] = 0;
            array_push($eligible_categories, $cat);
        }
    }

    if($enable_admin_discount ==1 && current_user_can('manage_options')){
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
    } else if($enable_discount_scheme == 1 && sizeof($category_counts) > 0){

        // Loop through cart items
        foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $price = $product->get_price();

            // Check if the product belongs to an eligible category
            foreach ($eligible_categories as $category) {
                if (has_term($category, 'product_cat', $product->get_id())) {
                    // Update the count and calculate the discount for items beyond the first one
                    for ($i = 1; $i <= $quantity; $i++) {
                        if ($category_counts[$category] > 0) { // Apply discount if not the first item in the category
                            $category_discounts[$category] += $price * 0.50; // 50% discount
                        }
                        $category_counts[$category]++;
                    }
                    break; // If found in one category, no need to check the other categories
                }
            }
        }

        // Calculate the total discount
        $total_discount = 0;
        foreach ($category_discounts as $category => $discount) {
            // Only apply discount if there are at least 2 items in the category
            if ($category_counts[$category] >= 2) {
                $total_discount += $discount;
            }
        }

        // Add the total discount to the cart
        if ($total_discount > 0) {
            $woocommerce->cart->add_fee($discount_message, -$total_discount);
        }
    }
}
add_action('woocommerce_cart_calculate_fees', 'apply_custom_category_discount');

// Add the tax exemption checkbox and tax ID field to the checkout
function custom_woocommerce_checkout_fields($fields) {
    $fields['billing']['tax_exempt'] = array(
        'type'    => 'checkbox',
        'label'   => __('I am tax exempt'),
        'class'   => array('form-row-wide'),
        'priority' => 120,
    );

    $fields['billing']['tax_id'] = array(
        'type'        => 'text',
        'label'       => __('Tax ID'),
        'placeholder' => _x('Enter Valid Tax ID Number', 'placeholder', 'woocommerce'),
        'class'       => array('form-row-wide'),
        'priority'    => 130,
        'required'    => false,
        'clear'       => true,
    );

    return $fields;
}
if(get_option($enable_dc_tax_exempt) == 1){
    add_filter('woocommerce_billing_fields', 'custom_woocommerce_checkout_fields');
}
// Display the fields conditionally based on state selection
function show_tax_exempt_fields() {
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            function toggleTaxExemptFields() {
                var state = $('#billing_state').val();
                if (state === 'DC') {
                    $('#billing_tax_exempt_field').show();
                    $('#billing_tax_id_field').show();
                } else {
                    $('#billing_tax_exempt_field').hide();
                    $('#billing_tax_id_field').hide();
                }
            }

            toggleTaxExemptFields();

            $('#billing_state').change(function() {
                toggleTaxExemptFields();
            });

            // Add input mask for Tax ID
            $('#billing_tax_id').on('input', function() {
                var value = $(this).val();
                $(this).val(value.replace(/[^0-9a-zA-Z]/g, '').toUpperCase());
            });
        });
    </script>
    <?php
}
if(get_option($enable_dc_tax_exempt) == 1){
    add_action('woocommerce_after_checkout_form', 'show_tax_exempt_fields');
}
// Validate the tax exemption fields
function validate_tax_exempt_fields() {
    if (isset($_POST['tax_exempt']) && '1' === $_POST['tax_exempt']) {
        if (empty($_POST['tax_id'])) {
            wc_add_notice(__('Please enter a valid Tax ID for tax exemption.'), 'error');
        }
        // Add your tax ID validation logic here if needed
    }
}
if(get_option($enable_dc_tax_exempt) == 1){
    add_action('woocommerce_checkout_process', 'validate_tax_exempt_fields');
}
// Save the tax exemption fields
function save_tax_exempt_fields($order_id) {
    if (isset($_POST['tax_exempt'])) {
        update_post_meta($order_id, '_billing_tax_exempt', sanitize_text_field($_POST['tax_exempt']));
    }
    if (isset($_POST['tax_id'])) {
        update_post_meta($order_id, '_billing_tax_id', sanitize_text_field($_POST['tax_id']));
    }
}
if(get_option($enable_dc_tax_exempt) == 1){
    add_action('woocommerce_checkout_update_order_meta', 'save_tax_exempt_fields');
}
// Apply tax exemption based on the Tax ID field
function custom_woocommerce_apply_tax_exemption($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $state = WC()->customer->get_billing_state();
    $tax_exempt = isset($_POST['tax_exempt']) ? sanitize_text_field($_POST['tax_exempt']) : '';
    $tax_id = isset($_POST['tax_id']) ? sanitize_text_field($_POST['tax_id']) : '';

    if ($state === 'DC' && $tax_exempt === '1' && !empty($tax_id)) {
        foreach ($cart->get_cart() as $cart_item) {
            $cart_item['data']->set_tax_class('zero-rate');
        }
    }
}
if(get_option($enable_dc_tax_exempt) == 1){
    add_action('woocommerce_cart_calculate_fees', 'custom_woocommerce_apply_tax_exemption');
}


