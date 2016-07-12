<?php

class CURL_SoapClient extends SoapClient
{
	protected $curl;
	protected $endpoint;

	public function __construct($curl, $wsdl, $endpoint, array $soap_options = array())
	{
		$this->curl = $curl;
		$this->endpoint = $endpoint;

		$cached_wsdl_filename = md5($wsdl) . '.wsdl';

		if (file_exists($cached_wsdl_filename) === false)
    {
			$handle = fopen($cached_wsdl_filename, 'wb');

			$curl_options = array(
				CURLOPT_POST		=> false,
				CURLOPT_FILE		=> $handle,
				CURLOPT_URL			=> $wsdl,
				CURLOPT_HTTPHEADER	=> array(),
			);

			curl_setopt_array($this->curl, $curl_options);
			curl_exec($this->curl);

			fflush($handle);
			fclose($handle);
		}

		if (file_exists($cached_wsdl_filename)) {
			parent::__construct($cached_wsdl_filename, $soap_options);
		} else {
			throw new Exception("Cannot retrieve WSDL file from cache and/or remote location.");
		}
	}

	public function __doRequest($request, $location, $action, $version, $one_way = 0)
  {
		$header = array(
      'Content-type: text/xml;charset="utf-8"',
      'Cache-Control: no-cache',
      'Pragma: no-cache',
      "SOAPAction: \"$action\"",
      'Content-length: ' . strlen($request),
    );

		$options = array(
      CURLOPT_POST		        => true,
      CURLOPT_URL			        => $this->endpoint,
      CURLOPT_POSTFIELDS	    => $request,
      CURLOPT_HTTPHEADER	    => $header,
      CURLOPT_RETURNTRANSFER  => true,
    );

		curl_setopt_array($this->curl, $options);

    return curl_exec($this->curl);
  }
}
