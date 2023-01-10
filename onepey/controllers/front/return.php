<?php

class onepeyReturnModuleFrontController extends ModuleFrontController
{
	public function process()
	{
		
		$onepey = new onepey();
		$success = $onepey->processCustomerReturn($_GET, $_POST);
		
		Tools::redirect('index.php?controller=history'.(($success == true)?'&onepey_order_sucess=1':'&onepey_order_error=1'), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
		exit();
	}
}