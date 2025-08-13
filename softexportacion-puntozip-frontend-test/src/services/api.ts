import axios, { type AxiosResponse, type AxiosError } from 'axios';
import type {
    ApiResponse,
    Estilo,
    Material,
    Proceso,
    Color,
    Talla,
    BomItem,
    FlujoEstilo,
    FlowNode,
    FlowEdge,
    CalculoVariante,
    EstiloFiltros,
    MaterialFiltros,
    ProcesoFiltros
} from '../types';

// ============================================================================
// CONFIGURACIÓN DE AXIOS
// ============================================================================

const API_BASE_URL = 'http://localhost:8002/api/v1';

const apiClient = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 10000, // 10 segundos
});

// Interceptor para manejo de errores global
apiClient.interceptors.response.use(
    (response: AxiosResponse) => response,
    (error: AxiosError) => {
        console.error('API Error:', error);

        if (error.response?.status === 404) {
            console.error('Endpoint no encontrado:', error.config?.url);
        } else if (error.response?.status === 500) {
            console.error('Error interno del servidor');
        } else if (error.code === 'ECONNABORTED') {
            console.error('Timeout de la petición');
        }

        return Promise.reject(error);
    }
);

// ============================================================================
// SERVICIO API PRINCIPAL
// ============================================================================

class ApiService {
    // ========================================================================
    // MÉTODOS AUXILIARES
    // ========================================================================

    private static async request<T>(
        method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH',
        endpoint: string,
        data?: any,
        params?: any
    ): Promise<ApiResponse<T>> {
        try {
            const response = await apiClient.request<ApiResponse<T>>({
                method,
                url: endpoint,
                data,
                params,
            });

            return response.data;
        } catch (error) {
            console.error(`Error en ${method} ${endpoint}:`, error);
            throw error;
        }
    }

    // ========================================================================
    // ESTILOS
    // ========================================================================

    static async getEstilos(filtros?: EstiloFiltros): Promise<Estilo[]> {
        try {
            const response = await this.request<any>('GET', '/estilos', null, filtros);
            console.log("response getEstilos", response);
            
            // Manejar estructura paginada del backend (similar a procesos)
            if (response.success && response.data?.data && Array.isArray(response.data.data)) {
                return response.data.data;
            } else if (response.success && Array.isArray(response.data)) {
                return response.data;
            } else if (Array.isArray(response.data)) {
                return response.data;
            } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                return (response.data as any).data || [];
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo estilos:', error);
            return [];
        }
    }

    static async getEstilo(id: number): Promise<Estilo> {
        const response = await this.request<Estilo>('GET', `/estilos/${id}`);
        return response.data;
    }

    static async createEstilo(data: Partial<Estilo>): Promise<Estilo> {
        const response = await this.request<Estilo>('POST', '/estilos', data);
        return response.data;
    }

    static async updateEstilo(id: number, data: Partial<Estilo>): Promise<Estilo> {
        const response = await this.request<Estilo>('PUT', `/estilos/${id}`, data);
        return response.data;
    }

    static async deleteEstilo(id: number): Promise<void> {
        await this.request<void>('DELETE', `/estilos/${id}`);
    }

    static async duplicarEstilo(id: number): Promise<Estilo> {
        const response = await this.request<Estilo>('POST', `/estilos/${id}/duplicar`);
        return response.data;
    }

    static async getResumenEstilos(): Promise<any> {
        const response = await this.request<any>('GET', '/estilos/resumen');
        return response.data;
    }

    static async getTiposProducto(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/estilos/tipos-producto');
        return response.data;
    }

    static async getTemporadas(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/estilos/temporadas');
        return response.data;
    }

    static async getEstadosEstilo(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/estilos/estados');
        return response.data;
    }

    // ========================================================================
    // MATERIALES
    // ========================================================================

    static async getMateriales(filtros?: MaterialFiltros): Promise<Material[]> {
        try {
            const response = await this.request<any>('GET', '/materiales', null, filtros);
            console.log("response getMateriales", response);
            
            // Manejar estructura paginada del backend
            if (response.success && response.data?.data && Array.isArray(response.data.data)) {
                return response.data.data;
            } else if (response.success && Array.isArray(response.data)) {
                return response.data;
            } else if (Array.isArray(response.data)) {
                return response.data;
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo materiales:', error);
            return [];
        }
    }

    static async getMaterial(id: number): Promise<Material> {
        const response = await this.request<Material>('GET', `/materiales/${id}`);
        return response.data;
    }

    static async createMaterial(data: Partial<Material>): Promise<Material> {
        const response = await this.request<Material>('POST', '/materiales', data);
        return response.data;
    }

    static async updateMaterial(id: number, data: Partial<Material>): Promise<Material> {
        const response = await this.request<Material>('PUT', `/materiales/${id}`, data);
        return response.data;
    }

    static async deleteMaterial(id: number): Promise<void> {
        await this.request<void>('DELETE', `/materiales/${id}`);
    }

    static async getTiposMaterial(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/materiales/tipos-material');
        return response.data;
    }

    static async getMaterialesCriticos(): Promise<Material[]> {
        const response = await this.request<Material[]>('GET', '/materiales/criticos');
        return response.data;
    }

    // ========================================================================
    // PROCESOS
    // ========================================================================

    static async getProcesos(filtros?: ProcesoFiltros): Promise<Proceso[]> {
        try {
            const response = await this.request<any>('GET', '/procesos', null, filtros);
            console.log("response getProcesos", response);

            // La API devuelve: { success: true, data: { data: Proceso[], current_page: 1, ... } }
            if (response.success && response.data?.data && Array.isArray(response.data.data)) {
                // Mapear los datos del backend al formato del frontend
                return response.data.data.map((proceso: any): Proceso => ({
                    id: proceso.id,
                    codigo: proceso.codigo,
                    nombre: proceso.nombre,
                    descripcion: proceso.descripcion,
                    sop: proceso.sop,
                    costo_base: parseFloat(proceso.costo_base || 0),
                    tiempo_base_min: parseFloat(proceso.tiempo_base_min || 0),
                    merma_porcentaje: parseFloat(proceso.merma_porcentaje || 0),
                    es_paralelo: !!proceso.es_paralelo,
                    es_opcional: !!proceso.es_opcional,
                    requiere_color: !!proceso.requiere_color,
                    estado: proceso.estado || 'activo',
                    tipo_proceso: proceso.tipo_proceso ? {
                        id: proceso.tipo_proceso.id,
                        nombre: proceso.tipo_proceso.nombre,
                        color_hex: proceso.tipo_proceso.color_hex,
                        icono: proceso.tipo_proceso.icono
                    } : undefined
                }));
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo procesos:', error);
            return [];
        }
    }

    static async getProceso(id: number): Promise<Proceso> {
        const response = await this.request<Proceso>('GET', `/procesos/${id}`);
        return response.data;
    }

    static async createProceso(data: Partial<Proceso>): Promise<Proceso> {
        const response = await this.request<Proceso>('POST', '/procesos', data);
        return response.data;
    }

    static async updateProceso(id: number, data: Partial<Proceso>): Promise<Proceso> {
        const response = await this.request<Proceso>('PUT', `/procesos/${id}`, data);
        return response.data;
    }

    static async deleteProceso(id: number): Promise<void> {
        await this.request<void>('DELETE', `/procesos/${id}`);
    }

    static async getTiposProceso(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/procesos/tipos-proceso');
        return response.data;
    }

    static async getProcesosParaReactFlow(): Promise<any[]> {
        const response = await this.request<any[]>('GET', '/procesos/para-reactflow');
        return response.data;
    }

    static async getSOPProceso(id: number): Promise<any> {
        const response = await this.request<any>('GET', `/procesos/${id}/sop`);
        return response.data;
    }

    // ========================================================================
    // COLORES
    // ========================================================================

    static async getColores(): Promise<Color[]> {
        try {
            const response = await this.request<Color[]>('GET', '/colores');
            
            // Manejar el formato de respuesta anidado que puede tener el backend
            if (response.success && response.data) {
                if (Array.isArray(response.data)) {
                    return response.data;
                } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                    return (response.data as any).data || [];
                }
            } else if (Array.isArray(response.data)) {
                return response.data;
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo colores:', error);
            return [];
        }
    }

    static async getColor(id: number): Promise<Color> {
        const response = await this.request<Color>('GET', `/colores/${id}`);
        return response.data;
    }

    static async createColor(data: Partial<Color>): Promise<Color> {
        const response = await this.request<Color>('POST', '/colores', data);
        return response.data;
    }

    static async updateColor(id: number, data: Partial<Color>): Promise<Color> {
        const response = await this.request<Color>('PUT', `/colores/${id}`, data);
        return response.data;
    }

    static async deleteColor(id: number): Promise<void> {
        await this.request<void>('DELETE', `/colores/${id}`);
    }

    // ========================================================================
    // TALLAS
    // ========================================================================

    static async getTallas(): Promise<Talla[]> {
        try {
            const response = await this.request<any>('GET', '/tallas');
            console.log("response getTallas", response);
            
            // Manejar estructura paginada del backend
            if (response.success && response.data?.data && Array.isArray(response.data.data)) {
                return response.data.data;
            } else if (response.success && Array.isArray(response.data)) {
                return response.data;
            } else if (Array.isArray(response.data)) {
                return response.data;
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo tallas:', error);
            return [];
        }
    }

    static async getTallasDisponibles(): Promise<Talla[]> {
        try {
            const response = await this.request<any>('GET', '/tallas/disponibles');
            console.log("response getTallasDisponibles", response);
            
            // El backend devuelve: { "1": "Small", "2": "Medium", "3": "Large", "4": "Extra Large" }
            if (response.success && response.data && typeof response.data === 'object') {
                // Transformar el objeto a array de Talla
                return Object.entries(response.data).map(([id, nombre]) => ({
                    id: parseInt(id),
                    codigo: nombre as string,
                    nombre: nombre as string,
                    multiplicador_cantidad: 1.0,
                    orden: parseInt(id), // Usar el ID como orden
                    estado: 'activo'
                }));
            }
            
            // Fallback para otros formatos
            if (response.success && response.data?.data && Array.isArray(response.data.data)) {
                return response.data.data;
            } else if (response.success && Array.isArray(response.data)) {
                return response.data;
            } else if (Array.isArray(response.data)) {
                return response.data;
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo tallas disponibles:', error);
            return [];
        }
    }

    static async getTalla(id: number): Promise<Talla> {
        const response = await this.request<Talla>('GET', `/tallas/${id}`);
        return response.data;
    }

    static async createTalla(data: Partial<Talla>): Promise<Talla> {
        const response = await this.request<Talla>('POST', '/tallas', data);
        return response.data;
    }

    static async updateTalla(id: number, data: Partial<Talla>): Promise<Talla> {
        const response = await this.request<Talla>('PUT', `/tallas/${id}`, data);
        return response.data;
    }

    static async deleteTalla(id: number): Promise<void> {
        await this.request<void>('DELETE', `/tallas/${id}`);
    }

    // ========================================================================
    // BOM (BILL OF MATERIALS)
    // ========================================================================

    static async getBOMEstilo(id: number): Promise<{
        bom_items: BomItem[];
        resumen: {
            total_items: number;
            costo_total_materiales: number;
            items_criticos: number;
        };
    }> {
        try {
            const response = await this.request<any>('GET', `/estilos/${id}/bom`);
            console.log("response getBOMEstilo", response);
            
            // El backend devuelve directamente: { bom_items: [...], resumen: {...} }
            // A través del formato: { success: true, data: { bom_items: [...], resumen: {...} } }
            let bomData = response;
            if (response.success && response.data) {
                bomData = response.data;
            } else if (response.data && response.data.bom_items) {
                bomData = response.data;
            }

            if (bomData && (bomData as any).bom_items) {
                // Mapear los items del BOM del formato del backend al frontend
                const mappedBomItems: BomItem[] = (bomData as any).bom_items.map((item: any) => ({
                    id: item.bom_item?.id || item.id,
                    id_material: item.material?.id || 0,
                    material: {
                        id: item.material?.id || 0,
                        codigo: item.material?.codigo || '',
                        nombre: item.material?.nombre || '',
                        categoria: { 
                            nombre: item.material?.categoria || 'Sin categoría' 
                        },
                        unidad_medida: { 
                            nombre: item.material?.unidad_medida || 'UND',
                            codigo: item.material?.unidad_medida || 'UND'
                        },
                        costo_unitario: parseFloat(item.material?.costo_unitario || 0),
                        tipo_material: item.material?.tipo || 'avio'
                    },
                    cantidad_base: parseFloat(item.bom_item?.cantidad_base || item.cantidad_base || 0),
                    es_critico: !!item.bom_item?.es_critico,
                    aplica_talla: !!item.bom_item?.aplica_talla,
                    aplica_color: !!item.bom_item?.aplica_color,
                    proceso: item.proceso ? {
                        id: item.proceso.id,
                        nombre: item.proceso.nombre
                    } : undefined
                }));

                return {
                    bom_items: mappedBomItems,
                    resumen: {
                        total_items: (bomData as any).resumen?.total_items || mappedBomItems.length,
                        costo_total_materiales: (bomData as any).resumen?.costo_total_materiales || 0,
                        items_criticos: (bomData as any).resumen?.items_criticos || mappedBomItems.filter(item => item.es_critico).length
                    }
                };
            }
            
            return {
                bom_items: [],
                resumen: {
                    total_items: 0,
                    costo_total_materiales: 0,
                    items_criticos: 0
                }
            };
        } catch (error) {
            console.error('Error obteniendo BOM del estilo:', error);
            return {
                bom_items: [],
                resumen: {
                    total_items: 0,
                    costo_total_materiales: 0,
                    items_criticos: 0
                }
            };
        }
    }

    static async actualizarBOM(estiloId: number, bomItems: Partial<BomItem>[]): Promise<any> {
        const response = await this.request<any>('POST', `/estilos/${estiloId}/bom`, {
            bom_items: bomItems
        });
        return response.data;
    }

    static async calcularBOMPorVariante(estiloId: number, data: {
        color_id: number;
        talla_id: number;
        cantidad: number;
    }): Promise<any> {
        const response = await this.request<any>('POST', `/estilos/${estiloId}/bom/calcular-variante`, data);
        return response.data;
    }

    // ========================================================================
    // FLUJOS
    // ========================================================================

    static async getFlujosByEstilo(estiloId: number): Promise<FlujoEstilo[]> {
        try {
            const response = await this.request<any>('GET', `/estilos/${estiloId}/flujos`);
            console.log("response getFlujosByEstilo", response);
            
            // El backend devuelve: { success: true, data: { estilo: {...}, flujos: [...], flujo_actual: {...} } }
            if (response.success && response.data) {
                // Extraer los flujos del array
                if (Array.isArray(response.data.flujos)) {
                    return response.data.flujos.map((flujo: any) => ({
                        id: flujo.id,
                        id_estilo: flujo.id_estilo,
                        nombre: flujo.nombre,
                        version: flujo.version,
                        costo_total_calculado: parseFloat(flujo.costo_total_calculado || 0),
                        tiempo_total_calculado: parseFloat(flujo.tiempo_total_calculado || 0),
                        es_actual: !!flujo.es_actual,
                        estado: flujo.estado || 'activo',
                        fecha_creacion: flujo.fecha_creacion,
                        fecha_actualizacion: flujo.fecha_actualizacion
                    }));
                }
                // Si hay flujo_actual, incluirlo también
                else if (response.data.flujo_actual) {
                    const flujo = response.data.flujo_actual;
                    return [{
                        id: flujo.id,
                        id_estilo: flujo.id_estilo,
                        nombre: flujo.nombre,
                        version: flujo.version,
                        costo_total_calculado: parseFloat(flujo.costo_total_calculado || 0),
                        tiempo_total_calculado: parseFloat(flujo.tiempo_total_calculado || 0),
                        es_actual: !!flujo.es_actual,
                        estado: flujo.estado || 'activo',
                        fecha_creacion: flujo.fecha_creacion,
                        fecha_actualizacion: flujo.fecha_actualizacion
                    }];
                }
            }
            
            return [];
        } catch (error) {
            console.error('Error obteniendo flujos del estilo:', error);
            return [];
        }
    }

    static async getFlujo(estiloId: number, flujoId: number): Promise<{
        flujo: FlujoEstilo;
        nodes: FlowNode[];
        edges: FlowEdge[];
    }> {
        try {
            const response = await this.request<any>('GET', `/estilos/${estiloId}/flujos/${flujoId}`);
            
            if (response.success && response.data) {
                return {
                    flujo: response.data.flujo || {},
                    nodes: response.data.nodes || [],
                    edges: response.data.edges || []
                };
            }
            
            return {
                flujo: {} as FlujoEstilo,
                nodes: [],
                edges: []
            };
        } catch (error) {
            console.error('Error obteniendo flujo:', error);
            return {
                flujo: {} as FlujoEstilo,
                nodes: [],
                edges: []
            };
        }
    }

    static async guardarFlujo(estiloId: number, data: {
        nombre: string;
        descripcion?: string;
        nodes: FlowNode[];
        edges: FlowEdge[];
    }): Promise<any> {
        const response = await this.request<any>('POST', `/estilos/${estiloId}/flujos`, data);
        return response.data;
    }

    static async actualizarPosicionesNodos(flujoId: number, nodos: Array<{
        id: number;
        pos_x: number;
        pos_y: number;
    }>): Promise<void> {
        await this.request<void>('PATCH', `/flujos/${flujoId}/posiciones`, {
            nodos
        });
    }

    static async eliminarFlujo(flujoId: number): Promise<void> {
        await this.request<void>('DELETE', `/flujos/${flujoId}`);
    }

    static async calcularTiempoFlujo(flujoId: number): Promise<any> {
        const response = await this.request<any>('GET', `/flujos/${flujoId}/calcular-tiempo`);
        return response.data;
    }

    // ========================================================================
    // CÁLCULOS DE VARIANTES
    // ========================================================================

    static async calcularVarianteTextil(estiloId: number, data: {
        color_id: number;
        talla_id: number;
        cantidad: number;
        procesos_opcionales?: number[];
    }): Promise<CalculoVariante> {
        // Transformar el formato del frontend al formato esperado por el backend
        const backendData = {
            id_color: data.color_id,
            id_talla: data.talla_id,
            cantidad_piezas: data.cantidad,
            id_flujo_estilo: 1 // Por defecto usar el flujo actual, se puede hacer dinámico después
        };
        
        const response = await this.request<any>('POST', `/estilos/${estiloId}/calcular-variante-textil`, backendData);
        console.log("response calcularVarianteTextil", response);
        
        // El backend devuelve: { success: true, data: { ... } }
        if (response.success && response.data) {
            return response.data;
        }
        
        throw new Error('Error en el cálculo de variante');
    }

    static async obtenerHistorialVariante(estiloId: number, colorId: number, tallaId: number): Promise<any[]> {
        const response = await this.request<any[]>('GET', `/estilos/${estiloId}/variantes/${colorId}/${tallaId}/historial`);
        return response.data;
    }

    // ========================================================================
    // UTILIDADES
    // ========================================================================

    static async getTemporadasUtils(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/utils/temporadas');
        return response.data;
    }

    static async getEstadosUtils(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/utils/estados');
        return response.data;
    }

    static async getAñosProduccion(): Promise<number[]> {
        const response = await this.request<number[]>('GET', '/utils/años-produccion');
        return response.data;
    }

    static async getTiposProcesoUtils(): Promise<string[]> {
        const response = await this.request<string[]>('GET', '/utils/tipos-proceso');
        return response.data;
    }
}

export default ApiService;