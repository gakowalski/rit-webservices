<?php
/*

  Example usage:

  require 'CURL_SoapClient.php';
  require 'RIT_Webservices.php';

  $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
  var_dump($webservice->get_metadata());

*/

class RIT_Webservices
{
	protected $user;
	protected $curl;
  protected $instance;

  public $instances = array(
    'production'  => 'https://intrit.poland.travel/rit/integration/',
    'test'        => 'https://intrittest.poland.travel/rit/integration/',
  );

	public function __construct($user, $pass, $cert, $instance = 'production')
  {
		$this->user = $user;
    $this->instance = $instance;

		if (file_exists($cert) === false) {
			throw new Exception("Certificate file '$cert' not found!");
		}

		$this->curl = curl_init();

		if ($this->curl === false) {
			throw new Exception("Couln't initialize cURL!");
		}

		curl_setopt_array($this->curl, array(
      CURLOPT_RETURNTRANSFER	=> false,
      CURLOPT_FOLLOWLOCATION	=> true,
      CURLOPT_SSL_VERIFYHOST	=> false,
      CURLOPT_SSL_VERIFYPEER	=> false,
      CURLOPT_USERAGENT		    => 'RIT_Webservices_PHP',
      CURLOPT_TIMEOUT			    => 1800,
      CURLOPT_SSLCERT			    => $cert,
      CURLOPT_SSLCERTPASSWD	  => $pass,
    ));
	}

	public function __destruct()
  {
		curl_close($this->curl);
	}

  protected function get_metric()
  {
    return (object) array(
      'distributionChannel'	    => $this->user,
      'username'					      => $this->user,
      'requestUniqueIdentifier'	=> time(),
      'requestDate'				      => date('Y-m-dP'),
    );
  }

  protected function get_webservice($method_name)
  {
    $url = $this->instances[$this->instance] . $method_name;

    return new CURL_SoapClient($this->curl, "$url?wsdl", $url, array(
			'soap_version' 	=> SOAP_1_1,
			'cache_wsdl' 	  => WSDL_CACHE_NONE,  //< WSDL is cached anyway by CURL_SoapClient
			'use' 			    => SOAP_LITERAL,
			'style' 		    => SOAP_DOCUMENT,
			'encoding' 		  => 'utf8',
  	));
  }

	public function get_objects($where, $remote_cache = false)
  {
    if ($remote_cache === true) {
      throw new Exception("Metod not implemented.");
    }

    $ws	= $this->get_webservice('CollectTouristObjects');

		$request = (object) array(
			'metric'	=> $this->get_metric(),
			'searchCondition'	=> $where,
		);

		return $ws->searchTouristObjects($request);
	}

	public function add_object($object)
  {
    $ws	= $this->get_webservice('GiveTouristObjects');

		$request = (object) array(
			'metric'	=> $this->get_metric(),
			'touristObject'	=> $object,
		);

		$result = $ws->addModifyObject($request);
	}

	public function add_objects() {
		throw new Exception("Metod not implemented.");
	}

	public function delete_object() {
		throw new Exception("Metod not implemented.");
	}

	public function delete_objects() {
		throw new Exception("Metod not implemented.");
	}

	public function get_report() {
		throw new Exception("Metod not implemented.");
	}

	public function get_metadata($lang = 'pl-PL')
  {
		$ws	= $this->get_webservice('MetadataOfRIT');

		$request = array(
			'metric'	=> $this->get_metric(),
			'language'	=> $lang,
		);

		return $ws->getMetadataOfRIT($request);
	}
}
