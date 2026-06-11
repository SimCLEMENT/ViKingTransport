<header class="site-header py-3">
    <nav>
        <ul class="nav nav-underline d-flex align-items-center px-3">
            <li class="nav-col">
                <a href="/">
                    <img src="/images/logo_blanc.png" alt="Viking Transport" height="60">
                </a>
            </li>
            <li class="nav-col nav-center">
                <a class="nav-link <?php if ($_SERVER["REQUEST_URI"] == "/") :?> active <?php endif; ?>" href="/"><i class="bi bi-house"></i> Page d'Accueil</a>
                <a class="nav-link <?php if ($_SERVER["REQUEST_URI"] == "/reservation/lignes.php") :?> active <?php endif; ?>" href="/reservation/lignes.php"><i class="bi bi-truck"></i> Se déplacer</a>
                <a class="nav-link <?php if ($_SERVER["REQUEST_URI"] == "/reservation/tarif.php") :?> active <?php endif; ?>" href="/reservation/tarif.php"><i class="bi bi-tag"></i> Tarifs</a>
                <a class="nav-link <?php if ($_SERVER["REQUEST_URI"] == "/apropos.php") :?> active <?php endif; ?>" href="/apropos.php"><i class="bi bi-question"></i> Qui sommes-nous</a>
            </li>
            <li class="nav-col nav-right d-flex gap-2 justify-content-end">
                <?php if (isset($_SESSION["id"])) :?>
                    <?php if (isset($_SESSION["type"]) && $_SESSION["type"] == 200 || $_SESSION["id"] == 200) :?>
                        <a class="btn btn-outline-primary fw-semibold" href="/administration/admin.php">
                            <i class="bi bi-shield-lock"></i> Administration
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-outline-primary fw-semibold" href="/compte/compte.php">
                        <i class="bi bi-person-gear me-1"></i>Mon compte
                    </a>
                    <a class="btn btn-outline-danger fw-semibold" href="/auth/deconnexion.php">Deconnexion</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary fw-semibold" href="/auth/connexion.php">
                        <i class="bi bi-person me-1"></i>Se connecter
                    </a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</header>