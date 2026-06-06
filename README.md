# 🩺 MediAgenda

Sistema web para gerenciamento e agendamento de consultas médicas desenvolvido em PHP como projeto acadêmico da disciplina de Programação II.

---

# 🚀 Funcionalidades

## Autenticação e Usuários

- Login seguro com senha criptografada
- Controle de sessão
- Alteração de senha
- Perfis de acesso (Administrador e Usuário)
- Cadastro de usuários por código convite
- Administração completa de usuários
- Geração de convites para novos cadastros

## Gestão Médica

- Cadastro de médicos
- Cadastro de especialidades
- Relacionamento entre médicos e múltiplas especialidades
- Controle de status (Ativo/Inativo)

## Agendamentos

- Cadastro de consultas
- Visualização em calendário mensal
- Controle de status:
  - Confirmado
  - Pendente
  - Cancelado
- Cancelamento de consultas
- Bloqueio de alterações em datas passadas

## Dashboard

- Calendário mensal interativo
- Visualização rápida dos agendamentos
- Navegação entre meses

---

# 🛠️ Tecnologias Utilizadas

| Tecnologia | Finalidade |
|------------|------------|
| PHP 8+ | Back-end |
| MySQL / MariaDB | Banco de dados |
| Bootstrap 5 | Interface |
| JavaScript | Interatividade |
| SweetAlert2 | Alertas |
| HTML5 | Estrutura |
| CSS3 | Estilização |
| Docker | Containerização |
| Git/GitHub | Versionamento |

---

# 📂 Estrutura do Projeto

```text
mediagenda/
│
├── db/
│   └── init.sql
│
├── www/
│   ├── login.php
│   ├── logout.php
│   ├── principal.php
│   ├── admin_usuarios.php
│   ├── cadastro_usuarios.php
│   ├── cadastro_medicos.php
│   ├── cadastro_especialidades.php
│   ├── cadastro_agendas.php
│   ├── cancelar_agendamento.php
│   ├── buscar_especialidades.php
│   ├── config_usuarios.php
│   ├── conexao.php
│   ├── style.css
│   └── img/
│
├── docker-compose.yml
├── dockerfile
└── README.md
```

---

# 🗄️ Banco de Dados

O banco foi modelado para atender ao gerenciamento de consultas médicas.

Principais entidades:

- Usuários
- Convites
- Médicos
- Especialidades
- Agendamentos

Views disponíveis:

### vw_agendamentos

Retorna os agendamentos com os nomes dos médicos e especialidades já resolvidos.

### vw_medicos

Retorna médicos e suas respectivas especialidades utilizando relacionamento muitos-para-muitos.

O script completo encontra-se em:

```sql
db/init.sql
```

---

# 🔒 Segurança

O sistema implementa:

- Senhas armazenadas com `password_hash()`
- Verificação com `password_verify()`
- Controle de sessão
- Controle de permissões por perfil
- Restrição de acesso administrativo
- Prepared Statements para consultas SQL
- Proteção contra SQL Injection
- Validação de autenticação em páginas restritas

---

# 🐳 Executando com Docker

## Subir os containers

```bash
docker compose up -d
```

## Acessar a aplicação

```text
http://localhost
```

---

# ⚙️ Executando Manualmente

## 1. Criar o banco

Execute:

```sql
db/init.sql
```

## 2. Configurar a conexão

Edite:

```php
www/conexao.php
```

e informe:

- host
- usuário
- senha
- banco de dados

## 3. Iniciar servidor PHP

Exemplo:

```bash
php -S localhost:8000
```

Acesse:

```text
http://localhost:8000
```

---

# 👤 Usuário Administrador Inicial

O banco já cria um usuário administrador padrão:

```text
Usuário: admin
Senha: admin123
```

Recomenda-se alterar a senha após o primeiro acesso.

---

# 📈 Melhorias Futuras

- Responsividade mobile completa
- Notificações automáticas
- Relatórios gerenciais
- Dashboard estatístico
- API REST
- Integração com sistemas externos
- Hospedagem em nuvem

---

# 👨‍💻 Equipe de Desenvolvimento

| Integrante | Matrícula |
|------------|------------|
| Allan Luiz Filipe Oliveira | 241216821 |
| André Felipe Andrade Oliveira | 251210008 |
| Gabriel Henrique da Fraga Santos | 2412112804 |
| Isaac Oliveira Ferreira de Sousa | 2412113877 |
| Vítor Hugo Moreira | 241211248 |
| Walkíria Aparecida de Souza | 241210014 |

---

# 📚 Objetivo Acadêmico

Projeto desenvolvido para aplicação prática dos conceitos estudados em Programação II, incluindo:

- Programação Web
- Banco de Dados
- Segurança de Aplicações
- CRUD
- Relacionamentos SQL
- Controle de Acesso
- Versionamento de Código