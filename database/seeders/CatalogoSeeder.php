<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoSeeder extends Seeder
{
    /**
     * Seed catalogos base necesarios para los datos de ejemplo.
     */
    public function run(): void
    {
        $this->seedSimpleCatalog('catalogo_tipos_inmueble', [
            'Casa',
            'Departamento',
            'Terreno',
            'Local comercial',
            'Oficina',
            'Galpon',
        ]);

        $this->seedSimpleCatalog('catalogo_zonas', [
            'Centro',
            'Norte',
            'Poniente',
            'Sur metropolitano',
        ]);

        $this->seedSimpleCatalog('catalogo_operaciones', [
            'Venta',
            'Renta',
        ]);

        $this->seedSimpleCatalog('catalogo_amc_estados', [
            'Si',
            'No',
        ]);

        $this->seedDocumentos();
        $this->seedEtapasYAcciones();

        $this->seedSimpleCatalog('catalogo_estados_colocacion', [
            'Abierta',
            'Negociacion',
            'Cerrada',
        ]);

        $this->seedSimpleCatalog('catalogo_tipos_tarea', [
            'Llamada',
            'Recorrido',
            'Seguimiento',
        ]);

        DB::table('catalogo_monedas')->insert([
            ['codigo' => 'USD', 'nombre' => 'Dolar estadounidense'],
            ['codigo' => 'MXN', 'nombre' => 'Peso mexicano'],
            ['codigo' => 'EUR', 'nombre' => 'Euro'],
        ]);
    }

    private function seedDocumentos(): void
    {
        $this->seedSimpleCatalog('catalogo_documentos', [
            'Escritura',
            'Identificacion oficial',
            'Comprobante de domicilio',
        ]);
    }

    private function seedEtapasYAcciones(): void
    {
        $this->seedSimpleCatalog('catalogo_etapas', [
            'Captacion',
            'Promocion',
            'Cierre',
        ]);

        $etapas = DB::table('catalogo_etapas')->pluck('id', 'nombre')->all();

        DB::table('catalogo_acciones')->insert([
            ['etapa_id' => $etapas['Captacion'], 'nombre' => 'Contacto inicial'],
            ['etapa_id' => $etapas['Captacion'], 'nombre' => 'Evaluacion de documentacion'],
            ['etapa_id' => $etapas['Promocion'], 'nombre' => 'Publicacion en portales'],
            ['etapa_id' => $etapas['Promocion'], 'nombre' => 'Visita programada'],
            ['etapa_id' => $etapas['Cierre'], 'nombre' => 'Presentacion de oferta'],
            ['etapa_id' => $etapas['Cierre'], 'nombre' => 'Firma de contrato'],
        ]);
    }

    private function seedSimpleCatalog(string $table, array $values): void
    {
        $rows = array_map(fn ($value) => ['nombre' => $value], $values);

        DB::table($table)->insert($rows);
    }
}
