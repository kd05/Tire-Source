<?php

//use Thunder\Shortcode\Event\FilterShortcodesEvent;
//use Thunder\Shortcode\EventContainer\EventContainer;
//use Thunder\Shortcode\Events;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Use a shortcode parser to build an array of data, even though the parser itself is more meant to
 * simply find and replace shortcodes from within a string, and only returns string results. by iterating
 * through the shortcodes and ignoring their string output, we can track the data in the correct order so that
 * we can build an array of data, and then later loop through that array to print html.
 *
 * Class Gallery_Shortcode_Parser
 */
Class Gallery_Shortcode_Parser{

	public static $handler;
	public static $processor;

	public static $current_item;
	public static $result;

	public function __construct(){

		self::$result = array();

		self::$handler = new HandlerContainer();
		self::$handler->add( 'gallery_item', array( $this, 'gallery_item_handler' ) );
		self::$handler->add( 'image', array( $this, 'image_handler' ) );
		self::$handler->add( 'caption', array( $this, 'caption_handler' ) );

		// if auto process content is true, things will not trigger in the correct order
		// and everything will break;
		$processor = new Processor(new RegularParser(), self::$handler);
		$processor = $processor->withAutoProcessContent( false );
		self::$processor = $processor;
	}

	/**
	 * @param $string
	 */
	public function process( $string ) {
		self::$processor->process( $string );
	}

	/**
	 * add the image to the (dynamic) "current item"
	 *
	 * @param ShortcodeInterface $s
	 */
	public static function image_handler( ShortcodeInterface $s ){
		self::$current_item['image'] = trim( $s->getContent() );
	}

	/**
	 * add the caption to the (dynamic) "current item"
	 *
	 * @param ShortcodeInterface $s
	 */
	public static function caption_handler( ShortcodeInterface $s ){
		self::$current_item['caption'] = trim( $s->getContent() );
	}

	/**
	 * empty the current item, process the content to apply images/captions to the current
	 * item, then append the current item to the result.
	 */
	public static function gallery_item_handler( ShortcodeInterface $s ){
		self::$current_item = array();
		self::$processor->process( $s->getContent() );
		self::$result[] = self::$current_item;
	}
}

/**
 * gets the content stores in the options table, then uses the gallery
 * shortcode parser to turn it into an array.
 */
function get_gallery_items_array(){

	$ret = array();
	$option = DB_Option::get_instance_via_option_key( 'gallery_content' );

	if ( $option ) {
		$parser = new Gallery_Shortcode_Parser();
		$parser->process( $option->get_and_clean( 'option_value' ) );
		$ret = $parser::$result;
	}

	return $ret;
}

//$h = new Gallery_Shortcode_Parser();
//$h->process( $test );
//echo '<pre>' . print_r( $h, true ) . '</pre>';



/**
 * repeating the same idea from Gallery_Shortcode_Parser. These 2 things are almost identical.
 * I would like to generalize it, but for certain reasons its not quite as easy as it should be.
 *
 * Class Faq_Shortcode_Parser
 */
Class Faq_Shortcode_Parser{

	public static $handler;
	public static $processor;

	public static $current_item;
	public static $result;

	public function __construct(){

		self::$result = array();

		self::$handler = new HandlerContainer();
		self::$handler->add( 'faq', array( $this, 'faq_handler' ) );
		self::$handler->add( 'q', array( $this, 'q_handler' ) );
		self::$handler->add( 'a', array( $this, 'a_handler' ) );

		// if auto process content is true, things will not trigger in the correct order
		// and everything will break;
		$processor = new Processor(new RegularParser(), self::$handler);
		$processor = $processor->withAutoProcessContent( false );
		self::$processor = $processor;
	}

	/**
	 * @param $string
	 */
	public function process( $string ) {
		self::$processor->process( $string );
	}

	/**
	 * @param ShortcodeInterface $s
	 */
	public static function q_handler( ShortcodeInterface $s ){
		self::$current_item['question'] = trim( $s->getContent() );
	}

	/**
	 * @param ShortcodeInterface $s
	 */
	public static function a_handler( ShortcodeInterface $s ){
		self::$current_item['answer'] = trim( $s->getContent() );
	}

	/**
	 * empty the current item, process the content to apply images/captions to the current
	 * item, then append the current item to the result.
	 */
	public static function faq_handler( ShortcodeInterface $s ){
		self::$current_item = array();
		self::$processor->process( $s->getContent() );
		self::$result[] = self::$current_item;
	}
}

/**
 * gets the content stores in the options table, then uses the gallery
 * shortcode parser to turn it into an array.
 */
function get_faq_items_array(){

	$ret = array();
	$option = DB_Option::get_instance_via_option_key( 'faq_content' );

	if ( $option ) {
		$parser = new Faq_Shortcode_Parser();
		// note: use $option->get() but don't clean. html is valid for answers.
		$parser->process( $option->get( 'option_value' ) );
		$ret = $parser::$result;
	}

	return $ret;
}
