<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\Persona;
use App\Models\PersonaEtiqueta;
use App\Services\FichaPersona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PadronController extends Controller
{
    public function __construct(private readonly FichaPersona $ficha) {}

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

    /**
     * Ficha 360°: todo lo que el instituto sabe de una persona, en una
     * sola pantalla.
     */
    public function show(Request $request, Persona $persona): Response
    {
        $persona->load([
            'etiquetas',
            'creaSolicitudes',
            'impulstateInscripciones',
            'nodicoMembresias',
            'herenciaVivaClientes',
            'juridicoAsesorias',
            'citasAgendamientos',
        ]);

        return Inertia::render('Padron/Ficha', [
            'persona' => [
                'id' => $persona->id,
                'nombre_completo' => $persona->nombre_completo,
                'estado_persona' => $persona->estado_persona,
                'tipo_persona' => $persona->tipo_persona,
                'municipio' => $persona->municipio,
                'demo' => $persona->demo,
                'latitud' => $persona->latitud,
                'longitud' => $persona->longitud,
            ],
            'secciones' => $this->ficha->datosGenerales($persona),
            'lineaDeTiempo' => $this->ficha->lineaDeTiempo($persona),
            'vinculos' => $this->ficha->vinculos($persona),
            'etiquetas' => $persona->etiquetas->pluck('etiqueta')->values(),
            'etiquetasSugeridas' => $this->etiquetasSugeridas(),
            'auditorias' => $this->auditorias($request, $persona),
            'filtrosAuditoria' => [
                'campo' => $request->string('campo')->toString(),
                'modulo' => $request->string('modulo')->toString(),
            ],
            'camposAuditados' => $persona->auditorias()
                ->distinct()->orderBy('campo_modificado')->pluck('campo_modificado')->filter()->values(),
            'modulosAuditados' => $persona->auditorias()
                ->distinct()->orderBy('modulo_origen')->pluck('modulo_origen')->filter()->values(),
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

        $persona = Persona::create($data);

        return redirect()
            ->route('padron.show', $persona)
            ->with('flash', ['success' => 'Contacto creado correctamente.']);
    }

    public function update(UpdatePersonaRequest $request, Persona $persona): RedirectResponse
    {
        $persona->update($request->validated());

        return back()->with('flash', ['success' => 'Contacto actualizado correctamente.']);
    }

    /**
     * Agrega una etiqueta a la persona. Idempotente: repetir la misma
     * etiqueta no crea un duplicado (la llave primaria es compuesta).
     */
    public function agregarEtiqueta(Request $request, Persona $persona): RedirectResponse
    {
        $datos = $request->validate([
            'etiqueta' => ['required', 'string', 'max:60', 'regex:/^[\pL\pN _-]+$/u'],
        ], [
            'etiqueta.regex' => 'La etiqueta solo admite letras, números, espacios, guiones y guiones bajos.',
        ]);

        $etiqueta = mb_strtolower(trim($datos['etiqueta']));

        $persona->agregarEtiqueta($etiqueta);
        $persona->marcarAuditoria('etiqueta', null, $etiqueta, $request->user()->id, 'padron');

        return back()->with('flash', ['success' => "Se agregó la etiqueta «{$etiqueta}»."]);
    }

    public function quitarEtiqueta(Request $request, Persona $persona, string $etiqueta): RedirectResponse
    {
        $persona->removerEtiqueta($etiqueta);
        $persona->marcarAuditoria('etiqueta', $etiqueta, null, $request->user()->id, 'padron');

        return back()->with('flash', ['success' => "Se quitó la etiqueta «{$etiqueta}»."]);
    }

    public function mapa(Request $request): Response
    {
        $etiqueta = $request->string('etiqueta')->toString();
        $modulo = $request->string('modulo')->toString();

        $personas = Persona::query()
            ->activas()
            ->geolocalizadas()
            ->when($etiqueta, fn ($query) => $query->porEtiqueta($etiqueta))
            ->when($modulo, fn ($query) => $query->porModulo($modulo))
            ->with('etiquetas:persona_id,etiqueta')
            ->get([
                'id', 'nombre_completo', 'municipio', 'telefono', 'email',
                'latitud', 'longitud', 'estado_persona', 'creado_por_modulo',
            ]);

        return Inertia::render('Padron/Mapa', [
            'personas' => $personas,
            'filtros' => ['etiqueta' => $etiqueta, 'modulo' => $modulo],
            'etiquetasDisponibles' => $this->etiquetasSugeridas(),
            'modulosDisponibles' => collect(config('modulos'))
                ->map(fn (array $m, string $slug) => ['slug' => $slug, 'nombre' => $m['nombre']])
                ->values(),
        ]);
    }

    /**
     * Etiquetas del catálogo más las que ya existen en la base, sin repetir.
     * Alimentan el autocompletado de la ficha y el filtro del mapa.
     */
    private function etiquetasSugeridas(): array
    {
        return collect(config('padron.etiquetas_sugeridas'))
            ->merge(PersonaEtiqueta::query()->distinct()->pluck('etiqueta'))
            ->map(fn (string $etiqueta) => mb_strtolower($etiqueta))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function auditorias(Request $request, Persona $persona)
    {
        return $persona->auditorias()
            ->when(
                $request->string('campo')->toString(),
                fn ($query, $campo) => $query->where('campo_modificado', $campo)
            )
            ->when(
                $request->string('modulo')->toString(),
                fn ($query, $modulo) => $query->where('modulo_origen', $modulo)
            )
            ->with('usuario:id,name,apellido')
            ->latest('fecha_cambio')
            ->paginate(20, ['*'], 'auditorias')
            ->withQueryString()
            ->through(fn ($auditoria) => [
                'id' => $auditoria->id,
                'campo' => $auditoria->campo_modificado,
                'valor_anterior' => $auditoria->valor_anterior,
                'valor_nuevo' => $auditoria->valor_nuevo,
                'modulo' => $auditoria->modulo_origen,
                'fecha' => $auditoria->fecha_cambio,
                'usuario' => $auditoria->usuario
                    ? trim("{$auditoria->usuario->name} {$auditoria->usuario->apellido}")
                    : 'Sistema',
            ]);
    }
}
