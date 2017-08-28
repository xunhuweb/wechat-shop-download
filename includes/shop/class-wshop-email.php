<?php 

class WShop_Email extends WShop_Object{
    const EMAIL_TYPE_TEXT='Content-type: text/plain' ;
    const EMAIL_TYPE_HTML='Content-type: text/html';
    
    public static $email_types=array(
        'TEXT'=>self::EMAIL_TYPE_TEXT,
        'HTML'=>self::EMAIL_TYPE_HTML
    );
    
    public function __construct($obj=null){
        parent::__construct($obj);
    }
    /**
     * {@inheritDoc}
     * @see WShop_Object::is_auto_increment()
     */
    public function is_auto_increment()
    {
        // TODO Auto-generated method stub
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_primary_key()
     */
    public function get_primary_key()
    {
        // TODO Auto-generated method stub
        return 'template_id';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_table_name()
     */
    public function get_table_name()
    {
        // TODO Auto-generated method stub
        return 'wshop_email';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys()
    {
        return array(
            'template_id'=>null,
            'enabled'=>true,
            'system_name'=>true,
            'recipients'=>array(),
            'subject'=>null,
            'email_type'=>null,
            'description'=>null,
        );
    }
  
    public $subject;
    public $recipients=array();
  
    /**
     * 
     * @param array $settings
     * @param array $content_atts
     * @return boolean
     */
    public function send($settings,$message){
        if(!$this->enabled){return;}
        $admin_email =get_option('admin_email');
        $defaults = array(
            '{email:admin}'=>$admin_email,
            '{site_title}'=>get_option('blogname')
        );
        
        $settings = wp_parse_args ( $settings, $defaults );
        $subject = $this->subject;
        $recipients = $this->recipients;
        
        foreach ($settings as $key=>$val){
            $subject = str_replace($key, $val,$subject);
            if($recipients){
                foreach ($recipients as $k=>$recipient){
                    $recipients[$k] = str_replace($key, $val,$recipient);
                }
            }
        }
     
        if(count($recipients)==0){
            return false;
        }
        
        try {
            return @wp_mail($recipients, $subject, $message,self::$email_types[$this->email_type]);
        } catch (Exception $e) {
            WShop_Log::error($e->getMessage());
            return false;
        }
    }
}

class WShop_Email_Model extends Abstract_WShop_Schema{
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Model_Api::init()
     */
    public function init()
    {
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query(
        "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_email` (
    		`template_id` VARCHAR(64) NOT NULL,
        	`enabled` TINYINT(4) NOT NULL DEFAULT '1',
        	`recipients` TEXT NULL,
        	`system_name` TEXT NULL,
        	`subject` TEXT NULL,
        	`description` TEXT NULL,
        	`email_type` VARCHAR(128) NULL DEFAULT NULL,
        	PRIMARY KEY (`template_id`)
        )
        $collate;");

        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        self::init_new_email('new-order',__('New order',WSHOP),__('[{site_title}] New order ({order_number}) - {order_date}',WSHOP),array(
            "{email:admin}"
        ),
        __('New order emails are sent to chosen recipient(s) when a new order is received.',WSHOP));
        
        self::init_new_email('order-received',__('Order received',WSHOP),__('[{site_title}] Order received ({order_number}) - {order_date}',WSHOP),array(
            "{email:customer}"
        ),
        __('New order emails are sent to chosen recipient(s) when a new order is received.',WSHOP));
        
        
        
        do_action('wshop_email_init');
    }
    
    public static function init_new_email($template_id,$system_name,$subject,$recipients=array(),$description=null){
        $mail = new WShop_Email($template_id);
        if(!$mail->is_load()){
            //初始化数据
            $mail = new WShop_Email(array(
                'template_id'=>$template_id,
                'system_name'=>$system_name,
                'enabled'=>1,
                'subject'=>$subject,
                'email_type'=>'HTML',
                'recipients'=>$recipients,
                'description'=>$description
            ));
            
            $error = $mail->insert();
            if(!$error->is_valid($error)){
                throw new Exception($error->errmsg);
            }
        }
    }
}
?>