describe('Módulo Ventas - Venta Mayorista', () => {
  
  it('Debe mostrar el formulario de venta mayorista después del login', () => {
    // 1. Login
    cy.visit('localhost/unideportes-system/public/index.php');
    cy.get('input[name="username"]').type('Pablo');
    cy.get('input[name="password"]').type('pablo123');
    cy.get('button[type="submit"]').click();
    
    // 2. Verificar que estamos en el panel
    cy.url().should('include', 'panel_vendedor.php');
    cy.wait(1000);
    
    // 3. Ir a venta mayorista - SOLUCIÓN: scrollIntoView + force: true
    cy.contains('Venta Mayorista').scrollIntoView().click({ force: true });
    
    // 4. Verificar que cargó el formulario
    cy.url().should('include', 'venta_mayorista.php');
    cy.wait(1000);
    
    // 5. Verificar elementos clave del formulario
    cy.get('#clienteInput').should('exist');
    cy.get('#productoInput').should('exist');
    cy.get('#btnAgregar').should('exist');
    cy.get('#barraProgreso').should('exist');
    cy.get('#inputAbono').should('exist');
    cy.get('#fecha_entrega').should('exist');
    
    // 6. Verificar que el carrito está vacío al inicio
    cy.get('#txtTotal').should('contain', '$0');
    cy.get('#txtTotalFinal').should('contain', '$0');
    cy.get('#txtDescuento').should('contain', '$0');
  });

  it('Debe verificar los mensajes de la barra de progreso', () => {
    // Login
    cy.visit('localhost/unideportes-system/public/index.php');
    cy.get('input[name="username"]').type('Pablo');
    cy.get('input[name="password"]').type('pablo123');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', 'panel_vendedor.php');
    cy.wait(1000);
    
    // Ir a venta mayorista - SOLUCIÓN: scrollIntoView + force: true
    cy.contains('Venta Mayorista').scrollIntoView().click({ force: true });
    cy.wait(1000);
    
    // Verificar mensaje inicial de la barra de progreso
    cy.get('#mensajeProgreso').should('contain', 'Agrega productos');
    cy.get('#textoProgreso').should('contain', '0 unidades');
    cy.get('#barraProgreso').should('contain', '0%');
  });

  it('Debe verificar que existe el campo de abono con validación del 50%', () => {
    // Login
    cy.visit('localhost/unideportes-system/public/index.php');
    cy.get('input[name="username"]').type('Pablo');
    cy.get('input[name="password"]').type('pablo123');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', 'panel_vendedor.php');
    cy.wait(1000);
    
    // Ir a venta mayorista - SOLUCIÓN: scrollIntoView + force: true
    cy.contains('Venta Mayorista').scrollIntoView().click({ force: true });
    cy.wait(1000);
    
    // Verificar que existe el campo de abono
    cy.get('#inputAbono').should('exist');
    
    // Verificar que existe el mensaje de advertencia del 50%
    cy.contains('50%').should('exist');
    
    // Verificar que existe el saldo pendiente
    cy.get('#txtSaldoPendiente').should('exist');
  });

});