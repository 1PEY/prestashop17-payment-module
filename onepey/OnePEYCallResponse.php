<?php

class OnePEYCallResponse {

	const ONEPEY_CALL_RESPONSE_CODE_APPROVED = 1;
	const ONEPEY_CALL_RESPONSE_CODE_DECLINED = 2;
	const ONEPEY_CALL_RESPONSE_CODE_ERROR = 3;
	const ONEPEY_CALL_RESPONSE_CODE_REDIRECT = 4;
	const ONEPEY_CALL_RESPONSE_CODE_CANCELED = 5;
	const ONEPEY_CALL_RESPONSE_CODE_PENDING_PROCESSOR = 8;

	const ONEPEY_CALL_REASON_CODE_APPROVED = 1;
	const ONEPEY_CALL_REASON_CODE_DECLINED = 2;
	const ONEPEY_CALL_REASON_CODE_NOT_SECURE_CONNECTION = 100;
	const ONEPEY_CALL_REASON_CODE_INVALID_HTTP_METHOD = 101;
	const ONEPEY_CALL_REASON_CODE_FIELD_MISSING = 102;
	const ONEPEY_CALL_REASON_CODE_FIELD_INVALID = 103;
	const ONEPEY_CALL_REASON_CODE_AUTH_FAILED = 104;
	const ONEPEY_CALL_REASON_CODE_GENERAL_ERROR = 105;
	const ONEPEY_CALL_REASON_CODE_DUPLICATE_ORDER = 106;
	const ONEPEY_CALL_REASON_CODE_ANTIFRAUD_REJECT = 107;
	const ONEPEY_CALL_REASON_CODE_BANK_REDIRECT = 108;

	public $pSign = ""; //string 40 - Hash (sha1) from SIGN algorhtym using all params according to specs and secret key

	/**
	 * Initialize Object directly from HTTP Params ($_GET or $_POST)
	 */
	public function __construct($httpParams=null){


		if ($httpParams == null)
			return;

		foreach ($this as $key=>$val){

			if (!isset($httpParams[$key]) || $httpParams[$key] == null || trim($httpParams[$key]) == ''){

				$this->$key = null;
			}
			else{
				//All mandatory params okay, assigning http vars to object props(vars)
				$this->$key = $httpParams[$key];
			}
		}
	}

	/**
	 * Check if All Parameters have been sent in 1PEY Response
	 */
	public function isValidCall(){

		foreach ($this as $key=>$val){

			if ($this->$key == null || trim($this->$key) == ''){

				return false;
			}
		}
		return true;
	}



	/**
	 * Check if the Signature is Valid
	 */
	public function isValidSignature($secretKey){

		$str = "";
		foreach ($this as $key=>$val){

			if ($key == 'pSign')
				continue;

			$str .= $this->$key;
		}

		return ($this->pSign == sha1($secretKey.$str)) ? true:false;
	}

	/**
	 * Build the psign into this Object
	 */
	public function buildSignature($secretKey){

		$str = "";
		foreach ($this as $key=>$val){

			if ($key == 'pSign')
				continue;

			$str .= $this->$key;
		}

		return sha1($secretKey.$str);
	}
	
	/**
	 * Build the psign into this Object
	 */
	public function buildUrlParams(){

		$str = "";
		foreach ($this as $key=>$val){
			$str .= "&".$key."=".urlencode($this->$key);
		}

		return substr($str, 1);
	}

}
?>