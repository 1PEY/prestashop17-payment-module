<?php

/*
 *  @author 1PEY <bobby@1pey.com>
 *  @copyright  2014 1PEY
 *
 *  International Registered Trademark & Property of 1PEY
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Adapter\Entity\Order;

if (!defined('_PS_VERSION_'))
	exit;

class onepey extends PaymentModule
{
	protected $_html = '';
	protected $_postErrors = array();
	
	public $details;
	public $owner;
	public $address;
	public $extra_mail_vars;
	
	const STATUS_PENDING = 3;
	const STATUS_ERROR = 8;
	const STATUS_CANCEL = 6;
	const STATUS_REFUND = 7;
	const STATUS_SUCCESS = 2;
	
	const REMOTE_STATUS_APPROVED = 1;
	const REMOTE_STATUS_DECLINED = 2;
	const REMOTE_STATUS_FAILED = 3;
	const REMOTE_STATUS_REDIRECT = 4;
	const REMOTE_STATUS_CANCELLED = 5;
	const REMOTE_STATUS_PENDING_PROCESSOR = 8;
	
	
	public function __construct() {
		
		include(dirname(__FILE__).'/config.php');
		
		$this->name = 'onepey';
		$this->version = '1.0';
		$this->author = '1PEY';
		$this->className = 'onepey';
		$this->tab = 'payments_gateways';
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
		$this->author = 'PrestaShop';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;
		
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		
		$this->onepeyurl = $onepeyurl;
		$this->apiurl = $apiurl;
		$this->api_initialRequestUri = $api_initialRequestUri;
		$this->api_redirectCustomerUri = $api_redirectCustomerUri;
		$this->sslport = $sslport;
		$this->verifypeer = $verifypeer;
		$this->verifyhost = $verifyhost;
		
		$this->bootstrap = true;
		parent::__construct();
		
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->trans('1PEY', array(), 'Modules.onepey.Admin');
		$this->description = $this->trans('Accepts credit card payments via 1PEY.', array(), 'Modules.onepey.Admin');
		$this->confirmUninstall = $this->trans('Are you sure about removing these details?', array(), 'Modules.onepey.Admin');
		
		if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
			$this->warning = $this->trans('Account owner and account details must be configured before using this module.', array(), 'Modules.onepey.Admin');
		}
		if (!count(Currency::checkPaymentCurrencies($this->id))) {
			$this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.onepey.Admin');
		}
		
		$this->extra_mail_vars = array();
	}
	
	
	public function install() {
		
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);
		
		if(!function_exists('curl_version')) {
			$this->_errors[] = $this->trans('Sorry, this module requires the cURL PHP extension but it is not enabled on your server. Please ask your Admin or Web Hosting Provider for assistance.', array(), 'Modules.onepey.Admin');
			return false;
		}
		
		if(!function_exists('json_encode') || !function_exists('json_decode')) {
			$this->_errors[] = $this->trans('Sorry, this module requires the json_encode/json-decode PHP functions - not enabled on your server. Please ask your Admin or Web Hosting Provider for assistance.', array(), 'Modules.onepey.Admin');
			return false;
		}
		
		if (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions') || !$this->registerHook('header')) {
			return false;
		}
		
		return true;
	}
	
	
	protected function _postValidation()
	{
		
	}
	
	
	/**
	 * @param $state
	 * @param $names
	 *
	 * @return bool
	 */
	private function addNewOrderState($state, $names, $template=null, $paidFlag=0)
	{
		if (Validate::isInt(Configuration::get($state)) 
			&& (Validate::isLoadedObject($order_state = new OrderState(Configuration::get($state)))))
		{
			$order_state = new OrderState();
	
			if (!empty($names))
			{
				foreach ($names as $code => $name)
					$order_state->name[Language::getIdByIso($code)] = $name;
					if ($template != null)
						$order_state->template[Language::getIdByIso($code)] = $template;
			}
	
			$order_state->module_name = 'onepey';
			$order_state->send_email = false;
			$order_state->invoice = false;
			$order_state->unremovable = false;
			$order_state->color = '#00AEEF';
			$order_state->paid = $paidFlag == 1 ? 1:0;
			$order_state->delivery = $paidFlag == 1 ? 1:0;
			
	
			if (!$order_state->add() ||
				!Configuration::updateValue($state, $order_state->id) || !Configuration::updateValue($state, $order_state->id))
					return false;
	
			copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'os/'.$order_state->id.'.gif');
	
			return $order_state->id;
		}
	
		return false;
	}
	
	
	
	public function uninstall() {
		
		$languages = Language::getLanguages(false);
		foreach ($languages as $lang) {
			if (!Configuration::deleteByName('BANK_WIRE_CUSTOM_TEXT', $lang['id_lang'])) {
				return false;
			}
		}
		
		if (!Configuration::deleteByName('1PEY_MERCHANTID')
				|| !Configuration::deleteByName('1PEY_PASSCODE')
				|| !Configuration::deleteByName('PS_OS_1PEY') 
				|| !parent::uninstall()) {
            return false;
        }
        
		return true;
	}
	
	
	
	public function hookPaymentReturn($params) {
		
		$order = ($params['objOrder']);
		//$state = $order->current_state;
		$state = $params['order']->getCurrentState();
		$this->smarty->assign(array(
				'state' => $state,
				'this_path' => $this->_path,
				'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));
		//return $this->display(__FILE__, 'payment_return.tpl');
		//return $this->fetch('onepey/views/templates/hook/payment_return.tpl');
		header("Location: ".Configuration::get('PS_FO_PROTOCOL').'index.php?fc=module&module=onepey&controller=return');
		exit();
	}
	
	public function hookHeader()
	{
		//$this->context->controller->addCSS($this->_path.'css/onepey.css', 'all');
		if (Tools::getValue('onepey_order_error'))
			return sprintf('<script>alert(%s);</script>', ToolsCore::jsonEncode($this->trans('An error occurred when processing the order.', array(), 'Modules.onepey.Admin')));
		
		if (Tools::getValue('onepey_order_sucess'))
			return sprintf('<script>alert(%s);</script>', ToolsCore::jsonEncode($this->trans('Your payment has been approved and successful.', array(), 'Modules.onepey.Admin')));
		
	}
	
	
	public function getContent() {
		$this->_html .= '<br />';
		$this->_html .= '<h2>'.$this->trans('1PEY').'</h2>';
	
		$this->_postProcess();
		$this->_setOnePEYActivation();
		$this->_setConfigurationForm();
	
		return $this->_html;
	}
	
	
	private function _setOnePEYActivation() {
		$this->_html .= '<div style="float: right; width: 440px; height: 150px; border: dashed 1px #666; padding: 8px; margin-left: 12px;">
                       <h2>'.$this->trans('1PEY Account', array(), 'Modules.onepey.Admin').'</h2>
                       <div style="clear: both;"></div>
                       <p>'.$this->trans('Please activate your account with 1PEY by contacting our representatives.', array(), 'Modules.onepey.Admin').'</p>
                       <p>'.$this->trans('You may find more details by visiting 1PEY website - click on logo below.', array(), 'Modules.onepey.Admin').'</p>
                       <p style="text-align: center;"><a href="http://www.1pey.com/"><img src="../modules/OnePEY/onepey.png" alt="PrestaShop & 1PEY" style="margin: 0;" /></a></p>
                       <div style="clear: right;"></div>
                       </div>
                       <b>'.$this->trans('This module allows you to execute credit card payments with 1PEY payment gateway.', array(), 'Modules.onepey.Admin').'</b><br /><br />
                       '.$this->trans('You need to configure your 1PEY account before using this module.', array(), 'Modules.onepey.Admin').'
                       <div style="clear:both;">&nbsp;</div>';
	}
	
	
	private function _setConfigurationForm() {
		$this->_html .= '<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
                       <script type="text/javascript">
                       var pos_select = '.(($tab = (int)Tools::getValue('tabs')) ? $tab : '0').';
                       </script>';
	
		if (_PS_VERSION_ <= '1.5') {
			$this->_html .= '<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'tabpane.js"></script>
                         <link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_CSS_DIR_.'tabpane.css" />';
		} else {
			$this->_html .= '<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'jquery/plugins/tabpane/jquery.tabpane.js"></script>
                         <link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_JS_DIR_.'jquery/plugins/tabpane/jquery.tabpane.css" />';
		}
	
		$this->_html .= '<input type="hidden" name="tabs" id="tabs" value="0" />
                       <div class="tab-pane" id="tab-pane-1" style="width:100%;">
                       <div class="tab-page" id="step1">
                       <h4 class="tab">'.$this->trans('Settings', array(), 'Modules.onepey.Admin').'</h2>
                       '.$this->_getSettingsTabHtml().'
                       </div>
                       </div>
                       <div class="clear"></div>
                       <script type="text/javascript">
                       function loadTab(id){}
                       setupAllTabs();
                       </script>
                       </form>';
	}
	
	
	
	
	public function _getSettingsTabHtml() {
	
		$html = '<h2>'.$this->trans('Settings', array(), 'Modules.onepey.Admin').'</h2>
               <h4 style="clear:both;">'.$this->trans('MerchantID', array(), 'Modules.onepey.Admin').'</h4>
               <div class="margin-form">
               <input type="text" name="merchantid_onepey" value="'.htmlentities(Tools::getValue('merchantid', Configuration::get('ONEPEY_MERCHANTID')), ENT_COMPAT, 'UTF-8').'" />
               </div>
               <h4 style="clear:both;">'.$this->trans('PassCode', array(), 'Modules.onepey.Admin').'</h4>
               <div class="margin-form">
               <input type="text" name="passcode_onepey" value="'.htmlentities(Tools::getValue('passcode', Configuration::get('ONEPEY_PASSCODE')), ENT_COMPAT, 'UTF-8').'" />
               </div>
               <br/>
               <p class="center"><input class="button" type="submit" name="submitonepey" value="'.$this->trans('Save settings', array(), 'Modules.onepey.Admin').'" /></p>';
	
		return $html;
	}
	
	
	public function hookPaymentOptions($params)
	{
		if (!$this->active) {
			return;
		}
	
		if (!$this->checkCurrency($params['cart'])) {
			return;
		}
	
		$this->smarty->assign(
			$this->getTemplateVarInfos()
		);
	
		$newOption = new PaymentOption();
		$newOption->setCallToActionText($this->trans('OnePEY', array(), 'Modules.onepey.Shop'))
						->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
						->setAdditionalInformation($this->fetch('module:onepey/views/templates/hook/onepey_intro.tpl'));
		
		$payment_options = [$newOption,];
		
		return $payment_options;
	}
	
	
	
	
	 protected function _postProcess() {
		
		if (Tools::isSubmit('submitonepey')) {
			$template_available = array('A', 'B', 'C');
			$this->_errors      = array();
	
			if (Tools::getValue('merchantid_onepey') == NULL)
				$this->_errors[]  = $this->trans('Missing Merchant ID', array(), 'Modules.onepey.Admin');
	
			if (Tools::getValue('passcode_onepey') == NULL)
				$this->_errors[]  = $this->trans('Missing API Passcode', array(), 'Modules.onepey.Admin');
			
			if (count($this->_errors) > 0) {
				$error_msg = '';
	
				foreach ($this->_errors AS $error)
					$error_msg .= $error.'<br />';
	
				$this->_html .= $this->displayError($error_msg);
			} else {
				Configuration::updateValue('ONEPEY_MERCHANTID', trim(Tools::getValue('merchantid_onepey')));
				Configuration::updateValue('ONEPEY_PASSCODE', trim(Tools::getValue('passcode_onepey')));
	
				$this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
			}
		}
	
	}
	
	public function checkCurrency($cart){
		
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);
	
		if (is_array($currencies_module)) {
			foreach ($currencies_module as $currency_module) {
				if ($currency_order->id == $currency_module['id_currency']) {
					return true;
				}
			}
		}
		return false;
	}
	
	
	
	public function processIPN($get, $post){
		
		return $this->processCustomerReturn($_GET, $_POST);
	}
	
	
	
	public function processCustomerReturn($get, $post){
		
		require_once(dirname(__FILE__) . "/OnePEYCustomerReturnResponse.php");
		
		$customerReturnResponse = new OnePEYCustomerReturnResponse($_GET);
		if (!isset($customerReturnResponse->responseCode))
			$customerReturnResponse = new OnePEYCustomerReturnResponse($_POST);
		
		
		if (!isset($customerReturnResponse->responseCode))
			return false;
		
		if (!$customerReturnResponse->isValidSignature(Configuration::get('ONEPEY_PASSCODE'))){
			
			return false;
		}
			
		else{
			
			
			$internalOrderId = $customerReturnResponse->orderID;
			$internalOrderId = (int)substr($internalOrderId,strrpos($internalOrderId,'-')+1);
			
			$history = new OrderHistory();
			$history->id_order = $internalOrderId;
			
			$error = Tools::getValue('err');
			
			if ($error)
			{
				$history->changeIdOrderState(self::STATUS_ERROR, $internalOrderId);
				
				$order=new Order($internalOrderId);
				$order->setCurrentState(self::STATUS_ERROR);
				//$history->addWithemail(true);
			}
			
			if ($customerReturnResponse->responseCode == OnePEYCallResponse::ONEPEY_CALL_RESPONSE_CODE_APPROVED
					&& $customerReturnResponse->reasonCode == OnePEYCallResponse::ONEPEY_CALL_REASON_CODE_APPROVED){
				
				
				$history->changeIdOrderState(_PS_OS_PAYMENT_, $internalOrderId);
				//$history->addWithemail(true);
				
				$order = new Order($internalOrderId);
				$order->setCurrentState(_PS_OS_PAYMENT_);
				
				$payment = $order->getOrderPaymentCollection();
				$payments = $payment->getAll();
				$payments[$payment->count() - 1]->transaction_id = $customerReturnResponse->transactionID;
				$payments[$payment->count() - 1]->update();
				
				return true;
			}
			else if ($customerReturnResponse->responseCode == OnePEYCallResponse::ONEPEY_CALL_RESPONSE_CODE_DECLINED){
				
				$history->changeIdOrderState(_PS_OS_ERROR_, $internalOrderId);
				
				$order = new Order($internalOrderId);
				$order->setCurrentState(_PS_OS_ERROR_);
				
				return false;
			}
			else if ($customerReturnResponse->responseCode == OnePEYCallResponse::ONEPEY_CALL_RESPONSE_CODE_ERROR){
				
				$history->changeIdOrderState(_PS_OS_ERROR_, $internalOrderId);
				$order = new Order($internalOrderId);
				$order->setCurrentState(_PS_OS_ERROR_);
				return false;
			}
			else if ($customerReturnResponse->responseCode == OnePEYCallResponse::ONEPEY_CALL_RESPONSE_CODE_CANCELED){
				
				$history->changeIdOrderState(_PS_OS_CANCELED_, $internalOrderId);
				$order = new Order($internalOrderId);
				$order->setCurrentState(_PS_OS_CANCELED_);
				return false;
			}
			else if ($customerReturnResponse->responseCode == OnePEYCallResponse::ONEPEY_CALL_RESPONSE_CODE_PENDING_PROCESSOR){
				
				$history->changeIdOrderState(_PS_OS_PREPARATION_, $internalOrderId);
				$order = new Order($internalOrderId);
				$order->setCurrentState(_PS_OS_PREPARATION_);
				return false;
			}
			else{
				
				$history->changeIdOrderState(_PS_OS_ERROR_, $internalOrderId);
				$order = new Order($internalOrderId);
				$order->setCurrentState(_PS_OS_ERROR_);
			}
		}
		return false;
	}
	
	
	/**
	 * 
	 * @param Cart $cart
	 */
	public function executeInitialRequest($cart) {
		
		$orderNo = $this->context->cart->id;
		$cart = New Cart($orderNo);
		$total = $cart->getOrderTotal(true, Cart::BOTH);
		
		$currency = Currency::getCurrencyInstance((int)$cart->id_currency);
		$notificationURL = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').'/index.php?fc=module&module=onepey&controller=ipn';
		$returnShopUrl = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').'/index.php?fc=module&module=onepey&controller=return';
		$address = AddressCore::addressExists($cart->id_address_invoice) ? new AddressCore($cart->id_address_invoice):null;
		$addressDelivery = AddressCore::addressExists($cart->id_address_delivery) ? new AddressCore($cart->id_address_delivery):new AddressCore($cart->id_address_invoice);
		$customer = $this->context->customer;
		$cartId = $cart->id;
		
		if ($customer == null || $address == null){
			return null;
		}
		
		$this->validateOrder($cart->id, Configuration::get('PS_OS_CHEQUE'),$total, $this->displayName);
		
		$order = new Order(OrderCore::getOrderByCartId($cartId));
		$orderId = $order->id;
		
		// Build 1PEY Call parameters for Initial Request
		require_once(dirname(__FILE__) . "/OnePEYInitCall.php");
		
		$onepeyExecuteCall = new OnePEYInitCall();
		$onepeyExecuteCall->merchantID = Configuration::get('ONEPEY_MERCHANTID');
		$onepeyExecuteCall->amount = round($total,2);
		$onepeyExecuteCall->currency = $currency->iso_code;
		$onepeyExecuteCall->orderID = date('YmdHis').'-'.str_pad(''.$orderId, 8, '0', STR_PAD_LEFT);
		$onepeyExecuteCall->returnURL = $returnShopUrl;
		$onepeyExecuteCall->notifyURL = $notificationURL;
		$onepeyExecuteCall->customerIP = $_SERVER['REMOTE_ADDR'];
		$onepeyExecuteCall->customerEmail = $customer->email;
		$onepeyExecuteCall->customerPhone = $address->phone;
		$onepeyExecuteCall->customerFirstName= $address->firstname;
		$onepeyExecuteCall->customerLastName= $address->lastname;
		$onepeyExecuteCall->customerAddress1 = $address->address1;
		
		if (trim($address->address2) != '')
			$onepeyExecuteCall->customerAddress2 = $address->address2;
		
		$onepeyExecuteCall->customerCity = $address->city;
		$onepeyExecuteCall->customerZipCode = $address->postcode;
		
		$custState = StateCore::getNameById($address->id_state);
		if (trim($custState) != '')
			$onepeyExecuteCall->customerStateProvince = $custState;
		
		$custCountry = CountryCore::getIsoById($address->id_country);
		if (trim($custCountry) != '')
			$onepeyExecuteCall->customerCountry = $custCountry;
		
		$onepeyExecuteCall->pSign = $onepeyExecuteCall->buildSignature(Configuration::get('ONEPEY_PASSCODE'));
		
		$paramsInitReq = $onepeyExecuteCall->buildUrlParams();
		
				
		// Call 1PEY - Initial Request (Register Transaction then Redirect Customer)
		$initReqUrl = $this->apiurl.$this->api_initialRequestUri;
		$ch = curl_init($initReqUrl);
		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$paramsInitReq);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifypeer); // verify certificate (1)
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifyhost); // check existence of CN and verify that it matches hostname (2)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$result = curl_exec($ch);
		
		if (empty($result) ){
			//die(Tools::displayError("Empty 1PEY response! General Failure! Empty response."));
			return null;
		}
		
		$info = curl_getinfo($ch);
		if (curl_errno($ch) > 0){
			//die(Tools::displayError("OnePEY Response protocol Error! CURL ERROR: ".curl_errno($ch) . '-' . curl_error($ch)));
			return null;
		}
		
		curl_close ($ch);
		
		
		$responseHttpCode = $info['http_code'];
		
		if ($responseHttpCode != 200){
			//die(Tools::displayError("OnePEY Response HTTP Error: " . $responseHttpCode));
			return null;
		}
		
		if ($result === false){
			//die(Tools::displayError('Empty 1PEY response! General Failure! Blank response.'));
			return null;
		}
		
		$result = utf8_encode($result);
		
		$result = json_decode($result, true);
		if (!is_array($result)) $result = array();
		if (isset($result['transaction'])){
			$transactResp = $result['transaction'];
		}
		else
			$transactResp = null;
		
		
		if (isset($result['responseCode']) && $result['responseCode'] == 4){
			
			$redirectURL = $result['redirectURL'];	
			
			require_once(dirname(__FILE__) . "/OnePEYRedirectCustomerCall.php");
			
			$onepeyRedirectCall = new OnePEYRedirectCustomerCall();
			
			$onepeyRedirectCall->merchantID = Configuration::get('ONEPEY_MERCHANTID');
			$onepeyRedirectCall->amount = round($total, 2);
			$onepeyRedirectCall->currency = $currency->iso_code;
			$onepeyRedirectCall->orderID = $transactResp['orderID'];
			$onepeyRedirectCall->returnURL = $returnShopUrl;
			$onepeyRedirectCall->transactionID = $transactResp['transactionID'];
			
			$onepeyRedirectCall->pSign = $onepeyRedirectCall->buildSignature(Configuration::get('ONEPEY_PASSCODE'));
			
			header("Location: ".$redirectURL.'?'.http_build_query($onepeyRedirectCall));
			exit();
		}
		
		header("Location: ".Configuration::get('PS_FO_PROTOCOL').'index.php?fc=module&module=onepey&controller=return');
		exit();
	}
	
	
	public function getTemplateVarInfos()
	{
		$cart = $this->context->cart;
		$total = sprintf(
				$this->trans('%1$s (tax incl.)', array(), 'Modules.WirePayment.Shop'),
				Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
				);
	
		return array(
				'total' => $total,
		);
	}
	
	
	
	
	
}
