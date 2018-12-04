<?php

namespace Raphael\PaypalPayout;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use \PayPal\Api\VerifyWebhookSignature;
use \PayPal\Api\WebhookEvent;

class PaypalPayoutController extends Controller
{
  private function validateWebhook(Request $request)
  {
    // $requestBody = '{"id":"WH-2F88658040694120E-7SF75308RH890690F","event_version":"1.0","create_time":"2018-11-29T16:27:48.000Z","resource_type":"payouts","event_type":"PAYMENT.PAYOUTSBATCH.SUCCESS","summary":"Payouts batch completed successfully.","resource":{"batch_header":{"payout_batch_id":"UVUV46WTMTE2A","batch_status":"SUCCESS","time_created":"2018-11-29T16:27:27Z","time_completed":"2018-11-29T16:27:48Z","sender_batch_header":{"sender_batch_id":"5c00136e4e3bd"},"amount":{"currency":"USD","value":"1.25"},"fees":{"currency":"USD","value":"0.03"},"payments":1},"links":[{"href":"https://api.sandbox.paypal.com/v1/payments/payouts/UVUV46WTMTE2A","rel":"self","method":"GET"}]},"links":[{"href":"https://api.sandbox.paypal.com/v1/notifications/webhooks-events/WH-2F88658040694120E-7SF75308RH890690F","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/notifications/webhooks-events/WH-2F88658040694120E-7SF75308RH890690F/resend","rel":"resend","method":"POST"}]}';
    // $headers = array(
    //   'Paypal-Auth-Algo' => ['SHA256withRSA'],
    //   'Paypal-Cert-Url' => ['https://api.sandbox.paypal.com/v1/notifications/certs/CERT-360caa42-fca2a594-aecacc47'],
    //   'Paypal-Transmission-Sig' => ['LSOeNSbPmnwDbfIQ1+/bat2X19bckzWFUjYvltDcmPhH4IMSCYIm/y65IeVlkyftbxb0k/6UWzhUXg+3nb2WNGVUTdjMIVc/tyRMWohOSlV0CoBj7nao+aoY4tHLO9HySM2BABuoh30vYAIcMFbl8Vslrz0rt0fQhiN/eOKJqmESv9iEazvtBzpNSmdzBYcjVQxZrFqNhKX3uPGIocUpFl7SVkpC7Dqlj4ll9jSXj1eR0+0pnZ+lcm06nsKmBzB6J0JcnV8LOOcqZn332BSQi5jDSFAras0HmxvjZKCHAYic3FV66i8q+gTdyEDOlAnxrUsP8SbLgFr+evxb7bg8vg=='],
    //   'Paypal-Transmission-Time' => ['2018-11-29T16:27:48Z'],
    //   'Paypal-Transmission-Id' => ['b5c8fa50-f3f3-11e8-b8f4-0de0de4a06f7'],
    // );
    $requestBody = file_get_contents('php://input');

    $headers = $request->headers->all();
    $headers = array_change_key_case($headers, CASE_UPPER);

    $signatureVerification = new VerifyWebhookSignature();
    $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO'][0]);
    $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID'][0]);
    $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL'][0]);
    $signatureVerification->setWebhookId(PaypalPayout::getConfig()['webhook_id']); // Note that the Webhook ID must be a currently valid Webhook that you created with your client ID/secret.
    $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG'][0]);
    $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME'][0]);

    $signatureVerification->setRequestBody($requestBody);
    $request = clone $signatureVerification;

    try {
      /** @var \PayPal\Api\VerifyWebhookSignatureResponse $output */
      $output = $signatureVerification->post(PaypalPayout::getPaypalContext());

      return $output->getVerificationStatus() != 'FAILURE';
    } catch (Exception $ex) {
      return false;
    }
  }

  public function webhook(Request $request)
  {
    $isValid = $this->validateWebhook($request);

    \Log::debug("--webhook event: " . ($isValid ? 'success' : 'fail') . "--");
    if (!$isValid) return;

    // accept only PAYOUT events
    $data = $request->all();

    if (strpos($data['event_type'], 'PAYMENT.PAYOUTS-ITEM') === false) {
      return response()->json('Not available event');
    }

    // update status
    $log = PayoutLog::where('item_id', $data['resource']['payout_item_id'])->first();
    if ($log) {
      $log->status = $data['resource']['transaction_status'];
      $log->transaction_id = $data['resource']['transaction_id'];
      $log->save();

      \Log::debug("{$log->item_id} is $log->status");
    }

    return response()->json($isValid);
  }
}