<?php
/*

  Example usage:

    require 'RIT_Webservices.php';
    $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
    var_dump($webservice->get_metadata());

*/

class RIT_Webservices
{
	protected $user;
  protected $instance;
  protected $soap_options;

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

		$stream_options = array(
			'http' => array(
				'timeout' => 900.0;		//< overrides default_socket_timeout
			),
			'ssl' => array(
				'allow_self_signed'	=> true,
			),
		);

		$stream_context = stream_context_create($stream_options);

    $this->soap_options = array(
      'soap_version' 	 => SOAP_1_1,
			'cache_wsdl' 	   => WSDL_CACHE_MEMORY,
			'encoding' 		   => 'utf8',
      'keep_alive'     => false,
			'local_cert' 	   => $cert,
			'passphrase' 	   => $pass,
			'stream_context' => $stream_context,
			'compression'		 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
    );
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
    $webservice = new SoapClient("$url?wsdl", $this->soap_options);
    $webservice->__setLocation($url);
    return $webservice;
  }

	public function get_objects($where, $remote_cache = false)
  {
    if ($remote_cache === true) {
      throw new Exception("Method not implemented.");
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
