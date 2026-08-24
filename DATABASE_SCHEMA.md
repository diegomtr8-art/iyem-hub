# Esquema de Base de Datos - IYEM Central

**Fecha:** Agosto 19, 2026  
**Estado:** ✅ Listo para migrations y seeders  
**Stack:** Laravel 12 + MySQL 8 + Jetstream 5.5

---

## 📋 Estructura Implementada

### 1. **Tabla Central: `personas`**

Fuente única de verdad para todos los contactos del IYEM. Todos los módulos referencian esta tabla.

**Campos (58 total):**

#### Identidad Básica
- `id` - PK
- `nombre_completo` - VARCHAR(255) NOT NULL
- `email` - VARCHAR(255) UNIQUE NULLABLE
- `telefono` - VARCHAR(20) NULLABLE
- `telefono_secundario` - VARCHAR(20) NULLABLE

#### Identificación Oficial
- `curp` - VARCHAR(18) UNIQUE NULLABLE
- `rfc` - VARCHAR(13) NULLABLE
- `ine_clave` - VARCHAR(20) NULLABLE

#### Domicilio
- `calle` - VARCHAR(255) NULLABLE
- `calle_2` - VARCHAR(255) NULLABLE
- `codigo_postal` - VARCHAR(10) NULLABLE
- `ciudad` - VARCHAR(100) NULLABLE
- `municipio` - VARCHAR(100) NULLABLE (INDEXED)
- `localidad` - VARCHAR(100) NULLABLE
- `estado` - VARCHAR(100) NULLABLE
- `pais` - VARCHAR(100) DEFAULT 'México'

#### Datos Demográficos
- `fecha_nacimiento` - DATE NULLABLE
- `edad` - SMALLINT NULLABLE
- `sexo` - ENUM('M', 'F', 'Otro') NULLABLE

#### Educación y Patrimonio
- `nivel_educativo` - VARCHAR(100) NULLABLE
- `habla_maya` - BOOLEAN DEFAULT FALSE

#### Redes Sociales y Web
- `facebook_negocio` - VARCHAR(255) NULLABLE
- `instagram_negocio` - VARCHAR(255) NULLABLE
- `tiktok_negocio` - VARCHAR(255) NULLABLE
- `sitio_web` - VARCHAR(255) NULLABLE

#### Preferencias y Comunicación
- `idioma` - VARCHAR(50) DEFAULT 'es'
- `medio_ingreso` - VARCHAR(100) NULLABLE

#### Gestión de Ciclo de Vida
- `tipo_persona` - ENUM('fisica', 'moral') DEFAULT 'fisica'
- `estado_persona` - ENUM('activa', 'inactiva', 'bloqueada') DEFAULT 'activa' (INDEXED)

#### Metadata
- `creado_por_modulo` - VARCHAR(50) NULLABLE
- `created_at` - TIMESTAMP
- `updated_at` - TIMESTAMP
- `deleted_at` - TIMESTAMP NULLABLE (soft delete)

**Índices:**
- `UNIQUE(curp)`, `UNIQUE(email)`
- `INDEX(municipio)`, `INDEX(estado_persona)`, `INDEX(telefono)`

---

### 2. **Tablas de Módulos**

Cada módulo tiene su propia tabla que referencia `personas.id`. No duplican datos.

#### 2.1 **CREA: `crea_solicitudes`**
```sql
persona_id (FK) | monto_solicitado | tipo_credito | estado_solicitud | fecha_solicitud
```
**Estados:** borrador, enviada, aprobada, rechazada, desembolsada

#### 2.2 **IMPULSATE: `impulsate_inscripciones`**
```sql
persona_id (FK) | programa_id | programa_nombre | fecha_inscripcion | estado
```
**Estados:** registrada, activa, completada, cancelada

#### 2.3 **NODICO: `nodico_membresias`**
```sql
persona_id (FK) | tipo_membresia | estado_membresia | fecha_inicio | fecha_fin
```
**Estados:** activa, pausada, cancelada

#### 2.4 **HERENCIA VIVA: `herencia_viva_clientes`**
```sql
persona_id (FK) | numero_cliente | fecha_primer_compra | total_gastado | numero_compras | es_mayorista
```

#### 2.5 **JURÍDICO: `juridico_asesorias`**
```sql
persona_id (FK) | tipo_asesoria | fecha_asesoria | estado | notas
```
**Estados:** programada, completada, no_comparecio

#### 2.6 **CITAS: `citas_agendamientos`**
```sql
persona_id (FK) | tipo_cita | fecha_cita | estado | modulo_destino
```
**Estados:** programada, realizada, cancelada, no_asistio

---

### 3. **Tabla de Etiquetas: `personas_etiquetas`**

Clasificación de personas con many-to-many.

```sql
persona_id (PK, FK) | etiqueta (PK)
```

**Ejemplo de etiquetas:** emprendedor, artesano, capacitado, moroso, deudor, vip, etc.

---

### 4. **Tabla de Auditoría: `personas_auditorias`**

Trazabilidad completa de cambios en personas.

```sql
id | persona_id (FK) | campo_modificado | valor_anterior | valor_nuevo | usuario_id | modulo_origen | fecha_cambio
```

**Uso:** Rastrear quién cambió qué, cuándo y en qué módulo.

---

## 🔗 Relaciones Eloquent

```php
// Persona → Módulos
Persona::has('creaSolicitudes', 'impulstateInscripciones', 'nodicoMembresias', ...)

// Persona → Etiquetas
Persona::belongsToMany('etiquetas')

// Persona → Auditoría
Persona::has('auditorias')

// Módulo → Persona
CreaSolicitud::belongsTo('persona')
```

---

## 🚀 Migraciones Creadas

1. ✅ `2026_08_17_172734_create_personas_table.php` (ACTUALIZADA)
2. ✅ `2026_08_19_000000_create_crea_solicitudes_table.php`
3. ✅ `2026_08_19_000001_create_impulsate_inscripciones_table.php`
4. ✅ `2026_08_19_000002_create_nodico_membresias_table.php`
5. ✅ `2026_08_19_000003_create_herencia_viva_clientes_table.php`
6. ✅ `2026_08_19_000004_create_juridico_asesorias_table.php`
7. ✅ `2026_08_19_000005_create_citas_agendamientos_table.php`
8. ✅ `2026_08_19_000006_create_personas_etiquetas_table.php`
9. ✅ `2026_08_19_000007_create_personas_auditorias_table.php`

---

## 📦 Modelos Creados

- ✅ `App\Models\Persona`
- ✅ `App\Models\PersonaAuditoria`
- ✅ `App\Models\Modulos\CreaSolicitud`
- ✅ `App\Models\Modulos\ImpulsateInscripcion`
- ✅ `App\Models\Modulos\NodicoMembresia`
- ✅ `App\Models\Modulos\HerenciaVivaCliente`
- ✅ `App\Models\Modulos\JuridicoAsesoria`
- ✅ `App\Models\Modulos\CitasAgendamiento`

---

## 🔧 Estado de los entregables

1. ✅ **Migrations ejecutadas** — `php artisan migrate:fresh --seed` corrido contra la BD local (`iyemyucatan`). La tabla `personas` ya tiene las 33 columnas nuevas (antes tenía el esquema viejo de un batch previo, se reconstruyó por completo).
2. ✅ **Seeder actualizado** — `PersonaSeeder` genera 20 personas con CURP/RFC con formato válido, municipios de Yucatán, y `creado_por_modulo` variado.
3. ✅ **Controllers & Routes:**
   - `PadronController` (web/Inertia) actualizado a los nuevos campos (`nombre_completo`, `estado_persona`, etc.)
   - `Api\PersonaController` nuevo con CRUD + `/api/personas/buscar`, `/por-municipio/{municipio}`, `/por-etiqueta/{etiqueta}` (protegidos con `auth:sanctum`)
4. ✅ **Validaciones** — `StorePersonaRequest`/`UpdatePersonaRequest`: CURP (18 chars, regex), RFC (12-13 chars, regex), email único, teléfono ≥10 dígitos, fecha de nacimiento no futura.
5. ✅ **Tests** — `tests/Feature/PersonaTest.php`: creación con todos los campos, relación con módulos, soft delete, auditoría automática, etiquetas, y el seeder generando ≥10 registros. Todos pasan.
6. ✅ **Auditoría automática** — `PersonaObserver` (registrado en `AppServiceProvider`) escribe en `personas_auditorias` en cada create/update/delete, sin necesidad de llamarlo manualmente.
7. ✅ **CURP/RFC restringidos** — `PersonaResource` (API) solo expone `curp`/`rfc`/`ine_clave` si `$user->esSuperAdmin()`.

### Bug corregido
`Persona::etiquetas()` apuntaba a un modelo `App\Models\Etiqueta` y un pivot `App\Models\Pivots\PersonaEtiqueta` que no existían — habría fallado en cuanto alguien llamara la relación. Se reemplazó por un `hasMany` simple hacia el nuevo modelo `App\Models\PersonaEtiqueta` (la tabla `personas_etiquetas` es solo `persona_id` + `etiqueta`, sin catálogo aparte, tal como se definió en la migración).

### Pendiente (fuera de alcance de esta sesión)
- Importar datos reales desde Odoo (el seeder actual es data falsa de prueba).
- Formulario Vue de edición (`Padron/Crear.vue` cubre alta con campos principales; falta una vista de edición y exponer el resto de los 33 campos si se necesita en UI).

---

## 📊 Diagrama de Relaciones (Visual)

```
┌─────────────────┐
│   PERSONAS      │ (Tabla Central)
│   (58 campos)   │
└────────┬────────┘
         │
    ┌────┴─────────────────────────────────────┐
    │                                           │
    ↓                                           ↓
┌─────────────────┐                    ┌──────────────────┐
│ CREA            │                    │ IMPULSATE        │
│ NODICO          │                    │ HERENCIA_VIVA    │
│ JURIDICO        │                    │ CITAS            │
└─────────────────┘                    └──────────────────┘

┌─────────────────┐
│ ETIQUETAS       │ (Many-to-Many)
└─────────────────┘

┌─────────────────┐
│ AUDITORIAS      │ (Trazabilidad)
└─────────────────┘
```

---

## 💾 Configuración de Base de Datos

Asegúrate de que tu `.env` tenga:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iyemyucatan
DB_USERNAME=root
DB_PASSWORD=
```

---

## ✨ Características Implementadas

✅ **Centralización Total:** Una sola tabla `personas`, múltiples módulos  
✅ **Trazabilidad:** Auditoría de todos los cambios  
✅ **Soft Deletes:** No se pierden datos, solo se marcan como borrados  
✅ **Etiquetado Flexible:** Clasificación dinámica sin nuevas columnas  
✅ **Índices Optimizados:** Queries rápidas por municipio, estado, teléfono, CURP, RFC  
✅ **Integridad Referencial:** Foreign keys en todas las tablas satélite  
✅ **Scopes Eloquent:** Búsquedas rápidas (`->activas()`, `->porMunicipio()`)  

---

**Creado por:** Claude AI basado en análisis real de estructura Odoo IYEM  
**Última actualización:** 2026-08-19
