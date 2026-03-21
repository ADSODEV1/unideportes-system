// Esperar a que todo el DOM (HTML) esté cargado antes de ejecutar el código
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Mensaje de consola para verificar que el archivo está conectado
    console.log("Archivo main.js cargado correctamente.");

    // 2. Función para resaltar la sección activa al hacer clic en el menú
    const enlacesNavegacion = document.querySelectorAll('ol li a');

    enlacesNavegacion.forEach(enlace => {
        enlace.addEventListener('click', (e) => {
            // Ejemplo de una alerta sencilla al navegar 
            const destino = e.target.getAttribute('href');
            console.log(`Navegando hacia la sección: ${destino}`);
        });
    });

    // 3. Mini-interactividad: Cambiar el tema (Modo Oscuro/Claro)
    // Vamos a crear un botón dinámicamente para demostrar manejo del DOM
    const botonModo = document.createElement('button');
    botonModo.innerText = '🌓 Cambiar Modo';
    botonModo.className = 'btn btn-outline-primary btn-sm fixed-bottom m-3';
    botonModo.style.width = '150px';
    document.body.appendChild(botonModo);

    botonModo.addEventListener('click', () => {
        document.body.classList.toggle('bg-dark');
        document.body.classList.toggle('text-white');
        
        // Cambiar el estilo de las secciones para que contrasten
        const secciones = document.querySelectorAll('section');
        secciones.forEach(s => {
            s.classList.toggle('border-secondary');
        });
    });
});