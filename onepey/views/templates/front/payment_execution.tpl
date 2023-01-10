{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}{l s='1PEY credit card payment.' mod='onepey'}{/capture}

<h2>{l s='Order summary' mod='onepey'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='onepey'}</p>
{else}

<form action="{$link->getModuleLink('onepey', 'validation', [], true)|escape:'html'}" method="post">
<p>
<img src="{$this_path}onepey.png" alt="{l s='Pay with OnePEY' mod='onepey'}" style="float:left; margin: 0px 20px 0 0;" />
{l s='You have chosen to pay by 1PEY using your credit card.' mod='onepey'}
<br/>
{l s='Please see below a short summary of your order:' mod='onepey'}
</p>
<p style="margin-top:30px;">
- {l s='The total amount of your order is' mod='onepey'}
<span id="amount" class="price">{displayPrice price=$total}</span>
{if $use_taxes == 1}
{l s='(tax incl.)' mod='onepey'}
{/if}
</p>
<p>
<b>{l s='Please confirm your order by clicking "I confirm my order."' mod='onepey'}.</b>
</p>

<p id="cart_navigation" class="cart_navigation clearfix">
	<button class="button btn btn-default button-medium" type="submit">
		<span>I confirm my order<i class="icon-chevron-right right"></i></span>
	</button>
	<a class="button-exclusive btn btn-default" href="/order?step=3">
		<i class="icon-chevron-left"></i>Other payment methods
	</a>
	
</p>

</form>
{/if}