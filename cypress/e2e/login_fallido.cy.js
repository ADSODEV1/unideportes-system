describe('Sistema Unideportes - Prueba 2: Login Fallido', () => {
  
  it('Debe mostrar error con credenciales incorrectas', () => {
    // 1. Ir al login
    cy.visit('localhost/unideportes-system/public/index.php');
    
    // 2. Escribir datos falsos
    cy.get('input[name="username"]').type('usuario_falso');
    cy.get('input[name="password"]').type('contraseña_falsa');
    
    // 3. Hacer clic en entrar
    cy.get('button[type="submit"]').click();
    
    // 4. Verificar que muestra error
    cy.get('.alert-error, .alert-danger').should('be.visible');
    
    // 5. Verificar que sigue en login (no entró)
    cy.url().should('include', 'index.php');
  });

});