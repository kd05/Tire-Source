<?php

use Product_Sync_Admin_UI as ui;
use Product_Sync_Pricing_UI as pricing;

assert( isset( $supplier ) );
assert( isset( $locale ) );
assert( isset( $type ) );

$db_supplier = DB_Supplier::get_instance_via_slug( $supplier );
$mem = new Time_Mem_Tracker();

if ( ! $db_supplier ) {
    echo "Invalid supplier.";
    exit;
}

if ( ! in_array( $locale, [ 'CA', 'US' ] ) ) {
    echo "Invalid locale.";
    exit;
}

if ( ! in_array( $type, [ 'tires', 'rims' ] ) ) {
    echo "Invalid product type.";
    exit;
}

Header::$title = gp_test_input( "Pricing $supplier $type $locale" );

// $product_counts = pricing::get_product_counts_by_supplier( $type, $locale );
$price_rules = Product_Sync_Compare::get_cached_indexed_price_rules();

if( $type === 'tires' ) {
    $brands = pricing::get_tire_brand_counts_via_supplier( $supplier, $locale );
    $models = pricing::get_tire_model_counts_via_supplier( $supplier, $locale );
} else {
    $brands = pricing::get_rim_brand_counts_via_supplier( $supplier, $locale );
    $models = pricing::get_rim_models_counts_via_supplier( $supplier, $locale );
}

$mem->breakpoint('brands_models');

list( $supplier_rules, $brand_rules, $model_rules ) = pricing::get_supplier_price_rules( $type, $locale, $supplier );

$add_delete_column = function( $price_rule ) {
    return array_merge( $price_rule, [
        'delete' => pricing::get_price_rule_delete_btn( $price_rule['id'] ),
    ] );
};

$supplier_rules_table = pricing::format_price_rules_for_html_table( $supplier_rules );
$brand_rules_table = pricing::format_price_rules_for_html_table( $brand_rules );
$model_rules_table = pricing::format_price_rules_for_html_table( $model_rules );

$mem->breakpoint('get_rules');

$title = gp_test_input( $supplier . ' ' . $type . ' ' . $locale );

echo ui::breadcrumb( [
    [ 'Price Rules', get_admin_page_url( 'pricing' ) ],
    [ $title, '' ],
]);

?>
<br><br>
<div class="general-content">
    <p><strong>Rule Type Explanation:</strong></p>
    <p>Cost (with MAP enforced): The price calculation is first based off Cost (modified by a % and a flat rate). After calculating the price, if the supplier specifies a MAP and the price is less than MAP, the MAP will be used instead.</p>
    <p>MAP (with Cost fallback): If the supplier provides a MAP price, the cost will be based off the MAP (can be modified by a % and flat rate). But if the supplier doesn't give a MAP, then we'll base the price off of cost instead. Some suppliers give a MAP price for only some of the product in the file, so this is why we need to fallback to Cost or MSRP.</p>
    <p>Example... If supplier gives Cost: $100, MAP: $150.</p>
    <p>If we use Cost (with MAP enforced) with Cost + 40%, the effective price will be $150 (because Cost + 40% is less than the MAP). If the MAP was instead $130, the effective price will be $140.</p>
    <p>If we use MAP (with Cost fallback) with Cost + 40%, the effective price will also be $150. But if the product has no MAP price, the effective price will be $140 (Cost + 40%).</p>
</div>
    <br>
<?php

echo ui::br(20);

echo ui::render_table( null, $supplier_rules_table, [
    'title' => 'Supplier Price Rule(s) (' . count( $supplier_rules ) . ')',
    'skip_sanitize' => [ 'delete' ],
    'add_count' => false,
    'sanitize' => true,
] );

echo pricing::get_insert_price_rule_form_table( $type, $locale, $supplier, 'supplier', $brands );

echo ui::br(30);
echo '<hr>';
echo ui::br(30);

echo ui::render_table( null, $brand_rules_table, [
    'title' => 'Supplier/Brand Price Rule(s) (' . count( $brand_rules ) . ')',
    'skip_sanitize' => [ 'delete' ],
    'add_count' => false,
    'sanitize' => true,
] );

echo pricing::get_insert_price_rule_form_table( $type, $locale, $supplier, 'brand', $brands );

echo ui::br(30);
echo '<hr>';
echo ui::br(30);

echo ui::render_table( null, $model_rules_table, [
    'title' => 'Supplier/Brand/Model Price Rule(s) (' . count( $model_rules ) . ')',
    'skip_sanitize' => [ 'delete' ],
    'add_count' => false,
    'sanitize' => true,
] );

echo pricing::get_insert_price_rule_form_table( $type, $locale, $supplier, 'model', $models );

echo ui::br(30);
echo '<hr>';
echo ui::br(30);

$mem->breakpoint('render_tables');

echo $mem->display_everything( true );


Footer::add_raw_html( function(){
    ?>
    <script>
        jQuery(document).ready(function(){
            $('body').on('submit', '.ps-price-rule-insert', function(e){
                e.preventDefault();

                var form = $(this);
                var dataArr = form.serializeArray();

                var data = {
                    nonce: form.attr('data-nonce'),
                    type: form.attr('data-type'),
                    locale: form.attr('data-locale'),
                    supplier: form.attr('data-supplier'),
                    entity_type: form.attr('data-entity-type'),
                    price_rule_action: form.attr('data-price-rule-action'),
                    delete_id: form.attr('data-delete-id'),
                };

                console.log(data);

                $.each(dataArr, function(k,v){
                    data[v.name] = v.value;
                });

                $.ajax({
                    url: form.attr('data-url'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    error: function(a, b, c){
                        alert("unexpected server error.");
                        console.error(a, b, c)
                    },
                    success: function(res){
                        if ( res.success ) {
                            alert(res.msg);
                            location.reload();
                        } else if ( res.error ) {
                            alert(res.error);
                        } else {
                            alert("Unexpected response from server.");
                        }
                    }
                })
            })


        });
    </script>
    <?php
});
