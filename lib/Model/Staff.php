<?php

class Model_Staff extends Model_Table{
	public $table="staffs";

	function init(){
		parent::init();

		$this->addField('name')->mandatory(true);
		$this->addField('username');
		$this->addField('password')->type('password');
		$this->add('dynamic_model/Controller_AutoCreator');


	}
}
