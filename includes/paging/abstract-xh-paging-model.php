<?php
abstract class WShop_Abstract_Paging_Model {
	const URL_COUNT = 3;
	public $page_index, $page_size, $page_count, $start_page_index, $end_page_index, $is_first_page, $is_last_page;
	public $from_index,$to_index,$total_count;
	
	protected function __construct($page_index, $page_size, $total_count) {
		if ($page_size < 1) {
			$page_size = 1;
		}
		$this->total_count = $total_count;
		$page_count = ceil ( $total_count / ($page_size * 1.0) );
		if($page_count>0&&$page_index>$page_count){
		    $page_index=$page_count;
		}
		
		if ($page_index < 1) {
			$page_index = 1;
		}
		
		$this->page_index = $page_index;
		$this->page_size = $page_size;
		$this->page_count = $page_count;
		
		
		$this->start_page_index = $page_index - self::URL_COUNT > 0 ? $page_index - self::URL_COUNT : 1;
		$this->end_page_index = $page_index + self::URL_COUNT <= $page_count ? $page_index + self::URL_COUNT : $page_count;
		$this->is_first_page = $page_index == 1 || $page_count == 0;
		$this->is_last_page = $page_index == $page_count || $page_count == 0;
		$this->from_index =($page_index - 1) * $page_size + 1; 
		$this->to_index = $page_index == $page_count ? $total_count : ($page_index * $page_size);
	}
}

