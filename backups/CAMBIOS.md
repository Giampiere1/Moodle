# Registro de Cambios — SalaVirtual (Local Dev)

> Todos los cambios aplicados al entorno de desarrollo local de Moodle 3.11.

---

## 2026-07-08 — Levantamiento inicial del proyecto

### Entorno
- **WAMP Server**: Apache 2.4.65 | MariaDB 11.4.9 (puerto 3307) | PHP 8.0.30
- **Proyecto**: `C:\wamp64\www\SalaVirtual`
- **URL local**: `http://localhost/SalaVirtual/`
- **Datos Moodle**: `C:\wamp64\www\moodledatacnl`

### Cambios en `config.php`
| Campo | Valor anterior | Valor nuevo |
|---|---|---|
| `$CFG->wwwroot` | `http://localhost/SalaVirtualCNL` | `http://localhost/SalaVirtual` |
| `$CFG->dataroot` | *(carpeta no existia)* | `C:\wamp64\www\moodledatacnl` (creada) |

### Base de Datos
- Se creo la BD `moodle` en MariaDB puerto 3307.
- Se importo el respaldo: `C:\wamp64\www\moodel cnl sala viryual.sql` (~443 MB).
- Se corrigio el `wwwroot` en `mdl_config` (estaba vacio, bloqueaba el login).
- Se eliminaron todos los cursos y diplomas del entorno de produccion via SQL directo.

### Usuarios Creados
| Usuario | Contrasena | Rol |
|---|---|---|
| `admin_dev` | `SalaVirtual2026!` | Administrador del sitio |
| `profesor_dev` | `SalaVirtual2026!` | Profesor (editing teacher) |
| `estudiante_dev` | `SalaVirtual2026!` | Estudiante |

### Curso de Prueba
- **Nombre**: `Curso de Prueba`
- **Shortname**: `CURSO-PRUEBA`
- **ID**: `201`
- **URL**: `http://localhost/SalaVirtual/course/view.php?id=201`
- Los 3 usuarios estan inscritos.

### Tema (Plantilla)
- **Antes**: `academi` (antiguo)
- **Ahora**: `moove` (Bootstrap 4, moderno y responsivo)

### Backup de BD
- **Archivo**: `backups/moodle_backup_20260708_205225.sql` (~44 MB)
- Estado limpio con usuarios y curso de prueba.

---

## Comandos utiles para mantenimiento

### Crear nuevo backup de BD (PowerShell)
```powershell
$ts = Get-Date -Format "yyyyMMdd_HHmmss"
C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysqldump.exe --host=127.0.0.1 --port=3307 --user=root --single-transaction moodle | Out-File "C:\wamp64\www\SalaVirtual\backups\moodle_backup_$ts.sql" -Encoding utf8
```

### Restaurar backup de BD
```powershell
C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mariadb.exe --host=127.0.0.1 --port=3307 --user=root moodle < "C:\wamp64\www\SalaVirtual\backups\moodle_backup_FECHA.sql"
```

### Purgar cache de Moodle
```powershell
C:\wamp64\bin\php\php8.0.30\php.exe C:\wamp64\www\SalaVirtual\admin\cli\purge_caches.php
```

### Resetear contrasena de usuario
```powershell
C:\wamp64\bin\php\php8.0.30\php.exe C:\wamp64\www\SalaVirtual\admin\cli\reset_password.php --username=admin_dev
```
