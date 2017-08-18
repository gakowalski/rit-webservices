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
    $object = self::$api->create_tourist_object(
      self::$api->encode_object_id(12345, 'my_test_table'),
      date('Y-m-dP', time() - 86400), // last modified yesterday
      array(
       'C040', //< guest rooms category
      ),
      array(
       'A001' => 'Testowa nazwa testowego obiektu',
       'A003' => 'Testowy krótki opis testowego obiektu',
       'A004' => 'Testowy długi opis testowego obiektu',
       'A009' => 'mazowieckie',
       'A010' => 'Warszawa', // powiat
       'A011' => 'Warszawa', // gmina
       'A012' => 'Warszawa', // miejscowosc
       'A013' => 'Ulica',
       'A014' => 'Testowa ulica',
       'A015' => '1A', // numer budynku
       'A016' => '2B', // numer lokalu
       'A017' => '01-234', // kod pocztowy
       'A018' => '51.123456,20.123456', // wspolrzedne geograficzne
       'A019' => array('W mieście', 'W centrum miasta'),
       'A020' => 'Testowy opis dojazdu',
       'A021' => 'Inny',	// region turystyczny
       'A044' => '11-11', // poczatek sezonu
       'A045' => '12-12', // koniec sezonu
       'A047' => '09-09', // poczatek sezonu dodatkowego
       'A048' => '10-10', // koniec sezonu dodatkowego
       'A057' => 'Testowe uwagi dotyczące dostępności',
       'A059' => '+48 001234567',
       'A060' => '+48 001234567',
       'A061' => 'Testowy numer specjalny',
       'A062' => '+48 123456789',
       'A063' => '+48 001234567',
       'A064' => 'test@test.pl',
       'A065' => 'pot.gov.pl',
       'A066' => 'GG:123456789',
       'A069' => '100-200 zł',
       'A070' => array('Dzieci', 'Rodziny', 'Seniorzy', 'Studenci'), // znizki
       'A086' => 'Gospodarstwa Gościnne', // przynaleznosc do sieci,
       'A087' => array('Leśniczówka, kwatera myśliwska', 'Apartamenty'), // D016 multiple,
       'A089' => 123,
       'A090' => 45,
       'A091' => 6,
       'A095' => array('Internet bezpłatny', 'Internet', 'Masaż'),
       'A096' => 'Testowe uwagi do miejsc noclegowych',
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
          $this->assertEquals('I2', $result->report->reportForObject->identifierSZ->identifierType);
          $this->assertObjectHasAttribute('artificialIdentifier', $result->report->reportForObject->identifierSZ);
          $this->assertEquals('12345', $result->report->reportForObject->identifierSZ->artificialIdentifier);
          $this->assertObjectHasAttribute('databaseTable', $result->report->reportForObject->identifierSZ);
          $this->assertEquals('my_test_table', $result->report->reportForObject->identifierSZ->databaseTable);
        }
        $this->assertObjectHasAttribute('objectState', $result->report->reportForObject);
        $this->assertEquals('OK', $result->report->reportForObject->objectState);
      }
    }
    unset($result);
  }
}
