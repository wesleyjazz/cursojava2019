<!-- footer.php -->
        </div> <!-- Fechamento do container principal -->

        <footer class="footer mt-auto py-4 bg-primary text-white">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Sobre o Sistema</h5>
                        <p class="mb-0 small">Sistema de gestão de manutenção preventiva e corretiva desenvolvido para controle de máquinas e equipamentos.</p>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <h5 class="mb-3"><i class="bi bi-link"></i> Links Úteis</h5>
                        <ul class="list-unstyled">
                            <li><a href="machines.php" class="text-white text-decoration-none"><i class="bi bi-chevron-right"></i> Máquinas</a></li>
                            <li><a href="orders.php" class="text-white text-decoration-none"><i class="bi bi-chevron-right"></i> Ordens de Serviço</a></li>
                            <li><a href="schedules.php" class="text-white text-decoration-none"><i class="bi bi-chevron-right"></i> Agendamentos</a></li>
                        </ul>
                    </div>

                    <div class="col-md-4 mb-4">
                        <h5 class="mb-3"><i class="bi bi-envelope"></i> Contato</h5>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-headset"></i> Suporte: wesley.firmino@filiperson.com.br</li>
                            <li><i class="bi bi-telephone"></i> +55 (21) 99209-9041</li>
                            <li class="mt-2">
                                <div class="social-links">
                                    <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                                    <a href="#" class="text-white me-2"><i class="bi bi-linkedin"></i></a>
                                    <a href="#" class="text-white me-2"><i class="bi bi-whatsapp"></i></a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-top pt-3 mt-4 text-center">
                    <p class="mb-0 small">
                        &copy; <?= date('Y') ?> Filiperson - Todos os direitos reservados
                        <span class="mx-2">|</span>
                        Versão 1.0.0
                    </p>
                </div>
            </div>
        </footer>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/scripts.js"></script>
    </body>
</html>
