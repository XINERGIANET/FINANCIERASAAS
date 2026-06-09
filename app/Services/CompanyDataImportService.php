<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Quota;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CompanyDataImportService
{
    private $companyId;
    private $company;
    private $clients = [];
    private $contractMap = [];
    private $errors = [];
    private $stats = [
        'clientes' => 0,
        'contratos' => 0,
        'cuotas' => 0,
        'pagos' => 0,
    ];

    public function import(int $companyId, string $filePath): array
    {
        $this->companyId = $companyId;
        $this->company = Company::findOrFail($companyId);
        $this->errors = [];
        $this->stats = ['clientes' => 0, 'contratos' => 0, 'cuotas' => 0, 'pagos' => 0];
        $this->clients = [];
        $this->contractMap = [];

        $spreadsheet = IOFactory::load($filePath);
        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheets[strtoupper(trim($worksheet->getTitle()))] = $worksheet->toArray(null, true, true, true);
        }

        DB::beginTransaction();

        try {
            if (isset($sheets['CLIENTES'])) {
                $this->loadClients($this->rowsFromSheet($sheets['CLIENTES']));
            }

            if (!isset($sheets['CONTRATOS'])) {
                throw new Exception('La hoja CONTRATOS es obligatoria.');
            }

            $this->importContracts($this->rowsFromSheet($sheets['CONTRATOS']));

            if (isset($sheets['CUOTAS'])) {
                $this->importQuotas($this->rowsFromSheet($sheets['CUOTAS']));
            }

            $this->generateMissingQuotas();

            if (isset($sheets['PAGOS'])) {
                $this->importPayments($this->rowsFromSheet($sheets['PAGOS']));
            }

            if (count($this->errors) > 0) {
                DB::rollBack();

                return [
                    'success' => false,
                    'errors' => $this->errors,
                    'stats' => $this->stats,
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'errors' => [],
                'stats' => $this->stats,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => array_merge($this->errors, [$e->getMessage()]),
                'stats' => $this->stats,
            ];
        }
    }

    private function rowsFromSheet(array $sheetRows): array
    {
        if (count($sheetRows) < 2) {
            return [];
        }

        $headerRow = array_shift($sheetRows);
        $headers = $this->normalizeHeaders($headerRow);
        $rows = [];

        foreach ($sheetRows as $line => $cells) {
            $row = [];
            $colIndex = 0;

            foreach ($headerRow as $col => $headerCell) {
                $key = $headers[$colIndex] ?? null;
                if ($key) {
                    $row[$key] = isset($cells[$col]) ? trim((string) $cells[$col]) : '';
                }
                $colIndex++;
            }

            if ($this->rowHasData($row)) {
                $row['_line'] = $line + 2;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function normalizeHeaders(array $headerRow): array
    {
        $headers = [];

        foreach ($headerRow as $cell) {
            $key = strtolower(trim((string) $cell));
            $key = str_replace([' ', '-'], '_', $key);
            $headers[] = $key ?: null;
        }

        return $headers;
    }

    private function rowHasData(array $row): bool
    {
        foreach ($row as $key => $value) {
            if ($key === '_line') {
                continue;
            }
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function loadClients(array $rows): void
    {
        foreach ($rows as $row) {
            $document = $this->onlyDigits($row['documento'] ?? '');
            if ($document === '') {
                $this->errors[] = 'CLIENTES fila ' . $row['_line'] . ': documento vacío.';
                continue;
            }

            $this->clients[$document] = $row;
            $this->stats['clientes']++;
        }
    }

    private function importContracts(array $rows): void
    {
        foreach ($rows as $row) {
            $line = $row['_line'];
            $importCode = strtoupper(trim($row['codigo_contrato'] ?? ''));

            if ($importCode === '') {
                $this->errors[] = 'CONTRATOS fila ' . $line . ': codigo_contrato es obligatorio.';
                continue;
            }

            if (Contract::withoutGlobalScopes()
                ->where('company_id', $this->companyId)
                ->where('import_code', $importCode)
                ->exists()) {
                $this->errors[] = 'CONTRATOS fila ' . $line . ': codigo_contrato "' . $importCode . '" ya existe.';
                continue;
            }

            $document = $this->onlyDigits($row['documento_cliente'] ?? '');
            $client = $this->clients[$document] ?? [];

            $seller = $this->resolveSeller($row['asesor_usuario'] ?? ($client['asesor_usuario'] ?? ''), $line, 'CONTRATOS');
            if (!$seller) {
                continue;
            }

            $typeQuota = $this->parseTypeQuota($row['tipo_cuota'] ?? 'Semanal', $line);
            if (!$typeQuota) {
                continue;
            }

            $loanDate = $this->parseDate($row['fecha_prestamo'] ?? '', $line, 'CONTRATOS');
            if (!$loanDate) {
                continue;
            }

            $requested = $this->toFloat($row['monto_solicitado'] ?? '0');
            $quotasNumber = (int) ceil($this->toFloat($row['numero_cuotas'] ?? '0'));
            $interest = $this->toFloat($row['monto_interes'] ?? '0');
            $insurance = $this->toFloat($row['monto_seguro'] ?? '0');
            $payable = $this->toFloat($row['monto_a_pagar'] ?? '0');
            $quotaAmount = $this->toFloat($row['monto_cuota'] ?? '0');
            $percentage = $this->toFloat($row['porcentaje_interes'] ?? '0');

            if ($requested <= 0 || $quotasNumber <= 0) {
                $this->errors[] = 'CONTRATOS fila ' . $line . ': monto_solicitado y numero_cuotas deben ser mayores a 0.';
                continue;
            }

            if ($payable <= 0) {
                $payable = $requested + $interest + $insurance;
            }

            if ($quotaAmount <= 0 && $quotasNumber > 0) {
                $quotaAmount = round($payable / $quotasNumber, 2);
            }

            $pagare = trim($row['numero_pagare'] ?? '');
            if ($pagare === '') {
                $this->company->number_pagare = (int) $this->company->number_pagare + 1;
                $this->company->save();
                $pagare = $this->company->number_pagare;
            }

            $clientType = ucfirst(strtolower(trim($row['tipo_cliente'] ?? 'Personal')));
            if (!in_array($clientType, ['Personal', 'Grupo'], true)) {
                $clientType = 'Personal';
            }

            $name = strtoupper(trim($row['nombre_completo'] ?? ($client['nombre_completo'] ?? '')));
            if ($name === '' && $document !== '') {
                $name = 'CLIENTE ' . $document;
            }

            $contract = Contract::withoutGlobalScopes()->create([
                'company_id' => $this->companyId,
                'import_code' => $importCode,
                'number_pagare' => $pagare,
                'client_type' => $clientType,
                'document' => $document,
                'name' => $name,
                'phone' => $row['telefono'] ?? ($client['telefono'] ?? ''),
                'address' => $row['direccion'] ?? ($client['direccion'] ?? ''),
                'reference' => $row['referencia'] ?? ($client['referencia'] ?? ''),
                'home_type' => $row['tipo_vivienda'] ?? ($client['tipo_vivienda'] ?? ''),
                'civil_status' => $row['estado_civil'] ?? ($client['estado_civil'] ?? ''),
                'husband_name' => $row['nombre_conyuge'] ?? ($client['nombre_conyuge'] ?? ''),
                'husband_document' => $this->onlyDigits($row['dni_conyuge'] ?? ($client['dni_conyuge'] ?? '')),
                'seller_id' => $seller->id,
                'requested_amount' => $requested,
                'months_number' => $this->monthsFromQuotas($quotasNumber, $typeQuota),
                'quotas_number' => $quotasNumber,
                'percentage' => $percentage,
                'interest' => $interest,
                'insurance_amount' => $insurance,
                'payable_amount' => $payable,
                'quota_amount' => $quotaAmount,
                'date' => $loanDate->format('Y-m-d'),
                'first_payment_date' => $loanDate->format('Y-m-d'),
                'last_payment_date' => $loanDate->format('Y-m-d'),
                'type_quota' => $typeQuota,
                'approved' => $this->toBool($row['aprobado'] ?? 'SI') ? 1 : 0,
                'paid' => $this->toBool($row['pagado_contrato'] ?? 'NO') ? 1 : 0,
                'deleted' => 0,
            ]);

            $this->contractMap[$importCode] = $contract->id;
            $this->stats['contratos']++;
        }
    }

    private function importQuotas(array $rows): void
    {
        foreach ($rows as $row) {
            $line = $row['_line'];
            $importCode = strtoupper(trim($row['codigo_contrato'] ?? ''));
            $contractId = $this->contractMap[$importCode] ?? null;

            if (!$contractId) {
                $this->errors[] = 'CUOTAS fila ' . $line . ': codigo_contrato "' . $importCode . '" no encontrado en esta importación.';
                continue;
            }

            $number = (int) $this->toFloat($row['numero_cuota'] ?? '0');
            $amount = $this->toFloat($row['monto_cuota'] ?? '0');
            $debt = array_key_exists('saldo_pendiente', $row) && $row['saldo_pendiente'] !== ''
                ? $this->toFloat($row['saldo_pendiente'])
                : $amount;
            $dueDate = $this->parseDate($row['fecha_vencimiento'] ?? '', $line, 'CUOTAS');

            if (!$dueDate || $number <= 0) {
                continue;
            }

            $paid = $this->toBool($row['pagada'] ?? ($debt <= 0 ? 'SI' : 'NO')) ? 1 : 0;
            if ($debt <= 0) {
                $paid = 1;
                $debt = 0;
            }

            Quota::updateOrCreate(
                [
                    'contract_id' => $contractId,
                    'number' => $number,
                ],
                [
                    'amount' => $amount,
                    'debt' => $debt,
                    'date' => $dueDate->format('Y-m-d'),
                    'paid' => $paid,
                ]
            );

            $this->stats['cuotas']++;
        }

        $this->refreshContractDates();
    }

    private function generateMissingQuotas(): void
    {
        foreach ($this->contractMap as $contractId) {
            $contract = Contract::withoutGlobalScopes()->find($contractId);
            if (!$contract || $contract->quotas()->count() > 0 || !$contract->approved) {
                continue;
            }

            $this->createQuotasForContract($contract);
            $this->stats['cuotas'] += $contract->quotas_number;
        }
    }

    private function createQuotasForContract(Contract $contract): void
    {
        $quotasRounded = (int) $contract->quotas_number;
        $typeQuota = (int) $contract->type_quota;
        $payableAmount = (float) $contract->payable_amount;
        $quotaAmountStandard = (float) $contract->quota_amount;
        $totalFirst = $quotaAmountStandard * ($quotasRounded - 1);
        $lastQuota = round($payableAmount - $totalFirst, 2);
        $date = Carbon::parse($contract->date);

        for ($i = 1; $i <= $quotasRounded; $i++) {
            if ($typeQuota === 1) {
                $quotaDate = $date->copy()->addWeeks($i);
            } elseif ($typeQuota === 2) {
                $quotaDate = $date->copy()->addDays($i * 15);
            } else {
                $quotaDate = $date->copy()->addWeeks($i);
            }

            $amount = ($i === $quotasRounded) ? $lastQuota : $quotaAmountStandard;

            Quota::create([
                'contract_id' => $contract->id,
                'number' => $i,
                'amount' => $amount,
                'debt' => $amount,
                'date' => $quotaDate->format('Y-m-d'),
                'paid' => 0,
            ]);
        }

        $first = $contract->quotas()->orderBy('number')->first();
        $last = $contract->quotas()->orderByDesc('number')->first();

        if ($first && $last) {
            $contract->update([
                'first_payment_date' => $first->date,
                'last_payment_date' => $last->date,
            ]);
        }
    }

    private function importPayments(array $rows): void
    {
        usort($rows, function ($a, $b) {
            $da = $this->parseDate($a['fecha_pago'] ?? '', 0, 'PAGOS');
            $db = $this->parseDate($b['fecha_pago'] ?? '', 0, 'PAGOS');

            if (!$da || !$db) {
                return 0;
            }

            return $da <=> $db;
        });

        foreach ($rows as $row) {
            $line = $row['_line'];
            $importCode = strtoupper(trim($row['codigo_contrato'] ?? ''));
            $contractId = $this->contractMap[$importCode] ?? null;

            if (!$contractId) {
                $this->errors[] = 'PAGOS fila ' . $line . ': codigo_contrato "' . $importCode . '" no encontrado.';
                continue;
            }

            $number = (int) $this->toFloat($row['numero_cuota'] ?? '0');
            $quota = Quota::where('contract_id', $contractId)->where('number', $number)->first();

            if (!$quota) {
                $this->errors[] = 'PAGOS fila ' . $line . ': cuota ' . $number . ' no existe.';
                continue;
            }

            $amount = $this->toFloat($row['monto'] ?? '0');
            $paymentDate = $this->parseDate($row['fecha_pago'] ?? '', $line, 'PAGOS');

            if ($amount <= 0 || !$paymentDate) {
                continue;
            }

            $method = $this->resolvePaymentMethod($row['metodo_pago'] ?? 'Efectivo', $line);
            if (!$method) {
                continue;
            }

            $dueDays = $row['dias_mora'] !== '' ? (int) $this->toFloat($row['dias_mora']) : null;
            if ($dueDays === null) {
                $diff = $paymentDate->diffInDays(Carbon::parse($quota->date), false);
                $dueDays = $diff < 0 ? abs($diff) : 0;
            }

            Payment::create([
                'quota_id' => $quota->id,
                'amount' => $amount,
                'payment_method_id' => $method->id,
                'date' => $paymentDate->format('Y-m-d'),
                'due_days' => $dueDays,
                'deleted' => 0,
            ]);

            $newDebt = max(0, round((float) $quota->debt - $amount, 2));
            $paid = $newDebt <= 0 ? 1 : 0;

            $quota->update([
                'debt' => $newDebt,
                'paid' => $paid,
            ]);

            $this->stats['pagos']++;
        }

        foreach ($this->contractMap as $contractId) {
            $contract = Contract::withoutGlobalScopes()->find($contractId);
            if (!$contract) {
                continue;
            }

            $pending = Quota::where('contract_id', $contractId)->where('paid', 0)->count();
            $contract->update(['paid' => $pending === 0 ? 1 : 0]);
        }
    }

    private function refreshContractDates(): void
    {
        foreach ($this->contractMap as $contractId) {
            $contract = Contract::withoutGlobalScopes()->find($contractId);
            if (!$contract) {
                continue;
            }

            $first = $contract->quotas()->orderBy('number')->first();
            $last = $contract->quotas()->orderByDesc('number')->first();

            if ($first && $last) {
                $contract->update([
                    'first_payment_date' => $first->date,
                    'last_payment_date' => $last->date,
                ]);
            }
        }
    }

    private function resolveSeller(string $username, int $line, string $sheet)
    {
        $username = trim($username);
        if ($username === '') {
            $this->errors[] = $sheet . ' fila ' . $line . ': asesor_usuario es obligatorio.';

            return null;
        }

        $seller = User::withoutGlobalScopes()
            ->where('company_id', $this->companyId)
            ->where('user', $username)
            ->where('deleted', 0)
            ->whereIn('role', ['seller', 'admin', 'operations', 'credit'])
            ->first();

        if (!$seller) {
            $this->errors[] = $sheet . ' fila ' . $line . ': asesor "' . $username . '" no existe en esta financiera.';

            return null;
        }

        return $seller;
    }

    private function resolvePaymentMethod(string $name, int $line)
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'Efectivo';
        }

        $method = PaymentMethod::withoutGlobalScopes()
            ->where('company_id', $this->companyId)
            ->where('name', $name)
            ->first();

        if (!$method) {
            $this->errors[] = 'PAGOS fila ' . $line . ': método de pago "' . $name . '" no existe. Use Efectivo o YAPE.';

            return null;
        }

        return $method;
    }

    private function parseTypeQuota(string $value, int $line)
    {
        $value = strtolower(trim($value));
        $map = [
            '1' => 1,
            'semanal' => 1,
            '2' => 2,
            'quincenal' => 2,
            'catorcenal' => 2,
        ];

        if (!isset($map[$value])) {
            $this->errors[] = 'CONTRATOS fila ' . $line . ': tipo_cuota inválido. Use Semanal o Quincenal.';

            return null;
        }

        return $map[$value];
    }

    private function monthsFromQuotas(int $quotas, int $typeQuota): float
    {
        $perMonth = [1 => 4, 2 => 2, 4 => 1][$typeQuota] ?? 4;

        return round($quotas / $perMonth, 2);
    }

    private function parseDate(string $value, int $line, string $sheet)
    {
        $value = trim($value);
        if ($value === '') {
            if ($line > 0) {
                $this->errors[] = $sheet . ' fila ' . $line . ': fecha inválida o vacía.';
            }

            return null;
        }

        try {
            if (preg_match('/^\d+(\.\d+)?$/', $value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
            }

            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
                return Carbon::createFromFormat('d/m/Y', sprintf('%02d/%02d/%04d', $m[1], $m[2], $m[3]));
            }

            return Carbon::parse($value);
        } catch (Exception $e) {
            if ($line > 0) {
                $this->errors[] = $sheet . ' fila ' . $line . ': fecha "' . $value . '" no reconocida.';
            }

            return null;
        }
    }

    private function toBool(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['1', 'si', 'sí', 's', 'yes', 'true'], true);
    }

    private function toFloat(string $value): float
    {
        $value = str_replace([' ', 'S/', 's/'], '', trim($value));
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }
}
