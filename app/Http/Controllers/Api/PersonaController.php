<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Http\Resources\PersonaResource;
use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        $personas = Persona::query()
            ->activas()
            ->orderBy('nombre_completo')
            ->paginate(20);

        return PersonaResource::collection($personas);
    }

    public function store(StorePersonaRequest $request)
    {
        $persona = Persona::create($request->validated());

        return PersonaResource::make($persona)->response()->setStatusCode(201);
    }

    public function show(Persona $persona)
    {
        return PersonaResource::make($persona->load('etiquetas'));
    }

    public function update(UpdatePersonaRequest $request, Persona $persona)
    {
        $persona->update($request->validated());

        return PersonaResource::make($persona);
    }

    public function destroy(Persona $persona)
    {
        $persona->delete();

        return response()->noContent();
    }

    public function buscar(Request $request)
    {
        $q = $request->string('q')->toString();

        $personas = Persona::query()
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('nombre_completo', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('curp', 'like', "%{$q}%")
                    ->orWhere('rfc', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%");
            }))
            ->orderBy('nombre_completo')
            ->paginate(20)
            ->withQueryString();

        return PersonaResource::collection($personas);
    }

    public function porMunicipio(string $municipio)
    {
        $personas = Persona::query()
            ->porMunicipio($municipio)
            ->orderBy('nombre_completo')
            ->paginate(20);

        return PersonaResource::collection($personas);
    }

    public function porEtiqueta(string $etiqueta)
    {
        $personas = Persona::query()
            ->porEtiqueta($etiqueta)
            ->orderBy('nombre_completo')
            ->paginate(20);

        return PersonaResource::collection($personas);
    }
}
