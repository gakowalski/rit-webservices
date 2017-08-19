<?php
/**
 * Class encompassing basic operations on touristic data objects made possible by RIT webservices
 *
 * There are three ways to make use of this class, each way utilizing more its methods and requiring less manual work but also requiring
 * better understanding of how this class works:
 *
 * Level 1) Use it only to get properly set-up SoapClient and then proceed with manual request building and response decoding;
 *
 * Level 2) Use request creating functions to create proper request objects and call webservices - but no response decoding;
 *
 * Level 3) Request creating with some helper functions to decode and process recieved response.
 *
 * At level 1 you need to know only about {@see __construct()} and {@see get_webservice()}. The latter will give you SoapClient object.
 *
 * At level 2 you can start with reading documentation of {@see add_object()} and tracing its helper functions to encode object identifier.
 * Then you can discover large family of wrappers for {@see get_objects()}. Then there are:
 * very important {@see get_metadata()},
 * useful {@see get_events()}
 * and some more functions are still waiting to be implemented.
 *
 * At level 3 you have everything from level 2 but some response decoding functions (at this time primarily focused on metadata) appear:
 * {@see get_languages()},
 * {@see get_dictionary()},
 * {@see get_dictionaries()},
 * {@see get_dictionary_title()},
 * {@see get_dictionary_values()},
 * {@see get_category()},
 * {@see get_categories()}.
 * More are underway.
 *
 * If it wasn't enough, you can always subclass RIT_Webservices and access some protected methods. There isn't many of them, but they might be useful:
 * {@see get_metric()}, {@see get_category_from()}.
 *
 * Example:
 * <code>
 * require 'RIT_Webservices.php';
 * $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
 * var_dump($webservice->get_metadata());
 * var_dump($webservice->get_objects(array(
 *  'language' => 'ru-RU',
 *  'allForDistributionChannel' => 'true',
 * )));
 * </code>
 *
 * @author Grzegorz Kowalski
 *
 * @todo Complete descriptions of all methods
 * @todo Complete implementation of RIT webservice API (e.g. mass action methods)
 */
class RIT_Webservices
{
	/**
	 * User login string
	 * @var string
	 */
	protected $user;

	/**
	 * RIT instance name, see {@see $instances}
	 * @var string
	 */
  protected $instance;

	/**
	 * Array of options for SoapClient
	 * @var array
	 */
  protected $soap_options;

	/**
	 * Last XML response (only if trace parameter of {@see __construct()} is set; otherwise NULL)
	 * @var string
	 */
	public $xml_response;

	/**
	 * Last XML request (only if trace parameter of {@see __construct()} is set; otherwise NULL)
	 * @var string
	 */
	public $xml_request;

	/**
	 * Array of available RIT instances
	 * @var array
	 */
  public $instances = array(
    'production'  => 'https://intrit.poland.travel/rit/integration/',
    'test'        => 'https://intrittest.poland.travel/rit/integration/',
  );

/**
 * Class constructor
 *
 * Initializes class with user authentication data and sets target RIT instance.
 * Lack of certificate file results in exception being thrown.
 *
 * Example:
 * <code>
 * $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
 * var_dump($webservice->get_metadata());
 * </code>
 *
 * @param string $user     Login
 * @param string $pass     Password
 * @param string $cert     Path and filename of certificate file (*.pem format)
 * @param string $instance RIT instance/environment name from {@see $instances}
 * @param boolean $trace   if true, store request and response XMLs to further inspection
 * @throws Exception
 */
	public function __construct($user, $pass, $cert, $instance = 'production', $trace = false)
  {
		$this->user = $user;
    $this->instance = $instance;
		$this->xml_response = null;
		$this->xml_request = null;

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
			'trace'					 => $trace,
			'compression'		 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
    );
	}

	private function store_trace_data($soap_client_object) {
		$this->xml_response = $soap_client_object->__getLastResponse();
		$this->xml_request = $soap_client_object->__getLastRequest();
	}

/**
 * Create new metric object to be used as part of a request object
 *
 * Internal function to help create metric object which should be incorporated into SOAP request.
 *
 * Example:
 * <code>
 * $ws	= $this->get_webservice('MetadataOfRIT');
 * $request = array(
 *   'metric'	=> $this->get_metric(),
 *   'language'	=> $lang,
 * );
 * return $ws->getMetadataOfRIT($request);
 * </code>
 *
 * @return object Metric object
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

/**
 * Function creating SoapClient object for webservice of given name.
 *
 * Example for internal use:
 * <code>
 * $ws	= $this->get_webservice('MetadataOfRIT');
 * // ... creating request object ...
 * return $ws->getMetadataOfRIT($request);
 * </code>
 *
 * Example for manual SoapClient usage:
 * <code>
 * $webservice = new RIT_Webservices('login', 'pass', 'cert.pem', 'env');
 * $client = $webservice->get_webservice('GiveTouristObjects');
 * // ... creating request object
 * $client->addModifyObject($request);
 * </code>
 *
 * @param  string $method_name Name of webservice / method
 * @return SoapClient Properly set-up and ready to use SoapClient instance
 */
  public function get_webservice($method_name)
  {
    $url = $this->instances[$this->instance] . $method_name;
    $webservice = new SoapClient("$url?wsdl", $this->soap_options);
    $webservice->__setLocation($url);
    return $webservice;
  }

/**
 * Get all objects satysfying given conditions
 *
 * Example:
 * <code>
 * var_dump($webservice->get_objects(array(
 *   'language' => 'ru-RU',
 *   'allForDistributionChannel' => 'true',
 * )));
 * </code>
 *
 * @param  array  $where 					Search conditions encoded in array to be inserted into request object
 * @param  boolean $remote_cache	Set true to get data from cached data; false otherwise
 * @return object									Response of webservice method call
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

		$result = $ws->searchTouristObjects($request);
		$this->store_trace_data($ws);
		return $result;
	}

/**
 * Get all objects in target language
 *
 * Wrapper function for using get_objects() to get all objects in target language.
 *
 * @param  string  $lang         Language code, see {@see get_languages()}
 * @param  boolean $remote_cache Set true to get data from cached data; false otherwise
 * @return object                Response object from webservice
 * @see    RIT_Webservices::get_languages()
 */
	public function get_all_objects($lang = 'pl-PL', $remote_cache = false) {
		return $this->get_objects(array(
		  'language' => $lang,
		  'allForDistributionChannel' => 'true',
		), $remote_cache);
	}

	/**
	 * @ignore
	 */
	public function get_objects_by_attributes($attributes) {
		throw new Exception("Metod not implemented.");
	}

	/**
	 * @ignore
	 */
	public function get_objects_by_categories($categories) {
		throw new Exception("Metod not implemented.");
	}

/**
 * Recieve single object from RIT database identified by its RIT ID
 * @param  int|string $rit_id	RIT ID
 * @param  string $lang   		Language code, see {@see get_languages()}
 * @return object         		Response object from webservice
 */
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

/**
 * Send new object to RIT database
 *
 * Example:
 * <code>
 * $object = $webservice->create_tourist_object(
 *	 $webservice->encode_object_id(124, 'my_test_objects_table'),
 *	 date('Y-m-dP', time() - 86400), // last modified yesterday
 *	 array(
 *		 'C040', // pokoje goscinne
 *	 ),
 *	 array(
 *	  'A001' => array('pl-PL' => 'Testowa nazwa testowego obiektu PL', 'en-GB' => 'Test name of test object'),
 *	  'A003' => array('pl-PL' => 'Testowy krótki opis testowego obiektu', 'en-GB' => 'Short description'),
 *	  'A004' => array('pl-PL' => 'Testowy długi opis testowego obiektu', 'en-GB' => 'Long description'),
 *	  'A009' => array('pl-PL' => 'mazowieckie'),
 *	  'A010' => array('pl-PL' => 'Warszawa'), // powiat
 *	  'A011' => array('pl-PL' => 'Warszawa'), // gmina
 *	  'A012' => array('pl-PL' => 'Warszawa'), // miejscowosc
 *	  'A013' => array('pl-PL' => 'Ulica'),
 *	  'A014' => array('pl-PL' => 'Testowa ulica'),
 *	  'A015' => array('pl-PL' => '1A'), // numer budynku
 *	  'A016' => array('pl-PL' => '2B'), // numer lokalu
 *	  'A017' => array('pl-PL' => '01-234'), // kod pocztowy
 *	  'A018' => array('pl-PL' => '51.123456,20.123456'), // wspolrzedne geograficzne
 *	  'A019' => array('pl-PL' => array('W mieście', 'W centrum miasta')),
 *	  'A020' => array('pl-PL' => 'Testowy opis dojazdu'),
 *	  'A021' => array('pl-PL' => 'Inny'),	// region turystyczny
 *	  'A044' => array('pl-PL' => '11-11'), // poczatek sezonu
 *	  'A045' => array('pl-PL' => '12-12'), // koniec sezonu
 *	  'A047' => array('pl-PL' => '09-09'), // poczatek sezonu dodatkowego
 *	  'A048' => array('pl-PL' => '10-10'), // koniec sezonu dodatkowego
 *	  'A057' => array('pl-PL' => 'Testowe uwagi dotyczące dostępności'),
 *	  'A059' => array('pl-PL' => '+48 001234567'),
 *	  'A060' => array('pl-PL' => '+48 001234567'),
 *	  'A061' => array('pl-PL' => 'Testowy numer specjalny'),
 *	  'A062' => array('pl-PL' => '+48 123456789'),
 *	  'A063' => array('pl-PL' => '+48 001234567'),
 *	  'A064' => array('pl-PL' => 'test@test.pl'),
 *	  'A065' => array('pl-PL' => 'pot.gov.pl'),
 *	  'A066' => array('pl-PL' => 'GG:123456789'),
 *	  'A069' => array('pl-PL' => '100-200 zł'),
 *	  'A070' => array('pl-PL' => array('Dzieci', 'Rodziny', 'Seniorzy', 'Studenci')), // znizki
 *	  'A086' => array('pl-PL' => 'Gospodarstwa Gościnne'), // przynaleznosc do sieci,
 *	  'A087' => array('pl-PL' => array('Leśniczówka, kwatera myśliwska', 'Apartamenty')), // D016 multiple,
 *	  'A089' => array('pl-PL' => 123),
 *	  'A090' => array('pl-PL' => 45),
 *	  'A091' => array('pl-PL' => 6),
 *	  'A095' => array('pl-PL' => array('Internet bezpłatny', 'Internet', 'Masaż')),
 *	  'A096' => array('pl-PL' => 'Testowe uwagi do miejsc noclegowych', 'en-GB' => 'Accomodation notice'),
 *	 ),
 *	 array(
 *		 $webservice->create_attachment(
 *			 'sample-rectangular-photo.jpg',
 *			 'jpg',
 *			 'https://unsplash.it/400'
 *		 ),
 *	 )
 * );
 * $result = $webservice->add_object($object);
 * </code>
 *
 * @param object $object Tourist object encoded using {@see create_tourist_object()}
 * or manually crafted as 'touristObject' subpart of the addModifyObject request
 *
 * @see create_tourist_object()
 */
	public function add_object($object)
  {
    $ws	= $this->get_webservice('GiveTouristObjects');

		$request = (object) array(
			'metric'	=> $this->get_metric(),
			'touristObject'	=> $object,
		);

		$result = $ws->addModifyObject($request);
		$this->store_trace_data($ws);
		return $result;
	}

	/**
	 * Encode source database ID for touristic data object
	 *
	 * @param  string|int $table_id   	Row ID in source database table
	 * @param  string|null $table_name	(optional) String containing name of source database table if there are multiple tables with touristic objects
	 * or NULL if there exists only one table with touristic data
	 * @return object             			Object to be used as ID in {@see create_tourist_object()}
	 *
	 * @see create_tourist_object()
	 */
	public function encode_object_id($table_id, $table_name = null) {
		$id = new \stdClass;
		$id->identifierType = 'I' . ($table_name? 2 : 1);
		$id->artificialIdentifier = $table_id;
		if ($table_name) {
			$id->databaseTable = $table_name;
		}
		return $id;
	}

	/**
	 * Encode manually created source unique identifier for touristic data object
	 *
	 * In case your touristic objects aren't stored inside database or they can't be identified by simple IDs,
	 * you can construct unique ID manually by whataver means you choose (concatenation of some fields: e.g. title, address; GUID generation;
	 * hash calculation).
	 *
	 * @param  string $unique_string_id	Unique string ID possibly
	 * @return object                 	Object to be used as ID in {@see create_tourist_object()}
	 *
	 * @see create_tourist_object()
	 */
	public function create_object_id($unique_string_id) {
		$id = new \stdClass;
		$id->identifierType = 'I3';
		$id->concatenationOfField = $unique_string_id;
		return $id;
	}

	/**
	 * [encode_attachment_license description]
	 * @param  [type] $valid_to [description]
	 * @param  string $owner    [description]
	 * @return [type]           [description]
	 *
	 * @todo Complete description
	 */
	public function encode_attachment_license($valid_to, $owner) {
		$license = new \stdClass;
		$license->validTo = $valid_to;
		$license->distributionChannelOwner = $owner;
		return $license;
	}

	/**
	 * [create_attachment description]
	 * @param  [type] $name        [description]
	 * @param  [type] $type        [description]
	 * @param  [type] $source      [description]
	 * @param  object $license     [description]
	 * @param  string $source_type [description]
	 * @return [type]              [description]
	 *
	 * @todo Complete description
	 */
	public function create_attachment($name, $type, $source, $license = NULL, $source_type = 'URL') {
		$attachment = new \stdClass;
		$attachment->fileName = $name;
		$attachment->fileType = $type;

		switch ($source_type) {
			case 'ftp':
				$attachment->relativePathToDirectory = $source;
				break;

			case 'base64':
				$attachment->encoded = $source;
				break;

			case 'URL':
			default:
				$attachment->URL = $source;
		}

		if ($license) {
			$attachment->certificate = $license;
		}

		return $attachment;
	}

/**
 * Encode tourist data object as subpart of request issued by {@see add_object()}.
 *
 * @param  mixed $object_id      RIT ID encoded as int or external ID encoded as object by {@see encode_object_id()} or {@see create_object_id()}
 * @param  string $last_modified Datetime of last modification in format of 'Y-m-dP'
 * @param  array  $categories    Array of strings with category names
 * @param  array  $attributes    Array of attribute_code=>array(language_code=>value) or attribute_code=>array(language_code=>array(values))
 * @param  array  $attachments	 Array of objects generated by {@see create_attachment()}
 * @return object                Tourist object to be used in {@see add_object()} call
 * @see RIT_Webservices::add_object()
 * @see RIT_Webservices::encode_object_id()
 * @see RIT_Webservices::create_object_id()
 * @see create_attachment()
 */
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

    $_attributes = $this->get_attributes();

		$object->attributes = new \stdClass;
		$object->attributes->attribute = array();
		foreach ($attributes as $_attribute_code => $_attribute_lang_values) {
			$tmp_object = array();

			foreach ($_attribute_lang_values as $_attribute_language => $_attribute_values) {
				if ($this->is_translatable_from($_attributes, $_attribute_code)) {
					$tmp_object[] = array('value' => $_attribute_values, 'language' => $_attribute_language);
				} else {
					$tmp_object[] = array('value' => $_attribute_values, 'language' => 'all');
					break;
				}
			}

			$object->attributes->attribute[] = (object) array(
				'attrVals' => $tmp_object,
				'code' => $_attribute_code,
			);
		}
		if (!empty($attachments)) {
			$object->binaryDocuments = new \stdClass;

			foreach ($attachments as $attachment) {
				if (isset($attachment->URL)) $object->binaryDocuments->documentURL[] = $attachment;
				if (isset($attachment->relativePathToDirectory)) $object->binaryDocuments->documentFile[] = $attachment;
				if (isset($attachment->encoded)) $object->binaryDocuments->documentBase64[] = $attachment;
			}
		}
		return $object;
	}

	/**
	 * [add_objects description]
	 * @param [type] $objects [description]
	 *
	 * @todo Make test case
	 * @todo Complete description
	 */
	public function add_objects($objects) {
		$ws	= $this->get_webservice('GiveTouristObjects');

		$request = (object) array(
			'metric'	=> $this->get_metric(),
			'touristObject'	=> $objects,
		);

		$result = $ws->addModifyObjects($request);
		$this->store_trace_data($ws);
		return $result;
	}

	/**
	 * @ignore
	 */
	public function delete_object() {
		throw new Exception("Metod not implemented.");
	}

	/**
	 * @ignore
	 */
	public function delete_objects() {
		throw new Exception("Metod not implemented.");
	}

	/**
	 * @param  string|int $transaction_id	Transaction ID number recieved from mass action methods
	 * @return object                			Object to be used as ID in {@see create_tourist_object()}
	 *
	 * @see add_objects()
	 * @see delete_objects()
	 *
	 * @todo Complete description
	 */
	public function get_report($transaction_id) {
		$ws	= $this->get_webservice('GiveTouristObjects');

		$request = array(
			'metric'	=> $this->get_metric(),
			'transactionIdentifier'	=> $transaction_id,
		);

		$result = $ws->getReport($request);
		$this->store_trace_data($ws);
		return $result;
	}

/**
 * Recieve all available metadata from metadata webservice
 *
 * Notice: metadata webservice does not send complete RIT metadata. Some datasets are ommited due to their large size which considerably
 * increses response times of the webservice. Those datasets can be found elsewhere in public databases, e.g. list of cities or regions can be found
 * in public TERYT databases.
 *
 * Example:
 * <code>
 * $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
 * $metadata = $webservice->get_metadata();
 * var_dump($metadata);
 * </code>
 *
 * @param  string $lang Language code, see {@see get_languages()}
 * @return object       Response object from webservice call
 */
	public function get_metadata($lang = 'pl-PL')
  {
		$ws	= $this->get_webservice('MetadataOfRIT');

		$request = array(
			'metric'	=> $this->get_metric(),
			'language'	=> $lang,
		);
		$result = $ws->getMetadataOfRIT($request);
		$this->store_trace_data($ws);
		return $result;
	}

	/**
	 * [get_metadata_last_modification_date description]
	 * @param  string $lang Language code, see {@see get_languages()}
	 * @return [type]       [description]
	 *
	 * @todo Complete description
	 */
	public function get_metadata_last_modification_date($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->lastModificationDate;
	}

	/**
	 * [get_attributes description]
	 * @param  string $lang Language code, see {@see get_languages()}
	 * @return [type]       [description]
	 *
	 * @todo Complete description
	 */
	public function get_attributes($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->ritAttribute;
	}

	/**
	 * [get_attribute_from description]
	 * @param  [type] $_attributes [description]
	 * @param  [type] $_code       [description]
	 * @return [type]              [description]
	 *
	 * @todo Complete description
	 */
	protected function get_attribute_from($_attributes, $_code)
	{
		$_result = null;
		foreach ($_attributes as $_attribute) {
			if ($_attribute->code == $_code) {
				$_result = $_attribute;
				break;
			}
		}
		return $_result;
	}

	/**
	 * [get_attribute description]
	 * @param  [type] $_code [description]
	 * @param  string $_lang Language code, see {@see get_languages()}
	 * @return [type]        [description]
	 *
	 * @todo Complete description
	 */
	public function get_attribute($_code, $_lang = 'pl-PL')
	{
		return $this->get_attribute_from($this->get_attributes($_lang), $_code);
	}

	/**
	 * [is_translatable_from description]
	 * @param  [type]  $_attributes [description]
	 * @param  [type]  $_code       [description]
	 * @return boolean              [description]
	 *
	 * @todo Complete description
	 */
	protected function is_translatable_from($_attributes, $_code)
	{
		switch ($_code) {
			case 'A009':	// wojewodztwo
			case 'A010':	// powiat
			case 'A011':	// gmina
			case 'A012':	// miejscowosc
				return false;
			default:
		}

		$_type = $this->get_attribute_from($_attributes, $_code)->typeValidator;

		switch ($_type) {
			case 'SHORT_TEXT':
			case 'LONG_TEXT':
			case 'MULTIPLY_LIST':
			case 'SINGLE_LIST':
				return true;

			case 'NUMBER':
			case 'BOOLEAN':
			case 'DATE':
			case 'COMPLEX':
			default:
		}

		return false;
	}

	/**
	 * [get_categories description]
	 * @param  string $lang Language code, see {@see get_languages()}
	 * @return [type]       [description]
	 *
	 * @todo Complete description
	 */
	public function get_categories($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->ritCategory;
	}

	/**
	 * [get_category_from description]
	 * @param  [type]  $_cache              [description]
	 * @param  [type]  $_code               [description]
	 * @param  boolean $_inherit_attributes [description]
	 * @param  string  $_lang               Language code, see {@see get_languages()}
	 * @return [type]                       [description]
	 *
	 * @todo Complete description
	 */
	protected function get_category_from($_cache, $_code, $_inherit_attributes = true, $_lang = 'pl-PL')
	{
		if ($_cache === null) {
			$_categories = $this->get_categories($_lang);
		} else {
			$_categories = $_cache;
		}

		$_result = null;
		foreach ($_categories as $_category) {
			if ($_category->code == $_code) {
				$_result = $_category;
				if ($_inherit_attributes === true && isset($_result->parentCode)) {
					$_result->attributeCodes->attributeCode = array_merge(
						$_result->attributeCodes->attributeCode,
						$this->get_category_from($_cache, $_result->parentCode, true, $_lang)->attributeCodes->attributeCode
					);
				}
				break;
			}
		}
		return $_result;
	}

	/**
	 * [get_category description]
	 * @param  [type]  $_code               [description]
	 * @param  boolean $_inherit_attributes [description]
	 * @param  string  $_lang               Language code, see {@see get_languages()}
	 * @return [type]                       [description]
	 *
	 * @todo Complete description
	 */
	public function get_category($_code, $_inherit_attributes = true, $_lang = 'pl-PL')
	{
		return $this->get_category_from(null, $_code, $_inherit_attributes, $_lang);
	}

	/**
	 * [get_dictionaries description]
	 * @param  string $lang Language code, see {@see get_languages()}
	 * @return [type]       [description]
	 *
	 * @todo Complete description
	 */
	public function get_dictionaries($lang = 'pl-PL')
	{
		return $this->get_metadata($lang)->ritDictionary;
	}

	/**
	 * [get_dictionary description]
	 * @param  [type] $code [description]
	 * @param  string $lang Language code, see {@see get_languages()}
	 * @return [type]       [description]
	 *
	 * @todo Complete description
	 */
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

	/**
	 * [get_dictionary_title description]
	 * @param  [type] $code [description]
	 * @param  string $lang Language code, see {@see get_languages()}
	 * @return [type]       [description]
	 *
	 * @todo Complete description
	 */

	public function get_dictionary_title($code, $lang = 'pl-PL')
	{
		$dictionary = $this->get_dictionary($code, $lang);
		if ($dictionary) {
			return $dictionary->name;
		}
		return null;
	}

/**
 * [get_dictionary_values description]
 * @param  [type] $code [description]
 * @param  string $lang Language code, see {@see get_languages()}
 * @return [type]       [description]
 *
 * @todo Complete description
 */
	public function get_dictionary_values($code, $lang = 'pl-PL')
	{
		$dictionary = $this->get_dictionary($code, $lang);
		if ($dictionary) {
			return $dictionary->value;
		}
		return null;
	}

/**
 * Get array of all language codes
 *
 * Convenient wrapper for {@see get_dictionary_values()} to get contents of languages dictionary.
 * This should give the same results regardless of the value of optional *$lang* parameter, so you
 * can always safely call this method without any arguments.
 *
 * Example:
 * <code>
 * $webservice = new RIT_Webservices('login', 'password', 'certificate.pem', 'test');
 * var_dump($webservice->get_languages());
 * </code>
 *
 * Example output:
 * <pre>
 * array(22) {
 *   [0]=> string(5) "en-GB"
 *   // ...
 *   [21]=> string(5) "zh-CN"
 * }
 * </pre>
 *
 * @see RIT_Webservices::get_dictionary_values()
 *
 * @param  string $lang (optional) Language code
 * @return array        Array of all language codes
 */
	public function get_languages($lang = 'pl-PL')
	{
		return $this->get_dictionary_values('L001', $lang);
	}

	/**
	 * @ignore
	 */
	public function get_file($file_id) {
		throw new Exception("Metod not implemented.");
	}

	/**
	 * @param  string $date_from [description]
	 * @param  string $date_to   [description]
	 * @return object            Response object from webservice call
	 *
	 * @todo Complete description
	 */
	public function get_events($date_from, $date_to) {
		$ws	= $this->get_webservice('GetTouristObjectEvents');

		$request = array(
			'metric'	=> $this->get_metric(),
			'criteria'	=> array(
				'dateFrom' => $date_from,
				'dateTo' => $date_to,
			),
		);

		$result = $ws->getEvents($request);
		$this->store_trace_data($ws);
		return $result;
	}
}
