<?php

require_once(dirname(__FILE__) . "/OnePEYCallResponse.php");

class OnePEYInitCall extends OnePEYCallResponse {

	public $merchantID = null; //numeric 8
	public $amount = null; //numeric (decimal 10,2) 1-12
	public $currency = ""; //string 3
	public $orderID = ""; // 1-32 string Merchant Unique Order/reference ID
	public $returnURL = ""; // string 1-120
	public $notifyURL = ""; // string 1-120

	public $customerIP = ""; //string 7-15
	public $customerForwardedIP = ""; //string 7-15 Customer http forwarder ip/ PHP code: $_SERVER['HTTP_X_FORWARDED_FOR']
	public $customerUserAgent = ""; //string 1-200 http Customer Agent String / PHP code: $_SERVER['HTTP_USER_AGENT']
	public $customerAcceptLanguage = ""; //string 1-60 Customer Accept Language String / PHP code: 	$_SERVER['HTTP_ACCEPT_LANGUAGE']

	public $customerEmail = ""; //string 1-120
	public $customerPhone = ""; //string 7-16
	public $customerFirstName = ""; //string 32
	public $customerLastName = ""; //string 32
	public $customerAddress1 = ""; //string 64
	public $customerAddress2 = ""; //string 64
	public $customerCity = ""; //string 32
	public $customerZipCode = ""; //string 16
	public $customerStateProvince = ""; //string 64
	public $customerCountry = ""; //string 2 country iso

	//public $pSign ----> implemented in Base Class

}
?>