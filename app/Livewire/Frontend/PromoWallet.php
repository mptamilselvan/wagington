<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use App\Models\Voucher;
use App\Models\BasePromotion;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\VoucherService;

class PromoWallet extends Component
{
    protected $voucherService;
    public $tab = 'active',$isOpen = false,$voucherDetail;
    public $promotions = [];
    public $userPromotions = [];

    // Modal & form
    public $promoCode;
    public $showModal = false;
    public $message;

    public function boot(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function mount()
    {
        $this->loadUserPromotions();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->loadUserPromotions();
    }

    private function loadUserPromotions()
    {
        $today = date('Y-m-d H:i:s');
        // dd($today);
        \DB::enableQueryLog();
        if ($this->tab === 'active') {
            $this->userPromotions = Voucher::with('promotion.referralPromotion')->where('valid_till', '>=', $today)->where('customer_id', Auth::id())
            ->get();
            // ->ddRawSql();
        } else {
            $this->userPromotions = Voucher::with('promotion.referralPromotion')->where('valid_till', '<', $today)->where('customer_id', Auth::id())->get();
        }

        // dd(\DB::getQueryLog());

        // dd($this->userPromotions);
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function applyPromo()
    {
        $this->validate([
            'promoCode' => 'required|string|max:32',
        ]);

        $promotion = BasePromotion::with('marketingCampaign')->where('promo_code', $this->promoCode)->first();
        if (!$promotion) {
            $this->addError('promoCode',"Invalid promo code.");
            return;
        }

        try {
            $voucherService = $this->voucherService->createVoucher($promotion, Auth::user(), Voucher::TYPE_MARKETING);
            
            session()->flash('success', "Promo code applied successfully!");

            $this->promoCode = '';
            $this->showModal = false;

            $this->loadUserPromotions();
            
        } catch (\Exception $e) {
            // dd($e->getMessage());
            $this->addError('promoCode', $e->getMessage());
        }  
    }

    public function render()
    {
        return view('livewire.frontend.promo-wallet');
    }

    public function index()
    {
        return view('frontend.promo-wallet');
    }

    public function openModel($id)
    {
        $this->isOpen = true;
        $this->voucherDetail = Voucher::with('promotion.referralPromotion','promotion.marketingCampaign')->where('id',$id)->first();
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }
}


