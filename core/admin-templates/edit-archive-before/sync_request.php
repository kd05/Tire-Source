<?php

echo DB_Table::get_archive_page_filters_form( 'sync_request', [
    'sync_key',
    'type',
    'supplier',
    'locale'
]);

