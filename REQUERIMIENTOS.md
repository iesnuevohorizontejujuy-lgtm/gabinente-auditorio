# Requerimientos del Sistema de Reservas — IESNH

## 1. Descripción general

El sistema administra la reserva de espacios institucionales del Instituto de Educación Superior Nuevo Horizonte (IESNH). Permite a profesores solicitar el uso de salas y a administradores gestionar, aprobar o rechazar esas solicitudes.

### Espacios institucionales

| Espacio | Capacidad | Equipamiento destacado |
|---|---|---|
| Gabinete de Informática | 30 terminales PC (40 personas) | Gigabit LAN, proyector 4K, climatización |
| Auditorio Institucional | 120 butacas (250 personas) | Láser 4K, audio 4 mics inalámbricos, cabina técnica |
| Sala de Streaming y Podcasting | 10 personas | Cámaras PTZ 4K, micrófonos Shure SM7B, consola RØDECaster |

---

## 2. Roles y permisos

### Administrador

- Revisar y resolver solicitudes pendientes (aprobar / rechazar con motivo).
- Gestionar espacios: crear, editar, activar, desactivar. Sin eliminación destructiva.
- Gestionar profesores: crear, editar, activar, desactivar. Sin eliminación destructiva.
- Configurar reglas generales de reservas (duración, anticipación, días, franja horaria, cancelación, límite mensual).
- Consultar todas las reservas y el calendario completo.
- Acceder a notificaciones y bandeja de solicitudes.
- Aprobar o rechazar masivamente reservas seleccionadas.

### Profesor

- Crear solicitudes de reserva dentro de las reglas permitidas.
- Editar solicitudes propias en estado **Pendiente**.
- Cancelar solicitudes propias según configuración vigente.
- Consultar sus propias reservas y su historial.
- Ver el calendario de espacios.
- Recibir notificaciones sobre el estado de sus solicitudes.

### Restricciones de acceso

- No existe registro público; los profesores son creados exclusivamente por un administrador.
- Las cuentas inactivas no pueden operar en el sistema.
- Las políticas protegen cada acción y pantalla según rol.

---

## 3. Estados de una reserva

| Estado | Descripción | Badge |
|---|---|---|
| **Pendiente** | Solicitud creada, esperando revisión del administrador | Ámbar `#F59E0B` — fondo `#FFFBEB`, texto `#92400E`, ícono: reloj |
| **Aprobada** | Solicitud confirmada por un administrador | Esmeralda `#10B981` — fondo `#ECFDF5`, texto `#065F46`, ícono: check |
| **Rechazada** | Solicitud denegada con motivo registrado | Carmesí `#EF4444` — fondo `#FEF2F2`, texto `#991B1B`, ícono: circle-slash |
| **Cancelada** | Solicitud retirada por el profesor o el administrador | Gris `#6B7280` — fondo `#F3F4F6`, texto `#374151`, ícono: x-circle |

---

## 4. Reglas de negocio

### Validaciones de solicitud

- La sala debe estar activa.
- El profesor debe estar activo.
- La hora final debe ser posterior a la hora inicial.
- Duración mínima y máxima configurables.
- Anticipación mínima y máxima configurables.
- Solo días habilitados según configuración.
- Dentro de la franja horaria configurada.
- Capacidad del espacio validada contra asistentes declarados.
- Máximo mensual de reservas por profesor.

### Superposición horaria

- Las solicitudes **pendientes** pueden compartir horario entre sí.
- Las reservas **aprobadas** no pueden superponerse.
- Dos reservas pueden compartir exactamente un límite horario (una termina a las 11:00 y otra empieza a las 11:00).

Regla de superposición:

```
inicio_existente < fin_nuevo AND fin_existente > inicio_nuevo
```

### Aprobación transaccional

- Al aprobar, se vuelve a verificar disponibilidad dentro de una transacción con bloqueo de sala y reserva.
- Esto reduce condiciones de carrera cuando dos administradores resuelven simultáneamente.

### Historial

- Cada cambio de estado genera un registro en el historial de la reserva.
- El historial incluye: estado anterior, estado nuevo, usuario que realizó el cambio, fecha y motivo cuando aplica.

### Cancelación

- Configuración define el plazo permitido para cancelar.
- Profesores pueden cancelar solicitudes propias dentro del plazo.
- Administradores pueden cancelar cualquier reserva.

### Configuración global

- Estructura preparada para excepciones por sala.
- Parámetros: duración mín/máx, anticipación mín/máx, días habilitados, franja horaria, límite mensual, plazo de cancelación.

---

## 5. Requerimientos funcionales por pantalla

### RF-01 · Login

- Inicio y cierre de sesión.
- Limitación de intentos de acceso (rate limiter).
- Sin registro público.
- Layout tipo card o split con componentes Flux.

### RF-02 · Dashboard administrativo

- Saludo personalizado con nombre y rol.
- Fecha actual con selector.
- 4 tarjetas de estadísticas:
  - Solicitudes pendientes (con urgencia).
  - Reservas programadas para hoy.
  - Espacios ocupados (con porcentaje y nivel de demanda).
  - Total de reservas del mes (con tendencia vs mes anterior).
- Solicitudes pendientes de aprobación con acciones rápidas (Revisar / Rechazar / Aprobar).
- Detalle de cada solicitud: profesor, departamento, espacio, fecha, horario, duración, motivo académico.
- Agenda del día: timeline vertical con reservas en curso y aprobadas.
- Disponibilidad en vivo de los 3 espacios (Ocupado / Disponible).
- Botón "Nueva reserva".
- Índice de aprobación mensual.

### RF-03 · Dashboard del profesor

- Resumen de sus reservas activas y pendientes.
- Acceso rápido a crear nueva reserva.
- Próximas reservas confirmadas.
- Notificaciones recientes.

### RF-04 · Calendario de espacios

- Vista semanal con bloques por hora (divisiones de 30 min).
- Selector de vista: Mes / Semana / Día.
- Navegación: anterior, hoy, siguiente.
- Filtro por espacio (Todos / Gabinete / Auditorio / Streaming).
- Filtro por estado (Pendientes / Aprobadas / Rechazadas / Canceladas).
- Buscador por responsable o aula.
- Cada bloque muestra: horario, título truncado, espacio, profesor, estado con badge.
- Indicador de hora actual (línea roja).
- Leyenda de estados al pie.
- Ocupación por espacio al pie (barras de progreso con porcentaje).
- Botón "+ Nueva reserva".
- Formato 24 horas.

### RF-05 · Gestión integral de reservas (Administrador)

- Tabla con columnas: código, responsable (avatar + nombre + departamento), espacio, fecha y horario, motivo y asistentes, fecha de solicitud.
- Tabs por estado: Todas (con count), Pendientes, Aprobadas, Rechazadas, Canceladas/Historial.
- Filtros: búsqueda por texto, espacio, estado, docente, rango de fechas.
- Botones: Aplicar filtros, Limpiar filtros.
- Selección múltiple con checkbox para aprobación/rechazo masivo.
- Paginación con selector de filas por página (10, 25, 50).
- Estadísticas al pie: tasa de aprobación global, espacio más solicitado, tiempo medio de resolución.
- Acciones superiores: sincronizar calendario, descargar reporte (CSV/PDF), nueva reserva directa.
- Última actualización automática.

### RF-06 · Listado de reservas del profesor

- Tabla con sus reservas propias.
- Filtro por estado y fecha.
- Acciones: ver detalle, editar (si pendiente), cancelar (si permitido).

### RF-07 · Crear reserva

- Selección de espacio (con estado de disponibilidad).
- Fecha con validación de días habilitados.
- Hora inicio y hora fin con validación de franja.
- Título de la actividad académica.
- Descripción / metodología.
- Cantidad de asistentes (validada contra capacidad).
- Motivo académico.
- Requerimientos técnicos (equipamiento solicitado).

### RF-08 · Editar reserva

- Solo para solicitudes propias en estado Pendiente.
- Mismos campos que la creación.
- Re-validación completa al guardar.

### RF-09 · Revisión administrativa de solicitud

- Código de reserva y estado (Pendiente de Resolución).
- Tiempo restante de respuesta.
- **Perfil del solicitante**: nombre, departamento, email, teléfono, historial semestral, penalizaciones.
- **Espacio y cronograma**: nombre del espacio, ubicación, aforo, fecha, franja horaria, duración.
- **Título y descripción** de la actividad.
- **Aforo previsto**: barra de progreso contra capacidad (ej: 32/40 = 80%).
- **Requerimientos técnicos**: chips con equipamiento solicitado, soporte técnico.
- **Observación especial** del docente.
- **Diagnóstico de agenda**: si hay conflictos de horario o solapamiento, ventana de limpieza.
- **Cronograma de ocupación del día**: timeline lateral con franjas ocupadas, la solicitud evaluada resaltada, intervalos libres.
- **Campo de resolución**: textarea para dictamen u observaciones del administrador.
- Opciones: notificar vía correo, firma digital.
- Acciones: Aprobar Solicitud de Reserva / Rechazar con Fundamento / Editar Parámetros.
- Enlace al historial completo de reservas del espacio.

### RF-10 · Gestión de espacios institucionales

- Cards por espacio con:
  - Fotografía institucional.
  - Estado (Disponible / Mantenimiento Preventivo).
  - ID interno y tipo de red (En Línea / Magno / Aislada).
  - Nombre, ubicación (edificio, piso, aula).
  - Chips de equipamiento (capacidad, conectividad, proyección, audio, ambiente).
  - Horario operativo.
  - Próximas reservas (count semanal).
  - Acciones: Editar, Ver calendario, Bloquear por mantenimiento.
- Estadísticas superiores: espacios activos, en mantenimiento, tasa de ocupación.
- Botón "+ Agregar nuevo espacio".
- Configuración rápida de normativas al pie:
  - Antelación mínima.
  - Límite de cancelación.
  - Límite semanal por docente.
  - Guardar parámetros / Restablecer valores.

### RF-11 · Crear nuevo espacio

Formulario en pasos:

1. **Información general**: nombre, código institucional, tipo de espacio, edificio, piso, aula, descripción.
2. **Aforo y normativas**: puestos activos, aforo con seguridad ampliada, franja horaria, antelación mínima, margen de sanitización, perfiles autorizados.
3. **Inventario tecnológico**: checkboxes de equipamiento disponible (PCs, red, proyector, audio, climatización, cámaras). Opción para agregar ítems.
4. **Fotografía oficial**: drag-and-drop para imagen institucional (PNG, JPG, WEBP hasta 10 MB, resolución 1920×1080).

- Preview en tiempo real del espacio (sidebar derecha).
- Estado operativo inicial: Habilitado y Disponible / En Calibración Técnica / Inactivo.
- Checklist de requisitos de apertura.
- Acciones: Cancelar / Guardar Borrador / Habilitar y Publicar Espacio.

### RF-12 · Editar espacio

- Tabs: Datos Generales, Capacidad y Horarios, Inventario Tecnológico, Normativas.
- Sidebar derecha con:
  - Métricas y próxima ocupación (tasa semanal, próxima sesión).
  - Historial completo de reservas.
  - Estado operativo (Disponible / Mantenimiento / Fuera de servicio) con nota informativa.
  - Trazabilidad y auditoría (última modificación, creación, UUID).
- Equipamiento como tags removibles con opción de añadir.
- Toggle para solicitud de software complementario.
- Fotografía con opción de reemplazar o eliminar.
- Acciones: Cancelar / Guardar Modificaciones / Eliminar Espacio / Bloquear temporalmente / Ver calendario.

### RF-13 · Gestión de profesores

- Listado de profesores con datos, rol, estado (activo/inactivo).
- Crear y editar profesores.
- Activar/desactivar sin eliminación destructiva.

### RF-14 · Configuración general

- Parámetros globales de reservas.
- Estructura preparada para excepciones por sala.
- Guardado con efecto inmediato sobre nuevas solicitudes.

### RF-15 · Notificaciones

- Listado de notificaciones persistidas en base de datos.
- Marcar como leídas.
- Contador en la navegación (badge con punto rojo).
- Generadas automáticamente al cambiar el estado de una reserva.

---

## 6. Requerimientos no funcionales

### Autenticación y seguridad

- Contraseñas almacenadas con el cast seguro de Laravel (bcrypt).
- Rate limiter en login.
- Middleware `EnsureUserIsActive` para bloquear cuentas inactivas.
- Políticas de autorización en cada acción.
- Doble validación en operaciones sensibles (aprobación, rechazo, cancelación).
- Bloqueos pesimistas en transacciones de aprobación.
- CSRF en todos los formularios.

### Rendimiento

- Eager loading obligatorio para evitar N+1 en listados y calendarios.
- Paginación en todos los listados.
- Consultas optimizadas sin `DB::` directo; usar `Model::query()`.

### Internacionalización

- Idioma: Español (es).
- Zona horaria: `America/Argentina/Buenos_Aires`.
- Formato de fecha: `DD/MM/YYYY`.
- Formato de hora: 24 horas (`HH:mm`).
- Números tabulares (`font-variant-numeric: tabular-nums`).

### Responsividad

- Mobile (< 768px): 4 columnas, tablas se transforman en cards apiladas.
- Tablet (768px–1024px): 8 columnas, navegación colapsable.
- Desktop (> 1024px): 12 columnas, sidebar fija de 256px.
- Contenido máximo: 80rem (1280px).

### Base de datos

- SQLite para desarrollo.
- Migraciones incrementales para evolución del esquema.
- Factories y seeders para datos de desarrollo.

---

## 7. Especificaciones de diseño UI

### Paleta de colores

| Rol | Color | Hex |
|---|---|---|
| Primario (navy institucional) | Azul oscuro | `#1E3A8A` |
| Secundario (interactivo) | Azul medio | `#2563EB` |
| Terciario (acento) | Celeste | `#38BDF8` |
| Neutro | Slate | `#64748B` (rango `#0F172A` a `#F8FAFC`) |
| Canvas base | Gris ultra-claro | `#F8FAFC` |
| Superficie elevada | Blanco puro | `#FFFFFF` |
| Bordes estructurales | Slate 200 | `#E2E8F0` |

### Tipografía

- Familia: **Inter** en todas las escalas.
- Display LG: 2.25rem / 700 / -0.025em.
- Headline LG: 1.75rem / 600 / -0.02em.
- Body MD: 0.875rem / 400.
- Label SM: 0.6875rem / 600 / 0.025em.

### Componentes

#### Botones

| Variante | Fondo | Texto | Hover |
|---|---|---|---|
| Primary | `#1E3A8A` | `#FFFFFF` | `#172554` |
| Secondary | `#2563EB` | `#FFFFFF` | `#1D4ED8` |
| Outline | `#FFFFFF` (borde `#E2E8F0`) | `#1E293B` | `#F8FAFC` |
| Ghost | Transparente | `#475569` | `#F1F5F9` |
| Destructive | `#EF4444` | `#FFFFFF` | `#DC2626` |

- Altura: 2.5rem (40px), padding horizontal: `px-4`, font-weight: 500.

#### Inputs

- Superficie: `#FFFFFF`, borde: `1px solid #CBD5E1`, altura: 2.5rem.
- Focus: borde `#2563EB` con ring `rgba(37, 99, 235, 0.15)`.
- Error: borde `#EF4444`, mensaje en `#DC2626`.

#### Elevación

| Nivel | Uso | Shadow |
|---|---|---|
| 0 | Canvas plano | Sin sombra |
| 1 | Cards, contenido | `shadow-sm` + borde `#E2E8F0` |
| 2 | Cards hover, paneles | `shadow-md` + borde `#CBD5E1` |
| 3 | Modales, popovers | `shadow-lg` + backdrop `rgba(15,23,42,0.45)` blur 4px |

#### Bordes redondeados

| Elemento | Radio |
|---|---|
| Controles (botones, inputs) | `0.375rem` (6px) |
| Cards, paneles, modales | `0.5rem` (8px) |
| Badges, pills, avatares | `9999px` (full) |

### Layout

- Sidebar fija: 256px para administradores.
- Header simplificado para profesores (evaluar).
- Contenedor máximo: 80rem.
- Padding cards: 1.25rem mobile, 1.5rem desktop.
- Padding celdas tabla: 0.75rem vertical, 1rem horizontal.

---

## 8. Stack tecnológico

| Capa | Tecnología |
|---|---|
| Framework | Laravel (starter kit Livewire) |
| Frontend reactivo | Livewire + Alpine.js (incluido por Livewire) |
| Componentes UI | Flux UI (gratuito; Flux Pro solo si se justifica) |
| CSS | Tailwind CSS v4 |
| Testing | Pest v4 |
| Formatter | Pint |
| Base de datos | SQLite (desarrollo) |

---

## 9. Arquitectura

### Dominio

- `app/Enums/UserRole.php`
- `app/Enums/ReservaEstado.php`
- `app/Models/Sala.php`
- `app/Models/Reserva.php`
- `app/Models/ReservaConfiguracion.php`
- `app/Models/ReservaHistorial.php`
- `app/Models/User.php`

### Acciones (casos de uso)

- `app/Actions/Reservas/CreateReserva.php`
- `app/Actions/Reservas/UpdateReserva.php`
- `app/Actions/Reservas/ApproveReserva.php`
- `app/Actions/Reservas/RejectReserva.php`
- `app/Actions/Reservas/CancelReserva.php`

### Servicio de reglas

- `app/Services/ReservaRuleService.php` — validaciones de dominio compartidas.

### Principio fundamental

Los componentes Livewire deben invocar las acciones existentes. **No deben duplicar lógica de negocio dentro del componente.**

---

## 10. Mapa de pantallas → componentes Livewire

| Pantalla | Componente propuesto |
|---|---|
| Dashboard | `Dashboard` |
| Calendario | `Calendar/Index` |
| Listado de reservas | `Reservations/Index` |
| Crear / editar reserva | `Reservations/Form` |
| Detalle e historial | `Reservations/Show` |
| Solicitudes pendientes | `Admin/Reservations/Pending` |
| Gestión de espacios | `Admin/Rooms/Index` + `Admin/Rooms/Form` |
| Gestión de profesores | `Admin/Teachers/Index` + `Admin/Teachers/Form` |
| Configuración | `Admin/ReservationSettings/Edit` |
| Notificaciones | `Notifications/Index` |

---

## 11. Criterios de aceptación

La implementación se considera completa cuando:

- [ ] Todas las funcionalidades de los RF-01 a RF-15 están operativas.
- [ ] No existe registro público.
- [ ] Las cuentas inactivas no pueden operar.
- [ ] Las políticas protegen acciones y pantallas Livewire.
- [ ] La regla de superposición conserva exactamente el comportamiento actual.
- [ ] La aprobación es transaccional y vuelve a validar disponibilidad.
- [ ] Todos los cambios generan historial.
- [ ] Las notificaciones funcionan correctamente.
- [ ] El diseño usa Flux UI de forma consistente con la paleta institucional.
- [ ] No se cargan dos instancias de Alpine.js.
- [ ] No hay consultas N+1 en listados ni calendarios.
- [ ] `npm run build` termina correctamente.
- [ ] La suite de pruebas anterior y las nuevas pruebas Livewire pasan.
- [ ] La aplicación fue verificada en escritorio y móvil.
- [ ] Formato de fechas DD/MM/YYYY y horarios en 24h en toda la interfaz.

---

## 12. Datos de demostración (solo desarrollo)

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@iesnh.edu.ar` | `Reservas123!` |
| Profesora | `laura@iesnh.edu.ar` | `Reservas123!` |
| Profesor | `martin@iesnh.edu.ar` | `Reservas123!` |

> **⚠️ Estas credenciales son exclusivamente para desarrollo. No deben migrarse a producción.**
