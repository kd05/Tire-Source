<?php

namespace CW\Migrations\Images;

function tire_model_image_origin__column(){

    $db = get_database_instance();

    $models = $db->get_results("SELECT * FROM tire_models LIMIT 0, 1");
    $model = (array) @$models[0];

    if ( ! array_key_exists( 'tire_model_image_origin', $model ) ) {
        $db->execute("ALTER TABLE tire_models ADD tire_model_image_origin varchar(511) DEFAULT '';");
        $db->execute("UPDATE tire_models SET tire_model_image_origin = tire_model_image;");
    }
}

function tire_model_image_new__column(){

    $db = get_database_instance();

    $models = $db->get_results("SELECT * FROM tire_models LIMIT 0, 1");
    $model = (array) @$models[0];

    if ( ! array_key_exists( 'tire_model_image_new_column', $model ) ) {
        $db->execute("ALTER TABLE tire_models ADD tire_model_image_new varchar(511) DEFAULT '';");
    }
}
