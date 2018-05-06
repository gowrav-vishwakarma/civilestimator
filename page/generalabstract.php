<?php


class page_generalabstract extends Page {
	function init(){
		parent::init();

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');

		$client_m = $this->add('Model_Client');
		$client_m->load($client_id);

		$bill_m = $this->add('Model_Bill');
		if($bill_id)
			$bill_m->load($bill_id);

		$project_m = $this->add('Model_Project');
		if($project_id)
			$project_m->load($project_id);

		$this->title = $client_m['name']. ' - ' . $bill_m['name'] .' [Abstract] @' . abs($client_m['tender_premium']).'% '.($client_m['tender_premium']>0?'Above':'Below');
		if($project_id){
			$this->title .= " Filtered For ". $project_m['name'];
		}
		$v= $this->add('View');
		$v->add('View_Info')->set($this->title);

		$f = $v->add('Form');
		$prj_field = $f->addField('DropDown','projects')->setEmptyText('All')->set($project_id);
		$bill_field = $f->addField('DropDown','bills')->set($bill_id);
		$prj_field->setModel('Project')->addCondition('client_id',$client_id);
		$bill_field->setModel('Bill')->addCondition('client_id',$client_id);

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
		})->type('money');

		$gs_m->addExpression('current_qty')->set(function($m,$q)use($bill_m,$client_id,$bill_id,$project_id){

			$bdm =  $m->add('Model_BillDetail');
			$bdm->join('project_bill','bill_id')->addField('order');
			$bdm->addCondition('schedule_id',$q->getField('id'));
			$bdm->addCondition('order',$bill_m['order']);
			
			if($project_id)
				$bdm->addCondition('project_id',$project_id);
			return $bdm->sum('qty');
		})->type('money');

		$gs_m->addExpression('total_qty')->set(function($m,$q){
			return $q->expr('IFNULL([0],0)+IFNULL([1],0)',[$m->getElement('previous_qty'),$m->getElement('current_qty')]);
		})->type('money');

		$gs_m->addExpression('previous_amt')->set(function($m,$q){
			return $q->expr('([0]*[1])',[$m->getElement('rate'),$m->getElement('previous_qty')]);
		})->type('money');

		$gs_m->addExpression('current_amt')->set(function($m,$q){
			return $q->expr('([0]*[1])',[$m->getElement('rate'),$m->getElement('current_qty')]);
		})->type('money');

		$gs_m->addExpression('total_amt')->set(function($m,$q){
			return $q->expr('IFNULL([0],0)+IFNULL([1],0)',[$m->getElement('previous_amt'),$m->getElement('current_amt')]);
		})->type('money');

		$gs_m->addCondition([['previous_qty','>',0],['current_qty','>',0]]);
		
		$g = $v->add('Grid');
		$g->setModel($gs_m,['name','','description','rate','unit','previous_qty','previous_amt','current_qty','current_amt','total_qty','total_amt','qty']);

		$g->addHook('formatRow',function($g){
			if($g->model['qty']){
				$per = $g->model['total_qty']/$g->model['qty']*100;
				$g->current_row_html['description'] = $g->model['description']."<br/><div style='width:100%;outline:1px solid black !important;height:5px'><div style='width:$per%;height:5px;background-color:black'></div></div><br/>".round($per,2).'%';
			}
		});

		$g->addTotals(['previous_amt','current_amt']);
		$g->removeColumn('qty');

		$current_amt_sum = round($gs_m->sum('current_amt')->getOne(),2);
		$previous_amt_sum = round($gs_m->sum('previous_amt')->getOne(),2);

		$str = 'Previous:'. $previous_amt_sum. ' [@ '.$client_m['tender_premium'].'% = '.(round($previous_amt_sum*$client_m['tender_premium']/100,2)).'] Total: '. (round($previous_amt_sum + ($previous_amt_sum*$client_m['tender_premium']/100),2)) .'<br/>';
		$str .= 'Current:'. $current_amt_sum. ' [@ '.$client_m['tender_premium'].'% = '.(round($current_amt_sum*$client_m['tender_premium']/100,2)).'] Total: '.(round($current_amt_sum + ($current_amt_sum*$client_m['tender_premium']/100),2)) .'<br/>';
		// $str .= 'Payable: '. (($current_amt_sum + ($current_amt_sum*$client_m['tender_premium']/100)) - ($previous_amt_sum + ($previous_amt_sum*$client_m['tender_premium']/100)));
		$v->add('View_Info')->setHtml($str);

		if($f->isSubmitted()){
			$v->js()->reload(['project_id'=>$f['projects'],'bill_id'=>$f['bills']])->execute();
		}

		// $g->add('misc/export');

		$prj_field->js('change',$f->js()->submit());
		$bill_field->js('change',$f->js()->submit());

	}
}