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
            'Captación',
            'Colocación',
            'Visita',
            'Pasar Información',
            'Promoción',
            'Cierre',
            'Inmuebles Captados',
        ]);

        $etapas = DB::table('catalogo_etapas')->pluck('id', 'nombre')->all();

        DB::table('catalogo_acciones')->insert([
            // Captación
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Contacto inicial'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Llamar'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Gestionar Reunión'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Visitar el inmueble'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Toma de fotografía'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Pedir la documentación'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Hacer el AMC'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Elaboración de contrato'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Firma de contrato'],
            ['etapa_id' => $etapas['Captación'], 'nombre' => 'Subir al sistema'],

            // Colocación
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Buscar inmueble'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Preguntar disponibilidad'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Pasar información'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Llamada de seguimiento'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Agendar visita'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Elaboración de contrato'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Notaría'],
            ['etapa_id' => $etapas['Colocación'], 'nombre' => 'Levantar inventario'],

            // Pasar Información
            ['etapa_id' => $etapas['Pasar Información'], 'nombre' => 'Pasar la información'],
            ['etapa_id' => $etapas['Pasar Información'], 'nombre' => 'Llamada de seguimiento'],
            ['etapa_id' => $etapas['Pasar Información'], 'nombre' => 'Llamada de confirmación de recepción'],

            // Visita
            ['etapa_id' => $etapas['Visita'], 'nombre' => 'Enviar información y ubicación'],
            ['etapa_id' => $etapas['Visita'], 'nombre' => 'Confirmar visita'],
            ['etapa_id' => $etapas['Visita'], 'nombre' => 'Llamada de seguimiento'],

            // Promoción
            ['etapa_id' => $etapas['Promoción'], 'nombre' => 'Visita programada'],

            // Inmuebles Captados
            ['etapa_id' => $etapas['Inmuebles Captados'], 'nombre' => 'Elaboración y edición de fotos y videos'],
            ['etapa_id' => $etapas['Inmuebles Captados'], 'nombre' => 'Subir al sistema'],
            ['etapa_id' => $etapas['Inmuebles Captados'], 'nombre' => 'Publicar en Marketplace'],
            ['etapa_id' => $etapas['Inmuebles Captados'], 'nombre' => 'Promocionar en Meta'],
            ['etapa_id' => $etapas['Inmuebles Captados'], 'nombre' => 'Hacer el arte y descripción'],
            ['etapa_id' => $etapas['Inmuebles Captados'], 'nombre' => 'Subir a los grupos de Whatsapp'],
        ]);
    }

    private function seedSimpleCatalog(string $table, array $values): void
    {
        $rows = array_map(fn ($value) => ['nombre' => $value], $values);

        DB::table($table)->insert($rows);
    }
}
