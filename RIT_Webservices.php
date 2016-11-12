<?php
/*

  Example usage:

    require 'RIT_Webservices.php';

    $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');

    var_dump($webservice->get_metadata());

		var_dump($webservice->get_objects(array(
		  'language' => 'ru-RU',
		  'allForDistributionChannel' => 'true',
		)));

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
				'timeout' => 900.0,		//< overrides default_socket_timeout
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

		$request = array(
			'metric'	=> $this->get_metric(),
			'searchCondition'	=> $where,
		);

		return $ws->searchTouristObjects($request);
	}

	public function get_all_objects($lang = 'pl-PL', $remote_cache = false) {
		return $this->get_objects(array(
		  'language' => $lang,
		  'allForDistributionChannel' => 'true',
		), $remote_cache);
	}

	public function get_objects_by_attributes($attributes) {
		throw new Exception("Metod not implemented.");
	}

	public function get_objects_by_categories($categories) {
		throw new Exception("Metod not implemented.");
	}

	public function get_object_by_id($rit_id, $lang = 'pl-PL') {
		return $this->get_objects(array(
		  'language' => $lang,
		  'allForDistributionChannel' => 'false',
			'objectIdentifier' => array(
				'identifierRIT' => $rit_id,
			),
		), false);
	}

	public function get_objects_by_modification_date($date_from, $date_to) {
		throw new Exception("Metod not implemented.");
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

	public function get_report($transaction_id) {
		$ws	= $this->get_webservice('GiveTouristObjects');

		$request = array(
			'metric'	=> $this->get_metric(),
			'transactionIdentifier'	=> $transaction_id,
		);

		return $ws->getReport($request);
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

	public function get_file($file_id) {
		throw new Exception("Metod not implemented.");
	}
}
