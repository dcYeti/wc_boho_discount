# wc_boho_discount
This is a WOOCOMMERCE EXTENSION tested for versions 8 & 9 though will work with most versions past and future.

Buy One Item in a Category (or Collection of Categories), Receive Subsequent Items in Category (or Collection of Categories) for 50% off!

Implements a discount scheme where buying 1 item in a category (or collection of categories) allows all subsequent items in that category (or collection of categories) to be half off.  This will allow up to 99 category collections to receive this discount scheme.  For each category group with 2 or more items, the cheaper items get discounted first while the most expensive item retains its full price.  Please note that discounts will not stack.

Also allows an administrators' discount.  If you are logged in as an administrator, enable and receive a 99.9% discount.  This is best used for checking payment portals and transaction behavior.

Please note that if you have both administrator and category discounts enabled and are logged in as admin, only the administrator discount will apply to avoid having a zero total.

TO USE:
Go to Settings->Antimony WC Discount and use the checkboxes to enable/disable either or both discount schemes.  To create a group of categories, enter the category slugs (see Wordpress reference if unsure what these are) separated by commas.  Additionally, enter the message that appears at cart/checkout for each group of categories.  The plugin will make sure discounts don't stack, but take efforts to make sure there is no category overlap across category groups in order to avoid confusion.

DISCLAIMER:  This is a non-commercial product!  While I've tested this to the best of my ability, there may be scenarios where the discounts don't work as intened.  Use at your own risk! 
