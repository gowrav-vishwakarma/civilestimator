<?php


class page_billdetails extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');

		$client_m = $this->add('Model_Client');
		$client_m->load($client_id);

		$bill_m = $this->add('Model_Bill');
		$bill_m->load($bill_id);

		$this->title = $client_m['name']. ' - ' . $bill_m['name'];
		$this->add('View_Info')->set($this->title);

		$bd_m = $this->add('Model_BillDetail');
		if($project_id){
			$bd_m->addCondition('project_id',$project_id);
		}
		$bd_m->addCondition('bill_id',$bill_id);

		$c = $this->add('CRUD');
		$c->setModel($bd_m);

		if($c->isEditing()){
			$c->form->getElement('project_id')->getModel()->addCondition('client_id',$client_id);
			$c->form->getElement('schedule_id')->getModel()->addCondition('client_id',$client_id);
		}

		$c->grid->setFormatter('description','wrap');
		$c->grid->addColumn('grand_total');

		$c->grid->addHook('formatRow',function($g){
			if(!isset($g->schedule)){
				$g->schedule = null;
				$g->schedule_sum = $g->model['qty'];
			}
			if($g->schedule == $g->model['schedule_id']){
				$g->current_row['description']="";
				$g->schedule_sum += $g->model['qty'];
				$g->current_row['grand_total']=$g->schedule_sum;
			}else{
				$g->current_row['description']=$g->model['description'];
				$g->schedule = $g->model['schedule_id'];
				$g->schedule_sum=0;
			}

			if(!isset($g->unit)) $g->unit = null;
			if($g->unit == $g->model['unit']){
				$g->current_row['unit']="";
			}else{
				$g->current_row['unit']=$g->model['unit'];
				$g->unit = $g->model['unit'];
			}

		});

		$c->grid->addOrder()->move('grand_total','after','unit')->now();

	}
}