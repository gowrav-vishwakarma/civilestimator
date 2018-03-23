<?php


class page_gschedule extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');

		$gs_m = $this->add('Model_GSchedule');
		$gs_m->addCondition('client_id',$client_id);

		$c = $this->add('CRUD');
		$c->setModel($gs_m);

		$c->grid->addTotals(['amount']);
	}
}