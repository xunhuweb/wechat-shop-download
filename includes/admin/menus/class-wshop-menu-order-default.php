<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * @since 1.0.0
 * @author ranj
 */
class WShop_Menu_Order_Default extends Abstract_WShop_Settings_Menu{
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;
    
    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * 菜单初始化
     *
     * @since  1.0.0
     */
    private function __construct(){
        $this->id='menu_order_default';
        $this->title=__('Orders',WSHOP);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_WShop_Settings_Menu::menus()
     */
    public function menus(){
        return apply_filters("wshop_admin_menu_{$this->id}", array(
            WShop_Menu_Order_Default_Settings::instance()
        ));
    }
}

class WShop_Menu_Order_Default_Settings extends Abstract_WShop_Settings {
    /**
     * @var WShop_Menu_Order_Default_Settings
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }

    private function __construct(){
        $this->id='menu_order_default_settings';
        $this->title=__('Orders',WSHOP);
      
    }

    public function admin_form_start(){}
     
    public function admin_options(){  
        ?>
        	<script type="text/javascript">
    			(function($){
    				window.wshop_view ={
    					delete:function(id){
    						if(confirm('<?php echo __('Are you sure?',WSHOP)?>'))
    						this._update_order(id,'<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'delete'),true,true)?>');
    					},
    					complete:function(id){
    						this._update_order(id,'<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'mark_complete'),true,true)?>');
    					},
    					restore:function(id){
    						this._update_order(id,'<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'restore'),true,true)?>');
    					},
    					trash:function(id){
    						this._update_order(id,'<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'trash'),true,true)?>');
    					},
    					_update_order:function(order_id,ajax_url){
    						if(!ajax_url){
    							return;
    						}
    						
    						$('#wpbody-content').loading();
    						$.ajax({
    							url:ajax_url,
    							type:'post',
    							timeout:60*1000,
    							async:true,
    							cache:false,
    							data:{
    								id:order_id
    							},
    							dataType:'json',
    							complete:function(){
    								$('#wpbody-content').loading('hide');
    							},
    							success:function(e){
    								if(e.errcode!=0){
    									alert(e.errmsg);
    									return;
    								}
    								
    								location.reload();
    							},
    							error:function(e){
    								console.error(e.responseText);
    								alert('<?php echo esc_attr( 'System error while modifing order!', WSHOP); ?>');
    							}
    						});
    					}
					};
			})(jQuery);
		</script>
        <?php 
    	   if(isset($_GET['view'])&&$_GET['view']=='edit'){
    	       $view = new WShop_Order_Edit_View($this);
    	       $view->view();
    	   }else{
    	       ?>	
	           	<div class="wrap">
	           		<h2>
	           			<?php echo __( 'Orders', WSHOP );?>
	           			
	           		</h2>
	           		
	           		 <style type="text/css">
                        .column-status{width:45px;text-align:center;}
                        .manage-column.column-status{width:45px;text-align:center;}
                        .column-ID{width: 15%;}
                        .column-order_date{width: 9%;}
                        .column-total{width: 9%;}
                        .column-toolbar{width: 9%;}
                   </style>
	           		<?php
	           
	           		$table = new WShop_Order_List_Table($this);
	           		$table->process_action();
	           		$table->views();
	           		$table->prepare_items();
	           		?>
	           		
       			<form method="post" id="form-wshop-order">
       			   <input type="hidden" name="page" value="<?php echo WShop_Admin::instance()->get_current_page()->get_page_id()?>"/>
                   <input type="hidden" name="section" value="<?php echo WShop_Admin::instance()->get_current_menu()->id?>"/>
                   <input type="hidden" name="tab" value="<?php echo WShop_Admin::instance()->get_current_submenu()->id?>"/>
	           		<div class="order-list" id="wshop-order-list">
	           		<?php $table->display(); ?>
	           		</div>
	       		</form>
	       		</div>
	       		
              <?php 
    	   }
    	
	}
	
    public function admin_form_end(){} 
}

class WShop_Order_Edit_View{
    /**
     * 
     * @var WShop_Menu_Order_Default_Settings
     */
    private $api;
    /**
     * @var WShop_Order
     */
    private $current_order;
    public function __construct($api){
        $this->api = $api;
        $this->current_order = WShop::instance()->payment->get_order('id', isset($_GET['id'])?sanitize_key($_GET['id']):null);
    }
    
    public function view(){
        if(!$this->current_order){
            WShop::instance()->WP->wp_die(WShop_Error::error_custom(__('Order is not found!',WSHOP)),false,false);
            return;
        }
        ?>
            <h1 class="wp-heading-inline"><?php echo __('Edit order',WSHOP)?></h1>
            <hr class="wp-header-end">
         
            <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
            <div id="postbox-container-1" class="postbox-container">
            <div id="side-sortables" class="meta-box-sortables ui-sortable">
            
                <div id="wshop-order-actions" class="postbox ">
                   <h2 class="hndle ui-sortable-handle"><span><?php echo __('Order actions',WSHOP)?></span></h2>
                    <div class="inside">
                		<ul class="order_actions submitbox">
                			<li class="wide" id="actions">
                				<div id="misc-publishing-actions">

                                    <div class="misc-pub-section misc-pub-visibility" id="visibility">
                                    	<?php echo __('Status:',WSHOP);
                                    	if($this->current_order->removed){
                                    	    ?><span style="color:red;" id="post-visibility-display"><?php echo __('Trash',WSHOP)?></span><?php
                                    	}else{
                                    	    ?><span id="post-visibility-display"><?php echo __('Published',WSHOP)?></span><?php 
                                    	}?>
                                    </div>
                                    
                                    <?php if($this->current_order->expire_date){
                                        ?>
                                        <div class="misc-pub-section curtime misc-pub-curtime">
                                        	<span id="timestamp"><?php echo __('Expire Date:',WSHOP).date('Y-m-d H:i',$this->current_order->expire_date)?></span>
                                        </div>
                                        <?php 
                                    }?>
                                 </div>
                			</li>
                
                			<li class="wide button-line">
                				<select id="wshop-order-action" style="width:200px;">
                					<option value=""><?php echo __('Select...',WSHOP)?></option>
                					<?php 
                					   if($this->current_order->removed){
                					       ?>
                					        <option value="<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'restore'),true,true)?>"><?php echo __('Restore',WSHOP)?></option>
											<option value="<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'delete'),true,true)?>"><?php echo __('Delete permanently',WSHOP)?></option>
                					       <?php 
                					   }else{
                					       ?>
                                            	<option value="<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'trash'),true,true)?>"><?php echo __('Move to trash',WSHOP)?></option>
                                            	<option value="<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'mark_processing'),true,true)?>"><?php echo __('Mark as Processing',WSHOP)?></option>
                                            	<option value="<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'mark_complete'),true,true)?>"><?php echo __('Mark as Complete',WSHOP)?></option>
                                            	<option value="<?php echo WShop::instance()->ajax_url(array('action'=>"wshop_update_order",'tab'=>'mark_pending'),true,true)?>"><?php echo __('Mark as Pending',WSHOP)?></option>
                					       <?php 
                					   }
                					?>
                				</select>	<input type="button" id="btn-order-submit" class="button save_order button-primary" name="save" value="<?php echo __('Submit',WSHOP)?>">
                			</li>
                		</ul>
                		<script type="text/javascript">
							(function($){
									$('#btn-order-submit').click(function(){
										window.wshop_view._update_order(<?php echo $this->current_order->id?>,$('#wshop-order-action').val());
									});
							})(jQuery);
                		</script>
                		</div>
                </div>
                
                <?php 
                     global $wpdb;
                    $histories =$this->current_order->get_order_notes(); 
                    
                        ?>
                <div id="wshop-order-notes" class="postbox">
                 
                    <div class="inside">
                    <ul class="order_notes">
                    	<?php if($histories&&count($histories)>0){
                    	    foreach ($histories as $history){
                    	    ?>
                    	    <li class="note <?php echo $history->note_type==WShop_Order_Note:: Note_Type_Customer?'':'system-note'?>">
            					<div class="note_content">
            						<p><?php echo $history->content?></p>
            					</div>
            					<p class="meta">
            						<abbr class="exact-date" ><?php echo sprintf(__('Added on %s',WSHOP),date('Y-m-d H:i',$history->created_date))?></abbr>
            						<?php if($history->user_id){
            						    $user = get_user_by('id', $history->user_id);
            						    if($user){
            						        echo sprintf(__('From %s'),$user->user_login);
            						    }
            						}?>					
            						<a href="javascript:void(0);" class="delete_note" onclick="window.wshop_note.remove(<?php echo $history->id?>);" role="button"><?php echo __('Remove remark',WSHOP)?></a>
            					</p>
            				</li>
                    	    <?php 
                    	}
                    	}?>				
                    
    				</ul>		
    				
    					<div class="add_note">
                			<p>
                				<label for="add_order_note"><?php echo __('Add remark',WSHOP)?> <span class="wshop-help-tip"></span></label>
                				<textarea style="width: 100%; height: 50px;" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
                			</p>
                			<p>
                				<label for="order_note_type" class="screen-reader-text"><?php echo __('Note type',WSHOP)?></label>
                				<select name="order_note_type" id="order_note_type">
                					<?php foreach (WShop_Order_Note::get_note_types() as $key=>$name){
                					    ?>
                					    <option value="<?php echo $key;?>"><?php echo $name;?></option>
                					    <?php 
                					}?>
                				</select>
                				<button type="button" class="add_note button" onclick="window.wshop_note.add();"><?php echo __('Add',WSHOP)?></button>
                			</p>
                		</div>
                	</div>
                	<script type="text/javascript">
						(function($){
							window.wshop_note={
								remove:function(id){
									if(!confirm('<?php echo __('Are you sure?',WSHOP)?>')){
										return;
									}
									
									$('#wpbody-content').loading();
									$.ajax({
										url:'<?php echo WShop::instance()->ajax_url(array(
										    'action'=>'wshop_order_note',
										    'tab'=>'remove'
										),true,true)?>',
										type:'post',
										timeout:60*1000,
										async:true,
										cache:false,
										data:{
											id:id,
											order_id:'<?php echo $this->current_order->id?>'
										},
										dataType:'json',
										complete:function(){
											$('#wpbody-content').loading('hide');
										},
										success:function(e){
											if(e.errcode!=0){
												alert(e.errmsg);
												return;
											}
											
											location.reload();
										},
										error:function(e){
											console.error(e.responseText);
											alert('<?php echo esc_attr( 'System error while modifing order note!', WSHOP); ?>');
										}
									});
								},
								add:function(){
									$('#wpbody-content').loading();
									$.ajax({
										url:'<?php echo WShop::instance()->ajax_url(array(
										    'action'=>'wshop_order_note',
										    'tab'=>'add'
										),true,true)?>',
										type:'post',
										timeout:60*1000,
										async:true,
										cache:false,
										data:{
											content:$.trim($('#add_order_note').val()),
											note_type:$.trim($('#order_note_type').val()),
											order_id:'<?php echo $this->current_order->id?>'
										},
										dataType:'json',
										complete:function(){
											$('#wpbody-content').loading('hide');
										},
										success:function(e){
											if(e.errcode!=0){
												alert(e.errmsg);
												return;
											}
											
											location.reload();
										},
										error:function(e){
											console.error(e.responseText);
											alert('<?php echo esc_attr( 'System error while add order note!', WSHOP); ?>');
										}
									});
								}
							};
						})(jQuery);
                	</script>
                </div>
            </div>
        </div>
            
            <div id="postbox-container-2" class="postbox-container">
                	<div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    
                    <?php $this->current_order->order_view_admin_order_detail();?>
                    
                    <?php $this->current_order->order_items_view_admin_order_detail();?>
                    </div>
             </div>
        </div><!-- /post-body -->
        <br class="clear">
        </div>
        <?php 
    }
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WShop_Order_List_Table extends WP_List_Table {

    /**
     * @var WShop_Menu_Order_Default_Settings
     * @since 1.0.0
     */
    public $api;
 
    private $order_status;
    
    /**
     * @var WP_User
     */
    private $customer_searched;
    
    /**
     * @var WP_Post
     */
    private $product_searched;
    
    private $order_date;
    
    private $order_id;
    /**
     * @param WShop_Menu_Order_Default_Settings $api
     * @param array $args
     * @since 1.0.0
     */
    public function __construct($api, $args = array() ) {
        $this->api = $api;
         
        parent::__construct( $args );
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable ,'ID');
        
        $this->order_status = isset($_REQUEST['status'])?$_REQUEST['status']:null;
        if(!$this->order_status||!in_array($this->order_status, $this->get_all_order_status())){
            $this->order_status=null;
        }
        
        $customer_id = isset($_REQUEST['_cid'])?intval($_REQUEST['_cid']):0;
        if($customer_id>0){
            $this->customer_searched = get_user_by('id', $customer_id);
        }
        
        $product_id = isset($_REQUEST['_pid'])?intval($_REQUEST['_pid']):0;
        if($product_id>0){
            $post = get_post($product_id);
            if($post){
                $post_types = WShop::instance()->payment->get_online_post_types();
                foreach ($post_types as $type =>$attr){
                    if($type==$post->post_type){
                        $this->product_searched = $post;
                        break;
                    }
                }
            }
        }
        
        $this->order_date = isset($_REQUEST['order_date'])&&!empty($_REQUEST['order_date'])?date('Y-m-d',strtotime($_REQUEST['order_date'])):null;
        $this->order_id  = isset($_REQUEST['order_id'])?sanitize_key($_REQUEST['order_id']):null;
    }
    
    public function process_action(){
        $bulk_action = $this->current_action();
        if(empty($bulk_action)){
            return;
        }
         
        check_admin_referer( 'bulk-' . $this->_args['plural'] );
         
        $order_ids   = isset($_POST['order_ids'])?$_POST['order_ids']:null;;
        if(!$order_ids||!is_array($order_ids)){
            return;
        }
     
        foreach ($order_ids as $order_id){
            $error = WShop_Order_Helper::update_order($order_id, $bulk_action);
            if(!WShop_Error::is_valid($error)){
                ?><div class="notice notice-error  is-dismissible"><p><?php echo $error->errmsg;?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_attr('Ignore this notice.',WSHOP)?></span></button></div><?php
                return;
            }
       }
    }
    
    public function get_all_order_status(){
        return array(
            Abstract_WShop_Order::Processing,
            Abstract_WShop_Order::Complete,
            Abstract_WShop_Order::Pending,
            'trash'
        );
    }
    function get_sortable_columns() {
        return array(
            'ID'    => array( 'ID', false ),
            'order_date' => array( 'order_date', false ),
            'total' => array( 'total', false ),
        );
    }

    function get_views() {
        global $wpdb;
        $status_pending = Abstract_WShop_Order::Pending;
        $status_processing = Abstract_WShop_Order::Processing;
        $status_complete = Abstract_WShop_Order::Complete;
    
        $result =$wpdb->get_row(
           "select sum(if(o.`removed`=1,0,1)) as total,
            sum(if(o.`status`='$status_processing' and o.`removed`=0,1,0)) as processing,
            sum(if(o.`status`='$status_pending' and o.`removed`=0,1,0)) as pending,
            sum(if(o.`status`='$status_complete' and o.`removed`=0,1,0)) as complete,
            sum(o.`removed`) as removed
            from `{$wpdb->prefix}wshop_order` o;");
         
        $form_count= array(
            'all'    => array(
                'title'=>__('All',WSHOP),
                'count'=>intval( $result->total )
            ),
            'processing' => array(
                'title'=>__('Processing',WSHOP),
                'count'=>intval( $result->processing )
            ),
            'complete'    => array(
                'title'=>__('Complete',WSHOP),
                'count'=>intval( $result->complete )
            ),
            'pending'    => array(
                'title'=>__('Pending',WSHOP),
                'count'=>intval( $result->pending )
            ),
            'trash'=> array(
                'title'=>__('Trash',WSHOP),
                'count'=>intval( $result->removed )
            ),
        );
    
        $current =null;
        $index=0;
        foreach ($form_count as $key=>$val){
            if($index++==0){
                $current=$key;
            }
    
            if($this->order_status==$key){
                $current=$key;
                break;
            }
        }
    
        if($this->order_status=='trash'){
            $current='trash';
        }
        
        $page_now = WShop_Admin::instance()->get_current_admin_url();
        $views=array();
        foreach ($form_count as $key=>$data){
            $now = $current==$key?"current":"";
            $views[$key] ="<a class=\"{$now}\" href=\"{$page_now}&status={$key}\">{$data['title']} <span class=\"count\">(<span>{$data['count']}</span>)</span></a>";
        }
         
        return $views;
    }

    
    function prepare_items() {
        $sort_column  = empty( $_REQUEST['orderby'] ) ? null : $_REQUEST['orderby'];
        $sort_columns = array_keys( $this->get_sortable_columns() );

        if (!$sort_column|| ! in_array( strtolower( $sort_column ), $sort_columns ) ) {
            $sort_column = 'id';
        }

        $sort = isset($_REQUEST['order']) ? $_REQUEST['order']:null;
        if(!in_array($sort, array('asc','desc'))){
            $sort ='desc';
        }

        $order_status ='trash'==$this->order_status?" and o.removed=1 ":  (empty($this->order_status)?" and o.removed=0 ":" and o.removed=0 and o.status='{$this->order_status}'");
        $customer_id = !$this->customer_searched?"":" and o.customer_id={$this->customer_searched->ID}";
        $order_date ="";
        if($this->order_date){
            $start = strtotime($this->order_date);
            $end = $start+24*60*60;
            $order_date=" and (o.order_date>=$start and o.order_date<=$end)";
        }
        
        global $wpdb;
        $sql=  "select count(o.id) as qty
                from `{$wpdb->prefix}wshop_order` o
                where  (%s ='' or o.id=%s)  
                      $order_status
                      $customer_id
                      $order_date ;";
        
        if($this->product_searched){
            $sql=  "select count(o.id) as qty
                    from `{$wpdb->prefix}wshop_order` o
                    inner join {$wpdb->prefix}wshop_order_item oi on oi.order_id = o.id
                    where (%s ='' or o.id=%s)  
                          and oi.post_ID={$this->product_searched->ID}
                          $order_status
                          $customer_id
                          $order_date ;";
        }
        
        $query = $wpdb->get_row($wpdb->prepare($sql, $this->order_id,$this->order_id));

        $total = intval($query->qty);
        $per_page = 20;
        if($per_page<=0){$per_page=20;}
        $total_page = intval(ceil($total/($per_page*1.0)));
        $this->set_pagination_args( array(
            'total_items' => $total,
            'total_pages' => $total_page,
            'per_page' => $per_page,
            'status'=>$this->order_status,
            '_cid'=>$this->customer_searched?$this->customer_searched->ID:null,
            '_pid'=>$this->product_searched?$this->product_searched->ID:null,
            'order_date'=>$this->order_date,
            'order_id'=>$this->order_id
        ));

        $pageIndex =$this->get_pagenum();
        $start = ($pageIndex-1)*$per_page;
        $end = $per_page;

        $sql ="select o.*
                from `{$wpdb->prefix}wshop_order` o
                where (%s ='' or o.id=%s)  
                      $order_status
                      $customer_id
                      $order_date
                order by o.$sort_column $sort
                limit $start,$end;";
        if($this->product_searched){
          $sql =  "select o.*
                  from `{$wpdb->prefix}wshop_order` o
                  inner join {$wpdb->prefix}wshop_order_item oi on oi.order_id = o.id
                  where (%s ='' or o.id=%s)  
                        and oi.post_ID={$this->product_searched->ID}
                        $order_status
                        $customer_id
                        $order_date
                  order by o.$sort_column $sort
                 limit $start,$end;";
        }
          
        $items = $wpdb->get_results($wpdb->prepare($sql, $this->order_id,$this->order_id));   
        if($items){
            foreach ($items  as $item){
                $this->items[]=WShop_Mixed_Object_Factory::to_entity($item);
            }
        }
    }
    
    function extra_tablenav( $which ) {
       if($which!='top'){
           return;
       }
       ?>
       
       <input type="search" id="search-order-date" name="order_date" style="height:32px;" value="<?php echo esc_attr($this->order_date)?>" placeholder="<?php echo __('Order date',WSHOP)?>"/>
       <input type="search" id="search-order-id" name="order_id" style="height:32px;" value="<?php echo esc_attr($this->order_id)?>" placeholder="<?php echo __('Order id',WSHOP)?>"/>
       <script type="text/javascript">
       		(function($){
          		$(function(){
          			$("#search-order-date").focus(function() {
              			WdatePicker({
              				dateFmt: 'yyyy-MM-dd'
              			});
              		});
              	});
           	})(jQuery);
	   </script>
       <select class="wshop-customer-search" name="_cid" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', WSHOP); ?>" data-allow_clear="true">
			<?php 
			if($this->customer_searched){
			    ?>
			    <option value="<?php echo $this->customer_searched->ID?>">
			    	<?php if(!empty($this->customer_searched->user_email)){
			    	    echo "{$this->customer_searched->user_login}({$this->customer_searched->user_email})";
			    	}else{
			    	    echo $this->customer_searched->user_login;
			    	}?>
			    </option>
			    <?php 
			}
			?>
		</select>
		 <select class="wshop-product-search" name="_pid" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', WSHOP); ?>" data-allow_clear="true">
			<?php 
			if($this->product_searched){
			    ?>
			    <option value="<?php echo $this->product_searched->ID?>">
			    	<?php echo $this->product_searched->post_title;?>
			    </option>
			    <?php 
			}
			?>
		</select>
		<input type="submit" class="button" style="line-height: 32px;height:32px;" value="<?php echo __('Filter',WSHOP)?>">
       <?php 
    }
    
    function get_bulk_actions() {
        if ( $this->order_status == 'trash' ) {
            return array(
                'restore' => esc_html__( 'Restore', WSHOP ),
                'delete' => esc_html__( 'Delete permanently', WSHOP ),
            );
        }

        return array(
            'trash' => esc_html__( 'Move to trash', WSHOP ),
            'mark_processing' => esc_html__( 'Mark as Processing', WSHOP ),
            'mark_complete' => esc_html__( 'Mark as Complete', WSHOP ),
            'mark_pending' => esc_html__( 'Mark as Pending', WSHOP )
        );
    }

    function get_columns() {
        return array(
            'cb'            => '<input type="checkbox" />',
            'status'        => '<span class="wshop-tips" title="'.__('Order status',WSHOP).'"><img style="width:18px;height:18px;" src="'.WSHOP_URL.'/assets/image/order/status.png"/></span>',
            'ID'         => __( 'Order', WSHOP ),
            'detail'        => __( 'Details', WSHOP ),
            'order_date'    => __( 'Order Date', WSHOP ),
            'total'         => __( 'Total', WSHOP ),
            'toolbar'       => __( 'Toolbar', WSHOP ),
        );
    }

    public function single_row( $item ) {
        echo '<tr id="form-tr-'.$item->id .'">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    function single_row_columns( $item ) {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        foreach ( $columns as $column_name => $column_display_name ) {
            $classes = "$column_name column-$column_name";
            if ( $primary === $column_name ) {
                $classes .= ' has-row-actions column-primary';
            }

            if ( in_array( $column_name, $hidden ) ) {
                $classes .= ' hidden';
            }

            // Comments column uses HTML in the display name with screen reader text.
            // Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
            $data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

            $attributes = "class='$classes' $data";

            if ( 'cb' === $column_name ) {
                echo '<th scope="row" class="check-column">';
                echo $this->column_cb( $item );
                echo '</th>';
            } elseif ( method_exists( $this, '_column_' . $column_name ) ) {
                echo call_user_func(
                    array( $this, '_column_' . $column_name ),
                    $item,
                    $classes,
                    $data,
                    $primary
                    );
            } elseif ( method_exists( $this, 'column_' . $column_name ) ) {
                echo "<td $attributes>";
                echo call_user_func( array( $this, 'column_' . $column_name ), $item );
                echo $this->handle_row_actions( $item, $column_name, $primary );
                echo "</td>";
            } else {
                echo "<td $attributes>";
                echo $this->column_default( $item, $column_name );
                echo $this->handle_row_actions( $item, $column_name, $primary );
                echo "</td>";
            }
        }
    }

	function column_cb( $form ) {
		$form_id = $form->id;
		?>
		<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $form_id ); ?>"><?php _e( 'Select order' ); ?></label>
		<input type="checkbox" class="wshop_list_checkbox" name="order_ids[]" value="<?php echo esc_attr( $form_id ); ?>" />
		<?php
	}
    
	/**
	 *
	 * @param Abstract_WShop_Order $item
	 */
	public function column_status($item){
	    $url =WSHOP_URL;
	    return "<span class=\"wshop-tips\" title=\"{$item->status}\"><img style=\"width:22px;height:22px;\" src=\"{$url}/assets/image/order/{$item->status}.png\"/></span>";
	}
	
	/**
	 *
	 * @param Abstract_WShop_Order $item
	 */
	public function column_ID($item){
	    $edit_url = WShop_Admin::instance()->get_current_admin_url(array(
	        'view'=>'edit',
	        'id'=>$item->id
	    ));
    ?>
        <a href="<?php echo $edit_url;?>" class="row-title"><strong>#<?php echo $item->id?></strong></a>
        <?php if($item->customer_id){
            $user = get_user_by('id', $item->customer_id);
            if($user){
                ?>
               <div> 
                     by <a href="<?php echo get_edit_user_link( $user->ID )?> "><?php echo esc_attr($user->display_name) ?> <?php echo $user->ID?></a>
                    
                    <div><small class="meta email"><a href="<?php echo esc_attr("mailto:{$user->user_email}")?>"><?php echo $user->user_email?></a></small></div>
               </div><?php 
            }
        }?>
         <div class="row-actions">
         	 <?php if($this->order_status=='trash'){
         	     ?>
          	      <span class="restore"><a href="javascript:void(0);" onclick="window.wshop_view.restore(<?php echo $item->id;?>);"><?php echo __('Restore',WSHOP)?></a> | </span>
              	  <span class="delete"><a href="javascript:void(0);" onclick="window.wshop_view.delete(<?php echo $item->id;?>);" ><?php echo __('Delete permanently',WSHOP)?></a></span>
          	     <?php 
         	 }else{
         	     ?>
         	     <span class="edit"><a href="<?php echo $edit_url;?>"><?php echo __('Edit',WSHOP)?></a> | </span>
             	 <span class="trash"><a href="javascript:void(0);" onclick="window.wshop_view.trash(<?php echo $item->id;?>);"><?php echo __('Trash',WSHOP)?></a></span>
         	     <?php 
         	 }?>
             
         </div>
        <?php 
    }
    
    /**
     * @param Abstract_WShop_Order $item
     */
    public function column_detail($item){
       $item->order_items_view_admin_order_list_item();
    }
    /**
     * @param Abstract_WShop_Order $item
     */
    public function column_order_date($item){
        ?>
        <time><?php echo date('Y-m-d H:i',$item->order_date)?></time>
        <?php 
    }
    
    /**
     * @param Abstract_WShop_Order $item
     */
    public function column_total($item){
        ?>
       <span class="amount"><?php echo $item->get_total_amount(true)?></span>
       <?php if($item->is_paid()){ 
           $payment_gateway =$item->get_payment_gateway();
           if($payment_gateway){
               ?>
                <small class="meta"><?php echo sprintf(__('via %s',WSHOP),$payment_gateway->title)?></small>
               <?php 
           }
       }
    }
    
    /**
     * @param Abstract_WShop_Order $item
     */
    public function column_toolbar($item){
        $edit_url = WShop_Admin::instance()->get_current_admin_url(array(
            'view'=>'edit',
            'id'=>$item->id
        ));
        
        ?><p>
            <?php if($item->status==Abstract_WShop_Order::Processing){
                ?>
                <a class="xh-list-imgbtn"  href="javascript:void(0);" onclick="window.wshop_view.complete(<?php echo $item->id;?>);"><img src="<?php echo WSHOP_URL?>/assets/image/order/do-complete.png"></a>
                <?php 
            }?>
			<a class="xh-list-imgbtn" href="<?php echo $edit_url?>"><img alt="" src="<?php echo WSHOP_URL?>/assets/image/order/do-view.png"></a>				
		  </p>
        <?php 
    }  

	function no_items() {
		echo __( "You don't have any orders!", WSHOP ) ;
	}
}
?>