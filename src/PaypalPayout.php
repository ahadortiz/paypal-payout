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
      'email' => $email,
      'amount' => $amount,
      'sender_id' => uniqid(),
    ];

    $senderItem = new \PayPal\Api\PayoutItem();
    $senderItem->setRecipientType('Email')
      ->setNote("Please use {$item['sender_id']} to contact sender if you have any problem")
      ->setReceiver($item['email'])
      ->setSenderItemId($item['sender_id'])
      ->setAmount(new \PayPal\Api\Currency(json_encode([
        'value' => $item['amount'],
        'currency' => 'USD',
      ])));

    $payouts->setSenderBatchHeader($senderBatchHeader)
      ->addItem($senderItem);

    $request = clone $payouts;

    try {
      $apiContext = self::getPaypalContext();
      $payoutBatchId = $payouts->create(null, $apiContext)->getBatchHeader()->getPayoutBatchId();

      $log = new PayoutLog;

      $log->item_id = $item['sender_id'];
      $log->email = $item['email'];
      $log->amount = $item['amount'];
      $log->status = 'PENDING';

      $log->save();
      // $payoutBatch = \PayPal\Api\Payout::get($payoutBatchId, $apiContext);
      // $payoutItems = $payoutBatch->getItems();
      // foreach ($payoutItems as $detail) {
      //   $log = new PayoutLog;

      //   $log->status = $detail->getTransactionStatus();

      //   $payoutItem = $detail->getPayoutItem();
      //   $log->item_id = $payoutItem->getSenderItemId();
      //   $log->email = $payoutItem->getReceiver();
      //   $log->amount = $payoutItem->getAmount()->getValue();

      //   $log->save();
      // }

      return $log;
    } catch (\Exception $e) {
      // echo "PayPal Payout GetData:<br>" . $e->getData() . "<br><br>";
      return null;
    }
  }
}
