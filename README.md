# raphael/paypal-payout

This package is developed for a special client.

## Installation

1.  Install package


         composer require raphael/paypal-payout

2.  publish config file

        php artisan vendor:publish

    After publishing, edit config/paypal.php with your setting


            return [
    		    'settings' => array(
    	    	    'mode' => env('PAYPAL_MODE', 'sandbox'),
    	    	    'http.ConnectionTimeOut' => 30,
    			    'log.LogEnabled' => true,
    	    	    'log.FileName' => storage_path() .  '/logs/paypal.log',
    	    	    'log.LogLevel' => 'ERROR'
        	    ),
        	    'sandbox' => [
    	    	    'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
    	    	    'secret' => env('PAYPAL_SANDBOX_SECRET', ''),
    	    	    'webhook_id' => env('PAYPAL_SANDBOX_WEBHOOK_ID', ''),
        	    ],
        	    'live' => [
    	    	    'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
    	    	    'secret' => env('PAYPAL_LIVE_SECRET', ''),
    	    	    'webhook_id' => env('PAYPAL_LIVE_WEBHOOK_ID', ''),
        	    ],
        ];

3.  create table for payout log

        php artisan migrate

4.  Set your paypal webhook url to `https://{your site url}/paypal/webhook`
