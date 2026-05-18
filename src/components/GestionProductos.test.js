// Ruta: src/components/GestionProductos.test.js
import { render, screen, fireEvent } from '@testing-library/react';
import GestionProductos from './GestionProductos';

describe('Prueba Unitaria de Interfaz - UniDeportes', () => {
  test('Debería alertar que los campos son obligatorios si se envía vacío', () => {
    render(<GestionProductos />);
    
    // Buscar el botón de envío
    const botonGuardar = screen.getByRole('button', { name: /guardar e integrar/i });
    
    // Simular el clic del aprendiz/usuario
    fireEvent.click(botonGuardar);
    
    // Verificar que aparezca el mensaje de error de frontend en la pantalla simulada
    const alerta = screen.getByText(/el nombre y el precio son obligatorios/i);
    expect(alerta).toBeInTheDocument();
  });
});