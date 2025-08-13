# 🧵 Sistema de Gestión Textil - Frontend

Frontend desarrollado en React + TypeScript con TanStack Query y @xyflow/react para la gestión integral de estilos, procesos y materiales textiles.

## 🚀 Características

- **📋 Gestión de Estilos**: Catálogo completo de estilos con información detallada
- **🔀 Flujo de Procesos**: Editor visual interactivo usando @xyflow/react
- **📦 BOM (Bill of Materials)**: Lista de materiales con cálculos automáticos
- **🧮 Calculadora de Variantes**: Cálculo de costos y tiempos por variante
- **⚡ React Query**: Gestión eficiente del estado del servidor
- **🎨 UI Moderna**: Interfaz limpia y responsive

## 🛠️ Tecnologías

- **React 19** - Framework principal
- **TypeScript** - Tipado estático
- **TanStack Query v5** - Gestión del estado del servidor
- **@xyflow/react** - Editor de flujos interactivo
- **Axios** - Cliente HTTP
- **Vite** - Build tool y dev server

## 📦 Instalación

### Prerrequisitos

- Node.js 18+
- npm o yarn
- Backend Laravel corriendo en `http://localhost:8002`

### Pasos de instalación

1. **Instalar dependencias**
   ```bash
   cd softexportacion-puntozip-frontend-test
   npm install
   ```

2. **Configurar variables de entorno**
   
   Crear archivo `.env` en la raíz del proyecto:
   ```bash
   # URL base del API backend Laravel
   VITE_API_URL=http://localhost:8002/api/v1
   
   # Configuraciones de desarrollo
   VITE_NODE_ENV=development
   
   # Configuraciones de React Query
   VITE_REACT_QUERY_DEVTOOLS=true
   
   # Configuraciones adicionales
   VITE_APP_NAME="Sistema de Gestión Textil"
   VITE_APP_VERSION=1.0.0
   ```

3. **Ejecutar en modo desarrollo**
   ```bash
   npm run dev
   ```

4. **Acceder a la aplicación**
   ```
   http://localhost:5173
   ```

## 🗂️ Estructura del proyecto

```
src/
├── components/           # Componentes reutilizables
│   └── CustomNode.tsx   # Nodo personalizado para ReactFlow
├── hooks/               # Custom hooks
│   └── useApi.ts       # Hooks de TanStack Query
├── services/           # Servicios externos
│   └── api.ts         # Cliente API con Axios
├── types/             # Definiciones de tipos
│   └── index.ts       # Tipos TypeScript principales
├── App.tsx           # Componente principal
└── main.tsx         # Punto de entrada
```

## 🔌 Integración con Backend

El frontend se conecta al backend Laravel a través de las siguientes APIs:

### Endpoints principales

- **Estilos**: `/api/v1/estilos`
- **Materiales**: `/api/v1/materiales`
- **Procesos**: `/api/v1/procesos`
- **Colores**: `/api/v1/colores`
- **Tallas**: `/api/v1/tallas`
- **BOM**: `/api/v1/estilos/{id}/bom`
- **Flujos**: `/api/v1/estilos/{id}/flujos`
- **Cálculos**: `/api/v1/estilos/{id}/calcular-variante-textil`

### Configuración de Axios

```typescript
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8002/api/v1';

const apiClient = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 10000,
});
```

## 📱 Funcionalidades

### 1. Catálogo de Estilos

- Lista completa de estilos con filtrado
- Información detallada (código, temporada, costos)
- Estados: desarrollo, activo, descontinuado

### 2. Editor de Flujo de Procesos

- **Drag & Drop**: Agregar procesos al flujo
- **Conexiones visuales**: Conectar procesos secuencialmente
- **Nodos personalizados**: Información detallada de cada proceso
- **Persistencia**: Guardar posiciones en base de datos
- **SOP**: Visualización de procedimientos operativos

### 3. Lista de Materiales (BOM)

- **Resumen visual**: Total de materiales, costos y críticos
- **Tabla detallada**: Cantidades, costos unitarios y totales
- **Alertas**: Identificación de materiales críticos

### 4. Calculadora de Variantes

- **Selección múltiple**: Color, talla, cantidad
- **Cálculo en tiempo real**: Costos y tiempos
- **Resultados detallados**: Desglose por materiales y procesos

## 🎯 Hooks Personalizados

### Hooks de datos

```typescript
// Estilos
const { data: estilos, isLoading } = useEstilos();
const createEstiloMutation = useCreateEstilo();

// Materiales
const { data: materiales } = useMateriales();
const { data: materialesCriticos } = useMaterialesCriticos();

// Procesos
const { data: procesos } = useProcesosParaReactFlow();

// BOM
const { data: bomData } = useBOMEstilo(estiloId);

// Flujos
const { data: flujoData } = useFlujo(estiloId, flujoId);
const guardarFlujoMutation = useGuardarFlujo();

// Cálculos
const calcularVarianteMutation = useCalcularVarianteTextil();
```

## 🔧 Configuración de TanStack Query

```typescript
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 2,
            refetchOnWindowFocus: false,
            staleTime: 5 * 60 * 1000, // 5 minutos
        },
    },
});
```

## 🎨 Componentes Principales

### CustomNode (ReactFlow)

Componente personalizado para representar procesos en el flujo:

- **Información visual**: Nombre, tipo, costos, tiempos
- **Indicadores**: Proceso paralelo, opcional, crítico
- **Modales**: SOP detallado e inputs/outputs
- **Interactividad**: Drag, conexiones, selección

### ReactFlowEditor

Editor principal para flujos de procesos:

- **Configuración**: Tipos de nodos, conexiones, controles
- **Eventos**: Cambios de posición, nuevas conexiones
- **Sincronización**: Backend para persistencia

## 🔄 Gestión del Estado

### TanStack Query

- **Cache inteligente**: Datos frescos y eficientes
- **Invalidación automática**: Actualizaciones tras mutaciones
- **Estados de carga**: Loading, error, success
- **Optimistic updates**: Mejora la UX

### Estados locales

- **Navegación**: Vista activa (list, flow, bom, calculator)
- **Selección**: Estilo, flujo, variante actual
- **Formularios**: Filtros, búsquedas, configuraciones

## 🚀 Scripts disponibles

```bash
# Desarrollo
npm run dev

# Build de producción
npm run build

# Preview del build
npm run preview

# Linting
npm run lint
```

## 🔍 Troubleshooting

### Error de conexión con backend

1. Verificar que el backend Laravel esté corriendo en puerto 8002
2. Confirmar variables de entorno en `.env`
3. Revisar CORS en el backend
4. Verificar rutas de API en `routes/api.php`

### Problemas con ReactFlow

1. Verificar importación de estilos: `import '@xyflow/react/dist/style.css'`
2. Comprobar estructura de nodos y edges
3. Validar que los IDs sean únicos

### Errores de TypeScript

1. Verificar tipos en `src/types/index.ts`
2. Confirmar importaciones como `type-only`
3. Revisar configuración en `tsconfig.json`

## 📄 Licencia

Este proyecto es parte del sistema de gestión textil y está destinado para uso interno de la empresa.

## 🤝 Contribución

Para contribuir al proyecto:

1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📞 Soporte

Para soporte técnico o consultas sobre el proyecto, contactar al equipo de desarrollo.