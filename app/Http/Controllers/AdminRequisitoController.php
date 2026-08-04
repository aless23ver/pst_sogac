<?php

namespace App\Http\Controllers;

use App\Models\Requisito;
use Illuminate\Http\Request;

class AdminRequisitoController extends Controller
{
    // MOSTRAR EL LISTADO (Leer)
    public function index()
    {
        $requisitos = Requisito::orderBy('nombre_tramite', 'asc')->get();
        return view('admin.requisitos.index', compact('requisitos'));
    }

    // MOSTRAR FORMULARIO DE CREAR
    public function create()
    {
        return view('admin.requisitos.form', ['requisito' => new Requisito()]);
    }

    // GUARDAR EL NUEVO REQUISITO EN LA BD (Crear)
    public function store(Request $request)
    {
        // Validamos. Fíjate cómo validamos que el nombre sea único en la tabla
        $request->validate([
            'nombre_tramite' => 'required|unique:requisitos,nombre_tramite',
            'documentos_necesarios' => 'required'
        ], [
            'nombre_tramite.unique' => 'Ese tipo de trámite ya existe en el sistema.'
        ]);

        Requisito::create($request->all());

        return redirect()->route('admin.requisitos.index')->with('success', '¡Trámite y requisitos agregados con éxito!');
    }

    // MOSTRAR FORMULARIO DE EDITAR
    public function edit(Requisito $requisito)
    {
        return view('admin.requisitos.form', compact('requisito'));
    }

    // ACTUALIZAR EN LA BD (Actualizar)
    public function update(Request $request, Requisito $requisito)
    {
        $request->validate([
            'nombre_tramite' => 'required|unique:requisitos,nombre_tramite,' . $requisito->id,
            'documentos_necesarios' => 'required'
        ]);

        $requisito->update($request->all());

        return redirect()->route('admin.requisitos.index')->with('success', '¡Requisitos actualizados con éxito!');
    }

    // ELIMINAR DE LA BD (Eliminar)
    public function destroy(Requisito $requisito)
    {
        $requisito->delete();
        return redirect()->route('admin.requisitos.index')->with('success', 'Trámite eliminado correctamente.');
    }
}