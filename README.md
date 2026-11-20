# Proyecto de Investigación — Frontend PHP

**Versión actual: V3 — Seguridad, administración y catálogo fijo de
roles (en desarrollo)**

- Pagina Desplegada con AlwaysData: https://proyectoinvestigacion.alwaysdata.net/

## Repositorios:

- Backend:
  https://github.com/Angel-7195/Proyecto_Investigacion_Backend.git
- Frontend:
  https://github.com/Angel-7195/Proyecto_Investigacion_Frontend.git

Proyecto académico del módulo de Investigación, desarrollado con **PHP
puro 8.3**, **HTML**, **CSS**, **Bootstrap local** y **Docker**. Esta
aplicación presenta las interfaces al usuario y consume mediante HTTP la
API REST del proyecto.

## Integrantes

- **Ángel David Gutiérrez Ladino:** integración de autenticación y
  autorización, vistas de `usuario` y gestión de asignaciones
  `rol_usuario`.
- **Miguel Ángel Lotero Álvarez:** trabajo del módulo `rol` y demás
  responsabilidades acordadas por el equipo.

> **Estado documentado al 19 de noviembre de 2026.** V3 se encuentra en
> desarrollo avanzado, pero **todavía no está cerrada**. El catálogo
> fijo `rol` ya está integrado como recurso de solo consulta; se
> aprobaron sus pruebas aisladas, se verificaron por HTTP las
> operaciones de `admin` y se revisó visualmente la pantalla `/roles`.
> Siguen pendientes la comprobación HTTP de los permisos de `consulta`
> sobre ese catálogo, la revisión del selector de `/roles-usuarios`, la
> regresión integral de V1/V2, otras pruebas de seguridad e instalación,
> la revisión conjunta y el proceso de PR/tag.

## 1. Evolución del frontend

| Versión | Alcance                                                                                                                                             | Estado conocido                                                                                                                                                  |
|---------|-----------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **V1**  | Interfaces para seis recursos del módulo de Investigación sin claves foráneas.                                                                      | Implementadas y comprobadas anteriormente.                                                                                                                       |
| **V2**  | Diez recursos adicionales con relaciones y claves compuestas; se conservan las seis interfaces de V1.                                               | Integradas en el proyecto y disponibles para consulta; queda pendiente registrar una regresión integral de todos sus métodos con seguridad V3.                   |
| **V3**  | Login y logout, sesión PHP, JWT Bearer, permisos `admin`/`consulta`, administración de `usuario`, catálogo fijo `rol` y asignaciones `rol_usuario`. | **En desarrollo avanzado:** `usuario`, `rol_usuario` y las operaciones de consulta de `rol` cuentan con verificaciones; faltan comprobaciones y cierre integral. |
| **V4**  | Consultas multitabla, dashboard, mejoras visuales y publicación.                                                                                    | Pendiente, fuera de este alcance.                                                                                                                                |

Este repositorio contiene **solo el frontend**. La base de datos MariaDB
y las reglas de negocio pertenecen al backend, que reside en
**`Proyecto_Investigacion_Backend`**.

## 2. Arquitectura y responsabilidades

``` text
Navegador
   ↓
Frontend PHP — sesión y vistas
   ↓ Solicitud HTTP + Authorization: Bearer <JWT>
API de Investigación — autenticación y autorización
   ↓ Controladores → Servicios → Repositorios PDO
MariaDB
```

La separación es intencional:

- **Frontend:** navegación, formularios, validaciones de presentación,
  sesión PHP, mensajes y solicitudes HTTP a la API.
- **Backend:** verifica credenciales, firma JWT, consulta roles activos,
  autoriza operaciones, valida reglas de negocio y accede a MariaDB.

El frontend **no usa PDO, no consulta MariaDB ni debe almacenar
credenciales de la base**. Ocultar botones según el rol mejora la
interfaz, pero la seguridad real debe comprobarse siempre en la API.

### Estructura principal

``` text
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
│   │   ├── rutas_rol.php                   # Catálogo fijo de roles (solo consulta)
│   │   └── rutas_rol_usuario.php           # Asignación/retiro de roles V3
│   ├── vistas/
│   │   ├── inicio.php
│   │   ├── autenticacion/login.php
│   │   ├── compartidas/plantilla.php
│   │   ├── usuario/
│   │   │   ├── lista.php
│   │   │   └── formulario.php
│   │   ├── rol/lista.php                  # Catálogo fijo; sin acciones CRUD
│   │   ├── rol_usuario/gestion.php
│   │   └── ...                             # Vistas de los recursos V1/V2
│   └── publico/
│       ├── bootstrap.min.css
│       ├── bootstrap.bundle.min.js
│       └── css/estilos.css
├── .gitignore
└── README.md
```

`index.php` interpreta las rutas, inicia y protege la sesión, incluye
los archivos de cada entidad y sirve los archivos estáticos existentes
de `publico/`. `cliente_api.php` utiliza `URL_API` para llamar al
backend y agrega el Bearer del JWT conservado en `$_SESSION` cuando
corresponde.

> **Decisión técnica por revisar con el equipo:** el cliente PHP usa
> `stream_context_create()` y `file_get_contents()` para HTTP. La
> documentación del curso menciona cURL; debe resolverse esa diferencia
> antes de afirmar cumplimiento completo de la metodología.

## 3. Cómo ejecutar el proyecto

El archivo `docker-compose.yml` se encuentra **en el repositorio
backend**, no en este frontend. Para ejecutar la aplicación completa se
requieren ambos repositorios en el computador.

1.  Clonar o disponer de `Proyecto_Investigacion_Backend` y
    `Proyecto_Investigacion_Frontend` en carpetas independientes.

2.  En el archivo `.env` del backend, configurar `FRONT_PATH` con la
    ruta real al frontend y completar las variables locales necesarias.
    La API debe tener un `JWT_SECRET` seguro. **Nunca publicar el
    `.env`.**

3.  Abrir PowerShell en la raíz del backend y ejecutar:

    ``` powershell
    docker compose up -d --build
    docker compose ps
    ```

Si se modificó solamente el frontend, se puede reconstruir su servicio
desde la raíz del backend:

``` powershell
docker compose up -d --build front-php
```

Para detener los contenedores sin eliminar la información de MariaDB:

``` powershell
docker compose down
```

**No ejecutar `docker compose down -v` como parte del trabajo
habitual:** ese comando también elimina volúmenes y puede borrar los
datos locales.

### Accesos del entorno probado

| Servicio                            | Dirección habitual          |
|-------------------------------------|-----------------------------|
| Frontend (login)                    | http://localhost:8110/login |
| Frontend (inicio tras autenticarse) | http://localhost:8110/      |
| API                                 | http://localhost:8111       |
| phpMyAdmin                          | http://localhost:8105       |
| MariaDB desde el equipo             | `localhost:13330`           |

Los puertos pueden variar con `docker-compose.yml`. Si es una
instalación nueva, el **primer ADMIN** debe inicializarse mediante el
script CLI del backend; crear esa cuenta no es una función pública del
frontend.

## 4. Las 16 pantallas de Investigación — V1 y V2

El frontend conserva rutas propias para los seis recursos de V1 y los
diez recursos relacionados de V2.

| Versión | Ruta web de consulta               | Recurso de la API                |
|---------|------------------------------------|----------------------------------|
| V1      | `/areas-conocimiento`              | `area_conocimiento`              |
| V1      | `/objetivos-desarrollo-sostenible` | `objetivo_desarrollo_sostenible` |
| V1      | `/areas-aplicacion`                | `area_aplicacion`                |
| V1      | `/terminos-clave`                  | `termino_clave`                  |
| V1      | `/universidades`                   | `universidad`                    |
| V1      | `/lineas-investigacion`            | `linea_investigacion`            |
| V2      | `/grupos-investigacion`            | `grupo_investigacion`            |
| V2      | `/semilleros`                      | `semillero`                      |
| V2      | `/docentes`                        | `docente`                        |
| V2      | `/participaciones-grupo`           | `participa_grupo`                |
| V2      | `/participaciones-semillero`       | `participa_semillero`            |
| V2      | `/asociaciones-grupo-linea`        | `grupo_linea`                    |
| V2      | `/asociaciones-semillero-linea`    | `semillero_linea`                |
| V2      | `/asociaciones-ac-linea`           | `ac_linea`                       |
| V2      | `/asociaciones-ods-linea`          | `ods_linea`                      |
| V2      | `/asociaciones-aa-linea`           | `aa_linea`                       |

Las tablas con claves compuestas conservan sus dos identificadores en
las operaciones de detalle. Algunas asociaciones de V2 permiten **GET,
POST y DELETE, pero no PUT/PATCH**; los métodos exactos dependen de los
contratos de cada recurso.

**En V3 se agrega autenticación a estas pantallas:** `consulta` solo
puede leer; `admin` puede acceder a las acciones de creación, edición y
retiro que permita el contrato del recurso. El backend vuelve a
verificar el rol antes de ejecutar cada operación.

## 5. Autenticación y sesión — V3

### Inicio de sesión

| Ruta web  | Método | Función                                                                               |
|-----------|--------|---------------------------------------------------------------------------------------|
| `/login`  | GET    | Mostrar el formulario de usuario y contraseña.                                        |
| `/login`  | POST   | Validar CSRF y enviar credenciales a `POST /api/login`.                               |
| `/logout` | POST   | Validar CSRF, limpiar JWT, destruir la sesión PHP y su cookie, y regresar a `/login`. |

Cuando las credenciales son correctas:

1.  La API verifica la cuenta y su contraseña y devuelve un JWT HS256
    vigente durante **60 minutos**.
2.  El frontend regenera el identificador de sesión y guarda el JWT, los
    datos mínimos del usuario y sus roles en `$_SESSION`.
3.  `cliente_api.php` envía el token mediante `Authorization: Bearer` en
    las peticiones protegidas.
4.  La navegación y las acciones visibles se adaptan al rol.

El frontend protege las cookies con `HttpOnly` y `SameSite=Lax`, y
utiliza `Secure` cuando la conexión es HTTPS. Estas medidas constan en
el código; queda pendiente una revisión dinámica completa de cookie y
cierre de sesión.

**Importante:** cerrar sesión elimina el token guardado por el frontend,
pero **no revoca por sí solo** una copia de JWT emitida anteriormente.
En cambio, si el backend detecta que el usuario perdió su rol o está
inactivo, deniega nuevas peticiones protegidas aunque el token siga
dentro de su plazo de vigencia.

### Permisos de navegación

| Rol                   | Navegación y acciones permitidas                                                                          |
|-----------------------|-----------------------------------------------------------------------------------------------------------|
| `admin`               | Lecturas y acciones autorizadas de V1/V2; gestión administrativa de usuarios y asignaciones.              |
| `consulta`            | Lectura de los 16 recursos de Investigación, sin crear, modificar, retirar ni administrar usuarios/roles. |
| Cuenta sin rol activo | No puede iniciar una nueva sesión autenticada.                                                            |

**Las rutas directas también están protegidas.** Escribir manualmente
`/usuarios`, `/roles` o `/roles-usuarios` siendo `consulta` produce una
pantalla de acceso denegado; además, la API mantiene la autorización
independiente.

## 6. Administración de usuarios — V3

La gestión de `usuario` es **exclusiva de `admin`**. Los formularios
HTML envían `POST` al frontend; `cliente_api.php` utiliza el método HTTP
específico de la API para la operación solicitada.

| Ruta web                                     | Método web | Operación                                          |
|----------------------------------------------|------------|----------------------------------------------------|
| `/usuarios`                                  | GET        | Listado de cuentas activas.                        |
| `/usuarios/nuevo`                            | GET        | Formulario de registro.                            |
| `/usuarios/crear`                            | POST       | `POST /api/usuario`.                               |
| `/usuarios/editar?id=N`                      | GET        | Ficha y formulario de edición.                     |
| `/usuarios/actualizar` con `accion=completo` | POST       | `PUT /api/usuario/N` para reemplazar perfil.       |
| `/usuarios/actualizar` con `accion=parcial`  | POST       | `PATCH /api/usuario/N` con los campos modificados. |
| `/usuarios/editar?id=N&seccion=password`     | GET        | Formulario independiente de contraseña.            |
| `/usuarios/actualizar` con `accion=password` | POST       | `PATCH /api/usuario/N/password`.                   |
| `/usuarios/editar?id=N&seccion=eliminar`     | GET        | Confirmación para desactivar la cuenta.            |
| `/usuarios/actualizar` con `accion=eliminar` | POST       | `DELETE /api/usuario/N` (borrado lógico).          |

### Validaciones principales

- Registro con `username`, correo válido, nombre completo opcional y
  contraseña con confirmación.
- Contraseñas de **12 a 72 bytes**, sin mostrarlas nuevamente después de
  un error.
- Edición del perfil separada del cambio de contraseña.
- `PATCH` envía únicamente los campos realmente modificados; si no hay
  cambios, se informa al usuario.
- Tokens CSRF para las operaciones de formularios administrativos.
- Mensajes comprensibles para duplicados (`409`), datos inválidos
  (`422`) y falta de autorización (`401`/`403`).
- Confirmación antes de desactivar un usuario. El backend conserva su
  fila con **`activo=0`** y el listado activo deja de mostrarla.
- El último administrador activo debe permanecer protegido por las
  reglas del backend.

**Registrar un usuario no le asigna un rol automáticamente.** Para que
pueda iniciar sesión, ADMIN debe asignarle un rol desde el módulo
`rol_usuario`.

## 7. Catálogo y asignaciones de roles — V3

### 7.1 Catálogo fijo `rol`

`rol` es un **catálogo fijo de consulta**, no un CRUD administrativo.
Los únicos nombres contemplados son `admin` y `consulta`; desde la
aplicación no se crean, editan ni retiran tipos de rol.

La pantalla web `/roles` está destinada a `admin` y presenta el catálogo
con las columnas **ID, Nombre y Descripción**. No muestra botones para
crear, editar ni retirar roles.

| Método y ruta de la API | Operación                                     |
|-------------------------|-----------------------------------------------|
| `GET /api/rol`          | Listar los roles activos del catálogo.        |
| `GET /api/rol/{id}`     | Consultar un rol activo por su identificador. |

Ambas operaciones son administrativas y requieren `admin`. Para un
usuario `admin`, las peticiones `POST`, `PUT`, `PATCH` y `DELETE` sobre
las rutas del catálogo responden **405 Method Not Allowed** e incluyen
`Allow: GET`.

Los identificadores son generados por MariaDB. En la base local revisada
se observaron los IDs 1 y 2 para `admin` y `consulta`, respectivamente,
pero **no son valores universales** y el frontend no debe tratarlos como
constantes. El campo `activo` se conserva como dato interno de
persistencia y filtro; no es editable desde la interfaz ni desde la API.
La columna `fecha_creacion` también se conserva en la base.

La lectura del catálogo por parte de `consulta` debe rechazarse con
**403**, porque `/roles` es una función administrativa. Este
comportamiento está definido, pero la petición HTTP directa con un token
`consulta` sigue pendiente de comprobación.

### 7.2 Administración de roles por usuario — `rol_usuario`

`rol_usuario` gestiona la relación entre una cuenta y un rol; **no crea
ni modifica tipos de rol**. La administración de estas asignaciones es
exclusiva de `admin`.

| Ruta web                                                 | Método web | Operación                                                   |
|----------------------------------------------------------|------------|-------------------------------------------------------------|
| `/roles-usuarios`                                        | GET        | Consultar asignaciones y mostrar el formulario.             |
| `/roles-usuarios/asignar`                                | POST       | `POST /api/rol_usuario` para crear un vínculo.              |
| `/roles-usuarios/confirmar-retiro?usuario_id=N&rol_id=R` | GET        | Consultar y confirmar una asociación existente.             |
| `/roles-usuarios/retirar`                                | POST       | `DELETE /api/rol_usuario/N/R` para retirar solo el vínculo. |

El módulo permite seleccionar un usuario activo y un rol, previene
asignaciones duplicadas (el backend responde `409`), solicita
confirmación adicional antes de asignar privilegios `admin` y protege
con CSRF las operaciones de asignación y retiro.

**Pendiente de revisión:** la última comprobación visual del catálogo
`/roles` no incluyó el formulario de `/roles-usuarios`. Por eso todavía
no se afirma que el selector de roles consuma dinámicamente el catálogo
ni que haya dejado de depender de los IDs locales 1 y 2. Esa integración
debe revisarse sin asumir que los identificadores se repiten en otras
instalaciones.

El backend también protege el último administrador activo. La interfaz
puede mostrar el aviso «Último admin protegido», pero el rechazo real de
una petición HTTP de retiro debe verificarse en una base de datos
descartable, nunca sobre la cuenta real.

**Diferencia con el borrado lógico de usuarios:** retirar `rol_usuario`
elimina **físicamente la asociación**, porque la tabla puente no tiene
columna `activo`; no elimina físicamente el usuario ni el registro del
catálogo `rol`. Las asignaciones repetidas responden `409`; esta tabla
utiliza GET, POST y DELETE, no PUT/PATCH.

## 8. Pruebas funcionales y de seguridad realizadas

Las siguientes evidencias fueron aportadas por Ángel mediante terminal,
navegador y consultas **de solo lectura** en phpMyAdmin:

| Prueba                             | Resultado observado                                                                                                                    |
|------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------|
| Levantar Docker y validar sintaxis | `docker compose up -d --build` correcto; PHP `-l` sin errores en archivos principales y del módulo de roles comprobados.               |
| ADMIN                              | Login correcto y acceso a las pantallas de administración.                                                                             |
| Alta de usuarios                   | Se registraron cuentas de prueba como Mario, Mariana y `prueba_delete_v3`.                                                             |
| Edición y contraseña               | Se actualizaron datos de perfil y contraseña; el acceso de prueba funcionó al asignar el rol requerido.                                |
| Borrado lógico                     | `prueba_delete_v3` desapareció de activos y MariaDB conservó el registro con `activo=0`.                                               |
| Asignación de `consulta`           | Mariana recibió el rol desde el frontend y pudo iniciar sesión.                                                                        |
| Rol duplicado                      | Se mostró un mensaje de asignación ya existente.                                                                                       |
| Acceso de `consulta`               | Lectura de **218 áreas de conocimiento**, sin botones CRUD; ruta administrativa directa denegada.                                      |
| Retiro de rol                      | La relación dejó de aparecer y SQL devolvió cero filas durante el retiro.                                                              |
| `rol_usuario` aislado              | **15/15** escenarios aprobados para listar, asignar, consultar, duplicados, retirar y validar identificadores.                         |
| Catálogo `rol` aislado             | **6 escenarios** aprobados para listado, consulta individual, catálogo vacío, inexistente e identificador inválido.                    |
| API del catálogo con `admin`       | `GET /api/rol`, `GET /api/rol/1` y `GET /api/rol/2` respondieron `200`; POST, PUT, PATCH y DELETE respondieron `405` con `Allow: GET`. |
| Pantalla `/roles`                  | Revisión visual: aparecen `admin` y `consulta`, con ID, nombre y descripción; no hay acciones para crear, editar o retirar.            |
| Cuenta sin rol                     | No pudo iniciar una sesión nueva.                                                                                                      |
| Revocación durante sesión abierta  | Al retirar su único rol, Mariana dejó de poder consultar el recurso protegido aunque la ventana seguía abierta.                        |
| Recuperación de acceso             | ADMIN devolvió el rol; después de iniciar sesión otra vez, Mariana recuperó la lectura.                                                |
| Último administrador               | La interfaz mostró «Último admin protegido»; un SELECT confirmó que ADMIN era la única cuenta administradora activa.                   |

**La protección del último ADMIN se verificó visualmente y por SQL, pero
no se ejecutó una retirada real de esa cuenta.** La prueba de rechazo
HTTP debe hacerse en una base descartable, no en el volumen habitual del
equipo.

### Comandos de verificación de sintaxis

Ejecutar desde la raíz del **repositorio backend**, donde está
`docker-compose.yml`:

``` powershell
docker compose exec -T front-php php -l /app/index.php
docker compose exec -T front-php php -l /app/cliente_api.php
docker compose exec -T front-php php -l /app/vistas/compartidas/plantilla.php
docker compose exec -T front-php php -l /app/rutas/rutas_usuario.php
docker compose exec -T front-php php -l /app/rutas/rutas_rol_usuario.php
docker compose exec -T front-php php -l /app/vistas/rol_usuario/gestion.php
```

Estos comandos **solo comprueban sintaxis**, no sustituyen las pruebas
de autorización o persistencia. No se afirma haber ejecutado ya todos
los comandos de la lista.

## 9. Pendientes para cerrar V3

1.  Comprobar mediante HTTP que `consulta` recibe `403` al intentar
    consultar `GET /api/rol`.
2.  Revisar el selector de roles de `/roles-usuarios` y confirmar que
    obtiene los identificadores del catálogo, sin asumir que los IDs
    locales 1 y 2 son universales.
3.  Completar la regresión de las **16 pantallas V1/V2** para todos sus
    métodos y validaciones con perfiles `admin` y `consulta`.
4.  Comprobar el frontend con la **API apagada** y confirmar que muestra
    un aviso de servicio no disponible, sin datos ficticios ni acceso
    directo a MariaDB.
5.  Completar las verificaciones de logout/cookies, CSRF manipulado,
    caducidad de JWT y respuestas de error de los formularios.
6.  Verificar el rechazo HTTP del retiro del último ADMIN y otros
    escenarios negativos en una **base de datos descartable**; no
    probarlos sobre la cuenta real.
7.  Comprobar instalación limpia, idempotencia del bootstrap y ausencia
    de secretos versionados.
8.  Resolver con el equipo la diferencia entre streams HTTP y cURL
    indicada por la metodología; realizar revisión cruzada y cerrar las
    compuertas SpecKit.
9.  Preparar los commits, Pull Requests y el tag `v3` **únicamente
    cuando la versión completa esté aprobada**.

El estado de los módulos desarrollados por Ángel no equivale a declarar
terminada toda V3.

## 10. Documentación y trabajo colaborativo

La documentación SpecKit centralizada se mantiene en el repositorio
**`Proyecto_Investigacion_Backend`**, dentro de:

``` text
docs/spec_kit/versiones/v3_seguridad/
```

Ahí se encuentran la especificación (`2_spec.md`), el plan
(`3_plan.md`), los contratos (`6_contracts.md`), el quickstart
(`7_quickstart.md`), las tareas (`8_tasks.md`), el checklist
(`9_checklist.md`), el registro de pruebas del frontend
(`REGISTRO_FASE6_FRONTEND.md`) y el registro del catálogo fijo `rol`
(`REGISTRO_FASE7_CATALOGO_ROL_20261119.md`). **No se duplican documentos
SpecKit en este repositorio frontend.**

### Rama de trabajo

El desarrollo de Ángel en este repositorio se realiza en:

``` text
feature/angel-frontend-v3
```

La rama complementaria del backend es `feature/angel-investigacion-v3`.
Los commits y PR se preparan **por repositorio**; el integrador revisa
antes de llevar cambios a `main`. Mantener separados frontend, backend y
sus responsabilidades facilita las pruebas y la sustentación del
proyecto.
