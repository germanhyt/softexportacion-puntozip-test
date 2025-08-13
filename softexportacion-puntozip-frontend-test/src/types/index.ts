// ============================================================================
// TIPOS DE DATOS BASADOS EN LA API BACKEND
// ============================================================================

export interface ApiResponse<T> {
    success: boolean;
    data: T;
    message?: string;
    errors?: Record<string, string[]>;
}

export interface Estilo {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    temporada: string;
    año_produccion: number;
    costo_objetivo?: number;
    tiempo_objetivo_min?: number;
    tipo_producto: 'polo' | 'camisa' | 'pantalon' | 'vestido' | 'otro';
    estado: 'desarrollo' | 'activo' | 'descontinuado';
    fecha_creacion: string;
    fecha_actualizacion: string;
}

export interface Material {
    id: number;
    codigo: string;
    nombre: string;
    costo_unitario: number;
    stock_actual: number;
    proveedor?: string;
    tipo_material: 'hilo' | 'tinte' | 'quimico' | 'tinta' | 'avio' | 'empaque';
    es_critico: boolean;
    estado: 'activo' | 'inactivo';
    categoria: {
        id: number;
        nombre: string;
    };
    unidad_medida: {
        id: number;
        codigo: string;
        nombre: string;
    };
}

export interface BomItem {
    id: number;
    id_material: number;
    material: {
        id: number;
        codigo: string;
        nombre: string;
        categoria: { nombre: string };
        unidad_medida: { nombre: string; codigo: string };
        costo_unitario: number;
        tipo_material: 'hilo' | 'tinte' | 'quimico' | 'tinta' | 'avio' | 'empaque';
    };
    cantidad_base: number;
    es_critico: boolean;
    aplica_talla: boolean;
    aplica_color: boolean;
    proceso?: { id: number; nombre: string };
}

export interface Proceso {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    sop?: string;
    costo_base: number;
    tiempo_base_min: number;
    merma_porcentaje: number;
    es_paralelo: boolean;
    es_opcional: boolean;
    requiere_color: boolean;
    estado: 'activo' | 'inactivo';
    tipo_proceso?: {
        id: number;
        nombre: string;
        color_hex: string;
        icono?: string;
    };
}

export interface Color {
    id: number;
    nombre: string;
    codigo_hex?: string;
    codigo_pantone?: string;
    estado: 'activo' | 'inactivo';
}

export interface Talla {
    id: number;
    codigo: string;
    nombre: string;
    multiplicador_cantidad: number;
    orden: number;
    estado: 'activo' | 'inactivo';
}

export interface FlujoEstilo {
    id: number;
    id_estilo: number;
    nombre: string;
    version: number;
    costo_total_calculado: number;
    tiempo_total_calculado: number;
    es_actual: boolean;
    estado: 'activo' | 'inactivo' | 'borrador';
    fecha_creacion: string;
    fecha_actualizacion: string;
}

export interface FlujoNodoProceso {
    id: number;
    id_flujo_estilo: number;
    id_proceso: number;
    orden_secuencia: number;
    pos_x: number;
    pos_y: number;
    ancho: number;
    alto: number;
    costo_personalizado?: number;
    tiempo_personalizado_min?: number;
    es_punto_inicio: boolean;
    es_punto_final: boolean;
    notas?: string;
    estado: 'activo' | 'inactivo';
    proceso?: Proceso;
}

export interface FlujoConexion {
    id: number;
    id_flujo_estilo: number;
    id_nodo_origen: number;
    id_nodo_destino: number;
    tipo_conexion: 'secuencial' | 'condicional' | 'paralelo';
    condicion_activacion?: string;
    etiqueta?: string;
    estilo_linea: 'solida' | 'punteada' | 'discontinua';
    color_linea: string;
    es_animada: boolean;
    orden_prioridad: number;
    estado: 'activo' | 'inactivo';
}

// ============================================================================
// TIPOS PARA REACTFLOW
// ============================================================================

export interface FlowNode {
    id: string;
    type: string;
    position: { x: number; y: number };
    data: {
        id_proceso: number;
        codigo: string;
        nombre: string;
        descripcion: string;
        tipo: string;
        costo_base: number;
        tiempo_base_min: number;
        es_paralelo: boolean;
        es_opcional: boolean;
        requiere_color: boolean;
        es_punto_inicio: boolean;
        es_punto_final: boolean;
        orden_secuencia: number;
        color_tipo: string;
        notas?: string;
        ancho?: number;
        alto?: number;
        inputs: string[];
        outputs: string[];
        tiene_personalizaciones: boolean;
        costo_personalizado?: number;
        tiempo_personalizado_min?: number;
        costo_efectivo: number;
        tiempo_efectivo: number;
        costo_con_merma?: number;
        tiempo_con_merma?: number;
        merma_porcentaje: number;
        pos_x?: number;
        pos_y?: number;
        tipo_proceso?: {
            id: number;
            nombre: string;
            color_hex: string;
            icono?: string;
        };
    };
}

export interface FlowEdge {
    id: string;
    source: string;
    target: string;
    type?: string;
    animated?: boolean;
    style?: React.CSSProperties;
    data?: {
        tipo_conexion: 'secuencial' | 'condicional' | 'paralelo';
        etiqueta?: string;
        condicion_activacion?: string;
        orden_prioridad: number;
    };
    label?: string;
    labelStyle?: React.CSSProperties;
}

// ============================================================================
// TIPOS PARA CÁLCULOS
// ============================================================================

export interface CalculoVariante {
    calculo_id: number;
    estilo: {
        id: number;
        codigo: string;
        nombre: string;
        tipo_producto: string;
    };
    variante: {
        id: number;
        codigo_sku: string;
        color: string;
        talla: string;
        cantidad_piezas: number;
        multiplicador_talla: number;
    };
    flujo: {
        id: number;
        nombre: string;
        version: number;
    };
    costos: {
        materiales: number;
        procesos: number;
        total: number;
        por_pieza: number;
    };
    tiempo: {
        total_minutos: number;
        por_pieza_minutos: number;
    };
    bom: Array<{
        material: {
            id: number;
            codigo: string;
            nombre: string;
            tipo: string;
        };
        cantidad_base: number;
        cantidad_final: number;
        cantidad_total_requerida: number;
        costo_unitario: number;
        costo_total: number;
        aplica_talla: boolean;
        aplica_color: boolean;
        es_critico: boolean;
    }>;
    flujo_procesos: Array<{
        proceso: {
            id: number;
            codigo: string;
            nombre: string;
            tipo: string;
        };
        orden_secuencia: number;
        costo_base: number;
        costo_personalizado?: number;
        costo_efectivo: number;
        costo_total: number;
        tiempo_base_min: number;
        tiempo_personalizado_min?: number;
        tiempo_efectivo_min: number;
        merma_porcentaje: number;
        es_paralelo: boolean;
        es_opcional: boolean;
    }>;
    estadisticas: {
        total_materiales: number;
        materiales_criticos: number;
        total_procesos: number;
        procesos_paralelos: number;
        procesos_opcionales: number;
        porcentaje_costo_materiales: number;
        porcentaje_costo_procesos: number;
    };
    fecha_calculo: string;
}

// ============================================================================
// TIPOS PARA FILTROS Y PARÁMETROS
// ============================================================================

export interface EstiloFiltros {
    // temporada?: string;
    // año_produccion?: number;
    // tipo_producto?: string;
    // estado?: string;
    // buscar?: string;
    // orden_por?: string;
    // direccion?: 'asc' | 'desc';
    // per_page?: number;
}

export interface MaterialFiltros {
    categoria_id?: number;
    tipo_material?: string;
    estado?: string;
    es_critico?: string;
    stock_estado?: string;
    proveedor?: string;
    buscar?: string;
    orden_por?: string;
    direccion?: 'asc' | 'desc';
    per_page?: number;
}

export interface ProcesoFiltros {
    id_tipo_proceso?: number | null;
    estado?: string | null;
    es_paralelo?: string | null;
    es_opcional?: string | null;
    requiere_color?: string | null;
    buscar?: string | null;
    orden_por?: string | null;
    direccion?: 'asc' | 'desc' | null;
    per_page?: number | null;
}

// ============================================================================
// TIPOS PARA RESPUESTAS PAGINADAS
// ============================================================================

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

// ============================================================================
// TIPOS PARA DASHBOARDS Y RESÚMENES
// ============================================================================

export interface ResumenEstilos {
    totales: {
        total: number;
        activos: number;
        en_desarrollo: number;
        descontinuados: number;
    };
    distribucion: {
        por_tipo: Record<string, number>;
        por_temporada: Record<string, number>;
    };
    tendencias: {
        ultimos_30_dias: Array<{
            fecha: string;
            total: number;
        }>;
    };
    porcentajes: {
        activos: number;
        desarrollo: number;
        descontinuados: number;
    };
}

export interface ResumenMateriales {
    totales: {
        total: number;
        activos: number;
        criticos: number;
        sin_stock: number;
        stock_bajo: number;
    };
    distribucion: {
        por_tipo: Record<string, number>;
    };
    inventario: {
        valor_total: number;
        promedio_por_material: number;
    };
    proveedores: {
        top_5: Array<{
            proveedor: string;
            total_materiales: number;
        }>;
    };
    alertas: {
        materiales_criticos: number;
        sin_stock: number;
        stock_bajo: number;
    };
}

export interface ResumenProcesos {
    totales: {
        total: number;
        activos: number;
        paralelos: number;
        opcionales: number;
        requieren_color: number;
    };
    distribucion: {
        por_tipo: Record<string, number>;
    };
    promedios: {
        costo_base: number;
        tiempo_base_min: number;
    };
    estadisticas: {
        procesos_mas_usados: Array<{
            id: number;
            codigo: string;
            nombre: string;
        }>;
        porcentaje_paralelos: number;
        porcentaje_opcionales: number;
    };
}
