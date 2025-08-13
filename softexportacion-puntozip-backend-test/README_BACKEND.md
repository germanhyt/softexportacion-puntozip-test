# Backend - Sistema Textil

## Descripción

Backend desarrollado en Laravel para el sistema de gestión textil que incluye:
- Gestión de estilos y variantes
- Gestión de materiales y BOM (Bill of Materials)
- Gestión de flujos de procesos
- Cálculos de costos y tiempos por variante
- APIs para integración con frontend React

## Características Principales

### 🎯 Gestión de Estilos
- Creación y gestión de estilos de prendas
- Tipos de producto: polo, camisa, pantalón, vestido, otro
- Temporadas y años de producción
- Objetivos de costo y tiempo

### 📦 Gestión de Materiales
- Materiales por tipo: hilo, tinte, químico, tinta, avío, empaque
- Materiales críticos y no críticos
- Asociación de colores a materiales
- Cálculo de costos por material

### 🔄 Flujos de Procesos
- Editor visual de flujos (compatible con @xyflow/react)
- Procesos secuenciales y paralelos
- Procesos opcionales y obligatorios
- Procesos que requieren color específico

### 🧮 Cálculos Textiles
- Cálculo de BOM con lógica textil específica
- Multiplicadores por talla
- Aplicación de colores a materiales
- Cálculo de costos y tiempos por variante

## Requisitos del Sistema

- PHP 8.1 o superior
- Composer
- MySQL 8.0 o superior
- Laravel 10.x

## Instalación

### 1. Clonar el repositorio
```bash
cd softexportacion-puntozip-backend-test
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
```

Editar `.env` con la configuración de tu base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=textil_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 4. Generar clave de aplicación
```bash
php artisan key:generate
```

### 5. Ejecutar migraciones
```bash
php artisan migrate
```

### 6. Importar datos iniciales
```bash
mysql -u tu_usuario -p textil_db < database/database.sql
```

### 7. Configurar Sanctum (opcional)
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Estructura del Proyecto

```
app/
├── Http/Controllers/Api/
│   ├── EstiloController.php          # Gestión de estilos
│   ├── MaterialController.php        # Gestión de materiales
│   ├── ProcesoController.php         # Gestión de procesos
│   ├── FlujoController.php           # Gestión de flujos
│   ├── BomEstiloController.php       # Gestión de BOM
│   ├── CalculoVarianteController.php # Cálculos de variantes
│   ├── TallaController.php           # Gestión de tallas
│   ├── ColorController.php           # Gestión de colores
│   └── CategoriaMaterialController.php # Categorías de materiales
├── Models/
│   ├── Estilo.php                    # Modelo de estilos
│   ├── Material.php                  # Modelo de materiales
│   ├── Proceso.php                   # Modelo de procesos
│   ├── BomEstilo.php                 # Modelo de BOM
│   ├── Talla.php                     # Modelo de tallas
│   └── ...                           # Otros modelos
└── Services/                         # Servicios de negocio
```

## Configuración de Base de Datos

### Tablas Principales

1. **estilos**: Estilos de prendas
   - `tipo_producto`: ENUM (polo, camisa, pantalon, vestido, otro)

2. **materiales**: Materiales utilizados
   - `tipo_material`: ENUM (hilo, tinte, quimico, tinta, avio, empaque)
   - `es_critico`: BOOLEAN

3. **procesos**: Procesos de producción
   - `es_opcional`: BOOLEAN
   - `requiere_color`: BOOLEAN

4. **bom_estilos**: Bill of Materials
   - `aplica_talla`: BOOLEAN
   - `aplica_color`: BOOLEAN

5. **tallas**: Tallas disponibles
   - `multiplicador`: DECIMAL (multiplicador de cantidad)

## APIs Principales

### Gestión de Estilos
- `GET /api/v1/estilos` - Listar estilos
- `POST /api/v1/estilos` - Crear estilo
- `GET /api/v1/estilos/{id}` - Obtener estilo
- `PUT /api/v1/estilos/{id}` - Actualizar estilo
- `DELETE /api/v1/estilos/{id}` - Eliminar estilo

### Cálculos de Variantes
- `POST /api/v1/estilos/{id}/calcular-variante-textil` - Calcular variante completa
- `GET /api/v1/estilos/{estilo_id}/variantes/{color_id}/{talla_id}/historial` - Historial de cálculos

### Gestión de Materiales
- `GET /api/v1/materiales` - Listar materiales
- `POST /api/v1/materiales` - Crear material
- `GET /api/v1/materiales/tipos-material` - Tipos de material disponibles

### Flujos de Procesos
- `GET /api/v1/estilos/{estilo_id}/flujos` - Listar flujos por estilo
- `POST /api/v1/estilos/{estilo_id}/flujos` - Guardar flujo
- `PATCH /api/v1/flujos/{flujo_id}/posiciones` - Actualizar posiciones

## Lógica Textil Implementada

### Cálculo de Materiales
```php
// Materiales que aplican talla
if ($bomItem->aplica_talla) {
    $cantidadBase *= $multiplicadorTalla;
}

// Materiales que aplican color
if ($bomItem->aplica_color) {
    $tieneColor = $material->colores->contains('id', $color->id);
    if (!$tieneColor) {
        $incluirMaterial = false;
    }
}
```

### Cálculo de Procesos
```php
// Procesos opcionales
if ($proceso->es_opcional && !$incluirOpcionales) {
    continue;
}

// Procesos que requieren color
if ($proceso->requiere_color) {
    if ($color->nombre === 'Natural' || $color->nombre === 'Blanco') {
        continue; // No aplicar teñido para colores naturales
    }
}
```

### Multiplicadores de Talla
```php
$tallaMultipliers = [
    'XS' => 0.90,
    'S' => 0.95,
    'M' => 1.00,
    'L' => 1.05,
    'XL' => 1.10,
    'XXL' => 1.15
];
```

## Ejecutar el Servidor

### Desarrollo
```bash
php artisan serve
```

El servidor estará disponible en: `http://localhost:8000`

### Producción
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Testing

### Ejecutar tests
```bash
php artisan test
```

### Tests específicos
```bash
php artisan test --filter=EstiloController
php artisan test --filter=CalculoVarianteController
```

## Integración con Frontend

### CORS
El backend está configurado para permitir CORS desde el frontend React:

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### Formato de Respuesta
Todas las APIs devuelven respuestas en formato JSON:

```json
{
    "success": true,
    "message": "Operación exitosa",
    "data": {
        // Datos específicos
    }
}
```

## Documentación Completa

Para la documentación completa de todas las APIs, consultar:
- `API_DOCUMENTATION.md` - Documentación detallada de endpoints
- `database/database.sql` - Estructura y datos de la base de datos

## Troubleshooting

### Error de conexión a base de datos
1. Verificar configuración en `.env`
2. Verificar que MySQL esté ejecutándose
3. Verificar permisos de usuario

### Error de migraciones
```bash
php artisan migrate:fresh
php artisan db:seed
```

### Error de permisos
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## Contribución

1. Crear rama para nueva funcionalidad
2. Implementar cambios
3. Ejecutar tests
4. Crear pull request

## Licencia

Este proyecto es parte del sistema textil desarrollado para gestión de producción.
