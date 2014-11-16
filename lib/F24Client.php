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
		'url' 	=> 'http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1?wsdl',
		'ns'	=> 'http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1'
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
	public function F24Client( $username, $password ) {

		$this->loginData['username'] = $username;
		$this->loginData['password'] = $password;

		$this->createSoapClient();

		$this->storageFile = sys_get_temp_dir() . '/f24client.bin';

		//check if session data is stored
		if ( file_exists($this->storageFile) ){

			//load session data from file
			$this->header = unserialize(file_get_contents($this->storageFile));

			//call login if session is not older than 10m
			if( time() - $this->header['timestamp'] > 600 ){
				$this->connect();
			}
		} else {
			error_reporting( 'Cannot load file: ' . $this->storageFile, E_USER_NOTICE);
		}
		

		$this->buildHeader();
	}

	/**
	*	deconstructor
	*/
	function __destruct(){
		if ( ! file_put_contents( $this->storageFile, serialize($this->header)) ) {
			error_reporting('Cannot write file: ' . $this->storageFile, E_USER_WARNING);
		}
	}

	
	/**
	 *  creates the Soap Client Object
	 *
	 * @access private
	 *
	 * @throws SOAPFault
	 *
	 */
	private function createSoapClient(){
		
		try {
			$this->SoapClient =  new SoapClient(
					$this->soapData['url'],
					array("trace" => 1, "exception" => 1)
			);

		} catch ( Exception $e){
			trigger_error("Create Soap Client error: " . $e->getMessage(), E_USER_ERROR);
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
	private function connect(){
		try {

			$result = $this->SoapClient->login(
					array( 
						'username' => $this->loginData['username'],
						'password' => $this->loginData['password']
					)
			);

			if ( isset( $result->sessionId )){
					$this->header['sessionid'] = $result->sessionId;
			} else {
				trigger_error("No value Session ID ", E_USER_ERROR);
			}

			if ( isset( $result->organizationName )){
					$this->header['orgname'] = $result->organizationName;
			} else {
				trigger_error("No value organization Name ", E_USER_ERROR);
			}

			$this->header['timestamp'] = time();

		} catch ( Exception $e){
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
	public function sendAlarm($alarmId, $message){


		$action = array(
					'activationAlarmNumber'	=> $alarmId,
					'variableMessages' 		=> array(
						'language' 			=> 'DE',
						'languageContent'	=> array(
							'textVoice' 			=> $message,
							'textSms' 				=> $message,
							'textEmail' 			=> $message,
							'textPager' 			=> $message
						)
					)
				);


		try {
			
			$this->SoapClient->startActivation($action);

			print "Request Headers ===================================: \n".
			$this->SoapClient->__getLastRequestHeaders() ."\n";
			print "Request===========================================: \n".
			$this->SoapClient->__getLastRequest() ."\n";
			print "Response Headers ================================================: \n".
			$this->SoapClient->__getLastResponseHeaders()."\n";
			print "Response ========================================================: \n".
			$this->SoapClient->__getLastResponse()."\n";

		} catch ( Exception $e){
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
	private function handleSoapError($action, $e){

		$msg = "SOAP-Error on {$action}: ";

		if (defined($e->faultstring)){
			$msg = $msg . "Error message: {$e->faultstring} ";
		}

		trigger_error( $msg, E_USER_ERROR);
		var_dump($e);
	}

	/**
	 *  Builds session headers for F24 api service
	 *
	 * @access private
	 *
	 *
	 */
	private function buildHeader(){

		$aSID 	= array( 'sessionId' 		=> new SOAPVar(
			$this->header['sessionid'], XSD_STRING, null, null, null, $this->soapData['ns'])
		);
		$aORG  	= array( 'organizationName' => new SOAPVar(
			$this->header['orgname'], XSD_STRING, null, null, null, $this->soapData['ns'])
		);

		$oSID 	= new SOAPVar($aSID, SOAP_ENC_OBJECT);
		$oORG 	= new SOAPVar($aORG, SOAP_ENC_OBJECT);

		$this->SoapClient->__setSoapHeaders(
			 array(
				new SOAPHeader($this->soapData['ns'], 'SessionHeader', $oSID),
				new SOAPHeader($this->soapData['ns'], 'LoginScopeHEader', $oORG)
				)
		);
	}
}

?>