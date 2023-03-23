<?php


Class Sidebar_Accordion_Item{

	public $title;
	public $body;
	protected $args;

	public function __construct( $args = array() ){
		$this->args = $args;
	}

	public function render(){

		$bind = array();

		$visible = gp_if_set( $this->args, 'visible', false );
		$add_class = gp_if_set( $this->args, 'add_class', '');
		$cls = [ 'sidebar-accordion-item' ];
		$cls[] = $visible ? 'visible' : 'not-visible';
		$cls[] = $add_class ? $add_class : '';

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="ai-header js-bind" data-bind="' . json_encode( $bind ) . '">';
		$op .= '<div class="ai-header-inner">';
		// count checked is for javascript.
		$op .= '<h2 class="title"><span class="text inherit">' . $this->title . '</span><span class="checked-count inherit"></span></h2>';
		$op .= '<span class="arrow"><i class="fa fa-caret-down"></i></span>';
		$op .= '</div>';
		$op .= '</div>';

		$op .= '<div class="ai-body">';
		$op .= '<div class="ai-body-inner">';
		$op .= $this->body;
		$op .= '</div>'; // ai-body-inner
		$op .= '</div>'; // ai-body

		$op .= '</div>'; // sidebar-accordion-item

		return $op;
	}

}