<?php

/* ============================================================
   principal.php - Dashboard de Agendamento de Consultas Médicas
============================================================ */
session_start();
require_once("conexao.php");

if(!isset($_SESSION['cod_usuario'])){
    header("Location: login.php");
    exit;
}
$cod_usuario = $_SESSION['cod_usuario'];
$nomeUsuario = "";
$perfilUsuario = "";
$pageError = '';

$sql = "SELECT * FROM usuario WHERE cod_usuario = " . $cod_usuario;
$result = mysqli_query($conexao_bd,$sql); 

if ($result && $consulta = mysqli_fetch_assoc($result)) {
    $nomeUsuario  = $consulta['nome'];
    $emailUsuario = $consulta['email'];
    $perfilUsuario = $consulta["perfil"];
} elseif ($result === false) {
    $pageError = mysqli_error($conexao_bd);
}
/* ============================================================
   DADOS DO OPERADOR LOGADO
============================================================ */
$operadorNome  = $nomeUsuario; 
$operadorEmail = $emailUsuario; 

/* ============================================================
   DADOS DO MÊS ATUAL (cálculo do calendário)
============================================================ */
$mesAtual    = isset($_GET['mes']) ? max(1, min(12, (int)$_GET['mes'])) : (int)date('n');
$anoAtual    = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$nomesMeses  = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$nomeMes     = $nomesMeses[$mesAtual];
$primeiroDia = mktime(0, 0, 0, $mesAtual, 1, $anoAtual);
$diaSemanaInicio = (int)date('w', $primeiroDia); // 0=Dom ... 6=Sáb
$totalDias   = (int)date('t', $primeiroDia);
$diaHoje     = (int)date('j');
$mesHoje     = (int)date('n');
$anoHoje     = (int)date('Y');

// Mês anterior
$mesAnterior = $mesAtual - 1;
$anoAnterior = $anoAtual;
if ($mesAnterior < 1) { $mesAnterior = 12; $anoAnterior--; }

// Próximo mês
$proximoMes = $mesAtual + 1;
$proximoAno = $anoAtual;
if ($proximoMes > 12) { $proximoMes = 1; $proximoAno++; }

/* ============================================================
   AGENDAMENTOS FICTÍCIOS
============================================================ */
$sql = "select *, DAY(data) diaAgenda from vw_agendamentos where MONTH(data) = $mesAtual AND YEAR(data) = $anoAtual";
$result = mysqli_query($conexao_bd,$sql);
if ($result) {
    while($row = mysqli_fetch_assoc($result)){

        $agendamentosFicticios[$row["diaAgenda"]][] = [
        'id'            => $row["id"],
        'horario'       => date("H:i", strtotime($row["horario"])),
        'paciente'      => $row["paciente"],
        'medico'        => $row["medico"],
        'especialidade' => $row["especialidade"],
        'status'        => $row["status"]
    ];
}
} else {
    if ($pageError === '') {
        $pageError = mysqli_error($conexao_bd);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Painel Principal</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">

    <!-- ================ CDNs ================ -->
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- ================ ESTILOS DA APLICAÇÃO ================ -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- ==================================================
         NAVBAR SUPERIOR
    ================================================== -->
    <nav class="navbar-topo d-flex align-items-center justify-content-between px-3">
        <!-- Lado esquerdo: sanduíche + logo + título -->
        <div class="d-flex align-items-center gap-2">
            <button class="btn-sanduiche" id="btnSanduiche" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand mb-0 d-flex align-items-center" href="#">
                <i class="fa-solid fa-stethoscope"></i>
                <span>MediAgenda</span>
            </a>
        </div>

        <!-- Lado direito: dropdown do operador -->
        <div class="dropdown">
            <button class="operador-toggle" type="button" id="dropdownOperador" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i>
                <span class="d-none d-md-inline"><?php echo($operadorNome); ?></span>
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

    <!-- ==================================================
         SIDEBAR LATERAL
    ================================================== -->
    <aside class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link ativo" href="principal.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_agendas.php"><i class="fa-solid fa-calendar-plus"></i> Agendamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_medicos.php"><i class="fa-solid fa-user-doctor"></i> Cadastro de Médicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="cadastro_especialidades.php"><i class="fa-solid fa-list-check"></i> Cadastro de Especialidades</a>
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

    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ==================================================
         CONTEÚDO PRINCIPAL - CALENDÁRIO
    ================================================== -->
    <main class="conteudo-principal" id="conteudoPrincipal">

        <div class="card-calendario">

            <!-- Cabeçalho do calendário com navegação -->
            <div class="calendario-cabecalho">
                <h4><?php echo $nomeMes ?> <?php echo $anoAtual ?></h4>
                <div class="d-flex gap-2">
                    <a class="btn-nav" href="?mes=<?php echo $mesAnterior ?>&amp;ano=<?php echo $anoAnterior ?>" title="Mês anterior"><i class="fa-solid fa-chevron-left"></i></a>
                    <a class="btn-nav" href="?" title="Hoje">Hoje</a>
                    <a class="btn-nav" href="?mes=<?php echo $proximoMes ?>&amp;ano=<?php echo $proximoAno ?>" title="Próximo mês"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>

            <!-- Grade do calendário -->
            <div class="calendario-grade">
                <!-- Cabeçalho dos dias da semana -->
                <div class="dia-semana">Dom</div>
                <div class="dia-semana">Seg</div>
                <div class="dia-semana">Ter</div>
                <div class="dia-semana">Qua</div>
                <div class="dia-semana">Qui</div>
                <div class="dia-semana">Sex</div>
                <div class="dia-semana">Sáb</div>

                <?php
                // Células vazias antes do dia 1 (para alinhar ao dia da semana correto)
                for ($i = 0; $i < $diaSemanaInicio; $i++) {
                    echo '<div class="dia vazio"></div>';
                }

                // Loop pelos dias do mês
                for ($dia = 1; $dia <= $totalDias; $dia++) {

                    $classeHoje = ($dia === $diaHoje && $mesAtual === $mesHoje && $anoAtual === $anoHoje) ? 'hoje' : '';

                    $diaSemana = date('w', mktime(0, 0, 0, $mesAtual, $dia, $anoAtual));

                    $classeFimSemana = ($diaSemana == 0 || $diaSemana == 6) ? 'fim-semana' : '';
                ?>
                <div class="dia <?php echo $classeHoje . ' ' . $classeFimSemana ?>">

                        <!-- NOVO: Faixa com número do dia -->
                        <div class="numero-dia">
                            <span><?php echo sprintf('%02d', $dia) ?></span>
                        </div>

                        <?php
                        /* ============================================================
                           PONTO DE INTEGRAÇÃO COM O BANCO DE DADOS
                        ============================================================ */
                        $agendamentosDoDia = isset($agendamentosFicticios[$dia]) ? $agendamentosFicticios[$dia] : array();

                        // Limita exibição a 3 cards; o restante vira "+N mais"
                        $maxExibir  = 3;
                        $totalAgend = count($agendamentosDoDia);
                        $exibir     = array_slice($agendamentosDoDia, 0, $maxExibir);

                        foreach ($exibir as $agend):
                        ?>
                            <!-- ====== Template do card de agendamento (clicável → modal) ====== -->
                            <div class="card-agendamento"
                                 data-id="<?php echo $agend['id'] ?>"
                                 data-horario="<?php echo htmlspecialchars($agend['horario']) ?>"
                                 data-paciente="<?php echo htmlspecialchars($agend['paciente']) ?>"
                                 data-medico="<?php echo htmlspecialchars($agend['medico']) ?>"
                                 data-especialidade="<?php echo htmlspecialchars($agend['especialidade']) ?>"
                                 data-status="<?php echo htmlspecialchars($agend['status']) ?>"
                                 data-data="<?php echo sprintf('%02d/%02d/%d', $dia, $mesAtual, $anoAtual) ?>">
                                <span class="horario"><?php echo htmlspecialchars($agend['horario']) ?></span>
                                <span class="paciente"><?php echo htmlspecialchars($agend['paciente']) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($totalAgend > $maxExibir): ?>
                            <span class="link-mais">+ <?php echo $totalAgend - $maxExibir ?> mais</span>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

    </main>

    <!-- ==================================================
         MODAL DE DETALHES DO AGENDAMENTO
    ================================================== -->
    <div class="modal fade modal-detalhe" id="modalAgendamento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-calendar-check me-2"></i>Detalhes do Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="info-item">
                        <i class="fa-solid fa-user"></i>
                        <div><strong>Paciente:</strong> <span id="modalPaciente"></span></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-user-doctor"></i>
                        <div><strong>Médico:</strong> <span id="modalMedico"></span></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-stethoscope"></i>
                        <div><strong>Especialidade:</strong> <span id="modalEspecialidade"></span></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-calendar"></i>
                        <div><strong>Data:</strong> <span id="modalData"></span></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-clock"></i>
                        <div><strong>Horário:</strong> <span id="modalHorario"></span></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-circle-info"></i>
                        <div><strong>Status:</strong> <span id="modalStatus"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="btnCancelarAgendamento">
                        <i class="fa-solid fa-ban me-1"></i> Cancelar Agendamento
                    </button>
                    <!-- TODO: implementar ação de editar -->
                    <button type="button" class="btn btn-primary" id="btnEditarAgendamento"><i class="fa-solid fa-pen me-1"></i> Editar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================ SCRIPTS ================ -->
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ==================================================
        // TOGGLE DA SIDEBAR (responsivo)
        // ==================================================
        const btnSanduiche      = document.getElementById('btnSanduiche');
        const sidebar           = document.getElementById('sidebar');
        const conteudoPrincipal = document.getElementById('conteudoPrincipal');
        const sidebarOverlay    = document.getElementById('sidebarOverlay');
        const urlParams         = new URLSearchParams(window.location.search);
        const pageAlert         = urlParams.get('alert');
        const pageAction        = urlParams.get('acao');
        const serverErrorMessage = <?php echo json_encode($pageError); ?>;

        if (serverErrorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Ops, algo deu errado!',
                text: serverErrorMessage,
                confirmButtonText: 'Entendi'
            });
        } else if (pageAlert === 'success') {
            let message = '';
            if (pageAction === 'novo') {
                message = 'Agendamento cadastrado com sucesso!';
            } else if (pageAction === 'editar') {
                message = 'Agendamento atualizado com sucesso!';
            } else if (pageAction === 'cancelar') {
                message = 'Agendamento cancelado com sucesso!';
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
            let errorMessage = urlParams.get('message') || 'Ocorreu um erro inesperado.';
            errorMessage = decodeURIComponent(errorMessage);
            Swal.fire({
                icon: 'error',
                title: 'Ops, algo deu errado!',
                text: errorMessage,
                confirmButtonText: 'Entendi'
            });
        }

        btnSanduiche.addEventListener('click', () => {
            if (window.innerWidth <= 991.98) {
                // Mobile: usa overlay
                sidebar.classList.toggle('aberta');
                sidebarOverlay.classList.toggle('ativo');
            } else {
                // Desktop: oculta/mostra a sidebar e expande/contrai o conteúdo
                sidebar.classList.toggle('oculta');
                conteudoPrincipal.classList.toggle('expandido');
            }
        });

        // Clicar no overlay (mobile) fecha a sidebar
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('aberta');
            sidebarOverlay.classList.remove('ativo');
        });

        // Ao redimensionar, limpa estados que não fazem sentido no novo layout
        window.addEventListener('resize', () => {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('aberta');
                sidebarOverlay.classList.remove('ativo');
            }
        });

        // ==================================================
        // CLIQUE NO CARD DE AGENDAMENTO → ABRE MODAL
        // ==================================================
        var modalAgendamento  = new bootstrap.Modal(document.getElementById('modalAgendamento'));
        var agendamentoAtual  = { id: null, paciente: null, data: null, horario: null, medico: null, especialidade: null, status: null };

        document.querySelectorAll('.card-agendamento').forEach(function(card) {
            card.addEventListener('click', function() {
                // Guarda os dados do agendamento selecionado para uso no cancelamento/edição
                agendamentoAtual.id            = card.dataset.id;
                agendamentoAtual.paciente      = card.dataset.paciente;
                agendamentoAtual.data          = card.dataset.data;
                agendamentoAtual.horario       = card.dataset.horario;
                agendamentoAtual.medico        = card.dataset.medico;
                agendamentoAtual.especialidade = card.dataset.especialidade;
                agendamentoAtual.status        = card.dataset.status;

                document.getElementById('modalPaciente').textContent      = card.dataset.paciente;
                document.getElementById('modalMedico').textContent        = card.dataset.medico;
                document.getElementById('modalEspecialidade').textContent = card.dataset.especialidade;
                document.getElementById('modalData').textContent          = card.dataset.data;
                document.getElementById('modalHorario').textContent       = card.dataset.horario;
                document.getElementById('modalStatus').textContent        = card.dataset.status;
                modalAgendamento.show();
            });
        });

        document.getElementById('btnEditarAgendamento').addEventListener('click', function() {
            if (!agendamentoAtual.id) return;
            var params = new URLSearchParams();
            params.set('editar', '1');
            params.set('id', agendamentoAtual.id);
            window.location.href = 'cadastro_agendas.php?' + params.toString();
        });

        // ==================================================
        // CANCELAR AGENDAMENTO — confirmação via SweetAlert2
        // ==================================================
        document.getElementById('btnCancelarAgendamento').addEventListener('click', function() {
            Swal.fire({
                title: 'Cancelar agendamento?',
                html:  'Deseja cancelar o agendamento de <strong>' + agendamentoAtual.paciente + '</strong>' +
                       '<br>Data: ' + agendamentoAtual.data + ' às ' + agendamentoAtual.horario + '?',
                icon: 'warning',
                showCancelButton:   true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor:  '#6c757d',
                confirmButtonText:  'Sim, cancelar',
                cancelButtonText:   'Voltar'
            }).then(function(result) {
                if (result.isConfirmed) {

                    fetch('cancelar_agendamento.php', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body:    'id=' + agendamentoAtual.id
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(dados) {
                        if (!dados.sucesso) {
                            Swal.fire({
                                icon:               'error',
                                title:              'Erro',
                                text:               dados.mensagem || 'Não foi possível cancelar o agendamento.',
                                confirmButtonColor: '#0d6efd'
                            });
                            return;
                        }

                        // Remove o card do calendário
                        var card = document.querySelector('.card-agendamento[data-id="' + agendamentoAtual.id + '"]');
                        if (card) {
                            card.remove();
                        }

                        modalAgendamento.hide();

                        Swal.fire({
                            icon:               'success',
                            title:              'Cancelado!',
                            text:               'O agendamento foi cancelado com sucesso.',
                            confirmButtonColor: '#0d6efd',
                            timer:              2000,
                            showConfirmButton:  false
                        }).then(function() {
                            window.location.reload();
                        });
                    })
                    .catch(function() {
                        Swal.fire({
                            icon:               'error',
                            title:              'Erro de comunicação',
                            text:               'Não foi possível conectar ao servidor. Tente novamente.',
                            confirmButtonColor: '#0d6efd'
                        });
                    });
                }
            });
        });

        document.querySelectorAll('.link-mais').forEach(function(link) {
            link.addEventListener('click', function() {
                Swal.fire({
                    icon:               'info',
                    title:              'Em breve',
                    text:               'Aqui será exibida a lista completa de agendamentos do dia.',
                    confirmButtonColor: '#0d6efd'
                });
            });
        });
    </script>
</body>
</html>