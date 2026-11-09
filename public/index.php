<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isLoggedIn()) {
    header('Location: ventas.php');
    exit;
}

$pageTitle = 'Iniciar sesión';
$error = $_GET['error'] ?? null;
require __DIR__ . '/../includes/header.php';
?>

<section class="login-box">
    <h1>Iniciar sesión</h1>

    <?php if ($error === 'bloqueado'): ?>
    <p class="alert-error">⚠️ Demasiados intentos. Espera 1 minuto e intenta de nuevo.</p>
<?php elseif ($error): ?>
    <p class="alert-error">Usuario o contraseña incorrectos.</p>
<?php endif; ?>

        <form action="../actions/login_action.php" method="POST">
        <?= csrfField() ?>
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
