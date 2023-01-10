{capture name=path}{l s='1PEY credit card payment.' mod='onepey'}{/capture}

<h2>{l s='Unexpected payment error' mod='onepey'}</h2>

<p>
<img src="{$this_path}onepey.png" alt="{l s='Pay with OnePEY' mod='onepey'}" style="float:left; margin: 0px 20px 0 0;" />
{l s='Your payment could not complete. An unexpected error has occured.' mod='onepey'}
<br/>
{l s='Please contact customer support if the problem persists.' mod='onepey'}
</p>

<p id="cart_navigation" class="cart_navigation clearfix">
	<a class="button-exclusive btn btn-default" href="/">
		<i class="icon-chevron-left"></i>Back to Shop
	</a>
</p>

