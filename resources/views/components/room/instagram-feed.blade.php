<section class="rounded-2xl bg-neutral-900 text-white p-5 sm:p-7">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-semibold">Follow us on Instagram</h3>
            <p class="text-sm text-neutral-300 mt-1">Share photos of your pets on Instagram and win prizes!</p>
        </div>
        <a href="#" class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg bg-white/10 hover:bg-white/15 text-white text-sm">Follow</a>
    </div>

    <!-- Simple horizontal scroll (can be enhanced later) -->
    <div class="mt-5 overflow-x-auto">
        <div class="flex gap-3 min-w-max">
            @foreach(range(1,6) as $i)
                <div class="w-44 h-52 bg-neutral-800 rounded-xl overflow-hidden shrink-0">
                    <img src="/images/waginton-image-1.png" alt="Insta {{ $i }}" class="w-full h-full object-cover" />
                </div>
            @endforeach
        </div>
    </div>
</section>