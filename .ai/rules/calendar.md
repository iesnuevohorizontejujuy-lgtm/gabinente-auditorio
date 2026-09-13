---
paths:
  - 'app/Models/{User,Carrera}.php,resources/views/pages/settings/**,resources/views/pages/calendar/**'
---

# Calendar

## Carreras docentes reutilizables
Un profesor puede pertenecer a varias carreras mediante carrera_user. Las carreras se cargan desde el perfil como nombres separados por comas y el calendario debe mostrarlas junto con `Profesor/a {nombre}`; cargar siempre profesor.carreras para evitar N+1.
