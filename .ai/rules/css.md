---
paths:
  - 'resources/views/pages/calendar/**,resources/js/app.js,resources/css/app.css'
---

# Css

## FullCalendar para disponibilidad de espacios
El calendario es individual por sala: siempre debe existir un salaId activo y no hay vista global. Muestra reservas aprobadas en verde y pendientes en amarillo (todas para administradores; solo las propias para profesores). Los eventos accionables abren el modal de edición/cancelación y cada alta o cambio ejecuta refetchEvents().
