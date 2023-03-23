<?php

Header::$title = "Privacy Policy";
Header::$canonical = get_url( 'privacy_policy' );

cw_get_header();

//  <a href="mailto: sales@email_removed.com">sales@email_removed.com</a>
?>
	<div class="page-wrap page-privacy-policy">
		<?php echo get_top_image( array(
			'title' => 'Privacy Policy',
			'img' => get_image_src( 'iStock-172668228-wide-lg.jpg' ),
			'overlay_opacity' => 70,
		)); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container general-content">
				<?php echo get_policy_sub_nav(); ?>
                <?php
                // hard coding these..
                // echo gp_render_textarea_content( cw_get_option( 'content_privacy_policy' ) );
                ?>
				<p>By visiting this site, you are accepting the practices described in this Privacy Notice. tiresource.COM knows that you care how information about you is used and shared. We believe that your personal information deserves protection. While you can visit the site without registering or providing any personal information, you will need to provide the necessary information if you want to make a purchase.</p>
				<p>What does tiresource.COM do with your personal information?</p>
				<ul>
					<li>Build your account.</li>
					<li>Process your orders.</li>
				</ul>
				<p>tiresource.COM will store customer information if they chose to open an account. This information will be used for customers to validate their purchases. We will not share customer information with any third parties. In addition we have used the most advanced techniques to secure our website, protect your information and ensure a secure transaction.</p>
				<p>By using our site you are allowing the cookie placement of third parties on your browser for targeted advertising purposes on our site, and sites across the internet. This data may be used by third parties for the advertising of our product based on your activity on our site, for a more personalized browsing experience. You can opt-out of this service, by going to the Network Advertising Initiative site at networkadvertising.org/choices.</p>
			</div>
		</div>
	</div>

<?php

cw_get_footer();
