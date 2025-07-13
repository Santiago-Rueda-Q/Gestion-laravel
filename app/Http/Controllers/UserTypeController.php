<?php

namespace App\Http\Controllers;

use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = UserType::query();

            // Filtrado por tipo si se proporciona
            if ($request->has('type') && $request->type) {
                $query->where('type', 'like', '%' . $request->type . '%');
            }

            // Filtrado por descripción si se proporciona
            if ($request->has('description') && $request->description) {
                $query->where('description', 'like', '%' . $request->description . '%');
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $userTypes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $userTypes,
                'message' => 'Tipos de usuario obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de usuario',
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
                'type' => 'required|string|max:255|unique:user_types,type',
                'description' => 'nullable|string|max:1000'
            ]);

            $userType = UserType::create([
                'uuid' => Str::uuid(),
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $userType,
                'message' => 'Tipo de usuario creado exitosamente'
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
                'message' => 'Error al crear el tipo de usuario',
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
            // Buscar por ID o UUID
            $userType = UserType::where('id', $id)
                ->orWhere('uuid', $id)
                ->with('users:id,name,email,user_type_id') // Incluir usuarios relacionados
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $userType,
                'message' => 'Tipo de usuario encontrado exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de usuario no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de usuario',
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
            $userType = UserType::where('id', $id)
                ->orWhere('uuid', $id)
                ->firstOrFail();

            $validated = $request->validate([
                'type' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('user_types', 'type')->ignore($userType->id)
                ],
                'description' => 'nullable|string|max:1000'
            ]);

            $userType->update([
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $userType->fresh(),
                'message' => 'Tipo de usuario actualizado exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de usuario no encontrado'
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
                'message' => 'Error al actualizar el tipo de usuario',
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
            $userType = UserType::where('id', $id)
                ->orWhere('uuid', $id)
                ->firstOrFail();

            // Verificar si hay usuarios asociados
            if ($userType->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el tipo de usuario porque tiene usuarios asociados'
                ], 409);
            }

            $userType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de usuario eliminado exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de usuario no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all user types for dropdown/select components
     */
    public function getAllForSelect(): JsonResponse
    {
        try {
            $userTypes = UserType::select('id', 'uuid', 'type', 'description')
                ->orderBy('type')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $userTypes,
                'message' => 'Tipos de usuario obtenidos para selección'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
