<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

abstract class WShop_Object{
    /**
     * 判断主键是否是递增
     * @return boolean
     * @since 1.0.0
     */
    abstract function is_auto_increment();
    
    /**
     * 获取主键名称
     * @return string
     * @since 1.0.0
     */
    abstract function get_primary_key();
    
    /**
     * 获取表名称
     * @return string
     * @since 1.0.0
     */
    abstract function get_table_name();

    /**
     * 获取属性集合(含默认值)
     * @return array
     * @since 1.0.0
     */
    abstract function get_propertys();
    
    public function ext_propertys(){
        return array();
    }
    
    public function __construct($obj=null){
        //如果是ID
        if($obj&&(is_numeric($obj)||is_string($obj))){
            global $wpdb;
            $table_name ="{$wpdb->prefix}".$this->get_table_name();
            $primary_key = $this->get_primary_key();
            
            $this->get_by($primary_key, $obj);
            return;
        }
        
        if($obj&&is_object($obj)){
            $obj = get_object_vars($obj);
        }
       
        if($obj&&is_array($obj)){
            foreach ( $obj as $key => $value ){
                $this->{$key} = maybe_unserialize($value);
            }
        }else{
            foreach (array_merge($this->get_propertys(),$this->ext_propertys()) as $key=>$default_val){
                $this->{$key} = maybe_unserialize($default_val);
            }
        }
    }
    
    /**
     * 判断实体是否已加载
     * @return boolean
     * @since 1.0.0
     */
    public function is_load(){
        $key = $this->get_primary_key();
        return isset($this->{$key})&&$this->{$key};
    }
    
    /**
     * 插入数据
     * @since 1.0.0
     * @return WShop_Error
     */
    public function insert(){
        global $wpdb;
        $table_name ="{$wpdb->prefix}".$this->get_table_name();
        $primary_key = $this->get_primary_key();
       
        $data =$this->get_property_datas();
       
        $wpdb->insert($table_name, $data); 
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            return WShop_Error::err_code($wpdb->last_error);
        }
         
        if($this->is_auto_increment()){
            if($wpdb->insert_id==0){
                return WShop_Error::error_unknow();
            }
            
            $this->{$primary_key} = $wpdb->insert_id;
        }
        $this->refresh_cache();
        
        return WShop_Error::success();
    }
   
    public function refresh_cache(){
        if(!$this->is_load()){
            throw new Exception('something is wrong when refresh cache');
        }
       
        $this->get_obj_by($this->get_primary_key(), $this->{$this->get_primary_key()},true);
    }
    
    protected function get_cache_key($primary_key_val = null){
        if(is_null($primary_key_val)){
            $primary_key_val = $this->{$this->get_primary_key()};
        }
       
        if(!$primary_key_val){
            throw new Exception('primary_key_val is invalid when refresh cache');
        }
        return strtolower($this->get_table_name()."_".$primary_key_val);
    }
    
    /**
     * 获取实体类转换为可执行的数据
     * @since 1.0.0
     * @return array
     */
    public function get_property_datas($properties=array()){
        if(count($properties)==0){
            $properties =shortcode_atts($this->get_propertys(), get_object_vars($this));
            if($this->is_auto_increment()){
                unset($properties[$this->get_primary_key()]);
            }
        }else{
            $opropertys = $this->get_propertys();
           
            $new_properties = array();
            foreach ($properties as $key=>$val){
                if(array_key_exists($key,$opropertys)){
                    $new_properties[$key]=$val;
                    $this->{$key}=maybe_unserialize($val);
                }
            }
            
            $properties=$new_properties;
        }
        
        foreach ($properties as $key=>$val){
            $properties[$key] = maybe_serialize($val);
        }
        
        return $properties;
    }
    
    /**
    * 更新数据
    * @since 1.0.0
    * @return WShop_Error
    */
    public function update($properties=array()){
        global $wpdb;
        $table_name ="{$wpdb->prefix}".$this->get_table_name();
        $primary_key = $this->get_primary_key();
    
        $data =$this->get_property_datas($properties);
    
        $wpdb->update($table_name,$data,array(
            $primary_key=>$this->{$primary_key}
        ));
       
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            return WShop_Error::err_code($wpdb->last_error);
        }
       
        $this->refresh_cache();
        
        return WShop_Error::success();
    }
    
    /**
     * 删除数据
     * @since 1.0.0
     * @return WShop_Error
     */
    public function remove(){
        global $wpdb;
        $table_name ="{$wpdb->prefix}".$this->get_table_name();
        $primary_key = $this->get_primary_key();
        
        $wpdb->delete($table_name,array(
            $primary_key=>$this->{$primary_key}
         ));
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            return WShop_Error::err_code($wpdb->last_error);
        }
         
        wp_cache_delete($this->get_cache_key(),'wshop_entity');
         
        $properties = $this->get_propertys();
        foreach ($properties as $key=>$val){
            unset($this->{$key});
        }
        
        return WShop_Error::success();
    }
    
    /**
     * 获取实体
     * @param string $field_name
     * @param mixed $field_val
     * @since 1.0.0
     */
    public function get_by($field_name,$field_val){
        global $wpdb;
        $obj = $this->get_obj_by($field_name, $field_val);
       
        if($obj){
            foreach (shortcode_atts(array_merge($this->get_propertys(),$this->ext_propertys()), get_object_vars($obj)) as $key=>$val){               
                $this->{$key} = maybe_unserialize($val);
            }
        }
        
       
    }
    
    /**
     * @param string $field_name
     * @param mixed $field_val
     * @return object
     * @since 1.0.0
     */
    public function get_obj_by($field_name,$field_val,$refresh_cache = false){
        global $wpdb;
        $table_name ="{$wpdb->prefix}".$this->get_table_name();
        
        $entity = null;
        if(!$refresh_cache){ 
            $primary_key = $this->get_primary_key();
            if($primary_key==$field_name){
                $entity = wp_cache_get($this->get_cache_key($field_val),'wshop_entity');
                if($entity){
                    return $entity;
                }
            }
        }
      
        $entity =  $wpdb->get_row($wpdb->prepare(
           "select c.*
            from $table_name c
            where c.{$field_name}=%s
            limit 1;", $field_val));
       
        wp_cache_set($this->get_cache_key($field_val), $entity,'wshop_entity',30*60);
        
        return $entity;
    }
}

/**
 * 需要动态加载的实体类
 * @author rain
 */
abstract class WShop_Mixed_Object extends WShop_Object{
    public function __construct($object=null){
        parent::__construct($object);
        
        $this->class = get_called_class();
    }
    
    /**
     * 类型名
     * @var string
     */
    public $class;
}

abstract class WShop_Post_Object extends WShop_Object{
    public $post_ID;
    public function __construct($object=null){
        
        if($object&& $object instanceof WP_Post){
            parent::__construct($object->ID);
            return;
        }
        if($object&& $object instanceof WP_Comment){
            parent::__construct($object->comment_post_ID);
            return;
        }
        parent::__construct($object);
    }
    
    public function ext_propertys(){
        return array(
            'post_author' => 0,
            'post_content' => '',
            'post_content_filtered' => '',
            'post_title' => '',
            'post_excerpt' => '',
            'post_status' => 'draft',
            'post_type' => 'post',
            'comment_status' => '',
            'ping_status' => '',
            'post_password' => '',
            'to_ping' =>  '',
            'pinged' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'guid' => '',
            'import_id' => 0,
            'context' => '',
        );
    }
    
    public function get_obj_by($field_name,$field_val,$refresh_cache = false){
        global $wpdb;
        $table_name ="{$wpdb->prefix}".$this->get_table_name();
        
        $entity = null;
        if(!$refresh_cache){ 
            $primary_key = $this->get_primary_key();
            if($primary_key==$field_name){
                $entity = wp_cache_get($this->get_cache_key($field_val),'wshop_entity');
                
                if($entity){
                    return $entity;
                }
            }
        }
       
        $entity = $wpdb->get_row($wpdb->prepare(
           "select *
            from $table_name t
            inner join {$wpdb->prefix}posts p on p.ID = t.post_ID
            where t.{$field_name}=%s
            limit 1;", $field_val));
     
        wp_cache_set($this->get_cache_key($field_val), $entity,'wshop_entity',30*60);
        
        return $entity;
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
        return 'post_ID';
    }
    
    /**
     * 获取post
     * @return WP_Post
     * @since 1.0.0
     */
    public function get_post(){
        return get_post($this->post_ID);
    }
}
/**
 * 
 * @author rain
 *
 */
class WShop_Mixed_Object_Factory{
    /**
     * @param mixed $obj
     * @return WShop_Mixed_Object|NULL
     */
    public static function to_entity($obj){
        if(is_string($obj)){
            return new $obj();
        }
         
        if(is_array($obj)&&isset($obj['class'])){
            $class = $obj['class'];
            return new $class($obj);
        }
        
        if(is_object($obj)&&isset($obj->class)){
            $class = $obj->class;
            return new $class($obj);
        }
        
        return null;
    } 
}