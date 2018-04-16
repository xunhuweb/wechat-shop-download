<?php 
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

$data = WShop_Temp_Helper::clear('atts','templates');
$order = isset($data['order'])&&$data['order'] instanceof WShop_Order?$data['order']:null;
$user = $order->customer_id?get_user_by('id', $order->customer_id):null;
if(!$order){
    return;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo get_option('blogname')?></title>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
	<div id="wrapper" dir="ltr" style="background-color: #f7f7f7; margin: 0; padding: 70px 0 70px 0; -webkit-text-size-adjust: none !important; width: 100%;">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<div id="template_header_image"></div>
					<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important; background-color: #ffffff; border: 1px solid #dedede; border-radius: 3px !important;">
						<tr>
							<td align="center" valign="top">
								<!-- Header -->
								<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header" style='background-color: #96588a; border-radius: 3px 3px 0 0 !important; color: #ffffff; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;'>
									<tr>
										<td id="header_wrapper"
											style="padding: 36px 48px; display: block;">
											<h1 style='color: #ffffff; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: left; text-shadow: 0 1px 0 #ab79a1; -webkit-font-smoothing: antialiased;'><?php echo __('Your purchase is succeed!',WSHOP)?></h1>
										</td>
									</tr>
								</table> <!-- End Header -->
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<!-- Body -->
								<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
									<tr>
										<td valign="top" id="body_content" style="background-color: #ffffff;">
											<!-- Content -->
											<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top" style="padding: 48px;">
														<div id="body_content_inner" style='color: #636363; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 150%; text-align: left;'>
															<h2
																style='color: #96588a; display: block; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 16px 0 8px; text-align: left;'>
																<a class="link"  style="color: #96588a; font-weight: normal; text-decoration: underline;"><?php echo sprintf(__('Order #%s',WSHOP),$order->id)?></a> 
																(<time><?php echo date('Y-m-d H:i',$order->paid_date)?></time>)
															</h2>
															<h2 style='color: #96588a; display: block; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 16px 0 8px; text-align: left;'>下载内容</h2>
                                                            <ul>
                                                            <?php 
                                                            global $wpdb;
                                                            $order_items = $order->get_order_items();
                                                            if($order_items){
                                                                foreach ($order_items as $item){
                                                                    $download = new WShop_Download($item->post_ID);
                                                                    if($download->is_load()){
                                                                        ?>
                                                                	    <li> <span class="text" style='color: #3c3c3c; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;'><?php echo $download->get('downloads')?></span></li>
                                                                	    <?php 
                                                                    }
                                                            	   
                                                            	}
                                                            }?>
                                                            </ul>
														</div>
													</td>
												</tr>
											</table> <!-- End Content -->
										</td>
									</tr>
								</table> <!-- End Body -->
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
