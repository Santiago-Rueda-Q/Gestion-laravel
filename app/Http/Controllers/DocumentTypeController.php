<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $documentTypes = DocumentType::all();

            return response()->json([
                'success' => true,
                'data' => $documentTypes,
                'message' => 'Document types retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving document types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * Not needed for API - return validation rules
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'validation_rules' => [
                'name' => 'required|string|max:255|unique:document_types,name',
                'code' => 'required|string|max:50|unique:document_types,code'
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
                'name' => 'required|string|max:255|unique:document_types,name',
                'code' => 'required|string|max:50|unique:document_types,code'
            ]);

            $documentType = DocumentType::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']) // Convertir a mayúsculas para consistencia
            ]);

            return response()->json([
                'success' => true,
                'data' => $documentType,
                'message' => 'Document type created successfully'
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
                'message' => 'Error creating document type',
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
            $documentType = DocumentType::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $documentType,
                'message' => 'Document type retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving document type',
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
            $documentType = DocumentType::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $documentType,
                'validation_rules' => [
                    'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
                    'code' => 'required|string|max:50|unique:document_types,code,' . $documentType->id
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving document type for edit',
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
            $documentType = DocumentType::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
                'code' => 'required|string|max:50|unique:document_types,code,' . $documentType->id
            ]);

            $documentType->update([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code'])
            ]);

            return response()->json([
                'success' => true,
                'data' => $documentType->fresh(),
                'message' => 'Document type updated successfully'
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
                'message' => 'Error updating document type',
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
            $documentType = DocumentType::where('uuid', $id)->orWhere('id', $id)->first();

            if (!$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document type not found'
                ], 404);
            }

            // Verificar si hay usuarios asociados
            if ($documentType->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete document type. There are users associated with this document type.'
                ], 409);
            }

            $documentType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document type deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
