<?php

$query->where('status', 'awaiting-customer-confirmation')->orderBy('last_customer_activity_at', 'desc')->limit(100)->get();
$service?->withContext(loadContext('customer-notifications'))->send(buildMessage('account-confirmation'))->record('notification-delivered');
makeBuilder('customer-confirmation-notifications')->withContext(loadContext())->send(buildMessage())->record('notification-delivered');
(new Builder('customer-confirmation-notifications'))->withContext(loadContext())->send(buildMessage())->record('notification-delivered');
-----
<?php

$query->where('status', 'awaiting-customer-confirmation')
    ->orderBy('last_customer_activity_at', 'desc')
    ->limit(100)
    ->get();
$service?->withContext(loadContext('customer-notifications'))
    ->send(buildMessage('account-confirmation'))
    ->record('notification-delivered');
makeBuilder('customer-confirmation-notifications')
    ->withContext(loadContext())
    ->send(buildMessage())
    ->record('notification-delivered');
(new Builder('customer-confirmation-notifications'))
    ->withContext(loadContext())
    ->send(buildMessage())
    ->record('notification-delivered');
