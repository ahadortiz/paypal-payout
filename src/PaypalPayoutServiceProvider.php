<?php
namespace Raphael\PaypalPayout;

use Illuminate\Support\ServiceProvider;

class PaypalPayoutServiceProvider extends ServiceProvider
{
  public function boot()
  {
    $this->publishes([
      __DIR__ . '/../config/paypal.php' => config_path('paypal.php'),
    ], 'config');

    $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    $this->loadRoutesFrom(__DIR__ . '/routes.php');
  }

  public function register()
  {
  }
}