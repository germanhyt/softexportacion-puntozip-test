-- =============================================
-- MODELO DE DATOS SISTEMA TEXTIL ESTILOS v2
-- Base de datos para gestión de procesos textiles
-- Optimizado para @xyflow/react con flujos separados
-- =============================================

DROP DATABASE IF EXISTS textil_estilos;
CREATE DATABASE textil_estilos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE textil_estilos;

-- =============================================
-- TABLAS MAESTRAS
-- =============================================

-- Tabla de categorías de materiales
CREATE TABLE categorias_materiales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de unidades de medida
CREATE TABLE unidades_medida (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    tipo ENUM('peso', 'longitud', 'volumen', 'area', 'unidad') NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de colores
CREATE TABLE colores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo_hex VARCHAR(7),
    codigo_pantone VARCHAR(20),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de tallas
CREATE TABLE tallas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    multiplicador_cantidad DECIMAL(5,3) DEFAULT 1.000,
    orden TINYINT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- MATERIALES Y RECURSOS
-- =============================================

-- Tabla de materiales (solo campos esenciales)
CREATE TABLE materiales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    id_categoria INT NOT NULL,
    id_unidad_medida INT NOT NULL,
    costo_unitario DECIMAL(10,4) NOT NULL,
    stock_actual DECIMAL(12,4) DEFAULT 0,
    proveedor VARCHAR(200),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_categoria) REFERENCES categorias_materiales(id),
    FOREIGN KEY (id_unidad_medida) REFERENCES unidades_medida(id),
    INDEX idx_material_codigo (codigo),
    INDEX idx_material_categoria (id_categoria)
);

-- Tabla de relación material-color (solo para tintes)
CREATE TABLE materiales_colores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_material INT NOT NULL,
    id_color INT NOT NULL,
    costo_adicional DECIMAL(8,4) DEFAULT 0,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_material) REFERENCES materiales(id),
    FOREIGN KEY (id_color) REFERENCES colores(id),
    UNIQUE KEY uk_material_color (id_material, id_color)
);

-- =============================================
-- ESTILOS Y PRODUCTOS
-- =============================================

-- Tabla de estilos (productos base)
CREATE TABLE estilos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    temporada VARCHAR(50),
    año_produccion YEAR,
    costo_objetivo DECIMAL(10,4),
    tiempo_objetivo_min DECIMAL(10,2),
    estado ENUM('desarrollo', 'activo', 'descontinuado') DEFAULT 'desarrollo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_estilo_codigo (codigo),
    INDEX idx_estilo_estado (estado)
);

-- Tabla de variantes de estilos (combinaciones color-talla)
CREATE TABLE variantes_estilos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_estilo INT NOT NULL,
    id_color INT NOT NULL,
    id_talla INT NOT NULL,
    codigo_sku VARCHAR(100) NOT NULL UNIQUE,
    costo_calculado DECIMAL(10,4),
    tiempo_calculado_min DECIMAL(10,2),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_estilo) REFERENCES estilos(id),
    FOREIGN KEY (id_color) REFERENCES colores(id),
    FOREIGN KEY (id_talla) REFERENCES tallas(id),
    UNIQUE KEY uk_estilo_color_talla (id_estilo, id_color, id_talla),
    INDEX idx_variante_sku (codigo_sku)
);

-- =============================================
-- PROCESOS PRODUCTIVOS
-- =============================================

-- Tabla de tipos de procesos
CREATE TABLE tipos_procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    color_hex VARCHAR(7) DEFAULT '#E5E7EB',
    icono VARCHAR(50),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de procesos (plantillas reutilizables)
CREATE TABLE procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    sop TEXT, -- Standard Operating Procedure
    id_tipo_proceso INT NOT NULL,
    costo_base DECIMAL(10,4) NOT NULL,
    tiempo_base_min DECIMAL(10,2) NOT NULL,
    merma_porcentaje DECIMAL(5,2) DEFAULT 0,
    es_paralelo BOOLEAN DEFAULT FALSE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_tipo_proceso) REFERENCES tipos_procesos(id),
    INDEX idx_proceso_codigo (codigo),
    INDEX idx_proceso_tipo (id_tipo_proceso)
);

-- =============================================
-- FLUJOS DE ESTILOS (SEPARADO)
-- =============================================

-- Tabla principal de flujos por estilo
CREATE TABLE flujos_estilos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_estilo INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    version TINYINT DEFAULT 1,
    costo_total_calculado DECIMAL(10,4) DEFAULT 0,
    tiempo_total_calculado DECIMAL(10,2) DEFAULT 0,
    es_actual BOOLEAN DEFAULT TRUE,
    estado ENUM('activo', 'inactivo', 'borrador') DEFAULT 'borrador',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_estilo) REFERENCES estilos(id),
    INDEX idx_flujo_estilo (id_estilo),
    INDEX idx_flujo_actual (id_estilo, es_actual)
);

-- Tabla de nodos de procesos dentro del flujo (para @xyflow/react)
CREATE TABLE flujos_nodos_procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_flujo_estilo INT NOT NULL,
    id_proceso INT NOT NULL,
    orden_secuencia TINYINT NOT NULL, -- Orden lógico de ejecución
    pos_x DECIMAL(8,2) NOT NULL DEFAULT 0, -- Posición X para @xyflow/react
    pos_y DECIMAL(8,2) NOT NULL DEFAULT 0, -- Posición Y para @xyflow/react
    ancho DECIMAL(8,2) DEFAULT 200,
    alto DECIMAL(8,2) DEFAULT 80,
    costo_personalizado DECIMAL(10,4), -- Override del costo base
    tiempo_personalizado_min DECIMAL(10,2), -- Override del tiempo base
    es_opcional BOOLEAN DEFAULT FALSE,
    es_punto_inicio BOOLEAN DEFAULT FALSE,
    es_punto_final BOOLEAN DEFAULT FALSE,
    notas TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_flujo_estilo) REFERENCES flujos_estilos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_proceso) REFERENCES procesos(id),
    UNIQUE KEY uk_flujo_proceso (id_flujo_estilo, id_proceso),
    INDEX idx_nodo_flujo (id_flujo_estilo),
    INDEX idx_nodo_orden (id_flujo_estilo, orden_secuencia)
);

-- Tabla de conexiones entre nodos (edges para @xyflow/react)
CREATE TABLE flujos_conexiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_flujo_estilo INT NOT NULL,
    id_nodo_origen INT NOT NULL, -- Referencia a flujos_nodos_procesos
    id_nodo_destino INT NOT NULL, -- Referencia a flujos_nodos_procesos
    tipo_conexion ENUM('secuencial', 'condicional', 'paralelo') DEFAULT 'secuencial',
    condicion_activacion VARCHAR(500),
    etiqueta VARCHAR(100),
    estilo_linea ENUM('solida', 'punteada', 'discontinua') DEFAULT 'solida',
    color_linea VARCHAR(7) DEFAULT '#64748B',
    es_animada BOOLEAN DEFAULT FALSE,
    orden_prioridad TINYINT DEFAULT 1, -- Para múltiples salidas del mismo nodo
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_flujo_estilo) REFERENCES flujos_estilos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nodo_origen) REFERENCES flujos_nodos_procesos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nodo_destino) REFERENCES flujos_nodos_procesos(id) ON DELETE CASCADE,
    INDEX idx_conexion_flujo (id_flujo_estilo),
    INDEX idx_conexion_origen (id_nodo_origen),
    INDEX idx_conexion_destino (id_nodo_destino)
);

-- =============================================
-- BILL OF MATERIALS (BOM) - SIMPLIFICADO
-- =============================================

-- Tabla de BOM por estilo (solo datos esenciales)
CREATE TABLE bom_estilos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_estilo INT NOT NULL,
    id_material INT NOT NULL,
    cantidad_base DECIMAL(12,6) NOT NULL, -- Cantidad para talla M
    id_proceso INT, -- Proceso donde se consume (opcional)
    es_critico BOOLEAN DEFAULT FALSE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_estilo) REFERENCES estilos(id),
    FOREIGN KEY (id_material) REFERENCES materiales(id),
    FOREIGN KEY (id_proceso) REFERENCES procesos(id),
    UNIQUE KEY uk_bom_estilo_material (id_estilo, id_material),
    INDEX idx_bom_estilo (id_estilo)
);

-- =============================================
-- INPUTS Y OUTPUTS SIMPLIFICADOS
-- =============================================

-- Tabla de inputs por proceso (solo esenciales)
CREATE TABLE procesos_inputs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_proceso INT NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    tipo_input ENUM('material', 'semifinal', 'otro') NOT NULL,
    id_material INT, -- Si es un material específico
    id_proceso_origen INT, -- Si proviene de otro proceso
    es_obligatorio BOOLEAN DEFAULT TRUE,
    orden TINYINT NOT NULL DEFAULT 1,
    
    FOREIGN KEY (id_proceso) REFERENCES procesos(id),
    FOREIGN KEY (id_material) REFERENCES materiales(id),
    FOREIGN KEY (id_proceso_origen) REFERENCES procesos(id),
    INDEX idx_input_proceso (id_proceso)
);

-- Tabla de outputs por proceso (solo esenciales)
CREATE TABLE procesos_outputs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_proceso INT NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    tipo_output ENUM('semifinal', 'final', 'subproducto', 'desperdicio') NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    orden TINYINT NOT NULL DEFAULT 1,
    
    FOREIGN KEY (id_proceso) REFERENCES procesos(id),
    INDEX idx_output_proceso (id_proceso)
);

-- =============================================
-- CÁLCULOS Y RESULTADOS - OPTIMIZADO
-- =============================================

-- Tabla de cálculos por variante (solo datos finales)
CREATE TABLE calculos_variantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_variante_estilo INT NOT NULL,
    id_flujo_estilo INT NOT NULL,
    costo_materiales DECIMAL(10,4) NOT NULL DEFAULT 0,
    costo_procesos DECIMAL(10,4) NOT NULL DEFAULT 0,
    costo_total DECIMAL(10,4) NOT NULL DEFAULT 0,
    tiempo_total_min DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    version INT DEFAULT 1,
    es_actual BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (id_variante_estilo) REFERENCES variantes_estilos(id),
    FOREIGN KEY (id_flujo_estilo) REFERENCES flujos_estilos(id),
    INDEX idx_calculo_variante (id_variante_estilo),
    INDEX idx_calculo_flujo (id_flujo_estilo),
    INDEX idx_calculo_actual (id_variante_estilo, es_actual)
);

-- =============================================
-- ÍNDICES ADICIONALES PARA RENDIMIENTO
-- =============================================

-- Índices para consultas frecuentes
CREATE INDEX idx_estilos_activos ON estilos(estado, fecha_creacion);
CREATE INDEX idx_variantes_activas ON variantes_estilos(estado, id_estilo);
CREATE INDEX idx_materiales_activos ON materiales(estado, id_categoria);
CREATE INDEX idx_flujos_actuales ON flujos_estilos(id_estilo, es_actual);
CREATE INDEX idx_nodos_posiciones ON flujos_nodos_procesos(id_flujo_estilo, pos_x, pos_y);

-- =============================================
-- VISTAS OPTIMIZADAS
-- =============================================

-- Vista de resumen de estilos
CREATE VIEW v_resumen_estilos AS
SELECT 
    e.id,
    e.codigo,
    e.nombre,
    e.estado,
    COUNT(DISTINCT ve.id) as total_variantes,
    COUNT(DISTINCT ve.id_color) as total_colores,
    COUNT(DISTINCT ve.id_talla) as total_tallas,
    AVG(ve.costo_calculado) as costo_promedio,
    AVG(ve.tiempo_calculado_min) as tiempo_promedio
FROM estilos e
LEFT JOIN variantes_estilos ve ON e.id = ve.id_estilo AND ve.estado = 'activo'
WHERE e.estado IN ('desarrollo', 'activo')
GROUP BY e.id, e.codigo, e.nombre, e.estado;

-- Vista de BOM completo por variante (optimizada)
CREATE VIEW v_bom_variantes AS
SELECT 
    ve.id as variante_id,
    ve.codigo_sku,
    e.codigo as codigo_estilo,
    e.nombre as nombre_estilo,
    c.nombre as color,
    t.codigo as talla,
    m.codigo as codigo_material,
    m.nombre as nombre_material,
    cm.nombre as categoria_material,
    um.codigo as unidad_medida,
    (be.cantidad_base * t.multiplicador_cantidad) as cantidad_final,
    m.costo_unitario,
    (be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario) as costo_total_material
FROM variantes_estilos ve
JOIN estilos e ON ve.id_estilo = e.id
JOIN colores c ON ve.id_color = c.id
JOIN tallas t ON ve.id_talla = t.id
JOIN bom_estilos be ON e.id = be.id_estilo
JOIN materiales m ON be.id_material = m.id
JOIN categorias_materiales cm ON m.id_categoria = cm.id
JOIN unidades_medida um ON m.id_unidad_medida = um.id
WHERE ve.estado = 'activo' 
  AND be.estado = 'activo' 
  AND m.estado = 'activo';

-- Vista de flujo de procesos para @xyflow/react (datos listos para frontend)
CREATE VIEW v_flujo_react_nodos AS
SELECT 
    fnp.id,
    CONCAT('node-', fnp.id) as node_id,
    fnp.id_flujo_estilo,
    e.codigo as codigo_estilo,
    e.nombre as nombre_estilo,
    p.codigo as codigo_proceso,
    p.nombre as nombre_proceso,
    tp.nombre as tipo_proceso,
    tp.color_hex,
    fnp.orden_secuencia,
    fnp.pos_x,
    fnp.pos_y,
    fnp.ancho,
    fnp.alto,
    COALESCE(fnp.costo_personalizado, p.costo_base) as costo_proceso,
    COALESCE(fnp.tiempo_personalizado_min, p.tiempo_base_min) as tiempo_proceso,
    p.merma_porcentaje,
    p.es_paralelo,
    fnp.es_opcional,
    fnp.es_punto_inicio,
    fnp.es_punto_final,
    fnp.estado
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
JOIN procesos p ON fnp.id_proceso = p.id
JOIN tipos_procesos tp ON p.id_tipo_proceso = tp.id
WHERE fnp.estado = 'activo' 
  AND fe.estado = 'activo'
  AND p.estado = 'activo';

-- Vista de conexiones para @xyflow/react
CREATE VIEW v_flujo_react_edges AS
SELECT 
    fc.id,
    CONCAT('edge-', fc.id) as edge_id,
    fc.id_flujo_estilo,
    CONCAT('node-', fc.id_nodo_origen) as source,
    CONCAT('node-', fc.id_nodo_destino) as target,
    fc.tipo_conexion,
    fc.etiqueta,
    fc.estilo_linea,
    fc.color_linea,
    fc.es_animada,
    fc.orden_prioridad,
    fc.estado
FROM flujos_conexiones fc
JOIN flujos_estilos fe ON fc.id_flujo_estilo = fe.id
WHERE fc.estado = 'activo' 
  AND fe.estado = 'activo';

-- Vista de secuencia de procesos (para validaciones y cálculos)
CREATE VIEW v_secuencia_procesos AS
SELECT 
    fe.id as flujo_id,
    e.codigo as codigo_estilo,
    fnp.id as nodo_id,
    p.codigo as codigo_proceso,
    p.nombre as nombre_proceso,
    fnp.orden_secuencia,
    fnp.es_punto_inicio,
    fnp.es_punto_final,
    p.es_paralelo,
    COALESCE(fnp.costo_personalizado, p.costo_base) as costo,
    COALESCE(fnp.tiempo_personalizado_min, p.tiempo_base_min) as tiempo,
    -- Siguiente proceso en la secuencia
    (SELECT fnp2.id 
     FROM flujos_nodos_procesos fnp2 
     WHERE fnp2.id_flujo_estilo = fnp.id_flujo_estilo 
       AND fnp2.orden_secuencia = fnp.orden_secuencia + 1
     LIMIT 1) as siguiente_nodo_id
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
JOIN procesos p ON fnp.id_proceso = p.id
WHERE fnp.estado = 'activo' 
  AND fe.estado = 'activo'
ORDER BY fe.id, fnp.orden_secuencia;








-------------------------------------


-- =============================================
-- DATOS DE PRUEBA SISTEMA TEXTIL ESTILOS v3
-- Basado en el ejemplo: Polo "Andes Premium" (P-2025-02)
-- Con nomenclatura actualizada: id_[tabla] en lugar de [tabla]_id
-- =============================================

USE textil_estilos;

-- =============================================
-- DATOS MAESTROS
-- =============================================

-- Insertar categorías de materiales
INSERT INTO categorias_materiales (nombre, descripcion) VALUES
('Hilos', 'Hilos para tejido y confección'),
('Tintes', 'Tintes reactivos y pigmentos'),
('Químicos', 'Productos químicos para procesos'),
('Tintas', 'Tintas de estampado'),
('Avíos', 'Accesorios y complementos'),
('Empaques', 'Materiales de empaque y etiquetado');

-- Insertar unidades de medida
INSERT INTO unidades_medida (codigo, nombre, tipo) VALUES
('KG', 'Kilogramo', 'peso'),
('L', 'Litro', 'volumen'),
('UND', 'Unidad', 'unidad'),
('CONO', 'Cono', 'unidad'),
('M', 'Metro', 'longitud'),
('M2', 'Metro cuadrado', 'area');

-- Insertar colores
INSERT INTO colores (nombre, codigo_hex, codigo_pantone) VALUES
('Azul Marino', '#1E3A8A', '19-4052 TPX'),
('Rojo', '#DC2626', '18-1664 TPX'),
('Blanco', '#FFFFFF', '11-0601 TPX'),
('Negro', '#000000', '19-0303 TPX');

-- Insertar tallas
INSERT INTO tallas (codigo, nombre, multiplicador_cantidad, orden) VALUES
('S', 'Small', 0.950, 1),
('M', 'Medium', 1.000, 2),
('L', 'Large', 1.050, 3),
('XL', 'Extra Large', 1.100, 4);

-- =============================================
-- MATERIALES OPTIMIZADOS
-- =============================================

-- Insertar materiales con nueva nomenclatura: id_categoria, id_unidad_medida
INSERT INTO materiales (codigo, nombre, id_categoria, id_unidad_medida, costo_unitario, stock_actual, proveedor) VALUES
-- Hilos (categoria 1)
('MAT-001', 'Algodón 100% 20/1', 1, 1, 8.5000, 1200.500, 'Textiles del Valle S.A.'),
('MAT-002', 'Hilo Elastano 40D', 1, 1, 25.7500, 150.000, 'Fibras Técnicas Ltda'),
-- Tintes (categoria 2) 
('MAT-003', 'Tinte Reactivo Azul Marino', 2, 1, 45.2000, 50.750, 'Colorantes Andinos'),
('MAT-004', 'Tinte Reactivo Rojo', 2, 1, 42.8000, 35.200, 'Colorantes Andinos'),
('MAT-005', 'Tinte Reactivo Blanco', 2, 1, 38.5000, 80.300, 'Colorantes Andinos'),
-- Químicos (categoria 3)
('MAT-006', 'Suavizante Textil', 3, 2, 12.3000, 200.000, 'Químicos Industriales'),
('MAT-007', 'Detergente Industrial', 3, 1, 18.9000, 120.500, 'Químicos Industriales'),
-- Tintas (categoria 4)
('MAT-008', 'Tinta Plastisol Blanca', 4, 1, 32.7500, 25.800, 'Tintas y Más'),
-- Avíos (categoria 5)
('MAT-009', 'Etiqueta Talla', 5, 3, 0.0850, 10000.000, 'Etiquetas Premium'),
('MAT-010', 'Etiqueta Composición', 5, 3, 0.0650, 8000.000, 'Etiquetas Premium'),
-- Empaques (categoria 6)
('MAT-011', 'Bolsa Polipropileno', 6, 3, 0.1200, 5000.000, 'Empaques del Norte');

-- =============================================
-- RELACIONES MATERIAL-COLOR (SOLO PARA TINTES)
-- =============================================

-- Insertar relaciones material-color con nueva nomenclatura: id_material, id_color
INSERT INTO materiales_colores (id_material, id_color) VALUES
-- Tintes con sus colores correspondientes
(3, 1), -- Tinte Azul Marino con Azul Marino
(4, 2), -- Tinte Rojo con Rojo  
(5, 3); -- Tinte Blanco con Blanco

-- =============================================
-- ESTILOS Y VARIANTES
-- =============================================

-- Insertar estilo principal
INSERT INTO estilos (codigo, nombre, descripcion, temporada, año_produccion, costo_objetivo, tiempo_objetivo_min) VALUES
('P-2025-02', 'Polo Andes Premium', 'Polo premium 100% algodón con diseño exclusivo de los Andes', 'Verano 2025', 2025, 35.5000, 180.00);

-- Insertar variantes del estilo con nueva nomenclatura: id_estilo, id_color, id_talla
INSERT INTO variantes_estilos (id_estilo, id_color, id_talla, codigo_sku) 
SELECT 
    e.id,
    c.id,
    t.id,
    CONCAT(e.codigo, '-', c.nombre, '-', t.codigo)
FROM estilos e
CROSS JOIN colores c 
CROSS JOIN tallas t
WHERE e.codigo = 'P-2025-02'
  AND c.nombre IN ('Azul Marino', 'Rojo', 'Blanco')
  AND t.codigo IN ('S', 'M', 'L', 'XL');

-- =============================================
-- TIPOS Y PROCESOS
-- =============================================

-- Insertar tipos de procesos
INSERT INTO tipos_procesos (nombre, descripcion, color_hex, icono) VALUES
('Tejeduría', 'Procesos de tejido y formación de tela', '#3B82F6', 'fabric'),
('Tintorería', 'Procesos de teñido y coloración', '#EF4444', 'palette'),
('Confección', 'Procesos de corte y costura', '#10B981', 'scissors'),
('Acabados', 'Procesos finales y control de calidad', '#F59E0B', 'check-circle'),
('Empaque', 'Procesos de empaque y etiquetado', '#8B5CF6', 'package');

-- Insertar procesos con nueva nomenclatura: id_tipo_proceso
INSERT INTO procesos (codigo, nombre, descripcion, sop, id_tipo_proceso, costo_base, tiempo_base_min, merma_porcentaje, es_paralelo) VALUES
('PROC-001', 'Tejido Circular', 'Tejido en máquina circular para crear la tela base', 'SOP-001: Configurar tensión hilos, velocidad 180rpm', 1, 8.5000, 45.00, 2.5, FALSE),
('PROC-002', 'Teñido Reactivo', 'Proceso de teñido con colorantes reactivos', 'SOP-002: Temperatura 60°C, pH 11-12, tiempo 120min', 2, 12.3000, 120.00, 3.0, FALSE),
('PROC-003', 'Corte Automático', 'Corte de piezas con máquina automática', 'SOP-003: Marcar moldes, configurar cuchilla, verificar medidas', 3, 3.2000, 15.00, 1.0, FALSE),
('PROC-004', 'Costura Principal', 'Costura de hombros, costados y mangas', 'SOP-004: Puntada overlock 3 hilos, velocidad 3000ppm', 3, 6.8000, 35.00, 0.5, FALSE),
('PROC-005', 'Acabado Cuello', 'Ribeteado y costura del cuello', 'SOP-005: Sesgo 2.5cm, puntada recta doble', 3, 4.2000, 20.00, 1.5, TRUE),
('PROC-006', 'Control de Calidad', 'Inspección visual y de medidas', 'SOP-006: Check list 15 puntos, muestreo 10%', 4, 2.1000, 12.00, 0.0, FALSE),
('PROC-007', 'Empaque Final', 'Doblado, etiquetado y empaque', 'SOP-007: Doblar según estándar, etiquetar, embolar', 5, 1.8000, 8.00, 0.0, FALSE);

-- =============================================
-- FLUJO DE PROCESO PARA EL ESTILO
-- =============================================

-- Crear flujo principal con nueva nomenclatura: id_estilo
INSERT INTO flujos_estilos (id_estilo, nombre, version, es_actual, estado) 
SELECT 
    e.id,
    CONCAT('Flujo Principal - ', e.nombre),
    1,
    TRUE,
    'activo'
FROM estilos e 
WHERE e.codigo = 'P-2025-02';

-- Insertar nodos del flujo con nueva nomenclatura: id_flujo_estilo, id_proceso
INSERT INTO flujos_nodos_procesos (id_flujo_estilo, id_proceso, orden_secuencia, pos_x, pos_y, ancho, alto, es_punto_inicio, es_punto_final) 
SELECT 
    fe.id as id_flujo_estilo,
    p.id as id_proceso,
    CASE p.codigo
        WHEN 'PROC-001' THEN 1
        WHEN 'PROC-002' THEN 2  
        WHEN 'PROC-003' THEN 3
        WHEN 'PROC-004' THEN 4
        WHEN 'PROC-005' THEN 5
        WHEN 'PROC-006' THEN 6
        WHEN 'PROC-007' THEN 7
    END as orden_secuencia,
    CASE p.codigo
        WHEN 'PROC-001' THEN 100.00
        WHEN 'PROC-002' THEN 350.00
        WHEN 'PROC-003' THEN 600.00
        WHEN 'PROC-004' THEN 850.00
        WHEN 'PROC-005' THEN 850.00  -- Paralelo con PROC-004
        WHEN 'PROC-006' THEN 1100.00
        WHEN 'PROC-007' THEN 1350.00
    END as pos_x,
    CASE p.codigo
        WHEN 'PROC-001' THEN 100.00
        WHEN 'PROC-002' THEN 100.00
        WHEN 'PROC-003' THEN 100.00
        WHEN 'PROC-004' THEN 50.00
        WHEN 'PROC-005' THEN 150.00   -- Paralelo debajo
        WHEN 'PROC-006' THEN 100.00
        WHEN 'PROC-007' THEN 100.00
    END as pos_y,
    200.00 as ancho,
    80.00 as alto,
    CASE WHEN p.codigo = 'PROC-001' THEN TRUE ELSE FALSE END as es_punto_inicio,
    CASE WHEN p.codigo = 'PROC-007' THEN TRUE ELSE FALSE END as es_punto_final
FROM flujos_estilos fe
JOIN estilos e ON fe.id_estilo = e.id
CROSS JOIN procesos p
WHERE e.codigo = 'P-2025-02' 
  AND fe.es_actual = TRUE
  AND p.codigo IN ('PROC-001', 'PROC-002', 'PROC-003', 'PROC-004', 'PROC-005', 'PROC-006', 'PROC-007');

-- Crear conexiones entre nodos con nueva nomenclatura: id_flujo_estilo, id_nodo_origen, id_nodo_destino
INSERT INTO flujos_conexiones (id_flujo_estilo, id_nodo_origen, id_nodo_destino, tipo_conexion, etiqueta, es_animada)
SELECT 
    fe.id as id_flujo_estilo,
    nodo_origen.id as id_nodo_origen,
    nodo_destino.id as id_nodo_destino,
    'secuencial' as tipo_conexion,
    '' as etiqueta,
    FALSE as es_animada
FROM flujos_estilos fe
JOIN estilos e ON fe.id_estilo = e.id
JOIN flujos_nodos_procesos nodo_origen ON fe.id = nodo_origen.id_flujo_estilo
JOIN procesos p_origen ON nodo_origen.id_proceso = p_origen.id
JOIN flujos_nodos_procesos nodo_destino ON fe.id = nodo_destino.id_flujo_estilo
JOIN procesos p_destino ON nodo_destino.id_proceso = p_destino.id
WHERE e.codigo = 'P-2025-02' 
  AND fe.es_actual = TRUE
  AND (
    -- Flujo secuencial principal
    (p_origen.codigo = 'PROC-001' AND p_destino.codigo = 'PROC-002') OR
    (p_origen.codigo = 'PROC-002' AND p_destino.codigo = 'PROC-003') OR
    (p_origen.codigo = 'PROC-003' AND p_destino.codigo = 'PROC-004') OR
    (p_origen.codigo = 'PROC-003' AND p_destino.codigo = 'PROC-005') OR -- Paralelo
    (p_origen.codigo = 'PROC-004' AND p_destino.codigo = 'PROC-006') OR
    (p_origen.codigo = 'PROC-005' AND p_destino.codigo = 'PROC-006') OR -- Convergencia
    (p_origen.codigo = 'PROC-006' AND p_destino.codigo = 'PROC-007')
  );

-- =============================================
-- BILL OF MATERIALS (BOM)
-- =============================================

-- Insertar BOM del estilo con nueva nomenclatura: id_estilo, id_material, id_proceso
INSERT INTO bom_estilos (id_estilo, id_material, cantidad_base, id_proceso, es_critico) 
SELECT 
    e.id as id_estilo,
    m.id as id_material,
    CASE m.codigo
        WHEN 'MAT-001' THEN 0.420000  -- 420g algodón para talla M
        WHEN 'MAT-002' THEN 0.045000  -- 45g elastano
        WHEN 'MAT-003' THEN 0.025000  -- 25g tinte (solo para azul marino)
        WHEN 'MAT-004' THEN 0.025000  -- 25g tinte (solo para rojo)
        WHEN 'MAT-005' THEN 0.025000  -- 25g tinte (solo para blanco)
        WHEN 'MAT-006' THEN 0.015000  -- 15g suavizante
        WHEN 'MAT-007' THEN 0.008000  -- 8g detergente
        WHEN 'MAT-008' THEN 0.012000  -- 12g tinta (si hay estampado)
        WHEN 'MAT-009' THEN 1.000000  -- 1 etiqueta talla
        WHEN 'MAT-010' THEN 1.000000  -- 1 etiqueta composición
        WHEN 'MAT-011' THEN 1.000000  -- 1 bolsa empaque
    END as cantidad_base,
    CASE m.codigo
        WHEN 'MAT-001' THEN p1.id  -- Algodón en tejido
        WHEN 'MAT-002' THEN p1.id  -- Elastano en tejido
        WHEN 'MAT-003' THEN p2.id  -- Tinte azul en teñido
        WHEN 'MAT-004' THEN p2.id  -- Tinte rojo en teñido
        WHEN 'MAT-005' THEN p2.id  -- Tinte blanco en teñido
        WHEN 'MAT-006' THEN p2.id  -- Suavizante en teñido
        WHEN 'MAT-007' THEN p2.id  -- Detergente en teñido
        WHEN 'MAT-008' THEN NULL   -- Tinta (proceso futuro)
        WHEN 'MAT-009' THEN p7.id  -- Etiqueta talla en empaque
        WHEN 'MAT-010' THEN p7.id  -- Etiqueta composición en empaque
        WHEN 'MAT-011' THEN p7.id  -- Bolsa en empaque
    END as id_proceso,
    CASE m.codigo
        WHEN 'MAT-001' THEN TRUE   -- Algodón es crítico
        WHEN 'MAT-002' THEN TRUE   -- Elastano es crítico
        ELSE FALSE
    END as es_critico
FROM estilos e
CROSS JOIN materiales m
LEFT JOIN procesos p1 ON p1.codigo = 'PROC-001'  -- Tejido
LEFT JOIN procesos p2 ON p2.codigo = 'PROC-002'  -- Teñido  
LEFT JOIN procesos p7 ON p7.codigo = 'PROC-007'  -- Empaque
WHERE e.codigo = 'P-2025-02'
  AND m.codigo IN (
    'MAT-001', 'MAT-002', 'MAT-003', 'MAT-004', 'MAT-005',
    'MAT-006', 'MAT-007', 'MAT-009', 'MAT-010', 'MAT-011'
  );

-- =============================================
-- CÁLCULOS INICIALES POR VARIANTE
-- =============================================

-- Calcular costos por variante con nueva nomenclatura: id_variante_estilo, id_flujo_estilo
INSERT INTO calculos_variantes (id_variante_estilo, id_flujo_estilo, costo_materiales, costo_procesos, costo_total, tiempo_total_min, es_actual)
SELECT 
    ve.id as id_variante_estilo,
    fe.id as id_flujo_estilo,
    -- Costo de materiales (ajustado por talla)
    (
        SELECT ROUND(SUM(
            CASE 
                WHEN m.codigo LIKE 'MAT-001%' OR m.codigo LIKE 'MAT-002%' THEN 
                    be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario
                ELSE 
                    be.cantidad_base * m.costo_unitario
            END
        ), 4)
        FROM bom_estilos be
        JOIN materiales m ON be.id_material = m.id
        WHERE be.id_estilo = ve.id_estilo AND be.estado = 'activo'
    ) as costo_materiales,
    
    -- Costo de procesos (suma de todos los procesos)
    (
        SELECT ROUND(SUM(COALESCE(fnp.costo_personalizado, p.costo_base)), 4)
        FROM flujos_nodos_procesos fnp
        JOIN procesos p ON fnp.id_proceso = p.id
        WHERE fnp.id_flujo_estilo = fe.id AND fnp.estado = 'activo'
    ) as costo_procesos,
    
    -- Costo total
    (
        SELECT ROUND(SUM(
            CASE 
                WHEN m.codigo LIKE 'MAT-001%' OR m.codigo LIKE 'MAT-002%' THEN 
                    be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario
                ELSE 
                    be.cantidad_base * m.costo_unitario
            END
        ), 4)
        FROM bom_estilos be
        JOIN materiales m ON be.id_material = m.id
        WHERE be.id_estilo = ve.id_estilo AND be.estado = 'activo'
    ) + (
        SELECT ROUND(SUM(COALESCE(fnp.costo_personalizado, p.costo_base)), 4)
        FROM flujos_nodos_procesos fnp
        JOIN procesos p ON fnp.id_proceso = p.id
        WHERE fnp.id_flujo_estilo = fe.id AND fnp.estado = 'activo'
    ) as costo_total,
    
    -- Tiempo total (suma de todos los procesos)
    (
        SELECT ROUND(SUM(COALESCE(fnp.tiempo_personalizado_min, p.tiempo_base_min)), 2)
        FROM flujos_nodos_procesos fnp
        JOIN procesos p ON fnp.id_proceso = p.id
        WHERE fnp.id_flujo_estilo = fe.id AND fnp.estado = 'activo'
    ) as tiempo_total_min,
    
    TRUE as es_actual
    
FROM variantes_estilos ve
JOIN tallas t ON ve.id_talla = t.id
JOIN flujos_estilos fe ON ve.id_estilo = fe.id_estilo AND fe.es_actual = TRUE
WHERE ve.estado = 'activo';

-- =============================================
-- VERIFICACIONES FINALES
-- =============================================

-- Mostrar resumen de datos insertados
SELECT 'RESUMEN DE DATOS INSERTADOS' as seccion;

SELECT 'Categorías Materiales' as tabla, COUNT(*) as registros FROM categorias_materiales
UNION ALL SELECT 'Unidades Medida', COUNT(*) FROM unidades_medida
UNION ALL SELECT 'Colores', COUNT(*) FROM colores  
UNION ALL SELECT 'Tallas', COUNT(*) FROM tallas
UNION ALL SELECT 'Materiales', COUNT(*) FROM materiales
UNION ALL SELECT 'Materiales-Colores', COUNT(*) FROM materiales_colores
UNION ALL SELECT 'Estilos', COUNT(*) FROM estilos
UNION ALL SELECT 'Variantes Estilos', COUNT(*) FROM variantes_estilos
UNION ALL SELECT 'Tipos Procesos', COUNT(*) FROM tipos_procesos
UNION ALL SELECT 'Procesos', COUNT(*) FROM procesos
UNION ALL SELECT 'Flujos Estilos', COUNT(*) FROM flujos_estilos
UNION ALL SELECT 'Nodos Procesos', COUNT(*) FROM flujos_nodos_procesos
UNION ALL SELECT 'Conexiones', COUNT(*) FROM flujos_conexiones
UNION ALL SELECT 'BOM Estilos', COUNT(*) FROM bom_estilos
UNION ALL SELECT 'Cálculos Variantes', COUNT(*) FROM calculos_variantes;

-- Verificar integridad de flujo
SELECT 'VERIFICACIÓN FLUJO' as seccion;
SELECT 
    'Nodos sin conexiones entrantes' as tipo,
    COUNT(*) as cantidad
FROM flujos_nodos_procesos fnp
LEFT JOIN flujos_conexiones fc ON fnp.id = fc.id_nodo_destino
WHERE fc.id IS NULL AND fnp.es_punto_inicio = FALSE

UNION ALL

SELECT 
    'Nodos sin conexiones salientes',
    COUNT(*)
FROM flujos_nodos_procesos fnp  
LEFT JOIN flujos_conexiones fc ON fnp.id = fc.id_nodo_origen
WHERE fc.id IS NULL AND fnp.es_punto_final = FALSE;



-------------------------------


-- =============================================
-- CONSULTAS PARA TESTING Y DESARROLLO v3
-- Sistema Textil Estilos - Con nomenclatura id_[tabla]
-- =============================================

-- =============================================
-- 1. CONSULTAS DE VERIFICACIÓN BÁSICA
-- =============================================

-- Contar registros en todas las tablas principales
SELECT 'Estilos' as tabla, COUNT(*) as registros FROM estilos
UNION ALL SELECT 'Variantes', COUNT(*) FROM variantes_estilos
UNION ALL SELECT 'Materiales', COUNT(*) FROM materiales
UNION ALL SELECT 'Procesos', COUNT(*) FROM procesos
UNION ALL SELECT 'Flujos Estilos', COUNT(*) FROM flujos_estilos
UNION ALL SELECT 'Nodos Procesos', COUNT(*) FROM flujos_nodos_procesos
UNION ALL SELECT 'Conexiones', COUNT(*) FROM flujos_conexiones
UNION ALL SELECT 'BOM Estilos', COUNT(*) FROM bom_estilos
UNION ALL SELECT 'Cálculos Variantes', COUNT(*) FROM calculos_variantes;

-- =============================================
-- 2. DATOS PARA @xyflow/react (NODOS) - v3
-- =============================================

-- Obtener nodos del flujo para React Flow (formato JSON listo)
SELECT 
    node_id as id,
    'default' as type,
    JSON_OBJECT(
        'x', pos_x,
        'y', pos_y
    ) as position,
    JSON_OBJECT(
        'label', nombre_proceso,
        'codigo', codigo_proceso,
        'costo', costo_proceso,
        'tiempo', tiempo_proceso,
        'tipo', tipo_proceso,
        'color', color_hex,
        'merma', merma_porcentaje,
        'orden', orden_secuencia,
        'es_inicio', es_punto_inicio,
        'es_final', es_punto_final,
        'es_opcional', es_opcional
    ) as data,
    JSON_OBJECT(
        'width', ancho,
        'height', alto,
        'backgroundColor', color_hex
    ) as style
FROM v_flujo_react_nodos
WHERE codigo_estilo = 'P-2025-02'
ORDER BY orden_secuencia;

-- =============================================
-- 3. DATOS PARA @xyflow/react (EDGES/CONEXIONES) - v3
-- =============================================

-- Obtener conexiones del flujo para React Flow
SELECT 
    edge_id as id,
    source,
    target,
    'default' as type,
    JSON_OBJECT(
        'label', etiqueta,
        'tipo', tipo_conexion,
        'animated', es_animada
    ) as data,
    JSON_OBJECT(
        'stroke', color_linea,
        'strokeWidth', 2,
        'strokeDasharray', CASE estilo_linea 
            WHEN 'punteada' THEN '5,5'
            WHEN 'discontinua' THEN '10,5'
            ELSE null
        END
    ) as style
FROM v_flujo_react_edges
WHERE id_flujo_estilo = (
    SELECT id FROM flujos_estilos 
    WHERE id_estilo = (SELECT id FROM estilos WHERE codigo = 'P-2025-02') 
    AND es_actual = TRUE
);

-- =============================================
-- 4. ANÁLISIS DE SECUENCIA DE PROCESOS
-- =============================================

-- Ver secuencia lógica de procesos con validaciones
SELECT 
    orden_secuencia,
    codigo_proceso,
    nombre_proceso,
    es_punto_inicio,
    es_punto_final,
    costo,
    tiempo,
    -- Validar si tiene proceso anterior
    CASE 
        WHEN orden_secuencia = 1 THEN 'INICIO'
        WHEN LAG(orden_secuencia) OVER (ORDER BY orden_secuencia) = orden_secuencia - 1 THEN 'OK'
        ELSE 'GAP EN SECUENCIA'
    END as validacion_anterior,
    -- Validar si tiene proceso siguiente  
    CASE 
        WHEN es_punto_final = TRUE THEN 'FINAL'
        WHEN LEAD(orden_secuencia) OVER (ORDER BY orden_secuencia) = orden_secuencia + 1 THEN 'OK'
        ELSE 'GAP EN SECUENCIA'
    END as validacion_siguiente
FROM v_secuencia_procesos
WHERE codigo_estilo = 'P-2025-02'
ORDER BY orden_secuencia;

-- =============================================
-- 5. CÁLCULO DE COSTOS DETALLADO POR VARIANTE
-- =============================================

-- Obtener desglose completo de costos de una variante específica
SELECT 
    ve.codigo_sku,
    c.nombre as color,
    t.codigo as talla,
    t.multiplicador_cantidad,
    
    -- Desglose de materiales con cálculo por talla
    'MATERIALES' as seccion,
    m.codigo as item_codigo,
    m.nombre as item_nombre,
    cm.nombre as categoria,
    um.codigo as unidad,
    be.cantidad_base as cantidad_base,
    CASE 
        WHEN m.codigo LIKE 'MAT-001%' THEN 
            ROUND(be.cantidad_base * t.multiplicador_cantidad, 6)
        ELSE 
            be.cantidad_base 
    END as cantidad_ajustada,
    m.costo_unitario,
    CASE 
        WHEN m.codigo LIKE 'MAT-001%' THEN 
            ROUND(be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario, 4)
        ELSE 
            ROUND(be.cantidad_base * m.costo_unitario, 4)
    END as costo_total_item,
    p.codigo as proceso_consumo
    
FROM variantes_estilos ve
JOIN estilos e ON ve.id_estilo = e.id
JOIN colores c ON ve.id_color = c.id
JOIN tallas t ON ve.id_talla = t.id
JOIN bom_estilos be ON e.id = be.id_estilo
JOIN materiales m ON be.id_material = m.id
JOIN categorias_materiales cm ON m.id_categoria = cm.id
JOIN unidades_medida um ON m.id_unidad_medida = um.id
LEFT JOIN procesos p ON be.id_proceso = p.id
WHERE ve.codigo_sku = 'P-2025-02-Azul Marino-M'
  AND be.estado = 'activo'

UNION ALL

-- Desglose de procesos
SELECT 
    ve.codigo_sku,
    c.nombre as color,
    t.codigo as talla,
    t.multiplicador_cantidad,
    'PROCESOS' as seccion,
    p.codigo as item_codigo,
    p.nombre as item_nombre,
    tp.nombre as categoria,
    'min' as unidad,
    COALESCE(fnp.tiempo_personalizado_min, p.tiempo_base_min) as cantidad_base,
    COALESCE(fnp.tiempo_personalizado_min, p.tiempo_base_min) as cantidad_ajustada,
    1.0000 as costo_unitario,
    COALESCE(fnp.costo_personalizado, p.costo_base) as costo_total_item,
    p.codigo as proceso_consumo
    
FROM variantes_estilos ve
JOIN estilos e ON ve.id_estilo = e.id
JOIN colores c ON ve.id_color = c.id
JOIN tallas t ON ve.id_talla = t.id
JOIN flujos_estilos fe ON e.id = fe.id_estilo AND fe.es_actual = TRUE
JOIN flujos_nodos_procesos fnp ON fe.id = fnp.id_flujo_estilo AND fnp.estado = 'activo'
JOIN procesos p ON fnp.id_proceso = p.id
JOIN tipos_procesos tp ON p.id_tipo_proceso = tp.id
WHERE ve.codigo_sku = 'P-2025-02-Azul Marino-M'
ORDER BY seccion, categoria, item_codigo;

-- =============================================
-- 6. ANÁLISIS DE MATERIALES CRÍTICOS
-- =============================================

-- Materiales críticos por estilo
SELECT 
    e.codigo as codigo_estilo,
    e.nombre as nombre_estilo,
    m.codigo as codigo_material,
    m.nombre as nombre_material,
    cm.nombre as categoria,
    be.cantidad_base,
    m.costo_unitario,
    be.cantidad_base * m.costo_unitario as costo_material_base,
    m.stock_actual,
    -- Calcular stock necesario para 100 unidades talla M
    be.cantidad_base * 100 as stock_necesario_100_unidades,
    CASE 
        WHEN m.stock_actual >= (be.cantidad_base * 100) THEN 'SUFICIENTE'
        WHEN m.stock_actual >= (be.cantidad_base * 50) THEN 'BAJO'
        ELSE 'CRÍTICO'
    END as estado_stock,
    be.es_critico
FROM estilos e
JOIN bom_estilos be ON e.id = be.id_estilo
JOIN materiales m ON be.id_material = m.id
JOIN categorias_materiales cm ON m.id_categoria = cm.id
WHERE e.codigo = 'P-2025-02' 
  AND be.estado = 'activo'
  AND be.es_critico = TRUE
ORDER BY costo_material_base DESC;

-- =============================================
-- 7. COMPARATIVO DE VARIANTES (OPTIMIZADO)
-- =============================================

-- Comparar todas las variantes del estilo
SELECT 
    c.nombre as color,
    t.codigo as talla,
    t.orden,
    ve.codigo_sku,
    cv.costo_materiales,
    cv.costo_procesos, 
    cv.costo_total,
    cv.tiempo_total_min,
    -- Comparar con la variante base (Azul Marino - M)
    cv.costo_total - base.costo_total as diferencia_vs_base,
    ROUND(((cv.costo_total - base.costo_total) / base.costo_total) * 100, 2) as porcentaje_diferencia,
    -- Ranking de costos
    RANK() OVER (ORDER BY cv.costo_total ASC) as ranking_costo_asc,
    RANK() OVER (ORDER BY cv.costo_total DESC) as ranking_costo_desc
FROM variantes_estilos ve
JOIN colores c ON ve.id_color = c.id
JOIN tallas t ON ve.id_talla = t.id
JOIN calculos_variantes cv ON ve.id = cv.id_variante_estilo AND cv.es_actual = TRUE
-- Subconsulta para obtener costo base (Azul Marino - M)
CROSS JOIN (
    SELECT cv2.costo_total
    FROM variantes_estilos ve2
    JOIN colores c2 ON ve2.id_color = c2.id
    JOIN tallas t2 ON ve2.id_talla = t2.id
    JOIN calculos_variantes cv2 ON ve2.id = cv2.id_variante_estilo AND cv2.es_actual = TRUE
    WHERE c2.nombre = 'Azul Marino' AND t2.codigo = 'M'
      AND ve2.id_estilo = (SELECT id FROM estilos WHERE codigo = 'P-2025-02')
) base
WHERE ve.estado = 'activo'
  AND ve.id_estilo = (SELECT id FROM estilos WHERE codigo = 'P-2025-02')
ORDER BY c.nombre, t.orden;

-- =============================================
-- 8. ANÁLISIS DE FLUJO Y CONEXIONES
-- =============================================

-- Verificar integridad del flujo
SELECT 
    'Total Nodos' as metrica,
    COUNT(*) as valor
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
WHERE e.codigo = 'P-2025-02' AND fe.es_actual = TRUE

UNION ALL

SELECT 
    'Total Conexiones',
    COUNT(*)
FROM flujos_conexiones fc
JOIN flujos_estilos fe ON fc.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
WHERE e.codigo = 'P-2025-02' AND fe.es_actual = TRUE

UNION ALL

SELECT 
    'Puntos de Inicio',
    COUNT(*)
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
WHERE e.codigo = 'P-2025-02' AND fe.es_actual = TRUE AND fnp.es_punto_inicio = TRUE

UNION ALL

SELECT 
    'Puntos de Final',
    COUNT(*)
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
WHERE e.codigo = 'P-2025-02' AND fe.es_actual = TRUE AND fnp.es_punto_final = TRUE;

-- Detectar nodos huérfanos (sin conexiones)
SELECT 
    'NODOS SIN CONEXIONES ENTRANTES' as tipo,
    fnp.id as nodo_id,
    p.codigo,
    p.nombre
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
JOIN procesos p ON fnp.id_proceso = p.id
LEFT JOIN flujos_conexiones fc ON fnp.id = fc.id_nodo_destino
WHERE e.codigo = 'P-2025-02' 
  AND fe.es_actual = TRUE
  AND fc.id IS NULL
  AND fnp.es_punto_inicio = FALSE

UNION ALL

SELECT 
    'NODOS SIN CONEXIONES SALIENTES',
    fnp.id,
    p.codigo,
    p.nombre
FROM flujos_nodos_procesos fnp
JOIN flujos_estilos fe ON fnp.id_flujo_estilo = fe.id
JOIN estilos e ON fe.id_estilo = e.id
JOIN procesos p ON fnp.id_proceso = p.id
LEFT JOIN flujos_conexiones fc ON fnp.id = fc.id_nodo_origen
WHERE e.codigo = 'P-2025-02' 
  AND fe.es_actual = TRUE
  AND fc.id IS NULL
  AND fnp.es_punto_final = FALSE;

-- =============================================
-- 9. SIMULACIONES DE COSTOS
-- =============================================

-- Simular impacto de cambio de precios en materiales críticos
SELECT 
    'Escenario Actual' as escenario,
    ve.codigo_sku,
    cv.costo_materiales,
    cv.costo_procesos,
    cv.costo_total
FROM variantes_estilos ve
JOIN calculos_variantes cv ON ve.id = cv.id_variante_estilo AND cv.es_actual = TRUE
WHERE ve.codigo_sku LIKE 'P-2025-02-Azul Marino%'

UNION ALL

SELECT 
    'Escenario +15% Mat. Críticos',
    ve.codigo_sku,
    -- Recalcular materiales con incremento en críticos
    ROUND((
        SELECT SUM(
            CASE 
                WHEN be.es_critico = TRUE THEN 
                    be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario * 1.15
                ELSE 
                    be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario
            END
        )
        FROM bom_estilos be
        JOIN materiales m ON be.id_material = m.id
        WHERE be.id_estilo = ve.id_estilo AND be.estado = 'activo'
    ), 4) as costo_materiales_simulado,
    cv.costo_procesos,
    ROUND((
        SELECT SUM(
            CASE 
                WHEN be.es_critico = TRUE THEN 
                    be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario * 1.15
                ELSE 
                    be.cantidad_base * t.multiplicador_cantidad * m.costo_unitario
            END
        )
        FROM bom_estilos be
        JOIN materiales m ON be.id_material = m.id
        WHERE be.id_estilo = ve.id_estilo AND be.estado = 'activo'
    ), 4) + cv.costo_procesos as costo_total_simulado
FROM variantes_estilos ve
JOIN tallas t ON ve.id_talla = t.id
JOIN calculos_variantes cv ON ve.id = cv.id_variante_estilo AND cv.es_actual = TRUE
WHERE ve.codigo_sku LIKE 'P-2025-02-Azul Marino%'
ORDER BY escenario, codigo_sku;

-- =============================================
-- 10. REPORTES EJECUTIVOS
-- =============================================

-- Resumen ejecutivo del estilo
SELECT 
    e.codigo,
    e.nombre,
    e.estado,
    COUNT(DISTINCT ve.id) as total_variantes,
    COUNT(DISTINCT ve.id_color) as total_colores,
    COUNT(DISTINCT ve.id_talla) as total_tallas,
    ROUND(AVG(cv.costo_total), 4) as costo_promedio,
    ROUND(MIN(cv.costo_total), 4) as costo_minimo,
    ROUND(MAX(cv.costo_total), 4) as costo_maximo,
    ROUND(AVG(cv.tiempo_total_min), 2) as tiempo_promedio,
    -- Calcular variabilidad de costos
    ROUND(STDDEV(cv.costo_total), 4) as desviacion_estandar_costo,
    ROUND((STDDEV(cv.costo_total) / AVG(cv.costo_total)) * 100, 2) as coeficiente_variacion_pct
FROM estilos e
JOIN variantes_estilos ve ON e.id = ve.id_estilo AND ve.estado = 'activo'
JOIN calculos_variantes cv ON ve.id = cv.id_variante_estilo AND cv.es_actual = TRUE
WHERE e.codigo = 'P-2025-02'
GROUP BY e.id, e.codigo, e.nombre, e.estado;














