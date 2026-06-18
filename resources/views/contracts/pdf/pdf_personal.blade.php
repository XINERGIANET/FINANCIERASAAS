@php
    $company = $contract->company ?? (auth()->check() ? auth()->user()->company : null);
    $companyName = $company ? $company->name : 'INVERSIONES SV CAPITAL';
    $companyRuc = $company ? $company->ruc : '';
    $companyCity = $company ? ($company->city ?: 'Piura') : 'Piura';
    $documentMode = $documentMode ?? 'pdf';
    $companyLogoWidth = $documentMode === 'word' ? 135 : 245;
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
    $creditorName = $companyName;
    $sellerName = optional($contract->seller)->name ?: 'ASESOR';
    $quotaType = strtoupper($contract->quota_type ?: 'SEMANAL');
    $amountWords = $contract->amount_in_words ?: '';
    $contractDate = \Carbon\Carbon::parse($contract->date);
    $lastPaymentDate = $contract->last_payment_date ? \Carbon\Carbon::parse($contract->last_payment_date) : null;
    $debtors = [];

    if ($contract->client_type === 'Grupo' && $contract->people) {
        $people = json_decode($contract->people) ?: [];
        foreach ($people as $person) {
            $debtors[] = [
                'name' => $person->name ?? '',
                'document' => $person->document ?? '',
                'address' => $person->address ?? '',
                'civil_status' => '',
            ];
        }
    } else {
        $debtors[] = [
            'name' => $contract->name,
            'document' => $contract->document,
            'address' => $contract->address,
            'civil_status' => $contract->civil_status,
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato {{ $contract->id }}</title>
    <style>
        @page {
            margin: 34px 36px;
        }
        body {
            font-family: "Times New Roman", serif;
            font-size: 10.5pt;
            color: #000;
            line-height: 1.22;
        }
        h1, h2, h3 {
            text-align: center;
            margin: 0;
            font-weight: bold;
        }
        h1 {
            font-size: 15pt;
            margin-bottom: 2px;
        }
        h2 {
            font-size: 12pt;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        h3 {
            font-size: 11pt;
            margin-top: 9px;
            margin-bottom: 5px;
        }
        p {
            margin: 4px 0;
            text-align: justify;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .upper {
            text-transform: uppercase;
        }
        .section-title {
            text-align: center;
            font-weight: bold;
            margin: 10px 0 6px;
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: middle;
        }
        th {
            text-align: center;
            font-weight: bold;
        }
        .no-border,
        .no-border td,
        .no-border th {
            border: none;
        }
        .small {
            font-size: 9pt;
        }
        .tiny {
            font-size: 8pt;
        }
        .signature-space {
            height: 54px;
        }
        .page-break {
            page-break-after: always;
        }
        .box {
            border: 1px solid #000;
            padding: 5px;
        }
        .nowrap {
            white-space: nowrap;
        }
        .document-logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .document-logo img {
            height: auto;
        }
    </style>
</head>
<body>
    @if($companyLogoSrc)
        <div class="document-logo">
            <img src="{{ $companyLogoSrc }}" alt="Logo {{ $companyName }}" width="{{ $companyLogoWidth }}" style="width: {{ $companyLogoWidth }}px; height: auto;">
        </div>
    @endif

    <h1>CONTRATO</h1>
    <h2>POR ACUERDO MUTUO</h2>

    <p>
        Conste por el presente documento que celebran de una parte
        <span class="bold upper">{{ $creditorName }}</span>
        @if($companyRuc)
            , identificado con RUC Nro. {{ $companyRuc }},
        @endif
        quien opera bajo el nombre comercial de <span class="bold upper">{{ $companyName }}</span>,
        quien actúa en condición de <span class="bold">EL ACREEDOR</span>, y de la otra parte
        <span class="bold">EL/LA/LOS DEUDOR(ES)</span>, persona(s) naturales que consignan sus datos personales
        en el siguiente orden:
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">N°</th>
                <th>APELLIDOS Y NOMBRES</th>
                <th style="width: 16%;">DNI</th>
                <th style="width: 28%;">DIRECCIÓN</th>
                <th style="width: 15%;">ESTADO CIVIL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $index => $debtor)
                <tr>
                    <td class="center">{{ $index + 1 }}.1.</td>
                    <td class="upper">{{ $debtor['name'] }}</td>
                    <td class="center">{{ $debtor['document'] }}</td>
                    <td class="upper">{{ $debtor['address'] }}</td>
                    <td class="center upper">{{ $debtor['civil_status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">ANTECEDENTES</div>
    <p>
        <span class="bold">CLÁUSULA PRIMERA:</span> EL ACREEDOR es una persona natural o jurídica que ha logrado
        de manera lícita, honrada e íntegra ahorrar un capital dinerario, el mismo que busca trabajar honestamente
        a fin de brindar préstamo económico a personas naturales que, en casos de emergencia o necesidad particular,
        lo soliciten libremente y sin coacción alguna.
    </p>
    <p>
        <span class="bold">CLÁUSULA SEGUNDA:</span> EL/LA/LOS DEUDOR(ES) requiere(n), con carácter de urgencia y
        para los fines pertinentes a su ocupación, necesidad laboral, familiar, de salud o de carácter particular,
        un monto dinerario en calidad de préstamo.
    </p>
    <p>
        <span class="bold">CLÁUSULA TERCERA:</span> EL ACREEDOR y EL/LA/LOS DEUDOR(ES) tienen pleno conocimiento
        que la operación se celebra de buena fe, sin intimidación, engaño, dolo o cualquier acto prohibido por ley.
    </p>

    <div class="section-title">FORMALIDADES DEL CONTRATO</div>
    <p>
        <span class="bold">CLÁUSULA SEXTA: OBJETO DEL CONTRATO.</span> Por el presente contrato, las partes acuerdan
        el préstamo del siguiente monto dinerario, el cual será entregado al prestatario, recibiendo de manera íntegra,
        completa y sin ninguna restricción o disminución la suma que se precisa:
    </p>

    <table>
        <tr>
            <th style="width: 16%;">CRÉDITO N°</th>
            <th style="width: 22%;">MONTO DEL PRÉSTAMO</th>
            <th>FIRMA Y HUELLA DEL ACREEDOR</th>
            <th>FIRMA Y HUELLA DEL DEUDOR(ES)</th>
        </tr>
        <tr>
            <td class="center">{{ $contract->id }}</td>
            <td>
                <div><span class="bold">EN NÚMERO:</span> S/. {{ number_format($contract->requested_amount, 2) }}</div>
                <div><span class="bold">EN LETRAS:</span> {{ $amountWords }}</div>
            </td>
            <td class="signature-space"></td>
            <td class="signature-space"></td>
        </tr>
    </table>

    <p>
        <span class="bold">CLÁUSULA SÉTIMA: MODALIDAD DE PAGO.</span> EL ACREEDOR y EL/LA/LOS DEUDOR(ES) reconocen
        que existe la siguiente modalidad de pago y tasa de interés correspondiente según modalidad elegida:
    </p>

    <table>
        <thead>
            <tr>
                <th>PAGO</th>
                <th>TASA COMPENSATORIA</th>
                <th>TASA MORATORIA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">SEMANAL</td>
                <td class="center">{{ $contract->percentage }}% por un mes de 4 semanas</td>
                <td class="center">S/ 0.50 por cada día de mora por S/ 100.00</td>
            </tr>
            <tr>
                <td class="center">QUINCENAL</td>
                <td class="center">{{ $contract->percentage }}% por 1 mes de 2 quincenas</td>
                <td class="center">S/ 0.70 por cada día de mora por S/ 100.00</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr>
            <th>NOMBRE DEL CLIENTE</th>
            <th>FIRMA Y HUELLA DEL ACREEDOR</th>
            <th>FIRMA Y HUELLA DEL DEUDOR(ES)</th>
        </tr>
        <tr>
            <td class="upper">{{ $debtors[0]['name'] ?? '' }}</td>
            <td class="signature-space center">
                <div>{{ $sellerName }}</div>
                <div class="tiny">ASESOR</div>
            </td>
            <td class="signature-space"></td>
        </tr>
    </table>

    <p>
        <span class="bold">CLÁUSULA OCTAVA: DE LOS PLAZOS ESTABLECIDOS.</span> Las partes tienen presente que la
        duración del presente contrato no debe extenderse más allá de la modalidad de pago y cronograma respectivo.
        De no cumplirse, EL/LA/LOS DEUDOR(ES) se somete(n) a las acciones legales correspondientes.
    </p>
    <p>
        <span class="bold">CLÁUSULA NOVENA:</span> En armonía con los artículos 1351, 1354, 1359, 1361 y 1362 del
        Código Civil peruano, las partes declaran conocer los términos del presente contrato y dejan constancia que
        no ha mediado dolo, coacción, engaño, deslealtad o cualquier acción destinada a invalidarlo.
    </p>
    <p>
        <span class="bold">CLÁUSULA DÉCIMA:</span> Queda expresamente convenido que EL ACREEDOR comprenderá cualquier
        hecho fortuito o fuerza mayor que retrase el cumplimiento de pago, siempre que EL/LA/LOS DEUDOR(ES) comunique(n)
        oportunamente la situación y asuma(n) la mora correspondiente.
    </p>

    <div class="section-title">OBLIGACIONES Y FACULTADES DE LAS PARTES</div>
    <p><span class="bold">CLÁUSULA DÉCIMO PRIMERA:</span> Corresponde a EL ACREEDOR:</p>
    <p class="small">
        11.1. Entregar la totalidad de la suma pactada en calidad de préstamo.
        11.2. No hostigar, acosar o intimidar para el objeto de cobranza.
        11.3. Adjuntar cronograma de pago con fechas según modalidad aceptada.
        11.4. Entregar cartilla de pagos para firma y sello.
        11.5. Entregar juego original del presente contrato.
    </p>
    <p><span class="bold">Corresponderá a EL/LA/LOS DEUDOR(ES):</span></p>
    <p class="small">
        11.7. Entregar copia de su DNI. 11.8. Realizar la misma firma que corresponde a su DNI.
        11.9. No consignar dirección errónea o falsa.
        11.10. Comunicar cambio de domicilio con 3 días de anticipación.
        11.11. Pagar oportunamente.
        11.12. Señalar un número de celular activo.
    </p>

    <div class="section-title">RESOLUCIÓN DEL CONTRATO</div>
    <p>
        <span class="bold">CLÁUSULA DÉCIMO SEGUNDA:</span> El incumplimiento de una o más cláusulas constituirá
        causal de resolución del contrato, produciéndose de pleno derecho cuando EL ACREEDOR comunique el incumplimiento
        al domicilio real de EL/LA/LOS DEUDOR(ES).
    </p>

    <div class="section-title">APLICACIÓN SUPLETORIA DE LA LEY</div>
    <p>
        <span class="bold">CLÁUSULA DÉCIMO TERCERA:</span> En todo lo no previsto por las partes, ambas se someten
        a la Ley de Conciliación Extrajudicial, el Código Civil y demás normas aplicables.
    </p>

    <div class="section-title">COMPETENCIA JURISDICCIONAL</div>
    <p>
        <span class="bold">CLÁUSULA DÉCIMO CUARTA:</span> Las controversias se priorizarán mediante conciliación
        extrajudicial. De corresponder, ambas partes se someten a la jurisdicción del Poder Judicial de la ciudad de
        {{ $companyCity }}.
    </p>

    <div class="section-title">CLÁUSULAS EXCEPCIONALES</div>
    <p>
        <span class="bold">CLÁUSULA ESPECIAL:</span> En caso EL/LA/LOS DEUDOR(ES) fuese(n) persona(s) iletrada(s),
        deberá(n) comparecer con testigo a ruego. Las partes declaran responsabilidad absoluta sobre la licitud del dinero
        y reconocen que gastos administrativos, impresiones, copias o legalizaciones serán asumidos por EL/LA/LOS
        DEUDOR(ES), cuando correspondan.
    </p>
    <p>
        <span class="bold">CLÁUSULA SUJETA A LOS ANEXOS:</span> EL ACREEDOR adjunta al presente contrato:
        Anexo 1: Cronograma de pagos. Anexo 2: Cartilla para firma y sello de pagos realizados.
    </p>

    <p>
        En señal de conformidad con todos los acuerdos pactados en el presente contrato, las partes suscriben este
        documento estableciendo firma, huella digital, nombres completos y número de DNI.
    </p>

    <table class="no-border" style="margin-top: 24px;">
        <tr>
            <td class="center" style="width: 50%;">
                _______________________________<br>
                <span class="bold">PRESTAMISTA</span><br>
                {{ $creditorName }}<br>
                @if($companyRuc) RUC: {{ $companyRuc }} @endif
            </td>
            <td class="center" style="width: 50%;">
                _______________________________<br>
                <span class="bold">ASESOR</span><br>
                {{ $sellerName }}
            </td>
        </tr>
    </table>

    <h3>FIRMA DE DEUDOR(ES) / INTEGRANTES</h3>
    <table>
        <tr>
            <th style="width: 38%;">APELLIDOS Y NOMBRES</th>
            <th style="width: 16%;">DNI</th>
            <th style="width: 23%;">FIRMA</th>
            <th style="width: 23%;">HUELLA</th>
        </tr>
        @foreach($debtors as $debtor)
            <tr>
                <td class="upper">{{ $debtor['name'] }}</td>
                <td class="center">{{ $debtor['document'] }}</td>
                <td style="height: 48px;"></td>
                <td></td>
            </tr>
        @endforeach
    </table>

    <div class="page-break"></div>

    <h2>MODELO</h2>
    <h2>Anexo 2: Cartilla para firma y sello de pagos realizados.</h2>
    <h1>CARTILLA</h1>
    <table>
        <tr>
            <th style="width: 24%;">MODALIDAD DE PAGO</th>
            <td class="center bold">{{ $quotaType }}</td>
            <th>FIRMA Y HUELLA DEL ACREEDOR</th>
            <th>FIRMA Y HUELLA DEL DEUDOR(ES)</th>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 13%;">CUOTA</th>
                <th style="width: 22%;">FECHA</th>
                <th style="width: 22%;">MONTO</th>
                <th>FIRMA / SELLO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contract->quotas as $quota)
                <tr>
                    <td class="center">{{ $quotaType === 'SEMANAL' ? 'SEMANA' : 'CUOTA' }} {{ $quota->number }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($quota->date)->format('d/m/Y') }}</td>
                    <td class="center">S/. {{ number_format($quota->amount, 2) }}</td>
                    <td style="height: 28px;"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>ANEXO 1: CRONOGRAMA DE PAGOS</h2>
    <table>
        <tr>
            <th>CRÉDITO N°</th>
            <td>{{ $contract->id }}</td>
            <th>FECHA CONTRATO</th>
            <td>{{ $contractDate->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>CLIENTE</th>
            <td colspan="3" class="upper">{{ $debtors[0]['name'] ?? '' }}</td>
        </tr>
        <tr>
            <th>MONTO PRESTADO</th>
            <td>S/. {{ number_format($contract->requested_amount, 2) }}</td>
            <th>MONTO A DEVOLVER</th>
            <td>S/. {{ number_format($contract->payable_amount, 2) }}</td>
        </tr>
        <tr>
            <th>TASA</th>
            <td>{{ $contract->percentage }}%</td>
            <th>CUOTAS</th>
            <td>{{ $contract->quotas_number }}</td>
        </tr>
        <tr>
            <th>SEGURO</th>
            <td>S/. {{ number_format($contract->insurance_amount ?? 0, 2) }}</td>
            <th>VENCIMIENTO</th>
            <td>{{ $lastPaymentDate ? $lastPaymentDate->format('d/m/Y') : '' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>CUOTA</th>
                <th>FECHA DE PAGO</th>
                <th>TOTAL A PAGAR</th>
                <th>SALDO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contract->quotas as $quota)
                <tr>
                    <td class="center">{{ $quota->number }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($quota->date)->format('d/m/Y') }}</td>
                    <td class="center">S/. {{ number_format($quota->amount, 2) }}</td>
                    <td class="center">S/. {{ number_format($quota->debt, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <h1>PAGARÉ</h1>
    <p><span class="bold">PAGARÉ N°:</span> {{ str_pad($contract->number_pagare ?: $contract->id, 6, '0', STR_PAD_LEFT) }}</p>
    <p>
        <span class="bold">FECHA DE EMISIÓN:</span> {{ $contractDate->format('d/m/Y') }}
        <span style="float: right;"><span class="bold">IMPORTE DEUDOR S/</span> {{ number_format($contract->payable_amount, 2) }}</span>
    </p>
    <p><span class="bold">IMPORTE ORIGINAL:</span> S/. {{ number_format($contract->requested_amount, 2) }}</p>
    <p><span class="bold">FECHA DE VENCIMIENTO:</span> {{ $lastPaymentDate ? $lastPaymentDate->format('d/m/Y') : '' }}</p>
    <p>
        <span class="bold">YO/NOSOTROS:</span> {{ collect($debtors)->pluck('name')->implode(', ') }},
        identificado(s) con DNI N° {{ collect($debtors)->pluck('document')->implode(', ') }}.
    </p>
    <p>
        Reconozco/reconocemos que adeudo/adeudamos y pagaré/pagaremos solidariamente e incondicionalmente, en la fecha
        de vencimiento consignada en el presente pagaré, a la orden de <span class="bold">{{ $creditorName }}</span>,
        la cantidad de S/. {{ number_format($contract->payable_amount, 2) }} soles, sin lugar a reclamo alguno.
    </p>

    <h3>CLÁUSULAS ESPECIALES</h3>
    <p>1. Este pagaré debe ser pagado en la misma moneda que expresa el título valor.</p>
    <p>2. El importe del presente pagaré genera interés compensatorio pactado en la tasa de {{ $contract->percentage }}%.</p>
    <p>3. En caso de incumplimiento, los obligados incurren en mora automática desde el vencimiento hasta su total cancelación.</p>
    <p>4. Este título no está sujeto a protesto por falta de pago, salvo disposición legal aplicable.</p>

    <table style="margin-top: 18px;">
        <thead>
            <tr>
                <th>N° APELLIDOS Y NOMBRES</th>
                <th>DNI N°</th>
                <th>DIRECCIÓN</th>
                <th>FIRMA</th>
                <th>HUELLA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $index => $debtor)
                <tr>
                    <td style="height: 48px;">{{ $index + 1 }}. {{ $debtor['name'] }}</td>
                    <td class="center">{{ $debtor['document'] }}</td>
                    <td>{{ $debtor['address'] }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
