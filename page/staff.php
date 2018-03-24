<?php


class page_staff extends Page {
	
	public $title = "Staff Management";

	function init(){
		parent::init();

		$staff_m = $this->add('Model_Staff');
		$c = $this->add('CRUD',$this->add('Model_ACL')->setNoneForOthers());
		$c->setModel($staff_m);

		$c->grid->add('VirtualPage')
		      ->addColumn('permissions','Permissions')
		      ->set(function($page){
					$staff_id = $_GET[$page->short_name.'_id'];
					
		       });
	}
}