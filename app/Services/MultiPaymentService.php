<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionDetail;
use App\Models\Quota;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MultiPaymentService
{
    public function register(array $data): PaymentTransaction
    {
        return DB::transaction(function () use ($data) {
            $quotaIds = collect($data['quota_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($quotaIds->isEmpty()) {
                throw new RuntimeException('Debe seleccionar al menos una cuota.');
            }

            $quotas = Quota::active()
                ->with('contract')
                ->whereIn('id', $quotaIds)
                ->lockForUpdate()
                ->orderBy('date')
                ->orderBy('number')
                ->get()
                ->values();

            if ($quotas->count() !== $quotaIds->count()) {
                throw new RuntimeException('Una o más cuotas seleccionadas no existen o ya no están disponibles.');
            }

            $contractIds = $quotas->pluck('contract_id')->unique()->values();
            if ($contractIds->count() !== 1) {
                throw new RuntimeException('Las cuotas seleccionadas deben pertenecer al mismo cliente/contrato.');
            }

            $contract = $quotas->first()->contract;
            if (!$contract) {
                throw new RuntimeException('No se pudo resolver el contrato asociado a las cuotas seleccionadas.');
            }

            $totalSelectedDebt = round((float) $quotas->sum('debt'), 2);
            $totalAmount = round((float) ($data['amount'] ?? 0), 2);

            if ($totalAmount <= 0) {
                throw new RuntimeException('El monto total recibido debe ser mayor a 0.');
            }

            if ($totalAmount > $totalSelectedDebt) {
                throw new RuntimeException('El monto total recibido no puede ser mayor que la suma de los saldos seleccionados.');
            }

            $voucherPath = $data['voucher_path'] ?? null;
            $paymentMethodId = (int) ($data['payment_method_id'] ?? 0);
            $paymentDate = Carbon::parse($data['date']);

            $transaction = PaymentTransaction::create([
                'contract_id' => $contract->id,
                'payment_method_id' => $paymentMethodId,
                'total_amount' => $totalAmount,
                'voucher_path' => $voucherPath,
                'date' => $paymentDate->format('Y-m-d'),
            ]);

            $remaining = $totalAmount;

            foreach ($quotas as $index => $quota) {
                if ($remaining <= 0) {
                    break;
                }

                $quotaBalanceBefore = round((float) $quota->debt, 2);
                if ($quotaBalanceBefore <= 0) {
                    continue;
                }

                $amountApplied = round(min($quotaBalanceBefore, $remaining), 2);
                $quotaBalanceAfter = round($quotaBalanceBefore - $amountApplied, 2);

                $diff = $paymentDate->diffInDays($quota->date, false);
                $dueDays = $diff < 0 ? abs($diff) : 0;

                $payment = Payment::create([
                    'quota_id' => $quota->id,
                    'payment_transaction_id' => $transaction->id,
                    'amount' => $amountApplied,
                    'payment_method_id' => $paymentMethodId,
                    'date' => $paymentDate->format('Y-m-d'),
                    'due_days' => $dueDays,
                    'image' => $voucherPath,
                    'people' => null,
                ]);

                PaymentTransactionDetail::create([
                    'payment_transaction_id' => $transaction->id,
                    'payment_id' => $payment->id,
                    'quota_id' => $quota->id,
                    'quota_balance_before' => $quotaBalanceBefore,
                    'amount_applied' => $amountApplied,
                    'quota_balance_after' => $quotaBalanceAfter,
                    'sequence' => $index + 1,
                ]);

                $quota->update([
                    'debt' => $quotaBalanceAfter,
                    'paid' => $quotaBalanceAfter <= 0 ? 1 : 0,
                ]);

                $remaining = round($remaining - $amountApplied, 2);
            }

            if ($remaining > 0.01) {
                throw new RuntimeException('No fue posible distribuir todo el monto ingresado sobre las cuotas seleccionadas.');
            }

            if ($contract->quotas()->where('paid', 0)->count() === 0) {
                $contract->update(['paid' => 1]);
            }

            return $transaction;
        });
    }
}
