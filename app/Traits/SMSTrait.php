<?php

namespace App\Traits;

use Log;
use DB;
use Auth;
use Exception;
use Twilio\Rest\Client; 

trait SMSTrait
{
    /**
     * Sends SMS message - accepts generated message, receiver number and then sends
     *
     * @return string
     */

    public function sendSMS($receiverNumber, $message)
    {
        try {
            $accountSid = env("TWILIO_SID");
            $authToken = env("TWILIO_TOKEN");
            $twilioNumber = env("TWILIO_FROM");

            $client = new Client($accountSid, $authToken);
            $client->messages->create($receiverNumber, [
                'from' => $twilioNumber,
                'body' => $message
            ]);

            Log::info('SMS Sent Successfully to' . $receiverNumber . 'Message:' . $message);
            return;
        } catch (Exception $e) {
            Log::info("Error: " . $e->getMessage());
        }
    }
}
