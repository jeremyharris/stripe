<?php
/**
 * Test card: 4242424242424242
 */

App::import('Model', 'Stripe.StripeCustomer');

class TestCustomer extends CakeTestCase {
	
	function startTest() {
		$this->Model = new StripeCustomer();
		$this->Model->setDataSource('stripe_test');
		$sources = ConnectionManager::enumConnectionObjects();
		$this->skipIf(!in_array('stripe_test', array_keys($sources)), '`stripe_test` db config not found');
	}
	
	function endTest() {
		unset($this->Model);
	}
	
	function testFlow() {
		// create a plan
		$this->Model->create();
		$result = $this->Model->save(array(
			'StripeCustomer' => array(
				'number' => '4242424242424242',
				'exp_month' => '11',
				'exp_year' => date('Y', strtotime('next year')),
				'cvc' => '123',
				'email' => 'jeremy@42pixels.com',
				'description' => 'Jeremy Harris'
			)
		));
		$this->assertTrue($result);
		$id = $this->Model->id;
		
		// retrieve
		$result = $this->Model->read();
		$this->assertEqual($result['StripeCustomer']['id'], $id);
		$this->assertEqual($result['StripeCustomer']['active_card']['last4'], '4242');

		// update
		$this->Model->id = $id;
		$result = $this->Model->save(array(
			'StripeCustomer' => array(
				'description' => 'Not Jeremy Harris'
			)
		));
		$this->assertTrue($result);
		
		$results = $this->Model->read();
		$this->assertEqual($result['StripeCustomer']['description'], 'Not Jeremy Harris');
		
		$this->assertTrue($this->Model->delete($id));
	}
	
}