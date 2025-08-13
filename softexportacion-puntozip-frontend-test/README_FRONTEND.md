# Sistema de Gestión Textil - Frontend React

## 📋 Descripción

Frontend moderno desarrollado en React con TypeScript para el Sistema de Gestión Textil. Integra completamente con el backend Laravel y proporciona una interfaz intuitiva para gestionar estilos, procesos, materiales y cálculos de variantes textiles.

## ✨ Características Principales

### 🎨 Interfaz de Usuario Moderna
- **Diseño Responsivo**: Adaptable a diferentes tamaños de pantalla
- **UI/UX Intuitiva**: Navegación clara y flujo de trabajo optimizado
- **Componentes Modulares**: Arquitectura escalable y mantenible

### 📊 Gestión de Estilos
- **Catálogo Visual**: Vista de tarjetas con información detallada
- **Búsqueda Avanzada**: Filtrado por nombre y código
- **Estados de Estilos**: Desarrollo, activo, descontinuado
- **Tipos de Producto**: Polo, camisa, pantalón, vestido, otro

### 🔀 Flujo de Procesos Interactivo
- **ReactFlow Integration**: Editor visual de flujos de procesos
- **Nodos Personalizados**: Información detallada de cada proceso
- **Conexiones Dinámicas**: Creación visual de secuencias de procesos
- **Posicionamiento Inteligente**: Arrastre y soltura de nodos
- **Procesos Opcionales**: Gestión de procesos condicionales

### 📦 Gestión de Materiales (BOM)
- **Lista de Materiales**: Vista completa del Bill of Materials
- **Materiales Críticos**: Identificación de componentes críticos
- **Cálculo de Costos**: Costos totales por material
- **Tipos de Material**: Hilos, tintes, avíos, empaques

### 🧮 Cálculos de Variantes Textiles
- **Calculadora Avanzada**: Cálculos específicos por color y talla
- **Multiplicadores de Talla**: Aplicación automática de factores
- **Procesos por Color**: Inclusión condicional según color seleccionado
- **Resultados Detallados**: Desglose completo de costos y tiempos

## 🛠️ Tecnologías Utilizadas

- **React 19**: Framework principal
- **TypeScript**: Tipado estático
- **@xyflow/react**: Editor de flujos visuales
- **Vite**: Build tool y desarrollo
- **CSS-in-JS**: Estilos inline para mejor rendimiento

## 📁 Estructura del Proyecto

```
src/
├── components/
│   ├── CustomNode.tsx          # Nodo personalizado para ReactFlow
│   └── CalculoVariante.tsx     # Componente de cálculos de variantes
├── services/
│   └── api.ts                  # Servicio de integración con backend
├── types.ts                    # Definiciones de tipos TypeScript
├── SistemaTextilApp.tsx        # Componente principal
├── App.tsx                     # Punto de entrada
└── main.tsx                    # Renderizado de la aplicación
```

## 🚀 Instalación y Configuración

### Prerrequisitos
- Node.js 18+ 
- npm o yarn
- Backend Laravel ejecutándose en `http://localhost:8002`

### Instalación

1. **Clonar el repositorio**:
```bash
cd softexportacion-puntozip-frontend-test
```

2. **Instalar dependencias**:
```bash
npm install
# o
yarn install
```

3. **Configurar variables de entorno**:
Crear archivo `.env` en la raíz del proyecto:
```env
VITE_API_BASE_URL=http://localhost:8002/api/v1
```

4. **Ejecutar en modo desarrollo**:
```bash
npm run dev
# o
yarn dev
```

5. **Abrir en el navegador**:
```
http://localhost:5173
```

## 🔧 Configuración del Backend

Asegúrate de que el backend Laravel esté configurado correctamente:

1. **CORS habilitado** en `config/cors.php`:
```php
'allowed_origins' => ['http://localhost:5173'],
```

2. **Servidor ejecutándose**:
```bash
php artisan serve --host=0.0.0.0 --port=8002
```

## 📖 Uso del Sistema

### 1. Navegación Principal
- **Catálogo de Estilos**: Vista principal con todos los estilos disponibles
- **Flujo de Procesos**: Editor visual para crear flujos de producción
- **Lista de Materiales**: Gestión del BOM de cada estilo
- **Cálculos de Variantes**: Calculadora para variantes específicas

### 2. Gestión de Estilos
1. Selecciona un estilo del catálogo
2. Accede a las diferentes vistas disponibles
3. Utiliza la búsqueda para filtrar estilos

### 3. Creación de Flujos de Procesos
1. Ve a la vista "Flujo Interactivo de Procesos"
2. Arrastra procesos desde el panel lateral
3. Conecta los procesos arrastrando entre nodos
4. Guarda el flujo cuando esté completo

### 4. Cálculos de Variantes
1. Selecciona un estilo
2. Ve a "Cálculos de Variantes"
3. Configura color, talla y cantidad
4. Ejecuta el cálculo
5. Revisa los resultados detallados

## 🔌 Integración con APIs

### Endpoints Principales

- **Estilos**: `/api/v1/estilos`
- **Materiales**: `/api/v1/materiales`
- **Procesos**: `/api/v1/procesos`
- **Flujos**: `/api/v1/estilos/{id}/flujos`
- **BOM**: `/api/v1/estilos/{id}/bom`
- **Cálculos**: `/api/v1/estilos/{id}/calcular-variante-textil`

### Manejo de Errores
- **Fallbacks**: Datos mock cuando el servidor no está disponible
- **Mensajes de Error**: Información clara sobre problemas de conexión
- **Retry Logic**: Reintentos automáticos en operaciones críticas

## 🎨 Personalización

### Estilos y Temas
Los estilos están definidos inline para mejor rendimiento. Para personalizar:

1. **Colores**: Modifica las variables de color en los componentes
2. **Tipografías**: Cambia las fuentes en los estilos inline
3. **Layout**: Ajusta el grid y espaciado según necesidades

### Componentes Personalizables
- `CustomNode`: Nodo personalizado para ReactFlow
- `CalculoVariante`: Modal de cálculos
- `SistemaTextilApp`: Componente principal

## 🧪 Testing

### Ejecutar Tests
```bash
npm run test
# o
yarn test
```

### Testing de Integración
```bash
npm run test:integration
```

## 📦 Build para Producción

### Generar Build
```bash
npm run build
# o
yarn build
```

### Preview del Build
```bash
npm run preview
# o
yarn preview
```

## 🔍 Debugging

### Herramientas de Desarrollo
- **React DevTools**: Para inspeccionar componentes
- **Network Tab**: Para monitorear llamadas a la API
- **Console**: Logs detallados de operaciones

### Logs Importantes
- Carga de datos iniciales
- Errores de API
- Operaciones de flujo
- Cálculos de variantes

## 🚨 Solución de Problemas

### Problemas Comunes

1. **Error de CORS**:
   - Verificar configuración CORS en backend
   - Asegurar que las URLs coincidan

2. **API no responde**:
   - Verificar que el backend esté ejecutándose
   - Revisar logs del servidor Laravel

3. **ReactFlow no funciona**:
   - Verificar que @xyflow/react esté instalado
   - Revisar la configuración de nodeTypes

4. **Cálculos incorrectos**:
   - Verificar datos en la base de datos
   - Revisar lógica de multiplicadores

### Logs de Debug
```javascript
// Habilitar logs detallados
localStorage.setItem('debug', 'true');
```

## 📈 Rendimiento

### Optimizaciones Implementadas
- **Lazy Loading**: Carga de componentes bajo demanda
- **Memoización**: Uso de useCallback y useMemo
- **Virtualización**: Para listas grandes
- **Code Splitting**: Separación de bundles

### Métricas de Rendimiento
- **First Contentful Paint**: < 1.5s
- **Time to Interactive**: < 3s
- **Bundle Size**: < 500KB

## 🔒 Seguridad

### Medidas Implementadas
- **Validación de Inputs**: Sanitización de datos
- **CORS**: Configuración segura
- **TypeScript**: Tipado estático para prevenir errores
- **Error Boundaries**: Manejo seguro de errores

## 🤝 Contribución

### Guías de Desarrollo
1. **Código Limpio**: Seguir estándares de TypeScript
2. **Componentes Modulares**: Mantener componentes pequeños
3. **Documentación**: Comentar código complejo
4. **Testing**: Escribir tests para nuevas funcionalidades

### Flujo de Trabajo
1. Crear rama feature
2. Desarrollar funcionalidad
3. Escribir tests
4. Crear pull request
5. Code review
6. Merge a main

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver archivo LICENSE para más detalles.

## 📞 Soporte

Para soporte técnico o preguntas:
- **Issues**: Crear issue en el repositorio
- **Documentación**: Revisar README del backend
- **API Docs**: Consultar documentación de APIs

---

**Desarrollado con ❤️ para la industria textil**
