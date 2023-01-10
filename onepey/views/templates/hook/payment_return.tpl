{if $state == '1'}
<p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='onepey'}
<br /><br /> <strong>{l s='Your payment has been processed. Thank you for shopping with us. ' mod='onepey'}</strong>
<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='onepey'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='onepey'}</a>
</p>
{else if $state == '8'}
<p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='onepey'}
<br /><br /> <strong>{l s='Your order will be sent as soon as your payment is confirmed by the 1PEY gateway.' mod='onepey'}</strong>
<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='onepey'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='onepey'}</a>
</p>
{else}
<p class="warning">
{l s="We noticed a problem with your order. If you think this is an error, feel free to contact our Support Team" mod='onepey'}
<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='onepey'}</a>.
</p>
{/if}