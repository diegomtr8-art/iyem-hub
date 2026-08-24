<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\Persona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PadronController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->toString();
        $estadoPersona = $request->string('estado_persona')->toString();

        $personas = Persona::query()
            ->when($busqueda, fn ($query) => $query->where(function ($query) use ($busqueda) {
                $query->where('nombre_completo', 'like', "%{$busqueda}%")
                    ->orWhere('email', 'like', "%{$busqueda}%")
                    ->orWhere('municipio', 'like', "%{$busqueda}%");
            }))
            ->when($estadoPersona, fn ($query) => $query->where('estado_persona', $estadoPersona))
            ->orderBy('nombre_completo')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Padron/Index', [
            'personas' => $personas,
            'filtros' => ['busqueda' => $busqueda, 'estado_persona' => $estadoPersona],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Padron/Crear');
    }

    public function store(StorePersonaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['creado_por_modulo'] = $data['creado_por_modulo'] ?? 'padron';

        Persona::create($data);

        return redirect()->route('padron.index')->with('flash', ['success' => 'Contacto creado correctamente.']);
    }

    public function update(UpdatePersonaRequest $request, Persona $persona): RedirectResponse
    {
        $persona->update($request->validated());

        return back()->with('flash', ['success' => 'Contacto actualizado correctamente.']);
    }

    public function mapa(): Response
    {
        $personas = Persona::query()
            ->activas()
            ->geolocalizadas()
            ->get(['id', 'nombre_completo', 'municipio', 'telefono', 'latitud', 'longitud', 'estado_persona']);

        return Inertia::render('Padron/Mapa', [
            'personas' => $personas,
        ]);
    }
}
