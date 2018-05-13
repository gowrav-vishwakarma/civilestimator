<?php

class Model_Project extends Model_Table{
	
	public $table="projects";

	function init(){
		parent::init();

		$this->addField('code')->mandatory(true);
		
		$this->hasOne('Client','client_id');
		$this->hasOne('ProjectType','project_type_id');
		$this->addField('name')->mandatory(true);

		$this->hasMany('BillDetail','project_id');
		$this->addHook('beforeDelete',$this);	
		
		$this->add('dynamic_model/Controller_AutoCreator');

	}

	function beforeDelete(){
		if($this->ref('BillDetail')->count()->getOne() > 0 )
			throw $this->exception('Project is used in Bill Details');
	}
}
