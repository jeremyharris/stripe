<?php
/**
 * Stripe app model
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @copyright Copyright 2011, Jeremy Harris
 * @link http://42pixels.com
 * @package stripe
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * StripeApp
 * 
 * @package stripe
*/
class StripeAppModel extends AppModel {
	
/**
 * The datasource 
 * 
 * @var string
 */
	public $useDbConfig = 'stripe';
	
/**
 * No table here
 * 
 * @var mixed
 */
	public $useTable = false;
	
	
/**
 * Unused function
 * 
 * @return true
 */
	public function exists() {
		return true;
	}
	
}

?>