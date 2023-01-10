<?php
class onepeyvalidationModuleFrontController extends ModuleFrontController
{
// 	public function __construct()
// 	{
// 		parent::__construct();
// 		$this->display_column_left = false;
// 	}
	
	
	public function initContent()
	{
		parent::initContent();
		$this->postProcess();	
	}
	
	
	/**
	* @see FrontController::postProcess()
	*/
	public function postProcess() {
		
		require_once(dirname(__FILE__) . "/../../OnePEY.php");
		require_once(dirname(__FILE__) . "/../../OnePEYCallResponse.php");
		require_once(dirname(__FILE__) . "/../../OnePEYCustomerReturnResponse.php");
		
		
		
		$cart = $this->context->cart;
		
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');
		
		$authorized = false;
		foreach (Module::getPaymentModules() as $module){
			
			if ($module['name'] == 'onepey') {
				$authorized = true;
				break;
			}
		}
		if (!$authorized)
			die($this->module->getTranslator()->trans('This payment method is not available.', array(), 'Modules.onepey.Shop'));
		
		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$mailVars = array();
		
		$cart = $this->context->cart;
		
		$onepeyRedirectCall = $onepeyRedirectURL = null;
		
		
		$onepeyInitCall = $this->module->executeInitialRequest($cart);
		
		if ($onepeyInitCall != null && is_array($onepeyInitCall) 
			&& array_key_exists('redirectURL', $onepeyInitCall)
			&& array_key_exists('callParams', $onepeyInitCall)){
			
			$onepeyRedirectCall = $onepeyInitCall['callParams'];
			$onepeyRedirectURL = $onepeyInitCall['redirectURL'];
		}
		
		if ($onepeyRedirectURL != null && $onepeyRedirectCall != null && 
				$onepeyRedirectCall->merchantID != null && $onepeyRedirectCall->pSign != null){
			
			$this->context->smarty->assign(array(
					'onepey_redirectURL' => $onepeyRedirectURL,
					'onepey_merchantID' => $onepeyRedirectCall->merchantID,
					'onepey_amount' => $onepeyRedirectCall->amount,
					'onepey_currency' => $onepeyRedirectCall->currency,
					'onepey_orderID' => $onepeyRedirectCall->orderID,
					'onepey_transactionID' => $onepeyRedirectCall->transactionID,
					'onepey_returnURL' => $onepeyRedirectCall->returnURL,
					'onepey_pSign' => $onepeyRedirectCall->pSign,
					'this_path' => $this->module->getPathUri(),
					'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
			));
			
			$this->setTemplate('module:onepey/views/templates/front/payment_customer_redirect.tpl');
		}
		else{
			
			$errors = new StdClass();
			$errors->code = "Payment Error! Could not complete the payment.";
			
			$cart = $this->context->cart;
			
			$this->context->smarty->assign(array(
					'onepey_error' => $errors,
					'this_path' => $this->module->getPathUri(),
					'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
			));
			
			$this->setTemplate('module:onepey/views/templates/front/payment_errors.tpl');
		}
	
	}
}