<?php

Ajax::add_init_fn(function () {

    Ajax::register_custom_route('price_rules', [
        'run' => function () {
            // file will check nonce
            require CORE_DIR . '/classes/product-sync/ajax/price-rules.php';
        }
    ]);

    Ajax::register_custom_route('sync_products', [
        'run' => function () {
            // file will check nonce
            require CORE_DIR . '/classes/product-sync/ajax/sync-products.php';
        }
    ]);

    Ajax::register_custom_route('fastco_fetch', [
        'run' => function () {

            if (IS_WFL) {
                echo json_encode( [
                    'error' => 'disabled here.'
                ]);
                exit;
            }

            $type = gp_test_input(@$_REQUEST['type']);
            $auth = @$_REQUEST['auth'];

            if (strval($auth) === '32723472134723487234') {
                $res = Product_Sync_Fastco::get_fastco_file_parsed($type);
                echo json_encode($res, JSON_INVALID_UTF8_IGNORE | JSON_PRETTY_PRINT);
                exit;
            }

            echo '[]';
            exit;
        }
    ]);
});
