<?php

class onepeyIpnModuleFrontController extends ModuleFrontController
{
	public function process()
	{
		
		$onepey = new onepey();
		$success = $onepey->processIPN($_GET, $_POST);
		echo 'OK';
		exit();
	}
}