<?php

class Product_Images_Admin_UI {

    /**
     * Render admin UI for tires or rims. Since they are both so similar, we'll just
     * use the same function.
     *
     * @param $is_tire
     * @param array $request - $_GET, probably
     * @param array $entities - all tire models or rim finishes
     */
    static function render( $is_tire, array $request, array $entities ) {

        // 'ok', or 'not_ok'
        $show = gp_test_input( @$request[ 'show' ] );
        $display = gp_test_input( @$request[ 'display' ] );
        $debug = (int) @$request[ 'debug' ];
        $id = (int) @$request[ 'id' ];
        $ajax_action = $is_tire ? 'tire_model_image' : 'rim_finish_image';

        // filter and count
        list( $entities_to_show, $count_ok, $count_not_ok ) = call_user_func( function () use ( $show, $entities ) {

            $ok = [];
            $not_ok = [];

            foreach ( $entities as $entity ) {
                /** @var DB_Rim_Finish|DB_Tire_Model $entity */
                if ( Product_Images::entity_requires_image_updates( $entity ) ) {
                    $not_ok[] = $entity;
                } else {
                    $ok[] = $entity;
                }
            }

            return [ $show === 'ok' ? $ok : $not_ok, count( $ok ), count( $not_ok ) ];
        } );

        if ( $id > 0 ) {
            $entities_to_show = array_filter( $entities, function ( $e ) use ( $id ) {

                return $e->get_primary_key_value() == $id;
            } );
        } else if ( $show === 'all' ) {
            $entities_to_show = $entities;
        }

        $url_ok = cw_add_query_arg( [
            'page' => $is_tire ? 'tire_images' : 'rim_images',
            'show' => 'ok',
            'display' => $display ? 1 : null,
        ], get_admin_page_url() );

        $url_not_ok = cw_add_query_arg( [
            'page' => $is_tire ? 'tire_images' : 'rim_images',
            'display' => $display ? 1 : null,
        ], get_admin_page_url() );

        $url_all = cw_add_query_arg( [
            'page' => $is_tire ? 'tire_images' : 'rim_images',
            'show' => 'all',
            'display' => $display ? 1 : null,
        ], get_admin_page_url() );

        $url_ents = get_admin_archive_link( $is_tire ? 'tire_models' : 'rim_finishes' );
        $ents_title = $is_tire ? "Models" : "Finishes";
        ?>

        <p style="margin-bottom: 10px;">
            <span><?= html_link( $url_ents, count( $entities ) ) . ' ' . $ents_title . ', '; ?></span>
            <span><?= html_link( $url_ok, $count_ok ) . ' Ok, '; ?></span>
            <span><?= html_link( $url_not_ok, $count_not_ok ) . ' Not ok. '; ?></span>
            <span><?= html_link( $url_all, 'Show all' ) . '.'; ?></span>
        </p>

        <p><strong>Before localizing in bulk, click the "Hide items with no image URLs" button.</strong></p>

        <p>Displaying: <?= $show === 'ok' ? 'Ok.' : 'Not ok.'; ?></p>

        <?php if ( $id == false ) { ?>
            <form class="form-style-1" method="get" action="<?= ADMIN_URL; ?>">

                <input type="hidden" name="page" value="<?= $is_tire ? 'tire_images' : 'rim_images'; ?>">
                <input type="hidden" name="show" value="<?= $show ? 'ok' : 'not_ok'; ?>">

                <select name="display" id="">
                    <?= get_select_options( [
                        'items' => [
                            '1' => 'Display images',
                        ],
                        'placeholder' => 'Don\'t display images',
                        'current_value' => $display,
                    ] ); ?>
                </select>

                <button class="">Filter Items</button>
            </form>
        <?php } ?>

        <!-- for jquery -->
        <div style="display: none;" id="product-images-data">
            <input type="hidden" name="action" value="<?= AJAX_URL . '?ajax_action=' . $ajax_action; ?>">
            <input type="hidden" name="nonce" value="<?= get_nonce_value( $ajax_action ); ?>">
        </div>

        <div class="product-images-controls">
            <div class="multi-buttons">
                <button class="hide-empty" title="Do this before selecting all items">Hide Items with no image URLs</button>
            </div>
            <br>
            <div class="multi-buttons">
                <button class="select-all">Select All</button>
                <button class="un-select-all">Un-Select All</button>
                <button class="pause">Cancel</button>
            </div>
            <br>
            <div class="button-1">
                <button class="css-reset submit">Localize Selected</button>
            </div>
            <div class="progress general-content" style="margin-top: 15px;"><p style="font-weight: 700;"></p></div>
        </div>

        <div class="table-wrap admin-table table-style-1">
            <table class="product-images-table table-csv-target" data-csv="1">
                <tr>
                    <th><?= $is_tire ? "Tire Model" : "Rim Finish"; ?></th>
                    <th>Localize?</th>
                    <th>URLs</th>
                    <th>Filesizes</th>
                    <th>Display</th>
                    <?= $debug ? "<th>Debug</th>" : ""; ?>
                </tr>

                <?php
                foreach ( $entities_to_show as $index => $entity ) {
                    echo self::render_row( $is_tire, $entity, $index + 1, $display > 0 || $id > 0, $debug );
                }
                ?>
            </table>
            <?php
            if ( ! $entities_to_show ) {
                echo "<br>" . wrap_tag( "No Items Found." );
            }
            ?>
        </div>

        <?php

        Footer::add_raw_html( function () {

            ?>
            <script>

                jQuery(document).ready(function ($) {

                    var items_to_do = [];
                    var items_finished = [];
                    var in_progress = false;
                    var paused = false;

                    var nonce = $('#product-images-data [name="nonce"]').val();
                    var url = $('#product-images-data [name="action"]').val();
                    var table = $('.product-images-table');

                    $('.product-images-controls .hide-empty').on('click', function () {

                        var count_hidden = 0;
                        var count_not = 0;

                        $.each( table.find('tr').not(':first'), function(k, v){
                            var tr = $(this);
                            if ( tr.find('input[name="new_source"]').val() ){
                                count_not++;
                            } else {
                                tr.remove();
                                count_hidden++;
                            }
                        });

                        alert( count_hidden + " item(s) hidden, " + count_not + " item(s) remain. Re-load the page to show the hidden items again, or select all and localize." );
                    });

                    $('.product-images-controls .select-all').on('click', function () {
                        table.find('.localize-checkbox').attr('checked', true);
                    });

                    $('.product-images-controls .un-select-all').on('click', function () {
                        table.find('.localize-checkbox').attr('checked', false);
                    });

                    $('.product-images-controls .pause').on('click', function () {
                        paused = true;
                    });

                    $('body').on('click', '.set-new-source-btn', function () {
                        var entity_id = $(this).closest('tr').attr('data-entity-id');
                        process(entity_id);
                        paused = true;
                    });

                    $('.product-images-controls .submit').on('click', function () {

                        if (in_progress) {
                            alert("Already in progress. Please wait or pause.");
                            return;
                        }

                        items_finished = [];

                        items_to_do = $.map(table.find('.localize-checkbox:checked'), function (el) {
                            return $(el).val();
                        });

                        if (items_to_do.length > 0) {
                            in_progress = true;
                            paused = false;
                            process(items_to_do[0]);
                        } else {
                            in_progress = false;
                            paused = false;
                            alert('No items selected.');
                        }
                    });

                    /**
                     *
                     */
                    function next_arr_el(arr, current) {
                        var idx = arr.indexOf(current);
                        var next = arr[idx + 1];
                        return next === undefined ? false : next;
                    }

                    /**
                     *
                     */
                    function set_progress_text(finished, raw_text) {

                        if (raw_text !== undefined) {
                            $('.product-images-controls .progress p').text(t);
                        } else {

                            var t = "" + items_finished.length + "/" + items_to_do.length;

                            if (finished) {
                                t += ". Complete.";
                            } else {
                                t += "...";
                            }

                            $('.product-images-controls .progress p').text(t);
                        }
                    }

                    /**
                     *
                     */
                    function get_row(id) {
                        return table.find('tr[data-entity-id="' + id + '"]');
                    }

                    /**
                     *
                     */
                    function process(id) {

                        set_progress_text(false);

                        var count = get_row(id).attr('data-count');
                        var debug = get_row(id).attr('data-debug');
                        var display = get_row(id).attr('data-display');
                        var new_source = get_row(id).find('input[name="new_source"]').val();

                        // light blue
                        get_row(id).css('background', '#92ccff');

                        /**
                         *
                         */
                        function cont() {

                            if (paused) {
                                in_progress = false;
                                return;
                            }

                            items_finished.push(id);

                            var next_id = next_arr_el(items_to_do, id);

                            // maybe do next item.
                            if (next_id) {
                                setTimeout(function () {
                                    process(next_id);
                                }, 200);
                            } else {
                                in_progress = false;
                                set_progress_text(true);
                            }
                        }

                        $.ajax({
                            url: url,
                            data: {
                                nonce: nonce,
                                entity_id: id,
                                new_source: new_source,
                                count: count,
                                debug: debug,
                                display: display
                            },
                            type: 'POST',
                            dataType: 'json',
                            error: function () {
                                if (confirm("An error occurred for ID #" + id + ", press OK to continue with the next one, or cancel to not continue.")) {
                                    cont();
                                }
                            },
                            success: function (res) {

                                // replace the entire row.
                                if (res.row_html) {
                                    get_row(id).before(res.row_html).remove();
                                }

                                // add response to the row.
                                get_row(id).find('._response').empty().append(res.msg);

                                // add colors because why not
                                if (res.success) {
                                    // light green
                                    get_row(id).css('background', '#c9ffdc');

                                } else {
                                    // light red
                                    get_row(id).css('background', '#f6baba');
                                }

                                if (res.continue) {
                                    cont();
                                }
                            }
                        });
                    }

                });

            </script>
            <?php
        } );

    }

    static function render_row( $is_tire, $entity, $count, $display, $debug ) {

        ob_start();

        /** @var DB_Rim_Finish|DB_Tire_Model $entity */

        if ( $is_tire ) {
            $entity->setup_brand();
        } else {
            $entity->setup_brand();
            $entity->setup_model();
        }

        $ok = ! Product_Images::entity_requires_image_updates( $entity );

        $br = function ( $height ) {

            return '<div style="height: ' . (int) $height . 'px"></div>';
        };

        $maybe_link = function ( $url, $text = null, $new_tab = true ) {

            $text = $text === null ? $url : $text;

            if ( is_url_not_strict( $url ) ) {
                return html_link( gp_sanitize_href( $url ), gp_test_input( $text ), [
                    'target' => $new_tab ? '_blank' : '',
                ] );
            } else {
                return wrap_tag( gp_sanitize_href( $text ), 'span' );
            }
        };

        $pk = (int) $entity->get_primary_key_value();
        $image_local = $is_tire ? $entity->get( 'tire_model_image' ) : $entity->get( 'image_local' );
        $image_source = $is_tire ? $entity->get( 'tire_model_image_origin' ) : $entity->get( 'image_source' );
        $image_source_new = $is_tire ? $entity->get( 'tire_model_image_new' ) : $entity->get( 'image_source_new' );

        $image_source__filename = is_url_not_strict( $image_source ) ? '' : $image_source;
        $image_source__effective_url = Product_Images::check_local_files( $image_source )[ 1 ];

        $image_source_new__filename = is_url_not_strict( $image_source_new ) ? '' : $image_source_new;
        $image_source_new__effective_url = Product_Images::check_local_files( $image_source_new )[ 1 ];

        $image_url_reg = $entity->get_image_url( 'reg', false );
        $image_url_thumb = $entity->get_image_url( 'thumb', false );

        $filesize_item = function ( $size ) use ( $entity ) {

            $filesize = $entity->get_image_filesize( $size );

            if ( $filesize ) {
                $url = gp_test_input( $entity->get_image_url( $size, false ) );
                $text = cw_format_bytes( $filesize, 1024, '' );
                return html_link_new_tab( $url, $text, [ 'title' => $url ] );
            } else {
                return "0";
            }
        };

        $filesizes_str = implode( " / ", [
            $filesize_item( 'full' ),
            $filesize_item( 'reg' ),
            $filesize_item( 'thumb' ),
        ] );

        $entity_title = $is_tire ?
            implode( ", ", [ $entity->brand->get( 'slug' ), $entity->get( 'slug' ) ] ) :
            implode( ", ", $entity->get_slugs( true, true, true ) );

        $admin_page_link = html_link( $entity->get_admin_single_page_url(), '(Edit ID ' . $pk . ')' );

        ?>
        <tr data-display="<?= (int) $display; ?>" data-debug="<?= (int) $debug; ?>" data-entity-id="<?= (int) $pk; ?>"
            data-count="<?= (int) $count; ?>">

            <td>
                <?= $count . ") " . $entity_title; ?>
                <?= "<br> " . $admin_page_link; ?>
            </td>

            <td>
                <label style="cursor: pointer; padding: 15px 10px; width: auto;">
                    <input class="localize-checkbox" style="cursor: pointer;" type="checkbox" name="localize[]"
                           value="<?= $pk; ?>">
                </label>
                <div style="padding: 0 3px;" class="_response"></div>
            </td>

            <td>
                <div style="margin-bottom: 8px; margin-top: 10px;">
                    Image Local: <?= gp_test_input( $image_local ); ?>
                </div>
                <div style="margin-bottom: 8px;">
                    Effectively: <?= $maybe_link( $image_url_reg, $image_url_reg ? null : "Not Found" ); ?>
                </div>

                <div style="margin-bottom: 8px; margin-top: 20px;">
                    Old Source: <?= $maybe_link( $image_source ); ?>
                </div>

                <div style="margin-bottom: 8px;">
                    Effectively: <?= $image_source__effective_url === $image_source
                        ? 'Same'
                        : $maybe_link( $image_source__effective_url, $image_source__effective_url ? null : "Not Found" ); ?>
                </div>

                <div style="margin-bottom: 8px; margin-top: 20px;">
                    New Source: <?= $maybe_link( $image_source_new ); ?>
                </div>

                <div style="margin-bottom: 8px;">
                    Effectively: <?= $image_source_new__effective_url === $image_source_new
                        ? 'Same'
                        : $maybe_link( $image_source_new__effective_url, $image_source_new__effective_url ? null : "Not Found" ); ?>
                </div>

                <div style="margin-bottom: 10px; margin-top: 20px;">
                    Status:
                    <?php
                    if ( $ok ) {
                        echo '<i class="fa fa-check"></i> Ok (sources match, and local image exists).';
                    } else {

                        if ( $image_local && $image_source_new && $image_source_new !== $image_source ) {
                            echo '<i class="fa fa-times"></i> Not ok (sources do not match).';
                        } else {
                            echo '<i class="fa fa-times"></i> Not ok (sources match, but local image not found.).';
                        }

                    }
                    ?>
                </div>

                <?php $label_title = "Enter the URL for the new image, then click Localize. Image filenames are also accepted, and it will try to find the image on your server. The value here is the same as New Source when the page loads."; ?>

                <div style="margin-bottom: 10px; margin-top: 14px;">
                    <div style="margin-bottom: 5px;">
                        <label title="<?= $label_title; ?>" for="">
                            Set Image: <input style="width: 900px;" type="text" name="new_source"
                                                         value="<?= gp_test_input_alt( $image_source_new ); ?>">
                        </label>
                    </div>
                    <div>
                        <button class="set-new-source-btn" type="button">Localize</button>
                    </div>
                </div>
            </td>

            <td>
                <?= $filesizes_str ?>
            </td>

            <td>
                <?php if ( $display ) { ?>
                    <img style="width: 150px; height: auto;"
                         src="<?= $image_url_thumb ? $image_url_thumb : image_not_available(); ?>" alt="">
                <?php } ?>
            </td>

            <?php

            if ( $debug ) {
                echo '<td>';
                if ( $entity->get_image_filesize( 'full' ) ) {
                    echo get_pre_print_r( @getimagesize( $entity->get_image_path( 'full' ) ) );
                    echo get_pre_print_r( @getimagesize( $entity->get_image_path( 'reg' ) ) );
                    echo get_pre_print_r( @getimagesize( $entity->get_image_path( 'thumb' ) ) );
                }
                echo '</td>';
            }
            ?>

        </tr>

        <?php

        return ob_get_clean();
    }

    /**
     * @param $request
     * @return array
     * @throws Exception
     */
    static function handle_submit( $request ) {

        $is_tire = (int) @$request[ 'is_tire' ];
        $entity_id = (int) @$request[ 'entity_id' ];
        $new_source_0 = trim( html_entity_decode( @$request[ 'new_source' ] ) );
        $count = (int) @$request[ 'count' ];
        $debug = (int) @$request[ 'debug' ];
        $display = (int) @$request[ 'display' ];
        $get_entity = function () use ( $is_tire, $entity_id ) {

            return $is_tire ? DB_Tire_Model::create_instance_via_primary_key( $entity_id ) : DB_Rim_Finish::create_instance_via_primary_key( $entity_id );
        };

        /** @var DB_Tire_Model|DB_Rim_Finish $entity */
        $entity = $get_entity();

        // example.com/image.jpg ---> https://example.com/image.jpg
        $new_source = Product_Images::possibly_convert_to_url( $new_source_0 );

        $ret = [
            'success' => false,
            'msg' => '',
            'continue' => true,
            'row_html' => '',
            // add what happened, because so many conditionals
            'debug' => [],
        ];

        $ret['debug'][] = 'START (new source, eff new source): ' . implode( ', ', [ $new_source_0, $new_source ] );

        if ( ! $new_source_0 ) {
            $ret[ 'msg' ] = "No image provided.";
            return $ret;
        }

        // remove thickbox filename extensions because we'll check multiple extensions below..
        // the reason is that stupid supplier files almost always list extensions with .jpg,
        // but then provide images to us with .png extensions. When we check the local server,
        // if the filename has no extension, it will just check all extensions. And yes,
        // this is becoming extremely complex and hard to follow.
        // 11026914-19465-thickbox.jpg -> 11026914-19465-thickbox
        if ( ! is_url_not_strict( $new_source ) && strpos( $new_source, '-thickbox' ) !== false ) {
            $_0 = $new_source;
            $new_source = Product_Images::remove_extension( $new_source );
            $ret['debug'][] = "Non URL Thickbox filename detected, REMOVING extension: $_0 --> $new_source";
        }

        if ( ! $entity ) {
            $ret[ 'msg' ] = $is_tire ? "Tire Model not found." : "Rim Finish not found.";
            return $ret;
        }

        list( $local, $source, $_source_new ) = Product_Images::get_image_data( $entity );

        if ( $new_source === '__delete' ) {

            if ( $is_tire ) {
                $deleted = Product_Images::delete_tire_model_image( $entity );
            } else {
                $deleted = Product_Images::delete_rim_finish_image( $entity );
            }

            $ret[ 'success' ] = (bool) $deleted;
            $ret[ 'msg' ] = $deleted ? "Image Deleted." : "Image deletion failed.";
            $ret[ 'row_html' ] = self::render_row( $is_tire, $get_entity(), $count, $display, $debug );
            return $ret;
        }

        // if the new source is not given, we'll re-run the current source, which may re-download
        // the same image, but the url may have had an error last time and now it works.
        $effective_source = $new_source ? $new_source : $source;

        // checks server in case source is a filename and not a URL.
        // (source can also omit file extension if its not a URL)
        $derived_url = Product_Images::check_local_files( $effective_source )[ 1 ];

        $ret['debug'][] = 'Check local files, convert source to url: ' . $effective_source . ' --> ' . $derived_url;

        if ( ! $derived_url ) {
            $_checked = $new_source_0 === $new_source ? $new_source_0 : "$new_source_0 or $new_source (checked png/jpg/jpeg)";
            $ret[ 'msg' ] = "The image provided wasn't found anywhere on the server ($_checked).";
            return $ret;
        }

        $ret['debug'][] = 'SET IMAGE: ' . implode( ", ", [ $effective_source, $derived_url, $entity->get_primary_key_value() ] );

        // passed by ref possibly mutated
        $_debug = [];

        // have to pass in non transformed $new_source_0 so that when product sync or imports
        // run again we can tell when the image does not need to be changed (confusing, sorry).
        if ( $is_tire ) {
            list( $success, $msg ) = Product_Images::set_product_image( $new_source_0, $derived_url, $entity, $_debug );
        } else {
            list( $success, $msg ) = Product_Images::set_product_image( $new_source_0, $derived_url, $entity, $_debug );
        }

        $ret['debug']['set_product_image'] = $_debug;

        $ret[ 'success' ] = $success;
        $ret[ 'msg' ] = $msg;
        $ret[ 'row_html' ] = self::render_row( $is_tire, $get_entity(), $count, 1, $debug );

        return $ret;
    }

}
