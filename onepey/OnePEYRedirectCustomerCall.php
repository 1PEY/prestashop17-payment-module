<?php

require_once(dirname(__FILE__) . "/OnePEYCallResponse.php");

class OnePEYRedirectCustomerCall extends OnePEYCallResponse {
	
	public $merchantID = null; //numeric 8
	public $amount = ""; // 1-32 string Merchant Unique Order/reference ID
	public $currency = ""; // 1-32 string Merchant Unique Order/reference ID
	public $orderID = ""; // 1-32 string Merchant Unique Order/reference ID
	public $returnURL = ""; // string 1-120
	public $transactionID = ""; // numeric 1-12
	
	//public $pSign -> calculated hash signature, implemented in base class
}
?>