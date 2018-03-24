<?php


class page_projectbills extends Page {
	function init(){
		parent::init();

		$pid= $this->app->stickyGET('project_id');

		IF($_GET['detail_measurement']){
			$this->app->redirect($this->app->url('billdetails',['bill_id'=>$_GET['detail_measurement']]));
		}

		$project = $this->add('Model_Project');
		$project->load($pid);
		$this->title = $project['name'];
		$this->add('View_Info')->set($project['name']);

		$bill = $this->add('Model_Bill');
		$bill->addCondition('project_id',$pid);

		$c = $this->add('CRUD',$this->add('Model_ACL')->setNoneForOthers());
		$c->setModel($bill);

		$c->grid->addColumn('Button','detail_measurement');
		$c->grid->addColumn('Button','abstract');
		// sno - scheule-code - description - previous qty - rate - unit - amount - current qty -rate -unit - amount - totalamount

	}
}