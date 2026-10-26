# Proyecto de Investigación — Versión 2

## Integrantes

- Ángel David Gutiérrez Ladino
- Miguel Ángel Lotero Álvarez

Proyecto académico desarrollado en **PHP puro**, **HTML**, **CSS**, **Bootstrap**
y **Docker** para el módulo de Investigación.

La versión 2 amplía los seis recursos sin claves foráneas de la v1 con los diez
recursos del módulo que poseen relaciones entre tablas.

El sistema está dividido en dos aplicaciones independientes:

- **Backend:** API REST desarrollada en PHP y conectada a MariaDB.
- **Frontend:** aplicación PHP que consume la API mediante HTTP y presenta las
  páginas HTML al usuario.

Este repositorio corresponde exclusivamente al **frontend**.

El frontend no se conecta directamente a MariaDB y no contiene credenciales de
la base de datos.

---

## Estado de la versión

La **v2 se encuentra en desarrollo**.

Actualmente se cuenta con:

- los seis CRUD de la v1 funcionando en el frontend;
- las rutas y vistas organizadas en carpetas por entidad;
- un Front Controller que dirige las solicitudes a los archivos de rutas;
- Bootstrap local y una hoja de estilos CSS personalizada;
- la comunicación HTTP con la API mediante `cliente_api.php`;
- el CRUD de `grupo_investigacion` integrado y comprobado desde el navegador;
- el formulario de grupos con selección de universidades activas.

De las diez entidades nuevas de la v2, **una tiene su interfaz implementada**:
`grupo_investigacion`. Las otras nueve están pendientes de desarrollo.

La implementación se realiza progresivamente siguiendo el Spec Kit del
repositorio backend. La incorporación de código a la rama principal se coordina
mediante Pull Requests.

---

## Cómo levantar el proyecto

El archivo `docker-compose.yml` se encuentra en el repositorio backend.

Por esta razón, para levantar el sistema completo se debe abrir una terminal
desde la raíz del repositorio backend y ejecutar:

```powershell
docker compose up -d --build
```

Para comprobar que los contenedores están activos:

```powershell
docker compose ps
```

Para reconstruir solamente el frontend después de modificar sus archivos:

```powershell
docker compose up -d --build front-php
```

Para detener los contenedores sin borrar los datos persistentes:

```powershell
docker compose down
```

No es necesario eliminar el volumen de MariaDB para actualizar las páginas
PHP del frontend.

## Accesos locales

| Servicio | Dirección |
|---|---|
| Frontend | http://localhost:8110 |
| API | http://localhost:8111 |
| phpMyAdmin | http://localhost:8105 |
| MariaDB | `localhost:13330` |

El endpoint raíz de la API permite comprobar que el backend está activo:

http://localhost:8111/

La página de inicio del frontend está disponible en:

http://localhost:8110/

## Qué incluye la v2

La versión 2 mantiene las seis entidades de la v1 e incorpora las diez entidades
con claves foráneas del módulo de Investigación.

### Recursos conservados de la v1

| Recurso | Llave primaria | Datos principales |
|---|---|---|
| `area_conocimiento` | `id` | gran área, área y disciplina |
| `objetivo_desarrollo_sostenible` | `id` | nombre y categoría |
| `area_aplicacion` | `id` | nombre |
| `termino_clave` | `termino` | término y término en inglés |
| `universidad` | `id` | nombre, tipo y ciudad |
| `linea_investigacion` | `id` autoincremental | nombre y descripción |

### Recursos incorporados progresivamente en la v2

| Recurso | Relación principal | Estado en el frontend |
|---|---|---|
| `grupo_investigacion` | universidad | Implementado |
| `docente` | línea de investigación principal | Pendiente |
| `semillero` | grupo de investigación | Pendiente |
| `participa_semillero` | docente y semillero | Pendiente |
| `participa_grupo` | docente y grupo de investigación | Pendiente |
| `semillero_linea` | semillero y línea de investigación | Pendiente |
| `grupo_linea` | grupo y línea de investigación | Pendiente |
| `ac_linea` | línea y área de conocimiento | Pendiente |
| `ods_linea` | línea y ODS | Pendiente |
| `aa_linea` | área de aplicación y línea | Pendiente |

Las últimas siete entidades son tablas de asociación con **claves primarias
compuestas**. Sus formularios y rutas se definirán al implementar cada recurso,
de acuerdo con los contratos del backend.

Las tablas `rol`, `usuario` y `rol_usuario` pertenecen al módulo de gestión de
usuarios previsto para la v3; no forman parte de los diez CRUD de la v2.

## Estructura principal del frontend

```text
front_php/
├── index.php
├── Dockerfile
├── cliente_api.php
├── rutas/
│   ├── compartidas/
│   │   └── acciones_catalogo.php
│   ├── rutas_area_conocimiento.php
│   ├── rutas_objetivo_desarrollo_sostenible.php
│   ├── rutas_area_aplicacion.php
│   ├── rutas_termino_clave.php
│   ├── rutas_universidad.php
│   ├── rutas_linea_investigacion.php
│   └── rutas_grupo_investigacion.php
├── vistas/
│   ├── inicio.php
│   ├── compartidas/
│   │   ├── plantilla.php
│   │   ├── no_encontrada.php
│   │   └── crud_catalogos.php
│   ├── area_conocimiento/
│   ├── objetivo_desarrollo_sostenible/
│   ├── area_aplicacion/
│   ├── termino_clave/
│   ├── universidad/
│   ├── linea_investigacion/
│   └── grupo_investigacion/
│       ├── lista.php
│       └── formulario.php
└── publico/
    ├── bootstrap.min.css
    ├── bootstrap.bundle.min.js
    └── css/
        └── estilos.css

.gitignore
README.md
```

### front_php/index.php

Es el **Front Controller** del frontend.

Todas las peticiones de navegación entran por este archivo. Su responsabilidad
es interpretar la ruta solicitada y cargar el archivo de rutas correspondiente.

También se encarga de:

- mostrar la página de inicio;
- servir correctamente los archivos estáticos del directorio `publico/`;
- cargar el cliente HTTP y las vistas compartidas;
- mostrar una página 404 cuando no existe una ruta.

No contiene SQL ni reglas de negocio de la base de datos.

### front_php/rutas/

Contiene los archivos que atienden las solicitudes de cada entidad.

Cada archivo de rutas puede:

- mostrar un listado o un formulario;
- recibir los datos enviados mediante formularios HTML;
- preparar esos datos antes de enviarlos a la API;
- ejecutar las operaciones del cliente HTTP;
- mostrar mensajes de validación y de error;
- redirigir después de guardar o retirar un registro.

El archivo `rutas/compartidas/acciones_catalogo.php` permite reutilizar acciones
de los catálogos de V1 sin concentrar todas las rutas en `index.php`.

La entidad `grupo_investigacion` tiene su archivo propio:

```text
front_php/rutas/rutas_grupo_investigacion.php
```

### front_php/cliente_api.php

Es el archivo responsable de la comunicación HTTP con la API REST.

Este archivo se encarga de:

- consultar registros;
- obtener un registro específico;
- crear registros;
- reemplazar registros mediante PUT;
- actualizar parcialmente registros mediante PATCH;
- retirar registros mediante DELETE;
- interpretar los códigos HTTP y las respuestas JSON.

La comunicación utiliza solicitudes HTTP desde PHP. El frontend trabaja con
los contratos publicados por la API y no utiliza los modelos o repositorios
internos del backend.

### front_php/vistas/

Contiene las páginas HTML que se muestran al usuario, organizadas por entidad.

Las vistas incluyen:

- la pantalla de inicio;
- los listados de registros;
- los formularios de creación y edición;
- los mensajes de éxito y de error;
- la pantalla de página no encontrada.

La carpeta `vistas/compartidas/` contiene la plantilla general de la página y
las vistas reutilizables.

Para `grupo_investigacion` se utilizan:

```text
front_php/vistas/grupo_investigacion/lista.php
front_php/vistas/grupo_investigacion/formulario.php
```

### front_php/publico/

Contiene los archivos estáticos de la aplicación:

- Bootstrap CSS almacenado localmente;
- Bootstrap JavaScript almacenado localmente;
- la hoja de estilos `css/estilos.css`.

El CSS personalizado se carga después de Bootstrap para complementar sus
estilos. Los archivos públicos se entregan directamente sin tratarlos como
rutas de navegación.

### front_php/Dockerfile

Define la imagen utilizada para ejecutar el frontend en PHP.

El servidor del frontend escucha dentro del contenedor en el puerto **8110**.

## Pantallas de la v2

Cada recurso dispone de una ruta propia en el frontend. No se utiliza una
pantalla genérica que reciba el nombre de una tabla como parámetro.

Las direcciones actuales de las siete entidades implementadas son:

```text
/areas-conocimiento
/objetivos-desarrollo-sostenible
/areas-aplicacion
/terminos-clave
/universidades
/lineas-investigacion
/grupos-investigacion
```

### Pantallas de Grupo de Investigación

| Método | Ruta | Acción |
|---|---|---|
| GET | `/grupos-investigacion` | Mostrar el listado de grupos activos |
| GET | `/grupos-investigacion/nuevo` | Mostrar el formulario de creación |
| GET | `/grupos-investigacion/editar?id=123` | Mostrar el formulario de edición |
| POST | `/grupos-investigacion/crear` | Solicitar la creación de un grupo |
| POST | `/grupos-investigacion/actualizar` | Solicitar actualización completa o parcial |
| POST | `/grupos-investigacion/retirar` | Solicitar el retiro lógico |

Los formularios HTML envían solicitudes POST al frontend. Después, el frontend
utiliza el método HTTP que corresponde para comunicarse con la API REST.

Las otras nueve entidades de V2 tendrán rutas propias cuando se implementen.

## Relaciones y claves foráneas

La principal novedad de V2 es que algunos registros dependen de otros.

En `grupo_investigacion`, el campo `universidsad` referencia a
`universidad.id`. En el formulario:

- las universidades se consultan a través de la API;
- el usuario puede seleccionar una universidad activa;
- existe la opción **Sin universidad**, porque la relación es opcional;
- el formulario prepara el identificador como entero o `null` antes de enviarlo
  a la API.

El nombre `universidsad` se conserva tal como aparece en el esquema SQL y en
los contratos actuales del backend. No se cambia únicamente desde el frontend.

El frontend puede ayudar con las validaciones de los campos, pero corresponde
al backend comprobar la existencia de las claves foráneas y aplicar las reglas
de integridad referencial.

## Comunicación con la API

El frontend se comunica con el backend únicamente mediante HTTP.

El flujo normal de información es:

```text
Navegador
    ↓
Frontend PHP
    ↓ HTTP y JSON
API de Investigación
    ↓
MariaDB
```

El frontend nunca utiliza este flujo:

```text
Frontend PHP
    ↓
MariaDB
```

Por lo tanto, este repositorio:

- no utiliza PDO;
- no ejecuta consultas SQL;
- no contiene usuario ni contraseña de MariaDB;
- no importa modelos, servicios o repositorios del backend;
- solamente conoce las rutas, los datos y los códigos HTTP de la API.

La dirección interna de la API se obtiene mediante la variable de entorno:

```text
URL_API
```

El archivo encargado de utilizarla es:

```text
front_php/cliente_api.php
```

## Manejo de formularios

Los formularios HTML reciben los valores introducidos por el usuario y los
preparan para las solicitudes JSON al backend.

En el formulario de `grupo_investigacion` se incluyen:

- identificador del grupo, proporcionado al crearlo;
- nombre y fecha de fundación;
- URL de GrupLAC, categoría y convocatoria, cuando correspondan;
- universidad relacionada, si existe;
- indicador de grupo interno y ámbito.

Para editar existen dos acciones:

- **Guardar la ficha completa:** el frontend envía los campos editables mediante PUT.
- **Guardar solo lo que cambié:** el frontend compara los datos y envía mediante
  PATCH solamente los campos modificados.

Las validaciones definitivas, incluidas las longitudes, las fechas y las
relaciones entre registros, corresponden al backend.

La interfaz utiliza mensajes comprensibles y evita exponer detalles internos
del protocolo o de MariaDB al usuario.

## Retiro de registros

Desde el frontend se utiliza el término **retirar** y no eliminar.

Al retirar un grupo, el frontend solicita a la API que ejecute DELETE. Es el
backend quien modifica el campo `activo` para aplicar el borrado lógico.

Después de retirarlo, el grupo deja de aparecer en los listados normales y no
puede consultarse como un recurso activo.

El frontend no ejecuta consultas SQL ni modifica directamente `activo`.

Un identificador retirado permanece almacenado en MariaDB y no debe suponerse
que queda disponible para volver a crear otro registro con el mismo ID.

## Listas vacías

Algunas entidades de V2 pueden comenzar sin registros activos.

Una lista vacía no se considera un error. En el caso de grupos de investigación,
la pantalla muestra un mensaje como:

```text
Todavía no hay grupos de investigación activos.
```

El usuario puede continuar navegando y acceder al formulario de registro.

Esta situación es diferente de una falla de comunicación con la API.

## API no disponible

El frontend y la API son aplicaciones independientes.

Si la API deja de responder, el frontend debe continuar mostrando sus páginas
y avisar que no puede consultar o guardar los datos.

En particular:

- no debe intentar conectarse directamente a MariaDB;
- no debe inventar registros para completar las tablas;
- no debe tratar un fallo de conexión como si fuera una lista vacía;
- debe mostrar un mensaje de error comprensible.

## Archivos estáticos

El frontend utiliza el servidor integrado de PHP y `index.php` como enrutador.

Las peticiones de archivos físicos dentro de `publico/`, como Bootstrap y
`css/estilos.css`, se entregan directamente mediante el servidor. Las demás
solicitudes continúan por el sistema de rutas de la aplicación.

Esto permite mantener separados los archivos visuales y las acciones de los
formularios.

## Pruebas y verificaciones

Para comprobar la sintaxis de los archivos PHP, desde la raíz del repositorio
frontend se puede ejecutar:

```powershell
Get-ChildItem -Path .\front_php -Recurse -Filter *.php |
    ForEach-Object { php -l $_.FullName }
```

Para verificar que el CSS está disponible:

```powershell
Invoke-WebRequest -Uri "http://localhost:8110/publico/css/estilos.css" -UseBasicParsing |
    Select-Object StatusCode
```

Para la primera entidad de V2 se deben comprobar, desde el navegador:

- el listado `/grupos-investigacion`;
- el formulario `/grupos-investigacion/nuevo`;
- la consulta de universidades activas;
- la creación de un grupo con un ID no utilizado;
- la edición completa y la edición parcial;
- el retiro lógico;
- la presentación de errores cuando la API no está disponible.

El frontend de `grupo_investigacion` ya fue comprobado funcionalmente desde el
navegador. Las pruebas de las nueve entidades restantes se realizarán cuando
sus pantallas estén implementadas.

## Separación entre frontend y backend

Aunque ambas aplicaciones están desarrolladas en PHP, permanecen en
repositorios independientes.

El frontend no utiliza `require_once` para cargar archivos internos del
backend. No comparte directamente modelos, controladores, servicios,
repositorios ni conexiones a MariaDB.

```text
Frontend
    ↓
cliente_api.php
    ↓ HTTP
Backend
```

Esta separación permite modificar las capas internas del backend sin cambiar
la interfaz, siempre que se mantengan los contratos de la API.

## Alcance de la v2

La v2 tiene como objetivo ofrecer interfaces CRUD para las **16 entidades del
módulo de Investigación**: las seis conservadas de V1 y las diez relacionadas
que se incorporan progresivamente.

En el estado actual del proyecto hay siete interfaces disponibles: las seis de
V1 y `grupo_investigacion` de V2.

El desarrollo de autenticación, usuarios, roles, JWT, dashboard y despliegue
corresponde a versiones posteriores, según la planificación del proyecto.

La documentación técnica de las relaciones, los contratos y las tareas se
mantiene en `docs/spec_kit/` dentro del repositorio backend.
