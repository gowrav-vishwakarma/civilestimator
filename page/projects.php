<?php


class page_projects extends Page {
	function init(){
		parent::init();

		if($_GET['manage_bills']){
			$this->app->redirect($this->app->url('projectbills',['project_id'=>$_GET['manage_bills']]));
		}

		$c = $this->add('CRUD',$this->add('Model_ACL')->setNoneForOthers());
		$c->setModel('Project');

		$c->grid->addColumn('Button','manage_bills');
	}
}