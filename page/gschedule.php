<?php


class page_gschedule extends Page {
	
	function page_index(){

		$client_id = $this->app->stickyGET('client_id');

		$vp = $this->add('VirtualPage');
		$vp->set([$this,'manage_gschedule_acl']);

		$vpimport = $this->add('VirtualPage');
		$vpimport->set([$this,'import_data']);

		$gs_m = $this->add('Model_GSchedule');
		$gs_m->addCondition('client_id',$client_id);

		$c = $this->add('CRUD',$this->add('Model_ACL')->forGSchedule($client_id));
		$c->setModel($gs_m);

		if($this->app->auth->model['is_super']){
			$btn = $c->grid->addButton('ACL');
			$btn->js('click')->univ()->frameURL('ACL',$vp->getURL());

			$btn = $c->grid->addButton('Import');
			$btn->js('click')->univ()->frameURL('Import',$vpimport->getURL());
		}

		$c->grid->addTotals(['amount']);
	}

	function import_data($p){

		$client_id = $this->app->stickyGET('client_id');
		$bill_id = $this->app->stickyGET('bill_id');
		$project_id = $this->app->stickyGET('project_id');


		$form = $p->add('Form');
		$form->addSubmit('Download Sample File')->addClass('btn btn-primary');
		
		if($_GET['download_sample_csv_file']){
			$output = ['code','description','qty','unit','rate'];
			$output = implode(",", $output);
	    	header("Content-type: text/csv");
	        header("Content-disposition: attachment; filename=\"sample_xepan_gschedule_import.csv\"");
	        header("Content-Length: " . strlen($output));
	        header("Content-Transfer-Encoding: binary");
	        print $output;
	        exit;
		}

		if($form->isSubmitted()){
			$form->js()->univ()->newWindow($form->app->url('.',['download_sample_csv_file'=>true]))->execute();
		}	

		if(!$client_id){
			$p->add('View_Error')->set('Client Must be selected to import data');
			return;
		}
		$p->add('View')->setElement('iframe')->setAttr('src',$this->api->url('./execute',array('cut_page'=>1)))->setAttr('width','100%');

	}

	function page_execute(){

		ini_set("memory_limit", "-1");
		set_time_limit(0);

		$client_id = $this->app->stickyGET('client_id');
		

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

				$unique_code=[];
				foreach ($data as $datum) {
					if(in_array($datum['code'],$unique_code)){
						throw new Exception("Unique Code is duplicated in file - ". $datum['code'], 1);
					}

					$unique_code[] = $datum['code'];
					$g_detail = $this->add('Model_GSchedule');
					$g_detail->addCondition('name',$datum['code']);
					$g_detail->tryLoadAny();

					if($g_detail->loaded()){
						throw new \Exception("Code ". $datum['code']. ' already exists in GSchedule for this client', 1);
					}

					
				}

				foreach ($data as $datum) {

					$g_detail = $this->add('Model_GSchedule');
					$g_detail['client_id'] = $client_id;
					$g_detail['name'] = $datum['code'];
					$g_detail['description'] = $datum['description'];
					$g_detail['qty'] = $datum['qty'];
					$g_detail['unit'] = $datum['unit'];
					$g_detail['rate'] = $datum['rate'];

					$g_detail->save();

				}
				

				
				$this->add('View')->set('All Data Imported');
			}
		}

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