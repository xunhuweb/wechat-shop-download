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
	<title><?php the_title();?></title>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
		<link media="all" type="text/css" rel="stylesheet" href="<?php print WSHOP_URL?>/assets/css/wshop.css?v=<?php echo WShop::instance()->version?>">	
		<script src="<?php echo $guessurl.'/wp-includes/js/jquery/jquery.js'; ?>"></script>
		<script src="<?php echo WSHOP_URL.'/assets/js/jquery-loading.min.js'; ?>"></script>
		<script type="text/javascript">
		<?php foreach (WShop::instance()->get_js_params() as $key=>$datas){
		    ?>
		    var <?php echo $key?> = <?php echo json_encode($datas)?>;
		    <?php 
		}?>
		</script>
		<script src="<?php echo WSHOP_URL.'/assets/js/wshop.js'; ?>"></script>
		<link media="all" type="text/css" rel="stylesheet" href="<?php print WSHOP_URL?>/assets/css/jquery.loading.min.css">
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
		
		 echo WShop::instance()->WP->requires(WSHOP_DIR, '__scripts.php');
	 ?>
	 
	</body>
</html>