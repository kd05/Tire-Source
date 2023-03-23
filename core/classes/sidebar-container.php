<?php

/**
 * Class Sidebar_Section
 */
Class Sidebar_Container{

	public $left;
	public $right;

	// optional top content
	public $top;
	public $add_class;
	public $left_class;
	public $right_class;

	/**
	 *
	 */
	public function render(){

		ob_start();

		$cls = array( 'sidebar-container' );
		$cls[] = $this->add_class;
		$cls[] = $this->top ? 'has-top' : 'no-top';

		$left_cls = array( 'sb-left' );
		$left_cls[] = $this->left_class;

		$right_cls = array( 'sb-right' );
		$right_cls[] = $this->right_class;

		?>
		<div class="<?php echo gp_parse_css_classes( $cls ); ?>">

            <?php if ( $this->top ) { ?>
                <div class="sb-top">
                    <div class="sb-top-2">
                        <?php echo $this->top; ?>
                    </div>
                </div>
            <?php } ?>

			<div class="<?php echo gp_parse_css_classes( $left_cls ); ?>">
				<div class="sb-left-2">
					<?php echo $this->left ?>
				</div>
			</div>
			<div class="<?php echo gp_parse_css_classes( $right_cls ); ?>">
				<div class="sb-right-2">
					<?php echo $this->right ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}