<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionDetail;
use App\Models\Quota;
use Illuminate\Support\Facades\DB;

class ContractBulkDeletionService
{
    public function delete(array $contractIds, int $companyId): int
    {
        $contractIds = Contract::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('id', $contractIds)
            ->pluck('id')
            ->all();

        if (count($contractIds) === 0) {
            return 0;
        }

        DB::transaction(function () use ($contractIds, $companyId) {
            $quotaIds = Quota::withoutGlobalScopes()
                ->whereIn('contract_id', $contractIds)
                ->pluck('id')
                ->all();

            $paymentIds = count($quotaIds) > 0
                ? Payment::withoutGlobalScopes()
                    ->whereIn('quota_id', $quotaIds)
                    ->pluck('id')
                    ->all()
                : [];

            $transactionIds = PaymentTransaction::withoutGlobalScopes()
                ->whereIn('contract_id', $contractIds)
                ->pluck('id')
                ->all();

            $expenseIds = Expense::withoutGlobalScopes()
                ->whereIn('contract_id', $contractIds)
                ->pluck('id')
                ->all();

            if (count($transactionIds) > 0) {
                PaymentTransactionDetail::withoutGlobalScopes()
                    ->whereIn('payment_transaction_id', $transactionIds)
                    ->delete();
            }

            if (count($paymentIds) > 0) {
                PaymentTransactionDetail::withoutGlobalScopes()
                    ->whereIn('payment_id', $paymentIds)
                    ->delete();

                Payment::withoutGlobalScopes()
                    ->whereIn('id', $paymentIds)
                    ->delete();
            }

            if (count($quotaIds) > 0) {
                PaymentTransactionDetail::withoutGlobalScopes()
                    ->whereIn('quota_id', $quotaIds)
                    ->delete();

                Quota::withoutGlobalScopes()
                    ->whereIn('id', $quotaIds)
                    ->delete();
            }

            if (count($transactionIds) > 0) {
                PaymentTransaction::withoutGlobalScopes()
                    ->whereIn('id', $transactionIds)
                    ->delete();
            }

            if (count($expenseIds) > 0) {
                ExpensePayment::whereIn('expenses_id', $expenseIds)->delete();

                Expense::withoutGlobalScopes()
                    ->whereIn('id', $expenseIds)
                    ->delete();
            }

            Contract::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('id', $contractIds)
                ->delete();
        });

        return count($contractIds);
    }
}
