<?php

App::uses('StripePlan', 'Stripe.Model');

class StripePlanTest extends CakeTestCase {
	
	public function setUp() {
		parent::setUp();
		$this->Model = new StripePlan();
		$this->Model->setDataSource('stripe_test');
		$sources = ConnectionManager::enumConnectionObjects();
		$this->skipIf(!in_array('stripe_test', array_keys($sources)), '`stripe_test` db config not found');
		$this->Source = $this->Model->getDataSource('stripe_test');
		$this->Source->Http = $this->getMock('HttpSocket', array('request'));
	}
	
	public function tearDown() {
		parent::tearDown();
		unset($this->Model);
	}
	
	public function testValidation() {
		$result = $this->Model->save(array('StripePlan' => array()));
		$this->assertFalse($result);
		
		$this->assertTrue(array_key_exists('id', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('amount', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('currency', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('interval', $this->Model->validationErrors));
		$this->assertTrue(array_key_exists('name', $this->Model->validationErrors));
		
		$this->assertFalse(array_key_exists('trial_period_days', $this->Model->validationErrors));
	}
	
	public function testFlow() {
		$this->Source->Http->response = array(
			'status' => array('code' => 200),
			'body' => json_encode(array(
				'id' => 'Basic',
				'amount' => '500',
			))
		);
		$this->Source->Http->expects($this->any())
			->method('request')
			->will($this->returnValue($this->Source->Http->response['body']));
		$results = $this->Model->save(array(
			'StripePlan' => array(
				'id' => 'Basic',
				'amount' => 500,
				'currency' => 'usd',
				'interval' => 'month',
				'name' => 'Basic Plan'
			)
		));
		$this->assertTrue(($results !== false));

		$id = $this->Model->id;

		$results = $this->Model->read();
		$this->assertEqual($results['StripePlan']['id'], $id);
		$this->assertEqual($results['StripePlan']['amount'], 500);

		$this->assertTrue($this->Model->delete($id));
	}
	
}