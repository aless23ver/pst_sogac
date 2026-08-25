<?php

namespace App\Http\Controllers;

use App\Models\Requisito;
use Illuminate\Http\Request;

class AdminRequisitoController extends Controller
{
    // Mostrar todos los requisitos (Equivale a $_GET['action'] == 'listar')
    public function index()
    {
        // Eloquent hace el "SELECT * ORDER BY" automáticamente
        $requisitos = Requisito::orderBy('req_nombre_requisito', 'ASC')->get();
        
        // Retornamos la vista (HTML) y le pasamos la variable
        return view('admin.requisitos.index', compact('requisitos'));
    }

    // Guardar un requisito nuevo (Equivale al bloque INSERT del POST)
    public function store(Request $request)
    {
        // Laravel valida los campos por ti, sin usar if ($nombre === '')
        $request->validate([
            'nombre_tramite' => 'required|string',
            'documentos_necesarios' => 'required|string',
        ]);

        // Insertamos en la BD mapeando los inputs a tus columnas personalizadas
        Requisito::create([
            'req_nombre_requisito' => $request->nombre_tramite,
            'req_descripcion' => $request->documentos_necesarios,
        ]);

        return redirect()->route('admin.requisitos.index')
                         ->with('success', '¡Trámite y requisitos agregados con éxito!');
    }

    // Actualizar un requisito (Equivale al bloque UPDATE del POST)
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_tramite' => 'required|string',
            'documentos_necesarios' => 'required|string',
        ]);

        // Buscamos por tu Primary Key (req_id) y actualizamos
        $requisito = Requisito::findOrFail($id);
        $requisito->update([
            'req_nombre_requisito' => $request->nombre_tramite,
            'req_descripcion' => $request->documentos_necesarios,
        ]);

        return redirect()->route('admin.requisitos.index')
                         ->with('success', '¡Requisitos actualizados con éxito!');
    }

    // Eliminar un requisito (Equivale a $_GET['action'] == 'eliminar')
    public function destroy($id)
    {
        $requisito = Requisito::findOrFail($id);
        $requisito->delete();

        return redirect()->route('admin.requisitos.index')
                         ->with('success', 'Trámite eliminado correctamente.');
    }
}