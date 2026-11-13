# Proyecto de Investigación — Frontend PHP

**Versión actual: V3 — Inicio de sesión, permisos y administración (en desarrollo)**

## Repositorios:

- Backend: https://github.com/Angel-7195/Proyecto_Investigacion_Backend.git
- Frontend: https://github.com/Angel-7195/Proyecto_Investigacion_Frontend.git

Proyecto académico del módulo de Investigación, desarrollado con **PHP puro 8.3**, **HTML**, **CSS**, **Bootstrap local** y **Docker**. Esta aplicación presenta las interfaces al usuario y consume mediante HTTP la API REST del proyecto.

## Integrantes

- **Ángel David Gutiérrez Ladino:** integración de autenticación y autorización, vistas de `usuario` y gestión de asignaciones `rol_usuario`.
- **Miguel Ángel Lotero Álvarez:** desarrollo del módulo independiente de administración de `rol` y demás responsabilidades acordadas por el equipo.

> **Estado al 13 de noviembre de 2026.** La interfaz de login, las restricciones por rol, la administración de usuarios y la asignación/retirada de roles ya cuentan con pruebas funcionales. **V3 aún no está cerrada:** falta integrar el CRUD del catálogo `rol` de Miguel, verificar escenarios de seguridad y regresión pendientes, revisar los cambios en equipo y completar PR/tag.

## 1. Evolución del frontend

| Versión | Alcance | Estado conocido |
|---|---|---|
| **V1** | Interfaces para seis recursos del módulo de Investigación sin claves foráneas. | Implementadas y comprobadas anteriormente. |
| **V2** | Diez recursos adicionales con relaciones y claves compuestas; se conservan las seis interfaces de V1. | Integradas en el proyecto y disponibles para consulta; queda pendiente registrar una regresión integral de todos sus métodos con seguridad V3. |
| **V3** | Login y logout, sesión PHP, JWT Bearer, menús por rol y pantallas administrativas de seguridad. | **En desarrollo avanzado:** `usuario` y `rol_usuario` probados; catálogo `rol` de Miguel pendiente de integración. |
| **V4** | Consultas multitabla, dashboard, mejoras visuales y publicación. | Pendiente, fuera de este alcance. |

Este repositorio contiene **solo el frontend**. La base de datos MariaDB y las reglas de negocio pertenecen al backend, que reside en **`Proyecto_Investigacion_Backend`**.

## 2. Arquitectura y responsabilidades

```text
Navegador
   ↓
Frontend PHP — sesión y vistas
   ↓ Solicitud HTTP + Authorization: Bearer <JWT>
API de Investigación — autenticación y autorización
   ↓ Controladores → Servicios → Repositorios PDO
MariaDB
```

La separación es intencional:

- **Frontend:** navegación, formularios, validaciones de presentación, sesión PHP, mensajes y solicitudes HTTP a la API.
- **Backend:** verifica credenciales, firma JWT, consulta roles activos, autoriza operaciones, valida reglas de negocio y accede a MariaDB.

El frontend **no usa PDO, no consulta MariaDB ni debe almacenar credenciales de la base**. Ocultar botones según el rol mejora la interfaz, pero la seguridad real debe comprobarse siempre en la API.

### Estructura principal

```text
Proyecto_Investigacion_Frontend/
├── front_php/
│   ├── index.php                           # Front Controller; login/logout y rutas
│   ├── cliente_api.php                     # Comunicación HTTP; encabezado Bearer
│   ├── Dockerfile
│   ├── rutas/
│   │   ├── compartidas/
│   │   ├── rutas_area_conocimiento.php
│   │   ├── ...                             # Otros 15 recursos V1/V2
│   │   ├── rutas_usuario.php               # Gestión de usuarios V3
│   │   └── rutas_rol_usuario.php           # Asignación/retiro de roles V3
│   ├── vistas/
│   │   ├── inicio.php
│   │   ├── autenticacion/login.php
│   │   ├── compartidas/plantilla.php
│   │   ├── usuario/
│   │   │   ├── lista.php
│   │   │   └── formulario.php
│   │   ├── rol_usuario/gestion.php
│   │   └── ...                             # Vistas de los recursos V1/V2
│   └── publico/
│       ├── bootstrap.min.css
│       ├── bootstrap.bundle.min.js
│       └── css/estilos.css
├── .gitignore
└── README.md
```

`index.php` interpreta las rutas, inicia y protege la sesión, incluye los archivos de cada entidad y sirve los archivos estáticos existentes de `publico/`. `cliente_api.php` utiliza `URL_API` para llamar al backend y agrega el Bearer del JWT conservado en `$_SESSION` cuando corresponde.

> **Decisión técnica por revisar con el equipo:** el cliente PHP usa `stream_context_create()` y `file_get_contents()` para HTTP. La documentación del curso menciona cURL; debe resolverse esa diferencia antes de afirmar cumplimiento completo de la metodología.

## 3. Cómo ejecutar el proyecto

El archivo `docker-compose.yml` se encuentra **en el repositorio backend**, no en este frontend. Para ejecutar la aplicación completa se requieren ambos repositorios en el computador.

1. Clonar o disponer de `Proyecto_Investigacion_Backend` y `Proyecto_Investigacion_Frontend` en carpetas independientes.
2. En el archivo `.env` del backend, configurar `FRONT_PATH` con la ruta real al frontend y completar las variables locales necesarias. La API debe tener un `JWT_SECRET` seguro. **Nunca publicar el `.env`.**
3. Abrir PowerShell en la raíz del backend y ejecutar:

   ```powershell
   docker compose up -d --build
   docker compose ps
   ```

Si se modificó solamente el frontend, se puede reconstruir su servicio desde la raíz del backend:

```powershell
docker compose up -d --build front-php
```

Para detener los contenedores sin eliminar la información de MariaDB:

```powershell
docker compose down
```

**No ejecutar `docker compose down -v` como parte del trabajo habitual:** ese comando también elimina volúmenes y puede borrar los datos locales.

### Accesos del entorno probado

| Servicio | Dirección habitual |
|---|---|
| Frontend (login) | http://localhost:8110/login |
| Frontend (inicio tras autenticarse) | http://localhost:8110/ |
| API | http://localhost:8111 |
| phpMyAdmin | http://localhost:8105 |
| MariaDB desde el equipo | `localhost:13330` |

Los puertos pueden variar con `docker-compose.yml`. Si es una instalación nueva, el **primer ADMIN** debe inicializarse mediante el script CLI del backend; crear esa cuenta no es una función pública del frontend.

## 4. Las 16 pantallas de Investigación — V1 y V2

El frontend conserva rutas propias para los seis recursos de V1 y los diez recursos relacionados de V2.

| Versión | Ruta web de consulta | Recurso de la API |
|---|---|---|
| V1 | `/areas-conocimiento` | `area_conocimiento` |
| V1 | `/objetivos-desarrollo-sostenible` | `objetivo_desarrollo_sostenible` |
| V1 | `/areas-aplicacion` | `area_aplicacion` |
| V1 | `/terminos-clave` | `termino_clave` |
| V1 | `/universidades` | `universidad` |
| V1 | `/lineas-investigacion` | `linea_investigacion` |
| V2 | `/grupos-investigacion` | `grupo_investigacion` |
| V2 | `/semilleros` | `semillero` |
| V2 | `/docentes` | `docente` |
| V2 | `/participaciones-grupo` | `participa_grupo` |
| V2 | `/participaciones-semillero` | `participa_semillero` |
| V2 | `/asociaciones-grupo-linea` | `grupo_linea` |
| V2 | `/asociaciones-semillero-linea` | `semillero_linea` |
| V2 | `/asociaciones-ac-linea` | `ac_linea` |
| V2 | `/asociaciones-ods-linea` | `ods_linea` |
| V2 | `/asociaciones-aa-linea` | `aa_linea` |

Las tablas con claves compuestas conservan sus dos identificadores en las operaciones de detalle. Algunas asociaciones de V2 permiten **GET, POST y DELETE, pero no PUT/PATCH**; los métodos exactos dependen de los contratos de cada recurso.

**En V3 se agrega autenticación a estas pantallas:** `consulta` solo puede leer; `admin` puede acceder a las acciones de creación, edición y retiro que permita el contrato del recurso. El backend vuelve a verificar el rol antes de ejecutar cada operación.

## 5. Autenticación y sesión — V3

### Inicio de sesión

| Ruta web | Método | Función |
|---|---|---|
| `/login` | GET | Mostrar el formulario de usuario y contraseña. |
| `/login` | POST | Validar CSRF y enviar credenciales a `POST /api/login`. |
| `/logout` | POST | Validar CSRF, limpiar JWT, destruir la sesión PHP y su cookie, y regresar a `/login`. |

Cuando las credenciales son correctas:

1. La API verifica la cuenta y su contraseña y devuelve un JWT HS256 vigente durante **60 minutos**.
2. El frontend regenera el identificador de sesión y guarda el JWT, los datos mínimos del usuario y sus roles en `$_SESSION`.
3. `cliente_api.php` envía el token mediante `Authorization: Bearer` en las peticiones protegidas.
4. La navegación y las acciones visibles se adaptan al rol.

El frontend protege las cookies con `HttpOnly` y `SameSite=Lax`, y utiliza `Secure` cuando la conexión es HTTPS. Estas medidas constan en el código; queda pendiente una revisión dinámica completa de cookie y cierre de sesión.

**Importante:** cerrar sesión elimina el token guardado por el frontend, pero **no revoca por sí solo** una copia de JWT emitida anteriormente. En cambio, si el backend detecta que el usuario perdió su rol o está inactivo, deniega nuevas peticiones protegidas aunque el token siga dentro de su plazo de vigencia.

### Permisos de navegación

| Rol | Navegación y acciones permitidas |
|---|---|
| `admin` | Lecturas y acciones autorizadas de V1/V2; gestión administrativa de usuarios y asignaciones. |
| `consulta` | Lectura de los 16 recursos de Investigación, sin crear, modificar, retirar ni administrar usuarios/roles. |
| Cuenta sin rol activo | No puede iniciar una nueva sesión autenticada. |

**Las rutas directas también están protegidas.** Escribir manualmente `/usuarios` o `/roles-usuarios` siendo `consulta` produce una pantalla de acceso denegado; además, la API mantiene la autorización independiente.

## 6. Administración de usuarios — V3

La gestión de `usuario` es **exclusiva de `admin`**. Los formularios HTML envían `POST` al frontend; `cliente_api.php` utiliza el método HTTP específico de la API para la operación solicitada.

| Ruta web | Método web | Operación |
|---|---|---|
| `/usuarios` | GET | Listado de cuentas activas. |
| `/usuarios/nuevo` | GET | Formulario de registro. |
| `/usuarios/crear` | POST | `POST /api/usuario`. |
| `/usuarios/editar?id=N` | GET | Ficha y formulario de edición. |
| `/usuarios/actualizar` con `accion=completo` | POST | `PUT /api/usuario/N` para reemplazar perfil. |
| `/usuarios/actualizar` con `accion=parcial` | POST | `PATCH /api/usuario/N` con los campos modificados. |
| `/usuarios/editar?id=N&seccion=password` | GET | Formulario independiente de contraseña. |
| `/usuarios/actualizar` con `accion=password` | POST | `PATCH /api/usuario/N/password`. |
| `/usuarios/editar?id=N&seccion=eliminar` | GET | Confirmación para desactivar la cuenta. |
| `/usuarios/actualizar` con `accion=eliminar` | POST | `DELETE /api/usuario/N` (borrado lógico). |

### Validaciones principales

- Registro con `username`, correo válido, nombre completo opcional y contraseña con confirmación.
- Contraseñas de **12 a 72 bytes**, sin mostrarlas nuevamente después de un error.
- Edición del perfil separada del cambio de contraseña.
- `PATCH` envía únicamente los campos realmente modificados; si no hay cambios, se informa al usuario.
- Tokens CSRF para las operaciones de formularios administrativos.
- Mensajes comprensibles para duplicados (`409`), datos inválidos (`422`) y falta de autorización (`401`/`403`).
- Confirmación antes de desactivar un usuario. El backend conserva su fila con **`activo=0`** y el listado activo deja de mostrarla.
- El último administrador activo debe permanecer protegido por las reglas del backend.

**Registrar un usuario no le asigna un rol automáticamente.** Para que pueda iniciar sesión, ADMIN debe asignarle un rol desde el módulo `rol_usuario`.

## 7. Administración de roles por usuario — V3

`rol_usuario` gestiona la relación entre una cuenta y un rol; **no crea ni modifica tipos de rol**.

| Ruta web | Método web | Operación |
|---|---|---|
| `/roles-usuarios` | GET | Consultar asignaciones y mostrar formulario. |
| `/roles-usuarios/asignar` | POST | `POST /api/rol_usuario` para crear un vínculo. |
| `/roles-usuarios/confirmar-retiro?usuario_id=N&rol_id=R` | GET | Consultar y confirmar una asociación existente. |
| `/roles-usuarios/retirar` | POST | `DELETE /api/rol_usuario/N/R` para retirar solo el vínculo. |

El módulo implementa:

- Selección de usuario activo y rol.
- Prevención de duplicados (el backend devuelve `409`).
- Confirmación explícita adicional antes de asignar privilegios `admin`.
- Pantalla de confirmación para retirar roles, con protección CSRF asociada a la pareja usuario–rol.
- Aviso «Último admin protegido» si solo queda una asignación administrativa activa; el backend también debe rechazar el retiro prohibido.
- Comprobación de permisos vigentes en la API incluso si un usuario mantiene una sesión anterior abierta.

**Diferencia con el borrado lógico de usuarios:** retirar `rol_usuario` elimina **físicamente la asociación**, porque la tabla puente no tiene columna `activo`; no elimina físicamente el usuario ni el registro del catálogo `rol`.

### Integración pendiente del catálogo `rol`

La creación, edición y baja de los tipos de rol corresponde al módulo `rol` de **Miguel**. En la instancia local probada, `admin` tenía ID 1 y `consulta` ID 2; el selector actual usa **temporalmente esos dos IDs confirmados**. Al integrar el CRUD de Miguel deberá consumir el catálogo real, sin suponer que los IDs son iguales en todas las bases.

## 8. Pruebas funcionales y de seguridad realizadas

Las siguientes evidencias fueron aportadas por Ángel mediante terminal, navegador y consultas **de solo lectura** en phpMyAdmin:

| Prueba | Resultado observado |
|---|---|
| Levantar Docker y validar sintaxis | `docker compose up -d --build` correcto; PHP `-l` sin errores en archivos principales y del módulo de roles comprobados. |
| ADMIN | Login correcto y acceso a las pantallas de administración. |
| Alta de usuarios | Se registraron cuentas de prueba como Mario, Mariana y `prueba_delete_v3`. |
| Edición y contraseña | Se actualizaron datos de perfil y contraseña; el acceso de prueba funcionó al asignar el rol requerido. |
| Borrado lógico | `prueba_delete_v3` desapareció de activos y MariaDB conservó el registro con `activo=0`. |
| Asignación de `consulta` | Mariana recibió el rol desde el frontend y pudo iniciar sesión. |
| Rol duplicado | Se mostró un mensaje de asignación ya existente. |
| Acceso de `consulta` | Lectura de **218 áreas de conocimiento**, sin botones CRUD; ruta administrativa directa denegada. |
| Retiro de rol | La relación dejó de aparecer y SQL devolvió cero filas durante el retiro. |
| Cuenta sin rol | No pudo iniciar una sesión nueva. |
| Revocación durante sesión abierta | Al retirar su único rol, Mariana dejó de poder consultar el recurso protegido aunque la ventana seguía abierta. |
| Recuperación de acceso | ADMIN devolvió el rol; después de iniciar sesión otra vez, Mariana recuperó la lectura. |
| Último administrador | La interfaz mostró «Último admin protegido»; un SELECT confirmó que ADMIN era la única cuenta administradora activa. |

**La protección del último ADMIN se verificó visualmente y por SQL, pero no se ejecutó una retirada real de esa cuenta.** La prueba de rechazo HTTP debe hacerse en una base descartable, no en el volumen habitual del equipo.

### Comandos de verificación de sintaxis

Ejecutar desde la raíz del **repositorio backend**, donde está `docker-compose.yml`:

```powershell
docker compose exec -T front-php php -l /app/index.php
docker compose exec -T front-php php -l /app/cliente_api.php
docker compose exec -T front-php php -l /app/vistas/compartidas/plantilla.php
docker compose exec -T front-php php -l /app/rutas/rutas_usuario.php
docker compose exec -T front-php php -l /app/rutas/rutas_rol_usuario.php
docker compose exec -T front-php php -l /app/vistas/rol_usuario/gestion.php
```

Estos comandos **solo comprueban sintaxis**, no sustituyen las pruebas de autorización o persistencia. No se afirma haber ejecutado ya todos los comandos de la lista.

## 9. Pendientes para cerrar V3

1. Integrar el CRUD del catálogo `rol` de Miguel y alimentar dinámicamente las opciones de roles.
2. Completar la regresión de las **16 pantallas V1/V2** para todos sus verbos y validaciones con perfiles `admin`/`consulta`.
3. Comprobar el frontend con la **API apagada** y confirmar mensajes sin datos ficticios ni acceso directo a MariaDB.
4. Completar pruebas de logout/cookies, CSRF manipulado, caducidad de JWT y respuesta a errores de todos los formularios.
5. Verificar el rechazo de retiro del último ADMIN en una **base de datos descartable**; no probarlo sobre la cuenta real.
6. Revisar con el equipo el uso de streams HTTP frente a cURL, realizar revisión cruzada y cerrar las compuertas SpecKit.
7. Preparar los commits, Pull Requests y el tag `v3` **únicamente cuando la versión completa esté aprobada**.

El estado de los módulos desarrollados por Ángel no equivale a declarar terminada toda V3.

## 10. Documentación y trabajo colaborativo

La documentación SpecKit centralizada se mantiene en el repositorio **`Proyecto_Investigacion_Backend`**, dentro de:

```text
docs/spec_kit/versiones/v3_seguridad/
```

Ahí se encuentran la especificación (`2_spec.md`), el plan (`3_plan.md`), los contratos (`6_contracts.md`), el quickstart (`7_quickstart.md`), las tareas (`8_tasks.md`), el checklist (`9_checklist.md`) y el registro de pruebas del frontend (`REGISTRO_FASE6_FRONTEND.md`). **No se duplican documentos SpecKit en este repositorio frontend.**

### Rama de trabajo

El desarrollo de Ángel en este repositorio se realiza en:

```text
feature/angel-frontend-v3
```

La rama complementaria del backend es `feature/angel-investigacion-v3`. Los commits y PR se preparan **por repositorio**; el integrador revisa antes de llevar cambios a `main`. Mantener separados frontend, backend y sus responsabilidades facilita las pruebas y la sustentación del proyecto.
