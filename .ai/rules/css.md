---
paths:
  - 'resources/views/pages/calendar/**,resources/js/app.js,resources/css/app.css'
---

# Css

## FullCalendar para disponibilidad de espacios
El calendario de espacios usa FullCalendar v6 instalado por npm, con timeGridWeek como vista inicial y estilos centralizados en resources/css/app.css. Los eventos se obtienen desde Livewire por rango visible y solo incluyen reservas aprobadas; al cambiar el filtro o crear una solicitud se debe ejecutar refetchEvents().
