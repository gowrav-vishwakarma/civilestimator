<?php


class page_billdetails extends Page {
	
	function page_index(){

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');

		$vp = $this->add('VirtualPage');
		$vp->set([$this,'manage_bill_detail_acl']);

		$vpimport = $this->add('VirtualPage');
		$vpimport->set([$this,'import_data']);

		$client_m = $this->add('Model_Client');
		$client_m->load($client_id);

		$bill_m = $this->add('Model_Bill');
		if($bill_id){
			$bill_m->load($bill_id);
		}

		$proj_name_m = $this->add('Model_Project');
		
		if($project_id){
			$proj_name_m->load($project_id);
		}

		$this->title = $client_m['name']. ' - ' . $bill_m['name']. ' - '. $proj_name_m['name'];
		$this->add('View_Info')->set($this->title);

		$c = $this->add('CRUD',$this->add('Model_ACL')->forBillDetail($bill_id));
		$form = $c->grid->add('Form',null,'grid_buttons',['form/stacked']);
		$p_f = $form->addField('autocomplete/Basic','project');
		$prj_m = $this->add('Model_Project');
		$prj_m->addExpression('name_with_code')->set('CONCAT(name," - ",code)');
		$prj_m->addCondition('client_id',$client_id);
		$prj_m->title_field='name_with_code';
		$p_f->setModel($prj_m);

		$b_f = $form->addField('DropDown','bill_id');
		$b_f->setEmptyText('All');
		$b_f->setModel('Model_Bill')->addCondition('client_id',$client_id);
		$b_f->set($bill_id);

		$form->addSubmit('Filter');
		
		$bd_m = $this->add('Model_BillDetail');
		$bd_m->addCondition('client_id',$client_id);
		$bd_m->setOrder(['schedule_id asc','id asc']);

		if($project_id){
			$bd_m->addCondition('project_id',$project_id);
			$p_f->set($project_id);
		}


		if($bill_id)
			$bd_m->addCondition('bill_id',$bill_id);


		$c->setModel($bd_m);

		$c->add('Controller_MultiDelete');

		if($c->isEditing()){
			if($c->form->hasElement('project_id'))
				$c->form->getElement('project_id')->getModel()->addCondition('client_id',$client_id);
			$c->form->getElement('schedule_id')->getModel()->addCondition('client_id',$client_id);
		}

		$c->grid->setFormatter('description','wrap');
		$c->grid->setFormatter('narration','wrap');
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

			$g->current_row['grand_total'] = round($g->current_row['grand_total'],2);

		});

		$c->grid->addOrder()->move('grand_total','after','unit')->now();

		if($this->app->auth->model['is_super']){
			$btn = $c->grid->addButton('ACL');
			$btn->js('click')->univ()->frameURL('ACL',$vp->getURL());

			$btn = $c->grid->addButton('Import');
			$btn->js('click')->univ()->frameURL('Import',$vpimport->getURL());
		}

		if(!$c->isEditing()){
			if($form->isSubmitted()){
				$this->app->redirect($this->app->url((['project_id'=>$form['project'],'bill_id'=>$form['bill_id']?:0])));
			}
		}

	}


	function manage_bill_detail_acl($page){
		$client_id = $page->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');

		$client_m = $this->add('Model_Client');
		if($client_id)
			$client_m->load($client_id);
		$bill_m = $this->add('Model_Bill');

		if($bill_id)
			$bill_m->load($bill_id);

		$page->add('View_Info')->set($this->app->auth->model['name'].' for '. $client_m['name'].'\'s '. $bill_m['name']);

		$acl_m = $page->add('Model_ACL');
		// $acl_m->addCondition('staff_id',$page->app->auth->model->id);
		if($bill_id)
			$acl_m->addCondition('details_of_bill_id',$bill_id);

		$c = $page->add('CRUD');
		$c->setModel($acl_m,['details_of_bill_id','staff_id','allow_add','allow_edit','allow_del'],['details_of_bill','staff','allow_add','allow_edit','allow_del']);
	}

	function import_data($p){

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');


		$form = $p->add('Form');
		$form->addSubmit('Download Sample File')->addClass('btn btn-primary');
		
		if($_GET['download_sample_csv_file']){
			$output = ['g_schdule_code','number','l','b','h','narration','from_rd','to_rd'];
			$output = implode(",", $output);
	    	header("Content-type: text/csv");
	        header("Content-disposition: attachment; filename=\"sample_xepan_isp_user_import.csv\"");
	        header("Content-Length: " . strlen($output));
	        header("Content-Transfer-Encoding: binary");
	        print $output;
	        exit;
		}

		if($form->isSubmitted()){
			$form->js()->univ()->newWindow($form->app->url('.',['download_sample_csv_file'=>true]))->execute();
		}	

		if(!$project_id){
			$p->add('View_Error')->set('Project Must be filtered to import data');
			return;
		}
		$p->add('View')->setElement('iframe')->setAttr('src',$this->api->url('./execute',array('cut_page'=>1)))->setAttr('width','100%');

	}

	function page_execute(){

		ini_set("memory_limit", "-1");
		set_time_limit(0);

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');
		

		$form= $this->add('Form');
		$form->template->loadTemplateFromString("<form method='POST' action='".$this->api->url(null,array('cut_page'=>1))."' enctype='multipart/form-data'>
			<input type='file' name='csv_file'/>
			<input type='submit' value='Upload'/>
			</form>"
			);

		if($_FILES['csv_file']){
			if ( $_FILES["csv_file"]["error"] > 0 ) {
				$this->add( 'View_Error' )->set( "Error: " . $_FILES["csv_file"]["error"] );
			}else{
				$mimes = ['text/comma-separated-values', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.ms-excel', 'application/vnd.msexcel', 'text/anytext'];
				if(!in_array($_FILES['csv_file']['type'],$mimes)){
					$this->add('View_Error')->set('Only CSV Files allowed');
					return;
				}

				$importer = new CSVImporter($_FILES['csv_file']['tmp_name'],true,',');
				$data = $importer->get();

				foreach ($data as $datum) {
					$g_schdule_code = $this->add('Model_GSchedule')
										->addCondition('client_id',$client_id)
										->addCondition('name',$datum['g_schdule_code'])
										->tryLoadAny();

					if(!$g_schdule_code->loaded()){
						throw new Exception("G schedule code not found in system for ". print_r($datum,true). ' data', 1);
					}
					if(!is_numeric($datum['number']) || !is_numeric($datum['l']) || !is_numeric($datum['b']) || !is_numeric($datum['b']) || !is_numeric($datum['h']) ){
							throw new Exception('data contained non numeric value for '. print_r($datum,true), 1);
					}
				}

				foreach ($data as $datum) {

					$g_schdule_code = $this->add('Model_GSchedule')
										->addCondition('client_id',$client_id)
										->addCondition('name',$datum['g_schdule_code'])
										->tryLoadAny();

					$bill_detail = $this->add('Model_BillDetail');
					$bill_detail['project_id'] = $project_id;
					$bill_detail['bill_id'] = $bill_id;
					$bill_detail['schedule_id'] = $g_schdule_code->id;
					$bill_detail['number'] = $datum['number'];
					$bill_detail['l'] = $datum['l'];
					$bill_detail['b'] = $datum['b'];
					$bill_detail['h'] = $datum['h'];
					$bill_detail['narration'] = $datum['narration'];
					$bill_detail['from_rd'] = $datum['from_rd'];
					$bill_detail['to_rd'] = $datum['to_rd'];

					$bill_detail->save();

				}
				

				
				$this->add('View')->set('All Data Imported');
			}
		}

	}
}