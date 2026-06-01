-- ============================================================
-- MediAgenda - Script de criação do banco de dados
-- Compatível com MySQL 5.6+ / MariaDB 10.1+
-- ============================================================

CREATE DATABASE IF NOT EXISTS labdbprog2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE labdbprog2;

CREATE TABLE IF NOT EXISTS usuario (
    cod_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    perfil VARCHAR(20) NOT NULL DEFAULT 'user',
    pass VARCHAR(255) NOT NULL,
    PRIMARY KEY (cod_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SUPER USUÁRIO INICIAL
-- ============================================================
INSERT INTO usuario (nome, email, username, pass, perfil) VALUES
    ('Administrador', 'admin@mediagenda.com', 'admin', '$2y$10$4SnRvd6aCpkYM5UdyUD3/O96w09EGCxx5DXQGuZxtfin8Z4p85fBK', 'admin');

-- ============================================================
-- TABELA: convite_usuario
-- Cadastro de convites para novos usuários.
-- ============================================================
CREATE TABLE convite_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    perfil VARCHAR(20) NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABELA: especialidades
-- Cadastro de especialidades médicas.
-- ============================================================
CREATE TABLE IF NOT EXISTS especialidades (
    id            INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(100) NOT NULL,
    cbo           VARCHAR(20)  NOT NULL,
    status        ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
    data_criacao  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_especialidade_nome (nome),
    UNIQUE KEY uq_especialidade_cbo (cbo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: medicos
-- Cadastro de médicos. (A especialidade foi movida para a tabela pivô)
-- ============================================================
CREATE TABLE IF NOT EXISTS medicos (
    id               INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    nome             VARCHAR(150) NOT NULL,
    crm              VARCHAR(20)  NOT NULL,
    telefone         VARCHAR(20)           DEFAULT NULL,
    email            VARCHAR(150)          DEFAULT NULL,
    status           ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_medico_crm (crm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA PIVÔ: medico_especialidades
-- Relacionamento N:N entre Médicos e Especialidades
-- ============================================================
CREATE TABLE IF NOT EXISTS medico_especialidades (
    medico_id        INT UNSIGNED NOT NULL,
    especialidade_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (medico_id, especialidade_id),
    
    CONSTRAINT fk_me_medico 
        FOREIGN KEY (medico_id) REFERENCES medicos(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
        
    CONSTRAINT fk_me_especialidade 
        FOREIGN KEY (especialidade_id) REFERENCES especialidades(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: agendamentos
-- Cadastro de consultas agendadas.
-- ============================================================
CREATE TABLE IF NOT EXISTS agendamentos (
    id               INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    paciente         VARCHAR(150) NOT NULL,
    medico_id        INT          UNSIGNED NOT NULL,
    especialidade_id INT          UNSIGNED NOT NULL,
    data             DATE         NOT NULL,
    horario          TIME         NOT NULL,
    status           ENUM('Confirmado','Pendente','Cancelado') NOT NULL DEFAULT 'Pendente',
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY fk_agendamento_medico_idx       (medico_id),
    KEY fk_agendamento_especialidade_idx (especialidade_id),
    KEY idx_agendamento_data            (data),
    KEY idx_agendamento_status          (status),

    CONSTRAINT fk_agendamento_medico
        FOREIGN KEY (medico_id)
        REFERENCES medicos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_agendamento_especialidade
        FOREIGN KEY (especialidade_id)
        REFERENCES especialidades (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS INICIAIS: especialidades
-- ============================================================
INSERT INTO especialidades (id, nome, cbo, status) VALUES
    (1, 'Médico infectologista', '225103', 'Ativo'),
    (2, 'Médico acupunturista', '225105', 'Ativo'),
    (3, 'Médico legista', '225106', 'Ativo'),
    (4, 'Médico nefrologista', '225109', 'Ativo'),
    (5, 'Médico alergista e imunologista', '225110', 'Ativo'),
    (6, 'Médico neurologista', '225112', 'Ativo'),
    (7, 'Médico angiologista', '225115', 'Ativo'),
    (8, 'Médico nutrologista', '225118', 'Ativo'),
    (9, 'Médico cardiologista', '225120', 'Ativo'),
    (10, 'Médico oncologista clínico', '225121', 'Ativo'),
    (11, 'Médico cancerologista pediátrico', '225122', 'Ativo'),
    (12, 'Médico pediatra', '225124', 'Ativo'),
    (13, 'Médico clínico', '225125', 'Ativo'),
    (14, 'Médico pneumologista', '225127', 'Ativo'),
    (15, 'Médico de família e comunidade', '225130', 'Ativo'),
    (16, 'Médico psiquiatra', '225133', 'Ativo'),
    (17, 'Médico dermatologista', '225135', 'Ativo'),
    (18, 'Médico reumatologista', '225136', 'Ativo'),
    (19, 'Médico sanitarista', '225139', 'Ativo'),
    (20, 'Médico do trabalho', '225140', 'Ativo'),
    (21, 'Médico da estratégia de saúde da família', '225142', 'Ativo'),
    (22, 'Médico em medicina de tráfego', '225145', 'Ativo'),
    (23, 'Médico anatomopatologista', '225148', 'Ativo'),
    (24, 'Médico em medicina intensiva', '225150', 'Ativo'),
    (25, 'Médico anestesiologista', '225151', 'Ativo'),
    (26, 'Médico antroposófico', '225154', 'Ativo'),
    (27, 'Médico endocrinologista e metabologista', '225155', 'Ativo'),
    (28, 'Médico fisiatra', '225160', 'Ativo'),
    (29, 'Médico gastroenterologista', '225165', 'Ativo'),
    (30, 'Médico generalista', '225170', 'Ativo'),
    (31, 'Médico geneticista', '225175', 'Ativo'),
    (32, 'Médico geriatra', '225180', 'Ativo'),
    (33, 'Médico hematologista', '225185', 'Ativo'),
    (34, 'Médico homeopata', '225195', 'Ativo'),
    (35, 'Médico em cirurgia vascular', '225203', 'Ativo'),
    (36, 'Médico cirurgião cardiovascular', '225210', 'Ativo'),
    (37, 'Médico cirurgião de cabeça e pescoço', '225215', 'Ativo'),
    (38, 'Médico cirurgião do aparelho digestivo', '225220', 'Ativo'),
    (39, 'Médico cirurgião geral', '225225', 'Ativo'),
    (40, 'Médico cirurgião pediátrico', '225230', 'Ativo'),
    (41, 'Médico cirurgião plástico', '225235', 'Ativo'),
    (42, 'Médico cirurgião torácico', '225240', 'Ativo'),
    (43, 'Médico ginecologista e obstetra', '225250', 'Ativo'),
    (44, 'Médico mastologista', '225255', 'Ativo'),
    (45, 'Médico neurocirurgião', '225260', 'Ativo'),
    (46, 'Médico oftalmologista', '225265', 'Ativo'),
    (47, 'Médico ortopedista e traumatologista', '225270', 'Ativo'),
    (48, 'Médico otorrinolaringologista', '225275', 'Ativo'),
    (49, 'Médico coloproctologista', '225280', 'Ativo'),
    (50, 'Médico urologista', '225285', 'Ativo'),
    (51, 'Médico cancerologista cirurgíco', '225290', 'Ativo'),
    (52, 'Médico cirurgião da mão', '225295', 'Ativo'),
    (53, 'Médico citopatologista', '225305', 'Ativo'),
    (54, 'Médico em endoscopia', '225310', 'Ativo'),
    (55, 'Médico em medicina nuclear', '225315', 'Ativo'),
    (56, 'Médico em radiologia e diagnóstico por imagem', '225320', 'Ativo'),
    (57, 'Médico patologista', '225325', 'Ativo'),
    (58, 'Médico radioterapeuta', '225330', 'Ativo'),
    (59, 'Médico patologista clínico / medicina laboratorial', '225335', 'Ativo'),
    (60, 'Médico hemoterapeuta', '225340', 'Ativo'),
    (61, 'Médico hiperbarista', '225345', 'Ativo'),
    (62, 'Médico neurofisiologista clínico', '225350', 'Ativo'),
    (63, 'Médico radiologista intervencionista', '225355', 'Ativo');

-- ============================================================
-- DADOS INICIAIS: medicos
-- ============================================================
INSERT INTO medicos (id, nome, crm, telefone, email, status) VALUES
    (1, 'Dr. Carlos Lima',    'CRM/SP 12345', '(11) 91234-5678', 'carlos.lima@clinica.com',    'Ativo'),
    (2, 'Dra. Ana Paula',     'CRM/SP 23456', '(11) 92345-6789', 'ana.paula@clinica.com',      'Ativo'),
    (3, 'Dr. Pedro Alves',    'CRM/SP 34567', '(11) 93456-7890', 'pedro.alves@clinica.com',    'Ativo'),
    (4, 'Dra. Marina Reis',   'CRM/SP 45678', '(11) 94567-8901', 'marina.reis@clinica.com',    'Ativo'),
    (5, 'Dr. Ricardo Souza',  'CRM/SP 56789', '(11) 95678-9012', 'ricardo.souza@clinica.com',  'Inativo'),
    (6, 'Dra. Fernanda Melo', 'CRM/SP 67890', '(11) 96789-0123', 'fernanda.melo@clinica.com',  'Ativo');

-- ============================================================
-- DADOS INICIAIS: Vínculo Médico x Especialidade
-- ============================================================
INSERT INTO medico_especialidades (medico_id, especialidade_id) VALUES
    (1, 1), -- Carlos Lima: infectologista
    (2, 2), -- Ana Paula: acupunturista
    (3, 5), -- Pedro Alves: alergista e imunologista
    (4, 6), -- Marina Reis: neurologista
    (5, 4), -- Ricardo Souza: nefrologista
    (6, 3); -- Fernanda Melo: legista

-- ============================================================
-- DADOS INICIAIS: agendamentos
-- ============================================================
INSERT INTO agendamentos (id, paciente, medico_id, especialidade_id, data, horario, status) VALUES
    ( 1, 'Maria Souza',     1, 1, '2026-06-05', '09:00', 'Confirmado'),
    ( 2, 'Carlos Andrade',  2, 2, '2026-06-08', '10:30', 'Confirmado'),
    ( 3, 'Juliana Reis',    3, 5, '2026-06-08', '14:00', 'Pendente'),
    ( 4, 'Pedro Henrique',  2, 2, '2026-06-12', '08:00', 'Confirmado'),
    ( 5, 'Júlia Mendes',    1, 1, '2026-06-15', '11:00', 'Confirmado'),
    ( 6, 'Roberto Dias',    3, 5, '2026-06-15', '15:30', 'Confirmado'),
    ( 7, 'Fernanda Costa',  4, 6, '2026-06-15', '16:30', 'Pendente'),
    ( 8, 'Lucas Silva',     1, 1, '2026-06-15', '17:30', 'Confirmado'),
    ( 9, 'Luiz Henrique',   4, 6, '2026-06-19', '09:30', 'Confirmado'),
    (10, 'Beatriz Ramos',   2, 2, '2026-06-23', '10:00', 'Pendente'),
    (11, 'Marcos Vinícius', 3, 5, '2026-06-26', '14:00', 'Confirmado');

-- ============================================================
-- VIEWS ÚTEIS
-- ============================================================

-- Agendamentos com nome do médico e especialidade resolvidos
CREATE OR REPLACE VIEW vw_agendamentos AS
    SELECT
        a.id,
        a.paciente,
        m.nome              AS medico,
        e.nome              AS especialidade,
        a.data,
        a.horario,
        a.status,
        a.created_at,
        a.updated_at
    FROM agendamentos  a
    JOIN medicos       m ON m.id = a.medico_id
    JOIN especialidades e ON e.id = a.especialidade_id;

-- Médicos com nome da especialidade resolvido (Atualizado para N:N)
CREATE OR REPLACE VIEW vw_medicos AS
    SELECT
        m.id,
        m.nome,
        m.crm,
        GROUP_CONCAT(e.nome SEPARATOR ', ') AS especialidades,
        m.telefone,
        m.email,
        m.status,
        m.created_at,
        m.updated_at
    FROM medicos m
    LEFT JOIN medico_especialidades me ON m.id = me.medico_id
    LEFT JOIN especialidades e ON me.especialidade_id = e.id
    GROUP BY m.id;

