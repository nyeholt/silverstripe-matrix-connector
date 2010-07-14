<?php
/**

Copyright (c) 2009, SilverStripe Australia Limited - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.
 
*/
 
/**
 * A web client of the APIs that have been made available for access via the Javascript
 * API. 
 * 
 * Currently, only the following methods have been implemented
 * 
 * getGeneral
 * getAttributes
 * getMetadata
 * getChildren
 * getParents
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class MatrixJSClient
{
	
	/**
	 * The webapiclient to use for making the requests
	 * 
	 * @var WebApiClient
	 */
	protected $api;
	
	public function __construct($cacheTimeout=1200)
	{
		$newMethods = array();

		foreach (self::$methods as $name => $method) {
			if (!isset($method['cache'])) {
				$method['cache'] = $cacheTimeout;
			}
			$newMethods[$name] = $method;
		}

		$this->api = new WebApiClient(null, $newMethods);
		$this->api->setUseCookies(true);
		$this->api->setMaintainSession(true);
	}
	
	/**
	 * Just assume it's true for now... 
	 * @return unknown_type
	 */
	public function isConnected()
	{
		// TODO: Make this check the login functionality or something..!!
		return strlen($this->api->getBaseUrl()) > 0;
	}
	
	/**
	 * Connect to the matrix server via the JS API
	 * 
	 * @param $details
	 */
	public function connect($details)
	{
		$this->api->setBaseUrl($details['apiUrl']);
		$this->api->setGlobalParam('key', $details['apiKey']);
		
		// try a login!
		try {
			$output = $this->call('login', array($details['username'], $details['password']));
		} catch (FailedRequestException $fre) {
			// see what was in the request... see if we can get a login key out and use in the next request!
			
			if (preg_match('/"SQ_LOGIN_KEY"\s*value="(\w+)"/', $fre->getResponse(), $matches)) {
				try {
					$this->call('login', array($details['username'], $details['password'], $matches[1]));
				} catch (FailedRequestException $again) {
					throw $again;
				}
			} else {
				throw $fre;
			}
		}
	}

	/**
	 * Doesn't do anything for now
	 */
	public function disconnect()
	{
	}

	/**
	 * Call a method on the matrix server
	 * 
	 * @param $method
	 * 				The method name
	 * @param $args
	 * 				The arguments to pass to the method in key => value form
	 * @return mixed
	 */
	public function call($method, $args)
	{
		// All matrix methods have this parameter... it's just different every method call
		$this->api->setGlobalParam('type', $method);
		return $this->api->callMethod($method, $args);
	}

	/**
	 * Catch calls to call()
	 *
	 * @param string $method
	 * @param array $args
	 */
	public function __call($method, $args) {
		$params = ($args != null && isset($args[0])) ? $args[0] : $args;
		return $this->call($method, $params);
	}

	/**
	 * A call to get General needs to getGeneral then getAttributes
	 *
	 * @param array $args
	 */
	public function getAsset($args) {
		$data = $this->getGeneral($args);
		$attr = $this->getAttributes($args);
		foreach ($attr as $k => $v) {
			$data->$k = $v;
		}

		return $data;
	}
	
	private static $methods = array(
		'login' => array(
			'method' => 'POST',
			'params' => array('SQ_LOGIN_USERNAME', 'SQ_LOGIN_PASSWORD', 'SQ_LOGIN_KEY'),
			'get'    => array('SQ_ACTION' => 'login'),
			'cache' => false
			// 'enctype' => Zend_Http_Client::ENC_FORMDATA,
		),
		'getGeneral' => array(
			'params' => array('id'),
			'return' => 'json'
		),
		'getAttributes' => array(
			'params' => array('id'),
			'return' => 'json'
		),
		'getMetadata' => array(
			'params' => array('id'),
			'return' => 'json'
		),
		'getChildren' => array(
			'params' => array('id', 'depth'),
			'return' => 'json'
		),
		'getParents' => array(
			'params' => array('id'),
			'return' => 'json'
		)
	);
}


?>