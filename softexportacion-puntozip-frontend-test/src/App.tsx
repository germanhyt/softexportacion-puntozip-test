import React, { useState, useEffect, useCallback } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
    ReactFlow,
    Controls,
    Background,
    type Connection,
    ConnectionMode,
    ReactFlowProvider,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

import { useEstilos, useBOMEstilo, useProcesos, useColores, useTallasDisponibles, useCalcularVarianteTextil } from './hooks/useApi';
import CustomNode from './components/CustomNode';
import type { Estilo, BomItem, Proceso, FlujoEstilo, FlowNode, FlowEdge, Color, Talla, CalculoVariante } from './types';
import ApiService from './services/api';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      retry: 1,
    },
  },
});

// Definir tipos de nodos para ReactFlow
const nodeTypes = {
  customNode: CustomNode,
};

// ============================================================================
// COMPONENTE REACTFLOW PRINCIPAL
// ============================================================================

const ReactFlowEditor: React.FC<{
  nodes: FlowNode[];
  edges: FlowEdge[];
  onNodesChange: (nodes: FlowNode[]) => void;
  onEdgesChange: (edges: FlowEdge[]) => void;
  onConnect: (connection: Connection) => void;
  onNodePositionChange?: (nodeId: string, position: { x: number; y: number }) => void;
}> = ({ nodes, edges, onNodesChange, onEdgesChange, onConnect, onNodePositionChange }) => {

  const handleConnect = useCallback((params: Connection) => {
    if (params.source === params.target) {
      alert('No se puede conectar un nodo consigo mismo');
      return;
    }
    onConnect(params);
  }, [onConnect]);

      const handleNodesChange = useCallback((changes: any) => {
        // Manejar cambios en los nodos incluyendo cambios de posición
        const updatedNodes = nodes.map((node: FlowNode) => {
            const change = changes.find((c: any) => c.id === node.id && c.type === 'position');
            if (change && change.position && !change.dragging) {
                // Solo actualizar cuando se suelta el nodo
                // Llamar callback para actualizar posición en BD si está disponible
                if (onNodePositionChange) {
                    onNodePositionChange(node.id, change.position);
                }

                // Actualizar posición del nodo y datos internos
                return {
                    ...node,
                    position: change.position,
                    data: {
                        ...node.data,
                        pos_x: change.position.x,
                        pos_y: change.position.y
                    }
                };
            } else if (change && change.type === 'position' && change.position) {
                // Durante el arrastre solo actualizar visualmente
                return { ...node, position: change.position };
            }

            // Manejar otros tipos de cambios
            const otherChange = changes.find((c: any) => c.id === node.id);
            if (otherChange) {
                switch (otherChange.type) {
                    case 'select':
                        return { ...node, selected: otherChange.selected };
                    case 'remove':
                        return null;
                    default:
                        return node;
                }
            }

            return node;
        }).filter(Boolean) as FlowNode[];

        onNodesChange(updatedNodes);
    }, [nodes, onNodesChange, onNodePositionChange]);

  const handleEdgesChange = useCallback((changes: any) => {
    const updatedEdges = edges.map((edge: FlowEdge) => {
      const change = changes.find((c: any) => c.id === edge.id);

      if (change) {
        switch (change.type) {
          case 'select':
            return { ...edge, selected: change.selected };
          case 'remove':
            return null;
          default:
            return edge;
        }
      }

      return edge;
    }).filter(Boolean) as FlowEdge[];

    onEdgesChange(updatedEdges);
  }, [edges, onEdgesChange]);

  return (
    <div style={{ width: '100%', height: '100%' }}>
      <ReactFlow
        nodes={nodes}
        edges={edges}
        onNodesChange={handleNodesChange}
        onEdgesChange={handleEdgesChange}
        onConnect={handleConnect}
        nodeTypes={nodeTypes}
        connectionMode={ConnectionMode.Loose}
        fitView
        fitViewOptions={{ padding: 0.2 }}
        attributionPosition="top-right"
        defaultEdgeOptions={{
          animated: true,
          style: { strokeWidth: 2, stroke: '#64748b' },
          type: 'smoothstep',
        }}
        nodesDraggable={true}
        nodesConnectable={true}
        elementsSelectable={true}
        selectNodesOnDrag={false}
        panOnDrag={true}
        zoomOnScroll={true}
        zoomOnPinch={true}
        panOnScroll={false}
        zoomOnDoubleClick={true}
                        maxZoom={1.5}
                minZoom={0.5}
        style={{
          background: '#fafbfc',
        }}
      >
        <Controls
          style={{
            backgroundColor: 'white',
            border: '1px solid #e2e8f0',
            borderRadius: '8px',
          }}
        />
        <Background />
      </ReactFlow>
    </div>
  );
};

// ============================================================================
// COMPONENTE CALCULADORA DE VARIANTES
// ============================================================================

interface VariantesCalculadoraProps {
  estilo: Estilo;
  colores: Color[];
  tallas: Talla[];
  loadingColores: boolean;
  loadingTallas: boolean;
  calcularVarianteMutation: any;
}

const VariantesCalculadora: React.FC<VariantesCalculadoraProps> = ({
  estilo,
  colores,
  tallas,
  loadingColores,
  loadingTallas,
  calcularVarianteMutation
}) => {
  const [selectedColor, setSelectedColor] = useState<number | null>(null);
  const [selectedTalla, setSelectedTalla] = useState<number | null>(null);
  const [cantidad, setCantidad] = useState<number>(100);
  const [procesosOpcionales] = useState<number[]>([]); // setProcesosOpcionales para futuras mejoras
  const [calculoResultado, setCalculoResultado] = useState<CalculoVariante | null>(null);

  const handleCalcular = async () => {
    if (!selectedColor || !selectedTalla) {
      alert('Por favor selecciona color y talla');
      return;
    }

    try {
      const resultado = await calcularVarianteMutation.mutateAsync({
        estiloId: estilo.id,
        data: {
          color_id: selectedColor,
          talla_id: selectedTalla,
          cantidad: cantidad,
          procesos_opcionales: procesosOpcionales
        }
      });
      setCalculoResultado(resultado);
    } catch (error) {
      console.error('Error calculando variante:', error);
      alert('Error al calcular la variante');
    }
  };

  return (
    <div>
      <div style={{ marginBottom: '24px' }}>
        <h2 style={{ fontSize: '28px', fontWeight: '700', color: '#1e293b', marginBottom: '8px' }}>
          🎨 Cálculo de Variantes - {estilo.nombre}
        </h2>
        <p style={{ color: '#64748b', fontSize: '16px' }}>
          Calcula costos y tiempos específicos por color y talla
        </p>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '24px' }}>
        {/* Panel de configuración */}
        <div style={{
          background: 'white',
          borderRadius: '16px',
          border: '2px solid #e2e8f0',
          padding: '24px',
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.05)',
        }}>
          <h3 style={{ fontSize: '20px', fontWeight: '700', color: '#1e293b', marginBottom: '20px' }}>
            Configuración de Variante
          </h3>

          {/* Selector de Color */}
          <div style={{ marginBottom: '20px' }}>
            <label style={{ 
              display: 'block', 
              fontSize: '14px', 
              fontWeight: '600', 
              color: '#374151', 
              marginBottom: '8px' 
            }}>
              Color:
            </label>
            {loadingColores ? (
              <div style={{ padding: '12px', color: '#64748b' }}>Cargando colores...</div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', gap: '8px' }}>
                {colores.map((color) => (
                  <button
                    key={color.id}
                    onClick={() => setSelectedColor(color.id)}
                    style={{
                      padding: '8px 12px',
                      border: selectedColor === color.id ? '2px solid #3b82f6' : '1px solid #e2e8f0',
                      borderRadius: '8px',
                      background: selectedColor === color.id ? '#dbeafe' : 'white',
                      cursor: 'pointer',
                      fontSize: '12px',
                      fontWeight: '500',
                      transition: 'all 0.2s'
                    }}
                  >
                    <div style={{
                      width: '16px',
                      height: '16px',
                      borderRadius: '50%',
                      background: color.codigo_hex || '#64748b',
                      display: 'inline-block',
                      marginRight: '8px',
                      border: '1px solid #d1d5db'
                    }} />
                    {color.nombre}
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Selector de Talla */}
          <div style={{ marginBottom: '20px' }}>
            <label style={{ 
              display: 'block', 
              fontSize: '14px', 
              fontWeight: '600', 
              color: '#374151', 
              marginBottom: '8px' 
            }}>
              Talla:
            </label>
            {loadingTallas ? (
              <div style={{ padding: '12px', color: '#64748b' }}>Cargando tallas...</div>
            ) : (
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                {tallas.map((talla) => (
                  <button
                    key={talla.id}
                    onClick={() => setSelectedTalla(talla.id)}
                    style={{
                      padding: '8px 16px',
                      border: selectedTalla === talla.id ? '2px solid #3b82f6' : '1px solid #e2e8f0',
                      borderRadius: '8px',
                      background: selectedTalla === talla.id ? '#dbeafe' : 'white',
                      cursor: 'pointer',
                      fontSize: '14px',
                      fontWeight: '600',
                      minWidth: '50px',
                      transition: 'all 0.2s'
                    }}
                  >
                    {talla.codigo}
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Cantidad */}
          <div style={{ marginBottom: '20px' }}>
            <label style={{ 
              display: 'block', 
              fontSize: '14px', 
              fontWeight: '600', 
              color: '#374151', 
              marginBottom: '8px' 
            }}>
              Cantidad de piezas:
            </label>
            <input
              type="number"
              value={cantidad}
              onChange={(e) => setCantidad(Number(e.target.value))}
              min="1"
              style={{
                width: '100%',
                padding: '12px',
                border: '1px solid #e2e8f0',
                borderRadius: '8px',
                fontSize: '14px'
              }}
            />
          </div>

          {/* Botón de cálculo */}
          <button
            onClick={handleCalcular}
            disabled={calcularVarianteMutation.isPending || !selectedColor || !selectedTalla}
            style={{
              width: '100%',
              padding: '12px 20px',
              background: (!selectedColor || !selectedTalla) ? '#94a3b8' : '#3b82f6',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '16px',
              fontWeight: '600',
              cursor: (!selectedColor || !selectedTalla) ? 'not-allowed' : 'pointer',
              transition: 'all 0.2s'
            }}
          >
            {calcularVarianteMutation.isPending ? 'Calculando...' : '🔢 Calcular Variante'}
          </button>
        </div>

        {/* Panel de resultados */}
        <div style={{
          background: 'white',
          borderRadius: '16px',
          border: '2px solid #e2e8f0',
          padding: '24px',
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.05)',
        }}>
          <h3 style={{ fontSize: '20px', fontWeight: '700', color: '#1e293b', marginBottom: '20px' }}>
            Resultados del Cálculo
          </h3>

          {calcularVarianteMutation.isPending && (
            <div style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
              <div style={{ fontSize: '48px', marginBottom: '16px' }}>⏳</div>
              <div>Calculando variante...</div>
            </div>
          )}

          {calculoResultado && (
            <div>
              {/* Información de la variante */}
              <div style={{ 
                background: '#f8fafc', 
                padding: '16px', 
                borderRadius: '8px', 
                marginBottom: '20px',
                border: '1px solid #e2e8f0'
              }}>
                <h4 style={{ fontSize: '16px', fontWeight: '600', color: '#1e293b', marginBottom: '8px' }}>
                  Variante Calculada
                </h4>
                <div style={{ fontSize: '14px', color: '#64748b' }}>
                  <div><strong>SKU:</strong> {calculoResultado.variante?.codigo_sku}</div>
                  <div><strong>Color:</strong> {calculoResultado.variante?.color}</div>
                  <div><strong>Talla:</strong> {calculoResultado.variante?.talla}</div>
                  <div><strong>Cantidad:</strong> {calculoResultado.variante?.cantidad_piezas} piezas</div>
                </div>
              </div>

              {/* Costos */}
              <div style={{ marginBottom: '20px' }}>
                <h4 style={{ fontSize: '16px', fontWeight: '600', color: '#1e293b', marginBottom: '12px' }}>
                  💰 Análisis de Costos
                </h4>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                  <div style={{ 
                    background: '#f0fdf4', 
                    padding: '12px', 
                    borderRadius: '8px',
                    border: '1px solid #bbf7d0'
                  }}>
                    <div style={{ fontSize: '12px', color: '#166534', fontWeight: '600' }}>MATERIALES</div>
                    <div style={{ fontSize: '18px', fontWeight: '700', color: '#166534' }}>
                      ${calculoResultado.costos?.materiales?.toFixed(2)}
                    </div>
                  </div>
                  <div style={{ 
                    background: '#fef3c7', 
                    padding: '12px', 
                    borderRadius: '8px',
                    border: '1px solid #fde68a'
                  }}>
                    <div style={{ fontSize: '12px', color: '#92400e', fontWeight: '600' }}>PROCESOS</div>
                    <div style={{ fontSize: '18px', fontWeight: '700', color: '#92400e' }}>
                      ${calculoResultado.costos?.procesos?.toFixed(2)}
                    </div>
                  </div>
                  <div style={{ 
                    background: '#dbeafe', 
                    padding: '12px', 
                    borderRadius: '8px',
                    border: '1px solid #93c5fd',
                    gridColumn: 'span 2'
                  }}>
                    <div style={{ fontSize: '12px', color: '#1e40af', fontWeight: '600' }}>TOTAL</div>
                    <div style={{ fontSize: '24px', fontWeight: '700', color: '#1e40af' }}>
                      ${calculoResultado.costos?.total?.toFixed(2)}
                    </div>
                    <div style={{ fontSize: '12px', color: '#1e40af' }}>
                      (${calculoResultado.costos?.por_pieza?.toFixed(2)} por pieza)
                    </div>
                  </div>
                </div>
              </div>

              {/* Tiempo */}
              <div style={{ marginBottom: '20px' }}>
                <h4 style={{ fontSize: '16px', fontWeight: '600', color: '#1e293b', marginBottom: '12px' }}>
                  ⏱️ Análisis de Tiempo
                </h4>
                <div style={{ 
                  background: '#fef3c7', 
                  padding: '16px', 
                  borderRadius: '8px',
                  border: '1px solid #fde68a'
                }}>
                  <div style={{ fontSize: '14px', color: '#92400e', fontWeight: '600', marginBottom: '4px' }}>
                    TIEMPO TOTAL
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: '700', color: '#92400e' }}>
                    {calculoResultado.tiempo?.total_minutos} minutos
                  </div>
                  <div style={{ fontSize: '12px', color: '#92400e' }}>
                    ({calculoResultado.tiempo?.por_pieza_minutos} min por pieza)
                  </div>
                </div>
              </div>
            </div>
          )}

          {!calculoResultado && !calcularVarianteMutation.isPending && (
            <div style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
              <div style={{ fontSize: '48px', marginBottom: '16px' }}>🎯</div>
              <div>Selecciona color, talla y cantidad para ver el cálculo</div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

// ============================================================================
// COMPONENTE PRINCIPAL MEJORADO
// ============================================================================

function SistemaTextilApp() {
  const [selectedEstilo, setSelectedEstilo] = useState<Estilo | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [activeView, setActiveView] = useState<'list' | 'flow' | 'bom' | 'variantes'>('list');

  // Estados para flujo de procesos
  const [selectedFlujo, setSelectedFlujo] = useState<FlujoEstilo | null>(null);
  const [nodes, setNodes] = useState<FlowNode[]>([]);
  const [edges, setEdges] = useState<FlowEdge[]>([]);
  const [guardandoFlujo, setGuardandoFlujo] = useState(false);

  // Hooks para cargar datos
  const { data: estilos, isLoading: loadingEstilos, error: errorEstilos } = useEstilos();
  // console.log("estilos", estilos);

  const { data: procesos = [], isLoading: loadingProcesos } = useProcesos();
  // console.log("procesos", procesos);
  const { data: bomData, isLoading: loadingBom } = useBOMEstilo(selectedEstilo?.id || 0);
  console.log("bomData", bomData);

  // Hooks para variantes
  const { data: colores = [], isLoading: loadingColores } = useColores();
  const { data: tallas = [], isLoading: loadingTallas } = useTallasDisponibles();
  console.log("tallas", tallas);

  const calcularVarianteMutation = useCalcularVarianteTextil();

  // Cargar flujos cuando se selecciona un estilo
  useEffect(() => {
    const cargarFlujosDelEstilo = async () => {
      if (!selectedEstilo) {
        setNodes([]);
        setEdges([]);
        setSelectedFlujo(null);
        return;
      }

      try {
        console.log('Cargando flujos para estilo:', selectedEstilo.id);
        const flujosData = await ApiService.getFlujosByEstilo(selectedEstilo.id);
        console.log('Flujos obtenidos:', flujosData);
        
        if (Array.isArray(flujosData) && flujosData.length > 0) {
          // Buscar flujo actual o tomar el primero
          const flujoActual = flujosData.find(f => f.es_actual) || flujosData[0];
          console.log('Cargando flujo:', flujoActual);
          await cargarFlujoOptimizado(selectedEstilo.id, flujoActual);
        } else {
          // No hay flujos, limpiar
          setNodes([]);
          setEdges([]);
          setSelectedFlujo(null);
        }
      } catch (error) {
        console.error('Error cargando flujos del estilo:', error);
        setNodes([]);
        setEdges([]);
        setSelectedFlujo(null);
      }
    };

    cargarFlujosDelEstilo();
  }, [selectedEstilo]);

  const onConnect = useCallback((connection: Connection) => {
    if (connection.source === connection.target) {
      alert('No se puede conectar un nodo consigo mismo');
      return;
    }

    const newEdge: FlowEdge = {
      id: `edge-${connection.source}-${connection.target}-${Date.now()}`,
      source: connection.source,
      target: connection.target,
      type: 'smoothstep',
      animated: true,
      style: { strokeWidth: 2, stroke: '#64748b' },
    };
    setEdges((eds: FlowEdge[]) => [...eds, newEdge]);
  }, []);

  const cargarFlujo = async (flujo: FlujoEstilo) => {
    if (!selectedEstilo) return;

    try {
      console.log('Cargando datos del flujo:', flujo.id);
      const flujoData = await ApiService.getFlujo(selectedEstilo.id, flujo.id);
      console.log('Datos del flujo obtenidos:', flujoData);
      
      setSelectedFlujo(flujo);

      // Asegurar que los nodos tengan el tipo correcto y usen pos_x/pos_y de la BD
      const nodosConTipo: FlowNode[] = (flujoData.nodes || []).map((node: any) => ({
        ...node,
        type: 'customNode',
        position: {
          x: node.data?.pos_x || node.data?.posX || node.position?.x || 100,
          y: node.data?.pos_y || node.data?.posY || node.position?.y || 100
        }
      }));

      console.log('Nodos procesados:', nodosConTipo);
      setNodes(nodosConTipo);
      setEdges(flujoData.edges || []);
    } catch (error) {
      console.error('Error cargando flujo:', error);
      // No mostrar alert, solo log del error
    }
  };

  const cargarFlujoOptimizado = async (estiloId: number, flujo: FlujoEstilo) => {
    try {
      console.log('Cargando flujo optimizado:', flujo.id);
      
      // Reutilizar los datos que ya obtuvimos en getFlujosByEstilo
      // En lugar de hacer otra llamada, usar los datos completos que ya tenemos
      const response = await fetch(`http://localhost:8002/api/v1/estilos/${estiloId}/flujos`, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });
      const responseData = await response.json();
      console.log('Respuesta completa flujos:', responseData);
      
      if (responseData.success && responseData.data) {
        const flujoCompleto = responseData.data.flujo_actual || 
                              responseData.data.flujos?.find((f: any) => f.id === flujo.id);
        
        if (flujoCompleto && flujoCompleto.nodos) {
          setSelectedFlujo(flujo);

          // Convertir nodos del backend al formato ReactFlow
          const nodosReactFlow: FlowNode[] = flujoCompleto.nodos.map((nodo: any) => ({
            id: nodo.id.toString(),
            type: 'customNode',
            position: {
              x: parseFloat(nodo.pos_x || 0),
              y: parseFloat(nodo.pos_y || 0)
            },
            data: {
              id_proceso: nodo.id_proceso,
              codigo: nodo.proceso?.codigo || '',
              nombre: nodo.proceso?.nombre || 'Proceso',
              descripcion: nodo.proceso?.descripcion || '',
              tipo: nodo.proceso?.tipo_proceso?.nombre || 'General',
              costo_base: parseFloat(nodo.proceso?.costo_base || 0),
              tiempo_base_min: parseFloat(nodo.proceso?.tiempo_base_min || 0),
              es_paralelo: !!nodo.proceso?.es_paralelo,
              es_opcional: !!nodo.proceso?.es_opcional,
              requiere_color: !!nodo.proceso?.requiere_color,
              es_punto_inicio: !!nodo.es_punto_inicio,
              es_punto_final: !!nodo.es_punto_final,
              orden_secuencia: nodo.orden_secuencia || 1,
              color_tipo: '#64748b', // Color por defecto
              notas: nodo.notas || '',
              ancho: parseFloat(nodo.ancho || 200),
              alto: parseFloat(nodo.alto || 80),
              inputs: [],
              outputs: [],
              tiene_personalizaciones: !!(nodo.costo_personalizado || nodo.tiempo_personalizado_min),
              costo_personalizado: nodo.costo_personalizado ? parseFloat(nodo.costo_personalizado) : undefined,
              tiempo_personalizado_min: nodo.tiempo_personalizado_min ? parseFloat(nodo.tiempo_personalizado_min) : undefined,
              costo_efectivo: parseFloat(nodo.costo_personalizado || nodo.proceso?.costo_base || 0),
              tiempo_efectivo: parseFloat(nodo.tiempo_personalizado_min || nodo.proceso?.tiempo_base_min || 0),
              merma_porcentaje: parseFloat(nodo.proceso?.merma_porcentaje || 0),
              pos_x: parseFloat(nodo.pos_x || 0),
              pos_y: parseFloat(nodo.pos_y || 0),
              tipo_proceso: nodo.proceso?.tipo_proceso ? {
                id: nodo.proceso.tipo_proceso.id,
                nombre: nodo.proceso.tipo_proceso.nombre,
                color_hex: nodo.proceso.tipo_proceso.color_hex,
                icono: nodo.proceso.tipo_proceso.icono
              } : undefined
            }
          }));

          // Convertir conexiones del backend al formato ReactFlow
          const edgesReactFlow: FlowEdge[] = (flujoCompleto.conexiones || []).map((conexion: any) => ({
            id: conexion.id.toString(),
            source: conexion.id_nodo_origen.toString(),
            target: conexion.id_nodo_destino.toString(),
            type: 'smoothstep',
            animated: !!conexion.es_animada,
            style: {
              strokeWidth: 2,
              stroke: conexion.color_linea || '#64748b'
            },
            data: {
              tipo_conexion: conexion.tipo_conexion || 'secuencial',
              etiqueta: conexion.etiqueta,
              condicion_activacion: conexion.condicion_activacion,
              orden_prioridad: conexion.orden_prioridad || 1
            },
            label: conexion.etiqueta || undefined
          }));

          console.log('Nodos ReactFlow procesados:', nodosReactFlow);
          console.log('Edges ReactFlow procesados:', edgesReactFlow);
          
          setNodes(nodosReactFlow);
          setEdges(edgesReactFlow);
        }
      }
    } catch (error) {
      console.error('Error cargando flujo optimizado:', error);
      // Fallback al método original
      await cargarFlujo(flujo);
    }
  };

  const agregarProceso = (proceso: Proceso) => {
    // Verificar si el proceso ya existe en el flujo
    const isAlreadyConnected = nodes.some(
      (node: FlowNode) => node.data.id_proceso === proceso.id
    );

    if (isAlreadyConnected) {
      alert('Ya existe el proceso seleccionado dentro del flujo');
      return;
    }

    const newNode: FlowNode = {
      id: proceso.id.toString(),
      type: 'customNode',
      position: {
        x: nodes.length > 0 ? 20 * (nodes.length + 1) + 100 : 100,
        y: nodes.length > 0 ? 15 * (nodes.length + 1) + 50 : 50
      },
      data: {
        id_proceso: proceso.id,
        nombre: proceso.nombre,
        descripcion: proceso.descripcion,
        tiempo_efectivo: proceso.tiempo_base_min,
        tiempo_base_min: proceso.tiempo_base_min,
        costo_efectivo: proceso.costo_base,
        costo_base: proceso.costo_base,
        tipo_proceso: proceso.tipo_proceso,
        tipo: proceso.tipo_proceso?.nombre || 'General',
        color_tipo: proceso.tipo_proceso?.color_hex || '#64748b',
        orden_secuencia: nodes.length + 1,
        es_paralelo: proceso.es_paralelo,
        es_opcional: false,
        es_punto_inicio: nodes.length === 0,
        es_punto_final: false,
        requiere_color: false,
        tiene_personalizaciones: false,
        merma_porcentaje: proceso.merma_porcentaje || 0,
        codigo: proceso.codigo,
        inputs: [],
        outputs: [],
        notas: '',
        pos_x: nodes.length > 0 ? 20 * (nodes.length + 1) + 100 : 100,
        pos_y: nodes.length > 0 ? 15 * (nodes.length + 1) + 50 : 50
      },
    };

    setNodes((nds: FlowNode[]) => [...nds, newNode]);
  };

  // Función para actualizar posición en la BD cuando se mueve un nodo
  const handleNodePositionChange = useCallback(async (nodeId: string, position: { x: number; y: number }) => {
    if (!selectedEstilo) return;

    const node = nodes.find((n: FlowNode) => n.id === nodeId);
    if (!node) return;

    try {
      // Llamar a la API para actualizar la posición en la BD
      await ApiService.actualizarPosicionesNodos(
        selectedFlujo?.id || 1,
        [{ id: parseInt(nodeId), pos_x: position.x, pos_y: position.y }]
      );
    } catch (error) {
      console.error('Error actualizando posición del nodo:', error);
    }
  }, [selectedEstilo, selectedFlujo, nodes]);

  const guardarFlujo = async () => {
    if (!selectedEstilo || nodes.length === 0) {
      alert('Agrega al menos un proceso antes de guardar');
      return;
    }

    try {
      setGuardandoFlujo(true);
      const flujoData = {
        nombre: `Flujo ${selectedEstilo.nombre} - ${new Date().toLocaleDateString()}`,
        nodes,
        edges,
      };

      const nuevoFlujo = await ApiService.guardarFlujo(selectedEstilo.id, flujoData);
      console.log('Flujo guardado:', nuevoFlujo);
      alert('Flujo guardado exitosamente');
    } catch (error) {
      console.error('Error guardando flujo:', error);
      alert('Error al guardar el flujo');
    } finally {
      setGuardandoFlujo(false);
    }
  };

  const crearNuevoFlujo = () => {
    setSelectedFlujo(null);
    setNodes([]);
    setEdges([]);
  };

  const estilosFiltrados = Array.isArray(estilos) ? estilos.filter((estilo: Estilo) =>
    estilo && estilo.nombre && estilo.codigo
    // &&
    // (estilo.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
    //   estilo.codigo.toLowerCase().includes(searchTerm.toLowerCase()))
  ) : [];

  console.log("estilosFiltrados", estilosFiltrados);

  console.log("Array.isArray(estilos)", Array.isArray(estilos));

  if (loadingEstilos) {
    return (
      <div style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        height: '100vh',
        fontFamily: 'system-ui, -apple-system, sans-serif',
        background: '#f8fafc',
      }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>⚙️</div>
          <div style={{ fontSize: '18px', color: '#64748b' }}>Cargando sistema textil...</div>
        </div>
      </div>
    );
  }

  if (errorEstilos) {
    return (
      <div style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        height: '100vh',
        fontFamily: 'system-ui, -apple-system, sans-serif',
        background: '#f8fafc',
      }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>❌</div>
          <div style={{ fontSize: '18px', color: '#dc2626' }}>
            Error cargando datos: {errorEstilos.message}
          </div>
          <div style={{ marginTop: '16px', fontSize: '14px', color: '#64748b' }}>
            Verifica que el backend esté ejecutándose en http://localhost:8002
          </div>
        </div>
      </div>
    );
  }

  return (
    <div style={{
      fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
      maxWidth: '1400px',
      margin: '0 auto',
      padding: '20px',
      background: '#f8fafc',
      minHeight: '100vh',
    }}>
      {/* Header mejorado inspirado en el HTML */}
      <header style={{
        textAlign: 'center',
        marginBottom: '32px',
        background: 'white',
        padding: '32px',
        borderRadius: '16px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.05)',
      }}>
        <h1 style={{
          fontSize: '36px',
          fontWeight: '800',
          color: '#1e293b',
          marginBottom: '8px',
          background: 'linear-gradient(135deg, #3b82f6, #8b5cf6)',
          WebkitBackgroundClip: 'text',
          WebkitTextFillColor: 'transparent',
        }}>
          🧵 Sistema de Gestión Textil Avanzado
        </h1>
        <p style={{
          color: '#64748b',
          fontSize: '18px',
          margin: '0'
        }}>
          Gestión integral de estilos, procesos y materiales con ReactFlow
        </p>
      </header>

      {/* Navigation mejorada */}
      <div style={{ marginBottom: '24px' }}>
        <div style={{
          display: 'flex',
          gap: '4px',
          background: 'white',
          borderRadius: '12px',
          padding: '6px',
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)',
          border: '1px solid #e2e8f0',
        }}>
          <button
            onClick={() => setActiveView('list')}
            style={{
              padding: '12px 20px',
              borderRadius: '8px',
              border: 'none',
              cursor: 'pointer',
              fontWeight: '600',
              fontSize: '14px',
              background: activeView === 'list' ? '#3b82f6' : 'transparent',
              color: activeView === 'list' ? 'white' : '#64748b',
              transition: 'all 0.2s',
            }}
          >
            📋 Catálogo de Estilos
          </button>
          {selectedEstilo && (
            <>
              <button
                onClick={() => setActiveView('flow')}
                style={{
                  padding: '12px 20px',
                  borderRadius: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  fontWeight: '600',
                  fontSize: '14px',
                  background: activeView === 'flow' ? '#3b82f6' : 'transparent',
                  color: activeView === 'flow' ? 'white' : '#64748b',
                  transition: 'all 0.2s',
                }}
              >
                🔀 Flujo Interactivo de Procesos
              </button>
              <button
                onClick={() => setActiveView('bom')}
                style={{
                  padding: '12px 20px',
                  borderRadius: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  fontWeight: '600',
                  fontSize: '14px',
                  background: activeView === 'bom' ? '#3b82f6' : 'transparent',
                  color: activeView === 'bom' ? 'white' : '#64748b',
                  transition: 'all 0.2s',
                }}
              >
                📦 Lista de Materiales (BOM)
              </button>
              <button
                onClick={() => setActiveView('variantes')}
                style={{
                  padding: '12px 20px',
                  borderRadius: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  fontWeight: '600',
                  fontSize: '14px',
                  background: activeView === 'variantes' ? '#3b82f6' : 'transparent',
                  color: activeView === 'variantes' ? 'white' : '#64748b',
                  transition: 'all 0.2s',
                }}
              >
                🎨 Cálculo de Variantes
              </button>
            </>
          )}
        </div>
      </div>

      {/* Content - Lista de Estilos */}
      {activeView === 'list' && (
        <div>
          <div style={{ marginBottom: '24px' }}>
            <input
              type="text"
              placeholder="🔍 Buscar estilos por nombre o código..."
              value={searchTerm}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
              style={{
                width: '100%',
                padding: '16px 20px',
                border: '2px solid #e2e8f0',
                borderRadius: '12px',
                fontSize: '16px',
                background: 'white',
                transition: 'border-color 0.2s',
              }}
              onFocus={(e: React.FocusEvent<HTMLInputElement>) => e.currentTarget.style.borderColor = '#3b82f6'}
              onBlur={(e: React.FocusEvent<HTMLInputElement>) => e.currentTarget.style.borderColor = '#e2e8f0'}
            />
          </div>

          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))',
            gap: '24px'
          }}>
            {estilosFiltrados.map((estilo) => (
              <div
                key={estilo.id}
                onClick={() => setSelectedEstilo(estilo)}
                style={{
                  background: 'white',
                  borderRadius: '16px',
                  border: '2px solid',
                  borderColor: selectedEstilo?.id === estilo.id ? '#3b82f6' : '#e2e8f0',
                  padding: '24px',
                  cursor: 'pointer',
                  transition: 'all 0.3s',
                  boxShadow: selectedEstilo?.id === estilo.id ?
                    '0 8px 25px rgba(59, 130, 246, 0.15)' :
                    '0 1px 3px rgba(0, 0, 0, 0.1)',
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '16px' }}>
                  <h3 style={{ fontSize: '18px', fontWeight: '700', color: '#1e293b', margin: 0 }}>
                    {estilo.nombre}
                  </h3>
                  <span style={{
                    padding: '4px 8px',
                    borderRadius: '9999px',
                    fontSize: '12px',
                    fontWeight: 'medium',
                    background: estilo.estado === 'activo' ? '#d1fae5' :
                      estilo.estado === 'desarrollo' ? '#dbeafe' : '#f3f4f6',
                    color: estilo.estado === 'activo' ? '#065f46' :
                      estilo.estado === 'desarrollo' ? '#1e40af' : '#374151',
                  }}>
                    {estilo.estado}
                  </span>
                </div>

                <div style={{ marginBottom: '16px' }}>
                  <p style={{ fontSize: '14px', color: '#64748b', margin: '4px 0' }}>
                    Código: {estilo.codigo}
                  </p>
                  <p style={{ fontSize: '14px', color: '#64748b', margin: '4px 0' }}>
                    Temporada: {estilo.temporada} {estilo.año_produccion}
                  </p>
                  {estilo.costo_objetivo && (
                    <p style={{ fontSize: '14px', color: '#10b981', margin: '4px 0' }}>
                      Costo objetivo: ${estilo.costo_objetivo}
                    </p>
                  )}
                </div>

                {estilo.descripcion && (
                  <p style={{
                    fontSize: '14px',
                    color: '#64748b',
                    lineHeight: '1.4',
                    overflow: 'hidden',
                    display: '-webkit-box',
                    WebkitLineClamp: 3,
                    WebkitBoxOrient: 'vertical',
                  }}>
                    {estilo.descripcion}
                  </p>
                )}
              </div>
            ))}
          </div>

          {estilosFiltrados.length === 0 && (
            <div style={{ textAlign: 'center', padding: '64px', background: 'white', borderRadius: '16px' }}>
              <div style={{ fontSize: '64px', marginBottom: '20px' }}>🔍</div>
              <h3 style={{ fontSize: '24px', fontWeight: '600', color: '#1e293b', marginBottom: '12px' }}>
                No se encontraron estilos
              </h3>
              <p style={{ color: '#64748b', fontSize: '16px' }}>
                {searchTerm ? 'Intenta con otros términos de búsqueda' : 'No hay estilos disponibles en el backend'}
              </p>
            </div>
          )}
        </div>
      )}

      {/* Content - Flujo de Procesos */}
      {activeView === 'flow' && selectedEstilo && (
        <div style={{ height: '70vh', display: 'flex', gap: '20px' }}>
          {/* Panel lateral */}
          <div style={{
            width: '380px',
            background: 'white',
            borderRadius: '16px',
            border: '2px solid #e2e8f0',
            padding: '20px',
            overflowY: 'auto',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.05)',
          }}>
            <h2 style={{ fontSize: '20px', fontWeight: '700', marginBottom: '16px', color: '#1e293b' }}>
              🔀 Flujo: {selectedEstilo.nombre}
            </h2>

            {/* Acciones */}
            <div style={{ marginBottom: '20px', display: 'flex', gap: '8px' }}>
              <button
                onClick={crearNuevoFlujo}
                style={{
                  flex: 1,
                  background: '#10b981',
                  color: 'white',
                  padding: '10px 16px',
                  borderRadius: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: '14px',
                  fontWeight: '600',
                }}
              >
                ➕ Nuevo
              </button>
              <button
                onClick={guardarFlujo}
                disabled={guardandoFlujo || nodes.length === 0}
                style={{
                  flex: 1,
                  background: guardandoFlujo || nodes.length === 0 ? '#94a3b8' : '#3b82f6',
                  color: 'white',
                  padding: '10px 16px',
                  borderRadius: '8px',
                  border: 'none',
                  cursor: guardandoFlujo || nodes.length === 0 ? 'not-allowed' : 'pointer',
                  fontSize: '14px',
                  fontWeight: '600',
                }}
              >
                💾 {guardandoFlujo ? 'Guardando...' : 'Guardar'}
              </button>
            </div>

            {/* Lista de procesos disponibles */}
            <div>
              <h3 style={{ fontSize: '16px', fontWeight: '700', marginBottom: '12px', color: '#1e293b' }}>
                ⚙️ Procesos Disponibles
              </h3>
              <div style={{
                display: 'flex',
                flexDirection: 'column',
                gap: '8px',
                maxHeight: '400px',
                overflowY: 'auto'
              }}>
                {loadingProcesos ? (
                  <div style={{ textAlign: 'center', padding: '20px', color: '#64748b' }}>
                    Cargando procesos...
                  </div>
                ) : Array.isArray(procesos) && procesos.length > 0 ? procesos.map((proceso) => (
                  <div
                    key={proceso.id}
                    onClick={() => agregarProceso(proceso)}
                    style={{
                      padding: '12px',
                      border: '1px solid #e2e8f0',
                      borderRadius: '8px',
                      cursor: 'pointer',
                      background: 'white',
                      transition: 'all 0.2s',
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.background = '#f8fafc';
                      e.currentTarget.style.borderColor = '#3b82f6';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.background = 'white';
                      e.currentTarget.style.borderColor = '#e2e8f0';
                    }}
                  >
                    <div style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      marginBottom: '6px',
                    }}>
                      <div style={{
                        fontWeight: '600',
                        fontSize: '14px',
                        color: '#1e293b',
                      }}>
                        {proceso.nombre}
                      </div>
                      <div style={{
                        background: proceso.tipo_proceso?.color_hex || '#64748b',
                        padding: '2px 6px',
                        borderRadius: '4px',
                        fontSize: '10px',
                        fontWeight: '600',
                        textTransform: 'uppercase',
                        color: 'white',
                      }}>
                        {proceso?.tipo_proceso?.nombre || 'General'}
                      </div>
                    </div>
                    <div style={{ fontSize: '12px', color: '#64748b', marginBottom: '6px' }}>
                      {proceso.codigo}
                    </div>
                    <div style={{
                      display: 'flex',
                      justifyContent: 'space-between',
                      fontSize: '12px',
                      fontWeight: '600',
                    }}>
                      <span style={{ color: '#3b82f6' }}>⏱️ {proceso.tiempo_base_min}min</span>
                      <span style={{ color: '#059669' }}>${proceso.costo_base}</span>
                    </div>
                  </div>
                )) : (
                  <div style={{ textAlign: 'center', padding: '20px', color: '#64748b' }}>
                    No hay procesos disponibles
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Editor visual */}
          <div style={{
            flex: 1,
            background: 'white',
            borderRadius: '16px',
            border: '2px solid #e2e8f0',
            overflow: 'hidden',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.05)',
          }}>
            <ReactFlowProvider>
              <ReactFlowEditor
                nodes={nodes}
                edges={edges}
                onNodesChange={setNodes}
                onEdgesChange={setEdges}
                onConnect={onConnect}
                onNodePositionChange={handleNodePositionChange}
              />
              {/* Debug info */}
              {import.meta.env.DEV && (
                <div style={{
                  position: 'absolute',
                  bottom: '10px',
                  left: '10px',
                  background: 'rgba(0,0,0,0.7)',
                  color: 'white',
                  padding: '8px',
                  borderRadius: '4px',
                  fontSize: '12px'
                }}>
                  Nodos: {nodes.length} | Conexiones: {edges.length}
                </div>
              )}
            </ReactFlowProvider>
          </div>
        </div>
      )}

      {/* Content - BOM */}
      {activeView === 'bom' && selectedEstilo && (
        <div>
          <div style={{ marginBottom: '24px' }}>
            <h2 style={{
              fontSize: '28px',
              fontWeight: '700',
              color: '#1e293b',
              marginBottom: '8px'
            }}>
              📦 Lista de Materiales - {selectedEstilo.nombre}
            </h2>
            <p style={{ color: '#64748b', fontSize: '16px' }}>
              Lista completa de materiales requeridos para la producción
            </p>
          </div>

          {loadingBom ? (
            <div style={{ textAlign: 'center', padding: '64px', background: 'white', borderRadius: '16px' }}>
              <div style={{ fontSize: '48px', marginBottom: '16px' }}>⏳</div>
              <p style={{ color: '#64748b', fontSize: '16px' }}>Cargando lista de materiales...</p>
            </div>
          ) : bomData && bomData.bom_items && Array.isArray(bomData.bom_items) && bomData.bom_items.length > 0 ? (
            <>
              {/* Resumen */}
              <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
                gap: '20px',
                marginBottom: '24px'
              }}>
                <div style={{
                  background: 'linear-gradient(135deg, #dbeafe, #bfdbfe)',
                  padding: '24px',
                  borderRadius: '16px',
                  border: '1px solid #93c5fd',
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', marginBottom: '8px' }}>
                    <span style={{ marginRight: '12px', fontSize: '24px' }}>📦</span>
                    <span style={{ fontSize: '16px', fontWeight: '600', color: '#1e40af' }}>
                      Total de Materiales
                    </span>
                  </div>
                  <div style={{ fontSize: '32px', fontWeight: '800', color: '#1e40af' }}>
                    {bomData?.resumen?.total_items || bomData?.bom_items?.length || 0}
                  </div>
                </div>

                <div style={{
                  background: 'linear-gradient(135deg, #d1fae5, #a7f3d0)',
                  padding: '24px',
                  borderRadius: '16px',
                  border: '1px solid #6ee7b7',
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', marginBottom: '8px' }}>
                    <span style={{ marginRight: '12px', fontSize: '24px' }}>💰</span>
                    <span style={{ fontSize: '16px', fontWeight: '600', color: '#065f46' }}>
                      Costo Total Materiales
                    </span>
                  </div>
                  <div style={{ fontSize: '32px', fontWeight: '800', color: '#065f46' }}>
                    ${bomData?.resumen?.costo_total_materiales 
                      ? bomData.resumen.costo_total_materiales.toFixed(2)
                      : bomData?.bom_items?.reduce((total, item) => {
                          return total + ((item.cantidad_base || 0) * (item.material?.costo_unitario || 0));
                        }, 0).toFixed(2) || '0.00'
                    }
                  </div>
                </div>

                <div style={{
                  background: 'linear-gradient(135deg, #fce7f3, #fbcfe8)',
                  padding: '24px',
                  borderRadius: '16px',
                  border: '1px solid #f9a8d4',
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', marginBottom: '8px' }}>
                    <span style={{ marginRight: '12px', fontSize: '24px' }}>⚠️</span>
                    <span style={{ fontSize: '16px', fontWeight: '600', color: '#be185d' }}>
                      Materiales Críticos
                    </span>
                  </div>
                  <div style={{ fontSize: '32px', fontWeight: '800', color: '#be185d' }}>
                    {bomData?.resumen?.items_criticos || bomData?.bom_items?.filter(item => item.es_critico).length || 0}
                  </div>
                </div>
              </div>

              {/* Tabla BOM */}
              <div style={{
                background: 'white',
                borderRadius: '16px',
                overflow: 'hidden',
                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.05)',
                border: '2px solid #e2e8f0',
              }}>
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead style={{ background: '#f8fafc' }}>
                      <tr>
                        <th style={{
                          padding: '16px 24px',
                          textAlign: 'left',
                          fontSize: '14px',
                          fontWeight: '700',
                          color: '#374151',
                          textTransform: 'uppercase',
                          letterSpacing: '0.5px',
                          borderBottom: '2px solid #e2e8f0',
                        }}>
                          Material
                        </th>
                        <th style={{
                          padding: '16px 24px',
                          textAlign: 'left',
                          fontSize: '14px',
                          fontWeight: '700',
                          color: '#374151',
                          textTransform: 'uppercase',
                          letterSpacing: '0.5px',
                          borderBottom: '2px solid #e2e8f0',
                        }}>
                          Cantidad Base
                        </th>
                        <th style={{
                          padding: '16px 24px',
                          textAlign: 'left',
                          fontSize: '14px',
                          fontWeight: '700',
                          color: '#374151',
                          textTransform: 'uppercase',
                          letterSpacing: '0.5px',
                          borderBottom: '2px solid #e2e8f0',
                        }}>
                          Costo Total
                        </th>
                        <th style={{
                          padding: '16px 24px',
                          textAlign: 'left',
                          fontSize: '14px',
                          fontWeight: '700',
                          color: '#374151',
                          textTransform: 'uppercase',
                          letterSpacing: '0.5px',
                          borderBottom: '2px solid #e2e8f0',
                        }}>
                          Estado
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {(bomData.bom_items || []).map((item: BomItem, index: number) => (
                        <tr
                          key={item.id}
                          style={{
                            borderBottom: '1px solid #f1f5f9',
                            background: index % 2 === 0 ? 'white' : '#fafbfc',
                          }}
                        >
                          <td style={{ padding: '20px 24px' }}>
                            <div style={{ fontWeight: '600', fontSize: '14px', color: '#1e293b', marginBottom: '4px' }}>
                              {item.material.nombre}
                            </div>
                            <div style={{ fontSize: '12px', color: '#64748b' }}>
                              {item.material.codigo}
                            </div>
                          </td>
                          <td style={{ padding: '20px 24px', fontSize: '14px', color: '#1e293b', fontWeight: '600' }}>
                            {item.cantidad_base || 0} {item.material?.unidad_medida?.codigo || 'UM'}
                          </td>
                          <td style={{ padding: '20px 24px', fontSize: '16px', fontWeight: '700', color: '#059669' }}>
                            ${((item.cantidad_base || 0) * (item.material?.costo_unitario || 0)).toFixed(2)}
                          </td>
                          <td style={{ padding: '20px 24px' }}>
                            {item.es_critico ? (
                              <span style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                padding: '4px 12px',
                                fontSize: '12px',
                                fontWeight: 'bold',
                                borderRadius: '9999px',
                                background: '#fecaca',
                                color: '#dc2626',
                              }}>
                                ⚠️ Crítico
                              </span>
                            ) : (
                              <span style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                padding: '4px 12px',
                                fontSize: '12px',
                                fontWeight: 'bold',
                                borderRadius: '9999px',
                                background: '#d1fae5',
                                color: '#065f46',
                              }}>
                                ✅ Normal
                              </span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </>
          ) : (
            <div style={{
              textAlign: 'center',
              padding: '64px',
              background: 'white',
              borderRadius: '16px',
              border: '2px solid #e2e8f0',
            }}>
              <div style={{ fontSize: '64px', marginBottom: '20px' }}>📦</div>
              <h3 style={{
                fontSize: '24px',
                fontWeight: '600',
                color: '#1e293b',
                marginBottom: '12px'
              }}>
                Sin lista de materiales
              </h3>
              <p style={{ color: '#64748b', fontSize: '16px' }}>
                Este estilo no tiene materiales definidos en su BOM
              </p>
            </div>
          )}
        </div>
      )}

      {/* Content - Cálculo de Variantes */}
      {activeView === 'variantes' && selectedEstilo && (
        <VariantesCalculadora 
          estilo={selectedEstilo}
          colores={colores}
          tallas={tallas}
          loadingColores={loadingColores}
          loadingTallas={loadingTallas}
          calcularVarianteMutation={calcularVarianteMutation}
        />
      )}
    </div>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <SistemaTextilApp />
    </QueryClientProvider>
  );
}

export default App;

