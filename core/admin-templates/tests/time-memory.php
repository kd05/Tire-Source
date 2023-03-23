<?php

$skip = gp_if_set( $_GET, 'skip' ) == 1;

if ( ! $skip ) {
	ini_set('memory_limit','128M');
	set_time_limit( 1800 );

	echo "SETTING MEMORY AND TIME LIMITS....128M memory_limit, 1800 time limit<br>";
}

echo '<div class="general-content">';
echo wrap_tag( 'setting up time and memory variables at runtime and seeing if they have an effect. Use &skip=1 to not adjust them.' );
echo '</div>';

phpinfo();

