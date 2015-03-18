<?php
/**
 *
 *	This file contains code for connecting to Fact 24 SOAP
 *
 *
 * @category   Web Services Alerting
 * @package    F24
 * @author     Moritz Kraus <moritz.kraus@muellergroup.com> Original Author
 * @copyright  2014 Mueller Service GmbH
 */

/**
 * @package F24
 */
class F24Client {

	/**
	 * login values for SOAP
	 *
	 * - usename 	username for authentication
	 * - password  	password for authentication
	 *
	 * @var array
	 */
	private $loginData = array();

	/**
	 * String values for SOAP
	 *
	 * - url 		URL to the WSDL on F24 api servers
	 * - ns 		name space of the F24
	 *
	 * @var array
	 */
	private $soapData = array(
		'url'      => 'https://fact.f24.com/axis2/services/F24ActivationService1.1?wsdl',
		'ns'       => 'https://fact.f24.com/axis2/services/F24ActivationService1.1',
		'location' => 'https://fact.f24.com/axis2/services/F24ActivationService1.1',
	);

	/**
	 * String values for the header
	 *
	 * - sessionid 	the id returned by the login call
	 * - orgname 	the organizationName returned by the login call
	 * - timestamp 	time when session was initialized
	 *
	 * @var array
	 */
	private $header = array();

	/**
	 * The SOAP Client instance
	 *
	 * @var object
	 */
	private $SoapClient;

	/**
	 *  storage file for header data
	 *
	 *  @var string
	 *
	 */
	private $storageFile;

	/**
	 * Constructor instantiates SoapClient, authenticate against F24
	 * and builds the Session headers
	 *
	 *
	 * @access public
	 *
	 * @param string username
	 *
	 * @param string password
	 *
	 */
	public function F24Client($username, $password) {

		$this->loginData['username'] = $username;
		$this->loginData['password'] = $password;

		$this->createSoapClient();

		$this->storageFile = sys_get_temp_dir().'/f24client.bin';

		if (!$this->loadHeader()) {
			trigger_error("No Sesion data available. Reconnecting\n", E_USER_NOTICE);
			$this->connect();
			$this->saveHeader();
		}

		$this->buildHeader();
	}

	/**
	 *	saveHeader data
	 *
	 *	@access private
	 */
	private function saveHeader() {
		if (!file_put_contents($this->storageFile, serialize($this->header))) {
			trigger_error('Cannot write file: '.$this->storageFile, E_USER_WARNING);
		}
	}

	/**
	 *	loadHeader
	 *
	 *	@access private
	 *
	 *	@return bool 	if load is successfull
	 */
	private function loadHeader() {

		//check if session data is stored
		if (file_exists($this->storageFile)) {

			//load session data from file
			$this->header = unserialize(file_get_contents($this->storageFile));

			$age = time()-$this->header['timestamp'];

			if ($age < 600) {
				trigger_error("Session data is younger than 10m.\n", E_USER_NOTICE);
				return true;
			}

			trigger_error("Session data is $ages old.", E_USER_NOTICE);

		} else {
			trigger_error('Cannot load file: '.$this->storageFile, E_USER_NOTICE);
		}
		return false;
	}

	/**
	 *  creates the Soap Client Object
	 *
	 * @access private
	 *
	 * @throws SOAPFault
	 *
	 */
	private function createSoapClient() {

		try {
			$this->SoapClient = new SoapClient(
				$this->soapData['url'],
				array("trace" => 1, "exception" => 1)
			);
			$this->SoapClient->__setLocation($this->soapData['location']);
			trigger_error('successfull instatiation of SOAP Client from URL: '.$this->soapData['url'], E_USER_NOTICE);
		} catch (Exception $e) {
			trigger_error("Create Soap Client error: ".$e->getMessage(), E_USER_ERROR);
		}
	}

	/**
	 *  calls the login function to retrieve Session ID and oranization name
	 *
	 * @access private
	 *
	 * @throws SOAPFault
	 *
	 */
	private function connect() {
		try {

			$result = $this->SoapClient->login(
				array(
					'username' => $this->loginData['username'],
					'password' => $this->loginData['password'],
				)
			);

			if (isset($result->sessionId)) {
				$this->header['sessionid'] = $result->sessionId;
			} else {
				trigger_error("No value Session ID ", E_USER_ERROR);
			}

			if (isset($result->organizationName)) {
				$this->header['orgname'] = $result->organizationName;
			} else {
				trigger_error("No value organization Name ", E_USER_ERROR);
			}

			$this->header['timestamp'] = time();
			trigger_error('Got  header data\n', E_USER_NOTICE);
		} catch (Exception $e) {
			$this->handleSoapError('logging in', $e);
		}
	}

	/**
	 *  calls sendAlarm
	 *
	 * @param int alert id
	 *
	 * @param string message
	 *
	 * @access public
	 *
	 * @throws SOAPFault
	 *
	 */
	public function sendAlarm($alarmId, $message) {

		$action = array(
			'activationAlarmNumber' => $alarmId,
			'variableMessages'      => array(
				'language'             => 'DE',
				'languageContent'      => array(
					'textVoice'           => $message,
					'textSms'             => $message,
					'textEmail'           => $message,
					'textPager'           => $message,
				)
			)
		);

		try {

			$this->SoapClient->startActivation($action);

			trigger_error("Request Headers ===================================: \n".
				$this->SoapClient->__getLastRequestHeaders()."\n", E_USER_NOTICE);
			trigger_error("Request===========================================: \n".
				$this->SoapClient->__getLastRequest()."\n", E_USER_NOTICE);
			trigger_error("Response Headers ================================================: \n".
				$this->SoapClient->__getLastResponseHeaders()."\n", E_USER_NOTICE);
			trigger_error("Response ========================================================: \n".
				$this->SoapClient->__getLastResponse()."\n", E_USER_NOTICE);

		} catch (Exception $e) {
			var_dump($this->SoapClient);
			$this->handleSoapError('send alarm', $e);
		}

	}

	/**
	 *  Hanldes SOAPFault messages
	 *
	 * @access private
	 *
	 * @param string action
	 *
	 * @param SoapFault e
	 *
	 * @throws SOAPFault
	 *
	 */
	private function handleSoapError($action, $e) {

		$msg = "SOAP-Error on {$action}: ";

		if (defined($e->faultstring)) {
			$msg = $msg."Error message: {$e->faultstring} ";
		}
		trigger_error($msg, E_USER_ERROR);
		var_dump($e);

	}

	/**
	 *  Builds session headers for F24 api service
	 *
	 * @access private
	 *
	 *
	 */
	private function buildHeader() {

		$aSID = array('sessionId' => new SOAPVar(
				$this->header['sessionid'], XSD_STRING, null, null, null, $this->soapData['ns'])
		);
		$aORG = array('organizationName' => new SOAPVar(
				$this->header['orgname'], XSD_STRING, null, null, null, $this->soapData['ns'])
		);

		$oSID = new SOAPVar($aSID, SOAP_ENC_OBJECT);
		$oORG = new SOAPVar($aORG, SOAP_ENC_OBJECT);

		$this->SoapClient->__setSoapHeaders(
			array(
				new SOAPHeader($this->soapData['ns'], 'SessionHeader', $oSID),
				new SOAPHeader($this->soapData['ns'], 'LoginScopeHEader', $oORG)
			)
		);
	}
}

?>