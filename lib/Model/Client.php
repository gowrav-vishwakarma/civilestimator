<?php

class Model_Client extends Model_Table{
	public $table="client";

	function init(){
		parent::init();

		$this->addField('name')->mandatory(true);
		$this->addField('tender_premium');
		
		$this->hasMany('GSchedule','client_id');
		$this->hasMany('Project','client_id');
		$this->hasMany('Bill','client_id');
		$this->hasMany('ProjectType','client_id');
		$this->add('dynamic_model/Controller_AutoCreator');
	}
}
