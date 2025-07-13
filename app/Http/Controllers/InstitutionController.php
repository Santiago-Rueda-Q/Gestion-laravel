<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstitutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Institution::query();

            // Filtrado por nombre
            if ($request->has('name') && $request->name) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filtrado por acrónimo
            if ($request->has('acronym') && $request->acronym) {
                $query->where('acronym', 'like', '%' . $request->acronym . '%');
            }

            // Filtrado por ciudad
            if ($request->has('city') && $request->city) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            // Filtrado por país
            if ($request->has('country') && $request->country) {
                $query->where('country', 'like', '%' . $request->country . '%');
            }

            // Incluir conteos de relaciones si se solicita
            if ($request->has('with_counts') && $request->with_counts) {
                $query->withCount(['programs', 'users']);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $institutions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $institutions,
                'message' => 'Instituciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las instituciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:institutions,name',
                'acronym' => 'nullable|string|max:50|unique:institutions,acronym',
                'city' => 'required|string|max:255',
                'country' => 'required|string|max:255'
            ]);

            $institution = Institution::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'acronym' => $validated['acronym'] ?? null,
                'city' => $validated['city'],
                'country' => $validated['country']
            ]);

            return response()->json([
                'success' => true,
                'data' => $institution,
                'message' => 'Institución creada exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la institución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $query = Institution::where('id', $id)->orWhere('uuid', $id);

            // Incluir relaciones si se solicita
            $with = [];
            if (request()->has('with_programs') && request()->with_programs) {
                $with[] = 'programs:id,institution_id,name,code,level';
            }
            if (request()->has('with_users') && request()->with_users) {
                $with[] = 'users:id,institution_id,name,email';
            }

            if (!empty($with)) {
                $query->with($with);
            }

            // Incluir conteos
            $query->withCount(['programs', 'users']);

            $institution = $query->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $institution,
                'message' => 'Institución encontrada exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Institución no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la institución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $institution = Institution::where('id', $id)
                ->orWhere('uuid', $id)
                ->firstOrFail();

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('institutions', 'name')->ignore($institution->id)
                ],
                'acronym' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('institutions', 'acronym')->ignore($institution->id)
                ],
                'city' => 'required|string|max:255',
                'country' => 'required|string|max:255'
            ]);

            $institution->update([
                'name' => $validated['name'],
                'acronym' => $validated['acronym'] ?? null,
                'city' => $validated['city'],
                'country' => $validated['country']
            ]);

            return response()->json([
                'success' => true,
                'data' => $institution->fresh(),
                'message' => 'Institución actualizada exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Institución no encontrada'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la institución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $institution = Institution::where('id', $id)
                ->orWhere('uuid', $id)
                ->firstOrFail();

            // Verificar si hay programas académicos asociados
            if ($institution->programs()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la institución porque tiene programas académicos asociados'
                ], 409);
            }

            // Verificar si hay usuarios asociados
            if ($institution->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la institución porque tiene usuarios asociados'
                ], 409);
            }

            $institution->delete();

            return response()->json([
                'success' => true,
                'message' => 'Institución eliminada exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Institución no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la institución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all institutions for dropdown/select components
     */
    public function getAllForSelect(): JsonResponse
    {
        try {
            $institutions = Institution::select('id', 'uuid', 'name', 'acronym', 'city', 'country')
                ->orderBy('name')
                ->get()
                ->map(function ($institution) {
                    return [
                        'id' => $institution->id,
                        'uuid' => $institution->uuid,
                        'name' => $institution->name,
                        'acronym' => $institution->acronym,
                        'city' => $institution->city,
                        'country' => $institution->country,
                        'display_name' => $institution->acronym ?
                            "{$institution->name} ({$institution->acronym})" :
                            $institution->name
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $institutions,
                'message' => 'Instituciones obtenidas para selección'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las instituciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get institutions by country
     */
    public function getByCountry(string $country): JsonResponse
    {
        try {
            $institutions = Institution::where('country', $country)
                ->select('id', 'uuid', 'name', 'acronym', 'city')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $institutions,
                'message' => "Instituciones de {$country} obtenidas exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las instituciones por país',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get institutions statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_institutions' => Institution::count(),
                'countries_count' => Institution::distinct('country')->count(),
                'cities_count' => Institution::distinct('city')->count(),
                'institutions_by_country' => Institution::selectRaw('country, COUNT(*) as count')
                    ->groupBy('country')
                    ->orderBy('count', 'desc')
                    ->get(),
                'recent_institutions' => Institution::latest()
                    ->take(5)
                    ->select('id', 'name', 'acronym', 'city', 'country', 'created_at')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de instituciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
