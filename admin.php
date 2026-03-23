<?php 
session_start();
include 'config.php'; 

// 1. Verificação de Segurança
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}

// 2. Lógica para Excluir Agendamentos
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $stmt = $conn->prepare("DELETE FROM agendamentos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit();
}

// 3. Consultas para o Dashboard (Somente Hoje)
$hoje = date('Y-m-d');
$query_stats = $conn->query("SELECT COUNT(*) as total, SUM(valor) as faturamento FROM agendamentos WHERE data_agendamento = '$hoje'");
$stats = $query_stats->fetch_assoc();

// 4. Lista de todos os agendamentos futuros e de hoje
$agendamentos = $conn->query("SELECT * FROM agendamentos WHERE data_agendamento >= '$hoje' ORDER BY data_agendamento ASC, horario_agendamento ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐧</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <h1>🐧 Painel do Pinguim</h1>
    <style>
        :root { --gold: #d4af37; --bg: #0a0a0a; --card: #161616; --text: #e0e0e0; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            margin: 0; 
            padding: 20px; 
        }
    
        .container { max-width: 1100px; margin: auto; }
    
        /* Header: Alinha Título e Botão Sair */
        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
    
        .btn-logout { 
            background: transparent; border: 1px solid #444; color: #888; 
            padding: 8px 15px; border-radius: 5px; text-decoration: none; 
            font-size: 14px; transition: 0.3s; 
        }
        .btn-logout:hover { background: #ff4444; color: white; border-color: #ff4444; }
    
        /* Cards de Estatísticas */
        .dashboard { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 40px; 
        }
        .stat-card { 
            background: var(--card); padding: 25px; border-radius: 12px; 
            border-bottom: 3px solid var(--gold); text-align: center;
        }
        .stat-card h3 { margin: 0; font-size: 13px; color: #999; text-transform: uppercase; }
        .stat-card .value { font-size: 28px; font-weight: bold; margin-top: 10px; color: var(--gold); }
    
        /* Tabela Responsiva (O segredo para celular) */
        .table-container { 
            background: var(--card); 
            border-radius: 12px; 
            overflow-x: auto; /* Permite deslizar para os lados no celular */
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th { background: #1f1f1f; padding: 15px; text-align: left; color: var(--gold); }
        td { padding: 15px; border-bottom: 1px solid #252525; }
        tr:hover { background: #1c1c1c; }
    
        /* Botões de Ação */
        .actions { display: flex; gap: 10px; }
        .btn-action { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; color: white; }
        .btn-whatsapp { background: #25d366; }
        .btn-delete { background: #ff4444; }
    
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #999;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link:hover { color: var(--gold); }
        
        @media (max-width: 600px) {
            body { padding: 10px; }
            header { justify-content: center; text-align: center; } 
        }
</style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1 style="margin:0;"><i class="fa-solid fa-chart-line"></i> Gerenciamento de Agenda</h1>
            <p style="color: #666; margin: 5px 0 0;">Controle seus clientes e ganhos</p>
        </div>
        <a href="login.php?logout=true" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair do Sistema</a>
    </header>

    <div class="dashboard">
        <div class="stat-card">
            <h3>Clientes Confirmados (Hoje)</h3>
            <div class="value"><?php echo $stats['total'] ?? 0; ?></div>
        </div>
        <div class="stat-card">
            <h3>Faturamento Previsto (Hoje)</h3>
            <div class="value">R$ <?php echo number_format($stats['faturamento'] ?? 0, 2, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <h3>Ticket Médio</h3>
            <div class="value">
                R$ <?php 
                    $ticket = ($stats['total'] > 0) ? ($stats['faturamento'] / $stats['total']) : 0;
                    echo number_format($ticket, 2, ',', '.');
                ?>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><i class="fa-solid fa-clock"></i> Data & Hora</th>
                    <th><i class="fa-solid fa-user"></i> Cliente</th>
                    <th><i class="fa-solid fa-scissors"></i> Serviço</th>
                    <th><i class="fa-solid fa-phone"></i> Telefone</th>
                    <th><i class="fa-solid fa-tag"></i> Valor</th>
                    <th><i class="fa-solid fa-gear"></i> Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if($agendamentos->num_rows > 0): ?>
                    <?php while($row = $agendamentos->fetch_assoc()): ?>
                    <?php
                        // --- Lógica da Mensagem do WhatsApp ---
                        $data_bonita = date('d/m', strtotime($row['data_agendamento']));
                        $hora_bonita = date('H:i', strtotime($row['horario_agendamento']));
                        $msg = "Olá " . $row['cliente'] . "! 🐧 Aqui é da Barbearia do Pinguim. Seu agendamento de " . $row['servico'] . " para o dia " . $data_bonita . " às " . $hora_bonita . " está confirmado!";
                        $fone_limpo = preg_replace('/[^0-9]/', '', $row['telefone']);
                        $link_whatsapp = "https://wa.me/55" . $fone_limpo . "?text=" . urlencode($msg);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $data_bonita; ?></strong> 
                            às <?php echo $hora_bonita; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                        <td><?php echo $row['servico']; ?></td>
                        <td><?php echo $row['telefone']; ?></td>
                        <td style="color: #2ecc71; font-weight: bold;">R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                        <td class="actions">
                            <a href="<?php echo $link_whatsapp; ?>" target="_blank" class="btn-action btn-whatsapp">
                                <i class="fa-brands fa-whatsapp"></i> Confirmar
                            </a>
                            <a href="?excluir=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Deseja realmente excluir este agendamento?')">Excluir
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">Nenhum agendamento encontrado para os próximos dias.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <a href="index.php" class="back-link">← Voltar para Agendamentos</a>
</div>


</body>
</html>