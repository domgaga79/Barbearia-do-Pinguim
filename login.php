<?php
session_start();
include 'config.php';

// Lógica de Logout: Se vier do admin clicando em "Sair"
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Se o usuário já estiver logado, redireciona direto para o admin
if (isset($_SESSION['logado'])) {
    header("Location: admin.php");
    exit();
}

// Lógica de Autenticação
if (isset($_POST['btn-entrar'])) {
    $usuarioInput = $_POST['usuario'];
    $senhaInput = $_POST['senha'];

    // Busca o usuário no banco de dados
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ? AND senha = ?");
    $stmt->bind_param("ss", $usuarioInput, $senhaInput);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $dados = $resultado->fetch_assoc();
        $_SESSION['logado'] = true;
        $_SESSION['usuario'] = $dados['usuario'];
        header("Location: admin.php");
        exit();
    } else {
        $erro = "Usuário ou senha inválidos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐧</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        :root { --gold: #d4af37; --bg: #0a0a0a; --card: #161616; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: var(--bg); 
            color: white; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-box { 
            background: var(--card); 
            padding: 40px; 
            border-radius: 12px; 
            width: 100%; 
            max-width: 350px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            border: 1px solid #222;
        }
        h2 { text-align: center; color: var(--gold); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 14px; color: #888; }
        input { 
            width: 100%; 
            padding: 12px; 
            background: #222; 
            border: 1px solid #333; 
            color: white; 
            border-radius: 6px; 
            box-sizing: border-box; 
            outline: none;
        }
        input:focus { border-color: var(--gold); }
        button { 
            width: 100%; 
            padding: 12px; 
            background: var(--gold); 
            border: none; 
            border-radius: 6px; 
            font-weight: bold; 
            font-size: 16px; 
            cursor: pointer; 
            transition: 0.3s;
        }
        button:hover { background: #b8962d; }
        .erro-msg { 
            background: rgba(255, 68, 68, 0.1); 
            color: #ff4444; 
            padding: 10px; 
            border-radius: 6px; 
            font-size: 13px; 
            text-align: center; 
            margin-bottom: 20px; 
            border: 1px solid #ff4444;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #999;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link:hover { color: var(--gold); }
    </style>
</head>
<body>

<div class="login-box">
    <h2>🐧 Acesso Pinguim Admin</h2>

    <?php if(isset($erro)): ?>
        <div class="erro-msg"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Usuário</label>
            <input type="text" name="usuario" required autofocus>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <input type="password" name="senha" required>
        </div>

        <button type="submit" name="btn-entrar">ENTRAR NO PAINEL</button>
    </form>

    <a href="index.php" class="back-link">← Voltar para Agendamentos</a>
</div>

</body>
</html>