<?php
namespace Raphael\PaypalPayout;

class PaypalPayout
{
  public static function getConfig()
  {
    $paypal_conf = \Config::get('paypal');
    $mode = $paypal_conf['settings']['mode'];

    return array_merge(
      $paypal_conf[$mode],
      [
        'settings' => $paypal_conf['settings']
      ]
    );
  }

  public static function getPaypalContext()
  {
    $conf = self::getConfig();
    $api_context = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential(
      $conf['client_id'],
      $conf['secret']
    ));
    $api_context->setConfig($conf['settings']);

    return $api_context;
  }

  public static function createPayout($email, $amount, $message = 'Thank you for your support')
  {
    $payouts = new \PayPal\Api\Payout();

    // create header
    $senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();
    $senderBatchHeader->setSenderBatchId(uniqid())
      ->setEmailSubject("You have a Payout!");

    // add items
    $item = [
      'value' => $amount,
      'currency' => 'USD',
    ];
    $senderItem = new \PayPal\Api\PayoutItem();
    $senderItem->setRecipientType('Email')
      ->setNote('Thanks for your patronage!')
      ->setReceiver($email)
      ->setSenderItemId(uniqid())
      ->setAmount(new \PayPal\Api\Currency(json_encode($item)));

    $payouts->setSenderBatchHeader($senderBatchHeader)
      ->addItem($senderItem);

    $request = clone $payouts;

    try {
      $batch = $payouts->create(null, self::getPaypalContext());

      $log = new PayoutLog;
      $log->batch_id = $batch->batch_header->payout_batch_id;
      $log->details = json_encode($item);
      $log->status = $batch->batch_header->batch_status;
      $log->save();

      return $log;
    } catch (\Exception $e) {
      return null;
    }
  }
}
