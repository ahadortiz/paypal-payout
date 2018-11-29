<?php
return [
  'settings' => array(
    'mode' => env('PAYPAL_MODE', 'sandbox'),
    'http.ConnectionTimeOut' => 30,
    'log.LogEnabled' => true,
    'log.FileName' => storage_path() . '/logs/paypal.log',
    'log.LogLevel' => 'ERROR'
  ),
  'sandbox' => [
    'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
    'secret' => env('PAYPAL_SANDBOX_SECRET', ''),
    'webhook_id' => env('PAYPAL_SANDBOX_SECRET', ''),
  ],
  'live' => [
    'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
    'secret' => env('PAYPAL_LIVE_SECRET', ''),
    'webhook_id' => env('PAYPAL_LIVE_SECRET', ''),
  ],
];
