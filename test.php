<?php

error_reporting(E_ALL);

$f24 = new F24Client('UserName842014', 'zyBC!123');

$f24->sendAlarm("Dies ist ein Test des F24Clients auf php");

class F24Client {

	
	private $data = array();
	private $SoapClient;
	private $SoapHeader;



	function F24Client( $username, $password ) {
	
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
	/*
	 *  setter for local data
	 */

	function __set($name,$value){
        $this->data[$name] = $value;
	}

	/*
	 *	__getter for local data
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

	/*

		<soapenv:Header>
	    	<q0:LoginScopeHeader>
	      		<organizationName>Mueller Group WebService Test Account</organizationName>
	    	</q0:LoginScopeHeader>
	    	<q0:SessionHeader>
	      		<sessionId>7c56b1f6-bcd9-4079-91e4-b8069999f0f7</sessionId>
	    	</q0:SessionHeader>
		</soapenv:Header>

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

			// $this->SoapClient->__setSoapHeaders( New SoapHeader(
			// 		'HEADER',
			// 		'Header',
			// 		array(
			// 			'SessionHeader' 	=> array(
			// 				'sessionId' => $this->__get('sessionid')
			// 				),
			// 			'LoginScopeHeader' 	=> array(
			// 				'organizationName'=> $this->__get('orgname')
			// 				),
			// 			)
			// 		)
			// );

			echo $this->__get('sessionid') ."\n";
			echo $this->__get('orgname')."\n";

		} catch ( Exception $e){
			$this->handleSoapError('logging in', $e);
		}
	}

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

	private function handleSoapError($action, $e){
		
//		var_dump($e);

		$msg = "SOAP-Error on {$action}: ";

		if (defined($e->faultstring)){
			$msg = $msg . "Error message: {$e->faultstring} ";
		}

		trigger_error( $msg, E_USER_ERROR);

	}

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


class SoapClientDebug extends SoapClient
{
  public function __doRequest($request, $location, $action, $version, $one_way = 0) {
  	var_dump($request);
      // Add code to inspect/dissect/debug/adjust the XML given in $request here

      // Uncomment the following line, if you actually want to do the request
      // return parent::__doRequest($request, $location, $action, $version, $one_way);
  }
}
?>