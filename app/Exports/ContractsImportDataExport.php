<?php

namespace App\Exports;

use App\Models\Contract;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContractsImportDataExport implements WithMultipleSheets
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $contracts = $this->contracts();

        return [
            new ImportTemplateSheet('INSTRUCCIONES', $this->instructionsRows(), false),
            new ImportTemplateSheet('CLIENTES', $this->clientsRows($contracts), false),
            new ImportTemplateSheet('CONTRATOS', $this->contractRows($contracts), false),
            new ImportTemplateSheet('INTEGRANTES', $this->groupMemberRows($contracts), false),
            new ImportTemplateSheet('CUOTAS', $this->quotaRows($contracts), false),
            new ImportTemplateSheet('PAGOS', $this->paymentRows($contracts), false),
        ];
    }

    private function contracts()
    {
        $user = auth()->user();

        return Contract::active()
            ->with([
                'seller',
                'advisor',
                'district',
                'quotas.payments.payment_method',
            ])
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($this->filters['name'] ?? null, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    return $query
                        ->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%');
                });
            })
            ->when($this->filters['seller_id'] ?? null, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->when($this->filters['start_date'] ?? null, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($this->filters['end_date'] ?? null, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->when(($this->filters['insurance_filter'] ?? null) === 'zero', function ($query) {
                return $query->where('insurance_amount', 0);
            })
            ->latest('date')
            ->latest('id')
            ->get();
    }

    private function instructionsRows(): array
    {
        return [
            ['Exportacion editable de contratos'],
            [''],
            ['Este archivo usa el mismo formato de la plantilla de importacion.'],
            ['Puede descargarlo, editarlo y volverlo a importar luego de eliminar los contratos que desea reemplazar.'],
            ['Use el mismo codigo_contrato en CONTRATOS, INTEGRANTES, CUOTAS y PAGOS para enlazar todo.'],
            ['Fechas: AAAA-MM-DD o DD/MM/AAAA. Valores monetarios: sin simbolo o con S/.'],
            ['tipo_cliente: Personal o Grupo'],
            ['tipo_cuota: Semanal o Quincenal'],
            ['aprobado / pagada / pagado_contrato: SI o NO'],
        ];
    }

    private function clientsRows($contracts): array
    {
        $rows = [ImportTemplateExport::clientsRows()[0]];
        $seenDocuments = [];

        foreach ($contracts as $contract) {
            if ($contract->client_type !== 'Personal' || !$contract->document) {
                continue;
            }

            if (isset($seenDocuments[$contract->document])) {
                continue;
            }

            $rows[] = [
                $contract->document,
                $contract->name,
                $contract->phone,
                $contract->address,
                $contract->reference,
                $contract->home_type,
                $contract->civil_status,
                $contract->husband_name,
                $contract->husband_document,
                optional($contract->seller)->user,
            ];

            $seenDocuments[$contract->document] = true;
        }

        return $rows;
    }

    private function contractRows($contracts): array
    {
        $rows = [ImportTemplateExport::contractsRows()[0]];

        foreach ($contracts as $contract) {
            $rows[] = [
                $this->importCode($contract),
                $contract->client_type,
                $contract->document,
                $contract->name,
                $contract->group_name,
                $contract->number_pagare,
                optional($contract->seller)->user,
                $contract->advisor_id,
                optional($contract->advisor)->name,
                $contract->district_id,
                optional($contract->district)->name,
                $contract->phone,
                $contract->address,
                $contract->reference,
                $contract->home_type,
                $contract->business_line,
                $contract->business_address,
                $this->formatDateValue($contract->business_start_date),
                $contract->civil_status,
                $contract->husband_name,
                $contract->husband_document,
                $contract->requested_amount,
                $contract->quotas_number,
                $this->quotaType($contract),
                $contract->percentage,
                $contract->interest,
                $contract->insurance_amount,
                $contract->payable_amount,
                $contract->quota_amount,
                optional($contract->date)->format('Y-m-d'),
                $contract->approved ? 'SI' : 'NO',
                $contract->paid ? 'SI' : 'NO',
            ];
        }

        return $rows;
    }

    private function groupMemberRows($contracts): array
    {
        $rows = [ImportTemplateExport::groupMembersRows()[0]];

        foreach ($contracts as $contract) {
            if ($contract->client_type !== 'Grupo') {
                continue;
            }

            foreach ($this->decodePeople($contract->people) as $person) {
                $rows[] = [
                    $this->importCode($contract),
                    $person['document'] ?? '',
                    $person['name'] ?? '',
                    $person['address'] ?? '',
                ];
            }
        }

        return $rows;
    }

    private function quotaRows($contracts): array
    {
        $rows = [ImportTemplateExport::quotasRows()[0]];

        foreach ($contracts as $contract) {
            foreach ($contract->quotas->sortBy('number') as $quota) {
                $rows[] = [
                    $this->importCode($contract),
                    $quota->number,
                    optional($quota->date)->format('Y-m-d'),
                    $quota->amount,
                    $quota->debt,
                    $quota->paid ? 'SI' : 'NO',
                ];
            }
        }

        return $rows;
    }

    private function paymentRows($contracts): array
    {
        $rows = [ImportTemplateExport::paymentsRows()[0]];

        foreach ($contracts as $contract) {
            foreach ($contract->quotas->sortBy('number') as $quota) {
                foreach ($quota->payments->where('deleted', 0)->sortBy('date') as $payment) {
                    $rows[] = [
                        $this->importCode($contract),
                        $quota->number,
                        $payment->amount,
                        optional($payment->date)->format('Y-m-d'),
                        optional($payment->payment_method)->name,
                        $payment->due_days,
                    ];
                }
            }
        }

        return $rows;
    }

    private function importCode(Contract $contract): string
    {
        return $contract->import_code ?: 'CTR-' . $contract->id;
    }

    private function quotaType(Contract $contract): string
    {
        $map = [1 => 'Semanal', 2 => 'Quincenal', 4 => 'Mensual'];

        if (!is_null($contract->type_quota) && isset($map[(int) $contract->type_quota])) {
            return $map[(int) $contract->type_quota];
        }

        $firstTwo = $contract->quotas->sortBy('date')->take(2)->values();

        if ($firstTwo->count() > 1) {
            $diff = Carbon::parse($firstTwo[0]->date)->diffInDays(Carbon::parse($firstTwo[1]->date));

            if ($diff >= 25 && $diff <= 35) {
                return 'Mensual';
            }

            if ($diff >= 12 && $diff <= 16) {
                return 'Quincenal';
            }

            if ($diff >= 5 && $diff <= 9) {
                return 'Semanal';
            }
        }

        return 'Semanal';
    }

    private function decodePeople(?string $people): array
    {
        $decoded = json_decode($people ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function formatDateValue($value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
