<?php

class Model_GSchedule extends Model_Table{
	public $table="g_schedule";

	function init(){
		parent::init();

		$this->hasOne('Client','client_id');

		$this->addField('name')->caption('code')->mandatory(true);
		$this->addField('description')->type('text');
		$this->addField('qty')->type('number');
		$this->addField('unit');
		$this->addField('rate')->type('number');
		$this->addField('amount')->type('money');

		$this->addHook('beforeSave',$this);	

		$this->add('dynamic_model/Controller_AutoCreator');

	}

	function beforeSave(){
		if(!$this['amount']) $this['amount'] = $this['qty'] * $this['rate'];
	}
}
