<?php 


/**
 * The MIT License (MIT)
 * Copyright (c) 2011 Angelo Rodrigues
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software 
 * and associated documentation files (the "Software"), to deal in the Software without 
 * restriction, including without limitation the rights to use, copy, modify, merge, publish, 
 * distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom 
 * the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or 
 * substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


/**
 * 
 * The ReST Client is really what powers the entire class. It provides access 
 * the the basic HTTP verbs that are currently supported by RIL
 * @author Angelo R.
 *
 */

class Rest_Client {
	
	/**
	 * 
	 * Perform any get type operation on a url
	 * @param string $url
	 * @return string The resulting data from the get operation
	 */
	public static function get($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}
	
	/**
	 * 
	 * Perform any post type operation on a url
	 * @param string $url
	 * @param array $params A list of post-based params to pass
	 */
	public static function post($url,array $params = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}
}

/**
 *
 * Since there is no PHP library for Read it Later, I had to whip one together
 * quickly to interact with the API. Thankfully the bulk of the work was already
 * present in the Box_Rest_Client library that I built a little while ago, so
 * really this was just porting a few things over and modifying the url builder.
 *
 * This should NOT be used for anything really RIL intensive as this doesn't
 * support everything that RIL does. Although, I guess if there's enough 
 * support for it I'll see what I can do
 */
class Read_It_Later {
	
	public $api_key = '';

	public $api_version = 'v2';
	public $base_url = 'https://readitlaterlist.com';
	public $upload_url = 'https://readitlaterlist.com';

	
	/**
	 * You need to create the client with the API KEY that you received when 
	 * you signed up for your apps. 
	 * 
	 * @param string $api_key
	 */
	public function __construct($api_key = '') {
		if(empty($this->api_key) && empty($api_key)) {
			throw new Exception('Invalid API Key. Please provide an API Key when creating an instance of the class, or by setting Read_It_Later->api_key');
		}
		else {
			$this->api_key = (empty($api_key))?$this->api_key:$api_key;
		}
	}
	
	/**
	 * 
	 * Executes an api function using get with the required opts. It will attempt to 
	 * execute it regardless of whether or not it exists.
	 * 
	 * @param string $api
	 * @param array $opts
	 */
	public function get($api, array $opts = array()) {
		$opts = $this->set_opts($opts);
		$url = $this->build_url($api,$opts);
		
		$data = Rest_Client::get($url);

		return $this->parse_result($data);
	}
	
	/**
	*
	* Executes an api function using post with the required opts. It will
	* attempt to execute it regardless of whether or not it exists.
	*
	* @param string $api
	* @param array $opts
	*/
	public function post($api, array $params = array(), array $opts = array()) {
		$opts = $this->set_opts($opts);
		$url = $this->build_url($api,$opts);
		
		$data = Rest_Client::post($url,$params);
		return $this->parse_result($data);
	}
	
	/**
	 * 
	 * To minimize having to remember things, get/post will automatically 
	 * call this method to set some default values as long as the default 
	 * values don't already exist.
	 * 
	 * @param array $opts
	 */
	private function set_opts(array $opts) {
		if(!array_key_exists('apikey',$opts)) {
			$opts['apikey'] = $this->api_key;
		}
		
		return $opts;
	}
	
	/**
	 * 
	 * Build the final api url that we will be curling. This will allow us to 
	 * get the results needed. 
	 * 
	 * @param string $api_func
	 * @param array $opts
	 */
	private function build_url($api_func, array $opts) {
		$base = $this->base_url.'/'.$this->api_version;
		
		$base .= '/'.$api_func.'?';
		
		$root = '';
		foreach($opts as $key=>$val) {
			if(is_array($val)) {
				foreach($val as $i => $v) {
					$root .= '&'.$key.'[]='.$v;
				}
			}
			else {
				$root .= '&'.$key.'='.$val;
			}
		}
		
		$base .= substr($root,1);
		
		return $base;
	}
	
	/**
	 * 
	 * Read It Later deals almost exclusively with headers to return information.
	 * This method parses the headers received through cURL and puts them into a
	 * handy associative array.
	 *
	 * Courtesy of: http://www.php.net/manual/en/function.http-parse-headers.php#77241
	 * 
	 * @param string $header
	 */
	private function parse_result($header) {
		$retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
    
		foreach( $fields as $field ) {
      if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
        $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
        if( isset($retVal[$match[1]]) ) {
          $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
        } else {
          $retVal[$match[1]] = trim($match[2]);
        }
      }
    }
    
		return $retVal;
	}
}

/* 
 * 53 6F 6C 6F 6E 
 * 67 61 6E 64 74 
 * 68 61 6E 6B 73 
 * 66 6F 72 61 6C 
 * 6C 74 68 65 66 
 * 69 73 68 
 */