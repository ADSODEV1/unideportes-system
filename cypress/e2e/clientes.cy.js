describe('Módulo Clientes', () => {
  
  it('Debe cargar la página de clientes', () => {
    // Login
    cy.visit('localhost/unideportes-system/public/index.php');
    cy.get('input[name="username"]').type('Pablo');
    cy.get('input[name="password"]').type('pablo123');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', 'panel_vendedor.php');
    
    // Ir a clientes
    cy.contains('Clientes').scrollIntoView().click({ force: true });
    
    // Solo verificar que la página cargó
    cy.url().should('include', 'clientes.php');
  });

});