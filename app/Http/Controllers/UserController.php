<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\DocumentType;
use App\Models\UserType;
use App\Models\Institution;
use App\Models\AcademicProgram;
use App\Models\Gender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['documentType', 'userType', 'institution', 'academicProgram', 'gender']);

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                });
            }

            // Filtros específicos
            if ($request->filled('user_type_id')) {
                $query->where('user_type_id', $request->user_type_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('institution_id')) {
                $query->where('institution_id', $request->institution_id);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
                'filters' => [
                    'search' => $request->search,
                    'user_type_id' => $request->user_type_id,
                    'status' => $request->status,
                    'institution_id' => $request->institution_id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data for creating a new user
     */
    public function create(): JsonResponse
    {
        try {
            $data = [
                'document_types' => DocumentType::select('id', 'name')->get(),
                'user_types' => UserType::select('id', 'name')->get(),
                'institutions' => Institution::select('id', 'name')->get(),
                'academic_programs' => AcademicProgram::select('id', 'name', 'institution_id')->get(),
                'genders' => Gender::select('id', 'name')->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del formulario',
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
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'birthdate' => 'nullable|date',
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'document_type_id' => 'required|exists:document_types,id',
                'user_type_id' => 'required|exists:user_types,id',
                'document_number' => 'required|string|unique:users,document_number',
                'institution_id' => 'nullable|exists:institutions,id',
                'academic_program_id' => 'nullable|exists:academic_programs,id',
                'gender_id' => 'nullable|exists:genders,id',
                'company_name' => 'nullable|string|max:255',
                'company_address' => 'nullable|string|max:500',
                'status' => 'required|in:active,inactive,pending',
                'accepted_terms' => 'required|boolean',
            ]);

            // Generar UUID único
            $validatedData['uuid'] = Str::uuid();

            // Hashear la contraseña
            $validatedData['password'] = Hash::make($validatedData['password']);

            // Manejar la foto de perfil
            if ($request->hasFile('profile_photo')) {
                $fileName = time() . '_' . $request->file('profile_photo')->getClientOriginalName();
                $path = $request->file('profile_photo')->storeAs('profile_photos', $fileName, 'public');
                $validatedData['profile_photo'] = $path;
            }

            $user = User::create($validatedData);

            // Cargar relaciones para la respuesta
            $user->load(['documentType', 'userType', 'institution', 'academicProgram', 'gender']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $user
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
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
            $user = User::with([
                'documentType',
                'userType',
                'institution',
                'academicProgram',
                'gender'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data for editing the specified resource.
     */
    public function edit(string $id): JsonResponse
    {
        try {
            $user = User::with([
                'documentType',
                'userType',
                'institution',
                'academicProgram',
                'gender'
            ])->findOrFail($id);

            $data = [
                'user' => $user,
                'document_types' => DocumentType::select('id', 'name')->get(),
                'user_types' => UserType::select('id', 'name')->get(),
                'institutions' => Institution::select('id', 'name')->get(),
                'academic_programs' => AcademicProgram::select('id', 'name', 'institution_id')->get(),
                'genders' => Gender::select('id', 'name')->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del usuario',
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
            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($user->id)
                ],
                'password' => 'nullable|string|min:8|confirmed',
                'birthdate' => 'nullable|date',
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'document_type_id' => 'required|exists:document_types,id',
                'user_type_id' => 'required|exists:user_types,id',
                'document_number' => [
                    'required',
                    'string',
                    Rule::unique('users', 'document_number')->ignore($user->id)
                ],
                'institution_id' => 'nullable|exists:institutions,id',
                'academic_program_id' => 'nullable|exists:academic_programs,id',
                'gender_id' => 'nullable|exists:genders,id',
                'company_name' => 'nullable|string|max:255',
                'company_address' => 'nullable|string|max:500',
                'status' => 'required|in:active,inactive,pending',
                'accepted_terms' => 'required|boolean',
            ]);

            // Solo actualizar la contraseña si se proporciona
            if (!empty($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            } else {
                unset($validatedData['password']);
            }

            // Manejar la foto de perfil
            if ($request->hasFile('profile_photo')) {
                // Eliminar la foto anterior si existe
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $fileName = time() . '_' . $request->file('profile_photo')->getClientOriginalName();
                $path = $request->file('profile_photo')->storeAs('profile_photos', $fileName, 'public');
                $validatedData['profile_photo'] = $path;
            }

            $user->update($validatedData);

            // Cargar relaciones para la respuesta
            $user->load(['documentType', 'userType', 'institution', 'academicProgram', 'gender']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
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
            $user = User::findOrFail($id);

            // Eliminar la foto de perfil si existe
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->status = $user->status === 'active' ? 'inactive' : 'active';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado del usuario actualizado exitosamente',
                'data' => [
                    'id' => $user->id,
                    'status' => $user->status
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get academic programs by institution
     */
    public function getAcademicPrograms(Request $request): JsonResponse
    {
        try {
            $institutionId = $request->institution_id;

            if (!$institutionId) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $programs = AcademicProgram::where('institution_id', $institutionId)
                ->select('id', 'name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $programs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los programas académicos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'action' => 'required|in:activate,deactivate,delete',
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id'
            ]);

            $userIds = $validatedData['user_ids'];
            $action = $validatedData['action'];

            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)->update(['status' => 'active']);
                    $message = 'Usuarios activados exitosamente';
                    break;

                case 'deactivate':
                    User::whereIn('id', $userIds)->update(['status' => 'inactive']);
                    $message = 'Usuarios desactivados exitosamente';
                    break;

                case 'delete':
                    $users = User::whereIn('id', $userIds)->get();

                    // Eliminar fotos de perfil
                    foreach ($users as $user) {
                        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                            Storage::disk('public')->delete($user->profile_photo);
                        }
                    }

                    User::whereIn('id', $userIds)->delete();
                    $message = 'Usuarios eliminados exitosamente';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'affected_count' => count($userIds)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la operación masiva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'inactive_users' => User::where('status', 'inactive')->count(),
                'pending_users' => User::where('status', 'pending')->count(),
                'users_by_type' => User::select('user_type_id')
                    ->with('userType:id,name')
                    ->get()
                    ->groupBy('user_type_id')
                    ->map(function ($group) {
                        return [
                            'type' => $group->first()->userType->name ?? 'Sin tipo',
                            'count' => $group->count()
                        ];
                    })
                    ->values(),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
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
