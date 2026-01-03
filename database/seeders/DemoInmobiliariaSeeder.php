<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoInmobiliariaSeeder extends Seeder
{
    /**
     * Seed de clientes e inmuebles para pruebas manuales.
     */
    public function run(): void
    {
        $now = now();

        $clienteIds = $this->seedClientes($now);

        $catalogos = [
            'tipos' => $this->idsFor('catalogo_tipos_inmueble', [
                'Casa',
                'Departamento',
                'Local comercial',
            ]),
            'zonas' => $this->idsFor('catalogo_zonas', [
                'Centro',
                'Sur metropolitano',
                'Poniente',
            ]),
            'operaciones' => $this->idsFor('catalogo_operaciones', [
                'Venta',
                'Renta',
            ]),
            'estados' => $this->idsFor('catalogo_amc_estados', [
                'Activa',
                'En analisis',
                'Prospecto',
            ]),
            'monedas' => $this->idsFor('catalogo_monedas', [
                'MXN',
                'USD',
            ], 'codigo'),
            'documentos' => $this->idsFor('catalogo_documentos', [
                'Escritura',
                'Identificacion oficial',
            ]),
        ];

        $inmuebleIds = $this->seedInmuebles($now, $clienteIds, $catalogos);

        $this->seedInmuebleFotos($now, $inmuebleIds);
        $this->seedInmuebleDocumentos($now, $inmuebleIds, $catalogos['documentos']);
    }

    private function seedClientes($timestamp): array
    {
        $clientes = [
            [
                'ref' => 'grupo-loma',
                'nombre' => 'Grupo Loma',
                'telefono' => '555-1000',
                'email' => 'contacto@grupoloma.mx',
            ],
            [
                'ref' => 'familia-diaz',
                'nombre' => 'Familia Diaz',
                'telefono' => '555-2000',
                'email' => 'familia.diaz@example.com',
            ],
            [
                'ref' => 'inversiones-rubi',
                'nombre' => 'Inversiones Rubi',
                'telefono' => '555-3000',
                'email' => 'hola@inversionesrubi.mx',
            ],
        ];

        $ids = [];

        foreach ($clientes as $cliente) {
            $ids[$cliente['ref']] = DB::table('clientes')->insertGetId([
                'nombre' => $cliente['nombre'],
                'telefono' => $cliente['telefono'],
                'email' => $cliente['email'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return $ids;
    }

    private function seedInmuebles($timestamp, array $clienteIds, array $catalogos): array
    {
        $inmuebles = [
            [
                'ref' => 'departamento-centro',
                'cliente_ref' => 'grupo-loma',
                'direccion' => 'Av Reforma 123, Centro',
                'descripcion' => 'Departamento remodelado con terraza y vista abierta al skyline.',
                'tipo' => 'Departamento',
                'zona' => 'Centro',
                'operacion' => 'Venta',
                'estado' => 'Activa',
                'valor' => 9500000,
                'moneda' => 'MXN',
            ],
            [
                'ref' => 'casa-sur',
                'cliente_ref' => 'familia-diaz',
                'direccion' => 'Calle Abedules 50, Sur metropolitano',
                'descripcion' => 'Casa familiar de dos niveles con jardin trasero.',
                'tipo' => 'Casa',
                'zona' => 'Sur metropolitano',
                'operacion' => 'Renta',
                'estado' => 'Prospecto',
                'valor' => 28000,
                'moneda' => 'MXN',
            ],
            [
                'ref' => 'local-poniente',
                'cliente_ref' => 'inversiones-rubi',
                'direccion' => 'Circuito Zapata 742, Poniente',
                'descripcion' => 'Local comercial con frente amplio ideal para concepto de comida rapida.',
                'tipo' => 'Local comercial',
                'zona' => 'Poniente',
                'operacion' => 'Venta',
                'estado' => 'En analisis',
                'valor' => 415000,
                'moneda' => 'USD',
            ],
        ];

        $ids = [];

        foreach ($inmuebles as $inmueble) {
            $ids[$inmueble['ref']] = DB::table('inmuebles')->insertGetId([
                'cliente_id' => $clienteIds[$inmueble['cliente_ref']],
                'direccion' => $inmueble['direccion'],
                'descripcion' => $inmueble['descripcion'],
                'tipo_id' => $catalogos['tipos'][$inmueble['tipo']],
                'zona_id' => $catalogos['zonas'][$inmueble['zona']],
                'operacion_id' => $catalogos['operaciones'][$inmueble['operacion']],
                'amc_estado_id' => $catalogos['estados'][$inmueble['estado']],
                'valor_estimado' => $inmueble['valor'],
                'moneda_id' => $catalogos['monedas'][$inmueble['moneda']],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return $ids;
    }

    private function seedInmuebleFotos($timestamp, array $inmuebleIds): void
    {
        $fotos = [
            [
                'inmueble_ref' => 'departamento-centro',
                'url' => 'https://picsum.photos/id/1018/640/480',
                'descripcion' => 'Fachada con balcones.',
            ],
            [
                'inmueble_ref' => 'departamento-centro',
                'url' => 'https://picsum.photos/id/1015/640/480',
                'descripcion' => 'Sala principal con ventanal.',
            ],
            [
                'inmueble_ref' => 'casa-sur',
                'url' => 'https://picsum.photos/id/1022/640/480',
                'descripcion' => 'Jardin trasero con area social.',
            ],
            [
                'inmueble_ref' => 'local-poniente',
                'url' => 'https://picsum.photos/id/1035/640/480',
                'descripcion' => 'Vista interior del local.',
            ],
        ];

        foreach ($fotos as $foto) {
            DB::table('inmuebles_fotos')->insert([
                'inmueble_id' => $inmuebleIds[$foto['inmueble_ref']],
                'url' => $foto['url'],
                'descripcion' => $foto['descripcion'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    private function seedInmuebleDocumentos($timestamp, array $inmuebleIds, array $documentos): void
    {
        $items = [
            [
                'inmueble_ref' => 'departamento-centro',
                'tipo' => 'Escritura',
                'url' => 'https://example.com/docs/departamento-centro-escritura.pdf',
                'descripcion' => 'Escritura simple actualizada.',
            ],
            [
                'inmueble_ref' => 'casa-sur',
                'tipo' => 'Identificacion oficial',
                'url' => 'https://example.com/docs/casa-sur-identificacion.pdf',
                'descripcion' => 'Identificacion del propietario.',
            ],
        ];

        foreach ($items as $item) {
            DB::table('inmuebles_documentos')->insert([
                'inmueble_id' => $inmuebleIds[$item['inmueble_ref']],
                'tipo_documento_id' => $documentos[$item['tipo']],
                'url' => $item['url'],
                'descripcion' => $item['descripcion'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    private function idsFor(string $table, array $values, string $column = 'nombre'): array
    {
        $records = DB::table($table)
            ->whereIn($column, $values)
            ->pluck('id', $column)
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = array_diff($values, array_keys($records));

        if (! empty($missing)) {
            throw new \RuntimeException('Faltan valores en ' . $table . ': ' . implode(', ', $missing));
        }

        return $records;
    }
}
