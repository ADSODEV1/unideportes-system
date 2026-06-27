<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <p class="footer-title">
                &copy; <?= date("Y") ?> <strong>Unideportes</strong> - Sistema de Gestión
            </p>
            <p class="footer-subtitle">
                Ropa Deportiva | Proyecto Formativo ADSO - SENA 2026
            </p>
        </div>
    </div>
</footer>

<style>
/* ============================================
   FOOTER - ESTILOS SIMPLIFICADOS
   ============================================ */

.main-footer {
    background: white;
    border-top: 1px solid #e2e8f0;
    margin-top: auto;
    padding: 20px 0;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

.footer-content {
    text-align: center;
}

.footer-title {
    margin: 0;
    color: #475569;
    font-size: 0.9rem;
    font-weight: 500;
}

.footer-title strong {
    color: #1e293b;
    font-weight: 700;
}

.footer-subtitle {
    margin: 5px 0 0 0;
    color: #94a3b8;
    font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 768px) {
    .main-footer {
        padding: 15px 0;
    }
    
    .footer-title {
        font-size: 0.85rem;
    }
    
    .footer-subtitle {
        font-size: 0.75rem;
    }
}
</style>

</body>
</html>