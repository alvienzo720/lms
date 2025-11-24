<div class="space-y-4">
    {{-- Next Payment Info --}}
    @if($nextPayment)
    <div class="rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 p-4">
        <h3 class="text-lg font-semibold text-warning-900 dark:text-warning-100 mb-2">
            <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 inline" />
            Next Payment Due
        </h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-600 dark:text-gray-400">Payment Date:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $nextPayment['payment_date']->format('M d, Y') }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Amount Due:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($nextPayment['payment_amount']) }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Balance After Payment:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($nextPayment['balance_after']) }}
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Loan Summary --}}
    <div class="rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 p-4">
        <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100 mb-3">
            <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 inline" />
            Loan Summary
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-600 dark:text-gray-400">Principal Amount:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($summary['principal_amount']) }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Interest Rate:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $summary['interest_rate'] }}%
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Total Interest:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($summary['total_interest']) }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Original Total:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($summary['original_total_repayment']) }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Total Paid:</span>
                <p class="font-semibold text-success-600 dark:text-success-400">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($summary['total_paid']) }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Current Balance:</span>
                <p class="font-semibold text-danger-600 dark:text-danger-400">
                    {{ \App\Helpers\CurrencyHelper::formatMoney($summary['current_balance']) }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Duration:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $summary['duration'] }} {{ $summary['interest_cycle'] }}
                </p>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Payments Made:</span>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $summary['payments_made'] }} / {{ $summary['duration'] }}
                </p>
            </div>
        </div>
    </div>

    {{-- Repayment Schedule Table --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Payment Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Payment Amount</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Principal</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Interest</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Balance After</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                    @foreach($schedule as $payment)
                    <tr class="transition-colors {{ $payment['is_paid'] ? 'bg-success-50 dark:bg-success-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                            {{ $payment['installment_number'] }}
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                            {{ $payment['payment_date']->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">
                            {{ \App\Helpers\CurrencyHelper::formatMoney($payment['payment_amount']) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">
                            {{ \App\Helpers\CurrencyHelper::formatMoney($payment['principal_portion']) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">
                            {{ \App\Helpers\CurrencyHelper::formatMoney($payment['interest_portion']) }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">
                            {{ \App\Helpers\CurrencyHelper::formatMoney($payment['balance_after']) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($payment['status'] === 'Paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="w-3 h-3 mr-1" />
                                    Paid
                                </span>
                            @elseif($payment['status'] === 'Overdue')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200">
                                    <x-filament::icon icon="heroicon-o-exclamation-circle" class="w-3 h-3 mr-1" />
                                    Overdue
                                </span>
                            @elseif($payment['status'] === 'Due Today')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">
                                    <x-filament::icon icon="heroicon-o-clock" class="w-3 h-3 mr-1" />
                                    Due Today
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200">
                                    <x-filament::icon icon="heroicon-o-calendar" class="w-3 h-3 mr-1" />
                                    Upcoming
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
