<?php
require __DIR__ . '/auth.php';
if (!empty($_SESSION['farmacia_autenticada'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    require __DIR__ . '/conexion.php';
    $stmt = $mysqli->prepare('SELECT id, nombre, correo, password_hash FROM usuarios WHERE correo=? AND activo=1 LIMIT 1');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($usuario && password_verify($contrasena, $usuario['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['farmacia_autenticada'] = true;
        $_SESSION['farmacia_usuario'] = $correo;
        $_SESSION['farmacia_usuario_id'] = (int)$usuario['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso | Farmacia</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#edf5f2;color:#243b37;font:15px Arial,sans-serif}.card{width:min(420px,calc(100% - 30px));background:white;border:1px solid #dce9e5;border-radius:16px;padding:30px;box-shadow:0 10px 32px #173d3515}.logo{width:54px;height:54px;border-radius:14px;background:#087f73;color:white;display:grid;place-items:center;font-size:30px;margin-bottom:16px}.card h1{color:#087f73;margin:0 0 6px;font-size:24px}.card p{color:#71827e;margin:0 0 22px}label{display:block;font-weight:bold;margin:14px 0 6px}input{width:100%;padding:12px;border:1px solid #cbdad6;border-radius:8px;font:inherit}.btn{width:100%;padding:12px;border:0;border-radius:8px;background:#087f73;color:white;font-weight:bold;font-size:16px;margin-top:20px;cursor:pointer}.error{background:#fff0ed;color:#8c3024;padding:11px;border-radius:8px;margin:14px 0}
</style></head><body><main class="card"><div class="logo">✚</div><h1>Farmacia</h1><p>Inicia sesión para acceder al sistema de facturación.</p><?php if ($error) { ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php } ?><form method="post" autocomplete="on"><label for="correo">Correo</label><input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required autofocus><label for="contrasena">Contraseña</label><input type="password" id="contrasena" name="contrasena" required><button class="btn" type="submit">Entrar al sistema</button></form></main></body></html>
