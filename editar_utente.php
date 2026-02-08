<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_GET['id'] ?? null;

if (!$utente_id) {
    die("Utente inválido.");
}

/* ===================== ATUALIZAR DADOS DO UTENTE ===================== */
if (isset($_POST['guardar_dados'])) {

    $nome = $_POST['nome'];
    $data_nascimento = $_POST['data_nascimento'];
    $peso = $_POST['peso'];
    $altura = $_POST['altura'];

    $stmt = $conn->prepare("UPDATE utentes SET nome=?, data_nascimento=?, peso=?, altura=? WHERE id=?");
    $stmt->bind_param("ssdii", $nome, $data_nascimento, $peso, $altura, $utente_id);
    $stmt->execute();

    header("Location: editar_utente.php?id=" . $utente_id);
    exit;
}

/* ===================== ADICIONAR MEDICAMENTO ===================== */
if (isset($_POST['adicionar_medicamento'])) {

    $nome = $_POST['nome_medicamento'];
    $dose = $_POST['dose'] ?? '';
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $recorrencia = $_POST['recorrencia'] ?? 'unica';
    $num_repeticoes = isset($_POST['num_repeticoes']) ? intval($_POST['num_repeticoes']) : 1;
    $dias_semana = $_POST['dias_semana'] ?? [];

    // Adicionar dose ao nome se fornecida
    if (!empty($dose)) {
        $nome = $nome . " - " . $dose;
    }

    // Limitar repetições
    if ($num_repeticoes > 365) $num_repeticoes = 365;
    if ($num_repeticoes < 1) $num_repeticoes = 1;

    $data_hora_base = $data . " " . $hora;
    
    $stmt = $conn->prepare("INSERT INTO medicamentos (id_utente, nome, data_hora) VALUES (?, ?, ?)");

    if ($recorrencia === 'unica') {
        // Apenas uma toma
        $stmt->bind_param("iss", $utente_id, $nome, $data_hora_base);
        $stmt->execute();
    } elseif ($recorrencia === 'semanal' && !empty($dias_semana)) {
        // Recorrência semanal com dias específicos
        $dateTime = new DateTime($data_hora_base);
        $contador_tomas = 0;
        $max_iteracoes = $num_repeticoes * 10; // Segurança contra loop infinito
        $iteracao = 0;
        
        while ($contador_tomas < $num_repeticoes && $iteracao < $max_iteracoes) {
            $dia_atual = (int)$dateTime->format('w'); // 0 = Domingo, 1 = Segunda, etc
            
            // Se o dia atual está na lista de dias selecionados
            if (in_array($dia_atual, $dias_semana)) {
                $data_hora_atual = $dateTime->format('Y-m-d H:i:s');
                $stmt->bind_param("iss", $utente_id, $nome, $data_hora_atual);
                $stmt->execute();
                $contador_tomas++;
            }
            
            $dateTime->modify('+1 day');
            $iteracao++;
        }
    } else {
        // Outras recorrências (diária, mensal, anual)
        $dateTime = new DateTime($data_hora_base);
        
        for ($i = 0; $i < $num_repeticoes; $i++) {
            $data_hora_atual = $dateTime->format('Y-m-d H:i:s');
            
            $stmt->bind_param("iss", $utente_id, $nome, $data_hora_atual);
            $stmt->execute();
            
            // Calcular próxima data
            switch ($recorrencia) {
                case 'diaria':
                    $dateTime->modify('+1 day');
                    break;
                case 'mensal':
                    $dateTime->modify('+1 month');
                    break;
                case 'anual':
                    $dateTime->modify('+1 year');
                    break;
            }
        }
    }

    header("Location: editar_utente.php?id=" . $utente_id);
    exit;
}

function getDiaSemanaTexto($dia) {
    $dias = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday'
    ];
    return $dias[$dia] ?? 'Monday';
}

/* ===================== REMOVER MEDICAMENTO ===================== */
if (isset($_GET['remover_med'])) {

    $med_id = $_GET['remover_med'];

    $stmt = $conn->prepare("DELETE FROM medicamentos WHERE id = ? AND id_utente = ?");
    $stmt->bind_param("ii", $med_id, $utente_id);
    $stmt->execute();

    header("Location: editar_utente.php?id=" . $utente_id);
    exit;
}

/* ===================== OBTER DADOS DO UTENTE ===================== */
$stmt = $conn->prepare("SELECT * FROM utentes WHERE id = ?");
$stmt->bind_param("i", $utente_id);
$stmt->execute();
$utente = $stmt->get_result()->fetch_assoc();

/* ===================== OBTER MEDICAMENTOS ===================== */
$stmt = $conn->prepare("SELECT * FROM medicamentos WHERE id_utente = ? ORDER BY data_hora ASC");
$stmt->bind_param("i", $utente_id);
$stmt->execute();
$medicamentos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Utente - WOLM</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body>

<header>
    <h1>WOLM</h1>
</header>

<main class="painel-container">

    <div class="perfil-container">
        <h2>Editar Utente</h2>

        <!-- FORMULÁRIO EDITAR UTENTE -->
        <form method="POST" class="perfil-form">

            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($utente['nome']) ?>" required>

            <label>Data de nascimento</label>
            <input type="date" name="data_nascimento" value="<?= htmlspecialchars($utente['data_nascimento']) ?>" required>

            <label>Peso (kg)</label>
            <input type="number" step="0.1" name="peso" value="<?= htmlspecialchars($utente['peso']) ?>" required>

            <label>Altura (cm)</label>
            <input type="number" step="0.1" name="altura" value="<?= htmlspecialchars($utente['altura']) ?>" required>

            <button type="submit" name="guardar_dados" class="btn-verde">
                Guardar alterações do utente
            </button>

        </form>

        <hr style="margin:2rem 0; border: none; height: 2px; background: #e8f5e9;">

        <!-- SEÇÃO MEDICAMENTOS ATUAIS -->
        <div class="secao-medicamentos">
            <h3 class="titulo-form">💊 Medicamentos Atuais</h3>

            <?php if ($medicamentos->num_rows > 0): ?>
                <div class="medicamentos-lista">
                    <?php while ($med = $medicamentos->fetch_assoc()): ?>
                        <div class="medicamento-card">
                            <div class="medicamento-info">
                                <span class="medicamento-nome"><?= htmlspecialchars($med['nome']) ?></span>
                                <span class="medicamento-data">📅 <?= date("d/m/Y", strtotime($med['data_hora'])) ?> às <?= date("H:i", strtotime($med['data_hora'])) ?></span>
                            </div>
                            <a href="editar_utente.php?id=<?= $utente_id ?>&remover_med=<?= $med['id'] ?>"
                               onclick="return confirm('Tem a certeza que deseja remover este medicamento?')"
                               class="btn-remover">
                               ❌ Remover
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="sem-medicamentos">
                    <p>📋 Nenhum medicamento registado.</p>
                </div>
            <?php endif; ?>
        </div>

        <hr style="margin:2rem 0; border: none; height: 2px; background: #e8f5e9;">

        <!-- SEÇÃO ADICIONAR MEDICAMENTO -->
        <div class="secao-adicionar">
            <h3 class="titulo-form">➕ Adicionar Novo Medicamento</h3>

            <button type="button" class="btn-verde" onclick="toggleMedicamentoForm()">
                ➕ Adicionar medicamento
            </button>

            <div id="formMedicamento" class="form-medicamento-wrapper" style="display:none;">
                <form method="POST" class="perfil-form">

                    <label>Nome do medicamento</label>
                    <input type="text" name="nome_medicamento" required placeholder="Ex: Paracetamol">

                    <label>Dose</label>
                    <input type="text" name="dose" placeholder="Ex: 500mg, 1 comprimido, 2ml...">

                    <div class="form-row">
                        <div class="form-col">
                            <label>Data de início</label>
                            <input type="date" name="data" required>
                        </div>
                        <div class="form-col">
                            <label>Hora</label>
                            <input type="time" name="hora" required>
                        </div>
                    </div>

                    <label>Recorrência</label>
                    <select name="recorrencia" id="recorrencia" onchange="toggleRecorrencia()" class="select-recorrencia">
                        <option value="unica">Toma única</option>
                        <option value="diaria">Diária (todos os dias)</option>
                        <option value="semanal">Semanal (todas as semanas)</option>
                        <option value="mensal">Mensal (todos os meses)</option>
                        <option value="anual">Anual (todos os anos)</option>
                    </select>

                    <div id="opcoes-recorrencia" style="display:none;">
                        <div class="info-recorrencia">
                            <p><strong>ℹ️ Como funciona:</strong></p>
                            <ul id="exemplo-recorrencia">
                                <!-- Preenchido via JavaScript -->
                            </ul>
                        </div>
                        
                        <label>Quantas vezes tomar?</label>
                        <input type="number" name="num_repeticoes" id="num_repeticoes" min="1" max="365" value="30" placeholder="Ex: 30" onchange="atualizarExemplo()">
                        
                        <div class="form-col" id="dia-semana-wrapper" style="display:none; margin-top:1rem;">
                            <label>Escolher dias da semana</label>
                            <div class="dias-semana-checkboxes">
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="1" onchange="atualizarExemplo()">
                                    <span>Seg</span>
                                </label>
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="2" onchange="atualizarExemplo()">
                                    <span>Ter</span>
                                </label>
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="3" onchange="atualizarExemplo()">
                                    <span>Qua</span>
                                </label>
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="4" onchange="atualizarExemplo()">
                                    <span>Qui</span>
                                </label>
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="5" onchange="atualizarExemplo()">
                                    <span>Sex</span>
                                </label>
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="6" onchange="atualizarExemplo()">
                                    <span>Sáb</span>
                                </label>
                                <label class="checkbox-dia">
                                    <input type="checkbox" name="dias_semana[]" value="0" onchange="atualizarExemplo()">
                                    <span>Dom</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="adicionar_medicamento" class="btn-verde">
                        💾 Guardar medicamento
                    </button>

                </form>
            </div>
        </div>

        <br>
        <a href="painel_cuidador.php" class="botao outline">⬅ Voltar ao painel</a>
    </div>

</main>

<script>
function toggleMedicamentoForm() {
    const form = document.getElementById('formMedicamento');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleRecorrencia() {
    const recorrencia = document.getElementById('recorrencia').value;
    const opcoesDiv = document.getElementById('opcoes-recorrencia');
    const diaSemanaWrapper = document.getElementById('dia-semana-wrapper');
    
    if (recorrencia === 'unica') {
        opcoesDiv.style.display = 'none';
        diaSemanaWrapper.style.display = 'none';
    } else {
        opcoesDiv.style.display = 'block';
        
        // Mostrar seletor de dia apenas para recorrência semanal
        if (recorrencia === 'semanal') {
            diaSemanaWrapper.style.display = 'block';
        } else {
            diaSemanaWrapper.style.display = 'none';
        }
        
        atualizarExemplo();
    }
}

function atualizarExemplo() {
    const recorrencia = document.getElementById('recorrencia').value;
    const numRepeticoes = document.getElementById('num_repeticoes').value || 30;
    const exemploDiv = document.getElementById('exemplo-recorrencia');
    
    // Obter dias selecionados
    const diasSelecionados = Array.from(document.querySelectorAll('input[name="dias_semana[]"]:checked'))
        .map(cb => cb.nextElementSibling.textContent);
    
    let exemplos = [];
    
    switch(recorrencia) {
        case 'diaria':
            exemplos = [
                `Vai criar <strong>${numRepeticoes} tomas</strong> (uma por dia)`,
                `Exemplo: se começar hoje, terá medicamentos até daqui a ${numRepeticoes} dias`
            ];
            break;
        case 'semanal':
            if (diasSelecionados.length > 0) {
                const diasTexto = diasSelecionados.join(', ');
                exemplos = [
                    `Vai criar <strong>${numRepeticoes} tomas</strong>`,
                    `Dias: <strong>${diasTexto}</strong>`,
                    `Exemplo: ${numRepeticoes} tomas distribuídas pelos dias escolhidos`
                ];
            } else {
                exemplos = [
                    "Pode escolher mais do que um dia da semana"
                ];
            }
            break;
        case 'mensal':
            exemplos = [
                `Vai criar <strong>${numRepeticoes} tomas</strong> (uma por mês)`,
                `Sempre no mesmo dia do mês`,
                `Duração: ${numRepeticoes} meses`
            ];
            break;
        case 'anual':
            exemplos = [
                `Vai criar <strong>${numRepeticoes} tomas</strong> (uma por ano)`,
                `Sempre no mesmo dia e mês`,
                `Duração: ${numRepeticoes} anos`
            ];
            break;
    }
    
    if (exemplos.length > 0) {
        exemploDiv.innerHTML = exemplos.map(ex => `<li>${ex}</li>`).join('');
    }
}
</script>

<footer>
    <p>&copy; 2025 WOLM</p>
</footer>

</body>
</html>