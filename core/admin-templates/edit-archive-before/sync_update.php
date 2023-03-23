<?php

echo DB_Table::get_archive_page_filters_form( 'sync_update', [
    'sync_key',
    'type',
    'supplier',
    'locale'
]);
