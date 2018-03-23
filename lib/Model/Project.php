<?php

class Model_Project extends Model_Table{
	
	public $table="projects";

	function init(){
		parent::init();

		$this->hasOne('Client','client_id');
		$this->hasOne('ProjectType','project_type_id');
		$this->addField('name')->mandatory(true);

		$this->add('dynamic_model/Controller_AutoCreator');

	}
}
