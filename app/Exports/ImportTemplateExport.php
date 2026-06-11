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
            new ImportTemplateSheet('INTEGRANTES', self::groupMembersRows()),
            new ImportTemplateSheet('CUOTAS', self::quotasRows()),
            new ImportTemplateSheet('PAGOS', self::paymentsRows()),
        ];
    }

    public static function instructionsRows(): array
    {
        return [
            ['Guia de importacion de datos - Contratos historicos'],
            [''],
            ['Orden recomendado: 1) CLIENTES  2) CONTRATOS  3) INTEGRANTES  4) CUOTAS  5) PAGOS'],
            ['Use el mismo codigo_contrato en CONTRATOS, INTEGRANTES, CUOTAS y PAGOS para enlazar todo.'],
            ['Campos con "(Obligatorio)" no deben quedar vacios. Los demas pueden omitirse si el dato no existe.'],
            ['Fechas: AAAA-MM-DD o DD/MM/AAAA. Valores monetarios: sin simbolo o con S/.'],
            ['tipo_cliente: Personal o Grupo'],
            ['tipo_cuota: Semanal o Quincenal'],
            ['aprobado / pagada / pagado_contrato: SI o NO'],
            ['El campo asesor_usuario debe existir como usuario de la financiera.'],
            ['Si omite CUOTAS y el contrato esta aprobado, el sistema genera el cronograma automaticamente.'],
        ];
    }

    public static function clientsRows(): array
    {
        return [
            [
                'documento (Obligatorio)',
                'nombre_completo (Obligatorio)',
                'telefono (Opcional)',
                'direccion (Opcional)',
                'referencia (Opcional)',
                'tipo_vivienda (Opcional)',
                'estado_civil (Opcional)',
                'nombre_conyuge (Opcional)',
                'dni_conyuge (Opcional)',
                'asesor_usuario (Opcional)',
            ],
            [
                '12345678',
                'MARIA LOPEZ GARCIA',
                '987654321',
                'Av. Grau 123 - Piura',
                'Frente al parque',
                'Propia',
                'Casado',
                'JOSE LOPEZ',
                '87654321',
                'ADMINCRECE',
            ],
        ];
    }

    public static function contractsRows(): array
    {
        return [
            [
                'codigo_contrato (Obligatorio)',
                'tipo_cliente (Obligatorio)',
                'documento_cliente (Obligatorio para Personal)',
                'nombre_completo (Opcional)',
                'group_name (Obligatorio para Grupo)',
                'numero_pagare (Opcional)',
                'asesor_usuario (Obligatorio)',
                'advisor_id (Opcional)',
                'asesor_credito (Opcional)',
                'district_id (Opcional)',
                'distrito (Opcional)',
                'telefono (Opcional)',
                'direccion (Opcional)',
                'reference (Opcional)',
                'tipo_vivienda (Opcional)',
                'business_line (Opcional)',
                'business_address (Opcional)',
                'business_start_date (Opcional)',
                'civil_status (Opcional)',
                'husband_name (Opcional)',
                'husband_document (Opcional)',
                'monto_solicitado (Obligatorio)',
                'numero_cuotas (Obligatorio)',
                'tipo_cuota (Obligatorio)',
                'porcentaje_interes (Obligatorio)',
                'monto_interes (Opcional)',
                'monto_seguro (Opcional)',
                'monto_a_pagar (Opcional)',
                'monto_cuota (Opcional)',
                'fecha_prestamo (Obligatorio)',
                'aprobado (Opcional)',
                'pagado_contrato (Opcional)',
            ],
            [
                'CTR-001',
                'Personal',
                '12345678',
                'MARIA LOPEZ GARCIA',
                '',
                '',
                'ADMINCRECE',
                '',
                '',
                '',
                'PIURA',
                '987654321',
                'Av. Grau 123 - Piura',
                'Frente al parque',
                'Propia',
                '',
                '',
                '',
                'Casado',
                'JOSE LOPEZ',
                '87654321',
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

    public static function groupMembersRows(): array
    {
        return [
            [
                'codigo_contrato (Obligatorio)',
                'documento (Obligatorio)',
                'nombre_completo (Obligatorio)',
                'direccion (Opcional)',
            ],
            [
                'CTR-002',
                '45678901',
                'JUAN PEREZ',
                'Calle 1',
            ],
            [
                'CTR-002',
                '45678902',
                'ANA PEREZ',
                'Calle 2',
            ],
        ];
    }

    public static function quotasRows(): array
    {
        return [
            [
                'codigo_contrato (Obligatorio)',
                'numero_cuota (Obligatorio)',
                'fecha_vencimiento (Obligatorio)',
                'monto_cuota (Obligatorio)',
                'saldo_pendiente (Opcional)',
                'pagada (Opcional)',
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
                'codigo_contrato (Obligatorio)',
                'numero_cuota (Obligatorio)',
                'monto (Obligatorio)',
                'fecha_pago (Obligatorio)',
                'metodo_pago (Opcional)',
                'dias_mora (Opcional)',
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
