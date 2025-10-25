<?php
namespace App\Livewire\Frontend;

use Livewire\Component;
use App\Models\User;
use App\Models\BasePromotion;

class ReferralDashboard extends Component
{
    public $tab = 'signed'; // default tab

    public function switchTab($tab)
    {
        $this->tab = $tab;
    }

    public function render()
    {
        $promotion = BasePromotion::where('promotion', 'referralpromotion')->where('published','=',true)->first();

        $user = auth()->user();

        $signedUp = $user->referredUsers()->latest()->get();
        // $completed = $user->referredUsers()
        //     ->whereHas('bookings', fn($q) => $q->where('status', 'completed'))
        //     ->get();

        return view('livewire.frontend.referral-dashboard', [
            'myReferralCode' => $user->referal_code,
            'signedUp' => $signedUp,
            'completed' => [],
            'promotion' => $promotion
        ]);
    }

    public function index()
    {
        return view('frontend.referral-dashboard');
    }
}

