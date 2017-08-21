<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * @since 1.0.0
 * @author ranj
 */
class WShop_User_Menu_Order_Default extends Abstract_WShop_Settings_Menu{
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
        $this->id='menu_user_order_default';
        $this->title=__('My Orders',WSHOP);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_WShop_Settings_Menu::menus()
     */
    public function menus(){
        return apply_filters("wshop_admin_menu_{$this->id}", array(
            WShop_User_Menu_Order_Default_Settings::instance()
        ));
    }
}

class WShop_User_Menu_Order_Default_Settings extends Abstract_WShop_Settings {
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
        $this->id='menu_user_order_default_settings';
        $this->title=__('My Orders',WSHOP);
      
    }

    public function admin_form_start(){}
     
    public function admin_options(){  
    	   if(isset($_GET['view'])&&$_GET['view']=='edit'){
    	       $view = new WShop_User_Order_Edit_View($this);
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
	           
	           		$table = new WShop_User_Order_List_Table($this);
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

class WShop_User_Order_Edit_View{
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
        $user_id = get_current_user_id();
        $order = WShop::instance()->payment->get_order('id', isset($_GET['id'])?sanitize_key($_GET['id']):null);
        if($order&&$order->customer_id==$user_id){
            $this->current_order=$order;
        }
        
    }
    
    public function view(){
        if(!$this->current_order){
            WShop::instance()->WP->wp_die(WShop_Error::error_custom(__('Order is not found!',WSHOP)),false,false);
            return;
        }
        
        global $wpdb;
        $note_type = WShop_Order_Note::Note_Type_Customer;
        $histories =$this->current_order->get_order_notes($note_type);
        
        ?>
            <h1 class="wp-heading-inline"><?php echo __('Edit order',WSHOP)?></h1>
            <hr class="wp-header-end">
         
            <div id="poststuff">
            <div id="post-body" class="metabox-holder <?php echo $histories&&count($histories)>0?"columns-2":""?>">
            <div id="postbox-container-1" class="postbox-container">
            <div id="side-sortables" class="meta-box-sortables ui-sortable">
            
            <?php  if($histories&&count($histories)>0){ ?>
                <div id="wshop-order-notes" class="postbox">
                 
                    <div class="inside">
                    <ul class="order_notes">
                    	<?php 
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
                    	}?>				
                    
    				</ul>		
                </div>
               </div> 
                   <?php 
                    	}?>				
                    
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

class WShop_User_Order_List_Table extends WP_List_Table {

    /**
     * @var WShop_Menu_Order_Default_Settings
     * @since 1.0.0
     */
    public $api;
 
    private $order_status;

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
    
    public function get_all_order_status(){
        return array(
            Abstract_WShop_Order::Processing,
            Abstract_WShop_Order::Complete,
            Abstract_WShop_Order::Pending
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
        $current_user_id = get_current_user_id();
        
        $result =$wpdb->get_row(
           "select sum(if(o.`removed`=1,0,1)) as total,
            sum(if(o.`status`='$status_processing' and o.`removed`=0,1,0)) as processing,
            sum(if(o.`status`='$status_pending' and o.`removed`=0,1,0)) as pending,
            sum(if(o.`status`='$status_complete' and o.`removed`=0,1,0)) as complete
            from `{$wpdb->prefix}wshop_order` o
            where  o.customer_id={$current_user_id};");
         
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
            )
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

        $order_status =(empty($this->order_status)?" and o.removed=0 ":" and o.removed=0 and o.status='{$this->order_status}'");
        $user_id =get_current_user_id();
        $customer_id = " and o.customer_id={$user_id}";
        $order_date ="";
        if($this->order_date){
            $start = strtotime($this->order_date);
            $end = $start+24*60*60;
            $order_date=" and (o.order_date>=$start and o.order_date<=$end)";
        }
        
        global $wpdb;
        $sql=  "select count(o.id) as qty
                from `{$wpdb->prefix}wshop_order` o
                where   (%s ='' or o.id=%s)  
                      $order_status
                      $customer_id
                      $order_date ;";
        
        if($this->product_searched){
            $sql=  "select count(o.id) as qty
                    from `{$wpdb->prefix}wshop_order` o
                    inner join {$wpdb->prefix}wshop_order_item oi on oi.order_id = o.id
                    where  (%s ='' or o.id=%s)  
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
            '_pid'=>$this->product_searched?$this->product_searched->ID:null,
            'order_date'=>$this->order_date,
            'order_id'=>$this->order_id
        ));

        $pageIndex =$this->get_pagenum();
        $start = ($pageIndex-1)*$per_page;
        $end = $per_page;

        $sql ="select o.*
                from `{$wpdb->prefix}wshop_order` o
                where  (%s ='' or o.id=%s)  
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
            } elseif ( has_action( 'wshop_form_list_column_' . $column_name ) ) {
                echo "<td $attributes>";
                do_action( 'wshop_form_list_column_' . $column_name, $item );
                echo $this->handle_row_actions( $item, $column_name, $primary );
                echo '</td>';
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
		<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $form_id ); ?>"><?php _e( 'Select form' ); ?></label>
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
       
         <div class="row-actions">
         	 <span class="edit"><a href="<?php echo $edit_url;?>"><?php echo __('Edit',WSHOP)?></a> | </span>
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
			<a class="button tips view" href="<?php echo $edit_url?>"><img alt="" src="<?php echo WSHOP_URL?>/assets/image/order/do-view.png"></a>				
		  </p>
        <?php 
    }  

	function no_items() {
		printf( __( "You don't have any orders!", WSHOP ) );
	}
}
?>