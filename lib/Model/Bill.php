<?php

class Model_Bill extends Model_Table{
	
	public $table="project_bill";

	function init(){
		parent::init();

		$this->addField('code')->mandatory(true);
		$this->hasOne('Client','client_id');
		$this->addField('name')->mandatory(true);
		$this->addField('order')->type('int');

		$this->setOrder('order');

		$this->add('dynamic_model/Controller_AutoCreator');

	}
}
