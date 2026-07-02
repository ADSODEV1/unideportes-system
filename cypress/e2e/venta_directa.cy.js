describe('Módulo Ventas - Venta Directa', () => {
  
  it('Debe mostrar el formulario de nueva venta', () => {
    // Login
    cy.visit('localhost/unideportes-system/public/index.php');
    cy.get('input[name="username"]').type('Pablo');
    cy.get('input[name="password"]').type('pablo123');
    cy.get('button[type="submit"]').click();
    
    // Verificar que estamos en el panel
    cy.url().should('include', 'panel_vendedor.php');
    cy.contains('Panel del Vendedor').should('be.visible');
    
    // Esperar y hacer clic en Nueva Venta
    cy.wait(1000);
    cy.contains('Nueva Venta').scrollIntoView().click({ force: true });
    
    // Verificar que cargó el formulario
    cy.url().should('include', 'nueva_venta.php');
    cy.contains('Nueva Venta Directa').should('be.visible');
  });

  it('Debe verificar el estado inicial del carrito', () => {
    // Login
    cy.visit('localhost/unideportes-system/public/index.php');
    cy.get('input[name="username"]').type('Pablo');
    cy.get('input[name="password"]').type('pablo123');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', 'panel_vendedor.php');
    
    // Ir a nueva venta
    cy.wait(1000);
    cy.contains('Nueva Venta').scrollIntoView().click({ force: true });
    
    // Verificar que el total es $0 al inicio
    cy.get('#txtTotal').should('contain', '$0');
    
    // Verificar que el carrito EXISTE (no que sea visible)
    // Cuando está vacío, el tbody tiene altura 0 pero SÍ existe en el DOM
    cy.get('#carritoBody').should('exist');
    
    // Verificar que el carrito está vacío
    cy.get('#carritoBody tr').should('have.length', 0);
  });

});