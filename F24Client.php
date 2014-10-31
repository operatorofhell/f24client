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
     * String values for SOAP
     * 
     * - url 		URL to the WSDL on F24 api servers
     * - ns 		name space of the F24 
     * - usename 	username for authentication
     * - password  	password for authentication
     *
     * @var array
     */
	private $data = array();

	/**
     * The SOAP Client instance
     *
     * @var object
     */
	private $SoapClient;

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
	
		$this->__set('url', 'http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1?wsdl');
		$this->__set('ns', 'http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1');
		
		$this->__set('username', $username);
		$this->__set('password', $password);

		$this->createSoapClient();

		$this->connect();

		$this->buildHeader();
	}

	function __destruct(){
	}

	/**
	 *  Setter for $this->data values
	 *
	 * @access public
	 *
	 * @param string name
	 * 
	 * @param mixed value
	 *
	 */
	function __set($name,$value){
        $this->data[$name] = $value;
	}

	/**
	 *  Getter for $this->data values
	 *
	 * @access public
	 *
	 * @param string name
	 * 
	 * @return mixed data element
	 *
	 */
	public function __get($name){
		if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefinierte Eigenschaft für __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' Zeile ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
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
					$this->__get('url'),
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
						'username' => $this->__get('username'),
						'password' => $this->__get('password')
					)
			);

			if (is_soap_fault($result)) {
    			trigger_error("SOAP-Fehler: (Fehlernummer: {$result->faultcode}, "
        			."Fehlermeldung: {$result->faultstring})", E_USER_ERROR);
			}


			if ( isset( $result->sessionId )){
					$this->__set('sessionid', $result->sessionId);
			} else {
				trigger_error("No value Session ID ", E_USER_ERROR);
			}
			if ( isset( $result->organizationName )){
					$this->__set('orgname', $result->organizationName);
			} else {
				trigger_error("No value organization Name ", E_USER_ERROR);
			}

			echo $this->__get('sessionid') ."\n";
			echo $this->__get('orgname')."\n";

		} catch ( Exception $e){
			$this->handleSoapError('logging in', $e);
		}
	}

	/**
	 *  calls sendAlarm
	 *
	 * @param string message
	 *
	 * @access public
	 *
	 * @throws SOAPFault
	 *
	 */
	public function sendAlarm($message){


		$action = array(
					'activationAlarmNumber'	=> '1000',
					'variableMessages' 		=> array(
						'language' 				=> 'DE',
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

	}

	/**
	 *  Builds session headers for F24 api service
	 *
	 * @access private
	 *
	 *
	 */
	private function buildHeader(){

		$ns = $this->__get('ns');

		$aSID 	= array( 'sessionId' => new SOAPVar($this->__get('sessionid'), XSD_STRING, null, null, null, $ns));
		$aORG  = array( 'organizationName' => new SOAPVar($this->__get('orgname'), XSD_STRING, null, null, null, $ns));

		$oSID 	= new SOAPVar($aSID, SOAP_ENC_OBJECT);
		$oORG 	= new SOAPVar($aORG, SOAP_ENC_OBJECT);

		$this->SoapClient->__setSoapHeaders(
			 array(
				new SOAPHeader($ns, 'SessionHeader', $oSID),
				new SOAPHeader($ns, 'LoginScopeHEader', $oORG)
				)
		);
	}
}

?>