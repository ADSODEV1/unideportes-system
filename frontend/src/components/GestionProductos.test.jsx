// Ruta: src/components/GestionProductos.test.jsx
import { describe, test, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import React from 'react';
import GestionProductos from './GestionProductos';

describe('Pruebas Unitarias - Gestión de Productos UniDeportes', () => {
  
  test('Debería mostrar mensaje de error si se envía el formulario vacío', () => {
    render(<GestionProductos />);
    
    const botonGuardar = screen.getByRole('button', { name: /guardar producto/i });
    fireEvent.click(botonGuardar);
    
    const alerta = screen.getByText(/El nombre, referencia y precio son obligatorios/i);
    expect(alerta).toBeInTheDocument();
  });

  test('Debería permitir escribir en los campos del formulario', () => {
    render(<GestionProductos />);
    
    const nombreInput = screen.getByLabelText(/nombre del producto/i);
    const referenciaInput = screen.getByLabelText(/referencia/i);
    const precioInput = screen.getByLabelText(/precio/i);
    
    fireEvent.change(nombreInput, { target: { value: 'Camiseta Deportiva' } });
    fireEvent.change(referenciaInput, { target: { value: 'CAM-001' } });
    fireEvent.change(precioInput, { target: { value: '45000' } });
    
    expect(nombreInput.value).toBe('Camiseta Deportiva');
    expect(referenciaInput.value).toBe('CAM-001');
    expect(precioInput.value).toBe('45000');
  });

  test('Debería mostrar el formulario completo con todos los campos', () => {
    render(<GestionProductos />);
    
    expect(screen.getByLabelText(/nombre del producto/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/referencia/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/precio/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/stock inicial/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/categoría/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/talla/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /guardar producto/i })).toBeInTheDocument();
  });
});