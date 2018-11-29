<?php

Route::any('/paypal/webhook', '\Raphael\PaypalPayout\PaypalPayoutController@webhook');