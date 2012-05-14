<?php
App::uses('StripeAppModel', 'Stripe.Model');

class StripeAppTest extends CakeTestCase {
	
	public function setUp() {
		parent::setUp();
		$this->Model = new StripeAppModel();
		$this->Model->setDataSource('stripe_test');
		$sources = ConnectionManager::enumConnectionObjects();
		$this->skipIf(!in_array('stripe_test', array_keys($sources)), '`stripe_test` db config not found');
	}
	
	public function tearDown() {
		parent::tearDown();
		unset($this->Model);
	}
	
	public function testGetStripeError() {
		$ds = ConnectionManager::getDataSource('stripe_test');
		$ds->lastError = 'Some error! Ack something broke!';
		
		$this->assertEqual($this->Model->getStripeError(), $ds->lastError);
	}
	
}