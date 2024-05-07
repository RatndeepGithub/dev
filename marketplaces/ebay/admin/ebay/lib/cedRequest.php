<?php

namespace Ced_Ebay_WooCommerce_Core;

class Cedrequest {


	public $devID;
	public $appID;
	public $certID;
	public $serverUrl;
	public $compatLevel;
	public $siteID;
	public $verb;
	public $ebayConfigInstance;
	public $oauthCodeGrantUrl;
	public $token;

	/** __construct
	Constructor to make a new instance of eBaySession with the details needed to make a call
	Note that authentication credentials (normally token, but could be username and password)
	are assumed to come in the request body, and not in the constructor args
	Input:  $developerID - Developer key obtained when registered at http://developer.ebay.com
	$applicationID - Application key obtained when registered at http://developer.ebay.com
	$certificateID - Certificate key obtained when registered at http://developer.ebay.com
	$useTestServer - Boolean, if true then Sandbox server is used, otherwise production server is used
	$compatabilityLevel - API version this is compatable with
	$siteToUseID - the Id of the eBay site to associate the call iwht (0 = US, 2 = Canada, 3 = UK, ...)
	$callName  - The name of the call being made (e.g. 'GeteBayOfficialTime')
	Output: Response string returned by the server
	 */

	public function __construct( $siteToUseID, $callName, $access_token = '' ) {
		$this->loadDepenedency();
		$this->devID             = $this->ebayConfigInstance->devID;
		$this->appID             = $this->ebayConfigInstance->appID;
		$this->oauthCodeGrantUrl = $this->ebayConfigInstance->oauthCodeGrantUrl;
		$this->certID            = $this->ebayConfigInstance->certID;

		$this->compatLevel = $this->ebayConfigInstance->compatLevel;

		$this->siteID    = $siteToUseID;
		$this->verb      = $callName;
		$this->serverUrl = $this->ebayConfigInstance->serverUrl;
		$this->token     = $access_token;
	}

	public function sendHttpRequestForOAuth( $code, $requestBody ) {
		$b64Encoded = base64_encode( $this->appID . ':' . $this->certID );
		$headers    = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic ' . $b64Encoded,
		);
		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $this->oauthCodeGrantUrl );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $connection, CURLOPT_POST, 1 );
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $connection );
		curl_close( $connection );
		return $response;
	}

	/** SendHttpRequest
	Sends a HTTP request to the server for this session
	Input:  $requestBody
	Output: The HTTP Response as a String
	 */
	public function sendHttpRequest( $requestBody ) {
		// build eBay headers using variables passed via constructor
		$headers = $this->buildEbayHeaders();
		// initialise a CURL session
		$connection = curl_init();
		// set the server we are using (could be Sandbox or Production server)
		curl_setopt( $connection, CURLOPT_URL, $this->serverUrl );

		// stop CURL from verifying the peer's certificate
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

		// set the headers using the array of headers
		curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

		// set method as POST
		curl_setopt( $connection, CURLOPT_POST, 1 );

		// set the XML body of the request
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );

		// set it to return the transfer as a string from curl_exec
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

		// Send the Request
		$response = curl_exec( $connection );
		// close the connection
		curl_close( $connection );

		// return the response
		return $this->ParseResponse( $response );
	}

	/** BuildEbayHeaders
	Generates an array of string to be used as the headers for the HTTP request to eBay
	Output: String Array of Headers applicable for this call
	 */
	private function buildEbayHeaders() {
		if ( 'UploadSiteHostedPictures' == $this->verb ) {
			$headers = array(
				'X-EBAY-API-IAF-TOKEN: ' . $this->token,
				'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatLevel,
				'X-EBAY-API-DEV-NAME: ' . $this->devID,
				'X-EBAY-API-APP-NAME: ' . $this->appID,
				'X-EBAY-API-CERT-NAME: ' . $this->certID,
				'X-EBAY-API-CALL-NAME: ' . $this->verb,
				'X-EBAY-API-SITEID: ' . $this->siteID,
			);
		} else {

			$headers = array(
				'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatLevel,
				'X-EBAY-API-DEV-NAME: ' . $this->devID,
				'X-EBAY-API-APP-NAME: ' . $this->appID,
				'X-EBAY-API-CERT-NAME: ' . $this->certID,
				'X-EBAY-API-CALL-NAME: ' . $this->verb,
				'X-EBAY-API-SITEID: ' . $this->siteID,
			);
		}
		return $headers;
	}




	public function ParseResponse( $responseXml ) {

		$sxe = new \SimpleXMLElement( $responseXml );
		$res = json_decode( json_encode( $sxe ), true );
		return $res;
	}
	/**
	 * Function loadDepenedency
	 *
	 * @name loadDepenedency
	 */
	public function loadDepenedency() {
		if ( is_file( __DIR__ . '/ebayConfig.php' ) ) {
			require_once 'ebayConfig.php';
			$this->ebayConfigInstance = Ebayconfig::get_instance();
		}

		if ( is_file( __DIR__ . '/MultiPartMessage.php' ) ) {
			require_once 'MultiPartMessage.php';
		}
	}
}
