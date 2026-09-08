# Reservas IESNH: prototipo actual y reconstrucción con Livewire

## Propósito

Este documento registra el trabajo realizado sobre el prototipo de reservas de espacios del IESNH y define una estrategia para reconstruir el sistema sobre el starter kit oficial de Laravel con Livewire y Flux UI, sin perder reglas de negocio, seguridad, datos ni pruebas.

La migración todavía no fue ejecutada. Este archivo sirve como inventario, referencia funcional y guía de traspaso.

## Estado verificado del proyecto

- Laravel `13.30.1` sobre PHP 8.4.
- Pinion UI `0.11.0`.
- Pest `4.7.0`.
- Tailwind CSS v4, daisyUI v5, Alpine.js y Vite 8.
- Base local SQLite.
- 33 rutas de aplicación.
- 25 vistas Blade analizadas por `php artisan ui:lint`.
- Build frontend de producción correcto mediante `npm run build`.
- Suite completa: 18 pruebas aprobadas y 34 aserciones.
- Login, dashboard y calendario revisados en un navegador real.
- El directorio de trabajo no contiene actualmente un repositorio Git inicializado. Antes de migrar debe crearse un punto de recuperación mediante Git o una copia completa del proyecto.

## Objetivo funcional implementado

El sistema administra reservas para tres espacios institucionales:

1. Sala Auditorio.
2. Gabinete de Informática.
3. Sala de Streaming.

Existen dos roles:

- `Administrador`: revisa solicitudes, administra espacios, profesores y reglas generales.
- `Profesor`: crea, consulta, modifica y cancela sus propias solicitudes dentro de las reglas permitidas.

Los estados de una reserva son:

- Pendiente.
- Aprobada.
- Rechazada.
- Cancelada.

## Instalación e incidencias resueltas

- Se integró `sparrowhawk-labs/pinion-ui` mediante Composer y se confirmó la versión `0.11.0`.
- Se alinearon Tailwind v4, daisyUI v5, Alpine y Vite con las dependencias requeridas por Pinion.
- Se importó `pinion-ui.css` después de Tailwind en `resources/css/app.css`.
- Se configuró Alpine con los plugins `collapse` y `focus` necesarios para los componentes interactivos actuales.
- Se reemplazó por completo la vista de bienvenida original de Laravel, que generaba infracciones del vocabulario de clases de Pinion.
- Se corrigió una diferencia entre la documentación y los archivos distribuidos del componente de paginación: en esta versión el componente disponible es `<x-pagination.full>`.
- Se eliminaron clases de componentes daisyUI excluidas y colores fijos hasta obtener un lint limpio.
- Se corrigieron relaciones no cargadas antes de crear notificaciones, evitando lazy loading accidental.
- Se añadieron bloqueos y una segunda validación en aprobación, rechazo y cancelación para evitar resolver dos veces una solicitud.
- Se corrigió una política que permitía a profesores abrir el listado administrativo de espacios.
- Se configuró la aplicación en español y con zona horaria `America/Argentina/Buenos_Aires`.
- El mensaje de Xdebug sobre `C:/wamp64/logs/xdebug.log` sin acceso apareció durante los comandos, pero no afectó migraciones, build ni pruebas.

## Funcionalidades terminadas

### Autenticación y usuarios

- Inicio y cierre de sesión.
- Limitación de intentos de acceso.
- Middleware que impide operar a cuentas inactivas.
- Registro público deshabilitado por decisión funcional.
- Gestión administrativa de profesores.
- Contraseñas almacenadas mediante el cast seguro de Laravel.
- Separación de permisos mediante políticas.

### Reservas

- Creación de solicitudes por profesores.
- Edición de solicitudes pendientes propias.
- Consulta individual y listado según rol.
- Aprobación y rechazo por administradores.
- Cancelación según rol, estado y configuración.
- Registro de cada cambio en el historial.
- Notificaciones persistidas en base de datos.
- Calendario mensual con filtro por espacio.

### Reglas de negocio

- La sala y el profesor deben estar activos.
- La hora final debe ser posterior a la inicial.
- Duración mínima y máxima configurables.
- Anticipación mínima y máxima configurables.
- Días habilitados configurables.
- Franja horaria configurable.
- Validación de capacidad del espacio.
- Máximo mensual de reservas por profesor.
- Configuración global con estructura preparada para excepciones por sala.
- Las solicitudes pendientes pueden compartir horario.
- Las reservas aprobadas no pueden superponerse.
- Dos reservas pueden compartir exactamente un límite horario: una puede terminar a las 11:00 y otra comenzar a las 11:00.

La regla de superposición utilizada es:

```text
inicio_existente < fin_nuevo AND fin_existente > inicio_nuevo
```

La aprobación vuelve a verificar la disponibilidad dentro de una transacción, bloqueando la sala y la reserva para reducir condiciones de carrera.

### Administración

- Bandeja de solicitudes pendientes.
- Gestión de espacios sin eliminación destructiva.
- Gestión de profesores sin eliminación destructiva.
- Configuración general de límites, disponibilidad y cancelación.
- Listado y lectura de notificaciones.

## Arquitectura implementada

### Dominio

- `app/Enums/UserRole.php`
- `app/Enums/ReservaEstado.php`
- `app/Models/Sala.php`
- `app/Models/Reserva.php`
- `app/Models/ReservaConfiguracion.php`
- `app/Models/ReservaHistorial.php`
- `app/Models/User.php`

### Casos de uso

La lógica que modifica reservas se separó de los controladores:

- `app/Actions/Reservas/CreateReserva.php`
- `app/Actions/Reservas/UpdateReserva.php`
- `app/Actions/Reservas/ApproveReserva.php`
- `app/Actions/Reservas/RejectReserva.php`
- `app/Actions/Reservas/CancelReserva.php`

Las validaciones de dominio compartidas se encuentran en:

- `app/Services/ReservaRuleService.php`

Esta separación debe mantenerse durante la migración. Los componentes Livewire deben invocar estas acciones; no deben duplicar la lógica de negocio dentro del componente.

### Seguridad y validación

- Form Requests para los endpoints Blade actuales.
- Políticas para reservas, salas, profesores y configuración.
- Middleware `EnsureUserIsActive`.
- Autorización nuevamente aplicada en las operaciones sensibles.
- Rate limiter para el inicio de sesión.

### Persistencia

Se agregaron migraciones para:

- Salas.
- Reservas.
- Configuración de reservas.
- Historial de reservas.
- Campos de rol y estado del usuario.
- Notificaciones de base de datos.

También existen factories y seeders para datos de desarrollo y pruebas.

## Interfaz del prototipo actual con Pinion UI

Esta sección es un registro del prototipo y una referencia funcional. Pinion UI no será trasladado al sistema nuevo. El diseño actual usa:

```html
<html data-theme="education" data-tune="corporate">
```

Componentes utilizados, entre otros:

- `<x-button>`
- `<x-card>`
- `<x-stat>`
- `<x-input>`
- `<x-select>`
- `<x-textarea>`
- `<x-checkbox>`
- `<x-sidebar>`
- `<x-menu-item>`
- `<x-avatar>`
- `<x-badge>`
- `<x-alert>`
- `<x-table-scroll>`
- `<x-pagination.full>`

La aplicación sigue el vocabulario de Pinion UI:

- Componentes Pinion como primera opción.
- Tailwind v4 para composición, grid, flexbox, tipografía y responsive.
- Colores semánticos de daisyUI, como `bg-primary`, `bg-base-200`, `text-error` y `border-base-300`.
- Tokens de tune para radios, tamaños y espaciado.
- Sin clases de componente de daisyUI como `.btn`, `.card` o `.input`.
- Sin paletas fijas o colores hexadecimales en la interfaz.

Las pantallas realizadas son:

- Login.
- Layout autenticado y navegación adaptable.
- Dashboard por rol.
- Calendario mensual.
- Listado, creación, edición y detalle de reservas.
- Historial de una reserva.
- Solicitudes pendientes.
- Gestión de espacios.
- Gestión de profesores.
- Configuración.
- Notificaciones.

## Análisis de PinionUIDemo

Se revisó el repositorio `CodingWithLuis/PinionUIDemo` como referencia visual.

Referencia: <https://github.com/CodingWithLuis/PinionUIDemo>

Patrones valiosos encontrados:

- Header fijo con navegación horizontal en escritorio.
- Sidebar móvil mediante `<x-sidebar>`.
- Contenido centrado con `max-w-7xl`.
- Tema `analytics` y tune `soft`.
- Selector de tema claro y oscuro.
- Íconos mediante `<x-i>`.
- Dropdown de usuario.
- Estadísticas con icono, tendencia y descripción.
- Espaciado amplio con tokens como `py-4xl`, `mb-2xl` y `gap-lg`.
- Grilla principal de dos tercios más una columna lateral.
- Alpine para búsqueda y filtrado local.
- `<x-notification-system>` para avisos temporales.

La referencia es una demostración visual con información simulada. No contiene un dominio real, autorización ni persistencia comparable con Reservas IESNH. Debemos adoptar sus patrones visuales, no su arquitectura de datos.

Mejoras visuales candidatas para la siguiente versión:

- Incorporar íconos en navegación, acciones y estadísticas.
- Agregar selector de apariencia clara y oscura del starter kit.
- Usar un dropdown de usuario.
- Incorporar notificaciones temporales con Flux.
- Aumentar el ritmo vertical de títulos y secciones.
- Mejorar estados mediante badges con punto.
- Evaluar el layout superior del demo para profesores y conservar sidebar para administradores.

## Starter kit oficial seleccionado

El objetivo de la siguiente etapa es usar el starter kit oficial de Laravel para Livewire.

Al momento de escribir este documento, el kit oficial utiliza:

- Laravel 13.
- Livewire 4.
- Tailwind CSS v4.
- Flux UI.
- Autenticación integrada de Laravel.
- Gestión de perfil, contraseña y apariencia.
- Layout de aplicación con variantes sidebar o header.
- Layouts de autenticación simple, card o split.

El starter kit moderno es una aplicación base completa y no un paquete que publique archivos sobre una aplicación existente. Por esa razón no se recomienda instalarlo directamente encima de este proyecto.

Fuentes oficiales:

- <https://laravel.com/starter-kits>
- <https://github.com/laravel/docs/blob/13.x/starter-kits.md>
- <https://github.com/laravel/livewire-starter-kit>

## Decisión definitiva: Livewire con Flux UI

El sistema nuevo utilizará el stack completo provisto por el starter kit oficial:

1. Laravel 13 como framework.
2. Livewire 4 para páginas y comportamiento reactivo.
3. Flux UI como biblioteca de componentes.
4. Tailwind CSS v4 para composición y personalización.
5. Alpine provisto por Livewire cuando sea necesario complementar una interacción.
6. Pest para pruebas de dominio, HTTP y componentes Livewire.

Pinion UI y daisyUI pertenecen únicamente al prototipo actual y no se instalarán en la aplicación nueva. Tampoco se mezclarán componentes Pinion y Flux. Esto reduce dependencias, evita dos sistemas visuales simultáneos y mantiene la interfaz alineada con el ecosistema oficial de Livewire.

Se comenzará con los componentes gratuitos incluidos por Flux. Flux Pro solamente se evaluará si una necesidad concreta del sistema requiere un componente exclusivo de esa edición y existe una licencia aprobada.

## Advertencia sobre Alpine.js

Livewire incluye Alpine. El proyecto actual inicializa Alpine manualmente desde `resources/js/app.js` con los plugins `collapse` y `focus`.

Durante la reconstrucción se debe evitar cargar dos instancias de Alpine. Se conservará la configuración JavaScript del starter kit y solamente se registrarán plugins adicionales cuando una interacción concreta lo requiera.

No se debe copiar el archivo JavaScript actual sin esta revisión.

## Estrategia de migración

### Fase 0: asegurar un punto de recuperación

1. Inicializar Git o crear una copia completa del proyecto.
2. Confirmar que `database/database.sqlite` esté respaldada si contiene datos que deban conservarse.
3. Registrar las versiones instaladas.
4. Volver a ejecutar pruebas, lint y build antes de mover archivos.

Criterio de salida:

- Existe una versión recuperable del sistema actual.
- Las 18 pruebas continúan pasando.

### Fase 1: crear una aplicación nueva desde el starter kit

Crear el nuevo proyecto en un directorio paralelo, no dentro del proyecto actual:

```powershell
laravel new reservas-iesnh-livewire
```

En el instalador seleccionar:

- Starter kit: Livewire.
- Autenticación: Laravel integrada, no WorkOS.
- Base de datos inicial: SQLite para desarrollo.
- Testing: Pest.

Luego:

```powershell
cd reservas-iesnh-livewire
npm install
npm run build
composer run dev
```

No ejecutar todavía migraciones contra una base de producción.

### Fase 2: configurar autenticación institucional

1. Conservar login, recuperación de contraseña, confirmación y gestión de perfil del starter kit.
2. Desactivar el registro público.
3. Incorporar `rol`, `apellido` y `activo` al modelo de usuario.
4. Reincorporar `EnsureUserIsActive`.
5. Mantener el rate limiter de login.
6. Decidir si el correo debe estar verificado antes de reservar.
7. Mantener la creación de profesores como función exclusiva del administrador.

Criterio de salida:

- Un administrador y un profesor activo pueden ingresar.
- Un usuario inactivo queda fuera del sistema.
- No existe una ruta pública funcional de registro.

### Fase 3: trasladar el dominio sin modificarlo innecesariamente

Copiar y adaptar en este orden:

1. Enums.
2. Migraciones propias.
3. Modelos y relaciones.
4. Factories y seeders.
5. Políticas.
6. Servicio de reglas.
7. Acciones de reservas.
8. Notificaciones.
9. Middleware.

Archivos que deberían conservar casi toda su implementación:

- `app/Enums/**`
- `app/Models/Reserva*.php`
- `app/Models/Sala.php`
- `app/Actions/Reservas/**`
- `app/Services/ReservaRuleService.php`
- `app/Policies/**`
- `app/Notifications/**`
- `database/factories/**`
- `database/seeders/**`
- Migraciones con fecha `2026_09_02_*`

El modelo `User` debe fusionarse cuidadosamente con el modelo provisto por el starter kit. No debe reemplazarse de manera directa porque el starter agrega capacidades de autenticación, perfil y posiblemente verificación o doble factor.

### Fase 4: adaptar Flux UI a la identidad del IESNH

1. Mantener la instalación de Flux incluida en el starter kit.
2. Seleccionar el layout sidebar como base administrativa.
3. Definir logo, nombre institucional, tipografía y paleta semántica.
4. Configurar modo claro y oscuro con el sistema de apariencia del starter.
5. Reutilizar componentes `<flux:*>` antes de construir componentes propios.
6. Publicar o personalizar componentes Flux solamente cuando sea necesario.
7. Mantener Tailwind v4 para grid, responsive y composición de página.
8. Evitar incorporar daisyUI o Pinion UI al nuevo proyecto.

### Fase 5: transformar pantallas en componentes Livewire

Mapa propuesto:

| Pantalla actual | Componente Livewire propuesto |
|---|---|
| Dashboard | `Dashboard` |
| Calendario | `Calendar/Index` |
| Listado de reservas | `Reservations/Index` |
| Crear y editar reserva | `Reservations/Form` |
| Detalle e historial | `Reservations/Show` |
| Solicitudes pendientes | `Admin/Reservations/Pending` |
| Gestión de espacios | `Admin/Rooms/Index` y `Admin/Rooms/Form` |
| Gestión de profesores | `Admin/Teachers/Index` y `Admin/Teachers/Form` |
| Configuración | `Admin/ReservationSettings/Edit` |
| Notificaciones | `Notifications/Index` |

Responsabilidades sugeridas:

- Los filtros, paginación, modales y estado de formularios pertenecen al componente Livewire.
- Las reglas de negocio continúan en `ReservaRuleService` y las acciones.
- Las políticas se invocan desde cada acción pública del componente.
- Los componentes no deben construir consultas dentro de la vista Blade.
- Las consultas deben cargar relaciones explícitamente para evitar N+1.
- Las acciones sensibles deben conservar transacciones y bloqueos.

### Fase 6: navegación reactiva

Incorporar progresivamente:

- `wire:navigate` en navegación interna.
- Filtros de calendario y reservas mediante propiedades enlazadas.
- Paginación Livewire.
- Modales de aprobación, rechazo y cancelación.
- Estados de carga mediante `wire:loading`.
- Deshabilitación de acciones mientras una petición esté en curso.
- Actualización del contador de notificaciones.

Los modales deben solicitar motivo de rechazo y confirmar cancelaciones, pero la autorización y la validación final siempre deben permanecer en el servidor.

### Fase 7: migrar o conservar los datos

Hay dos escenarios:

#### Desarrollo sin datos importantes

Ejecutar migraciones desde cero y volver a sembrar:

```powershell
php artisan migrate:fresh --seed
```

#### Base con datos que deben conservarse

1. No ejecutar `migrate:fresh`.
2. Comparar las migraciones base del starter kit con la tabla `users` existente.
3. Preparar migraciones incrementales.
4. Probar la migración sobre una copia de la base.
5. Verificar usuarios, reservas, historiales y notificaciones.
6. Realizar backup antes del cambio definitivo.

### Fase 8: pruebas

Conservar las pruebas actuales como red de seguridad y agregar pruebas de componentes Livewire.

Cobertura mínima:

- Acceso de invitados.
- Cuenta inactiva.
- Creación de solicitud e historial.
- Validaciones de fecha, duración y capacidad.
- Límite mensual.
- Superposición aprobada.
- Horarios contiguos permitidos.
- Solicitudes pendientes superpuestas permitidas.
- Edición propia.
- Acceso ajeno bloqueado.
- Administración de espacios restringida.
- Aprobación y rechazo.
- Cancelación según configuración.
- Notificaciones.
- Filtros y paginación Livewire.
- Estados de carga y errores de validación visibles.

Comandos de verificación:

```powershell
php artisan test --compact
vendor/bin/pint --format agent
npm run build
```

## Archivos que no deben copiarse directamente

- `resources/views/layouts/**`: reemplazarlos por los layouts Livewire y Flux del starter.
- `resources/views/components/layouts/**`: revisar convenciones de Livewire 4.
- `resources/views/auth/**`: el starter kit aporta un flujo de autenticación más completo.
- `app/Http/Controllers/Auth/**`: fusionar con Fortify y el flujo del starter.
- `app/Http/Requests/Auth/**`: revisar antes de conservar.
- `resources/js/app.js`: riesgo de duplicar Alpine.
- `routes/web.php`: reconstruir las rutas utilizando páginas Livewire y las rutas de autenticación del starter.
- `app/Models/User.php`: fusionar, no reemplazar.
- Migraciones base `0001_*`: utilizar las del nuevo starter y adaptar los campos adicionales mediante migraciones propias.

## Decisiones pendientes antes de ejecutar la migración

1. Elegir layout principal: sidebar, header o uno por rol.
2. Definir si se habilita recuperación de contraseña por correo.
3. Definir si el correo institucional requiere verificación.
4. Definir si se habilita autenticación de dos factores.
5. Confirmar si existen datos reales que deban conservarse.
6. Definir si las notificaciones seguirán siendo solo internas o también por correo.
7. Confirmar si los nombres de componentes y rutas permanecen en español o pasan a inglés internamente.
8. Determinar si los componentes gratuitos de Flux cubren todas las pantallas o si se evaluará Flux Pro.

## Recomendación de layout

- Administrador: sidebar, porque tiene más módulos y cambia frecuentemente entre solicitudes, espacios, profesores y configuración.
- Profesor: header o sidebar simplificado con dashboard, calendario, reservas y notificaciones.
- Autenticación: layout card o split del starter con componentes Flux.

## Criterios de aceptación de la migración

La migración se considera terminada cuando:

- Todas las funcionalidades actuales existen en el nuevo proyecto.
- No existe registro público.
- Las cuentas inactivas no pueden operar.
- Las políticas protegen acciones y páginas Livewire.
- La regla de superposición conserva exactamente el comportamiento actual.
- La aprobación es transaccional y vuelve a validar disponibilidad.
- Todos los cambios generan historial.
- Las notificaciones continúan funcionando.
- El diseño usa Flux UI de forma consistente.
- No se cargan dos instancias de Alpine.
- No hay consultas N+1 en listados o calendarios.
- `npm run build` termina correctamente.
- La suite anterior y las nuevas pruebas Livewire están en verde.
- La aplicación fue comprobada en escritorio y móvil.

## Datos locales de demostración

Los seeders actuales crean, fuera de producción:

- Administrador: `admin@iesnh.edu.ar`
- Profesora: `laura@iesnh.edu.ar`
- Profesor: `martin@iesnh.edu.ar`
- Contraseña común: `Reservas123!`

Estas credenciales son únicamente para desarrollo. No deben migrarse a producción.

## Resultado esperado

La aplicación final combinará:

- La base oficial y mantenible del starter kit de Laravel.
- Livewire 4 para interacción reactiva sin una SPA JavaScript.
- Flux UI como biblioteca visual integrada con Livewire.
- Las reglas de negocio, acciones, políticas y pruebas ya desarrolladas.
- Autenticación y perfil más completos.
- Una experiencia responsive con menos recargas y mejor respuesta durante filtros, formularios y aprobaciones.
