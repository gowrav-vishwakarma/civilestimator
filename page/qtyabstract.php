<?php


class page_qtyabstract extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');

		$client_m = $this->add('Model_Client');
		$client_m->load($client_id);

		$bill_m = $this->add('Model_Bill');
		if($bill_id)
			$bill_m->load($bill_id);

		$this->title = $client_m['name']. ' - ' . $bill_m['name'] .' [Qty Distribution Sheet] ';
		$v= $this->add('View');
		$v->add('View_Info')->set($this->title);

		$f = $v->add('Form');
		$bill_field = $f->addField('DropDown','bills')->set($bill_id);
		$bill_field->setModel('Bill')->addCondition('client_id',$client_id);

		$gs_m = $this->add('Model_GSchedule');
		$gs_m->addCondition('client_id',$client_id);

		$gs_m->addExpression('previous_qty')->set(function($m,$q)use($bill_m,$client_id,$bill_id){
		
			$bdm =  $m->add('Model_BillDetail');
			$bdm->join('project_bill','bill_id')->addField('order');
			$bdm->addCondition('schedule_id',$q->getField('id'));
			
			$bdm->addCondition('order','<',$bill_m['order']);

			return $bdm->sum('qty');
		})->type('money');

		$project_m = $this->add('Model_Project');
		$project_m->addCondition('client_id',$client_id);

		$prj_name_arr=[];
		foreach ($project_m as $prj_m) {
			$prj_name_arr[] = $exp_name = $this->app->normalizeName($prj_m['name']);
			$prj_id=$project_m->id;
			$gs_m->addExpression($exp_name)->set(function($m,$q)use($bill_m,$prj_id){
				$bdm =  $m->add('Model_BillDetail');
				$bdm->join('project_bill','bill_id')->addField('order');
				$bdm->addCondition('schedule_id',$q->getField('id'));
				
				$bdm->addCondition('order',$bill_m['order']);
				$bdm->addCondition('project_id',$prj_id);
				return $bdm->sum('qty');
			})->type('money');
		}

		$gs_m->addExpression('total_qty')->set(function($m,$q)use($project_m){
			$sum_arr= ["IFNULL([0],0)"];
			$elemnet_arr=[0=>$m->getElement('previous_qty')];
			$i=1;
			foreach ($project_m as $prj_m) {
				$exp_name = $this->app->normalizeName($prj_m['name']);
				$sum_arr[] = "IFNULL([$i],0)";
				$elemnet_arr[$i] = $m->getElement($exp_name);
				$i++;
			}
			return $q->expr(implode("+", $sum_arr),$elemnet_arr);
		})->type('money');
		
		$g = $v->add('Grid');
		$g->setModel($gs_m,array_merge(['name','','description','rate','unit','previous_qty'],$prj_name_arr,['total_qty']));

		$g->addTotals(array_merge(['previous_qty'],$prj_name_arr,['total_qty']));

		if($f->isSubmitted()){
			$v->js()->reload(['bill_id'=>$f['bills']])->execute();
		}

		$bill_field->js('change',$f->js()->submit());

	}
}