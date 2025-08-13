import React, { useState } from 'react';
import { Handle, Position, type NodeProps } from '@xyflow/react';
import type { FlowNode } from '../types';
import { useSOPProceso } from '../hooks/useApi';

interface CustomNodeProps extends NodeProps {
    data: FlowNode['data'];
    selected: boolean;
}

const CustomNode: React.FC<CustomNodeProps> = ({ data, selected }) => {
    const bgColor = data.color_tipo || data.tipo_proceso?.color_hex || '#f3f4f6';
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [showInsumosModal, setShowInsumosModal] = useState(false);

    // Cargar SOP del proceso solo si tenemos un id_proceso válido
    const { 
        data: sopData, 
        isLoading: cargandoSOP,
        error: errorSOP 
    } = useSOPProceso(data.id_proceso);

    console.log("sopData", sopData);


    const handleVerDetalle = (e: React.MouseEvent) => {
        e.stopPropagation();
        setShowDetailModal(true);
    };

    const handleVerInsumos = (e: React.MouseEvent) => {
        e.stopPropagation();
        setShowInsumosModal(true);
    };

    return (
        <>
            <div
                style={{
                    background: 'white',
                    border: `3px solid ${bgColor}`,
                    borderRadius: '12px',
                    padding: '0',
                    minWidth: '240px',
                    boxShadow: selected ? '0 8px 25px rgba(59, 130, 246, 0.3)' : '0 4px 12px rgba(0, 0, 0, 0.15)',
                    position: 'relative',
                }}
            >
                <Handle
                    type="target"
                    position={Position.Left}
                    style={{
                        background: bgColor,
                        border: '2px solid white',
                        width: '16px',
                        height: '16px',
                    }}
                />

                {/* Indicadores de estado */}
                <div style={{
                    position: 'absolute',
                    top: '4px',
                    left: '8px',
                    display: 'flex',
                    gap: '4px',
                    zIndex: 5,
                }}>
                    {data.es_punto_inicio && (
                        <span style={{
                            background: '#10b981',
                            color: 'white',
                            fontSize: '8px',
                            fontWeight: '600',
                            padding: '2px 4px',
                            borderRadius: '4px',
                            textTransform: 'uppercase',
                        }}>
                            Inicio
                        </span>
                    )}
                    {data.es_punto_final && (
                        <span style={{
                            background: '#ef4444',
                            color: 'white',
                            fontSize: '8px',
                            fontWeight: '600',
                            padding: '2px 4px',
                            borderRadius: '4px',
                            textTransform: 'uppercase',
                        }}>
                            Final
                        </span>
                    )}
                    {data.tiene_personalizaciones && (
                        <span style={{
                            background: '#f59e0b',
                            color: 'white',
                            fontSize: '8px',
                            fontWeight: '600',
                            padding: '2px 4px',
                            borderRadius: '4px',
                        }}>
                            ⚙️
                        </span>
                    )}
                </div>

                {/* Botones de acción */}
                <div style={{
                    position: 'absolute',
                    top: '8px',
                    right: '8px',
                    display: 'flex',
                    gap: '4px',
                    zIndex: 10,
                }}>
                    <button
                        onClick={handleVerDetalle}
                        disabled={cargandoSOP}
                        style={{
                            background: cargandoSOP ? '#94a3b8' : '#3b82f6',
                            color: 'white',
                            border: 'none',
                            borderRadius: '4px',
                            padding: '4px 8px',
                            fontSize: '10px',
                            cursor: cargandoSOP ? 'wait' : 'pointer',
                            fontWeight: '600',
                        }}
                        title="Ver Detalle del Proceso"
                    >
                        👁️ {cargandoSOP ? '...' : 'SOP'}
                    </button>
                    <button
                        onClick={handleVerInsumos}
                        style={{
                            background: '#10b981',
                            color: 'white',
                            border: 'none',
                            borderRadius: '4px',
                            padding: '4px 8px',
                            fontSize: '10px',
                            cursor: 'pointer',
                            fontWeight: '600',
                        }}
                        title="Ver Insumos y Productos"
                    >
                        📦 I/O
                    </button>
                </div>

                {/* Contenido del nodo */}
                <div style={{
                    paddingLeft: '20px',
                    paddingRight: '20px',
                    paddingTop: '32px',
                    paddingBottom: '15px',
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', marginBottom: '8px' }}>
                        <div style={{
                            width: '40px',
                            height: '40px',
                            borderRadius: '8px',
                            background: bgColor,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            marginRight: '12px',
                            fontSize: '18px',
                        }}>
                            ⚙️
                        </div>
                        <div>
                            <div style={{
                                fontWeight: '700',
                                fontSize: '14px',
                                color: '#1e293b',
                                lineHeight: '1.2',
                            }}>
                                {data.nombre}
                            </div>
                            <div style={{
                                fontSize: '11px',
                                color: '#64748b',
                                fontWeight: '500',
                            }}>
                                {data.tipo || data.tipo_proceso?.nombre || 'General'}
                            </div>
                        </div>
                    </div>

                    {data.descripcion && (
                        <div style={{
                            fontSize: '12px',
                            color: '#64748b',
                            marginBottom: '12px',
                            lineHeight: '1.3',
                            overflow: 'hidden',
                            display: '-webkit-box',
                            WebkitLineClamp: 2,
                            WebkitBoxOrient: 'vertical',
                        }}>
                            {data.descripcion}
                        </div>
                    )}

                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '8px',
                        marginBottom: '8px',
                    }}>
                        <div style={{ textAlign: 'center' }}>
                            <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '600' }}>TIEMPO</div>
                            <div style={{ fontSize: '13px', fontWeight: '700', color: '#3b82f6' }}>
                                {data.tiempo_efectivo || data.tiempo_base_min || 0}min
                            </div>
                            {data.merma_porcentaje > 0 && (
                                <div style={{ fontSize: '9px', color: '#f59e0b' }}>
                                    +{data.merma_porcentaje}% merma
                                </div>
                            )}
                        </div>
                        <div style={{ textAlign: 'center' }}>
                            <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '600' }}>COSTO</div>
                            <div style={{ fontSize: '13px', fontWeight: '700', color: '#10b981' }}>
                                ${(data.costo_efectivo || data.costo_base || 0).toFixed(2)}
                            </div>
                            {data.tiene_personalizaciones && (
                                <div style={{ fontSize: '9px', color: '#f59e0b' }}>
                                    Personalizado
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Indicadores especiales */}
                    <div style={{ display: 'flex', gap: '4px', flexWrap: 'wrap' }}>
                        {data.es_paralelo && (
                            <div style={{
                                background: '#fef3c7',
                                color: '#92400e',
                                padding: '2px 6px',
                                borderRadius: '6px',
                                fontSize: '9px',
                                fontWeight: '600',
                                textTransform: 'uppercase',
                            }}>
                                ⚡ Paralelo
                            </div>
                        )}
                        {data.es_opcional && (
                            <div style={{
                                background: '#e0e7ff',
                                color: '#4338ca',
                                padding: '2px 6px',
                                borderRadius: '6px',
                                fontSize: '9px',
                                fontWeight: '600',
                                textTransform: 'uppercase',
                            }}>
                                ? Opcional
                            </div>
                        )}
                        {data.requiere_color && (
                            <div style={{
                                background: '#fce7f3',
                                color: '#be185d',
                                padding: '2px 6px',
                                borderRadius: '6px',
                                fontSize: '9px',
                                fontWeight: '600',
                                textTransform: 'uppercase',
                            }}>
                                🎨 Color
                            </div>
                        )}
                    </div>
                </div>

                <Handle
                    type="source"
                    position={Position.Right}
                    style={{
                        background: bgColor,
                        border: '2px solid white',
                        width: '16px',
                        height: '16px',
                    }}
                />
            </div>

            {/* Modal de detalle SOP */}
            {showDetailModal && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0, 0, 0, 0.5)',
                    zIndex: 1000,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                }}>
                    <div style={{
                        background: 'white',
                        borderRadius: '16px',
                        padding: '24px',
                        maxWidth: '600px',
                        width: '90%',
                        maxHeight: '80vh',
                        overflowY: 'auto',
                        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                            <h3 style={{ margin: 0, color: '#1e293b', fontSize: '20px', fontWeight: '700' }}>
                                📋 SOP - {data.nombre}
                            </h3>
                            <button
                                onClick={() => setShowDetailModal(false)}
                                style={{
                                    background: 'none',
                                    border: 'none',
                                    fontSize: '24px',
                                    cursor: 'pointer',
                                    color: '#64748b',
                                    padding: '4px',
                                }}
                            >
                                ×
                            </button>
                        </div>

                        <div style={{ marginBottom: '16px' }}>
                            <h4 style={{ color: '#1e293b', marginBottom: '8px', fontSize: '16px' }}>
                                {data.codigo} - {data.nombre}
                            </h4>
                            <p style={{ color: '#64748b', lineHeight: '1.5', fontSize: '14px' }}>
                                {data.descripcion}
                            </p>
                        </div>

                        {cargandoSOP && (
                            <div style={{ textAlign: 'center', padding: '20px' }}>
                                <div style={{ color: '#64748b' }}>Cargando SOP del proceso...</div>
                            </div>
                        )}

                        {errorSOP && (
                            <div style={{ 
                                padding: '12px', 
                                background: '#fef2f2', 
                                borderRadius: '8px', 
                                color: '#dc2626',
                                marginBottom: '16px'
                            }}>
                                Error al cargar el SOP: {errorSOP.message}
                            </div>
                        )}

                        {sopData && !cargandoSOP && (
                            <div style={{ marginBottom: '16px' }}>
                                <h4 style={{ color: '#1e293b', marginBottom: '8px', fontSize: '14px', fontWeight: '600' }}>
                                    📖 Procedimiento Operativo Estándar
                                </h4>
                                <div style={{ 
                                    padding: '16px', 
                                    background: '#f8fafc', 
                                    borderRadius: '8px', 
                                    lineHeight: '1.6',
                                    whiteSpace: 'pre-wrap',
                                    fontSize: '13px',
                                    color: '#374151',
                                    border: '1px solid #e2e8f0'
                                }}>
                                    {sopData.sop || 'No hay SOP definido para este proceso'}
                                </div>
                            </div>
                        )}

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '16px' }}>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#64748b', textTransform: 'uppercase' }}>
                                    Tipo de Proceso
                                </label>
                                <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '8px', marginTop: '4px', fontSize: '14px' }}>
                                    {data.tipo}
                                </div>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#64748b', textTransform: 'uppercase' }}>
                                    Orden en Flujo
                                </label>
                                <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '8px', marginTop: '4px', fontSize: '14px' }}>
                                    #{data.orden_secuencia}
                                </div>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#64748b', textTransform: 'uppercase' }}>
                                    Tiempo Efectivo
                                </label>
                                <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '8px', marginTop: '4px', fontSize: '14px' }}>
                                    {data.tiempo_efectivo || data.tiempo_base_min || 0} minutos
                                    {data.merma_porcentaje > 0 && (
                                        <span style={{ color: '#f59e0b', fontSize: '12px' }}>
                                            {' '}(+{data.merma_porcentaje}% merma)
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#64748b', textTransform: 'uppercase' }}>
                                    Costo Efectivo
                                </label>
                                <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '8px', marginTop: '4px', fontSize: '14px' }}>
                                    ${(data.costo_efectivo || data.costo_base || 0).toFixed(2)}
                                    {data.tiene_personalizaciones && (
                                        <span style={{ color: '#f59e0b', fontSize: '12px' }}>
                                            {' '}(personalizado)
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {data.notas && (
                            <div style={{ marginBottom: '16px' }}>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#64748b', textTransform: 'uppercase' }}>
                                    Notas
                                </label>
                                <div style={{ 
                                    padding: '12px', 
                                    background: '#fffbeb', 
                                    borderRadius: '8px', 
                                    marginTop: '4px',
                                    borderLeft: '4px solid #f59e0b',
                                    fontSize: '14px',
                                    lineHeight: '1.5'
                                }}>
                                    {data.notas}
                                </div>
                            </div>
                        )}

                        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                            <button
                                onClick={() => setShowDetailModal(false)}
                                style={{
                                    background: '#3b82f6',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '8px',
                                    padding: '10px 20px',
                                    cursor: 'pointer',
                                    fontWeight: '600',
                                    fontSize: '14px',
                                }}
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal de insumos */}
            {showInsumosModal && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0, 0, 0, 0.5)',
                    zIndex: 1000,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                }}>
                    <div style={{
                        background: 'white',
                        borderRadius: '16px',
                        padding: '24px',
                        maxWidth: '600px',
                        width: '90%',
                        maxHeight: '80vh',
                        overflowY: 'auto',
                        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                            <h3 style={{ margin: 0, color: '#1e293b', fontSize: '20px', fontWeight: '700' }}>
                                📦 Inputs/Outputs - {data.nombre}
                            </h3>
                            <button
                                onClick={() => setShowInsumosModal(false)}
                                style={{
                                    background: 'none',
                                    border: 'none',
                                    fontSize: '24px',
                                    cursor: 'pointer',
                                    color: '#64748b',
                                    padding: '4px',
                                }}
                            >
                                ×
                            </button>
                        </div>

                        <div style={{ marginBottom: '16px' }}>
                            <h4 style={{ color: '#1e293b', marginBottom: '8px', fontSize: '16px' }}>
                                Proceso: {data.nombre}
                            </h4>
                        </div>

                        <div style={{ marginBottom: '20px' }}>
                            <h5 style={{ color: '#1e293b', marginBottom: '8px', fontSize: '14px', fontWeight: '600' }}>
                                📥 Insumos de Entrada
                            </h5>
                            {cargandoSOP ? (
                                <div style={{ 
                                    padding: '12px', 
                                    background: '#f0fdf4', 
                                    borderRadius: '8px', 
                                    border: '1px solid #bbf7d0',
                                    color: '#166534',
                                    fontSize: '13px',
                                    textAlign: 'center'
                                }}>
                                    Cargando insumos...
                                </div>
                            ) : sopData?.inputs && sopData.inputs.length > 0 ? (
                                <ul style={{ 
                                    padding: '12px 20px', 
                                    background: '#f0fdf4', 
                                    borderRadius: '8px', 
                                    margin: 0,
                                    border: '1px solid #bbf7d0'
                                }}>
                                    {sopData.inputs.map((inp: string, i: number) => (
                                        <li key={i} style={{ 
                                            fontSize: '13px', 
                                            lineHeight: '1.5',
                                            color: '#166534',
                                            marginBottom: '4px'
                                        }}>
                                            {inp}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div style={{ 
                                    padding: '12px', 
                                    background: '#f0fdf4', 
                                    borderRadius: '8px', 
                                    borderLeft: '4px solid #22c55e',
                                    color: '#166534',
                                    fontSize: '14px'
                                }}>
                                    No hay insumos de entrada registrados.
                                </div>
                            )}
                        </div>

                        <div style={{ marginBottom: '20px' }}>
                            <h5 style={{ color: '#1e293b', marginBottom: '8px', fontSize: '14px', fontWeight: '600' }}>
                                📤 Productos de Salida
                            </h5>
                            {cargandoSOP ? (
                                <div style={{ 
                                    padding: '12px', 
                                    background: '#fef3c7', 
                                    borderRadius: '8px', 
                                    border: '1px solid #fde68a',
                                    color: '#92400e',
                                    fontSize: '13px',
                                    textAlign: 'center'
                                }}>
                                    Cargando productos...
                                </div>
                            ) : sopData?.outputs && sopData.outputs.length > 0 ? (
                                <ul style={{ 
                                    padding: '12px 20px', 
                                    background: '#fef3c7', 
                                    borderRadius: '8px', 
                                    margin: 0,
                                    border: '1px solid #fde68a'
                                }}>
                                    {sopData.outputs.map((out: string, i: number) => (
                                        <li key={i} style={{ 
                                            fontSize: '13px', 
                                            lineHeight: '1.5',
                                            color: '#92400e',
                                            marginBottom: '4px'
                                        }}>
                                            {out}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div style={{ 
                                    padding: '12px', 
                                    background: '#fef3c7', 
                                    borderRadius: '8px', 
                                    borderLeft: '4px solid #f59e0b',
                                    color: '#92400e',
                                    fontSize: '14px'
                                }}>
                                    No hay productos de salida registrados.
                                </div>
                            )}
                        </div>

                        <div style={{
                            background: '#f8fafc',
                            padding: '20px',
                            borderRadius: '12px',
                            textAlign: 'center',
                            border: '1px solid #e2e8f0'
                        }}>
                            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📦</div>
                            <h4 style={{ color: '#1e293b', marginBottom: '8px', fontSize: '16px' }}>
                                Configuración desde Backend
                            </h4>
                            <p style={{ color: '#64748b', fontSize: '14px', margin: 0 }}>
                                Los inputs y outputs específicos de este proceso se configuran desde el sistema de gestión.
                                Contacta al administrador para modificar estas configuraciones.
                            </p>
                        </div>

                        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end', marginTop: '20px' }}>
                            <button
                                onClick={() => setShowInsumosModal(false)}
                                style={{
                                    background: '#10b981',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '8px',
                                    padding: '10px 20px',
                                    cursor: 'pointer',
                                    fontWeight: '600',
                                    fontSize: '14px',
                                }}
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
};

export default CustomNode;
