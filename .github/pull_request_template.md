## Qué cambia

<!-- Una o dos frases. Qué hace este PR y por qué. Si cierra un issue: "Cierra #12". -->

## Cómo probarlo

<!-- Pasos concretos para que quien revise lo reproduzca. Ejemplo:
1. `php artisan migrate:fresh --seed`
2. Entrar como tester@iyemyucatan.com
3. Ir a /padron y buscar "Balam"
4. Verificar que la CURP se ve enmascarada
-->

1.
2.
3.

## Capturas

> Obligatorias si tocaste la interfaz. Sin capturas de móvil el PR no se aprueba.

| Dispositivo | Captura |
|---|---|
| iPhone 14 (390×844) | |
| iPad Air vertical (820×1180) | |
| Escritorio (1440×900) | |

## Checklist

- [ ] La rama sale de `develop` y apunta a `develop`.
- [ ] Los commits siguen la convención de `CONTRIBUTING.md`.
- [ ] `php artisan test` pasa en local.
- [ ] `./vendor/bin/pint --test` no reporta cambios pendientes.
- [ ] `npm run build` compila sin errores.
- [ ] Sin scroll horizontal en 390×844 ni en 820×1180.
- [ ] Áreas táctiles de al menos 44×44 px en los controles nuevos.
- [ ] No quedaron `dd()`, `dump()`, `var_dump()` ni `console.log`.
- [ ] No hay credenciales ni tokens escritos a mano.
- [ ] Si hay migración nueva, `php artisan migrate:rollback` funciona.
- [ ] Si toqué el padrón o la API, agregué o actualicé una prueba.
- [ ] Actualicé `CHANGELOG.md` en la sección `[Sin publicar]`.

## Riesgos y notas para quien revise

<!-- Qué se puede romper, qué dejaste pendiente, qué decisión tomaste que
     no es obvia. Si no hay nada, escribe "Ninguno". -->
