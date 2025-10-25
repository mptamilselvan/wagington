<div class="min-h-screen bg-white relative">
<script>
    function copyReferralCode() {
        let copyText = document.getElementById("referralLink");
        // Select the text
        copyText.select();
        copyText.setSelectionRange(0, 99999); // for mobile

        // Copy to clipboard
        navigator.clipboard.writeText(copyText.value).then(() => {
            // Show confirmation
            let msg = document.getElementById("copyMessage");
            msg.classList.remove("hidden");
            setTimeout(() => msg.classList.add("hidden"), 2000);
        }).catch(err => {
            alert("Failed to copy: " + err);
        });
    }
</script>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 ">
        {{-- Header --}}
        <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8">
                <div class="flex items-start mb-3">
                    <a href="{{ route('home') }}" class="flex items-center mt-1 mr-3 text-gray-600 transition-colors hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">My Referrals</h1>
                        <p class="mt-2 text-gray-500">Invite your friends to earn discounts.</p>
                    </div>
                </div>
            
                {{-- Referral Code Section --}}
                <div class="bg-[#ECF5FF]  rounded-lg flex justify-between items-center">
                    <div class="p-6">
                        <h2 class="text-sm text-[#1B85F3]">Earn {{ ($promotion->referralpromotion?$promotion->referralpromotion->referrer_reward:'').($promotion->referralpromotion->discount_type == 'percentage'?'%':'$') }} With Each Referral</h2>
                        <p class="text-gray-600 text-sm my-5">
                            Spread the word about us and earn rewards! When your friend signs up using your unique referral code you'll both get [Discount/Reward]! 
                        </p>
                        {{-- <input type="text" value="{{ $myReferralCode }}" readonly
                            class="border p-2 rounded-l bg-white text-center w-32"> --}}
                        @component('components.forms.inputWithCopy', [
                            'id' => 'referralLink',
                            'onClickFn' => 'copyReferralCode()',
                            'value' => $myReferralCode,
                            'classInput' => 'h-[32px] md:h-[38px] truncate pr-10 w-[255px] text-[#374151] form-input text-[12px]
                                            md:text-[13px]',
                            'classSvg' => 'w-[16px] h-[16px] md:w-[24px] md:h-[24px]',
                            'readonly' => true
                        ])
                        @endcomponent
                        <p id="copyMessage" class="text-sm text-green-600 hidden mt-1">Copied!</p>

                    </div>
                    <div class="flex items-center pt-10">
                        @include('components.icons.referralimg')
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="mt-8">
                    <span class="font-bold">Referral History</span>
                    <ul class="flex border-b mt-4">
                        <li class="mr-6">
                            <button wire:click="switchTab('signed')"
                                    class="{{ $tab === 'signed' ? 'border-b-2 border-[#1B85F3] text-[#1B85F3]' : 'font-normal' }}">
                                Signed Up
                            </button>
                        </li>
                    </ul>

                    {{-- Signed Up List --}}
                    @if($tab === 'signed')
                        <div class="grid grid-cols-3 gap-4 mt-6">
                            @forelse($signedUp as $refUser)
                                
                                <div class="bg-white shadow p-4 rounded-lg">
                                    <div class="flex justify-between items-center gap-1">
                                        <div class="flex items-center">
                                            <img src="{{ $refUser->image ? env('DO_SPACES_URL').'/'.$refUser->image : asset('storage/user.jpeg') }}" alt="Preview" class="w-14 h-14 object-cover rounded-full">
                                            <div class="ml-5"> <!-- reduce margin here -->
                                                <p class="font-semibold">
                                                    {{ substr($refUser->first_name,0,3) }}*******
                                                </p>
                                                <p>******{{ substr($refUser->phone,-4) }}</p>
                                            </div>
                                        </div>
                                        @if($promotion)
                                            <div class="bg-yellow-200 manrope-600 h-[20px] text-[12px] text-yellow-800 flex items-center justify-center rounded-lg p-2 mb-10">{{ $promotion->getDaysLeft($refUser->phone_verified_at) }} days left</div>
                                        @endif
                                        
                                    </div>
                                    <p class="text-sm text-gray-500 mt-3">
                                        Signed up on {{ $refUser->created_at->format('jS M Y') }}
                                    </p>
                                    {{-- <span class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded mt-2 inline-block">
                                        {{ $promotion->getDaysLeft($refUser->phone_verified_at) }} days left
                                    </span> --}}
                                </div>
                            @empty
                                <p class="text-gray-500">No Referral signup yet.</p>
                            @endforelse

                            
                        </div>
                    @endif

                </div>
        </div>
    </div>
</div>
