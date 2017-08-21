<?php 
/*
 Template Name: WShop - Checkout
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $guessurl = site_url() ){
    $guessurl = wp_guess_url();
}
?>
<!DOCTYPE html>
<html>
	<head>
	<title><?php echo the_title();?></title>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
		<link media="all" type="text/css" rel="stylesheet" href="<?php print WSHOP_URL?>/assets/css/wshop.css?v=<?php echo WShop::instance()->version?>">	
		<script src="<?php echo $guessurl.'/wp-includes/js/jquery/jquery.js'; ?>"></script>
		<style type="text/css">
            body{background: #f2f2f4;font-size: 0.875em;    }
        </style>
	</head>
	<body class="xh-checkouttop">	
	 <?php
	    while ( have_posts() ) : 
	       the_post();
	       the_content();
		// End the loop.
		endwhile;
	 ?>
	</body>
</html>