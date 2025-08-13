# API Documentation - Sistema Textil

## Base URL
```
http://localhost:8000/api/v1
```

## Autenticación
Todas las APIs requieren autenticación mediante Laravel Sanctum. Incluir el token en el header:
```
Authorization: Bearer {token}
```

## Endpoints Principales

### 1. Estilos

#### Listar Estilos
```http
GET /estilos
```

**Parámetros de consulta:**
- `temporada`: Filtrar por temporada (primavera, verano, otoño, invierno)
- `año_produccion`: Filtrar por año de producción
- `activos_solo`: Solo estilos activos
- `en_desarrollo`: Solo estilos en desarrollo
- `buscar`: Búsqueda por nombre
- `tipo_producto`: Filtrar por tipo (polo, camisa, pantalon, vestido, otro)

#### Crear Estilo
```http
POST /estilos
```

**Body:**
```json
{
    "codigo": "EST-001",
    "nombre": "Polo Básico",
    "descripcion": "Polo básico de algodón",
    "temporada": "verano",
    "año_produccion": 2024,
    "tipo_producto": "polo",
    "costo_objetivo": 15.50,
    "tiempo_objetivo_min": 45.0,
    "estado": "activo"
}
```

#### Obtener Estilo
```http
GET /estilos/{id}
```

#### Actualizar Estilo
```http
PUT /estilos/{id}
```

#### Eliminar Estilo
```http
DELETE /estilos/{id}
```

#### Calcular Costos
```http
GET /estilos/{id}/costos
```

#### Calcular Variante
```http
POST /estilos/{id}/calcular-variante
```

**Body:**
```json
{
    "color": "Azul",
    "talla": "M",
    "cantidad_piezas": 100
}
```

#### Obtener Tipos de Producto
```http
GET /estilos/tipos-producto
```

### 2. Materiales

#### Listar Materiales
```http
GET /materiales
```

**Parámetros de consulta:**
- `tipo_material`: Filtrar por tipo (hilo, tinte, quimico, tinta, avio, empaque)
- `es_critico`: Filtrar materiales críticos
- `buscar`: Búsqueda por nombre o código

#### Crear Material
```http
POST /materiales
```

**Body:**
```json
{
    "codigo": "MAT-001",
    "nombre": "Hilo Algodón 30/1",
    "descripcion": "Hilo de algodón 30/1 para tejido",
    "id_categoria": 1,
    "id_unidad_medida": 1,
    "costo_unitario": 2.50,
    "stock_actual": 1000.0,
    "tipo_material": "hilo",
    "es_critico": true,
    "proveedor": "Proveedor ABC",
    "estado": "activo"
}
```

#### Obtener Tipos de Material
```http
GET /materiales/tipos-material
```

#### Asociar Colores a Material
```http
POST /materiales/{id}/colores
```

**Body:**
```json
{
    "colores": [
        {
            "id_color": 1,
            "costo_adicional": 0.50
        },
        {
            "id_color": 2,
            "costo_adicional": 0.75
        }
    ]
}
```

### 3. Procesos

#### Listar Procesos
```http
GET /procesos
```

#### Crear Proceso
```http
POST /procesos
```

**Body:**
```json
{
    "codigo": "PROC-01",
    "nombre": "Tejido Jersey",
    "descripcion": "Proceso de tejido jersey",
    "id_tipo_proceso": 1,
    "costo_base": 5.00,
    "tiempo_base_min": 30.0,
    "merma_porcentaje": 2.0,
    "es_paralelo": false,
    "es_opcional": false,
    "requiere_color": false,
    "sop": "Procedimiento detallado..."
}
```

#### Obtener SOP
```http
GET /procesos/{id}/sop
```

### 4. Flujos de Procesos

#### Listar Flujos por Estilo
```http
GET /estilos/{estilo_id}/flujos
```

#### Obtener Flujo Completo
```http
GET /estilos/{estilo_id}/flujos/{flujo_id}
```

#### Guardar Flujo
```http
POST /estilos/{estilo_id}/flujos
```

**Body:**
```json
{
    "nombre": "Flujo Principal",
    "descripcion": "Flujo principal de producción",
    "nodos": [
        {
            "id_proceso": 1,
            "pos_x": 100,
            "pos_y": 100,
            "orden_secuencia": 1,
            "es_punto_inicio": true,
            "es_punto_final": false
        }
    ],
    "conexiones": [
        {
            "nodo_origen": 1,
            "nodo_destino": 2,
            "tipo_conexion": "secuencial"
        }
    ]
}
```

#### Actualizar Posiciones
```http
PATCH /flujos/{flujo_id}/posiciones
```

#### Calcular Tiempo Total
```http
GET /flujos/{flujo_id}/calcular-tiempo
```

### 5. BOM (Bill of Materials)

#### Obtener BOM por Estilo
```http
GET /estilos/{id}/bom
```

#### Actualizar BOM
```http
POST /estilos/{id}/bom
```

**Body:**
```json
{
    "materiales": [
        {
            "id_material": 1,
            "cantidad_base": 0.5,
            "id_proceso": 1,
            "aplica_talla": true,
            "aplica_color": false,
            "es_critico": true
        }
    ]
}
```

### 6. Cálculos de Variantes (Nuevo)

#### Calcular Variante Textil
```http
POST /estilos/{id}/calcular-variante-textil
```

**Body:**
```json
{
    "color_id": 1,
    "talla_id": 3,
    "cantidad_piezas": 100,
    "incluir_procesos_opcionales": true
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "calculo_id": 1,
        "estilo": {
            "id": 1,
            "codigo": "EST-001",
            "nombre": "Polo Básico",
            "tipo_producto": "polo"
        },
        "variante": {
            "color": "Azul",
            "talla": "M",
            "cantidad_piezas": 100,
            "multiplicador_talla": 1.0
        },
        "costos": {
            "materiales": 1250.50,
            "procesos": 500.00,
            "total": 1750.50,
            "por_pieza": 17.51
        },
        "tiempo": {
            "total_minutos": 3000.0,
            "por_pieza_minutos": 30.0
        },
        "bom": [...],
        "flujo_procesos": [...]
    }
}
```

#### Obtener Historial de Cálculos
```http
GET /estilos/{estilo_id}/variantes/{color_id}/{talla_id}/historial
```

### 7. Tallas

#### Listar Tallas
```http
GET /tallas
```

#### Crear Talla
```http
POST /tallas
```

**Body:**
```json
{
    "codigo": "M",
    "nombre": "Mediana",
    "descripcion": "Talla mediana",
    "multiplicador": 1.0,
    "estado": "activo"
}
```

#### Obtener Tallas Disponibles
```http
GET /tallas/disponibles
```

### 8. Colores

#### Listar Colores
```http
GET /colores
```

#### Crear Color
```http
POST /colores
```

**Body:**
```json
{
    "codigo": "AZL",
    "nombre": "Azul",
    "descripcion": "Color azul",
    "color_hex": "#0000FF",
    "estado": "activo"
}
```

### 9. Utilidades

#### Obtener Temporadas
```http
GET /utils/temporadas
```

#### Obtener Estados
```http
GET /utils/estados
```

#### Obtener Años de Producción
```http
GET /utils/años-produccion
```

#### Obtener Tipos de Proceso
```http
GET /utils/tipos-proceso
```

### 10. Reportes y Dashboard

#### Resumen del Dashboard
```http
GET /dashboard/resumen
```

#### Reporte de Costos por Estilo
```http
GET /reportes/costos-por-estilo
```

### 11. Búsqueda Global

#### Búsqueda en Múltiples Entidades
```http
GET /buscar?q=texto
```

## Códigos de Respuesta

- `200`: Éxito
- `201`: Creado exitosamente
- `400`: Error de validación
- `401`: No autorizado
- `404`: No encontrado
- `422`: Error de validación de datos
- `500`: Error interno del servidor

## Estructura de Respuesta

Todas las respuestas siguen este formato:

```json
{
    "success": true,
    "message": "Mensaje descriptivo",
    "data": {
        // Datos específicos del endpoint
    }
}
```

En caso de error:

```json
{
    "success": false,
    "message": "Descripción del error",
    "error": "Detalles técnicos del error",
    "errors": {
        // Errores de validación específicos
    }
}
```

## Lógica Textil Específica

### Cálculo de Materiales
- Los materiales con `aplica_talla: true` se multiplican por el multiplicador de la talla
- Los materiales con `aplica_color: true` solo se incluyen si el material tiene el color seleccionado
- Los materiales críticos (`es_critico: true`) se destacan en los reportes

### Cálculo de Procesos
- Los procesos opcionales (`es_opcional: true`) solo se incluyen si se especifica
- Los procesos que requieren color (`requiere_color: true`) se omiten para colores naturales/blancos
- Los procesos paralelos se calculan simultáneamente

### Multiplicadores de Talla
- XS: 0.90
- S: 0.95
- M: 1.00
- L: 1.05
- XL: 1.10
- XXL: 1.15

## Integración con Frontend

Las APIs están diseñadas para integrarse con React y @xyflow/react:

- Los flujos de procesos devuelven datos en formato compatible con @xyflow/react
- Las posiciones de nodos se pueden actualizar en tiempo real
- Los cálculos se realizan considerando la lógica textil específica
- Los datos se estructuran para facilitar la visualización en dashboards
