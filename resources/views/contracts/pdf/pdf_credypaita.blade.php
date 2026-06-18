@php
    $company = $contract->company ?? (auth()->check() ? auth()->user()->company : null);
    $companyName = $company ? $company->name : 'CREDIPAITA S.A.C';
    $companyRuc = $company ? $company->ruc : '';
    $companyAddress = $company ? ($company->address ?: '') : '';
    $companyCity = $company ? ($company->city ?: 'Paita') : 'Paita';
    $companyRegistry = $company ? ($company->registry_info ?: 'Zona Registral N° I - Sede Paita / Oficina Registral Paita.') : 'Zona Registral N° I - Sede Paita / Oficina Registral Paita.';
    $documentMode = $documentMode ?? 'pdf';
    $companyLogoWidth = 210;
    $companyLogoPath = $company && $company->logo ? $company->logo : 'assets/images/logo.png';
    $companyLogoPath = ltrim(str_replace('\\', '/', $companyLogoPath), '/');
    $companyLogoSrc = null;
    $companyLogoFullPath = public_path($companyLogoPath);

    if ($companyLogoPath && is_file($companyLogoFullPath)) {
        $extension = strtolower(pathinfo($companyLogoFullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'image/png';
        $companyLogoSrc = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($companyLogoFullPath));
    }

    $contractDate = \Carbon\Carbon::parse($contract->date);
    $lastPaymentDate = $contract->last_payment_date ? \Carbon\Carbon::parse($contract->last_payment_date) : null;
    $firstQuota = $contract->quotas && $contract->quotas->count() ? $contract->quotas->sortBy('number')->first() : null;
    $firstPaymentDate = $firstQuota ? \Carbon\Carbon::parse($firstQuota->date) : $contractDate;
    $quotaType = strtoupper($contract->quota_type ?: 'SEMANAL');
    $requestedAmountWords = trim(($contract->amount_in_words ?: '') . ' ' . str_pad((string) round(((float) $contract->requested_amount - floor((float) $contract->requested_amount)) * 100), 2, '0', STR_PAD_LEFT) . '/100');
    $pagareNumber = str_pad((string) ($company->number_pagare ?? $contract->id), 6, '0', STR_PAD_LEFT);
    $totalDays = (int) ($contract->total_days ?? 0);
    $debtors = [];

    if ($contract->client_type === 'Grupo' && $contract->people) {
        $people = json_decode($contract->people) ?: [];
        foreach ($people as $person) {
            $debtors[] = [
                'name' => $person->name ?? '',
                'document' => $person->document ?? '',
                'address' => $person->address ?? '',
            ];
        }
    } else {
        $debtors[] = [
            'name' => $contract->name,
            'document' => $contract->document,
            'address' => $contract->address,
        ];
    }

    $mainDebtor = $debtors[0] ?? ['name' => '', 'document' => '', 'address' => ''];
    $debtorNames = collect($debtors)->pluck('name')->filter()->implode(', ');
    $debtorDocuments = collect($debtors)->pluck('document')->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato CREDYPAITA {{ $contract->id }}</title>
    <style>
        @page { margin: 32px 38px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.25;
        }
        h1, h2, h3 {
            text-align: center;
            margin: 0;
            font-weight: bold;
        }
        h1 { font-size: 14pt; margin: 6px 0 14px; text-decoration: underline; }
        h2 { font-size: 11pt; margin: 12px 0 7px; text-decoration: underline; }
        h3 { font-size: 10.5pt; margin: 10px 0 6px; }
        p { margin: 5px 0; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin: 7px 0; }
        th, td { border: 1px solid #000; padding: 4px 5px; vertical-align: middle; }
        th { font-weight: bold; text-align: center; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .upper { text-transform: uppercase; }
        .no-border, .no-border td, .no-border th { border: none; }
        .logo { text-align: center; margin-bottom: 8px; }
        .logo img { height: auto; }
        .signature-space { height: 58px; }
        .page-break { page-break-after: always; }
        .small { font-size: 9pt; }
    </style>
</head>
<body>
    @if($companyLogoSrc)
        <div class="logo">
            <img src="{{ $companyLogoSrc }}" alt="Logo {{ $companyName }}" width="{{ $companyLogoWidth }}" style="width: {{ $companyLogoWidth }}px; height: auto;">
        </div>
    @endif

    <h1>CONTRATO DE PRESTAMO PERSONAL</h1>

    <p>Conste por el presente documento el contrato de mutuo que celebran de una parte:</p>
    <p>
        <span class="bold upper">{{ $companyName }}</span>
        @if($companyRuc) con RUC N° {{ $companyRuc }}, @endif
        con domicilio en {{ $companyAddress ?: $companyCity }}, a quien en adelante se le denominará
        <span class="bold">{{ $companyName }}</span>; y de la otra parte
        <span class="bold upper">{{ $debtorNames }}</span>, identificado(s) con DNI N° {{ $debtorDocuments }},
        con domicilio en <span class="upper">{{ collect($debtors)->pluck('address')->filter()->implode(', ') }}</span>,
        a quien(es) se denominará <span class="bold">EL MUTUATARIO / CLIENTE</span>.
    </p>

    <h2>ANTECEDENTES:</h2>
    <p>
        <span class="bold">PRIMERA. -</span> {{ $companyName }} es una persona jurídica dedicada a la concesión de créditos
        sin intermediación financiera. Ante ello, mediante el presente contrato, a solicitud de
        EL MUTUATARIO / CLIENTE, se otorga préstamo dinerario previa evaluación crediticia y cumpliendo con los requisitos requeridos.
    </p>

    <h2>OBJETO DEL CONTRATO:</h2>
    <p>
        <span class="bold">SEGUNDA. -</span> Por el presente contrato, {{ $companyName }} se obliga a entregar en calidad de
        préstamo, en favor de EL MUTUATARIO / CLIENTE, la suma de
        <span class="bold">S/ {{ number_format($contract->requested_amount, 2) }} ({{ $requestedAmountWords }} nuevos soles)</span>.
    </p>
    <p>
        EL MUTUATARIO / CLIENTE se obliga a devolver a {{ $companyName }} la suma de dinero estipulada conforme al calendario
        de pagos y condiciones pactadas en el presente contrato.
    </p>

    <h2>OBLIGACIONES DE LAS PARTES:</h2>
    <p>
        <span class="bold">TERCERA. -</span> {{ $companyName }} se obliga a entregar la suma de dinero objeto de la prestación
        a su cargo en el momento de la firma de este documento, sin más constancia que las firmas de las partes puestas en
        el comprobante de entrega en anexo 1-A al presente contrato.
    </p>
    <p>
        <span class="bold">CUARTA. -</span> EL MUTUATARIO / CLIENTE se obliga a devolver el íntegro del dinero objeto del mutuo,
        en un plazo de {{ $totalDays }} días como máximo, a partir de la firma del presente contrato. Su pago será de manera
        {{ $quotaType }}, generando un interés de {{ number_format($contract->percentage, 2) }}% mensual. Por tanto,
        EL MUTUATARIO / CLIENTE devolverá la suma de
        <span class="bold">S/ {{ number_format($contract->payable_amount, 2) }}</span>
        @if((float) $contract->insurance_amount > 0)
            (incluye seguro por S/ {{ number_format($contract->insurance_amount, 2) }})
        @endif
        como consecuencia del cumplimiento de la obligación pactada; asimismo, se obliga a cumplir con el pago en las oportunidades
        indicadas en el calendario de pagos, anexo 1-B del presente contrato.
    </p>
    <p>
        <span class="bold">QUINTA. -</span> EL MUTUATARIO / CLIENTE autoriza que {{ $companyName }} pueda efectuar el cobro de la
        obligación contraída y de cada cuota descrita en el cronograma de pagos mediante personal debidamente acreditado, en el
        domicilio declarado en el presente contrato.
    </p>
    <p>
        En caso de pagos mediante Yape, Plin, transferencia bancaria o interbancaria, el pago será comunicado a {{ $companyName }},
        siendo la empresa quien confirma el pago y comunica el descargo de la cuota.
    </p>
    <p>
        <span class="bold">SEXTA. -</span> EL MUTUATARIO / CLIENTE se obliga a cumplir fielmente con el cronograma de pagos. En caso
        de incumplimiento en el pago de una de las armadas, quedarán vencidas todas las demás, quedando {{ $companyName }} facultado
        para exigir el pago íntegro de la suma mutuada, más intereses y gastos que correspondan.
    </p>
    <p>
        <span class="bold">SÉPTIMA. -</span> EL MUTUATARIO / CLIENTE autoriza el pago de S/ {{ number_format($contract->insurance_amount ?? 0, 2) }}
        adicional, al íntegro de la obligación descrita en la cláusula cuarta, por concepto de seguro.
    </p>
    <p>
        <span class="bold">OCTAVA. -</span> En respaldo de la obligación asumida frente a {{ $companyName }}, EL MUTUATARIO / CLIENTE
        suscribe un pagaré N° {{ $pagareNumber }} emitido en forma incompleta.
    </p>
    <p>
        <span class="bold">NOVENA. -</span> Ambas partes convienen que el presente contrato se celebra a título oneroso; en consecuencia,
        EL MUTUATARIO / CLIENTE está obligado al pago de intereses compensatorios en favor de {{ $companyName }}.
    </p>
    <p>
        <span class="bold">DÉCIMO. -</span> En lo no previsto por las partes, ambas se someten a las normas del Código Civil y demás
        normas del sistema jurídico aplicables.
    </p>
    <p>
        Para efectos de cualquier controversia, las partes se someten a la competencia territorial de los jueces y tribunales de
        {{ $companyCity }}.
    </p>
    <p>
        En señal de conformidad las partes suscriben este documento en la ciudad de {{ $companyCity }},
        el día {{ $contractDate->format('d/m/Y') }}.
    </p>

    <table class="no-border" style="margin-top: 34px;">
        <tr>
            <td class="center" style="width: 50%;">
                <div class="signature-space"></div>
                <div>______________________________</div>
                <div class="bold upper">{{ $companyName }}</div>
                <div class="small">{{ $companyRegistry }}</div>
            </td>
            <td class="center" style="width: 50%;">
                <div class="signature-space"></div>
                <div>______________________________</div>
                <div class="bold">ASESOR</div>
                <div class="upper">{{ optional($contract->seller)->name }}</div>
            </td>
        </tr>
    </table>

    <h3>FIRMA DE MUTUATARIO(S) / INTEGRANTES</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 38%;">APELLIDOS Y NOMBRES</th>
                <th style="width: 16%;">DNI</th>
                <th style="width: 23%;">FIRMA</th>
                <th style="width: 23%;">HUELLA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $debtor)
                <tr>
                    <td class="upper">{{ $debtor['name'] }}</td>
                    <td class="center">{{ $debtor['document'] }}</td>
                    <td style="height: 48px;"></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>ANEXO 1 - A. - COMPROBANTE DE ENTREGA.</h2>
    <p><span class="bold">NUMERO DE CONTRATO:</span> N° {{ $contract->id }}</p>
    <p><span class="bold">RECIBI DE:</span> {{ $companyName }}</p>
    <p><span class="bold">LA SUMA DE:</span> {{ $requestedAmountWords }} nuevos soles</p>
    <p><span class="bold">POR CONCEPTO:</span> DE PRESTAMO DE DINERO PARA DEVOLVER CLIENTE: {{ $debtorNames }} DNI N°: {{ $debtorDocuments }}</p>
    <p><span class="bold">RUTA / ASESOR:</span> {{ optional($contract->seller)->name }}</p>

    <table>
        <thead>
            <tr>
                <th>NÚMERO DE PRÉSTAMO</th>
                <th>APELLIDOS Y NOMBRE DEL CLIENTE</th>
                <th>DNI</th>
                <th>DIRECCIÓN</th>
                <th>MONTO</th>
                <th>FIRMA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $debtor)
                <tr>
                    <td class="center">{{ $contract->id }}</td>
                    <td class="upper">{{ $debtor['name'] }}</td>
                    <td class="center">{{ $debtor['document'] }}</td>
                    <td class="upper">{{ $debtor['address'] }}</td>
                    <td class="right">S/ {{ number_format($contract->requested_amount, 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>ANEXO 1 - B. - CRONOGRAMA DE PAGOS.</h2>
    <p>
        EL MUTUATARIO / CLIENTE, bajo el presente documento deja constancia que toma conocimiento del cronograma de pagos
        que se indica en el contrato de mutuo dinerario.
    </p>

    <h3>Datos del Préstamo:</h3>
    <table>
        <tr>
            <td><span class="bold">Monto:</span> S/ {{ number_format($contract->requested_amount, 2) }}</td>
            <td><span class="bold">Tasa:</span> {{ number_format($contract->percentage, 2) }}% mensual</td>
        </tr>
        <tr>
            <td><span class="bold">Cuotas:</span> {{ $contract->quotas_number }}</td>
            <td><span class="bold">Monto a Devolver:</span> S/ {{ number_format($contract->payable_amount, 2) }}</td>
        </tr>
        <tr>
            <td><span class="bold">Periodicidad:</span> {{ $quotaType }}</td>
            <td><span class="bold">Monto seguro:</span> S/ {{ number_format($contract->insurance_amount ?? 0, 2) }}</td>
        </tr>
    </table>

    <h3>CRONOGRAMA</h3>
    <table>
        <thead>
            <tr>
                <th>CUOTA</th>
                <th>FECHA</th>
                <th>TOTAL A PAGAR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contract->quotas->sortBy('number') as $quota)
                <tr>
                    <td class="center">{{ $quota->number }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($quota->date)->format('d/m/Y') }}</td>
                    <td class="right">S/ {{ number_format($quota->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="no-border" style="margin-top: 30px;">
        <tr>
            <td class="center">
                <div class="signature-space"></div>
                <div>______________________________</div>
                <div class="upper">{{ $companyName }}</div>
            </td>
            <td class="center">
                <div class="signature-space"></div>
                <div>______________________________</div>
                <div class="upper">{{ optional($contract->seller)->name }}</div>
                <div>ASESOR</div>
            </td>
        </tr>
    </table>

    <h3>FIRMA DE MUTUATARIO(S) / INTEGRANTES</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 38%;">APELLIDOS Y NOMBRES</th>
                <th style="width: 16%;">DNI</th>
                <th style="width: 23%;">FIRMA</th>
                <th style="width: 23%;">HUELLA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $debtor)
                <tr>
                    <td class="upper">{{ $debtor['name'] }}</td>
                    <td class="center">{{ $debtor['document'] }}</td>
                    <td style="height: 48px;"></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <h1>PAGARE</h1>
    <p><span class="bold">PAGARE N°:</span> {{ $pagareNumber }}</p>
    <p>
        <span class="bold">FECHA DE EMISIÓN:</span> {{ $contractDate->format('d/m/Y') }}
        <span style="float: right;"><span class="bold">IMPORTE DEUDOR S/</span> {{ number_format($contract->payable_amount, 2) }}</span>
    </p>
    <p><span class="bold">IMPORTE ORIGINAL:</span> S/ {{ number_format($contract->requested_amount, 2) }}</p>
    <p><span class="bold">FECHA DE VENCIMIENTO:</span> {{ $lastPaymentDate ? $lastPaymentDate->format('d/m/Y') : '' }}</p>
    <p><span class="bold">YO:</span> <span class="upper">{{ $debtorNames }}</span></p>
    <p>
        identificado(s) con D.N.I. N° {{ $debtorDocuments }}, reconozco/reconocemos que adeudo y pagaré/pagaremos
        solidaria e incondicionalmente, en la fecha de vencimiento consignada en el presente pagaré, a la orden de
        {{ $companyName }} la cantidad de S/ {{ number_format($contract->payable_amount, 2) }} soles.
    </p>

    <h2>CLAUSULAS ESPECIALES.</h2>
    <p>Este pagaré debe ser pagado en la misma moneda que expresa el título valor.</p>
    <p>A su vencimiento, podrá ser prorrogado por {{ $companyName }} o por su tenedor por el plazo que este señale.</p>
    <p>El importe de este pagaré generará un interés compensatorio que se pacta en la tasa de {{ number_format($contract->percentage, 2) }}% mensual.</p>
    <p>En caso de no ser cancelado el importe de una o más cuotas, el deudor incurre en mora automáticamente por el solo hecho del vencimiento.</p>
    <p>Este título no está sujeto a protesto por falta de pago, salvo lo dispuesto por la Ley de Títulos Valores.</p>
    <p>El presente pagaré está sujeto a la Ley Peruana de Títulos Valores vigente a la fecha de suscripción.</p>

    <p class="right">{{ $companyCity }}, {{ $contractDate->format('d/m/Y') }}.</p>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Apellidos y nombres</th>
                <th>DNI N°</th>
                <th>Dirección</th>
                <th>Firma</th>
                <th>Huella</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $index => $debtor)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="upper">{{ $debtor['name'] }}</td>
                    <td class="center">{{ $debtor['document'] }}</td>
                    <td class="upper">{{ $debtor['address'] }}</td>
                    <td style="height: 42px;"></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
