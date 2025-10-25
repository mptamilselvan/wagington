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

class SendPromotionMailJob implements ShouldQueue
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
        $validFrom = $this->promotion['valid_from_date'] . ' ' . $this->promotion['valid_from_time'];
        $validTill = $this->promotion['valid_till_date'] . ' ' . $this->promotion['valid_till_time'];

        Mail::to('sonali@digitalprizm.net')
            ->send(new PromotionMail([
                'customer_name' => $this->customer['name'],
                'promotion_name' => $this->promotion['name'],
                'promo_code' => $this->promotion['promo_code'],
                'discount' => $this->promotion['discount_type'] === 'percentage'? $this->promotion['discount_value'].'%':$this->promotion['discount_value'].'SGD',
                'valid_till' =>  $validTill,
                'subject' => '"Exclusive Offer Just for You!"',
                'view' => 'emails.promotions',
            ]));
    }
}

