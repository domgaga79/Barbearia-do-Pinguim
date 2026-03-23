<?php 
include 'config.php'; 


// Lista de horários de 40 em 40 minutos
$horarios_disponiveis = [
    "08:00", "08:40", "09:20", "10:00", "10:40", "11:20", 
    "13:00", "13:40", "14:20", "15:00", "15:40", "16:20", "17:00", "17:40"
];


// Captura o IP do visitante
$ip_usuario = $_SERVER['REMOTE_ADDR'];

// Lógica para Salvar
if (isset($_POST['agendar'])) {
    $cliente  = $_POST['cliente'];
    $telefone = $_POST['telefone'];
    $servico  = $_POST['servico'];
    $data     = $_POST['data'];
    $hora     = $_POST['hora'];
    
    // Captura o IP do cliente
    $ip_cliente = $_SERVER['REMOTE_ADDR'];
    
    date_default_timezone_set('America/Bahia');
    $data_atual = date('Y-m-d');
    $hora_atual = date('H:i');

    // 1. Validação de horário passado
    if ($data < $data_atual || ($data == $data_atual && $hora <= $hora_atual)) {
        echo "<script>alert('Erro: Escolha um horário futuro!'); window.history.back();</script>";
        exit();
    }

    // 2. Validação de horário ocupado
    $check = $conn->prepare("SELECT id FROM agendamentos WHERE data_agendamento = ? AND horario_agendamento = ?");
    $check->bind_param("ss", $data, $hora);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Ops! Este horário já está ocupado.'); window.history.back();</script>";
        exit();
    }

    // 3. Tabela de Preços
    $precos = [
        "Corte Simples" => 30.00,
        "Barba" => 20.00,
        "Cabelo + Barba" => 45.00
    ];
    $valor = isset($precos[$servico]) ? $precos[$servico] : 0;

    // 4. GRAVAÇÃO COM IP (Note o "s" a mais no bind_param e o campo ip_cliente no final)
    $stmt = $conn->prepare("INSERT INTO agendamentos (cliente, telefone, servico, valor, data_agendamento, horario_agendamento, ip_cliente) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // "sssdsss" -> 7 campos (cliente, fone, serv, valor, data, hora, ip)
    $stmt->bind_param("sssdsss", $cliente, $telefone, $servico, $valor, $data, $hora, $ip_cliente);
    
    if($stmt->execute()) {
        echo "<script>alert('🐧 Agendado com sucesso na Barbearia do Pinguim!'); window.location.href='index.php';</script>";
    } else {
        echo "Erro ao gravar: " . $conn->error;
    }
}

// Lógica para Cancelar (Só funciona se o IP for o mesmo)
if (isset($_GET['cancelar'])) {
    $id = $_GET['cancelar'];
    // Segurança extra: O SQL só deleta se o ID E o IP baterem
    $conn->query("DELETE FROM agendamentos WHERE id=$id AND ip_cliente='$ip_usuario'");
    header("Location: index.php");
}

// Busca todos os agendamentos futuros para exibição pública
$resultado = $conn->query("SELECT * FROM agendamentos WHERE data_agendamento >= CURDATE() ORDER BY data_agendamento ASC, horario_agendamento ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐧</text></svg>">
    <title>🐧 Barbearia do Pinguim - Agendamento</title>
    <style>
    :root { --gold: #d4af37; --bg: #121212; --card: #1e1e1e; }
    body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: white; padding: 15px; margin: 0; }
    .container { max-width: 800px; margin: auto; }
    
    /* GRID RESPONSIVO: 1 coluna no celular, 2 colunas no PC */
    .grid { 
        display: grid; 
        grid-template-columns: 1fr; 
        gap: 20px; 
    }
    
    @media (min-width: 768px) {
        .grid { grid-template-columns: 1fr 1fr; }
    }
    
    .box { background: var(--card); padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
    
    /* Ajuste de Inputs com Ícones */
    .input-group { position: relative; margin-bottom: 5px; }
    .input-group i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gold); }
    input, select { 
        width: 100%; 
        padding: 12px 12px 12px 35px; /* Espaço para o ícone */
        margin: 8px 0; 
        border-radius: 6px; 
        border: 1px solid #333; 
        background: #252525; 
        color: white; 
        box-sizing: border-box; 
        font-size: 16px; /* Evita zoom no iPhone */
    }
    
    button { 
        width: 100%; padding: 15px; background: var(--gold); border: none; 
        font-weight: bold; cursor: pointer; border-radius: 6px; color: #000;
        transition: 0.3s; margin-top: 10px;
    }
    button:hover { opacity: 0.9; transform: scale(1.02); }
    
    .apt-item { background: #2a2a2a; padding: 12px; margin-bottom: 8px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--gold); }
    .badge-meu { background: #2ecc71; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .btn-cancel { color: #ff4444; font-size: 12px; text-decoration: none; border: 1px solid #ff4444; padding: 5px 10px; border-radius: 5px; transition: 0.3s; }
    .btn-cancel:hover { background: #ff4444; color: white; }
    
    /* Rodapé Ajustado */
    .footer-pinguim { margin-top: 40px; border-top: 1px solid #333; padding-top: 20px; }
    .footer-content { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center; }
    .footer-section h4 { color: var(--gold); margin-bottom: 10px; text-transform: uppercase; }
    .social-links a { display: inline-block; margin: 5px; color: white; text-decoration: none; border: 1px solid #444; padding: 8px 15px; border-radius: 20px; font-size: 13px; }
    
    /* Botão Flutuante */
    .whatsapp-float {
        position: fixed;
        width: 60px;
        height: 60px;
        bottom: 30px;
        right: 30px;
        background-color: #25d366;
        color: #FFF;
        border-radius: 50px;
        text-align: center;
        font-size: 30px;
        box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        animation: pulse-whats 2s infinite;
    }
    
    .whatsapp-float img {
        width: 35px;
        height: 35px;
    }
    
    .whatsapp-float:hover {
        transform: scale(1.1);
        background-color: #20ba5a;
    }
    
    /* Tooltip (Balão de texto) */
    .tooltip-whats {
        position: absolute;
        right: 70px;
        background-color: #fff;
        color: #333;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: bold;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        opacity: 0;
        visibility: hidden;
        transition: 0.3s;
    }
    
    .whatsapp-float:hover .tooltip-whats {
        opacity: 1;
        visibility: visible;
    }
    
    .admin-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #999;
            text-decoration: none;
            font-size: 13px;
        }
        
    .admin-link:hover { color: var(--gold); }
    
    .footer-bottom {
        text-align: center;
        font-size: small;
    }
    
    /* Animação de Pulso (Aumenta e diminui a sombra) */
    @keyframes pulse-whats {
        0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
    }
    
    /* Animação de Movimento (Balança levemente) */
    @keyframes shake-whats {
        0% { transform: scale(1); }
        50% { transform: scale(1.1) rotate(5deg); }
        100% { transform: scale(1); }
    }
    
    .whatsapp-float:hover {
        transform: scale(1.2); /* Aumenta um pouco mais quando passa o mouse */
        background-color: #128c7e;
    }
    
    /* Estilo padrão (Desktop) */
    .titulo-pinguim {
      text-align: center;
      color: var(--gold);
      font-size: 2rem; /* Ajuste como preferir */
    }
    
    /* Regra para Celular (telas menores que 600px) */
    @media (max-width: 600px) {
      .titulo-pinguim span {
        display: block; /* Força o span a ocupar a linha toda, empurrando para baixo */
      }
    }
    
</style>
</head>
<body>

<div class="container">

    <h1 class="titulo-pinguim">
        🐧 Barbearia <span>do Pinguim</span>
    </h1>
    
    <div class="grid">
        <div class="box">
            <h3>Agende seu horário</h3>
            <form method="POST">
                <input type="text" name="cliente" placeholder="Nome" required>
                <input type="text" name="telefone" placeholder="WhatsApp" required>
                
                <select name="servico" required>
                    <option value="Corte Simples">Corte Simples</option>
                    <option value="Barba">Barba</option>
                    <option value="Cabelo + Barba">Cabelo + Barba</option>
                </select>
            
                <input type="text" 
                   name="data" 
                   placeholder="dd/mm/aaaa" 
                   onfocus="(this.type='date')" 
                   onblur="if(!this.value)this.type='text'" 
                   required>
            
                <label>Escolha o Horário:</label>
                <select name="hora" required>
                    <option value="">Selecione um horário</option>
                    <?php foreach($horarios_disponiveis as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                    <?php endforeach; ?>
                </select>
            
                <button type="submit" name="agendar">Confirmar</button>
            </form>
        </div>

        <div class="box">
            <h3>Agenda de Clientes</h3>
            <?php while($row = $resultado->fetch_assoc()): ?>
                <div class="apt-item">
                    <div>
                        <strong><?php echo date('d/m', strtotime($row['data_agendamento'])); ?> às <?php echo $row['horario_agendamento']; ?></strong><br>
                        <small><?php echo ($row['ip_cliente'] == $ip_usuario) ? "Seu Agendamento" : "Horário Ocupado"; ?></small>
                    </div>
                    
                    <?php if($row['ip_cliente'] == $ip_usuario): ?>
                        <a href="?cancelar=<?php echo $row['id']; ?>" class="btn-cancel" onclick="return confirm('Cancelar seu horário?')">Desmarcar</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <a href="admin.php" class="admin-link">Acesso Restrito ao Barbeiro</a>
</div>


<footer class="footer-pinguim">
    <div class="footer-content">
        <div class="footer-section">
            <h4>📍 Localização</h4>
            <p>Rua do Gelo, 123 - Centro<br>Amargosa, Bahia</p>
        </div>
        
        <div class="footer-section">
            <h4>📱 Redes Sociais</h4>
            <div class="social-links">
                <a href="https://instagram.com/GagarinJoe79" target="_blank">Instagram</a>
                <a href="https://api.whatsapp.com/send?phone=5571993143374&text=Olá,%20gostaria%20de%20informações%20sobre%20os%20serviços%20da%20Barbearia%20do%20Pinguim!" target="_blank">WhatsApp</a>
            </div>
        </div>

        <div class="footer-section">
            <h4>⏰ Horários</h4>
            <p>Seg - Sex: 08h às 19h<br>Sáb: 08h às 17h</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2026 Barbearia do Pinguim 🐧 - Todos os direitos reservados.
    </div>
</footer>


<a href="https://api.whatsapp.com/send?phone=5571993143374&text=Olá,%20vim%20pelo%20site%20da%20Barbearia%20do%20Pinguim%20e%20tenho%20uma%20dúvida!" class="whatsapp-float" target="_blank" rel="noopener noreferrer">
    <span class="tooltip-whats">Dúvidas? Fale conosco!</span>
    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
</a>

<script>
    // Garante que o campo de data sempre comece com a data de hoje no navegador do cliente
    document.getElementById('data_agendamento').min = new Date().toISOString().split("T")[0];
</script>

</body>
</html>