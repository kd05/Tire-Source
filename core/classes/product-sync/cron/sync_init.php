<?php

use PS\Cron as Cron;

Cron\init_cron_state();

Cron_Helper::$merge_into_log_after['state'] = Cron\get_cron_state();