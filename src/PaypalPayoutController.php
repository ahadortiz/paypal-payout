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
    // $requestBody = '{"id":"WH-8DK82354G95998644-4T1484836X171290U","event_version":"1.0","create_time":"2018-11-29T14:35:04.000Z","resource_type":"payouts","event_type":"PAYMENT.PAYOUTSBATCH.SUCCESS","summary":"Payouts batch completed successfully.","resource":{"batch_header":{"payout_batch_id":"D5YAF6D4EJFVU","batch_status":"SUCCESS","time_created":"2018-11-29T14:34:45Z","time_completed":"2018-11-29T14:35:04Z","sender_batch_header":{"sender_batch_id":"5bfff90384393"},"amount":{"currency":"USD","value":"1.25"},"fees":{"currency":"USD","value":"0.03"},"payments":1},"links":[{"href":"https://api.sandbox.paypal.com/v1/payments/payouts/D5YAF6D4EJFVU","rel":"self","method":"GET"}]},"links":[{"href":"https://api.sandbox.paypal.com/v1/notifications/webhooks-events/WH-8DK82354G95998644-4T1484836X171290U","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/notifications/webhooks-events/WH-8DK82354G95998644-4T1484836X171290U/resend","rel":"resend","method":"POST"}]}';
    // $headers = array(
    //   'Paypal-Auth-Algo' => ['SHA256withRSA'],
    //   'Paypal-Cert-Url' => ['https://api.sandbox.paypal.com/v1/notifications/certs/CERT-360caa42-fca2a594-aecacc47'],
    //   'Paypal-Transmission-Sig' => ['s8ZqFchYhnqDLXPXLES1kVlyOz6y1ijvwQKp3XLkEvTZr1sXe4nHNMtvZ8nZjvDNgVC+LuQXsq8ewSvVplxa5JWqWoMH7wHBiXA46fOPsQDdxRnwJr7s8zKGukZ5T4O2t/rPpjwkgWui5kfbWxmehcfcd9l6w7YwuIHXqJGqRqJYZ3Jhz8Ugjv0qAqwdiS0Jd7Eull9JOKHtXdhS0kAm5tj9uQ9T1l/Zb0ePHtbU4BfCddaWFQ5HlMm4MSdpnZxd05km1rQ3E9CFvf7WxMl86TOX7rbXTa9bGIzo56S5q9p/NjOtFNImWRZs/OIxZbmPw8Zrq4FqceIae711P0Mz4Q=='],
    //   'Paypal-Transmission-Time' => ['2018-11-29T14:35:04Z'],
    //   'Paypal-Transmission-Id' => ['f5c64e60-f3e3-11e8-8941-d953a11868e8'],
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

    // accept only avaiable events
    $availabe_events = ['PAYMENT.PAYOUTSBATCH.SUCCESS', 'PAYMENT.PAYOUTSBATCH.DENIED'];
    $data = $request->all();
    if (!in_array($data['event_type'], $availabe_events)) {
      return response()->json('Not available event');
    }

    // update status
    $log = PayoutLog::where(['batch_id' => $data['resource']['batch_header']['payout_batch_id']])->first();
    if ($log) {
      $log->status = $data['resource']['batch_header']['batch_status'];
      $log->save();

      \Log::debug("{$log->batch_id} is $log->status");
    }

    return response()->json($isValid);
  }
}