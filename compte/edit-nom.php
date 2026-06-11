<?php session_start(); ?>

<div class="login-wrapper">
    <div class="bg-white border rounded-3 p-4 w-100" style="max-width: 400px;">
        <a href="/compte/compte.php" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-3">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <h4 class="text-center mb-4 fw-bold" style="color: var(--viking-red);">Modifiez votre identité actuelle</h4>
        <form method="post" action="./server/nom.php">

            <div class="mb-4">
                <label for="prenom" class="form-label fw-semibold small">Prénom</label>
                <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Votre prénom">
            </div>

            <div class="mb-3">
                <label for="nom" class="form-label fw-semibold small">Nom de famille</label>
                <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom de famille">
            </div>

            <div class="d-flex flex-column gap-2">
                <input id="change" name="change" type="submit" class="btn w-100 fw-semibold text-white" style="background-color: #C62828;"
                       value="Modifier">
            </div>

        </form>
    </div>
</div>