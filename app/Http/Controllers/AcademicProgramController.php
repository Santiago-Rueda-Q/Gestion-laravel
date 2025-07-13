<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AcademicProgram::with('institution');

            // Filtrar por institución si se proporciona
            if ($request->has('institution_id')) {
                $query->where('institution_id', $request->institution_id);
            }

            // Búsqueda por nombre o código
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('code', 'like', '%' . $search . '%');
                });
            }

            $academicPrograms = $query->get();

            return response()->json([
                'success' => true,
                'data' => $academicPrograms,
                'message' => 'Academic programs retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving academic programs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * Not needed for API - return validation rules and institutions
     */
    public function create(): JsonResponse
    {
        try {
            $institutions = Institution::all(['id', 'name']);

            return response()->json([
                'success' => true,
                'institutions' => $institutions,
                'validation_rules' => [
                    'name' => 'required|string|max:255',
                    'code' => 'required|string|max:50|unique:academic_programs,code',
                    'description' => 'nullable|string|max:1000',
                    'institution_id' => 'required|exists:institutions,id'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error preparing create form',
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
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:academic_programs,code',
                'description' => 'nullable|string|max:1000',
                'institution_id' => 'required|exists:institutions,id'
            ]);

            $academicProgram = AcademicProgram::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']), // Convertir a mayúsculas para consistencia
                'description' => $validated['description'] ?? null,
                'institution_id' => $validated['institution_id']
            ]);

            // Cargar la relación con la institución
            $academicProgram->load('institution');

            return response()->json([
                'success' => true,
                'data' => $academicProgram,
                'message' => 'Academic program created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating academic program',
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
            // Buscar por UUID o ID e incluir la institución
            $academicProgram = AcademicProgram::with('institution')
                ->where('uuid', $id)
                ->orWhere('id', $id)
                ->first();

            if (!$academicProgram) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic program not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $academicProgram,
                'message' => 'Academic program retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving academic program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * Not needed for API - return current data, validation rules and institutions
     */
    public function edit(string $id): JsonResponse
    {
        try {
            $academicProgram = AcademicProgram::with('institution')
                ->where('uuid', $id)
                ->orWhere('id', $id)
                ->first();

            if (!$academicProgram) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic program not found'
                ], 404);
            }

            $institutions = Institution::all(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $academicProgram,
                'institutions' => $institutions,
                'validation_rules' => [
                    'name' => 'required|string|max:255',
                    'code' => 'required|string|max:50|unique:academic_programs,code,' . $academicProgram->id,
                    'description' => 'nullable|string|max:1000',
                    'institution_id' => 'required|exists:institutions,id'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving academic program for edit',
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
            $academicProgram = AcademicProgram::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$academicProgram) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic program not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:academic_programs,code,' . $academicProgram->id,
                'description' => 'nullable|string|max:1000',
                'institution_id' => 'required|exists:institutions,id'
            ]);

            $academicProgram->update([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'description' => $validated['description'] ?? null,
                'institution_id' => $validated['institution_id']
            ]);

            // Cargar la relación con la institución
            $academicProgram->load('institution');

            return response()->json([
                'success' => true,
                'data' => $academicProgram->fresh(['institution']),
                'message' => 'Academic program updated successfully'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating academic program',
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
            $academicProgram = AcademicProgram::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$academicProgram) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic program not found'
                ], 404);
            }

            // Verificar si hay usuarios asociados
            if ($academicProgram->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete academic program. There are users associated with this program.'
                ], 409);
            }

            $academicProgram->delete();

            return response()->json([
                'success' => true,
                'message' => 'Academic program deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting academic program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get academic programs by institution
     */
    public function getByInstitution(string $institutionId): JsonResponse
    {
        try {
            $academicPrograms = AcademicProgram::with('institution')
                ->where('institution_id', $institutionId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $academicPrograms,
                'message' => 'Academic programs retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving academic programs by institution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
