import React from 'react';
import './App.css';

/**
 * COMPONENTE PRINCIPAL - SISTEMA UNIDEPORTES
 * DESCRIPCIÓN: Punto de entrada para el módulo de inventario de Unideportes.
 * ESTÁNDAR DE CODIFICACIÓN: Uso de Functional Components y Hooks.
 * AUTOR: Joel Abdias Castro Hernandez- Aprendiz: ADSO FICHA: 3118291
 */
function App() {
  return (
    <div className="App">
      <header className="App-header" style={{ backgroundColor: '#1A2B4C', padding: '20px', color: 'white' }}>
        <h1>Unideportes - Gestión de Fábrica y Tienda</h1>
        <p>Módulo: Control de Inventario y Ventas</p>
      </header>
      
      <main style={{ padding: '40px' }}>
        <div style={{ border: '1px solid #ddd', padding: '20px', borderRadius: '8px', backgroundColor: '#f9f9f9' }}>
          <h2>Bienvenido al Panel de Control</h2>
          <p>Estado del sistema: <strong>Conectado a Rama modulo-inventario</strong></p>
          <hr />
          <p>Próximo paso: Cargar Inventario de la tienda.</p>
        </div>
      </main>

      <footer style={{ marginTop: '50px', fontSize: '0.8em' }}>
        <p>&copy; 2026 Unideportes System - Evidencia GA7-220501096-AA4-EV03</p>
      </footer>
    </div>
  );
}

export default App;
