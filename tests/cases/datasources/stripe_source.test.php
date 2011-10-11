<?php

App::import('Model', 'Stripe.App');
App::import('Core', 'HttpSocket');

Mock::generatePartial('HttpSocket', 'MockHttpSocket', array('request'));

class TestStripeModel extends StripeAppModel {
	
	public $path = '/action';
	
}

class TestStripeSource extends CakeTestCase {
	
	function startTest() {
		ConnectionManager::loadDatasource(array(
			'plugin' => 'Stripe',
			'classname' => 'StripeSource'
		));
		
		$this->Source = new StripeSource(array(
			'api_key' => '123456'
		));
		$this->Source->Http = new MockHttpSocket();
		$this->Model = new TestStripeModel();
	}
	
	function endTest() {
		unset($this->Source);
		unset($this->Model);
	}
	
	function testReformat() {
		$data = array(
			'number' => '234',
			'name' => 'Jeremy',
			'email' => 'jeremy@42pixels.com',
			'address_line_1' => '123 Main'
		);
		$result = $this->Source->reformat($data);
		$expected = array(
			'card' => array(
				'number' => '234',
				'name' => 'Jeremy',
				'address_line_1' => '123 Main'
			),
			'email' => 'jeremy@42pixels.com'
		);
		$this->assertEqual($result, $expected);
		
		$this->Source->formatFields['user'] = array('email');
		$data = array(
			'number' => '234',
			'name' => 'Jeremy',
			'email' => 'jeremy@42pixels.com',
			'address_line_1' => '123 Main'
		);
		$result = $this->Source->reformat($data);
		$expected = array(
			'card' => array(
				'number' => '234',
				'name' => 'Jeremy',
				'address_line_1' => '123 Main'
			),
			'user' => array(
				'email' => 'jeremy@42pixels.com'
			)
		);
		$this->assertEqual($result, $expected);
	}
	
	function testConstructWithoutKey() {
		$this->expectException();
		$source = new StripeSource();
	}
	
	function testRequest() {
		$this->Source->Http->response = array(
			'status' => array(
				'code' => '404',
			),
			'body' => '{}'
		);
		$this->Source->Http->setReturnValueAt(0, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->request(array('uri' => array('path' => '/path/')));
		$this->assertFalse($response);
		$this->assertEqual($this->Source->lastError, 'Unexpected error.');
		$this->assertEqual($this->Source->request['uri']['path'], '/v1/path');
		
		$this->Source->Http->response = array(
			'status' => array(
				'code' => '402',
			),
			'body' => '{"message" : "This is an error message"}'
		);
		$this->Source->Http->setReturnValueAt(1, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->request();
		$this->assertFalse($response);
		$this->assertEqual($this->Source->lastError, 'This is an error message');
		
		$this->Source->Http->response = array(
			'status' => array(
				'code' => '200',
			),
			'body' => '{"id" : "123"}'
		);
		$this->Source->Http->setReturnValueAt(2, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->request();
		$this->assertNull($this->Source->lastError);
		$this->assertEqual($response, array('id' => '123'));
	}
	
	function testCreate() {
		$this->Source->Http->response = array(
			'status' => array('code' => 200),
			'body' => '{"object" : "customer", "id" : "1234"}'
		);
		$this->Source->Http->setReturnValueAt(0, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->create($this->Model, array('email', 'description'), array('jeremy@42pixels.com', 'Jeremy Harris'));
		$this->assertTrue($response);
		$this->assertEqual($this->Source->request['method'], 'POST');
		$this->assertEqual($this->Model->getLastInsertId(), 1234);
		$this->assertEqual($this->Source->request['body'], array(
			'email' => 'jeremy@42pixels.com',
			'description' => 'Jeremy Harris'
		));
	}
	
	function testRead() {
		$this->Source->Http->response = array(
			'status' => array('code' => 200),
			'body' => '{"object" : "customer", "id" : "1234", "description" : "Jeremy Harris"}'
		);
		$this->Source->Http->setReturnValueAt(0, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->read($this->Model, array('conditions' => array('TestStripeModel.id' => '1234')));
		$this->assertEqual($response, array(
			'id' => '1234',
			'object' => 'customer',
			'description' => 'Jeremy Harris'
		));
		$this->assertEqual($this->Model->id, 1234);
		$this->assertEqual($this->Source->request['method'], 'GET');
		$this->assertEqual($this->Source->request['uri']['path'], '/v1/action/1234');
	}
	
	function testUpdate() {
		$this->Source->Http->response = array(
			'status' => array('code' => 200),
			'body' => '{"object" : "customer", "id" : "1234"}'
		);
		$this->Source->Http->setReturnValueAt(0, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->update($this->Model, array('email', 'description', 'id'), array('jeremy@42pixels.com', 'Jeremy Harris', '1234'));
		$this->assertTrue($response);
		$this->assertEqual($this->Model->id, 1234);
		$this->assertEqual($this->Source->request['body'], array(
			'email' => 'jeremy@42pixels.com',
			'description' => 'Jeremy Harris'
		));
		$this->assertEqual($this->Source->request['method'], 'POST');
		$this->assertEqual($this->Source->request['uri']['path'], '/v1/action/1234');
	}
	
	function testDelete() {
		$this->Source->Http->response = array(
			'status' => array('code' => 200),
			'body' => '{"deleted" : "true", "id" : "1234"}'
		);
		$this->Source->Http->setReturnValueAt(0, 'request', $this->Source->Http->response['body']);
		$response = $this->Source->delete($this->Model, array('TestStripeModel.id' => '1234'));
		$this->assertTrue($response);
		$this->assertEqual($this->Source->request['method'], 'DELETE');
		$this->assertEqual($this->Source->request['uri']['path'], '/v1/action/1234');
	}
	
}