<?php


class page_generalabstract extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');

		$client_m = $this->add('Model_Client');
		$client_m->load($client_id);

		$bill_m = $this->add('Model_Bill')->load($bill_id);

		$this->title = $client_m['name']. ' - ' . $bill_m['name'] .' [Abstract]';
		$this->add('View_Info')->set($this->title);

		$f = $this->add('Form');
		$prj_field = $f->addField('DropDown','projects')->setEmptyText('All');
		$prj_field->setModel('Project')->addCondition('client_id',$client_id);

		$gs_m = $this->add('Model_GSchedule');
		$gs_m->addCondition('client_id',$client_id);

		$gs_m->addExpression('previous_qty')->set(function($m,$q)use($bill_m,$client_id,$bill_id,$project_id){
		
			$bdm =  $m->add('Model_BillDetail');
			$bdm->join('project_bill','bill_id')->addField('order');
			$bdm->addCondition('schedule_id',$q->getField('id'));
			
			$bdm->addCondition('order','<',$bill_m['order']);

			if($project_id)
				$bdm->addCondition('project_id',$project_id);

			return $bdm->sum('qty');
		});

		$gs_m->addExpression('current_qty')->set(function($m,$q)use($bill_m,$client_id,$bill_id,$project_id){

			$bdm =  $m->add('Model_BillDetail');
			$bdm->join('project_bill','bill_id')->addField('order');
			$bdm->addCondition('schedule_id',$q->getField('id'));
			$bdm->addCondition('order',$bill_m['order']);
			
			if($project_id)
				$bdm->addCondition('project_id',$project_id);
			return $bdm->sum('qty');
		});

		$gs_m->addExpression('previous_amt')->set(function($m,$q){
			return $q->expr('([0]*[1])',[$m->getElement('rate'),$m->getElement('previous_qty')]);
		})->type('money');

		$gs_m->addExpression('current_amt')->set(function($m,$q){
			return $q->expr('([0]*[1])',[$m->getElement('rate'),$m->getElement('current_qty')]);
		})->type('money');

		$gs_m->addCondition([['previous_qty','>',0],['current_qty','>',0]]);

		$g = $this->add('Grid');
		$g->setModel($gs_m,['name','','description','rate','unit','previous_qty','previous_amt','current_qty','current_amt']);

		// $g->addTotals(['amount']);

		if($f->isSubmitted()){
			$g->js()->reload(['project_id'=>$f['projects']])->execute();
		}

		$prj_field->js('change',$f->js()->submit());

	}
}