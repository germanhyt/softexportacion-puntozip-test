import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import ApiService from '../services/api';
import type {
    Estilo,
    Material,
    Proceso,
    EstiloFiltros,
    MaterialFiltros,
    ProcesoFiltros,
    FlowNode,
    FlowEdge
} from '../types';

// ============================================================================
// QUERY KEYS
// ============================================================================

export const queryKeys = {
    // Estilos
    estilos: ['estilos'] as const,
    estilo: (id: number) => ['estilos', id] as const,
    estilosWithFilters: (filtros: EstiloFiltros) => ['estilos', filtros] as const,
    resumenEstilos: ['estilos', 'resumen'] as const,
    tiposProducto: ['estilos', 'tipos-producto'] as const,
    temporadas: ['estilos', 'temporadas'] as const,
    estadosEstilo: ['estilos', 'estados'] as const,

    // Materiales
    materiales: ['materiales'] as const,
    material: (id: number) => ['materiales', id] as const,
    materialesWithFilters: (filtros: MaterialFiltros) => ['materiales', filtros] as const,
    tiposMaterial: ['materiales', 'tipos'] as const,
    materialesCriticos: ['materiales', 'criticos'] as const,

    // Procesos
    procesos: ['procesos'] as const,
    proceso: (id: number) => ['procesos', id] as const,
    procesosWithFilters: (filtros: ProcesoFiltros) => ['procesos', filtros] as const,
    procesosReactFlow: ['procesos', 'reactflow'] as const,
    sopProceso: (id: number) => ['procesos', id, 'sop'] as const,

    // Colores
    colores: ['colores'] as const,
    color: (id: number) => ['colores', id] as const,

    // Tallas
    tallas: ['tallas'] as const,
    talla: (id: number) => ['tallas', id] as const,
    tallasDisponibles: ['tallas', 'disponibles'] as const,

    // BOM
    bomEstilo: (id: number) => ['estilos', id, 'bom'] as const,

    // Flujos
    flujos: (estiloId: number) => ['estilos', estiloId, 'flujos'] as const,
    flujo: (estiloId: number, flujoId: number) => ['estilos', estiloId, 'flujos', flujoId] as const,

    // Cálculos
    calculoVariante: (estiloId: number, colorId: number, tallaId: number) => ['estilos', estiloId, 'calculo', colorId, tallaId] as const,
    historialVariante: (estiloId: number, colorId: number, tallaId: number) => ['estilos', estiloId, 'historial', colorId, tallaId] as const,

    // Utilidades
    temporadasUtils: ['utils', 'temporadas'] as const,
    estadosUtils: ['utils', 'estados'] as const,
    añosProduccion: ['utils', 'años-produccion'] as const,
    tiposProcesoUtils: ['utils', 'tipos-proceso'] as const,
};

// ============================================================================
// HOOKS PARA ESTILOS
// ============================================================================

export const useEstilos = (filtros?: EstiloFiltros) => {
    return useQuery({
        queryKey: filtros ? queryKeys.estilosWithFilters(filtros) : queryKeys.estilos,
        queryFn: () => ApiService.getEstilos(filtros),
        staleTime: 5 * 60 * 1000, // 5 minutos
        gcTime: 10 * 60 * 1000, // 10 minutos
    });
};

export const useEstilo = (id: number) => {
    return useQuery({
        queryKey: queryKeys.estilo(id),
        queryFn: () => ApiService.getEstilo(id),
        enabled: !!id,
        staleTime: 5 * 60 * 1000,
    });
};

export const useCreateEstilo = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (data: Partial<Estilo>) => ApiService.createEstilo(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.estilos });
            queryClient.invalidateQueries({ queryKey: queryKeys.resumenEstilos });
        },
    });
};

export const useUpdateEstilo = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: ({ id, data }: { id: number; data: Partial<Estilo> }) => 
            ApiService.updateEstilo(id, data),
        onSuccess: (_, { id }) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.estilo(id) });
            queryClient.invalidateQueries({ queryKey: queryKeys.estilos });
        },
    });
};

export const useDeleteEstilo = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (id: number) => ApiService.deleteEstilo(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.estilos });
            queryClient.invalidateQueries({ queryKey: queryKeys.resumenEstilos });
        },
    });
};

export const useDuplicarEstilo = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (id: number) => ApiService.duplicarEstilo(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.estilos });
        },
    });
};

export const useResumenEstilos = () => {
    return useQuery({
        queryKey: queryKeys.resumenEstilos,
        queryFn: () => ApiService.getResumenEstilos(),
        staleTime: 10 * 60 * 1000, // 10 minutos
    });
};

export const useTiposProducto = () => {
    return useQuery({
        queryKey: queryKeys.tiposProducto,
        queryFn: () => ApiService.getTiposProducto(),
        staleTime: 30 * 60 * 1000, // 30 minutos
    });
};

// ============================================================================
// HOOKS PARA MATERIALES
// ============================================================================

export const useMateriales = (filtros?: MaterialFiltros) => {
    return useQuery({
        queryKey: filtros ? queryKeys.materialesWithFilters(filtros) : queryKeys.materiales,
        queryFn: () => ApiService.getMateriales(filtros),
        staleTime: 5 * 60 * 1000,
    });
};

export const useMaterial = (id: number) => {
    return useQuery({
        queryKey: queryKeys.material(id),
        queryFn: () => ApiService.getMaterial(id),
        enabled: !!id,
        staleTime: 5 * 60 * 1000,
    });
};

export const useCreateMaterial = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (data: Partial<Material>) => ApiService.createMaterial(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.materiales });
        },
    });
};

export const useUpdateMaterial = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: ({ id, data }: { id: number; data: Partial<Material> }) => 
            ApiService.updateMaterial(id, data),
        onSuccess: (_, { id }) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.material(id) });
            queryClient.invalidateQueries({ queryKey: queryKeys.materiales });
        },
    });
};

export const useDeleteMaterial = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (id: number) => ApiService.deleteMaterial(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.materiales });
        },
    });
};

export const useTiposMaterial = () => {
    return useQuery({
        queryKey: queryKeys.tiposMaterial,
        queryFn: () => ApiService.getTiposMaterial(),
        staleTime: 30 * 60 * 1000,
    });
};

export const useMaterialesCriticos = () => {
    return useQuery({
        queryKey: queryKeys.materialesCriticos,
        queryFn: () => ApiService.getMaterialesCriticos(),
        staleTime: 10 * 60 * 1000,
    });
};

// ============================================================================
// HOOKS PARA PROCESOS
// ============================================================================

export const useProcesos = (filtros?: ProcesoFiltros) => {
    return useQuery({
        queryKey: filtros ? queryKeys.procesosWithFilters(filtros) : queryKeys.procesos,
        queryFn: () => ApiService.getProcesos(filtros),
        staleTime: 5 * 60 * 1000,
    });
};

export const useProceso = (id: number) => {
    return useQuery({
        queryKey: queryKeys.proceso(id),
        queryFn: () => ApiService.getProceso(id),
        enabled: !!id,
        staleTime: 5 * 60 * 1000,
    });
};

export const useCreateProceso = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (data: Partial<Proceso>) => ApiService.createProceso(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.procesos });
        },
    });
};

export const useUpdateProceso = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: ({ id, data }: { id: number; data: Partial<Proceso> }) => 
            ApiService.updateProceso(id, data),
        onSuccess: (_, { id }) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.proceso(id) });
            queryClient.invalidateQueries({ queryKey: queryKeys.procesos });
        },
    });
};

export const useDeleteProceso = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: (id: number) => ApiService.deleteProceso(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.procesos });
        },
    });
};

export const useProcesosReactFlow = () => {
    return useQuery({
        queryKey: queryKeys.procesosReactFlow,
        queryFn: () => ApiService.getProcesosParaReactFlow(),
        staleTime: 5 * 60 * 1000,
    });
};

export const useSOPProceso = (id: number) => {
    return useQuery({
        queryKey: queryKeys.sopProceso(id),
        queryFn: () => ApiService.getSOPProceso(id),
        enabled: !!id,
        staleTime: 10 * 60 * 1000,
    });
};

// ============================================================================
// HOOKS PARA COLORES
// ============================================================================

export const useColores = () => {
    return useQuery({
        queryKey: queryKeys.colores,
        queryFn: () => ApiService.getColores(),
        staleTime: 10 * 60 * 1000,
    });
};

export const useColor = (id: number) => {
    return useQuery({
        queryKey: queryKeys.color(id),
        queryFn: () => ApiService.getColor(id),
        enabled: !!id,
        staleTime: 10 * 60 * 1000,
    });
};

// ============================================================================
// HOOKS PARA TALLAS
// ============================================================================

export const useTallas = () => {
    return useQuery({
        queryKey: queryKeys.tallas,
        queryFn: () => ApiService.getTallas(),
        staleTime: 10 * 60 * 1000,
    });
};

export const useTallasDisponibles = () => {
    return useQuery({
        queryKey: queryKeys.tallasDisponibles,
        queryFn: () => ApiService.getTallasDisponibles(),
        staleTime: 10 * 60 * 1000,
    });
};

export const useTalla = (id: number) => {
    return useQuery({
        queryKey: queryKeys.talla(id),
        queryFn: () => ApiService.getTalla(id),
        enabled: !!id,
        staleTime: 10 * 60 * 1000,
    });
};

// ============================================================================
// HOOKS PARA BOM
// ============================================================================

export const useBOMEstilo = (estiloId: number) => {
    return useQuery({
        queryKey: queryKeys.bomEstilo(estiloId),
        queryFn: () => ApiService.getBOMEstilo(estiloId),
        enabled: !!estiloId,
        staleTime: 5 * 60 * 1000,
    });
};

export const useActualizarBOM = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: ({ estiloId, bomItems }: { estiloId: number; bomItems: any[] }) => 
            ApiService.actualizarBOM(estiloId, bomItems),
        onSuccess: (_, { estiloId }) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.bomEstilo(estiloId) });
        },
    });
};

// ============================================================================
// HOOKS PARA FLUJOS
// ============================================================================

export const useFlujosByEstilo = (estiloId: number) => {
    return useQuery({
        queryKey: queryKeys.flujos(estiloId),
        queryFn: () => ApiService.getFlujosByEstilo(estiloId),
        enabled: !!estiloId,
        staleTime: 5 * 60 * 1000,
    });
};

export const useFlujo = (estiloId: number, flujoId: number) => {
    return useQuery({
        queryKey: queryKeys.flujo(estiloId, flujoId),
        queryFn: () => ApiService.getFlujo(estiloId, flujoId),
        enabled: !!estiloId && !!flujoId,
        staleTime: 5 * 60 * 1000,
    });
};

export const useGuardarFlujo = () => {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: ({ estiloId, data }: { 
            estiloId: number; 
            data: { 
                nombre: string; 
                descripcion?: string; 
                nodes: FlowNode[]; 
                edges: FlowEdge[] 
            } 
        }) => ApiService.guardarFlujo(estiloId, data),
        onSuccess: (_, { estiloId }) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.flujos(estiloId) });
        },
    });
};

export const useActualizarPosicionesNodos = () => {
    return useMutation({
        mutationFn: ({ flujoId, nodos }: { 
            flujoId: number; 
            nodos: Array<{ id: number; pos_x: number; pos_y: number }> 
        }) => ApiService.actualizarPosicionesNodos(flujoId, nodos),
    });
};

// ============================================================================
// HOOKS PARA CÁLCULOS
// ============================================================================

export const useCalcularVarianteTextil = () => {
    return useMutation({
        mutationFn: ({ estiloId, data }: {
            estiloId: number;
            data: {
                color_id: number;
                talla_id: number;
                cantidad: number;
                procesos_opcionales?: number[];
            }
        }) => ApiService.calcularVarianteTextil(estiloId, data),
    });
};

export const useHistorialVariante = (estiloId: number, colorId: number, tallaId: number) => {
    return useQuery({
        queryKey: queryKeys.historialVariante(estiloId, colorId, tallaId),
        queryFn: () => ApiService.obtenerHistorialVariante(estiloId, colorId, tallaId),
        enabled: !!estiloId && !!colorId && !!tallaId,
        staleTime: 5 * 60 * 1000,
    });
};

// ============================================================================
// HOOKS PARA UTILIDADES
// ============================================================================

export const useTemporadasUtils = () => {
    return useQuery({
        queryKey: queryKeys.temporadasUtils,
        queryFn: () => ApiService.getTemporadasUtils(),
        staleTime: 60 * 60 * 1000, // 1 hora
    });
};

export const useEstadosUtils = () => {
    return useQuery({
        queryKey: queryKeys.estadosUtils,
        queryFn: () => ApiService.getEstadosUtils(),
        staleTime: 60 * 60 * 1000, // 1 hora
    });
};

export const useAñosProduccion = () => {
    return useQuery({
        queryKey: queryKeys.añosProduccion,
        queryFn: () => ApiService.getAñosProduccion(),
        staleTime: 60 * 60 * 1000, // 1 hora
    });
};

export const useTiposProcesoUtils = () => {
    return useQuery({
        queryKey: queryKeys.tiposProcesoUtils,
        queryFn: () => ApiService.getTiposProcesoUtils(),
        staleTime: 60 * 60 * 1000, // 1 hora
    });
};