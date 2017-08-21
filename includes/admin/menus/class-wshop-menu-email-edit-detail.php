<?php

if ( ! defined ( 'ABSPATH' ) ) {
	die();
}

class WShop_Email_Edit_Detail{
    /**
     * @var WShop_Email
     */
    private $email;
    
    public function __construct($id=0){
        $this->email = new WShop_Email($id);
    }
   
	public function view() {
	    if(!$this->email->is_load()){
	        ?><script type="text/javascript">
			location.href="<?php echo admin_url('admin.php?page=wshop_page_default&section=add_ons_menu_email_edit')?>";
			</script> <?php 
	        return;
	    }
	    
	    if(isset($_POST['notice'])){
	        try {
	            if(wp_verify_nonce($_POST['notice'], WShop::instance()->session->get_notice('admin:form:email_edit',true))){        
	                $error =$this->email->update(array(
	                   'enabled'=>$_POST['enabled']=='yes'?1:0,
	                   'recipients'=>explode(',', $_POST['recipients']),
	                   'subject'=>$_POST['subject'],
	                   'email_type'=>$_POST['email_type']
	               ));
	               
	               if(!WShop_Error::is_valid($error)){
	                   throw new Exception($error->errmsg);
	               }
	               
	               $this->email = new WShop_Email($this->email->template_id);
	               ?>
	               <div id="message" class="success notice notice-success is-dismissible">
               		<p><?php echo __('Data saved successfully!',WSHOP);?></p>
               		<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php print __('Ignore')?></span></button>
               		</div>
	               <?php 
	            }else{
	                throw new Exception(WShop_Error::err_code(701)->errmsg);
	            }
	        } catch (Exception $e) {
	            ?><div id="message" class="error notice notice-error is-dismissible">
	            <p>
	            <?php echo $e->getMessage();?>
        		</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php print __('Ignore')?></span></button></div>
	            <?php 
	        }	            
	   }
        ?>
        
         <form method="post" id="mainform" action="" enctype="multipart/form-data">
			<h2><?php echo $this->email->system_name?></h2>

			<p><?php echo $this->email->description?></p>

			<table class="form-table">
				<tbody>
				<tr valign="top">
        			<th scope="row" class="titledesc">
        				<label for="wshop_new_order_enabled"><?php echo __('Enabled/Disabled',WSHOP)?></label>
        			</th>
        			<td class="forminp">
        				<fieldset>
        					<legend class="screen-reader-text"><span><?php echo __('Enabled/Disabled',WSHOP)?></span></legend>
        					<label>
        					<input type="checkbox" name="enabled" value="yes" <?php echo $this->email->enabled?"checked":"";?>> <?php echo __('Enable this email notification',WSHOP)?>
        					</label><br>
        				</fieldset>
        			</td>
        		</tr>
        		
				<tr valign="top">
        			<th scope="row" class="titledesc">
        				<span class="wshop-help-tip"></span>				
        				<label for="wshop_new_order_recipient"><?php echo __('Recipients',WSHOP)?></label>
        			</th>
        			<td class="forminp">
        				<fieldset>
        					<legend class="screen-reader-text"><span><?php echo __('Recipients',WSHOP)?></span></legend>
        					<input style="width:400px;" class="input-text regular-input" type="text" name="recipients" id="recipients" value="<?php echo $this->email->recipients&&is_array($this->email->recipients)?join(',', $this->email->recipients):null; ?>" />
        					<div class="description"></div>
        				</fieldset>
        			</td>
        		</tr>
        		
				<tr valign="top">
        			<th scope="row" class="titledesc">
        				<span class="wshop-help-tip"></span>				
        				<label for="wshop_new_order_subject"><?php echo __('Subject',WSHOP)?></label>
        			</th>
        			<td class="forminp">
        				<fieldset>
        					<legend class="screen-reader-text"><span><?php echo __('Subject',WSHOP)?></span></legend>
        					<input style="width:400px;" class="input-text regular-input" type="text" name="subject" id="subject" value="<?php echo $this->email->subject ?>" />
        				</fieldset>
        			</td>
        		</tr>
			
				<tr valign="top">
        			<th scope="row" class="titledesc">
        				<span class="wshop-help-tip"></span>				
        				<label for="wshop_new_order_email_type"><?php echo __( 'Email type', WSHOP );?></label>
        			</th>
        			<td class="forminp">
        				<fieldset>
        					<legend class="screen-reader-text"><span><?php echo __( 'Email type', WSHOP );?></span></legend>
        					<select class="select" name="email_type" id="email_type" >
								<?php foreach (WShop_Email::$email_types as $key=>$val){
								    ?>
								    <option value="<?php echo $key?>" <?php echo $key==$this->email->email_type?"selected":""?>><?php echo $val?></option>
								    <?php 
								}?>
							</select>
								
						</fieldset>
        			</td>
        		</tr>
				</tbody>
				</table>
					<div id="template">
						<div class="template template_html">
    						<h4><?php echo __('Email template')?></h4>
    						<?php 
    						$theme_dir = get_template_directory ();
    						?>
    						<p><?php echo sprintf(__('Copy %s to your theme folder:%s.',WSHOP),"<code>templates/emails/{$this->email->template_id}.php</code>","<code>[theme]/wechat-shop/emails/{$this->email->template_id}.php</code>")?></p>
    					</div>
					</div>
				<p class="submit">
				<input type="hidden" name="notice" value="<?php print wp_create_nonce ( WShop::instance()->session->get_notice('admin:form:email_edit'));?>"/>
				<input class="button-primary wshop-save-button" type="submit" value="<?php echo __('Save change',WSHOP)?>">
				</p>
			</form>  
		<?php
	}
}
