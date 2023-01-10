<?php

require_once(dirname(__FILE__) . "/OnePEYCallResponse.php");

class OnePEYCustomerReturnResponse extends OnePEYCallResponse {
	
	public $responseCode = null; //numeric 8
	public $reasonCode = null; //numeric 8
	public $transactionID = ""; // numeric 1-12
	public $amount = null; //numeric (decimal 10,2) 1-12
	public $currency = ""; //string 3
	public $orderID = ""; // 1-32 string Merchant Unique Order/reference ID
	public $executed = ""; // string 1-120
	
	//public $pSign -> calculated hash signature, implemented in base class
}
?>