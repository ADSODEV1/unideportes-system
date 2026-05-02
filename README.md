# 🏆 Sistema CRUD Unideportes

Sistema completo de gestión de usuarios con operaciones CRUD (Crear, Leer, Actualizar, Eliminar) para el control de personal en Unideportes.

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Base de Datos](#-base-de-datos)
- [Funcionalidades CRUD](#-funcionalidades-crud)
- [Uso del Sistema](#-uso-del-sistema)
- [Testing](#-testing)
- [API Endpoints](#-api-endpoints)
- [Seguridad](#-seguridad)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

## ✨ Características

- 🔐 **Autenticación segura** con contraseñas hasheadas
- 👥 **Gestión completa de usuarios** (Admin, Colaborador, Vendedor)
- 📊 **Panel administrativo** con métricas en tiempo real
- 🎯 **Roles y permisos** basados en usuario
- 🔍 **Búsqueda y filtrado** de usuarios
- 📱 **Interfaz responsive** y moderna
- 🧪 **Suite de testing** completa
- 🚀 **Arquitectura MVC** organizada

## 🛠 Tecnologías

### Backend
- **PHP 8.0+** - Lenguaje principal
- **MySQL 8.0+** - Base de datos relacional
- **MySQLi** - Extensión para conexión a BD

### Frontend
- **HTML5** - Estructura
- **CSS3** - Estilos y diseño
- **JavaScript** - Interactividad

### Herramientas
- **XAMPP** - Entorno de desarrollo
- **phpMyAdmin** - Gestión de BD
- **Composer** - Gestión de dependencias (opcional)

## 📋 Requisitos

### Sistema Operativo
- Windows 10/11
- Linux (Ubuntu/Debian)
- macOS

### Software Requerido
- **XAMPP 8.0+** (Apache + MySQL + PHP)
- **Navegador web** moderno (Chrome, Firefox, Edge)
- **Editor de código** (VS Code recomendado)

### Requisitos PHP
- PHP 8.0 o superior
- Extensión MySQLi habilitada
- Funciones `password_hash()` y `password_verify()`

## 🚀 Instalación

### Paso 1: Clonar o Descargar
```bash
# Si usas Git
git clone https://github.com/tu-usuario/crudunideportes.git

# O descarga el ZIP y extráelo en:
C:\xampp\htdocs\crudunideportes
```

### Paso 2: Iniciar XAMPP
1. Abre el **Panel de Control de XAMPP**
2. Inicia los módulos:
   - ✅ Apache
   - ✅ MySQL

### Paso 3: Crear Base de Datos
1. Abre tu navegador: `http://localhost/phpmyadmin/`
2. Crea nueva base de datos: `unideportes`
3. Importa el archivo: `db/unideportes_bd_actual.sql`

### Paso 4: Configurar Conexión
Verifica que `config/connection.php` tenga:
```php
$host = "localhost";
$user = "root";
$pass = "";
$db = "unideportes_pruebas";
```

### Paso 5: Usuario Administrador
Ejecuta en phpMyAdmin:
```sql
INSERT INTO usuarios (username, password, role, name, lastname, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'Sistema', 'admin@unideportes.com');
```

**Credenciales por defecto:**
- Usuario: `admin`
- Contraseña: `admin123`

## ⚙️ Configuración

### Variables de Entorno
Edita `config/connection.php` según tu configuración:

```php
<?php
function connection(): mysqli {
    $host = "localhost";      // Tu servidor MySQL
    $user = "root";           // Usuario de BD
    $pass = "";               // Contraseña de BD
    $db = "unideportes";      // Nombre de la BD

    $conn = new mysqli($host, $user, $pass, $db);
    // ... resto del código
}
?>
```

### Permisos de Archivos
Asegúrate de que la carpeta del proyecto tenga permisos de escritura:
```bash
chmod -R 755 /opt/lampp/htdocs/crudunideportes
```

## 📁 Estructura del Proyecto

```crudunideportes/
├── 📁 config/
│   └── connection.php          # Configuración de BD
├── 📁 controllers/
│   ├── auth.php               # Autenticación principal
│   ├── login.php              # Login alternativo
│   ├── edit_user.php          # Actualizar usuarios
│   └── delete_user.php        # Eliminar usuarios
├── 📁 db/
│   ├── conexion.php           # Conexión legacy
│   └── unideportes_bd_actual.sql # Esquema de BD
├── 📁 models/
│   ├── insert_user.php        # Crear usuarios
│   └── update.php             # Formulario de edición
├── 📁 public/
│   ├── index.php              # Página principal/login
│   └── actualizar_passwords.php # Script de actualización
├── 📁 views/
│   ├── admin_user.php         # Gestión de usuarios
│   ├── panel_admin.php        # Dashboard admin
│   ├── panel_vendedor.php     # Dashboard vendedor
│   ├── header.php             # Cabecera común
│   └── footer.php             # Pie de página
├── 📁 assets/
│   └── CSS/
│       └── style.css          # Estilos principales
├── test.php                   # Suite de testing
└── README.md                  # Esta documentación
```

## 🗄️ Base de Datos

### Tabla: `usuarios`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | ID único del usuario |
| `name` | VARCHAR(50) | Nombre del usuario |
| `lastname` | VARCHAR(50) | Apellido del usuario |
| `username` | VARCHAR(50) UNIQUE | Nombre de usuario único |
| `password` | VARCHAR(255) | Contraseña hasheada |
| `email` | VARCHAR(100) | Correo electrónico |
| `role` | ENUM('admin','colaborador') | Rol del usuario |

### Tabla: `productos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | ID único del producto |
| `nombre` | VARCHAR(100) | Nombre del producto |
| `referencia` | VARCHAR(50) UNIQUE | Referencia única |
| `talla` | VARCHAR(10) | Talla del producto |
| `stock` | INT | Cantidad en inventario |
| `precio` | DECIMAL(10,2) | Precio del producto |
| `created_at` | TIMESTAMP | Fecha de creación |

## 🎯 Funcionalidades CRUD

### 👤 Gestión de Usuarios

#### **CREATE** - Crear Usuario
- **Archivo:** `models/insert_user.php`
- **Función:** Agregar nuevos usuarios al sistema
- **Campos:** nombre, apellido, usuario, email, contraseña, rol
- **Validaciones:**
  - Email válido
  - Usuario único
  - Contraseña hasheada

#### **READ** - Leer Usuarios
- **Archivo:** `views/admin_user.php`
- **Función:** Mostrar lista completa de usuarios
- **Características:**
  - Tabla responsive
  - Información completa
  - Contador de usuarios

#### **UPDATE** - Actualizar Usuario
- **Archivos:**
  - `models/update.php` (formulario)
  - `controllers/edit_user.php` (procesamiento)
- **Función:** Modificar datos de usuarios existentes
- **Campos editables:** nombre, apellido, usuario, email, contraseña

#### **DELETE** - Eliminar Usuario
- **Archivo:** `controllers/delete_user.php`
- **Función:** Remover usuarios del sistema
- **Características:**
  - Confirmación de eliminación
  - Redirección automática

### 🔐 Sistema de Autenticación

#### Login Seguro
- **Archivo:** `controllers/auth.php`
- **Características:**
  - Verificación de contraseñas hasheadas
  - Sesiones seguras
  - Redirección por roles
  - Protección CSRF básica

#### Roles y Permisos
- **Admin:** Acceso completo a gestión de usuarios
- **Colaborador:** Acceso limitado
- **Vendedor:** Acceso básico

## 🎮 Uso del Sistema

### Acceder al Sistema
1. Abre: `http://localhost/crudunideportes/public/`
2. Ingresa credenciales:
   - Usuario: `admin`
   - Contraseña: `admin123`

### Panel Administrativo
- **Gestión de Personal:** Crear, editar, eliminar usuarios
- **Control de Inventario:** Gestionar productos
- **Reportes:** Ver estadísticas de ventas
- **Base de Clientes:** Administrar información de clientes

### Crear Nuevo Usuario
1. Ve a "Gestionar Personal"
2. Completa el formulario:
   - Nombre y apellido
   - Usuario único
   - Email válido
   - Contraseña segura
   - Rol apropiado
3. Haz clic en "Guardar Usuario"

### Editar Usuario Existente
1. En la tabla de usuarios, haz clic en ✏️
2. Modifica los campos necesarios
3. Haz clic en "ACTUALIZAR DATOS"

### Eliminar Usuario
1. En la tabla de usuarios, haz clic en 🗑️
2. Confirma la eliminación

## 🧪 Testing

### Ejecutar Suite de Pruebas
Ve a: `http://localhost/crudunideportes/test.php`

### Pruebas Incluidas
- ✅ **Configuración del servidor**
- ✅ **Archivos del sistema**
- ✅ **Conexión a base de datos**
- ✅ **Estructura de tablas**
- ✅ **Operaciones CRUD reales**
- ✅ **Sistema de autenticación**

### Pruebas CRUD Automáticas
El test crea, lee, actualiza y elimina un usuario de prueba automáticamente.

## 🌐 API Endpoints

### Autenticación
```
POST /crudunideportes/controllers/auth.php
- username: string
- password: string
```

### Gestión de Usuarios
```
POST /crudunideportes/models/insert_user.php     # Crear
GET  /crudunideportes/views/admin_user.php       # Leer
POST /crudunideportes/controllers/edit_user.php  # Actualizar
GET  /crudunideportes/controllers/delete_user.php?id={id}  # Eliminar
```

### Paneles
```
GET /crudunideportes/views/panel_admin.php       # Admin
GET /crudunideportes/views/panel_vendedor.php    # Vendedor
```

## 🔒 Seguridad

### Medidas Implementadas
- **Contraseñas hasheadas** con `password_hash()`
- **Sentencias preparadas** contra SQL Injection
- **Validación de entrada** de datos
- **Sesiones seguras** con regeneración de ID
- **Protección XSS** básica
- **Control de acceso** por roles

### Mejores Prácticas
- Nunca almacenar contraseñas en texto plano
- Usar HTTPS en producción
- Implementar rate limiting
- Logs de seguridad
- Actualizaciones regulares

## 🤝 Contribución

### Cómo Contribuir
1. **Fork** el proyecto
2. Crea una **branch** para tu feature: `git checkout -b feature/nueva-funcionalidad`
3. **Commit** tus cambios: `git commit -m 'Agrega nueva funcionalidad'`
4. **Push** a la branch: `git push origin feature/nueva-funcionalidad`
5. Abre un **Pull Request**

### Estándares de Código
- Usar **sentencias preparadas** siempre
- **Validar** toda entrada de usuario
- **Comentar** código complejo
- Seguir **convenciones de nombres** PHP
- **Testing** antes de commits

### Reportar Bugs
1. Verifica que el bug no esté reportado
2. Abre un **issue** con:
   - Descripción clara
   - Pasos para reproducir
   - Comportamiento esperado vs actual
   - Información del entorno

---

**Desarrollado con ❤️ para Unideportes**
