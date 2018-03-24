<?php


class page_gschedule extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');

		$vp = $this->add('VirtualPage');
		$vp->set([$this,'manage_gschedule_acl']);

		$gs_m = $this->add('Model_GSchedule');
		$gs_m->addCondition('client_id',$client_id);

		$c = $this->add('CRUD',$this->add('Model_ACL')->forGSchedule($client_id));
		$c->setModel($gs_m);

		if($this->app->auth->model['is_super']){
			$btn = $c->grid->addButton('ACL');
			$btn->js('click')->univ()->frameURL('ACL',$vp->getURL());
		}

		$c->grid->addTotals(['amount']);
	}

	function manage_gschedule_acl($page){
		$client_id = $page->app->stickyGET('client_id');
		$client_m = $this->add('Model_Client')->load($client_id);

		$page->add('View_Info')->set($this->app->auth->model['name'].' for '. $client_m['name'].'\'s GSchedule ');

		$acl_m = $page->add('Model_ACL');
		// $acl_m->addCondition('staff_id',$page->app->auth->model->id);
		$acl_m->addCondition('gschedule_of_client_id',$client_id);

		$c = $page->add('CRUD');
		$c->setModel($acl_m,['staff_id','allow_add','allow_edit','allow_del'],['staff','allow_add','allow_edit','allow_del']);

	}

}