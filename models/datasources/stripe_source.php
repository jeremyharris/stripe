<?php
/**
 * Stripe datasource
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @copyright Copyright 2011, Jeremy Harris
 * @link http://42pixels.com
 * @package stripe
 * @subpackage stripe.models.datasources
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Imports
 */
App::import('Core', 'HttpSocket');

/**
 * StripSource
 * 
 * @package stripe
 * @subpackage stripe.models.datasources
 */
class StripeSource extends DataSource {

/**
 * Formats data for Stripe
 * 
 * Fields within a key will be moved into that key when sent to Stripe. Everything
 * else will remain in tact.
 * 
 * @var array
 */
	public $formatFields = array(
		'card' => array(
			'number',
			'exp_month',
			'exp_year',
			'cvc',
			'name',
			'address_line_1',
			'address_1ine_2',
			'address_zip',
			'address_state',
			'address_country'
		)
	);

/**
 * HttpSocket
 * 
 * @var HttpSocket
 */
	public $Http = null;
	
/**
 * Constructor. Sets API key and throws an error if it's not defined in the
 * db config
 * 
 * @param array $config 
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		
		if (empty($config['api_key'])) {
			throw new Exception('StripeSource: Missing api key');
		}
		
		$this->Http = new HttpSocket();
	}

/**
 * Creates a record in Stripe
 * 
 * @param Model $model The calling model
 * @param array $fields Array of fields
 * @param array $values Array of field values
 * @return boolean Success
 */
	public function create($model, $fields = array(), $values = array()) {
		$request = array(
			'uri' => array(
				'path' => $model->path
			),
			'method' => 'POST',
			'body' => array_combine($fields, $values)
		);
		$response = $this->request($request);
		if ($response === false) {
			return false;
		}
		$model->setInsertId($response['id']);
		$model->id = $response['id'];
		return true;
	}

/**
 * Reads a Stripe record
 * 
 * @param Model $model The calling model
 * @param array $queryData Query data (conditions, limit, etc)
 * @return mixed `false` on failure, data on success
 */
	public function read($model, $queryData = array()) {
		if (empty($queryData['conditions'][$model->alias.'.'.$model->primaryKey])) {
			$queryData['conditions'][$model->alias.'.'.$model->primaryKey] = $model->id;
		}
		$request = array(
			'uri' => array(
				'path' => trim($model->path, '/').'/'.$queryData['conditions'][$model->alias.'.'.$model->primaryKey]
			)
		);
		$response = $this->request($request);
		if ($response === false) {
			return false;
		}
		$model->id = $response['id'];
		return $response;
	}

/**
 * Updates a Stripe record
 * 
 * @param Model $model The calling model
 * @param array $fields Array of fields to update
 * @param array $values Array of field values
 * @return mixed `false` on failure, data on success
 */
	public function update($model, $fields = array(), $values = array()) {
		$data = array_combine($fields, $values);
		if (!isset($data['id'])) {
			$data['id'] = $model->id;
		}
		$id = $data['id'];
		unset($data['id']);
		$request = array(
			'uri' => array(
				'path' => trim($model->path, '/').'/'.$id
			),
			'method' => 'POST',
			'body' => $data
		);
		
		$response = $this->request($request);
		if ($response === false) {
			return false;
		}
		$model->id = $id;
		return $response;
	}

/**
 * Deletes a Stripe record
 * 
 * @param Model $model The calling model
 * @param integer $id Id to delete
 * @return boolean Success
 */
	public function delete($model, $id = null) {
		$request = array(
			'uri' => array(
				'path' => trim($model->path, '/').'/'.$id[$model->alias.'.'.$model->primaryKey]
			),
			'method' => 'DELETE'
		);
		$response = $this->request($request);
		if ($response === false) {
			return false;
		}
		return true;
	}

/**
 * Submits a request to Stripe. Requests are merged with default values, such as
 * the api host. If an error occurs, it is stored in `$lastError` and `false` is
 * returned.
 * 
 * @param array $request Request details
 * @return mixed `false` on failure, data on success 
 */
	public function request($request = array()) {
		$this->lastError = null;
		$this->request = array(
			'uri' => array(
				'host' => 'api.stripe.com',
				'scheme' => 'https',
				'path' => '/'
			),
			'method' => 'GET',
			'auth' => array(
				'user' => $this->config['api_key'],
				'pass' => ''
			)
		);
		$this->request = Set::merge($this->request, $request);
		$this->request['uri']['path'] = '/v1/'.trim($this->request['uri']['path'], '/');
		
		if (isset($this->request['body'])) {
			$this->request['body'] = $this->reformat($this->request['body']);
		}
		
		try {
			$response = $this->Http->request($this->request);
			switch ($this->Http->response['status']['code']) {
				case '200':
					return json_decode($response, true);
				break;
				case '402':
					$error = json_decode($response, true);
					$this->lastError = $error['message'];
					return false;
				break;
				default:
					$this->lastError = 'Unexpected error.';
					CakeLog::write('stripe', $this->lastError);
					return false;
				break;
			} 
		} catch (Exception $e) {
			$this->lastError = $e->message;
			CakeLog::write('stripe', $e->message);
		}
	}
	
/**
 * Formats data for Stripe based on `$formatFields`
 * 
 * @param Model $model The calling model
 * @param array $data Data sent by Cake
 * @return array Stripe-formatted data
 */
	public function reformat($data) {
		foreach ($data as $field => $value) {
			foreach ($this->formatFields as $key => $fields) {
				if (in_array($field, $fields)) {
					$data[$key][$field] = $value;
					unset($data[$field]);
				}
			}
		}
		return $data;
	}
	
	
	
/**
 * Unused function
 * 
 * @param Model $model
 * @param string $func
 * @return null
 */
	public function calculate($model, $func) {
		return null;
	}
	
	
}