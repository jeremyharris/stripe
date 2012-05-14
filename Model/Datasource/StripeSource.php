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
App::uses('HttpSocket', 'Network/Http');

/**
 * StripSource
 * 
 * @package stripe
 * @subpackage stripe.models.datasources
 */
class StripeSource extends DataSource {

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
			throw new CakeException('StripeSource: Missing api key');
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
			'body' => $this->reformat($model, array_combine($fields, $values))
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
		return array(
			array(
				$model->alias => $response
			)
		);
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
			'body' => $this->reformat($model, $data)
		);
		
		$response = $this->request($request);
		if ($response === false) {
			return false;
		}
		$model->id = $id;
		return array($model->alias => $response);
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
		
		try {
			$response = $this->Http->request($this->request);
			switch ($this->Http->response['status']['code']) {
				case '200':
					return json_decode($response, true);
				break;
				case '402':
					$error = json_decode($response, true);
					$this->lastError = $error['error']['message'];
					return false;
				break;
				default:
					$this->lastError = 'Unexpected error.';
					CakeLog::write('stripe', $this->lastError);
					return false;
				break;
			} 
		} catch (CakeException $e) {
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
	public function reformat($model, $data) {
		if (!isset($model->formatFields)) {
			return $data;
		}
		foreach ($data as $field => $value) {
			foreach ($model->formatFields as $key => $fields) {
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