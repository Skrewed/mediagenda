<?php
session_start();
require_once("conexao.php");

if(!isset($_SESSION['cod_usuario'])){
    header("Location: login.php");
    exit;
}
$cod_usuario = $_SESSION['cod_usuario'];
$nomeUsuario = "";
$emailUsuario = "";
$perfilUsuario = "";
$pageError = '';

$sql = "SELECT * FROM usuario WHERE cod_usuario = " . $cod_usuario;
$result = mysqli_query($conexao_bd, $sql);

if ($result && $consulta = mysqli_fetch_assoc($result)) {
    $nomeUsuario = $consulta["nome"];
    $emailUsuario = $consulta["email"];
    $perfilUsuario = $consulta["perfil"];
} elseif ($result === false) {
    $pageError = mysqli_error($conexao_bd);
}

if ($perfilUsuario != "admin") {
    header("Location: principal.php");
    exit;
}

if (isset($_POST["adicionar"])) {
    $nome = mysqli_real_escape_string($conexao_bd, $_POST["novo_nome"]);
    $email = mysqli_real_escape_string($conexao_bd, $_POST["novo_email"]);
    $username = mysqli_real_escape_string($conexao_bd, $_POST["novo_username"]);
    $perfil = mysqli_real_escape_string(
        $conexao_bd,
        $_POST["novo_perfil"]
    );

    $pass = password_hash($_POST["novo_pass"], PASSWORD_DEFAULT);
    $pass = mysqli_real_escape_string($conexao_bd, $pass);

    $sqlInsert = "
        INSERT INTO usuario (nome, email, username, pass, perfil)
        VALUES ('$nome', '$email', '$username', '$pass', '$perfil')
    ";

    mysqli_query($conexao_bd, $sqlInsert);

    header("Location: admin_usuarios.php?sucesso=adicionar");
    exit;
}

if (isset($_POST["gerar_convite"])) {

    $perfilConvite = mysqli_real_escape_string(
        $conexao_bd,
        $_POST["perfil_convite"]
    );

    $codigoConvite =
        strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    $sqlConvite = "
        INSERT INTO convite_usuario
        (codigo, perfil, usado)
        VALUES
        ('$codigoConvite', '$perfilConvite', 0)
    ";

    if (mysqli_query($conexao_bd, $sqlConvite)) {
        header(
            "Location: admin_usuarios.php?convite=$codigoConvite"
        );
        exit;
    }
}

if (isset($_POST["editar"])) {

    $cod_usuario = intval($_POST["cod_usuario"]);
    $nome = mysqli_real_escape_string($conexao_bd, $_POST["nome"]);
    $email = mysqli_real_escape_string($conexao_bd, $_POST["email"]);
    $username = mysqli_real_escape_string($conexao_bd, $_POST["username"]);
    $pass = trim($_POST["pass"] ?? '');

    $sqlUpdate = "
        UPDATE usuario
        SET
            nome = '$nome',
            email = '$email',
            username = '$username'
    ";

    if ($pass != "") {

        $passHash = password_hash($pass, PASSWORD_DEFAULT);
        $passHash = mysqli_real_escape_string($conexao_bd, $passHash);

        $sqlUpdate .= ",
            pass = '$passHash'
        ";
    }

    $sqlUpdate .= "
        WHERE cod_usuario = $cod_usuario
    ";

    mysqli_query($conexao_bd, $sqlUpdate);

    header("Location: admin_usuarios.php?sucesso=editar");
    exit;
}

if (isset($_GET["excluir"])) {
    $username = mysqli_real_escape_string($conexao_bd, $_GET["excluir"]);

    $sqlDelete = "DELETE FROM usuario WHERE username = '$username'";
    mysqli_query($conexao_bd, $sqlDelete);

    header("Location: admin_usuarios.php?sucesso=excluir");
    exit;
}

$filtroNome = trim($_GET["nome"] ?? '');
$filtroUsername = trim($_GET["username"] ?? '');
$filtroEmail = trim($_GET["email"] ?? '');

$where = array();

if ($filtroNome != '') {
    $nomeEsc = mysqli_real_escape_string($conexao_bd, $filtroNome);
    $where[] = "nome LIKE '%$nomeEsc%'";
}

if ($filtroUsername != '') {
    $usernameEsc = mysqli_real_escape_string($conexao_bd, $filtroUsername);
    $where[] = "username LIKE '%$usernameEsc%'";
}

if ($filtroEmail != '') {
    $emailEsc = mysqli_real_escape_string($conexao_bd, $filtroEmail);
    $where[] = "email LIKE '%$emailEsc%'";
}

$sql = "SELECT cod_usuario, nome, email, username, pass, perfil FROM usuario";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY nome ASC";

$usuariosPorPagina = 10;

$sqlTotal = "SELECT COUNT(*) as total FROM usuario";

if (!empty($where)) {
    $sqlTotal .= " WHERE " . implode(" AND ", $where);
}

$resultTotal = mysqli_query($conexao_bd, $sqlTotal);
$totalUsuarios = mysqli_fetch_assoc($resultTotal)["total"];

$totalPaginas = ceil($totalUsuarios / $usuariosPorPagina);

$paginaAtual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

$inicio = ($paginaAtual - 1) * $usuariosPorPagina;

$sql .= " LIMIT $inicio, $usuariosPorPagina";

$resultado = mysqli_query($conexao_bd, $sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Usuários cadastrados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        /* ==================== NAVBAR SUPERIOR ==================== */
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

        /* ==================== SIDEBAR LATERAL ==================== */
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

        /* Overlay (em mobile, escurece o fundo quando sidebar aberta) */
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

        /* ==================== CONTEÚDO PRINCIPAL ==================== */
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

        /* ==================== CABEÇALHO DA PÁGINA ==================== */
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

        /* ==================== CARD GENÉRICO ==================== */
        .card-pagina {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--cinza-borda);
            padding: 20px 24px;
            margin-bottom: 20px;
            width: 100%;
            max-width: 100%;
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

        /* ==================== TABELA ==================== */
        .tabela-agendamentos {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.88rem;
        }
        .tabela-agendamentos thead th {
            background: var(--azul-claro);
            color: var(--azul-escuro);
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 2px solid var(--cinza-borda);
            white-space: nowrap;
        }
        .tabela-agendamentos tbody tr {
            transition: background 0.15s;
        }
        .tabela-agendamentos tbody tr:hover {
            background: #f8fbff;
        }
        .tabela-agendamentos tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--cinza-borda);
            vertical-align: middle;
        }
        .tabela-agendamentos tbody tr:last-child td {
            border-bottom: none;
        }

        /* ==================== BADGES DE STATUS ==================== */
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .badge-confirmado {
            background: #d1e7dd;
            color: #0a3622;
        }
        .badge-pendente {
            background: #fff3cd;
            color: #664d03;
        }
        .badge-cancelado {
            background: #f8d7da;
            color: #58151c;
        }

        /* ==================== MODAL ==================== */
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
        .modal-form .modal-content{
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-form .modal-footer{
            border-top: 1px solid #e9ecef;
            padding: 16px 20px;
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
            <button
                class="operador-toggle"
                type="button"
                id="dropdownOperador"
                data-bs-toggle="dropdown"
                aria-expanded="false">

                <i class="fa-solid fa-circle-user"></i>

                <span class="d-none d-md-inline">
                    <?php echo htmlspecialchars($nomeUsuario); ?>
                </span>

                <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
            </button>

            <ul
                class="dropdown-menu dropdown-menu-end dropdown-menu-operador"
                aria-labelledby="dropdownOperador">

                <li>
                    <a class="dropdown-item" href="#">
                        <i class="fa-solid fa-user"></i>
                        <?php echo htmlspecialchars($nomeUsuario); ?>
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="#">
                        <i class="fa-solid fa-envelope"></i>
                        <?php echo htmlspecialchars($emailUsuario); ?>
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item" href="config_usuarios.php">
                        <i class="fa-solid fa-gear"></i>
                        Configurações
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Sair
                    </a>
                </li>

            </ul>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">

        <li class="nav-item">
            <a class="nav-link" href="principal.php">
                <i class="fa-solid fa-calendar-days"></i>
                Calendário
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="cadastro_agendas.php">
                <i class="fa-solid fa-calendar-plus"></i>
                Agendamentos
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="cadastro_medicos.php">
                <i class="fa-solid fa-user-doctor"></i>
                Cadastro de Médicos
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="cadastro_especialidades.php">
                <i class="fa-solid fa-list-check"></i>
                Cadastro de Especialidades
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link ativo" href="admin_usuarios.php">
                <i class="fa-solid fa-users"></i>
                Administração de Usuários
            </a>
        </li>

    </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="conteudo-principal" id="conteudoPrincipal">

    <div class="page-header">
        <h2>
            <i class="fa-solid fa-users"></i>
            Administração de Usuários
        </h2>

        <div class="d-flex gap-2">

            <button
                type="button"
                class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#conviteModal">
                <i class="fa-solid fa-key me-2"></i>
                Gerar Convite
            </button>

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#adicionarModal">
                <i class="fa-solid fa-user-plus me-2"></i>
                Adicionar Usuário
            </button>

        </div>
    </div>

    <div class="card-pagina">
        <div class="card-titulo">
            <i class="fa-solid fa-magnifying-glass"></i>
            Filtros
        </div>

        <form method="GET" action="admin_usuarios.php">
            <div class="row g-3">

                <div class="col-md-4">
                    <label>Nome</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control form-control-sm"
                        placeholder="Nome do usuário"
                        value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label>Usuário</label>
                    <input
                        type="text"
                        name="username"
                        class="form-control form-control-sm"
                        placeholder="Login do usuário"
                        value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label>E-mail</label>
                    <input
                        type="text"
                        name="email"
                        class="form-control form-control-sm"
                        placeholder="E-mail"
                        value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                </div>

            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>
                    Filtrar
                </button>

                <a href="admin_usuarios.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-xmark me-1"></i>
                    Limpar
                </a>
            </div>
        </form>
    </div>

    <div class="card-pagina">

        <div class="card-titulo d-flex justify-content-between align-items-center">
            <span>
                <i class="fa-solid fa-table-list"></i>
                Usuários cadastrados
            </span>

            <span class="text-muted" style="font-size:0.82rem; font-weight:400;">
                <?php echo mysqli_num_rows($resultado); ?> registro(s) encontrado(s)
            </span>
        </div>

        <div class="table-responsive">
            <table class="tabela-agendamentos">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Usuário</th>
                        <th>Perfil</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($usuario = mysqli_fetch_assoc($resultado)) { ?>
                        <tr>
                            <td class="text-muted"><?= $usuario["cod_usuario"] ?></td>
                            <td><?= htmlspecialchars($usuario["nome"]) ?></td>
                            <td><?= htmlspecialchars($usuario["email"]) ?></td>
                            <td><?= htmlspecialchars($usuario["username"]) ?></td>
                            <td>
                                <?php if ($usuario["perfil"] == "admin") { ?>
                                    <span class="badge bg-primary">Administrador</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Usuário</span>
                                <?php } ?>
                            </td>

                            <td class="text-center" style="white-space: nowrap;">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm me-2"
                                    style="width: 40px;"
                                    title="Editar usuário"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editarModal"
                                    data-cod="<?= $usuario["cod_usuario"] ?>"
                                    data-nome="<?= htmlspecialchars($usuario["nome"]) ?>"
                                    data-email="<?= htmlspecialchars($usuario["email"]) ?>"
                                    data-username="<?= htmlspecialchars($usuario["username"]) ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <?php if ($usuario["cod_usuario"] != $_SESSION["cod_usuario"]) { ?>
                                    <a
                                        href="?excluir=<?= urlencode($usuario["username"]) ?>"
                                        class="btn btn-outline-danger btn-sm btn-excluir"
                                        style="width: 40px;"
                                        title="Excluir usuário">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPaginas > 1) { ?>
            <div class="d-flex justify-content-end mt-3">
                <nav>
                    <ul class="pagination pagination-sm mb-0">

                        <li class="page-item <?= ($paginaAtual <= 1) ? 'disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="?pagina=<?= $paginaAtual - 1 ?>&nome=<?= urlencode($filtroNome) ?>&username=<?= urlencode($filtroUsername) ?>&email=<?= urlencode($filtroEmail) ?>">
                                &laquo;
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
                            <li class="page-item <?= ($paginaAtual == $i) ? 'active' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?pagina=<?= $i ?>&nome=<?= urlencode($filtroNome) ?>&username=<?= urlencode($filtroUsername) ?>&email=<?= urlencode($filtroEmail) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php } ?>

                        <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="?pagina=<?= $paginaAtual + 1 ?>&nome=<?= urlencode($filtroNome) ?>&username=<?= urlencode($filtroUsername) ?>&email=<?= urlencode($filtroEmail) ?>">
                                &raquo;
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
        <?php } ?>
    </div>

    <div class="modal fade modal-form" id="editarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <form method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuário</h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                        </button>
                    </div>

                    <div class="modal-body">

                        <input
                            type="hidden"
                            name="cod_usuario"
                            id="edit_cod_usuario">

                        <div class="mb-3">
                            <label class="form-label">Nome Completo <span class="text-danger">*</span></label>

                            <input
                                type="text"
                                name="nome"
                                id="edit_nome"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail <span class="text-danger">*</span></label>

                            <input
                                type="email"
                                name="email"
                                id="edit_email"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Usuário <span class="text-danger">*</span></label>

                            <input
                                type="text"
                                name="username"
                                id="edit_username"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nova senha</label>

                            <input
                                type="text"
                                name="pass"
                                id="edit_pass"
                                class="form-control"
                                placeholder="Digite a nova senha ou deixe em branco para manter a atual">
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="button"
                            id="btnSalvarEdicao"
                            class="btn btn-primary">
                            Salvar
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    <div class="modal fade modal-form" id="adicionarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <form method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-user-plus me-2"></i>
                            Adicionar Usuário
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="novo_nome"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                name="novo_email"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Usuário <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="novo_username"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Senha <span class="text-danger">*</span></label>

                            <div class="position-relative">
                                <input
                                    type="password"
                                    name="novo_pass"
                                    id="novo_pass"
                                    class="form-control pe-5"
                                    required>

                                <button
                                    type="button"
                                    id="toggleNovoPass"
                                    style="
                                        position:absolute;
                                        right:12px;
                                        top:50%;
                                        transform:translateY(-50%);
                                        border:none;
                                        background:none;
                                        padding:0;
                                        color:#6c757d;
                                        cursor:pointer;
                                    ">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Perfil</label>

                            <select
                                name="novo_perfil"
                                class="form-select"
                                required>

                                <option value="user" selected>Usuário</option>
                                <option value="admin">Administrador</option>

                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            name="adicionar"
                            class="btn btn-primary">
                            Cadastrar
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <div class="modal fade modal-form" id="conviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <form method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-key me-2"></i>
                            Gerar Convite
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Perfil</label>

                            <select
                                name="perfil_convite"
                                class="form-select"
                                required>

                                <option value="user">Usuário</option>
                                <option value="admin">Administrador</option>

                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            name="gerar_convite"
                            class="btn btn-primary">
                            Gerar Código
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const sucesso = urlParams.get('sucesso');

        if (sucesso) {
            let mensagem = '';

            if (sucesso === 'adicionar') {
                mensagem = 'Usuário cadastrado com sucesso!';
            }

            if (sucesso === 'editar') {
                mensagem = 'Usuário atualizado com sucesso!';
            }

            if (sucesso === 'excluir') {
                mensagem = 'Usuário excluído com sucesso!';
            }

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: mensagem,
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true
            });
        }

        const toggleNovoPass = document.getElementById('toggleNovoPass');
        const novoPass = document.getElementById('novo_pass');

        if (toggleNovoPass && novoPass) {

            toggleNovoPass.addEventListener('click', function () {

                const tipoAtual = novoPass.getAttribute('type');

                if (tipoAtual === 'password') {
                    novoPass.setAttribute('type', 'text');

                    this.innerHTML =
                        '<i class="fa-solid fa-eye-slash"></i>';

                } else {
                    novoPass.setAttribute('type', 'password');

                    this.innerHTML =
                        '<i class="fa-solid fa-eye"></i>';
                }

            });

}

        const codigoConvite = new URLSearchParams(window.location.search).get('convite');

        if (codigoConvite) {
            Swal.fire({
                icon: 'success',
                title: 'Convite gerado com sucesso!',
                html: `
                    <p style="margin-bottom: 12px;">
                        Compartilhe este código com o usuário:
                    </p>

                    <div style="
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        gap:10px;
                        margin-top:16px;
                    ">

                        <div style="
                            flex:1;
                            background:#f8f9fa;
                            border:1px solid #dee2e6;
                            border-radius:12px;
                            padding:14px 18px;
                            font-size:1.5rem;
                            font-weight:700;
                            letter-spacing:3px;
                            color:#0d6efd;
                            text-align:center;
                        ">
                            ${codigoConvite}
                        </div>

                        <button
                            id="btnCopiarConvite"
                            type="button"
                            onclick="event.preventDefault(); event.stopPropagation(); return false;"
                            style="
                                width:52px;
                                height:52px;
                                border:none;
                                border-radius:12px;
                                background:#0d6efd;
                                color:white;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                cursor:pointer;
                                flex-shrink:0;
                            "
                            title="Copiar código"
                        >
                            <i class="fa-solid fa-copy"></i>
                        </button>

                    </div>
                `,
                confirmButtonText: 'Fechar',
                confirmButtonColor: '#0d6efd',

                allowOutsideClick: false,
                allowEscapeKey: true,
                stopKeydownPropagation: false,

                didOpen: () => {
                    const btnCopiar = document.getElementById('btnCopiarConvite');

                    btnCopiar.onclick = async function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        await navigator.clipboard.writeText(codigoConvite);

                        const toast = document.createElement('div');

                        toast.innerHTML = `
                            <div style="
                                position: fixed;
                                top: 20px;
                                right: 20px;
                                background: #198754;
                                color: white;
                                padding: 12px 18px;
                                border-radius: 10px;
                                box-shadow: 0 6px 18px rgba(0,0,0,0.15);
                                z-index: 99999;
                                font-size: 14px;
                                font-weight: 500;
                            ">
                                Código copiado!
                            </div>
                        `;

                        document.body.appendChild(toast);

                        setTimeout(() => {
                            toast.remove();
                        }, 1800);

                        return false;
                    };
                }
            });
        }
    
        const editarModal = document.getElementById('editarModal');

        editarModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const cod = button.getAttribute('data-cod');
            const nome = button.getAttribute('data-nome');
            const email = button.getAttribute('data-email');
            const username = button.getAttribute('data-username');

            document.getElementById('edit_cod_usuario').value = cod;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_pass').value = '';
        });

        const btnSalvarEdicao = document.getElementById('btnSalvarEdicao');

        if (btnSalvarEdicao) {
            btnSalvarEdicao.addEventListener('click', function () {

                Swal.fire({
                    title: 'Salvar alterações?',
                    text: 'Deseja atualizar os dados deste usuário?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Salvar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {

                    if (result.isConfirmed) {
                        btnSalvarEdicao.closest('form').submit();
                    }

                });
            });
        }

        document.querySelectorAll('.btn-excluir').forEach(function(botao) {
            botao.addEventListener('click', function(e) {
                e.preventDefault();

                const url = this.getAttribute('href');

                Swal.fire({
                    title: 'Excluir usuário?',
                    text: 'Essa ação não poderá ser desfeita.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });

    </script>

</body>
</html>