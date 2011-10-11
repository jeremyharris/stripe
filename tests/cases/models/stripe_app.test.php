<?php
App::import('Model', 'Stripe.StripeAppModel');

class TestStripeAppModel extends CakeTestCase {
	
	function startTest() {
		$this->Model = new StripeAppModel();
		$this->Model->setDataSource('stripe_test');
		$sources = ConnectionManager::enumConnectionObjects();
		$this->skipIf(!in_array('stripe_test', array_keys($sources)), '`stripe_test` db config not found');
	}
	
	function endTest() {
		unset($this->Model);
	}
	
	function testGetStripeError() {
		$ds = ConnectionManager::getDataSource('stripe_test');
		$ds->lastError = 'Some error! Ack something broke!';
		
		$this->assertEqual($this->Model->getStripeError(), $ds->lastError);
	}
	
}