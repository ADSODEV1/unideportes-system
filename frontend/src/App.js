// Ruta: src/App.js
import React from 'react';
import GestionProductos from './components/GestionProductos';

function App() {
  return (
    <div style={{ 
      display: 'flex', 
      justifyContent: 'center', 
      alignItems: 'center', 
      minHeight: '100vh', 
      backgroundColor: '#3C668F' // Usando la paleta azul del proyecto
    }}>
      <GestionProductos />
    </div>
  );
}

export default App;