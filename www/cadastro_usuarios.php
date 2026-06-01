<?php
require_once("conexao.php");

    $mensagem = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $nome = trim($_POST["nome"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $usuario = trim($_POST["usuario"] ?? '');
        $senha = trim($_POST["senha"] ?? '');
        $confirmar_senha = trim($_POST["confirmar_senha"] ?? '');
        $codigo_convite = trim($_POST["codigo_convite"] ?? '');

        if (
            $nome == "" ||
            $email == "" ||
            $usuario == "" ||
            $senha == "" ||
            $confirmar_senha == "" ||
            $codigo_convite == ""
        ) {

            $mensagem = "Preencha todos os campos.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem = "Informe um e-mail válido.";

        } else {

            $codigoEsc = mysqli_real_escape_string($conexao_bd, $codigo_convite);

            $sqlConvite = "
                SELECT *
                FROM convite_usuario
                WHERE codigo = '$codigoEsc'
                AND usado = 0
                LIMIT 1
            ";

            $resultadoConvite = mysqli_query($conexao_bd, $sqlConvite);

            if (mysqli_num_rows($resultadoConvite) == 0) {

                $mensagem = "Código de convite inválido ou já utilizado.";

            } elseif ($senha !== $confirmar_senha) {

                $mensagem = "As senhas não coincidem.";

            } elseif (
                strlen($senha) < 6 ||
                !preg_match('/[a-z]/', $senha) ||
                !preg_match('/[A-Z]/', $senha) ||
                !preg_match('/[0-9]/', $senha) ||
                !preg_match('/[^A-Za-z0-9]/', $senha)
            ) {

                $mensagem = "A senha deve ser forte: mínimo 6 caracteres, com maiúscula, minúscula, número e símbolo.";

            } else {

                $convite = mysqli_fetch_assoc($resultadoConvite);
                $perfilUsuario = $convite["perfil"];

                $nome = mysqli_real_escape_string($conexao_bd, $nome);
                $email = mysqli_real_escape_string($conexao_bd, $email);
                $usuario = mysqli_real_escape_string($conexao_bd, $usuario);

                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $senhaHash = mysqli_real_escape_string($conexao_bd, $senhaHash);

                $verifica = "
                    SELECT *
                    FROM usuario
                    WHERE username = '$usuario'
                    OR email = '$email'
                ";

                $resultado = mysqli_query($conexao_bd, $verifica);

                if (mysqli_num_rows($resultado) > 0) {

                    $mensagem = "Usuário ou e-mail já cadastrado.";

                } else {

                    $sql = "
                        INSERT INTO usuario
                        (nome, email, username, pass, perfil)
                        VALUES
                        (
                            '$nome',
                            '$email',
                            '$usuario',
                            '$senhaHash',
                            '$perfilUsuario'
                        )
                    ";

                    if (mysqli_query($conexao_bd, $sql)) {

                        mysqli_query(
                            $conexao_bd,
                            "UPDATE convite_usuario
                            SET usado = 1
                            WHERE codigo = '$codigoEsc'"
                        );

                        header("Location: login.php?sucesso=cadastro");
                        exit;

                    } else {

                        $mensagem = "Erro ao cadastrar usuário.";

                    }
                }
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MediAgenda - Cadastro de Usuário</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0d6efd;
            --bg-secondary: #084298;
            --surface: #fff;
            --text: #1f2d3d;
            --text-muted: #6c757d;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #eef6ff 0%, #dbe9ff 100%);
            color: var(--text);
        }

        .page-login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: 0;
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            background: var(--surface);
            display: flex;
            flex-direction: column;
        }

        .login-hero {
            padding: 1.4rem 1.4rem;
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            color: #fff;
            text-align: center;
        }
        .login-hero h1 {
            margin-bottom: 0.4rem;
            font-size: 1.7rem;
            letter-spacing: 0.01em;
        }
        .login-hero p {
            margin: 0;
            opacity: 0.92;
            font-size: 0.94rem;
            line-height: 1.5;
        }

        .login-body {
            padding: 1.2rem 1.25rem 1.4rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            max-height: 70vh;
            overflow-y: auto;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.85rem 1rem;
            border: 1px solid #d5dbe8;
            background: #f8fafd;
        }

        .form-control:focus {
            border-color: var(--bg-primary);
            box-shadow: 0 0 0 0.12rem rgba(13, 110, 253, 0.16);
        }

        .btn-auth {
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-weight: 600;
        }

        .toggle-password-btn:focus,
        .toggle-password-btn:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body>
    <div class="page-login">
        <div class="login-card">
            <div class="login-hero">
                <div class="mb-3">
                    <i class="fa-solid fa-stethoscope fa-2x"></i>
                </div>

                <h1>MediAgenda</h1>

                <p>Crie sua conta para acessar o sistema.</p>
            </div>

            <div class="login-body">

                <?php if($mensagem != "") { ?>
                    <div class="alert alert-danger">
                        <?php echo $mensagem; ?>
                    </div>
                <?php } ?>

                <form method="POST" novalidate>

                    <label class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control mb-3"
                        placeholder="Digite seu nome completo"
                        value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                        required>

                    <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        name="email"
                        class="form-control mb-3"
                        placeholder="Digite seu e-mail"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required>

                    <label class="form-label fw-semibold">Usuário <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="usuario"
                        class="form-control mb-3"
                        placeholder="Escolha um nome de usuário"
                        value="<?php echo htmlspecialchars($usuario ?? ''); ?>"
                        required>

                    <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
                    <div class="position-relative mb-2">
                        <input
                            type="password"
                            name="senha"
                            id="senha"
                            class="form-control pe-5"
                            placeholder="Digite sua senha"
                            required>

                        <button
                            type="button"
                            onclick="toggleSenha('senha', this)"
                            class="btn toggle-password-btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent me-2">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <div class="mt-2 mb-3">
                        <div class="progress" style="height: 8px; border-radius: 999px;">
                            <div
                                id="barraSenha"
                                class="progress-bar"
                                role="progressbar"
                                style="width: 0%; border-radius: 999px;">
                            </div>
                        </div>
                        <small id="textoForcaSenha" class="text-muted"></small>
                    </div>

                    <label class="form-label fw-semibold">Confirmar senha <span class="text-danger">*</span></label>
                    <div class="position-relative mb-3">
                        <input
                            type="password"
                            name="confirmar_senha"
                            id="confirmar_senha"
                            class="form-control pe-5"
                            placeholder="Digite a senha novamente"
                            required>

                        <button
                            type="button"
                            onclick="toggleSenha('confirmar_senha', this)"
                            class="btn toggle-password-btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent me-2">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Código de convite <span class="text-danger">*</span></label>

                        <input
                            type="text"
                            name="codigo_convite"
                            class="form-control"
                            placeholder="Informe o código recebido"
                            value="<?= htmlspecialchars($_POST['codigo_convite'] ?? '') ?>"
                            required>
                    </div>    

                    <button type="submit" class="btn btn-primary btn-auth w-100">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        Cadastrar
                    </button>

                    <a href="login.php" class="btn btn-outline-primary btn-auth w-100 mt-2">
                        <i class="fa-solid fa-arrow-left me-2"></i>
                        Voltar ao Login
                    </a>

                </form>
            </div>
        </div>
    </div>
    <script>
        function toggleSenha(idCampo, botao) {
            const campo = document.getElementById(idCampo);
            const icone = botao.querySelector("i");

            if (campo.type === "password") {
                campo.type = "text";
                icone.classList.remove("fa-eye");
                icone.classList.add("fa-eye-slash");
            } else {
                campo.type = "password";
                icone.classList.remove("fa-eye-slash");
                icone.classList.add("fa-eye");
            }
        }

        const campoSenha = document.getElementById("senha");
        const barraSenha = document.getElementById("barraSenha");
        const textoForcaSenha = document.getElementById("textoForcaSenha");

        campoSenha.addEventListener("input", function () {
            const senha = campoSenha.value;
            let pontos = 0;

            if (senha.length >= 6) pontos++;
            if (/[a-z]/.test(senha)) pontos++;
            if (/[A-Z]/.test(senha)) pontos++;
            if (/[0-9]/.test(senha)) pontos++;
            if (/[^A-Za-z0-9]/.test(senha)) pontos++;

            if (senha.length === 0) {
                barraSenha.style.width = "0%";
                barraSenha.style.backgroundColor = "";
                textoForcaSenha.innerText = "";
                return;
            }

            if (pontos <= 2) {
                barraSenha.style.width = "33%";
                barraSenha.style.backgroundColor = "#dc3545";
                textoForcaSenha.innerText = "Senha fraca";
            }
            else if (pontos <= 4) {
                barraSenha.style.width = "66%";
                barraSenha.style.backgroundColor = "#ffc107";
                textoForcaSenha.innerText = "Senha média";
            }
            else {
                barraSenha.style.width = "100%";
                barraSenha.style.backgroundColor = "#198754";
                textoForcaSenha.innerText = "Senha forte";
            }
        });

    </script>
</body>
</html>