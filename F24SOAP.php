<?php

class F24SOAP extends SoapClient {

	public $headers;
	public function __call($function, $args) {
		$query_string = http_build_query(array(
			'callname' => $function, /* ... */);
		$location = "{$this->location}?{$query_string}";
		return $this->__soapCall($function, $args,
		array('location' => $location), $this->headers);
	}
}
?>