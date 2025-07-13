<?php

namespace App\Http\Controllers;

use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $genders = Gender::all();

            return response()->json([
                'success' => true,
                'data' => $genders,
                'message' => 'Genders retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving genders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * Not needed for API - removed or return validation rules
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'validation_rules' => [
                'name' => 'required|string|max:255|unique:genders,name'
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:genders,name'
            ]);

            $gender = Gender::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name']
            ]);

            return response()->json([
                'success' => true,
                'data' => $gender,
                'message' => 'Gender created successfully'
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
                'message' => 'Error creating gender',
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
            // Buscar por UUID o ID
            $gender = Gender::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$gender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gender not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $gender,
                'message' => 'Gender retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving gender',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * Not needed for API - return current data and validation rules
     */
    public function edit(string $id): JsonResponse
    {
        try {
            $gender = Gender::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$gender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gender not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $gender,
                'validation_rules' => [
                    'name' => 'required|string|max:255|unique:genders,name,' . $gender->id
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving gender for edit',
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
            $gender = Gender::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$gender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gender not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:genders,name,' . $gender->id
            ]);

            $gender->update($validated);

            return response()->json([
                'success' => true,
                'data' => $gender->fresh(),
                'message' => 'Gender updated successfully'
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
                'message' => 'Error updating gender',
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
            $gender = Gender::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$gender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gender not found'
                ], 404);
            }

            // Verificar si hay usuarios asociados
            if ($gender->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete gender. There are users associated with this gender.'
                ], 409);
            }

            $gender->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gender deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting gender',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
