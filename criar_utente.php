<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['cuidador_id'])) {
    header("Location: login.php");
    exit;
}

$cuidador_id = $_SESSION['cuidador_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $data_nascimento = $_POST['data_nascimento'];
    $peso = $_POST['peso'];
    $altura = $_POST['altura'];

    // Iniciar transação
    $conn->begin_transaction();

    try {
        // Inserir utente
        $stmt = $conn->prepare("
            INSERT INTO utentes (nome, data_nascimento, peso, altura, id_cuidador)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssdii", $nome, $data_nascimento, $peso, $altura, $cuidador_id);
        $stmt->execute();
        
        // Pegar o ID do utente criado
        $utente_id = $conn->insert_id;

        // Se houver medicamentos para adicionar
        if (isset($_POST['medicamentos']) && is_array($_POST['medicamentos'])) {
            foreach ($_POST['medicamentos'] as $medicamento) {
                if (!empty($medicamento['nome_medicamento'])) {
                    $med_nome = $medicamento['nome_medicamento'];
                    $med_dose = $medicamento['dose'] ?? '';
                    $data = $medicamento['data'];
                    $hora = $medicamento['hora'];
                    $recorrencia = $medicamento['recorrencia'] ?? 'unica';
                    $num_repeticoes = isset($medicamento['num_repeticoes']) ? intval($medicamento['num_repeticoes']) : 1;
                    $dias_semana = $medicamento['dias_semana'] ?? [];

                    // Adicionar dose ao nome se fornecida
                    if (!empty($med_dose)) {
                        $med_nome = $med_nome . " - " . $med_dose;
                    }

                    // Limitar repetições
                    if ($num_repeticoes > 365) $num_repeticoes = 365;
                    if ($num_repeticoes < 1) $num_repeticoes = 1;

                    $data_hora_base = $data . " " . $hora;
                    
                    $stmt_med = $conn->prepare("INSERT INTO medicamentos (id_utente, nome, data_hora) VALUES (?, ?, ?)");

                    if ($recorrencia === 'unica') {
                        // Apenas uma toma
                        $stmt_med->bind_param("iss", $utente_id, $med_nome, $data_hora_base);
                        $stmt_med->execute();
                    } elseif ($recorrencia === 'semanal' && !empty($dias_semana)) {
                        // Recorrência semanal com dias específicos
                        $dateTime = new DateTime($data_hora_base);
                        $contador_tomas = 0;
                        $max_iteracoes = $num_repeticoes * 10;
                        $iteracao = 0;
                        
                        while ($contador_tomas < $num_repeticoes && $iteracao < $max_iteracoes) {
                            $dia_atual = (int)$dateTime->format('w');
                            
                            if (in_array($dia_atual, $dias_semana)) {
                                $data_hora_atual = $dateTime->format('Y-m-d H:i:s');
                                $stmt_med->bind_param("iss", $utente_id, $med_nome, $data_hora_atual);
                                $stmt_med->execute();
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
                            
                            $stmt_med->bind_param("iss", $utente_id, $med_nome, $data_hora_atual);
                            $stmt_med->execute();
                            
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
                }
            }
        }

        // Confirmar transação
        $conn->commit();
        
        $_SESSION['mensagem'] = "Utente '$nome' criado com sucesso!";
        header("Location: painel_cuidador.php");
        exit;

    } catch (Exception $e) {
        // Reverter em caso de erro
        $conn->rollback();
        $erro = "Erro ao criar utente: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Utente - WOLM</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body>

<header>
    <h1>WOLM</h1>
</header>

<main class="painel-container">

    <div class="perfil-container">
        <h2>Criar Novo Utente</h2>

        <?php if (!empty($erro)): ?>
            <div class="perfil-erro">✗ <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO CRIAR UTENTE -->
        <form method="POST" class="perfil-form" id="formCriarUtente">

            <label>Nome</label>
            <input type="text" name="nome" required placeholder="Ex: Maria Silva">

            <label>Data de nascimento</label>
            <input type="date" name="data_nascimento" required>

            <label>Peso (kg)</label>
            <input type="number" step="0.1" name="peso" required placeholder="Ex: 65.5">

            <label>Altura (cm)</label>
            <input type="number" step="0.1" name="altura" required placeholder="Ex: 165">

            <button type="submit" class="btn-verde">
                Guardar utente
            </button>

        </form>

        <hr style="margin:2rem 0; border: none; height: 2px; background: #e8f5e9;">

        <!-- SEÇÃO ADICIONAR MEDICAMENTOS -->
        <div class="secao-adicionar">
            <h3 class="titulo-form">➕ Adicionar Medicamentos (Opcional)</h3>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                Pode adicionar medicamentos agora ou depois na página de edição.
            </p>

            <button type="button" class="btn-verde" onclick="adicionarMedicamento()">
                ➕ Adicionar medicamento
            </button>

            <div id="medicamentos-container" style="margin-top: 1.5rem;">
                <!-- Medicamentos serão adicionados aqui -->
            </div>
        </div>

        <br>
        <a href="painel_cuidador.php" class="botao outline">⬅ Voltar ao painel</a>
    </div>

</main>

<script>
let contadorMedicamentos = 0;

function adicionarMedicamento() {
    contadorMedicamentos++;
    
    const container = document.getElementById('medicamentos-container');
    
    const medicamentoDiv = document.createElement('div');
    medicamentoDiv.className = 'medicamento-form-wrapper';
    medicamentoDiv.id = 'medicamento-' + contadorMedicamentos;
    
    medicamentoDiv.innerHTML = `
        <div class="form-medicamento-wrapper" style="display: block; background: #f9f9f9; padding: 1.5rem; border-radius: 8px; border: 2px solid #e8f5e9; margin-bottom: 1rem; position: relative;">
            
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: #1b5e20; font-size: 1.5rem;">💊 Medicamento</h3>
            
            <div class="perfil-form" style="margin: 0;">
                <label>Nome do medicamento</label>
                <input type="text" 
                       name="medicamentos[${contadorMedicamentos}][nome_medicamento]" 
                       required 
                       placeholder="Ex: Paracetamol">
                
                <label>Dose</label>
                <input type="text" 
                       name="medicamentos[${contadorMedicamentos}][dose]" 
                       placeholder="Ex: 500mg, 1 comprimido, 2ml...">
                
                <div class="form-row">
                    <div class="form-col">
                        <label>Data de início</label>
                        <input type="date" 
                               name="medicamentos[${contadorMedicamentos}][data]" 
                               required>
                    </div>
                    <div class="form-col">
                        <label>Hora</label>
                        <input type="time" 
                               name="medicamentos[${contadorMedicamentos}][hora]" 
                               required>
                    </div>
                </div>
                
                <label>Recorrência</label>
                <select name="medicamentos[${contadorMedicamentos}][recorrencia]" 
                        id="recorrencia-${contadorMedicamentos}" 
                        onchange="toggleRecorrencia(${contadorMedicamentos})" 
                        class="select-recorrencia">
                    <option value="unica">Toma única</option>
                    <option value="diaria">Diária (todos os dias)</option>
                    <option value="semanal">Semanal (todas as semanas)</option>
                    <option value="mensal">Mensal (todos os meses)</option>
                    <option value="anual">Anual (todos os anos)</option>
                </select>
                
                <div id="opcoes-recorrencia-${contadorMedicamentos}" style="display:none;">
                    <div class="info-recorrencia">
                        <p><strong>ℹ️ Como funciona:</strong></p>
                        <ul id="exemplo-recorrencia-${contadorMedicamentos}">
                            <!-- Preenchido via JavaScript -->
                        </ul>
                    </div>
                    
                    <label>Quantas vezes tomar?</label>
                    <input type="number" 
                           name="medicamentos[${contadorMedicamentos}][num_repeticoes]" 
                           id="num-repeticoes-${contadorMedicamentos}" 
                           min="1" 
                           max="365" 
                           value="30" 
                           placeholder="Ex: 30" 
                           onchange="atualizarExemplo(${contadorMedicamentos})">
                    
                    <div class="form-col" id="dia-semana-wrapper-${contadorMedicamentos}" style="display:none; margin-top:1rem;">
                        <label>Escolher dias da semana</label>
                        <div class="dias-semana-checkboxes">
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="1" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Seg</span>
                            </label>
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="2" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Ter</span>
                            </label>
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="3" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Qua</span>
                            </label>
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="4" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Qui</span>
                            </label>
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="5" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Sex</span>
                            </label>
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="6" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Sáb</span>
                            </label>
                            <label class="checkbox-dia">
                                <input type="checkbox" name="medicamentos[${contadorMedicamentos}][dias_semana][]" value="0" onchange="atualizarExemplo(${contadorMedicamentos})">
                                <span>Dom</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(medicamentoDiv);
}

function removerMedicamento(id) {
    if (confirm('Tem a certeza que deseja remover este medicamento?')) {
        const elemento = document.getElementById('medicamento-' + id);
        if (elemento) {
            elemento.remove();
        }
    }
}

function toggleRecorrencia(id) {
    const recorrencia = document.getElementById('recorrencia-' + id).value;
    const opcoesDiv = document.getElementById('opcoes-recorrencia-' + id);
    const diaSemanaWrapper = document.getElementById('dia-semana-wrapper-' + id);
    
    if (recorrencia === 'unica') {
        opcoesDiv.style.display = 'none';
        diaSemanaWrapper.style.display = 'none';
    } else {
        opcoesDiv.style.display = 'block';
        
        if (recorrencia === 'semanal') {
            diaSemanaWrapper.style.display = 'block';
        } else {
            diaSemanaWrapper.style.display = 'none';
        }
        
        atualizarExemplo(id);
    }
}

function atualizarExemplo(id) {
    const recorrencia = document.getElementById('recorrencia-' + id).value;
    const numRepeticoes = document.getElementById('num-repeticoes-' + id).value || 30;
    const exemploDiv = document.getElementById('exemplo-recorrencia-' + id);
    
    const diasSelecionados = Array.from(document.querySelectorAll(`input[name="medicamentos[${id}][dias_semana][]"]:checked`))
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