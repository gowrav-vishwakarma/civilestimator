<?php

class Model_BillDetail extends Model_Table{
	
	public $table="bill_detail";

	function init(){
		parent::init();

		$this->hasOne('Project','project_id');
		$this->hasOne('Bill','bill_id');
		$this->hasOne('GSchedule','schedule_id');

		$this->addField('from_rd');
		$this->addField('to_rd');

		$this->addExpression('description')->set(function($m,$q){
			return $m->refSQL('schedule_id')->fieldQuery('description');
		});


		$this->addField('number');
		$this->addField('l');
		$this->addField('b');
		$this->addField('h');

		$this->addExpression('qty')->set('(number*l*b*h)');
		
		$this->addExpression('unit')->set(function($m,$q){
			return $m->refSQL('schedule_id')->fieldQuery('unit');
		});
		
		$this->addField('narration')->type('text');
		// $this->addField('name')->mandatory(true);
		// $this->addField('order')->type('int');

		$this->setOrder('schedule_id');

		$this->add('dynamic_model/Controller_AutoCreator');

	}
}
