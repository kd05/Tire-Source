<?php

/**
 * A class to help track time and memory usage, and also print
 * it nicely into HTML or CLI.
 *
 * Note: haven't really tested printing in CLI yet (I don't know if it
 * will be very pretty).
 *
 * Class Time_Mem_Tracker
 */
class Time_Mem_Tracker{

    /**
     * Globally stored instances via context, which you
     * may or may not need to use.
     *
     * @var array
     */
    public static $instances = [];

    /**
     * The context, if any, for the globally stored instances.
     *
     * @var null
     */
    public $context;

    /**
     * An array of arrays, containing all the time/mem info.
     *
     * @see breakpoint()
     *
     * @var array
     */
    public $breakpoints = [];

    /**
     * Time_Mem_Tracker constructor.
     * @param null $context
     * @param string $desc
     */
    public function __construct( $context = null, $desc = '__start' ) {
        $this->context = $context;
        $this->breakpoint( $desc );
    }

    /**
     * An optional helper to create, store, and retrieve an instance
     * associated with a $context, globally.
     *
     * @param $context
     * @param string $desc
     * @return mixed
     */
    public static function with_context($context, $desc = "__start" ){

        if ( ! isset( self::$instances[$context] ) ) {
            self::$instances[$context] = new static( $context, $desc );
        }

        return self::$instances[$context];
    }

    /**
     * Adds a snapshot of time and memory usage.
     *
     * @param null $desc
     */
    public function breakpoint( $desc = null ){

        $bp = [
            'desc' => $desc,
            'time' => microtime( true ),
            'mem' => memory_get_usage(),
            'peak_mem' => memory_get_peak_usage(),
        ];

        if ( count( $this->breakpoints ) === 0 ) {

            $bp['delta_time'] = 0;
            $bp['delta_time_total'] = 0;

            $bp['delta_mem'] = 0;
            $bp['delta_mem_total'] = 0;

            $bp['delta_peak_mem'] = 0;
            $bp['delta_peak_mem_total'] = 0;

        } else {

            $first = $this->breakpoints[0];
            $last = $this->breakpoints[count( $this->breakpoints ) - 1];

            $bp['delta_time'] = $bp['time'] - $last['time'];
            $bp['delta_time_total'] = $bp['time'] - $first['time'];

            $bp['delta_mem'] = $bp['mem'] - $last['mem'];
            $bp['delta_mem_total'] = $bp['mem'] - $first['mem'];

            $bp['delta_peak_mem'] = $bp['peak_mem'] - $last['peak_mem'];
            $bp['delta_peak_mem_total'] = $bp['peak_mem'] - $first['peak_mem'];
        }

        $this->breakpoints[] = $bp;
    }

    /**
     * 123123123 => "+123,123,123"
     * 123123123 => "123,123,123"
     *
     * @param $value
     * @param $do_prefix
     * @return string
     */
    public static function _format_mem( $value, $do_prefix ) {
        $pre = ( $do_prefix && $value >= 0 ) ? "+" : "";
        return $pre . number_format( (float) $value, 0, ".", "," );
    }

    /**
     * Formats an element of a single breakpoint for displaying.
     *
     * For "delta" values, it ensures that the value starts with + or -
     *
     * For memory values, adds commas.
     *
     * @param $key
     * @param $value
     * @return string
     */
    public static function _format_key( $key, $value ) {

        // time, differences
        if ( in_array( $key, [
            'delta_time',
            'delta_time_total',
        ] ) ){
            return $value >= 0 ? "+$value" : $value;
        }

        // memory, totals
        if ( in_array( $key, [
            'mem',
            'peak_mem'
        ] ) ){
            return static::_format_mem( $value, false );
        }

        // memory, differences
        if ( in_array( $key, [
            'delta_mem',
            'delta_peak_mem',
            'delta_mem_total',
            'delta_peak_mem_total',
        ] ) ){
            return static::_format_mem( $value, true );
        }

        // I guess, sanitize, I don't know. Shouldn't be necessary, but
        // will be safe instead.
        return is_scalar( $value ) ? htmlspecialchars( $value ) : "__not_scalar";
    }

    /**
     * Formats all elements of a breakpoint so that they are ready for printing.
     *
     * @param $breakpoint
     * @return mixed
     */
    public static function _format_breakpoint( array $breakpoint ) {
        foreach ( $breakpoint as $k=>$v ) {
            $breakpoint[$k] = self::_format_key($k, $v );
        }
        return $breakpoint;
    }

    /**
     * Dumb helper function.
     *
     * @param $breakpoints
     * @return mixed
     */
    public static function _format_breakpoints( array $breakpoints ) {
        return array_map( function( $bp ) {
            return self::_format_breakpoint( $bp );
        }, $breakpoints );
    }

    /**
     * Prints an array of items using string padding to mimic table columns.
     *
     * @param array $items
     * @param int $pad_length
     * @param string $pad_string
     * @return string
     */
    public static function _print_row( array $items, $pad_length = 20, $pad_string = " " ) {
        return implode( " ", array_map( function( $item ) use( $pad_length, $pad_string ){
            return str_pad( $item, $pad_length, $pad_string, STR_PAD_RIGHT );
        }, $items ) );
    }

    /**
     * Displays the last breakpoint added (or the first)
     *
     * @param string $after
     * @return string
     */
    public function display_last_item_summary( $after = "<br>" ){

        $last = @$this->breakpoints[count( $this->breakpoints ) - 1];

        if ( $last ) {
            $_last = self::_format_breakpoint( $last );

            $items = [
                // don't need time formatted with + here
                $last['delta_time_total'] . ' seconds',
                $_last['mem'] . ' / ' . $_last['peak_mem'] . ' bytes',
            ];

            return "[time, mem/peak_mem]: " . implode( $items, " _ _ _ " ) . $after;
        }

        return "";
    }

    /**
     * Dumps all information, which is probably a lot more than you need
     * most of the time. Recommend using display_summary most of the time.
     *
     * @param bool $html
     * @return string
     */
    public function display_everything( $html = true ) {

        $bps = static::_format_breakpoints( $this->breakpoints );

        $ret = '';

        $br = $html ? '<br>' : "\r\n";

        if ( $html ) {
            $ret .= $this->html_pre_tag()[0];
        }

        $cols = [
            'desc',
            'time',
            'delta_time',
            'delta_time_total',
            'mem',
            'delta_mem',
            'delta_mem_total',
            'peak_mem',
            'delta_peak_mem',
            'delta_peak_mem_total'
        ];

        $ret .= static::_print_row( $cols );
        $ret .= $br;

        $ret .= implode( $br, array_map( function( $bp ) use( $cols ){

            $row = array_map( function( $col ) use( $bp ) {
                return @$bp[$col];
            }, $cols );

            return self::_print_row( $row );
        }, $bps ) );

        if ( $html ) {
            $ret .= $this->html_pre_tag()[1];
        }

        return $ret;
    }

    /**
     * You can extend and change this if needed. Must return an array of
     * length 2.
     *
     * @return array
     */
    public function html_pre_tag(){
        return [
            '<pre style="white-space: pre-wrap; font-family: monospace, monospace;">',
            '</pre>'
        ];
    }

    /**
     * Displays a summary of all the breakpoints. Probably the most
     * useful function to call, in most cases.
     *
     * @param bool $html
     * @return string
     */
    public function display_summary( $html = true ){

        $ret = '';

        $br = $html ? '<br>' : "\r\n";

        if ( $html ) {
            $ret .= $this->html_pre_tag()[0];
        }

        $ret .= static::_print_row( [ htmlspecialchars( $this->context ), "Time", "Mem", "Peak Mem" ] );
        $ret .= $br;

        // format all values
        $breakpoints = self::_format_breakpoints( $this->breakpoints );

        $first = @$breakpoints[0];
        $last = @$breakpoints[count( $breakpoints ) - 1];

        if ( $first ) {
            $ret .= static::_print_row( [
                "START",
                $first['time'],
                $first['mem'],
                $first['peak_mem']
            ]);
        }

        $ret .= $br;

        // show delta time for all rows except first
        foreach ( $breakpoints as $count => $bp ) {
            if ( $count > 0 ) {

                $ret .= static::_print_row( [
                    $bp['desc'],
                    $bp['delta_time'],
                    $bp['delta_mem'],
                    $bp['peak_mem']
                ]);

                $ret .= $br;
            }
        }

        if ( $last ) {
            // last row totals
            $ret .= static::_print_row( [
                "END",
                $last['time'],
                $last['mem'],
                $last['peak_mem']
            ]);

            $ret .= $br;

            $ret .= static::_print_row( [
                "TOTAL_DIFF",
                $last['delta_time_total'],
                $last['delta_mem_total'],
                $last['delta_peak_mem_total']
            ]);
        }

        if ( $html ) {
            $ret .= $this->html_pre_tag()[1];
        }

        return $ret;
    }
}
