<main class="py-10 lg:pl-72 bg-gray-50">
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $title }}</span>
        </div>
    </div>

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-full p-4 bg-white">
            <div class="mt-[28px] table-wrapper overflow-visible">
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="th">Promotion</th>
                            <th class="th">Customer</th>
                            <th class="th">Voucher Type</th>
                            <th class="th">voucher code</th>
                            <th class="th">Discount</th>
                            <th class="th">Status</th>
                            <th class="th">Valid till</th>
                            <th class="th">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $voucher)
                            <tr class="border-t">
                                <td class="td">{{ $voucher->promotion?$voucher->promotion->name:'-' }}</td>
                                <td class="td">{{ $voucher->customer?$voucher->customer->name:'-' }}</td>
                                <td class="td">{{ $voucher->voucher_type }}</td>
                                <td class="td">{{ $voucher->voucher_code }}</td>
                                <td class="td">{{ $voucher->discount_type === 'percentage' 
                                    ? $voucher->discount_value . '%' 
                                    : $voucher->discount_value.' SGD' }}</td>
                                <td class="td">{{ $voucher->status }}</td>
                                <td class="td">{{ ($voucher->valid_till)->format('d F Y') }}</td>
                                <td class="td">{{ ($voucher->created_at)->format('d F Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $data->links()}}
            </div>
        </div>
    </div>
</main>