Probably contains scripts to run over command line (you'll likely need to include the file first).

Migrations probably shouldn't be run in production.

Also see core/migrations.php

ie. 
php -a
include './core/_init.php';
include './core/rewrite-urls';
\CW\Migrations::rewrite_urls( 'https://tiresource.com' , 'localhost:8080/tiresource' );