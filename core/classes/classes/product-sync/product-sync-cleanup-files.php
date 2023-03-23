<?php

namespace PS\CleanupFiles;

/**
 * Deletes older sync requests files to clear up more space.
 *
 * @return void
 */
function delete_old_sync_request_files(){

    $now = time();
    $base = LOG_DIR . '/sync-requests';
    // 30 days
    $limit = 60 * 60 * 24 * 30;
    $count = 0;

    if ($handle = opendir($base)) {

        while (false !== ($entry = readdir($handle))) {

            if ( $entry === '.' || $entry === '..' ) {
                continue;
            }

            $path = $base . '/' . $entry;

            if ( is_dir( $path ) ) {
                if ( $filetime = filemtime( $path ) ) {
                    if ( $now - $filetime > $limit ) {
                        rmrf( $path );
                        // just assume it was successful
                        $count++;
                    }
                }
            }
        }

        closedir($handle);
    }
}

/**
 * Delete old price update logs to clear up space
 *
 * @return void
 */
function delete_old_price_update_files() {

    $now = time();
    // 30 days
    $limit = 60 * 60 * 24 * 30;
    $count = 0;

    foreach (glob(LOG_DIR . '/price-updates/*.json') as $filename) {
        if ( $filetime = filemtime( $filename ) ) {
            if ( $now - $filetime > $limit ) {
                if ( unlink( $filename ) ) {
                    $count++;
                }
            }
        }
    }
}

/**
 * Delete old files from inventory processes... inventory is a separate module from
 * product sync, but it's convenient to just have the function here and run it at
 * the same time as other product sync code.
 *
 * @return void
 */
function delete_old_inventory_files() {

    $now = time();

    if ( IS_WFL ) {
        $limit = 60 * 60 * 24 * 10;
    } else {
        $limit = 60 * 60 * 24 * 30;
    }

    $count = 0;

    foreach (glob(ADMIN_UPLOAD_DIR . '/inventory/*.csv') as $filename) {
        if ( $filetime = filemtime( $filename ) ) {
            if ( $now - $filetime > $limit ) {
                if ( unlink( $filename ) ) {
                    $count++;
                }
            }
        }
    }
}

/*
 * recursively force delete a directory,
 *
 * fails if directory contains files starting with . (ie. .htaccess)
 * apparently. Seems that this will not be an issue for us.
 *
 * https://stackoverflow.com/a/53313238/7220351
 *
 * @param string $dir the directory name
 */
function rmrf($dir) {

    // haven't tested this line
    // (for example, does it even work with relative paths? I don't know)
    if (empty($dir) || $dir === '/') {
        return;
    }

    foreach (glob($dir) as $file) {
        if (is_dir($file)) {
            rmrf("$file/*");
            rmdir($file);
        } else {
            unlink($file);
        }
    }
}
