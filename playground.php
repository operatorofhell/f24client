<?php

error_reporting(E_ALL);

$url = 'http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1?wsdl';
$id = 'd1820c47-81db-476d-8ff7-81e6105d79e8';
$org = 'Mueller Group WebService Test Account';
$ns 	= 'http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1';

$auth 	= array( 'sessionId' => new SOAPVar($id, XSD_STRING, null, null, null, $ns));
$auth1  = array( 'organizationName' => new SOAPVar($org, XSD_STRING, null, null, null, $ns));

$hbody 	= new SOAPVar($auth, SOAP_ENC_OBJECT);
$lhb 	= new SOAPVar($auth1, SOAP_ENC_OBJECT);

$header = array(
	new SOAPHeader($ns, 'SessionHeader', $hbody),
	new SOAPHeader($ns, 'LoginScopeHEader', $lhb)
		);





$message ='bla';

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


$client = new SoapClientDebug('http://fwi.f24.com:8080/axis2/services/F24ActivationService1.1?wsdl', array("trace" => 1, "exception" => 1));

$client->__setSoapHeaders($header);


$client->startActivation($action);

	// $client->__soapCall(
	// 	'startActivation',
	// 	$action,
	// 	array(),
	// 	$header
	// );



}catch ( Exception $e) {
var_dump($e);
}








//var_dump($client);

// var_dump (
// 	$head
// 	); 


// $client = new SoapClient("ein.wsdl", array('trace' => 1));

// #$result = $client->EineFunktion();

// echo "ANFRAGE:\n" . $client->__getLastRequestHeaders() . "\n";


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