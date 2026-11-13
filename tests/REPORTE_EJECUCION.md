# Reporte de ejecución de pruebas

**Proyecto:** Rumbo al Cielo  
**Fecha de ejecución:** 13/11/2026  
**Entorno de ejecución:** PHP CLI 8.4.24  
**Base de datos:** No utilizada en estas pruebas unitarias; se emplearon dobles de repositorio en memoria.

## Resultado

| Suite | Resultado |
|---|---:|
| `tests/run_unit_tests.php` | 9 correctas, 0 fallidas |
| `tests/run_smoke.php` | Correcta |
| `tests/run_migration_smoke.php` | Correcta |
| Sintaxis PHP de los archivos del proyecto | Sin errores de sintaxis |

## Casos unitarios cubiertos

1. Usuarios: autenticación, credenciales incorrectas y registro/validación de empleados.
2. Familiares: registro, titular autorizado, parentesco permitido y propiedad del familiar.
3. Pensiones: validación de mes y tipo de cuenta, inscripción mensual y generación de reservas.
4. Platos: alta, duplicidad, activar/desactivar, filtros y restricción por menú vigente.
5. Menú: publicación de menú completo y rechazo de selección incompleta.
6. Reservas: rechazo de fecha pasada, creación de reserva y prevención de duplicados.
7. Reportes: aplicación del mínimo de pensión, suma de familiares y guardado del resultado.

## Comandos para reproducir

Ejecutar desde la raíz del proyecto:

```bash
php tests/run_unit_tests.php
php tests/run_smoke.php
php tests/run_migration_smoke.php
```

O ejecutar el conjunto con Composer:

```bash
composer test
```

## Alcance y limitaciones

Las pruebas unitarias aíslan los casos de uso y servicios mediante repositorios falsos en memoria. Por ello no modifican datos reales ni requieren configurar MySQL. La conexión y las consultas reales a MySQL deben verificarse por separado en XAMPP mediante pruebas de integración y recorridos funcionales de cada módulo.
