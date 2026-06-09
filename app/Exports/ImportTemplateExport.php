<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ImportTemplateSheet('INSTRUCCIONES', self::instructionsRows()),
            new ImportTemplateSheet('CLIENTES', self::clientsRows()),
            new ImportTemplateSheet('CONTRATOS', self::contractsRows()),
            new ImportTemplateSheet('CUOTAS', self::quotasRows()),
            new ImportTemplateSheet('PAGOS', self::paymentsRows()),
        ];
    }

    public static function instructionsRows(): array
    {
        return [
            ['Guía de importación de datos — Crece Conmigo / Xinergia'],
            [''],
            ['Orden recomendado: 1) CLIENTES (opcional)  2) CONTRATOS  3) CUOTAS  4) PAGOS'],
            ['Use codigo_contrato igual en CONTRATOS, CUOTAS y PAGOS para enlazar filas.'],
            ['tipo_cuota: Semanal | Quincenal'],
            ['metodo_pago: Efectivo | YAPE (debe existir en la financiera)'],
            ['asesor_usuario: login del asesor (ej. JUAN) registrado en esa financiera'],
            ['Fechas: AAAA-MM-DD o DD/MM/AAAA'],
            ['aprobado / pagada / pagado_contrato: SI o NO'],
            ['Si omite CUOTAS, el sistema genera el cronograma según el contrato.'],
            ['Importe solo en UNA financiera desde Superadmin → Importar datos.'],
        ];
    }

    public static function clientsRows(): array
    {
        return [
            [
                'documento',
                'nombre_completo',
                'telefono',
                'direccion',
                'referencia',
                'tipo_vivienda',
                'estado_civil',
                'nombre_conyuge',
                'dni_conyuge',
                'asesor_usuario',
            ],
            [
                '12345678',
                'MARIA LOPEZ GARCIA',
                '987654321',
                'Av. Grau 123 - Piura',
                'Frente al parque',
                'Propia',
                'Casado',
                'JUAN LOPEZ',
                '87654321',
                'ADMINCRECE',
            ],
        ];
    }

    public static function contractsRows(): array
    {
        return [
            [
                'codigo_contrato',
                'documento_cliente',
                'tipo_cliente',
                'numero_pagare',
                'asesor_usuario',
                'monto_solicitado',
                'numero_cuotas',
                'tipo_cuota',
                'porcentaje_interes',
                'monto_interes',
                'monto_seguro',
                'monto_a_pagar',
                'monto_cuota',
                'fecha_prestamo',
                'aprobado',
                'pagado_contrato',
            ],
            [
                'CTR-001',
                '12345678',
                'Personal',
                '',
                'ADMINCRECE',
                '1000',
                '12',
                'Semanal',
                '10',
                '100',
                '50',
                '1150',
                '95.83',
                '2026-01-15',
                'SI',
                'NO',
            ],
        ];
    }

    public static function quotasRows(): array
    {
        return [
            [
                'codigo_contrato',
                'numero_cuota',
                'fecha_vencimiento',
                'monto_cuota',
                'saldo_pendiente',
                'pagada',
            ],
            [
                'CTR-001',
                '1',
                '2026-01-22',
                '95.83',
                '0',
                'SI',
            ],
            [
                'CTR-001',
                '2',
                '2026-01-29',
                '95.83',
                '95.83',
                'NO',
            ],
        ];
    }

    public static function paymentsRows(): array
    {
        return [
            [
                'codigo_contrato',
                'numero_cuota',
                'monto',
                'fecha_pago',
                'metodo_pago',
                'dias_mora',
            ],
            [
                'CTR-001',
                '1',
                '95.83',
                '2026-01-22',
                'YAPE',
                '0',
            ],
        ];
    }
}
