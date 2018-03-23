<?php


class page_projects extends Page {
	function init(){
		parent::init();

		if($_GET['manage_bills']){
			$this->app->redirect($this->app->url('projectbills',['project_id'=>$_GET['manage_bills']]));
		}

		$c = $this->add('CRUD');
		$c->setModel('Project');

		$c->grid->addColumn('Button','manage_bills');
	}
}