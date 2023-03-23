<?php

Class Icons{

	public function __construct(){}

	/**
	 * Header Logo
	 *
	 * @return string
	 */
	public static function get_logo(){
		ob_start();
		?>
		<!-- xml version="1.0" encoding="utf-8" -->
		<!-- Generator: Adobe Illustrator 19.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
		<?php
		return ob_get_clean();
	}
}