<?php


class page_clients extends Page {
	function init(){
		parent::init();

		if($_GET['g_schedule']){
			$this->app->redirect($this->app->url('gschedule',['client_id'=>$_GET['g_schedule']]));
		}

		$c = $this->add('CRUD',$this->add('Model_ACL')->setNoneForOthers());
		$c->setModel('Client');

		$c->grid->addColumn('Button','g_schedule');

		$c->grid->add('VirtualPage')
		      ->addColumn('project_types')
		      ->set(function($page){
		           $client_id = $_GET[$page->short_name.'_id'];
		           $pt_m = $this->add('Model_ProjectType');
		           $pt_m->addCondition('client_id',$client_id);
		           $page->add('CRUD',$this->add('Model_ACL')->setNoneForOthers())->setModel($pt_m);
		       });
		
		$c->grid->add('VirtualPage')
		      ->addColumn('projects')
		      ->set(function($page){
		           $client_id = $_GET[$page->short_name.'_id'];
		           $pt_m = $this->add('Model_Project');
		           $pt_m->addCondition('client_id',$client_id);
		           $c= $page->add('CRUD',$this->add('Model_ACL')->setNoneForOthers());
		           $c->setModel($pt_m);
		           if($c->isEditing()){
						$c->form->getElement('project_type_id')->getModel()->addCondition('client_id',$client_id);
					}
		       });

		$c->grid->add('VirtualPage')
		      ->addColumn('bills')
		      ->set(function($page){
					$client_id = $_GET[$page->short_name.'_id'];
					$bill_m = $this->add('Model_Bill');
					$bill_m->addCondition('client_id',$client_id);
					
					$c= $page->add('CRUD',$this->add('Model_ACL')->setNoneForOthers());
					$c->setModel($bill_m);
					
		           	if($bill_id = $_GET['details_of_work']){
						$this->app->redirect($this->app->url('billdetails',['client_id'=>$client_id,'bill_id'=>$bill_id]));
		           	}

		    //        	if($bill_id = $_GET['abstract']){
						// $this->app->redirect($this->app->url('abstract',['client_id'=>$client_id,'bill_id'=>$bill_id]));
		    //        	}

		           	if($bill_id = $_GET['general_abstract']){
						$this->app->redirect($this->app->url('generalabstract',['client_id'=>$client_id,'bill_id'=>$bill_id]));
		           	}

		           	if($bill_id = $_GET['qty_abstract']){
						$this->app->redirect($this->app->url('qtyabstract',['client_id'=>$client_id,'bill_id'=>$bill_id]));
		           	}

					$c->grid->addColumn('Button','details_of_work');
					$c->grid->addColumn('Button','qty_abstract');
					$c->grid->addColumn('Button','general_abstract');
		       });

	}
}