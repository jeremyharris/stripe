<?php
App::import('Model', 'Stripe.StripePlan');

class TestPlan extends CakeTestCase {
	
	function startTest() {
		$this->Model = new StripePlan();
		$this->Model->setDataSource('stripe_test');
		$sources = ConnectionManager::enumConnectionObjects();
		$this->skipIf(!in_array('stripe_test', array_keys($sources)), '`stripe_test` db config not found');
	}
	
	function endTest() {
		unset($this->Model);
	}
	
	function testValidation() {
		$result = $this->Model->save(array('StripePlan' => array()));
		$this->assertFalse($result);
		
		$this->assertTrue(array_key_exists('id', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('amount', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('currency', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('interval', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('name', $this->Model->validationErrors));
		
		$this->assertFalse(array_key_exists('trial_period_days', $this->Model->validationErrors));
	}
	
	function testFlow() {
		$results = $this->Model->save(array(
			'StripePlan' => array(
				'id' => 'Basic',
				'amount' => 500,
				'currency' => 'usd',
				'interval' => 'month',
				'name' => 'Basic Plan'
			)
		));
		$this->assertTrue($results);
		
		$id = $this->Model->id;
		
		$results = $this->Model->read();
		$this->assertEqual($results['StripePlan']['id'], $id);
		$this->assertEqual($results['StripePlan']['amount'], 500);
		
		$this->assertTrue($this->Model->delete($id));
	}
	
}