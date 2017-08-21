<?php
require_once 'abstract-xh-paging-model.php';
class WShop_Paging_Model_Simple extends WShop_Abstract_Paging_Model{
	public function __construct($page_index, $page_size, $total_count){
		parent::__construct ( $page_index, $page_size, $total_count );
	}

}