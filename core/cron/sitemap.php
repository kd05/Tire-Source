<?php

$sitemap = new App_Sitemap();;
$sitemap->build();
$sitemap->write_to_root_directory();

Cron_Helper::$merge_into_log_after['counts'] = $sitemap->export_counts();
