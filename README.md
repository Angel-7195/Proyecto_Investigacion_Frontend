# Proyecto de Investigación — Versión 1

## Integrantes

- Ángel David Gutiérrez Ladino
- Miguel Ángel Lotero Álvarez

Proyecto académico desarrollado en **PHP puro** y **Docker** para el módulo de
Investigación.

La versión 1 trabaja con los seis recursos del módulo que no poseen claves
foráneas.

El sistema está dividido en dos aplicaciones independientes:

- **Backend:** API REST desarrollada en PHP.
- **Frontend:** aplicación PHP que consume la API mediante HTTP.

Este repositorio corresponde exclusivamente al **frontend**.

El frontend no se conecta directamente a MariaDB y no contiene credenciales de
la base de datos.

---

## Estado de la versión

La **v1 se encuentra en desarrollo**.

Actualmente se encuentra configurado:

- el contenedor del frontend PHP;
- la comunicación con la API mediante HTTP;
- el Front Controller del frontend;
- la estructura base para las vistas;
- la carpeta de archivos públicos.

La implementación de las pantallas correspondientes a los seis recursos se
realiza de forma progresiva siguiendo la documentación del Spec Kit ubicada en
el repositorio backend.

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

Para detenerlos:

```powershell
docker compose down
```

## Accesos locales

| Servicio | Dirección |
|---|---|
| Frontend | http://localhost:8110 |
| API | http://localhost:8111 |
| phpMyAdmin | http://localhost:8105 |
| MariaDB | `localhost:13330` |

El endpoint raíz de la API permite comprobar que el servicio está activo:

http://localhost:8111/

## Qué incluye la v1

La versión 1 implementa CRUD para los seis recursos del módulo de Investigación
que no tienen claves foráneas.

| Recurso | Llave primaria | Datos principales |
|---|---|---|
| `area_conocimiento` | `id` | gran área, área y disciplina |
| `objetivo_desarrollo_sostenible` | `id` | nombre y categoría |
| `area_aplicacion` | `id` | nombre |
| `termino_clave` | `termino` | término y término en inglés |
| `universidad` | `id` | nombre, tipo y ciudad |
| `linea_investigacion` | `id` autoincremental | nombre y descripción |

## Estructura principal del frontend

```
front_php/
├── index.php
├── Dockerfile
├── cliente_api.php
├── publico/
└── vistas/

.gitignore
README.md
```

### front_php/index.php

Es el Front Controller del frontend.

Todas las peticiones de navegación entran por este archivo y desde allí se
decide qué pantalla debe mostrarse.

Sus responsabilidades principales son:
- interpretar la ruta solicitada;
- procesar las acciones enviadas desde los formularios;
- solicitar información mediante cliente_api.php;
- redirigir después de determinadas operaciones;
- enviar los datos correspondientes a las vistas;
- responder con una pantalla 404 cuando una ruta no existe.

No contiene SQL ni accede directamente a MariaDB.

### front_php/cliente_api.php

Es el único archivo del frontend encargado de comunicarse con la API.
La comunicación se realiza mediante HTTP.

Este archivo se encarga de:
- consultar registros;
- obtener un registro específico;
- crear registros;
- reemplazar registros;
- actualizar parcialmente registros;
- retirar registros;
- interpretar las respuestas recibidas desde la API.

El frontend trabaja con los datos JSON enviados por la API y no utiliza las
clases internas del backend.

### front_php/vistas/

Contiene las páginas que se muestran al usuario.
Las vistas presentan:
- pantalla de inicio;
- listados;
- formularios de creación;
- formularios de edición;
- mensajes de éxito;
- mensajes de error;
- pantalla de página no encontrada.

Las vistas pueden compartir una plantilla común para mantener una presentación
uniforme.

### front_php/publico/

Contiene los archivos estáticos utilizados por la interfaz.
Por ejemplo:
- hojas de estilo;
- Bootstrap almacenado localmente;
- archivos JavaScript;
- otros recursos visuales.

Estos archivos se sirven directamente y no pasan por las rutas normales de la
aplicación.

### front_php/Dockerfile

Define la imagen utilizada para ejecutar el frontend con PHP.

El servidor del frontend escucha dentro del contenedor en el puerto 8110.

## Pantallas de la v1

Cada recurso posee una dirección propia dentro del frontend.

No se utiliza una pantalla genérica que reciba el nombre de una tabla como
parámetro.

Las rutas principales contempladas para la v1 son:

```
/areas-de-conocimiento
/objetivos-desarrollo-sostenible
/areas-de-aplicacion
/terminos-clave
/universidades
/lineas-de-investigacion
```

Cada recurso tendrá además las rutas necesarias para crear, editar y retirar
sus registros.

Por ejemplo, para áreas de conocimiento:

```text
GET  /areas-de-conocimiento
GET  /areas-de-conocimiento/nuevo
POST /areas-de-conocimiento/nuevo
GET  /areas-de-conocimiento/{clave}/editar
POST /areas-de-conocimiento/{clave}/editar
POST /areas-de-conocimiento/{clave}/retirar
```

Las demás pantallas siguen el mismo concepto, utilizando una dirección propia
para cada recurso.

## Comunicación con la API

El frontend se comunica con el backend únicamente mediante HTTP.

El flujo normal de información es:

```text
Navegador
    ↓
Frontend PHP
    ↓ HTTP
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
- solamente conoce las respuestas y contratos HTTP de la API.

La dirección interna de la API se obtiene mediante la variable de entorno:

```text
URL_API
```

El archivo encargado de utilizar esta dirección es:

```text
front_php/cliente_api.php
```

## Manejo de formularios

Los formularios HTML producen valores en forma de texto.

El frontend prepara los datos antes de enviarlos a la API, pero las
validaciones definitivas y las reglas de negocio pertenecen al backend.

Para la edición de una ficha se contemplan dos acciones diferentes:

- **Guardar la ficha completa:** envía todos los campos editables del registro.
- **Guardar solo lo que cambié:** envía únicamente los campos que se desean modificar.

Desde la interfaz no es necesario mostrar al usuario términos técnicos como:

```text
PUT
PATCH
422
/api/
MariaDB
```

La interfaz presenta las operaciones mediante acciones comprensibles para la
persona que utiliza el sistema.

## Retiro de registros

Desde el frontend se utiliza el término **retirar** y no eliminar.

Cuando una persona retira un registro, el frontend envía la solicitud
correspondiente a la API.

El frontend no modifica directamente el campo `activo` ni ejecuta operaciones
sobre MariaDB.

El borrado lógico es responsabilidad exclusiva del backend.

Después de retirar correctamente un registro, este deja de mostrarse en los
listados normales de la aplicación.

## Listas vacías

Algunos recursos de la v1 pueden comenzar sin registros.

Una lista vacía no se considera un error del sistema.

Cuando la API indique que no existen registros disponibles, el frontend debe
mostrar un mensaje comprensible, por ejemplo:

```text
Todavía no hay registros.
```

La pantalla debe continuar funcionando y permitir que el usuario cree un
nuevo registro.

Esto es diferente a una situación en la que la API no se encuentre
disponible.

## API no disponible

El frontend y la API son aplicaciones independientes.

Si la API deja de responder, el frontend debe continuar mostrando su
estructura y sus pantallas, pero no debe presentar datos provenientes de
MariaDB.

En esta situación se debe mostrar un mensaje indicando que el servicio no se
encuentra disponible.

Esto permite comprobar que el frontend no se conecta directamente a la base de
datos.

## Archivos estáticos

El frontend utiliza el servidor integrado de PHP con `index.php` como
enrutador.

Las solicitudes de archivos físicos existentes, como hojas de estilo o
archivos JavaScript, deben ser servidas directamente.

Para esto se utiliza:

```php
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . $ruta;

    if ($ruta !== '/' && is_file($archivo)) {
        return false;
    }
}
```

De esta manera, archivos como los almacenados en:

```text
front_php/publico/
```

no son tratados como rutas de navegación de la aplicación.

## Separación entre frontend y backend

Aunque el frontend y el backend están desarrollados en PHP, ambos se mantienen
como aplicaciones y repositorios independientes.

El frontend no utiliza `require_once` para cargar archivos internos del
backend.

No comparte directamente:

- modelos;
- controladores;
- servicios;
- repositorios;
- conexión a MariaDB.

La comunicación entre ambos proyectos se realiza solamente mediante HTTP y
datos JSON.

```text
Frontend
    ↓
cliente_api.php
    ↓ HTTP
Backend
```

Esta separación permite que la implementación interna del backend pueda
cambiar sin modificar el frontend, siempre que se respeten los contratos de
la API.

## Alcance de la v1

La versión 1 del frontend se limita a las pantallas correspondientes a los
seis recursos sin claves foráneas:

```text
Áreas de conocimiento
Objetivos de Desarrollo Sostenible
Áreas de aplicación
Términos clave
Universidades
Líneas de investigación
```

El recorrido esperado para las operaciones realizadas desde la interfaz es:

```text
Usuario
   ↓
Frontend PHP
   ↓
cliente_api.php
   ↓ HTTP
API de Investigación
```

Las funcionalidades correspondientes a versiones posteriores quedan fuera del
alcance de la v1.

Esto incluye:

- tablas con claves foráneas;
- autenticación;
- usuarios;
- roles;
- JWT;
- consultas multitabla;
- dashboard;
- demás funcionalidades correspondientes a versiones posteriores.