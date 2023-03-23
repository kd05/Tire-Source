<?php

Header::$title = "Home";
Header::$canonical = get_url( 'home' );

// Shows the "vehicle/tire" nav on the right side of the header
global $show_shop_nav;
$show_shop_nav = true;

cw_get_header();

$home_top_image  = get_image_src( cw_get_option( 'home_top_image' ) );
$home_top_video  = get_video_src( cw_get_option( 'home_top_video' ) );
$home_video_type = 'video/mp4';

?>
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "What kind of products does tiresource.COM sell?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "We offer one of the largest inventories of wheels, tires and packages. All the wheels sold on this website are custom and designer wheels, also known as after-market wheels."
    }
  },{
    "@type": "Question",
    "name": "Does tiresource.COM sell OEM wheels?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "tiresource.COM does not sell OEM (original equipment manufacturer) wheels. However, we offer replica wheels with OEM designs and specifications."
    }
  },{
    "@type": "Question",
    "name": "How do I know that the tires/wheels I choose will fit my vehicle?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "To the best of our knowledge, the tiresource.COM vehicle make and model search is accurate. Please be aware that if you are installing aftermarket wheels, and/or changing tire sizes you are customizing your vehicle, therefore, they may not fit exactly the same as the factory settings. We guarantee that the products you have purchased will fit your unmodified vehicle, meaning the following:

1. Rims: will bolt on and will not interfere on OEM brakes, suspension, or body components. Rims that require hub centric rings are still considered a correct fitment.

2. Tires: will fall within the approved overall tire diameter, load rating, (and speed rating for non-winter tires).

3. Rim and Tire Packages: The package will bolt on and will not interfere on OEM brakes, suspension, or body components. When you place an order, you must include as much detailed vehicle information as possible. Correct tire and wheel information can usually be found on the inside of the driver’s side door on a sticker."
    }
  },{
    "@type": "Question",
    "name": "Are the posted prices in U.S. or Canadian dollars?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "The prices will be the same as your shipping region. To verify or change your shipping region, look for the flag icons at the top right of your screen."
    }
  },{
    "@type": "Question",
    "name": "How are your wheels packaged?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Wheels are sealed in a box with a cloth cover or a foam sheet on both sides. Further they are wrapped in a plastic bag and packaged professionally to avoid any damage. Our products are delivered with the highest standards."
    }
  },{
    "@type": "Question",
    "name": "Where do you ship in US/Canada",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "We chose FedEx, Purolater and Canpar as our reliable partners for shipping purposes. We ship to anywhere as long as FedEx, Purolater or Canpar can deliver to your address. Note that if for any reason we are unable to ship the product to your final destination, a representative will contact you within 1-2 business days with an explanation and your options to either proceed or cancel the order."
    }
  },{
    "@type": "Question",
    "name": "How do you calculate associated shipping costs?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "The price of shipping any product from tiresource.COM depends on the location of the recipient, the location the product is shipped from as well as the weight and size of the order. Each order's shipping charges are calculated at the time of the purchase and prominently displayed before each order is finalized. You may choose to ship your order to your home address (billing address) or an installer of your choice if available in your area. Please note that your order is your responsibility if shipped to an address other than your own. We do not ship internationally."
    }
  },{
    "@type": "Question",
    "name": "Can I cancel my order?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "If you cancel your order any time prior to shipping, we reserve the right to charge you a re-stocking fee of 15% on the product only. If your order has been shipped, and it is in transit to you, please contact us immediately. We will try to intercept your order and have it returned to us, but please be advised that the credit for the order will be issued as per our Return/Refund Policy and you will be charged for incurred shipping charges both directions."
    }
  },{
    "@type": "Question",
    "name": "Can I purchase an odd quantity of wheels or tires?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Yes, you can buy an odd number or less than 4 wheels/rims on many brands. However, some of our wheels can only be sold as a set of four. If you are concerned, please contact us before placing your order. Note, however, you may order as many tires as you need."
    }
  },{
    "@type": "Question",
    "name": "I am in Canada. Do I have to pay any duties or other charges when I receive my shipment?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "All tires and wheels are sourced from our Canadian distributors. There are no duties or brokerage fees charged on Canadian orders delivered in Canada, or charged to you upon delivery. You will be charged the appropriate GST, HST, PST as described on the payment page."
    }
  },{
    "@type": "Question",
    "name": "I am in the U.S. Do I have to pay any duties or other charges when I receive my shipment?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "All U.S. orders will be sourced from our U.S. distributors. Therefore, there are no duties charged."
    }
  },{
    "@type": "Question",
    "name": "What if my order arrives and no one is at home to receive it?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Every order with a value over $50, requires a signature on delivery (no safe drop option). Our shipping providers will generally attempt delivery to a residential address three times. Each time they attempt delivery, they will leave a slip on the door indicating the delivery attempt. After three attempts they will contact you and usually the order will then have to be picked up at the closest depot."
    }
  },{
    "@type": "Question",
    "name": "Is my tire and wheel package mounted and balanced before shipping? Is hardware included?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "For Canadian orders, if you are buying a wheel and tire package, you will have the option to mount the tires on to the wheels including Hunter Road Force balancing at an additional cost. For an additional charge an OPTIONAL Wheel Installation Kit (NUTS or BOLTS) is included in your package, which comes with either nuts or bolts (depending on vehicle), hub centric rings (if applicable) and valve stems. We wrap your package in specially designed packaging to ensure safe delivery. If you buy wheels only, they are shipped with the center caps only (if applicable). You do have the option however, to purchase the installation kit at an extra cost.

For U.S. customers, we do not offer a mount and balance option."
    }
  },{
    "@type": "Question",
    "name": "How long before my order is shipped?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "The amount of time required to process your order and ship it depends on the product purchased, if the product is in stock, and where you are located. During our busiest seasons (fall and spring), order processing time may take up to an additional 5-7 business days. If a product ordered is not in stock and needs to be sourced out, please add 7-10 business days.

- Tire or wheels: we expect 2-5 business days.
- Wheel and Tire Package: we expect approximately 3-5 business days.

Unfortunately there are some instances where the order can be delayed beyond the above terms. We will contact you by email with any updates. Once the order is ready to be shipped, you will receive tracking numbers via email."
    }
  }]
}
</script>

    <div class="page-wrap home-page">
        <div class="home-top">
            <div class="mobile-indicator"></div>
            <div class="background-image standard">
                <div class="img-tag-cover inherit-size">
                    <img src="<?= $home_top_image; ?>" alt="Click It Wheels for wheels and tires canada">
                    <div class="overlay" style="opacity: 0.52"></div>
                </div>
            </div>
			<?php if ( $home_top_video ) { ?>
                <div class="video-wrapper">
                    <video tabindex="-1" aria-hidden="true" autoplay loop muted>
                        <source src="<?php echo $home_top_video; ?>"
                                type="<?php echo $home_video_type; ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
			<?php } ?>
            <div class="shop-container">
                <div class="wide-container">
                    <div class="wide-container-inner">
                        <div class="shop-left">
                            <div class="shop-left-2 general-content color-white">
                                <h1 class="like-h1-xxl"><?php echo cw_get_option( 'home_top_title' ); ?></h1>
                            </div>
                        </div>
                        <div class="shop-right">
                            <div class="shop-nav">
                                <div class="shop-btn shop-vehicle active">
                                    <button class="css-reset">Vehicle</button>
                                </div>
                                <div class="shop-btn shop-tire">
                                    <button class="css-reset">Tire</button>
                                </div>
                            </div>
                            <div class="by-vehicle">
                                <?php

                                $banner_text = app_is_locale_canada_otherwise_force_us() ? 'Free Shipping <br>Across Canada' : 'Free Shipping <br>Across The U.S.';
                                // $banner_text = $banner_text ? get_anchor_tag_simple( get_url( 'shipping_policy'  ), $banner_text ) : '';
                                $details = '<p class="small"><a class="lb-trigger" data-for="home-shipping" href="' . get_url( 'shipping_policy' ) . '">See our shipping policy</a></p>';
                                // $banner_text = ''; // this just looks bad but.. need to put this in somehow.

                                $before = '<div class="general-content">';
                                $after = '</div>';
                                echo get_general_lightbox_content( 'home-shipping', wrap( get_shipping_policy_html( true ), $before, $after ), array(
                                    'add_class' => 'general-lightbox embed-page',
                                ));

                                if ( $banner_text  ) {
                                    ?>
                                    <div class="banner">
                                        <p class="main"><?php echo $banner_text ; ?></p>
                                        <?php echo $details; ?>
                                    </div>
                                    <?php
                                }
                                ?>
								<?php echo get_vehicle_lookup_form( array(
									'title' => 'Shop By Vehicle',
									'page' => 'packages'
								) ); ?>
                            </div>
                            <div class="by-tire hidden">
								<?php echo tires_by_size_form( array(
									'title' => 'Shop Tires'
								) ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php echo Components::grey_bar( array( 'add_class' => 'after-home-top' ) ); ?>
        <div class="home-image-nav">
            <div class="top-content">
                <div class="background-image standard">
                    <div class="img-tag-cover inherit-size">
                        <img src="<?= get_image_src( 'rims-car-1-lg.jpg' ); ?>" alt="Rims and Tires in Canada at Click It Wheels">
                        <span class="overlay" style="opacity: 0.6"></span>
                    </div>
                </div>
                <div class="wide-container">
                    <div class="flex">
                        <div class="left">
                            <div class="left-inner general-content color-white">
                                <h2 class="like-h1-xxl not-mobile">Time For<br> An Upgrade</h2>
                                <h2 class="like-h1-xxl mobile">Time<br> For An<br> Upgrade</h2>
                            </div>
                        </div>
                        <div class="right">
                            <div class="right-inner">
                                <div class="item item-1">
                                    <a href="<?php echo get_url( 'tires' ); ?>" class="item-inner">
                                        <div class="img-tag-cover inherit-size">
                                            <img src="<?= get_image_src( 'closeup-tire.jpg' ); ?>" alt="Best Tires at Click It Wheels">
                                        </div>
                                        <span class="link-text">Shop Tires <i class="fa fa-angle-right"></i></span>
                                    </a>
                                </div>
                                <div class="item item-2">
                                    <a href="<?php echo get_url( 'rims' ); ?>" class="item-inner">
                                        <div class="img-tag-cover inherit-size">
                                            <img src="<?= get_image_src( 'alloy-rims-1-sm.jpg' ); ?>" alt="Aftermarket Wheels and Tires">
                                        </div>
                                        <span class="link-text">Shop Rims <i class="fa fa-angle-right"></i></span>
                                    </a>
                                </div>
                                <div class="item item-3">
                                    <a href="<?php echo get_url( 'packages' ); ?>" class="item-inner">
                                        <div class="img-tag-cover inherit-size">
                                            <img src="<?= get_image_src( 'rims-car-1-tall-sm.jpg' ); ?>" alt="Shop Aftermarket Rims and Tires">
                                        </div>
                                        <span class="link-text">Shop Packages <i class="fa fa-angle-right"></i></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bottom-spacer"></div>
        </div>
        <div class="full-width-image after-image-nav">
			<?php
			$image   = cw_get_option( 'home_middle_banner', 'ruffino-wheels.jpg' );
			$img_url = get_image_src( $image );
			?>
            <img src="<?php echo get_image_src( $img_url ); ?>" alt="Ruffino Wheels at Click It Wheels">
        </div>
		<?php
		$content = '';

		$title   = cw_get_option( 'home_bottom_title' );
		$content = cw_wpautop( cw_get_option( 'home_bottom_content' ) );

		?>
		<?php echo Components::get_split_row( array(
			'image1' => 'iStock-855180088-wide-lg.jpg',
			'image2' => 'rim-transparent.png',
			'image1alt' => 'Wheels and Tires Canada',
			'image2alt' => 'Ruffino Wheels',
			'title_content' => '<h2 class="like-h1-xxl left-border-red">' . $title . '</h2>',
			'content' => $content,
		) );
		?>
        <style>
            .page_home .sr-right-inner{
                max-width: 760px !important;
            }
        </style>
        <div class="main-content">
            <div class="container">
                <div class="general-content">
                    <div class="faq-items">
                        <div class="faq-controls all-hidden">
                            <button class="css-reset expand-all">[Expand All]</button>
                            <button class="css-reset collapse-all">[Collapse All]</button>
                        </div>
                        <div class="faq-item hidden">
                            <div class="question">
                                <h3 class="question-2">What Products Does Click It Wheels Have?</h3>
                            </div>
                            <div class="answer">
                                <div class="answer-2 general-content">
                                    <p>Click It Wheels is your destination for the latest wheels and tires available in the market. Saving your time on the search, we offer a vast selection of branded <a href="<?= get_url( 'wheels' ); ?>">rims</a>, <a href="<?= get_url( 'tires' ); ?>">tires</a>, and <a href="<?= get_url( 'packages' ); ?>">wheel and tire packages</a> with an online purchase facility in Canada and the USA.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item hidden">
                            <div class="question">
                                <h3 class="question-2">Could you suggest the best in Canada tires and wheels for my car model?</h3>
                            </div>
                            <div class="answer">
                                <div class="answer-2 general-content">
                                    <p>To make the best of your search, go with the most precise recommendations from the ‘Vehicle Make And Model’ search function.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item hidden">
                            <div class="question">
                                <h3 class="question-2">To what radius do you ship in The US and Canada?</h3>
                            </div>
                            <div class="answer">
                                <div class="answer-2 general-content">
                                    <p>As far as our delivery partners - FedEx, Purolator, and Canpar can reach. We ship anywhere in the US and Canada. Under any circumstance, if the product is not delivered to your doorstep, our executive will reach out to you (within 2 business days) with a valid explanation and the option to proceed or cancel the order.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item hidden">
                            <div class="question">
                                <h3 class="question-2">How much is the shipping cost on online delivery?</h3>
                            </div>
                            <div class="answer">
                                <div class="answer-2 general-content">
                                    <p>Shipping is “Free Of Cost” across Canada and the US. Moreover, our executives are always there to assist you even with the slightest inconvenience related to the time or location of the delivery. We ship to your address (billing address) or the installer of your choice.</p>
                                    <p>Please note that the package is your responsibility if shipped to an address other than your own.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item hidden">
                            <div class="question">
                                <h3 class="question-2">How long does it take to ship the online order?</h3>
                            </div>
                            <div class="answer">
                                <div class="answer-2 general-content">
                                    <p>We try to ensure that every order reaches its destination within 5 working days. But uncertainty prevails, and it can get delayed due to circumstances like overcrowding or out-of-stock products. As soon as the order exits the facility, you will get the order tracking number via email.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item hidden">
                            <div class="question">
                                <h3 class="question-2">Is my tire and wheel package mounted and balanced before shipping? Is hardware included?</h3>
                            </div>
                            <div class="answer">
                                <div class="answer-2 general-content">
                                    <p>Mount and balance options are only available for orders within Canada. You can get this facility at an additional cost. Or you can get the on-demand “Wheel installation kit” with your order. The kit has everything you need, from nuts and bolts (as per vehicle model) to hub-centric rings and valve stems.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php

cw_get_footer();
