// Ruta: src/components/GestionProductos.jsx
import React, { useState } from 'react';

export default function GestionProductos() {
  const [producto, setProducto] = useState({ 
    nombre: '', 
    referencia: '', 
    precio: '', 
    stock: 0,
    categoria: '',
    talla: '',
    estado: 'activo'
  });
  const [mensaje, setMensaje] = useState('');

  const handleChange = (e) => {
    setProducto({ ...producto, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validación
    if (!producto.nombre || !producto.precio || !producto.referencia) {
      setMensaje("El nombre, referencia y precio son obligatorios.");
      return;
    }

    try {
      const response = await fetch('http://localhost/unideportes-system/controllers/ProductoController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(producto),
      });

      const data = await response.json();
      
      if (response.status === 201 || response.status === 200) {
        setMensaje(`¡Éxito!: ${data.message || 'Producto registrado correctamente'}`);
        setProducto({ 
          nombre: '', 
          referencia: '', 
          precio: '', 
          stock: 0,
          categoria: '',
          talla: '',
          estado: 'activo'
        });
      } else {
        setMensaje(`Error: ${data.message || 'Error en el servidor'}`);
      }
    } catch (error) {
      setMensaje("Error al conectar con el controlador PHP.");
      console.error(error);
    }
  };

  return (
    <div style={{ padding: '20px', maxWidth: '500px', backgroundColor: '#F0F2F5', borderRadius: '8px' }}>
      <h2 style={{ color: '#1A2B4C' }}>UniDeportes - Registro de Productos</h2>
      
      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '10px' }}>
          <label htmlFor="nombre">Nombre del Producto: *</label>
          <input 
            type="text" 
            id="nombre"
            name="nombre" 
            value={producto.nombre} 
            onChange={handleChange} 
            style={{ width: '100%', padding: '8px' }} 
          />
        </div>

        <div style={{ marginBottom: '10px' }}>
          <label htmlFor="referencia">Referencia: *</label>
          <input 
            type="text" 
            id="referencia"
            name="referencia" 
            value={producto.referencia} 
            onChange={handleChange} 
            placeholder="Ej: CAM-001"
            style={{ width: '100%', padding: '8px' }} 
          />
        </div>

        <div style={{ marginBottom: '10px' }}>
          <label htmlFor="precio">Precio ($): *</label>
          <input 
            type="number" 
            id="precio"
            name="precio" 
            value={producto.precio} 
            onChange={handleChange} 
            style={{ width: '100%', padding: '8px' }} 
          />
        </div>

        <div style={{ marginBottom: '10px' }}>
          <label htmlFor="stock">Stock Inicial:</label>
          <input 
            type="number" 
            id="stock"
            name="stock" 
            value={producto.stock} 
            onChange={handleChange} 
            style={{ width: '100%', padding: '8px' }} 
          />
        </div>

        <div style={{ marginBottom: '10px' }}>
          <label htmlFor="categoria">Categoría:</label>
          <input 
            type="text" 
            id="categoria"
            name="categoria" 
            value={producto.categoria} 
            onChange={handleChange} 
            placeholder="Ej: Camisetas"
            style={{ width: '100%', padding: '8px' }} 
          />
        </div>

        <div style={{ marginBottom: '10px' }}>
          <label htmlFor="talla">Talla:</label>
          <input 
            type="text" 
            id="talla"
            name="talla" 
            value={producto.talla} 
            onChange={handleChange} 
            placeholder="Ej: M, L, Única"
            style={{ width: '100%', padding: '8px' }} 
          />
        </div>

        <button 
          type="submit" 
          style={{ 
            backgroundColor: '#3C668F', 
            color: 'white', 
            border: 'none', 
            padding: '10px', 
            cursor: 'pointer', 
            width: '100%',
            marginTop: '10px'
          }}
        >
          Guardar Producto
        </button>
      </form>

      {mensaje && (
        <p style={{ 
          marginTop: '15px', 
          fontWeight: 'bold', 
          color: mensaje.includes('Éxito') ? '#28a745' : '#dc3545',
          padding: '10px',
          backgroundColor: '#fff',
          borderRadius: '4px'
        }}>
          {mensaje}
        </p>
      )}
    </div>
  );
}