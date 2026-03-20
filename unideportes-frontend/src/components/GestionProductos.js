import React, { useState } from 'react';

function GestionProductos() {
  // 1. Aquí guardamos la lista de uniformes. 
  // "productos" es la lista y "setProductos" es la función para actualizarla.
  const [productos, setProductos] = useState([
    { id: 1, nombre: 'Camiseta Local' }
  ]);

  // 2. Aquí guardamos lo que el usuario escribe en el cuadrito de texto.
  const [textoEscrito, setTextoEscrito] = useState('');

  // 3. Esta función se activa cuando haces clic en el botón "Guardar"
  const guardarUniforme = (event) => {
    event.preventDefault(); // Evita que la página se recargue

    // Creamos el nuevo objeto para el uniforme
    const nuevo = {
      id: Date.now(), // Genera un número único usando la hora actual
      nombre: textoEscrito
    };

    // Agregamos el nuevo a la lista que ya teníamos
    setProductos([...productos, nuevo]);

    // Limpiamos el cuadrito de texto
    setTextoEscrito('');
  };

  return (
    <div style={{ padding: '20px' }}>
      <h1>Tienda UniDeportes</h1>

      {/* Formulario para escribir el nombre */}
      <form onSubmit={guardarUniforme}>
        <input 
          type="text" 
          placeholder="Escribe el uniforme..." 
          value={textoEscrito}
          onChange={(e) => setTextoEscrito(e.target.value)} 
        />
        <button type="submit">Guardar</button>
      </form>

      {/* Lista donde se muestran los uniformes */}
      <h3>Lista de Inventario:</h3>
      <ul>
        {productos.map((item) => (
          <li key={item.id}>
            {item.nombre}
          </li>
        ))}
      </ul>
    </div>
  );
}

export default GestionProductos;