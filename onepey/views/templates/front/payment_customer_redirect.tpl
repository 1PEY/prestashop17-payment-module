
{capture name=path}{l s='1PEY credit card payment.' mod='onepey'}{/capture}

<h2>{l s='Order summary' mod='onepey'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<p>
<img src="{$this_path}onepey.png" alt="{l s='Pay with OnePEY' mod='onepey'}" style="float:left; margin: 0px 20px 0 0;" />
{l s='If your browser does not start loading the page, press the button below.' mod='onepey'}  
<br/>
{l s='You will be sent to 1PEY to make the payment.' mod='onepey'}
</p>

<form name="OnePEYCustomerRedirect" method="post" action="{$onepey_redirectURL}">
	
	<input type="hidden" name="merchantID" value="{$onepey_merchantID}" />
	<input type="hidden" name="amount" value="{$onepey_amount}" />
	<input type="hidden" name="currency" value="{$onepey_currency}" />
	<input type="hidden" name="orderID" value="{$onepey_orderID}" />
	<input type="hidden" name="returnURL" value="{$onepey_returnURL}" />
	<input type="hidden" name="transactionID" value="{$onepey_transactionID}" />
	<input type="hidden" name="pSign" value="{$onepey_pSign}" />

	<p id="cart_navigation" class="cart_navigation clearfix">
		<button class="button btn btn-default button-medium" type="submit">
			<span>Pay with OnePEY<i class="icon-chevron-right right"></i></span>
		</button>
		<a class="button-exclusive btn btn-default" href="/">
			<i class="icon-chevron-left"></i>Back to Shop
		</a>
	</p>
</form>
<script type="text/javascript">document.OnePEYCustomerRedirect.submit();</script>

