<?php

class Model_ProjectType extends Model_Table{
	
	public $table="project_types";

	function init(){
		parent::init();

		$this->hasOne('Client','client_id');
		$this->addField('name')->mandatory(true);

		$this->hasMany('Project','project_type_id');

		$this->addHook('beforeDelete',function($m){
			if($m->ref('Project')->count()->getOne() > 0)
				throw $this->exception('Project Type contains project, to remove project type, remove all projects first');
		});

		$this->add('dynamic_model/Controller_AutoCreator');

	}
}
