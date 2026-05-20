// Ruta: src/components/GestionProductos.jsx
import React, { useState } from 'react';

export default function GestionProductos() {
  const [producto, setProducto] = useState({ nombre_producto: '', precio: '', stock: 0 });
  const [mensaje, setMensaje] = useState('');

  const handleChange = (e) => {
    setProducto({ ...producto, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validación minimalista antes del envío
    if (!producto.nombre_producto || !producto.precio) {
      setMensaje("El nombre y el precio son obligatorios.");
      return;
    }

    try {
      const response = await fetch('http://localhost/unideportes-system/controllers/ProductoController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(producto),
      });

      const data = await response.json();
      
      if (response.status === 201) {
        setMensaje(`¡Éxito!: ${data.message}`);
        setProducto({ nombre_producto: '', precio: '', stock: 0 }); // Limpiar formulario
      } else {
        setMensaje(`Error en el servidor: ${data.message}`);
      }
    } catch (error) {
      setMensaje("Error al conectar con el controlador PHP.");
    }
  };

  return (
    <div style={{ padding: '20px', maxWidth: '400px', backgroundColor: '#F0F2F5', borderRadius: '8px' }}>
      <h2 style={{ color: '#1A2B4C' }}>UniDeportes MVP - Registro</h2>
      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '10px' }}>
          <label>Nombre del Producto:</label>
          <input type="text" name="nombre_producto" value={producto.nombre_producto} onChange={handleChange} style={{ width: '100%' }} />
        </div>
        <div style={{ marginBottom: '10px' }}>
          <label>Precio ($):</label>
          <input type="number" name="precio" value={producto.precio} onChange={handleChange} style={{ width: '100%' }} />
        </div>
        <div style={{ marginBottom: '10px' }}>
          <label>Stock Inicial:</label>
          <input type="number" name="stock" value={producto.stock} onChange={handleChange} style={{ width: '100%' }} />
        </div>
        <button type="submit" style={{ backgroundColor: '#3C668F', color: 'white', border: 'none', padding: '10px w-100', cursor: 'pointer', width: '100%' }}>
          Guardar e Integrar
        </button>
      </form>
      {mensaje && <p style={{ marginTop: '15px', fontWeight: 'bold', color: '#1A2B4C' }}>{mensaje}</p>}
    </div>
  );
}