<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers RIT_Webservices
 */
final class RIT_WebservicesTest extends TestCase
{
  protected static $api;

  public static function setUpBeforeClass() {
    global $phpunit_test_config;

    $user = $phpunit_test_config['user'];
    $pass = $phpunit_test_config['pass'];
    $cert = $phpunit_test_config['cert'];
    $instance = $phpunit_test_config['instance'];

    self::$api = new RIT_Webservices($user, $pass, $cert, $instance, true);
  }

  /**
   * @covers RIT_Webservices::__construct
   */
  public function test_cannot_construct_without_certificate_file() {
    $this->expectException(Exception::class);
    new RIT_Webservices('test', 'test', 'non-existent.pem', 'test');
  }

  /**
   * @covers RIT_Webservices::get_metadata
   * @covers RIT_Webservices::store_trace_data
   */
  public function test_store_trace_data() {
    $this->assertNull(self::$api->xml_response);
    $this->assertNull(self::$api->xml_request);
    self::$api->get_metadata();
    $this->assertNotNull(self::$api->xml_response);
    $this->assertNotNull(self::$api->xml_request);
  }

  /**
   * @covers RIT_Webservices::get_metadata
   */
  public function test_get_metadata() {
    $metadata = self::$api->get_metadata();
    $this->assertInstanceOf(stdClass::class, $metadata);
    $this->assertObjectHasAttribute('lastModificationDate', $metadata);
    $this->assertObjectHasAttribute('ritCategory', $metadata);
    $this->assertObjectHasAttribute('ritAttribute', $metadata);
    $this->assertObjectHasAttribute('ritDictionary', $metadata);
    unset($metadata);
  }

  /**
   * @covers RIT_Webservices::create_tourist_object
   * @covers RIT_Webservices::encode_object_id
   * @covers RIT_Webservices::create_attachment
   * @covers RIT_Webservices::encode_attachment_license
   */
  public function test_add_object() {
    global $phpunit_test_config;

    $object = self::$api->create_tourist_object(
      self::$api->encode_object_id(
        $phpunit_test_config['dummy_source_object_id'],
        $phpunit_test_config['dummy_source_table_name']
      ),
      date('Y-m-dP', time() - 86400), // last modified yesterday
      array(
       'C040', //< guest rooms category
      ),
      array(
        'A001' => array('pl-PL' => 'Testowa nazwa testowego obiektu PL', 'en-GB' => 'Test name of test object'),
        'A003' => array('pl-PL' => 'Testowy krótki opis testowego obiektu', 'en-GB' => 'Short description'),
        'A004' => array('pl-PL' => 'Testowy długi opis testowego obiektu', 'en-GB' => 'Long description'),
        'A009' => array('pl-PL' => 'mazowieckie'),
        'A010' => array('pl-PL' => 'Warszawa'), // powiat
        'A011' => array('pl-PL' => 'Warszawa'), // gmina
        'A012' => array('pl-PL' => 'Warszawa'), // miejscowosc
        'A013' => array('pl-PL' => 'Ulica'),
        'A014' => array('pl-PL' => 'Testowa ulica'),
        'A015' => array('pl-PL' => '1A'), // numer budynku
        'A016' => array('pl-PL' => '2B'), // numer lokalu
        'A017' => array('pl-PL' => '01-234'), // kod pocztowy
        'A018' => array('pl-PL' => '51.123456,20.123456'), // wspolrzedne geograficzne
        'A019' => array('pl-PL' => array('W mieście', 'W centrum miasta')),
        'A020' => array('pl-PL' => 'Testowy opis dojazdu'),
        'A021' => array('pl-PL' => 'Inny'),	// region turystyczny
        'A044' => array('pl-PL' => '11-11'), // poczatek sezonu
        'A045' => array('pl-PL' => '12-12'), // koniec sezonu
        'A047' => array('pl-PL' => '09-09'), // poczatek sezonu dodatkowego
        'A048' => array('pl-PL' => '10-10'), // koniec sezonu dodatkowego
        'A057' => array('pl-PL' => 'Testowe uwagi dotyczące dostępności'),
        'A059' => array('pl-PL' => '+48 001234567'),
        'A060' => array('pl-PL' => '+48 001234567'),
        'A061' => array('pl-PL' => 'Testowy numer specjalny'),
        'A062' => array('pl-PL' => '+48 123456789'),
        'A063' => array('pl-PL' => '+48 001234567'),
        'A064' => array('pl-PL' => 'test@test.pl'),
        'A065' => array('pl-PL' => 'pot.gov.pl'),
        'A066' => array('pl-PL' => 'GG:123456789'),
        'A069' => array('pl-PL' => '100-200 zł'),
        'A070' => array('pl-PL' => array('Dzieci', 'Rodziny', 'Seniorzy', 'Studenci')), // znizki
        'A086' => array('pl-PL' => 'Gospodarstwa Gościnne'), // przynaleznosc do sieci,
        'A087' => array('pl-PL' => array('Leśniczówka, kwatera myśliwska', 'Apartamenty')), // D016 multiple,
        'A089' => array('pl-PL' => 123),
        'A090' => array('pl-PL' => 45),
        'A091' => array('pl-PL' => 6),
        'A095' => array('pl-PL' => array('Internet bezpłatny', 'Internet', 'Masaż')),
        'A096' => array('pl-PL' => 'Testowe uwagi do miejsc noclegowych', 'en-GB' => 'Accomodation notice'),
      ),
      array(
        self::$api->create_attachment(
          'sample-rectangular-photo.jpg',
          'image/jpeg',
          'https://unsplash.it/400'
        ),
        self::$api->create_attachment(
          'sample-landscape-photo-licensed.jpg',
          'image/jpeg',
          'https://unsplash.it/400/200',
           self::$api->encode_attachment_license(
             date('Y-m-dP', time() + 86400),  //< tommorow
             'John Doe' //< owner
          )
        ),
      )
    );

    $result = self::$api->add_object($object);

    $this->assertInstanceOf(stdClass::class, $result);
    $this->assertObjectHasAttribute('report', $result);
    if (isset($result->report)) {
      $this->assertObjectHasAttribute('reportForObject', $result->report);
      if (isset($result->report->reportForObject)) {
        $this->assertObjectHasAttribute('identifierSZ', $result->report->reportForObject);
        if (isset($result->report->reportForObject->identifierSZ)) {
          $this->assertObjectHasAttribute('identifierType', $result->report->reportForObject->identifierSZ);
          $this->assertEquals(
            'I' . ($phpunit_test_config['dummy_source_table_name']? 2 : 1),
            $result->report->reportForObject->identifierSZ->identifierType
          );
          $this->assertObjectHasAttribute('artificialIdentifier', $result->report->reportForObject->identifierSZ);
          $this->assertEquals(
            $phpunit_test_config['dummy_source_object_id'],
            $result->report->reportForObject->identifierSZ->artificialIdentifier
          );
          $this->assertObjectHasAttribute('databaseTable', $result->report->reportForObject->identifierSZ);
          $this->assertEquals(
            $phpunit_test_config['dummy_source_table_name'],
            $result->report->reportForObject->identifierSZ->databaseTable
          );
        }
        $this->assertObjectHasAttribute('objectState', $result->report->reportForObject);
        $this->assertEquals('OK', $result->report->reportForObject->objectState);
      }
    }

    unset($result);
  }
}
