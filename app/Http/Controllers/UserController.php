<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Models\DocumentType;
use App\Models\Models\UserType;
use App\Models\Models\Institution;
use App\Models\Models\AcademicProgram;
use App\Models\Models\Gender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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

        // Filtro por tipo de usuario
        if ($request->filled('user_type_id')) {
            $query->where('user_type_id', $request->user_type_id);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por institución
        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        // Datos para los filtros
        $userTypes = UserType::all();
        $institutions = Institution::all();

        return view('users.index', compact('users', 'userTypes', 'institutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $documentTypes = DocumentType::all();
        $userTypes = UserType::all();
        $institutions = Institution::all();
        $academicPrograms = AcademicProgram::all();
        $genders = Gender::all();

        return view('users.create', compact(
            'documentTypes',
            'userTypes',
            'institutions',
            'academicPrograms',
            'genders'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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

        try {
            $user = User::create($validatedData);

            return redirect()->route('users.index')
                ->with('success', 'Usuario creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with([
            'documentType',
            'userType',
            'institution',
            'academicProgram',
            'gender'
        ])->findOrFail($id);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $documentTypes = DocumentType::all();
        $userTypes = UserType::all();
        $institutions = Institution::all();
        $academicPrograms = AcademicProgram::all();
        $genders = Gender::all();

        return view('users.edit', compact(
            'user',
            'documentTypes',
            'userTypes',
            'institutions',
            'academicPrograms',
            'genders'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
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

        try {
            $user->update($validatedData);

            return redirect()->route('users.index')
                ->with('success', 'Usuario actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Eliminar la foto de perfil si existe
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->delete();

            return redirect()->route('users.index')
                ->with('success', 'Usuario eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar el estado del usuario
     */
    public function toggleStatus(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status = $user->status === 'active' ? 'inactive' : 'active';
            $user->save();

            return redirect()->back()
                ->with('success', 'Estado del usuario actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al cambiar el estado del usuario: ' . $e->getMessage());
        }
    }

    /**
     * Obtener programas académicos por institución (para AJAX)
     */
    public function getAcademicPrograms(Request $request)
    {
        $institutionId = $request->institution_id;

        if (!$institutionId) {
            return response()->json([]);
        }

        $programs = AcademicProgram::where('institution_id', $institutionId)
            ->select('id', 'name')
            ->get();

        return response()->json($programs);
    }

    /**
     * Exportar usuarios a Excel/CSV
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx');

        // Aquí puedes implementar la lógica de exportación
        // usando Laravel Excel o similar

        return redirect()->back()
            ->with('info', 'Funcionalidad de exportación en desarrollo.');
    }

    /**
     * Importar usuarios desde Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        // Aquí puedes implementar la lógica de importación
        // usando Laravel Excel o similar

        return redirect()->back()
            ->with('info', 'Funcionalidad de importación en desarrollo.');
    }
}
