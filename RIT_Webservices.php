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

	/*
		// Example #1:
		$webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
		var_dump($webservice->get_metadata());
	*/
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

	/*
		Internal function to help create metric object which should be incorporated into SOAP request.

		Example usage:

			$ws	= $this->get_webservice('MetadataOfRIT');

			$request = array(
				'metric'	=> $this->get_metric(),
				'language'	=> $lang,
			);

			return $ws->getMetadataOfRIT($request);
	*/
  protected function get_metric()
  {
    return (object) array(
      'distributionChannel'	    => $this->user,
      'username'					      => $this->user,
      'requestUniqueIdentifier'	=> time(),
      'requestDate'				      => date('Y-m-dP'),
    );
  }

	/*
		Internal function creating SoapClient object for webservice of given name.

		Example:

			$ws	= $this->get_webservice('MetadataOfRIT');
			// ... creating request object ...
			return $ws->getMetadataOfRIT($request);
	*/
  protected function get_webservice($method_name)
  {
    $url = $this->instances[$this->instance] . $method_name;
    $webservice = new SoapClient("$url?wsdl", $this->soap_options);
    $webservice->__setLocation($url);
    return $webservice;
  }

	/*
		Example (get all objects in Russian language):

		var_dump($webservice->get_objects(array(
		  'language' => 'ru-RU',
		  'allForDistributionChannel' => 'true',
		)));

		// $remote_cache is not implemented now
	*/

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

	/*
		Wrapper function for using get_objects() to get all objects in target language.
	*/

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
		return $result;
	}

	public function encode_object_id($table_id, $table_name = null) {
		$id = new \stdClass;
		$id->identifierType = 'I' . ($table_name? 2 : 1);
		$id->artificialIdentifier = $table_id;
		if ($table_name) {
			$id->databaseTable = $table_name;
		}
		return $id;
	}

	public function create_object_id($unique_string_id) {
		$id = new \stdClass;
		$id->identifierType = 'I3';
		$id->concatenationOfField = $unique_string_id;
		return $id;
	}

	public function create_tourist_object($object_id, $last_modified, $categories, $attributes, $attachments = array()) {
		$object = new \stdClass;
		if (is_object($object_id)) {
			$object->touristObjectIdentifierSZ = $object_id;
			$object->touristObjectIdentifierSZ->distributionChannel = new \stdClass;
			$object->touristObjectIdentifierSZ->distributionChannel->name = $this->user;
			$object->touristObjectIdentifierSZ->distributionChannel->code = $this->user;
			$object->touristObjectIdentifierSZ->lastModified = $last_modified;
		} else {
			$object->touristObjectIdentifierRIT = new \stdClass;
			$object->touristObjectIdentifierRIT->identifierRIT = $object_id;
		}

		$object->categories = new \stdClass;
		$object->categories->category = array();
		foreach ($categories as $category) {
			$object->categories->category[] = (object) array('code' => $category);
		}

		$object->attributes = new \stdClass;
		$object->attributes->attribute = array();
		foreach ($attributes as $_attribute_code => $_attribute_value) {
			$object->attributes->attribute[] = (object) array(
				'code' => $_attribute_code,
				'_' => array('attrVals' => array('language' => 'pl-PL', '_' => $_attribute_value)),
			);
		}
		if (!empty($attachments)) {
			$object->binaryDocuments = (object) $attachments;
		}
		return $object;
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

	/*
		Get all data from getMetadataOfRIT.

		Example:
			$webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
			$metadata = $webservice->get_metadata();
			var_dump($metadata);
	*/

	public function get_metadata($lang = 'pl-PL')
  {
		$ws	= $this->get_webservice('MetadataOfRIT');

		$request = array(
			'metric'	=> $this->get_metric(),
			'language'	=> $lang,
		);

		return $ws->getMetadataOfRIT($request);
	}

	public function get_metadata_last_modification_date($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->lastModificationDate;
	}

	public function get_attributes($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->ritAttribute;
	}

	public function get_categories($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->ritCategory;
	}

	public function get_dictionaries($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->ritDictionary;
	}

	public function get_dictionary($code, $lang = 'pl-PL')
	{
		$dictionaries = $this->get_dictionaries($lang);
		foreach ($dictionaries as $dictionary) {
			if ($dictionary->code == $code) {
				return $dictionary;
			}
		}
		return null;
	}

	public function get_dictionary_title($code, $lang = 'pl-PL')
	{
		$dictionary = $this->get_dictionary($code, $lang);
		if ($dictionary) {
			return $dictionary->name;
		}
		return null;
	}

	public function get_dictionary_values($code, $lang = 'pl-PL')
	{
		$dictionary = $this->get_dictionary($code, $lang);
		if ($dictionary) {
			return $dictionary->value;
		}
		return null;
	}

	/*

	Example:

		$webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
		var_dump($webservice->get_languages());

	Output:

		array(22) {
			[0]=>
			string(5) "en-GB"
			// ...
			[21]=>
			string(5) "zh-CN"
		}

	*/
	public function get_languages($lang = 'pl-PL')
	{
		return $this->get_dictionary_values('L001', $lang);
	}

	public function get_file($file_id) {
		throw new Exception("Metod not implemented.");
	}
}
