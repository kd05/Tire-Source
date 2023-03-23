<?php

?>

<div class="pb-flex">
    <?php


    /** @var DB_Product_Brand $brands */
    if ( is_array( $brands ) ) {

        /** @var DB_Rim_Brand $brand */
        foreach ( $brands as $count => $brand ) {
            $name = $brand->get( 'name' );
            $img_url = $brand->get_logo( true );
            $is_rim = $brand instanceof DB_Rim_Brand;

            $url = Router::build_url( [ $is_rim ? 'wheels' : 'tires', $brand->get( 'slug' ) ] );

            ?>

            <div class="pb-item">
                <div class="pb-item-2">
                    <a href="<?= $url; ?>">
                        <span class="img-tag-contain">
                            <img src="<?= $img_url; ?>" alt="Brand logo for <?= $name; ?> tires" />
                        </span>
                        <span><?= gp_test_input( $name ); ?></span>
                    </a>
                </div>
            </div>
            <?php
        }
    }
    ?>
</div>
