<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
 
/**
 * Log handler interface
 *
 * @since 1.0.0
 * @author ranj
 */
interface WShop_Log_Handler {
    /**
     * Log the msg
     * 
     * @param string $msg
     */
	public function write($msg);
}
