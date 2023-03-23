<?php

list( $items, $content ) = \PS\Cron\get_email_content();

echo nl2br( $content );