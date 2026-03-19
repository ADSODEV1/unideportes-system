import React from 'react';

/**
 * PROYECTO: Unideportes
 * APRENDIZ: Joel Abdias Castro Hernandez
 * EXPLICACIÓN: Este es el componente para ver los productos que hay en la tienda.
 */
const InventarioTienda = () => {
    
    // Aquí creo una lista (array) con los productos que tenemos para vender
    const stock = [
        { id: 1, producto: "Camiseta Selección", talla: "M", unidades: 15, estado: "Disponible" },
        { id: 2, producto: "Sudadera Training", talla: "L", unidades: 3, estado: "Crítico" },
        { id: 3, producto: "Pantaloneta Running", talla: "S", unidades: 25, estado: "Suficiente" }
    ];

    return (
        <div style={{ padding: '20px' }}>
            
            {/* Título principal con el azul marino de la marca Unideportes */}
            <h2 style={{ color: '#1A2B4C' }}>Inventario de Tienda</h2>
            
            {/* Creo una tabla con borde 1 para que se vean las líneas clarito */}
            <table border="1" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                    {/* El encabezado de la tabla con fondo oscuro y letra blanca */}
                    <tr style={{ backgroundColor: '#1A2B4C', color: 'white' }}>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Talla</th>
                        <th>Cant.</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    {/* Uso .map para que React recorra mi lista de productos uno por uno */}
                    {stock.map(item => (
                        <tr key={item.id} style={{ textAlign: 'center' }}>
                            <td>{item.id}</td>
                            <td>{item.producto}</td>
                            <td>{item.talla}</td>
                            <td>{item.unidades}</td>
                            {/* Si el estado es Crítico, se pone en rojo alerta, si no, en verde acción */}
                            <td style={{ 
                                color: item.estado === 'Crítico' ? '#E53935' : '#2D4628',
                                fontWeight: 'bold'
                            }}>
                                {item.estado}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Botón naranja para registrar ventas, siguiendo la paleta de colores */}
            <button style={{ 
                marginTop: '15px', 
                backgroundColor: '#D98E2B', 
                color: 'white',
                padding: '10px',
                border: 'none',
                cursor: 'pointer'
            }}>
                Registrar Nueva Venta
            </button>
        </div>
    );
};

export default InventarioTienda;