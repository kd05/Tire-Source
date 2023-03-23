<?php

use PS\Cron as Cron;

// Cron_Helper::$merge_into_log_after['state_before'] = Cron\get_cron_state();

Cron\cron_state_do_next();

Cron_Helper::$merge_into_log_after['state_after'] = Cron\get_cron_state();
