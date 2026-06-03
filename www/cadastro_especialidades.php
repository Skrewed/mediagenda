<?php
require_once("conexao.php");

session_start();
if (!isset($_SESSION['cod_usuario'])) {
    header("Location: login.php");
    exit;
}

$cod_usuario = intval($_SESSION['cod_usuario']);
$nomeUsuario = "";
$emailUsuario = "";
$perfilUsuario = "";
$pageError = '';

$sql = "SELECT * FROM usuario WHERE cod_usuario = " . $cod_usuario;

$result = mysqli_query($conexao_bd, $sql);
if ($result && $consulta = mysqli_fetch_assoc($result)) {
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
    $perfilUsuario = $consulta["perfil"];
} elseif ($result === false) {
    $pageError = mysqli_error($conexao_bd);
}

$operadorNome  = $nomeUsuario;
$operadorEmail = $emailUsuario;

/* ============================================================
   CARREGAR LISTA CBO DO JSON
============================================================ */
$especialidadesList = [];
$sqlCbo = "SELECT id, nome, cbo FROM especialidades ORDER BY nome";
$resultCbo = mysqli_query($conexao_bd, $sqlCbo);

if ($resultCbo && mysqli_num_rows($resultCbo) > 0) {
    while ($rowCbo = mysqli_fetch_assoc($resultCbo)) {
        $opcaoFormatada = $rowCbo['cbo'] . ' - ' . $rowCbo['nome'];
        $especialidadesList[] = [
            'id' => $rowCbo['id'],
            'cod_cbo' => $rowCbo['cbo'],
            'nome_cbo' => $rowCbo['nome']
        ];
    }
} else {
    // Fallback em caso de erro (mesmo que raro)
    $especialidadesList = []; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? trim($_POST['acao']) : '';
    $acao = strtolower($acao);
    $redirect = 'cadastro_especialidades.php';

    try {
        if ($acao === 'novo' || $acao === 'editar') {
            $nome = trim($_POST['nome'] ?? '');
            $cbo  = trim($_POST['cbo'] ?? '');
            $status = isset($_POST['status']) && $_POST['status'] === 'Inativo' ? 'Inativo' : 'Ativo'; 
            $id   = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($nome === '' || $cbo === '') {
                throw new Exception('Selecione uma especialidade válida da lista CBO.');
            }
            
            $nomeEsc = mysqli_real_escape_string($conexao_bd, $nome);
            $cboEsc  = mysqli_real_escape_string($conexao_bd, $cbo); 
            
            // --- NOVA VALIDAÇÃO: VERIFICA SE JÁ EXISTE ---
            if ($acao === 'editar') {
                $sqlCheck = "
                    SELECT id
                    FROM especialidades
                    WHERE (cbo = '$cboEsc' OR nome = '$nomeEsc')
                    AND id != $id
                ";
            } else {
                $sqlCheck = "
                    SELECT id
                    FROM especialidades
                    WHERE cbo = '$cboEsc'
                    OR nome = '$nomeEsc'
                ";
            }
            $resultCheck = mysqli_query($conexao_bd, $sqlCheck);
            if (mysqli_num_rows($resultCheck) > 0) {
                throw new Exception('Esta especialidade (CBO: ' . $cboEsc . ') já foi adicionada anteriormente.');
            }
            // ---------------------------------------------

            // --- VALIDAÇÃO: NÃO DEIXA INATIVAR SE HOUVER MÉDICOS ATIVOS VINCULADOS ---
            if ($acao === 'editar' && $status === 'Inativo') {
                $sqlCheckAtivos = "
                    SELECT COUNT(*) AS total
                    FROM medico_especialidades me
                    JOIN medicos m ON m.id = me.medico_id
                    WHERE me.especialidade_id = $id
                    AND m.status = 'Ativo'
                ";
                $resultCheckAtivos = mysqli_query($conexao_bd, $sqlCheckAtivos);
                if ($resultCheckAtivos) {
                    $rowCheckAtivos = mysqli_fetch_assoc($resultCheckAtivos);
                    if (intval($rowCheckAtivos['total']) > 0) {
                        throw new Exception('Não é possível inativar esta especialidade enquanto houver médicos ativos vinculados a ela.');
                    }
                }
            }
            // -----------------------------------------------
            
            if ($acao === 'novo') {
                $sql = "INSERT INTO especialidades (nome, cbo, status) VALUES ('" . $nomeEsc . "', '" . $cboEsc . "', '" . $status . "')";
                $resultExec = mysqli_query($conexao_bd, $sql);
                if (!$resultExec) {
                    throw new Exception('Não foi possível criar a especialidade. ' . mysqli_error($conexao_bd));
                }
                $redirect .= '?alert=success&acao=novo';
            } elseif ($acao === 'editar') {
                if ($id <= 0) {
                    throw new Exception('Especialidade inválida para edição.');
                }
                $sql = "UPDATE especialidades SET nome = '" . $nomeEsc . "', cbo = '" . $cboEsc . "', status = '" . $status . "' WHERE id = " . $id;
                $resultExec = mysqli_query($conexao_bd, $sql);
                if (!$resultExec) {
                    throw new Exception('Não foi possível atualizar a especialidade. ' . mysqli_error($conexao_bd));
                }
                $redirect .= '?alert=success&acao=editar';
            }
        } elseif ($acao === 'excluir') {

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if ($id <= 0) {
                throw new Exception('Especialidade inválida para exclusão.');
            }

            // Verifica se existem vínculos com médicos ou agendamentos
            $sqlCheck = "
                SELECT
                (
                    SELECT COUNT(*)
                    FROM medico_especialidades
                    WHERE especialidade_id = $id
                ) +
                (
                    SELECT COUNT(*)
                    FROM agendamentos
                    WHERE especialidade_id = $id
                ) AS total
            ";

            $resultCheck = mysqli_query($conexao_bd, $sqlCheck);

            if (!$resultCheck) {
                throw new Exception('Erro ao validar vínculos da especialidade. ' . mysqli_error($conexao_bd));
            }

            $rowCheck = mysqli_fetch_assoc($resultCheck);

            if ((int)$rowCheck['total'] > 0) {
                throw new Exception('Esta especialidade possui vínculos com médicos ou agendamentos e não pode ser excluída.');
            }

            $sql = "DELETE FROM especialidades WHERE id = " . $id;

            $resultExec = mysqli_query($conexao_bd, $sql);

            if (!$resultExec) {
                throw new Exception('Não foi possível excluir a especialidade. ' . mysqli_error($conexao_bd));
            }

            $redirect .= '?alert=success&acao=excluir';
        }
    } catch (Exception $e) {
        $redirect .= '?alert=error&message=' . rawurlencode($e->getMessage());
    }

    header("Location: " . $redirect);
    exit;
}

$filtroBusca = trim(isset($_GET['busca']) ? $_GET['busca'] : '');

$especialidades = array();
$where = array();
$pageError = '';

if ($filtroBusca !== '') {
    if (strpos($filtroBusca, ' - ') !== false) {
        $partesBusca = explode(' - ', $filtroBusca);
        $cboBusca = mysqli_real_escape_string($conexao_bd, $partesBusca[0]);
        $where[] = "e.cbo = '" . $cboBusca . "'";
    } else {
        $buscaEsc = mysqli_real_escape_string($conexao_bd, $filtroBusca);
        $where[] = "(e.nome LIKE '%" . $buscaEsc . "%' OR e.cbo LIKE '%" . $buscaEsc . "%')";
    }
}

$sqlConsulta = "SELECT e.id, e.nome, e.cbo, e.status, e.data_criacao,
                       COUNT(DISTINCT me.medico_id) AS medico_count,
                       COUNT(DISTINCT a.id) AS agenda_count
                FROM especialidades e
                LEFT JOIN medico_especialidades me
                    ON me.especialidade_id = e.id
                LEFT JOIN agendamentos a
                    ON a.especialidade_id = e.id";
if (count($where) > 0) {
    $sqlConsulta .= " WHERE " . implode(' AND ', $where);
}
$sqlConsulta .= " GROUP BY e.id, e.nome, e.cbo, e.status, e.data_criacao ORDER BY e.nome ASC";

try {
    $resultEsp = mysqli_query($conexao_bd, $sqlConsulta);
    if ($resultEsp) {
        while ($row = mysqli_fetch_assoc($resultEsp)) {
            $especialidades[] = $row;
        }
    } else {
        $pageError = mysqli_error($conexao_bd);
    }
} catch (Exception $ex) {
    $pageError = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Cadastro de Especialidades</title>

    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <datalist id="listaSugestoesCboCombinado">
    <?php foreach ($especialidadesList as $itemCbo): ?>
        <option value="<?php echo htmlspecialchars($itemCbo['cod_cbo'] . ' - ' . $itemCbo['nome_cbo']); ?>">
    <?php endforeach; ?>
</datalist>

    <nav class="navbar-topo d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-sanduiche" id="btnSanduiche" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand mb-0 d-flex align-items-center" href="principal.php">
                <i class="fa-solid fa-stethoscope"></i>
                <span>MediAgenda</span>
            </a>
        </div>

        <div class="dropdown">
            <button class="operador-toggle" type="button" id="dropdownOperador" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i>
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($operadorNome) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-operador" aria-labelledby="dropdownOperador">
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($operadorNome) ?></a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($operadorEmail) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="config_usuarios.php"><i class="fa-solid fa-gear"></i>Configurações</a></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Sair</a></li>
            </ul>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ativo" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a>
            </li>
            <?php if ($perfilUsuario == "admin") { ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin_usuarios.php">
                        <i class="fa-solid fa-users"></i>
                        Administração de Usuários
                    </a>
                </li>
            <?php } ?>
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">

        <!-- Cabeçalho da página -->
        <div class="page-header">
            <h2><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</h2>
            <button id="btnNovaEspecialidade" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFormEspecialidade">
                <i class="fa-solid fa-plus me-1"></i> Nova Especialidade
            </button>
        </div>

        <div class="card-pagina">
            <div class="card-titulo"><i class="fa-solid fa-magnifying-glass"></i> Filtros</div>
            <form method="GET" action="cadastro_especialidades.php" id="formFiltro">
                <div class="row g-3">
                    <div class="col-12">
                        <input type="text" class="form-control form-control-sm" id="filtroBusca" name="busca" 
                               list="listaFiltroCbo" placeholder="Digite para buscar por Especialidade / CBO..." 
                               value="<?php echo htmlspecialchars($filtroBusca); ?>" autocomplete="off">
                        
                        <datalist id="listaFiltroCbo">
    <?php foreach ($especialidadesList as $itemCbo): 
        $opcaoFormatada = $itemCbo['cod_cbo'] . ' - ' . $itemCbo['nome_cbo'];
    ?>
        <option value="<?php echo htmlspecialchars($opcaoFormatada); ?>">
    <?php endforeach; ?>
</datalist>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar
                    </button>
                    <a href="cadastro_especialidades.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-xmark me-1"></i> Limpar
                    </a>
                </div>
            </form>
        </div>

        
        <div class="card-pagina">
            <div class="card-titulo d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-table-list"></i> Especialidades</span>
                <span id="contadorRegistros" class="text-muted" style="font-size:0.82rem; font-weight:400;">
                    <?php echo count($especialidades) ?> registo(s) encontrado(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="tabela-especialidades">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>CBO</th>
                            <th>Status</th>
                            <th>Adicionado em</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($especialidades)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-list-dots me-2"></i>Nenhuma especialidade encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($especialidades as $esp): ?>
                                <?php 
                                    $classeStatus = $esp['status'] === 'Ativo' ? 'badge-ativo' : 'badge-inativo';
                                ?>
                                <tr>
                                    <td class="text-muted"><?php echo intval($esp['id']) ?></td>
                                    <td><?php echo htmlspecialchars($esp['nome']) ?></td>
                                    <td><?php echo htmlspecialchars($esp['cbo'] ?? '-') ?></td>
                                    <td><span class="badge-status <?php echo $classeStatus ?>"><?php echo htmlspecialchars($esp['status']) ?></span></td>
                                    <td class="text-muted">
                                        <?php echo !empty($esp['data_criacao']) ? date('d/m/Y H:i', strtotime($esp['data_criacao'])) : '-'; ?>
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button class="btn btn-sm btn-icon-sm btn-outline-primary btn-editar"
                                                type="button"
                                                data-id="<?php echo intval($esp['id']) ?>"
                                                data-nome="<?php echo htmlspecialchars($esp['nome']) ?>"
                                                data-cbo="<?php echo htmlspecialchars($esp['cbo'] ?? '') ?>"
                                                data-status="<?php echo htmlspecialchars($esp['status']) ?>"
                                                title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php $referencias = intval($esp['medico_count']) + intval($esp['agenda_count']); ?>
                                        <?php if ($referencias === 0): ?>
                                            <button class="btn btn-sm btn-icon-sm btn-outline-danger btn-excluir"
                                                    type="button"
                                                    data-id="<?php echo intval($esp['id']) ?>"
                                                    data-nome="<?php echo htmlspecialchars($esp['nome']) ?>"
                                                    title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-icon-sm btn-outline-secondary" type="button" disabled
                                                    title="Esta especialidade está vinculada a registos e não pode ser excluída">
                                                <i class="fa-solid fa-link"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade modal-form" id="modalFormEspecialidade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFormTitulo">
                        <i class="fa-solid fa-plus me-2"></i>Nova Especialidade
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formEspecialidade" action="cadastro_especialidades.php" method="POST">
                    <input type="hidden" name="acao" id="formAcao" value="novo">
                    <input type="hidden" name="id" id="formId" value="">
                    <input type="hidden" name="nome" id="formNomeHidden">
                    <input type="hidden" name="cbo" id="formCboHidden">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="formBuscaCbo" class="form-label">Especialidade / CBO <span class="text-danger">*</span></label>
                            <select class="form-select" id="formBuscaCbo" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($especialidadesList as $itemCbo): ?> 
                                    <option value="<?php echo htmlspecialchars($itemCbo['cod_cbo'] . ' - ' . $itemCbo['nome_cbo']); ?>">
                                        <?php echo htmlspecialchars($itemCbo['cod_cbo'] . ' - ' . $itemCbo['nome_cbo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="formStatus" class="form-label">Status</label>
                            <select class="form-select" id="formStatus" name="status">
                                <option value="Ativo">Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="salvarEspecialidade()">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="formExcluir" action="cadastro_especialidades.php" method="POST" style="display:none;">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" id="formExcluirId" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <script>
        var btnSanduiche      = document.getElementById('btnSanduiche');
        var sidebar           = document.getElementById('sidebar');
        var conteudoPrincipal = document.getElementById('conteudoPrincipal');
        var sidebarOverlay    = document.getElementById('sidebarOverlay');
        var btnNovaEspecialidade = document.getElementById('btnNovaEspecialidade');
        var modalFormEspecialidadeEl = document.getElementById('modalFormEspecialidade');
        var modalFormEspecialidade   = new bootstrap.Modal(modalFormEspecialidadeEl);
        var formEspecialidade        = document.getElementById('formEspecialidade');
        
        var formBuscaCbo   = document.getElementById('formBuscaCbo');
        var formNomeHidden = document.getElementById('formNomeHidden');
        var formCboHidden  = document.getElementById('formCboHidden');

        // 1. Array com os CBOs já cadastrados no banco de dados
        var cbosRegistrados = <?php echo json_encode(array_column($especialidades, 'cbo')); ?>;

        // 2. Função para ocultar/exibir os CBOs na lista do Modal
        function atualizarOpcoesCbo(cboPermitido = null) {
            var options = formBuscaCbo.options;
            for (var i = 1; i < options.length; i++) { // pula o índice 0 ("Selecione...")
                var val = options[i].value;
                var cboOption = val.split(' - ')[0]; // Pega apenas o número do CBO
                
                if (cbosRegistrados.includes(cboOption) && cboOption !== cboPermitido) {
                    options[i].style.display = 'none';
                    options[i].disabled = true;
                } else {
                    options[i].style.display = '';
                    options[i].disabled = false;
                }
            }
        }

        // Reset do formulário quando abre modal para nova especialidade
        modalFormEspecialidadeEl.addEventListener('show.bs.modal', function() {
            // Só reseta se for uma nova especialidade (não edição)
            var btnClicado = document.activeElement;
            if (btnClicado && btnClicado.id === 'btnNovaEspecialidade') {
                resetarFormularioEspecialidade();
            }
        });

        btnSanduiche.addEventListener('click', function() {
            if (window.innerWidth <= 991.98) {
                sidebar.classList.toggle('aberta');
                sidebarOverlay.classList.toggle('ativo');
            } else {
                sidebar.classList.toggle('oculta');
                conteudoPrincipal.classList.toggle('expandido');
            }
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('aberta');
            sidebarOverlay.classList.remove('ativo');
        });

        formBuscaCbo.addEventListener('change', function() {
            var val = this.value;
            if (val === '') {
                formCboHidden.value = '';
                formNomeHidden.value = '';
            } else {
                var parts = val.split(' - ');
                formCboHidden.value = parts[0];
                formNomeHidden.value = parts.slice(1).join(' - '); 
            }
        });

        function resetarFormularioEspecialidade() {
            document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Nova Especialidade';
            document.getElementById('formAcao').value = 'novo';
            document.getElementById('formId').value = '';
            formBuscaCbo.value = '';
            formCboHidden.value = '';
            formNomeHidden.value = '';
            document.getElementById('formStatus').value = 'Ativo';
            
            // 3. Atualiza a lista escondendo os já cadastrados no banco (não mais JSON)
            atualizarOpcoesCbo(null);
        }

        document.querySelector('.tabela-especialidades').addEventListener('click', function(e) {
            var btnEditar = e.target.closest('.btn-editar');
            var btnExcluir = e.target.closest('.btn-excluir');

            if (btnEditar) {
                document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar Especialidade';
                document.getElementById('formAcao').value = 'editar';
                document.getElementById('formId').value = btnEditar.dataset.id;
                
                var cbo_salvo = btnEditar.dataset.cbo;
                var nome_salvo = btnEditar.dataset.nome;
                var status_salvo = btnEditar.dataset.status;
                
                formCboHidden.value = cbo_salvo; 
                formNomeHidden.value = nome_salvo;
                document.getElementById('formStatus').value = status_salvo;
                
                // 4. Atualiza a lista permitindo o CBO que está sendo editado aparecer
                atualizarOpcoesCbo(cbo_salvo);
                
                if (cbo_salvo && cbo_salvo !== '-') {
                    formBuscaCbo.value = cbo_salvo + ' - ' + nome_salvo;
                } else {
                    formBuscaCbo.value = '';
                }
                modalFormEspecialidade.show();
                return;
            }

            if (btnExcluir) {
                Swal.fire({
                    title: 'Excluir especialidade?',
                    html: 'Deseja excluir <strong>' + btnExcluir.dataset.nome + '</strong>?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Voltar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        document.getElementById('formExcluirId').value = btnExcluir.dataset.id;
                        document.getElementById('formExcluir').submit();
                    }
                });
            }
        });

        function salvarEspecialidade() {
            if (formBuscaCbo.value === '') {
                Swal.fire('Atenção', 'Selecione uma especialidade!', 'warning');
                return;
            }
            formEspecialidade.submit();
        }

        // Verificar alertas de sucesso/erro na URL
        document.addEventListener('DOMContentLoaded', function() {
            var urlParams = new URLSearchParams(window.location.search);
            var alertType = urlParams.get('alert');
            var message = urlParams.get('message');
            var acao = urlParams.get('acao');
            
            if (alertType === 'success') {
                var titulo = 'Sucesso!';
                if (acao === 'novo') {
                    titulo = 'Especialidade criada!';
                } else if (acao === 'editar') {
                    titulo = 'Especialidade atualizada!';
                } else if (acao === 'excluir') {
                    titulo = 'Especialidade excluída!';
                }
                Swal.fire(titulo, message || 'Operação realizada com sucesso.', 'success').then(function() {
                    // Recarrega a página limpa dos parâmetros de alerta
                    window.location.href = 'cadastro_especialidades.php';
                });
            } else if (alertType === 'error') {
                Swal.fire('Erro', message || 'Ocorreu um erro durante a operação.', 'error');
            }
        });
    </script>
</body>
</html>