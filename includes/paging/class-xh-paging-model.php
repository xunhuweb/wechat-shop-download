<?php

require_once 'abstract-xh-paging-model.php';
class WShop_Paging_Model extends WShop_Abstract_Paging_Model {
	var $urlCallback=null,$params = array();
	
	public function __construct($page_index, $page_size, $total_count, $urlCallback=null) {
		parent::__construct ( $page_index, $page_size, $total_count );
		
		$this->urlCallback = $urlCallback;
		$this->params[0]=null;
		$args_qty = func_num_args();
		if($args_qty>4){
		    for ($i=4;$i<$args_qty;$i++){
		        $this->params[]=func_get_arg($i);
		    }
		}
		
	}
	
	
	protected function url($page_index) {
		if($this->urlCallback==null){
			return '';
		}
		
		$this->params[0]=$page_index;
		return call_user_func_array($this->urlCallback, $this->params);
	}

	public function bootstrap($class = 'xh-pagination xh-pagination-sm') {
		if ($this->page_count <= 0) {
			return '';
		}
		$output = '<ul class="' . $class . '">';
		
		if (! $this->is_first_page) {
			$output .= '<li class="first"><a href="' . $this->url ( $this->page_index - 1 ) . '"><<</a></li>';
		} else {
			$output .= '<li class="first disabled"><span><<</span></li>';
		}
		
		if ($this->start_page_index > 1) {
			$output .= '<li><a href="' . $this->url ( 1 ) . '">1</a></li>';
			if ($this->start_page_index > 2) {
				$output .= '<li><span>...</span></li> ';
			}
		}
		
		for($i = $this->start_page_index; $i <= $this->end_page_index; $i ++) {
			$output .= '<li ' . ($i == $this->page_index ? 'class="page active"' : 'class="page"') . '><a href="' . $this->url ( $i ) . '">' . $i . '</a></li>';
		}
		
		if ($this->end_page_index < $this->page_count) {
			if ($this->end_page_index < $this->page_count - 1) {
				$output .= ' <li><span>...</span></li>';
			}
			$output .= '<li ><a href="' . $this->url ( $this->page_count ) . '">' . $this->page_count . '</a></li>';
		}
		
		if ($this->is_last_page) {
			$output .= '<li class="last disabled"><span>>></span></li>';
		} else {
			$output .= ' <li class="last"><a href="' . $this->url ( $this->page_index + 1 ) . '">>></a></li>';
		}
		
		$output .= "</ul>";
		return $output;
	}
}