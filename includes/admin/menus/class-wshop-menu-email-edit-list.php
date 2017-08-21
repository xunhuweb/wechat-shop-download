<?php

if ( ! defined ( 'ABSPATH' ) ) {
	die();
}

class WShop_Email_Edit_List {

	public function view() {
		global $wpdb;
		?>
		<div class="wrap">
		<h2>
			<?php esc_html_e( 'Emails', WSHOP );?>
		</h2>
   		<?php
   
   		$table = new WShop_Email_List_Table();
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

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WShop_Email_List_Table extends WP_List_Table {

    /**
     * @param WShop_Menu_Order_Default_Settings $api
     * @param array $args
     * @since 1.0.0
     */
    public function __construct( $args = array() ) {
        parent::__construct( $args );
        
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable ,'system_name');
    }

    function prepare_items() {
       
        global $wpdb;
        $sql=  "select count(o.template_id) as qty
                from `{$wpdb->prefix}wshop_email` o;";
        
        $query = $wpdb->get_row($sql);

        $total = intval($query->qty);
        $per_page = 20;
        $total_page = intval(ceil($total/($per_page*1.0)));
        $this->set_pagination_args( array(
            'total_items' => $total,
            'total_pages' => $total_page,
            'per_page' => $per_page
        ));

        $pageIndex =$this->get_pagenum();
        $start = ($pageIndex-1)*$per_page;
        $end = $per_page;

        $sql = "select o.*
                from `{$wpdb->prefix}wshop_email` o
                limit $start,$end;";
     
        $items = $wpdb->get_results($sql); 
        if($items){
            foreach ($items as $item){
                $this->items[]=new WShop_Email($item);
            }
        }
    }

    function get_columns() {
        return array(
            'system_name'               => __( 'Subject', WSHOP ),
            'email_type'          => __( 'Email type', WSHOP ),
            'recipients'            => __( 'Recipients', WSHOP ),
            'enabled'               => __( 'Status', WSHOP ),
            'tools'                 => __( 'Tools', WSHOP )
        );
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

            $data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

            $attributes = "class='$classes' $data";

            if ( 'cb' === $column_name ) {
                echo '<th scope="row" class="check-column">';
                echo $this->column_cb( $item );
                echo '</th>';
            }  elseif ( method_exists( $this, '_column_' . $column_name ) ) {
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

	public function column_system_name($item){
	    $edit_url = WShop_Admin::instance()->get_current_admin_url(array(
	        'view'=>'edit',
	        'id'=>$item->template_id
	    ));
        ?><a href="<?php echo $edit_url;?>" class="row-title"><strong><?php echo $item->system_name?></strong></a><?php 
    }
    
    public function column_email_type($item){
        echo WShop_Email::$email_types[$item->email_type];
    }
    
    public function column_enabled($item){
        echo $item->enabled?'<span style="color:green">YES</span>':'<span>NO</span>';
    }
    
    public function column_recipients($item){
        echo $item->recipients?join(',',$item->recipients):null;
    }
    
    public function column_tools($item){
       $edit_url = WShop_Admin::instance()->get_current_admin_url(array(
	        'view'=>'edit',
	        'id'=>$item->template_id
	    ));
        ?><a href="<?php echo $edit_url;?>" class="row-title"><strong><?php echo __('edit',WSHOP)?></strong></a><?php 
    }
    
	function no_items() {
		echo __( "You don't have any email!", WSHOP ) ;
	}
}
