======================================================================
<{$greeting}> <{$customerName}>

<{$thankYou}>
======================================================================

----------------------------------------------------------------------
<{$order_details_label}>
----------------------------------------------------------------------
<{$order_id_label}>: #<{$order_id}>
<{$order_date_label}>: <{$order_date}>

----------------------------------------------------------------------
<{$customer_info_label}>
----------------------------------------------------------------------
<{if $customerName}><{$name_label}>: <{$customerName}><{/if}>
<{if $customerEmail}><{$email_label}>: <{$customerEmail}><{/if}>
<{if $customerPhone}><{$phone_label}>: <{$customerPhone}><{/if}>
<{if $customerTablePreference}><{$table_pref_label}>: <{$customerTablePreference}><{/if}>
<{if $customerHelpendehanden}><{$help_label}>: <{$customerHelpendehanden}><{/if}>

----------------------------------------------------------------------
<{$items_label}>
----------------------------------------------------------------------
<{$name_label|string_format:"%-35s"}> <{$price_label|string_format:"%12s"}> <{$qty_label|string_format:"%8s"}> <{$subtotal_label|string_format:"%12s"}>
----------------------------------------------------------------------
<{foreach from=$items item=item}>
<{$item.name|truncate:35|string_format:"%-35s"}> <{$item.price|string_format:"%12s"}> <{$item.qty|string_format:"%8d"}> <{$item.subtotal|string_format:"%12s"}>
<{/foreach}>
----------------------------------------------------------------------
<{$total_label|string_format:"%-35s"}> <{""|string_format:"%12s"}> <{""|string_format:"%8s"}> <{$total_amount|string_format:"%12s"}>
======================================================================

<{if $sepa}>
----------------------------------------------------------------------
<{$payment_info_label}>
----------------------------------------------------------------------
<{$beneficiary_label}>: <{$sepa.beneficiary_name}>
<{$iban_label}>: <{$sepa.beneficiary_iban}>
<{if $sepa.beneficiary_bic}>BIC: <{$sepa.beneficiary_bic}><{/if}>
<{$amount_label}>: <{$total_amount}>

<{$payment_instructions}>
----------------------------------------------------------------------

<{/if}>
<{$footer_text}>
======================================================================
