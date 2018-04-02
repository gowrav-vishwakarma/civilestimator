<?php


class page_billdetails extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');

		$vp = $this->add('VirtualPage');
		$vp->set([$this,'manage_bill_detail_acl']);

		$client_m = $this->add('Model_Client');
		$client_m->load($client_id);

		$bill_m = $this->add('Model_Bill');
		$bill_m->load($bill_id);

		$this->title = $client_m['name']. ' - ' . $bill_m['name'];
		$this->add('View_Info')->set($this->title);

		$c = $this->add('CRUD',$this->add('Model_ACL')->forBillDetail($bill_id));
		$form = $c->grid->add('Form',null,'grid_buttons',['form/stacked']);
		$p_f = $form->addField('Dropdown','project')->setEmptyText('All');
		$p_f->setModel('Project')->addCondition('client_id',$client_id);
		$p_f->js('change',$form->js()->submit());
		
		$bd_m = $this->add('Model_BillDetail');
		if($project_id){
			$bd_m->addCondition('project_id',$project_id);
			$p_f->set($project_id);
		}
		$bd_m->addCondition('bill_id',$bill_id);


		$c->setModel($bd_m);

		if($c->isEditing()){
			if($c->form->hasElement('project_id'))
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
				$g->schedule_sum=$g->model['qty'];
				$g->current_row['grand_total']=$g->schedule_sum;
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

		if($this->app->auth->model['is_super']){
			$btn = $c->grid->addButton('ACL');
			$btn->js('click')->univ()->frameURL('ACL',$vp->getURL());
		}

		if(!$c->isEditing()){
			if($form->isSubmitted()){
				$this->js()->reload(['project_id'=>$form['project']])->execute();
			}
		}

	}


	function manage_bill_detail_acl($page){
		$client_id = $page->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');

		$client_m = $this->add('Model_Client')->load($client_id);
		$bill_m = $this->add('Model_Bill')->load($bill_id);

		$page->add('View_Info')->set($this->app->auth->model['name'].' for '. $client_m['name'].'\'s '. $bill_m['name']);

		$acl_m = $page->add('Model_ACL');
		// $acl_m->addCondition('staff_id',$page->app->auth->model->id);
		$acl_m->addCondition('details_of_bill_id',$bill_id);

		$c = $page->add('CRUD');
		$c->setModel($acl_m,['staff_id','allow_add','allow_edit','allow_del'],['staff','allow_add','allow_edit','allow_del']);
	}
}