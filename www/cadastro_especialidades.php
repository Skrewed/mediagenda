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
$pageError = '';
$sql = "SELECT * FROM usuario WHERE cod_usuario = " . $cod_usuario;
$result = mysqli_query($conexao_bd, $sql);
if ($result && $consulta = mysqli_fetch_assoc($result)) {
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
} elseif ($result === false) {
    $pageError = mysqli_error($conexao_bd);
}

$operadorNome  = $nomeUsuario;
$operadorEmail = $emailUsuario;

/* ============================================================
   CARREGAR LISTA CBO DO JSON
============================================================ */
$jsonCbo = file_get_contents('lista_cbo.json');
$listaCbo = json_decode($jsonCbo, true);
if (!$listaCbo) {
    $listaCbo = []; // Fallback caso o arquivo não seja lido
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? trim($_POST['acao']) : '';
    $acao = strtolower($acao);
    $redirect = 'cadastro_especialidades.php';

    try {
        if ($acao === 'novo' || $acao === 'editar') {
            $nome = trim($_POST['nome'] ?? '');
            $cbo  = trim($_POST['cbo'] ?? ''); // NOVO CAMPO
            $id   = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($nome === '') {
                throw new Exception('Nome da especialidade é obrigatório.');
            }
            
            $nomeEsc = mysqli_real_escape_string($conexao_bd, $nome);
            $cboEsc  = mysqli_real_escape_string($conexao_bd, $cbo); // NOVO CAMPO
            
            if ($acao === 'novo') {
                $sql = "INSERT INTO especialidades (nome, cbo) VALUES ('" . $nomeEsc . "', '" . $cboEsc . "')";
                $resultExec = mysqli_query($conexao_bd, $sql);
                if (!$resultExec) {
                    throw new Exception('Não foi possível criar a especialidade. ' . mysqli_error($conexao_bd));
                }
                $redirect .= '?alert=success&acao=novo';
            } elseif ($acao === 'editar') {
                if ($id <= 0) {
                    throw new Exception('Especialidade inválida para edição.');
                }
                $sql = "UPDATE especialidades SET nome = '" . $nomeEsc . "', cbo = '" . $cboEsc . "' WHERE id = " . $id;
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

$filtroNome = trim(isset($_GET['nome']) ? $_GET['nome'] : '');
$filtroCbo  = trim(isset($_GET['cbo']) ? $_GET['cbo'] : ''); // NOVO FILTRO

$especialidades = array();
$where = array();
$pageError = '';

if ($filtroNome !== '') {
    $nomeEsc = mysqli_real_escape_string($conexao_bd, $filtroNome);
    $where[] = "e.nome LIKE '%" . $nomeEsc . "%'";
}

if ($filtroCbo !== '') {
    $cboEsc = mysqli_real_escape_string($conexao_bd, $filtroCbo);
    $where[] = "e.cbo = '" . $cboEsc . "'";
}

// ADICIONADO e.cbo NO SELECT
$sqlConsulta = "SELECT e.id, e.nome, e.cbo, COUNT(DISTINCT m.id) AS medico_count, COUNT(DISTINCT a.id) AS agenda_count"
             . " FROM especialidades e"
             . " LEFT JOIN medicos m ON m.especialidade_id = e.id"
             . " LEFT JOIN agendamentos a ON a.especialidade_id = e.id";
if (count($where) > 0) {
    $sqlConsulta .= " WHERE " . implode(' AND ', $where);
}
$sqlConsulta .= " GROUP BY e.id, e.nome, e.cbo ORDER BY e.nome ASC";

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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Cadastro de Especialidades</title>

    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --azul-primario: #0d6efd;
            --azul-escuro:   #084298;
            --azul-claro:    #e7f1ff;
            --cinza-fundo:   #f5f7fa;
            --cinza-borda:   #e3e6ea;
            --texto-escuro:  #1f2d3d;
            --sidebar-larg:  250px;
        }

        body {
            background-color: var(--cinza-fundo);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: var(--texto-escuro);
            overflow-x: hidden;
        }

        .navbar-topo {
            background: linear-gradient(90deg, var(--azul-primario) 0%, var(--azul-escuro) 100%);
            height: 60px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
        }
        .navbar-topo .navbar-brand {
            color: #fff;
            font-weight: 600;
            font-size: 1.25rem;
        }
        .navbar-topo .navbar-brand i {
            margin-right: 8px;
        }
        .btn-sanduiche {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.3rem;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-sanduiche:hover {
            background: rgba(255,255,255,0.15);
        }
        .operador-toggle {
            background: transparent;
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 30px;
            transition: background 0.2s;
        }
        .operador-toggle:hover, .operador-toggle:focus {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .operador-toggle i.fa-circle-user {
            font-size: 1.6rem;
        }
        .dropdown-menu-operador {
            min-width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            border: none;
        }
        .dropdown-menu-operador .dropdown-item i {
            width: 22px;
            color: var(--azul-primario);
        }

        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: var(--sidebar-larg);
            height: calc(100vh - 60px);
            background: #fff;
            border-right: 1px solid var(--cinza-borda);
            padding: 20px 0;
            transition: transform 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
        }
        .sidebar.oculta {
            transform: translateX(calc(var(--sidebar-larg) * -1));
        }
        .sidebar .nav-link {
            color: var(--texto-escuro);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar .nav-link i {
            width: 22px;
            color: var(--azul-primario);
            font-size: 1.05rem;
        }
        .sidebar .nav-link:hover {
            background: var(--azul-claro);
            border-left-color: var(--azul-primario);
            color: var(--azul-escuro);
        }
        .sidebar .nav-link.ativo {
            background: var(--azul-claro);
            border-left-color: var(--azul-primario);
            color: var(--azul-escuro);
            font-weight: 600;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 60px; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1010;
        }
        .sidebar-overlay.ativo {
            display: block;
        }

        .conteudo-principal {
            margin-top: 60px;
            margin-left: var(--sidebar-larg);
            padding: 25px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 60px);
        }
        .conteudo-principal.expandido {
            margin-left: 0;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(calc(var(--sidebar-larg) * -1));
            }
            .sidebar.aberta {
                transform: translateX(0);
                box-shadow: 2px 0 12px rgba(0,0,0,0.15);
            }
            .conteudo-principal {
                margin-left: 0;
            }
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }
        .page-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--azul-escuro);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header h2 i {
            color: var(--azul-primario);
        }

        .card-pagina {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--cinza-borda);
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .card-pagina .card-titulo {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--azul-escuro);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-pagina .card-titulo i {
            color: var(--azul-primario);
        }

        .tabela-especialidades {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.88rem;
        }
        .tabela-especialidades thead th {
            background: var(--azul-claro);
            color: var(--azul-escuro);
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 2px solid var(--cinza-borda);
            white-space: nowrap;
        }
        .tabela-especialidades tbody tr:hover {
            background: #f8fbff;
        }
        .tabela-especialidades tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--cinza-borda);
            vertical-align: middle;
        }
        .tabela-especialidades tbody tr:last-child td {
            border-bottom: none;
        }

        .modal-form .modal-header {
            background: var(--azul-primario);
            color: #fff;
        }
        .modal-form .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-form label {
            font-weight: 500;
            font-size: 0.88rem;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

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
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear"></i>Configurações</a></li>
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
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">

        <div class="page-header">
            <h2><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</h2>
            <button id="btnNovaEspecialidade" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFormEspecialidade">
                <i class="fa-solid fa-plus me-1"></i> Nova Especialidade
            </button>
        </div>

        <div class="card-pagina">
            <div class="card-titulo"><i class="fa-solid fa-magnifying-glass"></i> Filtros</div>
            <form method="GET" action="cadastro_especialidades.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="filtroCbo">CBO</label>
                        <select class="form-select form-select-sm" id="filtroCbo" name="cbo">
                            <option value="" data-nome="">Todos</option>
                            <?php foreach ($listaCbo as $itemCbo): ?>
                                <option value="<?php echo htmlspecialchars($itemCbo['cod_cbo']); ?>" 
                                    data-nome="<?php echo htmlspecialchars($itemCbo['nome_cbo']); ?>"
                                    <?php echo ($filtroCbo === $itemCbo['cod_cbo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($itemCbo['cod_cbo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="filtroNome">Nome da Especialidade</label>
                        <input type="text" class="form-control form-control-sm" id="filtroNome"
                               name="nome" placeholder="Ex: Cardiologia"
                               value="<?php echo htmlspecialchars($filtroNome) ?>">
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
                    <?php echo count($especialidades) ?> registro(s) encontrado(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="tabela-especialidades">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>CBO</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($especialidades)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-list-dots me-2"></i>Nenhuma especialidade encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($especialidades as $esp): ?>
                                <tr>
                                    <td class="text-muted"><?php echo intval($esp['id']) ?></td>
                                    
                                    <td><?php echo htmlspecialchars($esp['nome']) ?></td>
                                    
                                    <td><?php echo htmlspecialchars($esp['cbo'] ?? '-') ?></td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-editar"
                                                type="button"
                                                data-id="<?php echo intval($esp['id']) ?>"
                                                data-nome="<?php echo htmlspecialchars($esp['nome']) ?>"
                                                data-cbo="<?php echo htmlspecialchars($esp['cbo'] ?? '') ?>"
                                                title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php $referencias = intval($esp['medico_count']) + intval($esp['agenda_count']); ?>
                                        <?php if ($referencias === 0): ?>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-excluir"
                                                    type="button"
                                                    data-id="<?php echo intval($esp['id']) ?>"
                                                    data-nome="<?php echo htmlspecialchars($esp['nome']) ?>"
                                                    title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2" type="button" disabled
                                                    title="Esta especialidade está vinculada a registros e não pode ser excluída">
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
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="formCbo" class="form-label">Código CBO <span class="text-danger">*</span></label>
                            <select class="form-select" id="formCbo" name="cbo" required>
                                <option value="" data-nome="">Selecione...</option>
                                <?php foreach ($listaCbo as $itemCbo): ?>
                                    <option value="<?php echo htmlspecialchars($itemCbo['cod_cbo']); ?>" data-nome="<?php echo htmlspecialchars($itemCbo['nome_cbo']); ?>">
                                        <?php echo htmlspecialchars($itemCbo['cod_cbo'] . ' - ' . $itemCbo['nome_cbo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="formNome" class="form-label">Nome da especialidade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formNome" name="nome" placeholder="Ex: Médico cardiologista" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
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
        var urlParams = new URLSearchParams(window.location.search);
        var pageAlert = urlParams.get('alert');
        var pageAction = urlParams.get('acao');
        var serverErrorMessage = <?php echo json_encode($pageError); ?>;

        if (serverErrorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Ops, algo deu errado!',
                text: serverErrorMessage,
                confirmButtonText: 'Entendi'
            });
        } else if (pageAlert === 'success') {
            var message = '';
            if (pageAction === 'novo') {
                message = 'Especialidade criada com sucesso!';
            } else if (pageAction === 'editar') {
                message = 'Especialidade atualizada com sucesso!';
            } else if (pageAction === 'excluir') {
                message = 'Especialidade excluída com sucesso!';
            }
            if (message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Tudo certo!',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true
                });
            }
        } else if (pageAlert === 'error') {
            var errorMessage = urlParams.get('message') || 'Ocorreu um erro inesperado.';
            errorMessage = decodeURIComponent(errorMessage);
            Swal.fire({
                icon: 'error',
                title: 'Ops, algo deu errado!',
                text: errorMessage,
                confirmButtonText: 'Entendi'
            });
        }

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

        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('aberta');
                sidebarOverlay.classList.remove('ativo');
            }
        });

        // Evento que preenche o Nome da Especialidade automaticamente ao selecionar o CBO (Filtro)
        document.getElementById('filtroCbo').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var nomeCbo = selectedOption.getAttribute('data-nome');
            var inputNomeFiltro = document.getElementById('filtroNome');
            
            if (nomeCbo) {
                inputNomeFiltro.value = nomeCbo;
            } else {
                inputNomeFiltro.value = ''; // Limpa se voltar para "Todos"
            }
        });

        // Evento que preenche o Nome da Especialidade automaticamente ao selecionar o CBO (Modal de Cadastro)
        document.getElementById('formCbo').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var nomeCbo = selectedOption.getAttribute('data-nome');
            var inputNome = document.getElementById('formNome');
            
            if (nomeCbo) {
                inputNome.value = nomeCbo;
            } else {
                inputNome.value = ''; // Limpa se voltar para "Selecione..."
            }
        });

        function resetarFormularioEspecialidade() {
            document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Nova Especialidade';
            document.getElementById('formAcao').value = 'novo';
            document.getElementById('formId').value = '';
            document.getElementById('formCbo').value = '';
            document.getElementById('formNome').value = ''; 
        }

        if (btnNovaEspecialidade) {
            btnNovaEspecialidade.addEventListener('click', function() {
                resetarFormularioEspecialidade();
                document.getElementById('formCbo').focus();
            });
        }

        document.querySelector('.tabela-especialidades').addEventListener('click', function(e) {
            var btnEditar = e.target.closest('.btn-editar');
            var btnExcluir = e.target.closest('.btn-excluir');

            if (btnEditar) {
                document.getElementById('modalFormTitulo').innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar Especialidade';
                document.getElementById('formAcao').value = 'editar';
                document.getElementById('formId').value = btnEditar.dataset.id;
                document.getElementById('formCbo').value = btnEditar.dataset.cbo; 
                document.getElementById('formNome').value = btnEditar.dataset.nome;
                modalFormEspecialidade.show();
                return;
            }

            if (btnExcluir) {
                Swal.fire({
                    title: 'Excluir especialidade?',
                    html: 'Deseja excluir a especialidade <strong>' + btnExcluir.dataset.nome + '</strong>?',
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
            if (!formEspecialidade.checkValidity()) {
                formEspecialidade.reportValidity();
                return;
            }
            formEspecialidade.submit();
        }
    </script>
</body>
</html>