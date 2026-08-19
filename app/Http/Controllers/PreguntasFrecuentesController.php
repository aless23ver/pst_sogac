<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PreguntasFrecuentes\CreatePostRequest;
use App\Http\Requests\PreguntasFrecuentes\UpdatePostRequest;
use App\Services\PreguntasFrecuentesService;

class PreguntasFrecuentesController extends Controller
{

    public function __construct(protected PreguntasFrecuentesService $service)
    {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $preguntas = $this->service->getAll();
        return view('soporte\preguntas_frecuentes', compact('preguntas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('soporte.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePostRequest $request)
    { 
        $this->service->create($request->validated());

        return redirect()->route('soporte.index')->with('message','Pregunta añadida existosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, string $id)
    {
        // 1. Enviamos la ID y la data ya validada al servicio
        $this->service->update((int)$id, $request->validated());

        // 2. Redirigimos
        return redirect()->route('soporte.index')->with('message', 'Pregunta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // 1. Enviamos la orden de eliminar al servicio
        $this->service->delete((int)$id);

        // 2. Redirigimos
        return redirect()->route('soporte.index')->with('message', 'Pregunta eliminada exitosamente.');
    }
}