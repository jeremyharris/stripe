<?php
/**
 * Test card: 4242424242424242
 */

App::uses('StripeCustomer', 'Stripe.Model');

class StripeCustomerTest extends CakeTestCase {
	
	public function setUp() {
		parent::setUp();
		$this->Model = new StripeCustomer();
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
	
	public function testFlow() {
		$this->Source->Http->response = array(
			'status' => array('code' => 200),
			'body' => json_encode(array(
				'id' => '1234',
				'active_card' => array(
					'last4' => '4242',
				)
			))
		);
		$this->Source->Http->expects($this->any())
			->method('request')
			->will($this->returnValue($this->Source->Http->response['body']));
		// create a plan
		$this->Model->create();
		$expected = array(
			'StripeCustomer' => array(
				'id' => '1234',
				'exp_month' => '11',
				'exp_year' => date('Y', strtotime('next year')),
				'cvc' => '123',
				'email' => 'jeremy@42pixels.com',
				'description' => 'Jeremy Harris',
			),
		);
		$result = $this->Model->save($expected);
		$this->assertTrue(($result !== false));
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
		$this->assertTrue(($result !== false));
		
		$results = $this->Model->read();
		$this->assertEqual($result['StripeCustomer']['description'], 'Not Jeremy Harris');
		
		$this->assertTrue($this->Model->delete($id));
	}
	
}