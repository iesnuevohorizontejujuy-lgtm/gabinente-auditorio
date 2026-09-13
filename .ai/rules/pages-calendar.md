---
paths:
  - 'app/Actions/Feriados/**,app/Models/Feriado.php,app/Console/Commands/SyncFeriadosCommand.php,resources/views/pages/calendar/**'
---

# Pages Calendar

## Feriados persistidos por jurisdicción
Los feriados nacionales se sincronizan desde ArgentinaDatos con `feriados:sincronizar {anio?}` y se persisten antes de mostrarse. Los asuetos de Jujuy deben cargarse con jurisdiccion=jujuy y una fuente oficial; todos se muestran como eventos de día completo en cada calendario de sala.
