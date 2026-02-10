<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NormalizeCatalogoAccionesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->normalizeEtapas();
            $this->normalizeAcciones();
        });
    }

    private function normalizeEtapas(): void
    {
        $canonical = [
            'captacion' => 'Captación',
            'colocacion' => 'Colocación',
            'promocion' => 'Promoción',
            'pasar informacion' => 'Pasar Información',
            'visita' => 'Visita',
            'visitas' => 'Visita',
            'cierre' => 'Cierre',
            'inmuebles captados' => 'Inmuebles Captados',
            'inmueble captado' => 'Inmuebles Captados',
        ];

        $rows = DB::table('catalogo_etapas')->select(['id', 'nombre'])->get();

        $groups = [];
        foreach ($rows as $row) {
            $key = $this->normalizeText($row->nombre);
            $groups[$key][] = $row;
        }

        // Ensure canonical etapas exist
        $canonicalIds = [];
        foreach ($canonical as $key => $name) {
            if (!isset($groups[$key])) {
                $id = DB::table('catalogo_etapas')->insertGetId(['nombre' => $name]);
                $canonicalIds[$key] = $id;
                $groups[$key] = [(object) ['id' => $id, 'nombre' => $name]];
                continue;
            }

            $list = $groups[$key];
            $exact = collect($list)->firstWhere('nombre', $name);
            $keep = $exact ?? $list[0];
            $canonicalIds[$key] = $keep->id;

            // Rename canonical to the expected label
            if ($keep->nombre !== $name) {
                DB::table('catalogo_etapas')
                    ->where('id', $keep->id)
                    ->update(['nombre' => $name]);
            }

            // Merge duplicates
            foreach ($list as $row) {
                if ($row->id === $keep->id) {
                    continue;
                }
                DB::table('catalogo_acciones')
                    ->where('etapa_id', $row->id)
                    ->update(['etapa_id' => $keep->id]);
                DB::table('catalogo_etapas')->where('id', $row->id)->delete();
            }
        }
    }

    private function normalizeAcciones(): void
    {
        $canonical = [
            'Captación' => [
                'Contacto inicial',
                'Llamar',
                'Gestionar Reunión',
                'Visitar el inmueble',
                'Toma de fotografía',
                'Pedir la documentación',
                'Hacer el AMC',
                'Elaboración de contrato',
                'Firma de contrato',
                'Subir al sistema',
            ],
            'Colocación' => [
                'Buscar inmueble',
                'Preguntar disponibilidad',
                'Pasar información',
                'Llamada de seguimiento',
                'Agendar visita',
                'Elaboración de contrato',
                'Notaría',
                'Levantar inventario',
            ],
            'Pasar Información' => [
                'Pasar la información',
                'Llamada de seguimiento',
                'Llamada de confirmación de recepción',
            ],
            'Visita' => [
                'Enviar información y ubicación',
                'Confirmar visita',
                'Llamada de seguimiento',
            ],
            'Promoción' => [
                'Visita programada',
            ],
            'Inmuebles Captados' => [
                'Elaboración y edición de fotos y videos',
                'Subir al sistema',
                'Publicar en Marketplace',
                'Promocionar en Meta',
                'Hacer el arte y descripción',
                'Subir a los grupos de Whatsapp',
            ],
        ];

        $etapas = DB::table('catalogo_etapas')->select(['id', 'nombre'])->get();
        $etapasByName = [];
        foreach ($etapas as $etapa) {
            $etapasByName[$this->normalizeText($etapa->nombre)] = $etapa->id;
        }

        foreach ($canonical as $etapaNombre => $acciones) {
            $etapaKey = $this->normalizeText($etapaNombre);
            $etapaId = $etapasByName[$etapaKey] ?? null;
            if (!$etapaId) {
                $etapaId = DB::table('catalogo_etapas')->insertGetId(['nombre' => $etapaNombre]);
                $etapasByName[$etapaKey] = $etapaId;
            }

            $allRows = DB::table('catalogo_acciones')
                ->where('etapa_id', $etapaId)
                ->get();

            $byUserAndKey = [];
            foreach ($allRows as $row) {
                $userKey = $row->usuario_id ?? 'null';
                $key = $this->normalizeText($row->nombre);
                $byUserAndKey[$userKey][$key][] = $row;
            }

            foreach ($acciones as $accionNombre) {
                $accionKey = $this->normalizeText($accionNombre);
                foreach ($byUserAndKey as $userKey => $group) {
                    $candidates = collect($group[$accionKey] ?? []);
                    if ($candidates->isEmpty()) {
                        if ($userKey === 'null') {
                            DB::table('catalogo_acciones')->insert([
                                'etapa_id' => $etapaId,
                                'usuario_id' => null,
                                'nombre' => $accionNombre,
                            ]);
                        }
                        continue;
                    }

                    $keep = $candidates->firstWhere('nombre', $accionNombre) ?? $candidates->first();

                    if ($keep->nombre !== $accionNombre) {
                        DB::table('catalogo_acciones')
                            ->where('id', $keep->id)
                            ->update(['nombre' => $accionNombre]);
                    }

                    foreach ($candidates as $row) {
                        if ($row->id === $keep->id) {
                            continue;
                        }
                        $this->repointAccionId($row->id, $keep->id);
                        DB::table('catalogo_acciones')->where('id', $row->id)->delete();
                    }
                }
            }
        }
    }

    private function repointAccionId(int $fromId, int $toId): void
    {
        $tables = [
            'historial_acciones',
            'colocacion_historial_acciones',
            'visitas_historial_acciones',
            'pasar_informacion_historial_acciones',
            'inmuebles_captados_historial_acciones',
            'interesados_visitas_acciones',
        ];

        foreach ($tables as $table) {
            DB::table($table)->where('accion_id', $fromId)->update(['accion_id' => $toId]);
        }
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\\s+/', ' ', $value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9\\s]/', '', $value);
        $value = preg_replace('/\\s+/', ' ', $value);

        return $value;
    }
}
