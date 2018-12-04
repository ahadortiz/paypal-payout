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
    $log = PayoutLog::where('item_id', $data['resource']['payout_item']['sender_item_id'])->first();
    if ($log) {
      $log->status = $data['resource']['transaction_status'];
      $log->transaction_id = $data['resource']['transaction_id'];
      $log->save();

      \Log::debug("{$log->item_id} is $log->status");
    }

    return response()->json($isValid);
  }
}