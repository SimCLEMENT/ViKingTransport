<?php session_start(); ?>

<div class="login-wrapper">
    <div class="bg-white border rounded-3 p-4 w-100" style="max-width: 400px;">
        <a href="/compte/compte.php" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-3">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <h4 class="text-center mb-4 fw-bold" style="color: var(--viking-red);">Modifiez votre numéro de téléphone actuel</h4>
        <form method="post" action="./server/tel.php">

            <div class="mb-3">
                <label for="tel" class="form-label fw-semibold small">N° de téléphone</label>
                <input type="tel" class="form-control" id="tel" name="tel" placeholder="06 12 34 56 78">
            </div>

            <div class="d-flex flex-column gap-2">
                <input id="change" name="change" type="submit" class="btn w-100 fw-semibold text-white" style="background-color: #C62828;"
                       value="Modifier">
            </div>

        </form>
    </div>
</div>