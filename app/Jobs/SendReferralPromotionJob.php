<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; 
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\PromotionMail;
use App\Models\User;

class SendReferralPromotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customer;
    public $promotion;

    public function __construct($customer, $promotion)
    {
        $this->customer = $customer;
        $this->promotion = $promotion;
    }

    public function handle()
    {
        $valid_from_date = $this->promotion['valid_from_date'] . ' ' . $this->promotion['valid_from_time'];
        $valid_till_date = $this->promotion['valid_till_date'] . ' ' . $this->promotion['valid_till_time'];

        Mail::to('sonali@digitalprizm.net')
            ->send(new PromotionMail([
                'customer_name' => $this->customer['name'],
                'promotion_name' => $this->promotion['name'],
                'description' => $this->promotion['description'],
                'referrer_reward' => $this->promotion['referrer_reward'],
                'referee_reward' => $this->promotion['referee_reward'],
                'valid_from_date' => $valid_from_date,
                'valid_till_date' =>  $valid_till_date,
                'discount_type' =>  $this->promotion['discount_type'],
                'subject' => 'New Referral Promotion Available!',
                'view' => 'emails.referral_promotion',
            ]));
    }
}

