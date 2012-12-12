<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Yahoo! Placefinder class
 * Geocodes zip/post codes into longtitude and latitude
 *
 * This CodeIgniter library connects to Yahoo's Placefinder service 
 *
 * @package   yahoo_placefinder
 * @version   1.0
 * @author    Ollie Rattue, Too many tabs <orattue[at]toomanytabs.com>
 * @copyright Copyright (c) 2011, Ollie Rattue
 * @license   http://www.opensource.org/licenses/mit-license.php
 * @link      http://github.com/ollierattue/codeigniter-yahoo-placefinder
 */

class Yahoo_Geo
{
	var $yahoo_geo_app_id;
	var $CI;
	protected $method = 'GET';
	protected $params;
	public $format = 'json';
	
	protected static function buildRequest($params)
	{
		foreach($params as $key => $val)
			$requestString[] = $key.'='.$val;
		
		return implode('&',$requestString);
	}
	

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	
	public function __construct()
	{
		if (!isset($this->CI))
		{
			$this->CI =& get_instance();
		}

		$this->CI->load->config('yahoo_placefinder_config');

		$this->yahoo_geo_app_id = $this->CI->config->item('yahoo_geo_app_id');
		
		log_message('debug', "Yahoo Placefinder Class Initialized");
	}
			
	// --------------------------------------------------------------------
	
	/**
	* Geocode - supports http://developer.yahoo.com/geo/placefinder
	* @access public
	* @param string
	* @return array or FALSE
	*/

	function geocode($location = NULL)
	{
		$location = urlencode($location);

		if (!$this->yahoo_geo_app_id)
		{
			return FALSE;
		}

		$url = "http://where.yahooapis.com/geocode?format=".$this->format."&location={$location}&flags=P&appid={$this->yahoo_geo_app_id}";

		$this->method = 'GET';
		$geo_data = json_decode($this->do_curl($url),true);

		$geo_data['longitude'] = $geo_data['Result']['longitude'];
		$geo_data['latitude'] = $geo_data['Result']['latitude'];

		return $geo_data;
	}

	// --------------------------------------------------------------------
	
	/**
	* Geocode - supports http://developer.yahoo.com/geo/placefinder
	* @access public
	* @param string
	* @param string
	* @return array or FALSE
	*/

	function reverse_geocode($lat = NULL, $lng = NULL)
	{
		$geo_data = array();
		
		$latlng = urlencode($lat.', '.$lng);

		if (!$this->yahoo_geo_app_id)
		{
			return FALSE;
		}

		$url = "http://where.yahooapis.com/geocode?format=".$this->format."&location={$latlng}&flags=PT&gflags=R&appid={$this->yahoo_geo_app_id}";


		$this->method = 'GET';
		$geo_data = json_decode($this->do_curl($url),true);

		$geo_data['zip'] = $geo_data['Result']['postal'];
		$geo_data['country']['name'] = $geo_data['Result']['country'];
		$geo_data['country']['name_short'] = $geo_data['Result']['countrycode'];
		$geo_data['state']['name'] = $geo_data['Result']['state'];
		$geo_data['state']['name_short'] = $geo_data['Result']['statecode'];
		$geo_data['city'] = $geo_data['Result']['city'];
		$geo_data['street_address'] = $geo_data['Result']['street'];
		$geo_data['street_number'] = $geo_data['Result']['house'];

		return $geo_data;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Getting timezone by coordinates
	* @access public
	* @param string
	* @param string
	* @return array or FALSE
	*/

	function timezoneByCoordinates($lat = NULL, $lng = NULL)
	{
		$geo_data = $this->reverse_geocode($lat, $lng);
		if (isset($geo_data['ResultSet']))
			return $geo_data['ResultSet']['Result'][0]['timezone'];
		else
		{
			log_message('error','Timezone not found: for latitude: '.$lat.' longitude:'.$lng);
			return false;
		}
	}

	// --------------------------------------------------------------------
	
	/**
	* Geocode - supports http://developer.yahoo.com/geo/placefinder
	* @access public
	* @param string - refers to postal in database
	* @return array or FALSE
	*/

	private function do_curl($url)
	{
		// Open the cURL session
		$curlSession = curl_init();

		// Set the URL
		curl_setopt ($curlSession, CURLOPT_URL, $url);
		
		if ($this->method == 'POST')
		{
			// post
			curl_setopt ($curlSession, CURLOPT_POST, 1);
			
			// Set the POST variables
			curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $this::buildRequest($this->params));
		}
		
		// No headers, please
		curl_setopt ($curlSession, CURLOPT_HEADER, 0);
		
		// Return it direct, don't print it out
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1); 
		
		// This connection will timeout in 30 seconds
		curl_setopt($curlSession, CURLOPT_TIMEOUT,30); 
		
		// The next two lines must be present for the kit to work with newer version of cURL
		// You should remove them if you have any problems in earlier versions of cURL
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

		// Send the request and store the result in an array
		$rawresponse = curl_exec($curlSession);

		// Store the raw response as it's useful to see for debugging 
		//$this->CI->session->set_userdata('rawrespons', $rawresponse);

		// Close the cURL session
		curl_close ($curlSession);

		$geo_data = ($rawresponse);

		// Convenience array names

		return $geo_data;
	}

	// --------------------------------------------------------------------	
}

/* End of file yahoo_placefinder.php */
/* Location: ./application/libraries/yahoo_placefinder.php */
