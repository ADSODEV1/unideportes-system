describe('Sistema Unideportes - Prueba 1: Login Exitoso', () => {
  
  it('Debe permitir login exitoso como vendedor y redirigir al panel', () => {
    // 1. Visitar la página de login
    cy.visit('localhost/unideportes-system/public/index.php');
    
    // 2. Verificar que cargó la página de login
    cy.contains('Ingresar con usuario y contraseña').should('be.visible');
    cy.url().should('include', 'index.php');
    
    // 3. Llenar credenciales de vendedor (usuario: Pablo, contraseña: pablo123)
    cy.get('input[name="username"]')
      .clear()
      .type('Pablo')
      .should('have.value', 'Pablo');
    
    cy.get('input[name="password"]')
      .clear()
      .type('pablo123')
      .should('have.value', 'pablo123');
    
    // 4. Hacer clic en el botón de entrar
    cy.get('button[type="submit"]')
      .should('be.visible')
      .should('not.be.disabled')
      .click();
    
    // 5. Verificar redirección al panel del vendedor
    cy.url().should('include', 'panel_vendedor.php');
    
    // 6. Esperar a que cargue el panel
    cy.wait(1000);
    
    // 7. Verificar elementos visibles del panel (sin hacer scroll)
    cy.contains('Panel del Vendedor').should('be.visible');
    cy.contains('Bienvenido, Pablo').should('be.visible');
    
    // 8. Verificar elementos del sidebar (haciendo scroll si es necesario)
    cy.contains('Nueva Venta').scrollIntoView().should('be.visible');
    cy.contains('Venta Mayorista').scrollIntoView().should('be.visible');
    cy.contains('Clientes').scrollIntoView().should('be.visible');
  });

});