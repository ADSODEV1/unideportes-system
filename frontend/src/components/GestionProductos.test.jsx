// Ruta: src/components/GestionProductos.test.jsx
import { describe, test, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import React from 'react'; // Asegura la compatibilidad de JSX
import GestionProductos from './GestionProductos';

describe('Prueba Unitaria de Interfaz - UniDeportes', () => {
  test('Debería alertar que los campos son obligatorios si se envía vacío', async () => {
    render(<GestionProductos />);
    
    // 1. Buscar el botón de envío
    const botonGuardar = screen.getByRole('button', { name: /guardar e integrar/i });
    
    // 2. Simular el clic del aprendiz/usuario
    fireEvent.click(botonGuardar);
    
    // 3. Verificar que aparezca el mensaje con la letra exacta que pusiste en el JSX
    const alerta = screen.getByText(/El nombre y el precio son obligatorios/i);
    expect(alerta).toBeInTheDocument();
  });
});