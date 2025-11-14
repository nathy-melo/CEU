DROP DATABASE IF EXISTS CEU_bd;
CREATE DATABASE CEU_bd DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE CEU_bd;

SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "-03:00";

CREATE TABLE evento(
    cod_evento INT PRIMARY KEY NOT NULL,
    categoria VARCHAR(40) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    lugar VARCHAR(40) NOT NULL,
    descricao VARCHAR(500),
    publico_alvo VARCHAR(100),
    inicio DATETIME NOT NULL,
    conclusao DATETIME NOT NULL,
    duracao FLOAT, 
    certificado TINYINT(1) NOT NULL DEFAULT 0,
    modalidade ENUM('Presencial','Online','Híbrido') NOT NULL DEFAULT 'Presencial',
    imagem VARCHAR(255) NULL,
    inicio_inscricao DATETIME NULL,
    fim_inscricao DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificado(
    cod_verificacao VARCHAR(8) PRIMARY KEY,
    modelo VARCHAR(255),
    tipo VARCHAR(100),
    arquivo VARCHAR(255) NULL,
    dados TEXT NULL,
    criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    cpf CHAR(11) NULL,
    cod_evento INT NULL,
    UNIQUE KEY uniq_cert_cpf_evento (cpf, cod_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario(
    CPF CHAR(11) PRIMARY KEY,
    Nome VARCHAR(100),
    Email VARCHAR(100),
    Senha VARCHAR(255),
    RA CHAR(7) NULL,
    Codigo VARCHAR(8),
    Organizador TINYINT(1) NOT NULL DEFAULT 0,
    TemaSite TINYINT(1) NOT NULL DEFAULT 0,
    FotoPerfil VARCHAR(255) NULL,
    CONSTRAINT chk_codigo_organizador CHECK (
        (Organizador = 0) OR 
        (Organizador = 1 AND Codigo IS NOT NULL)
    ),
    CONSTRAINT chk_tema_site CHECK (TemaSite IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lista_de_participantes(
    CPF CHAR(11) PRIMARY KEY,
    Nome VARCHAR(100),
    RA CHAR(7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organiza(
    cod_evento INT,
    CPF CHAR(11),
    PRIMARY KEY (cod_evento,CPF),
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inscricao (
    CPF CHAR(11) NOT NULL,
    cod_evento INT NOT NULL,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativa','cancelada') NOT NULL DEFAULT 'ativa',
    presenca_confirmada TINYINT(1) NOT NULL DEFAULT 0,
    certificado_emitido TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (CPF, cod_evento),
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE codigos_organizador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(8) UNIQUE NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_uso TIMESTAMP NULL,
    usado_por VARCHAR(11) NULL COMMENT 'CPF do usuário que usou o código',
    criado_por VARCHAR(100) DEFAULT 'SISTEMA' COMMENT 'Quem criou o código',
    observacoes TEXT NULL COMMENT 'Observações sobre o código'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE imagens_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_evento INT NOT NULL,
    caminho_imagem VARCHAR(255) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CPF CHAR(11) NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL COMMENT 'Tipo: inscricao, desinscricao, evento_cancelado, evento_prestes_iniciar, etc',
    mensagem VARCHAR(255) NOT NULL,
    cod_evento INT NULL COMMENT 'Referência ao evento se aplicável',
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE colaboradores_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_evento INT NOT NULL,
    CPF CHAR(11) NOT NULL,
    papel ENUM('colaborador','coorganizador') NOT NULL DEFAULT 'colaborador',
    presenca_confirmada TINYINT(1) NOT NULL DEFAULT 0,
    certificado_emitido TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_evento_cpf (cod_evento, CPF),
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacoes_colaboracao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_evento INT NOT NULL,
    cpf_solicitante CHAR(11) NOT NULL,
    status ENUM('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
    mensagem TEXT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resolucao TIMESTAMP NULL,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
    FOREIGN KEY (cpf_solicitante) REFERENCES usuario(CPF) ON DELETE CASCADE,
    UNIQUE KEY uk_pedido (cod_evento, cpf_solicitante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacoes_redefinicao_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    CPF CHAR(11) NULL COMMENT 'CPF do usuário se encontrado',
    nome_usuario VARCHAR(100) NULL COMMENT 'Nome do usuário',
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente','resolvida','cancelada') NOT NULL DEFAULT 'pendente',
    data_resolucao TIMESTAMP NULL,
    resolvido_por VARCHAR(100) NULL COMMENT 'Admin que resolveu',
    observacoes TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favoritos_evento (
    CPF VARCHAR(14) NOT NULL,
    cod_evento INT NOT NULL,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (CPF, cod_evento),
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuario (CPF, Nome, Email, Senha, Codigo, Organizador, TemaSite) VALUES
('12345678901', 'Aurora Sobrinho', 'aurora@ceu.edu.br', '$2y$10$RCjaM7e2Hq/a/p56ggSTEeFvYlQC4GEUgayQ476pn0SY1y1fN70R.', 'CAIKE123', 1, 0),
('123', 'Caike', 'ck@ceu.com', '$2y$10$w1m1cvEFWj4exWSbvll6FugnXw2RoksAEFrMg0FNZH9BAyV2CMFiC', 'CAIKE001', 1, 0),
('1234', 'Caike', 'ck@pceu.com', '$2y$10$w1m1cvEFWj4exWSbvll6FugnXw2RoksAEFrMg0FNZH9BAyV2CMFiC', NULL, 0, 0),
('36528418080', 'Jean Victor Gonçalves Martins', 'jeanvictornet@gmail.com', '$2y$10$nC1jU07miJxHZ2n1xP9RDe9Lco.wPGE1KlLeXt1ZKLTVJTmGrtTJq', NULL, 0, 1),
('59015506680', 'Jean Martins', 'j@gmail.com', '$2y$10$nC1jU07miJxHZ2n1xP9RDe9Lco.wPGE1KlLeXt1ZKLTVJTmGrtTJq', 'JEAN001', 1, 1);

INSERT INTO codigos_organizador (codigo, ativo, usado, data_criacao, data_uso, usado_por, criado_por, observacoes) VALUES 
('CAIKE123', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '12345678901', 'SISTEMA', 'Código utilizado pela Aurora'),
('CAIKE001', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '123', 'SISTEMA', 'Código de teste - Caike - ck@ceu.com'),
('JEAN001', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '59015506680', 'SISTEMA', 'Código utilizado por Jean Martins');

INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado, modalidade, imagem, inicio_inscricao, fim_inscricao) VALUES
(1, 'Workshop', 'Workshop de JavaScript', 'Sala 101', 'Aprenda conceitos básicos e avançados de JavaScript.', 'Todos', '2025-02-15 09:00:00', '2025-02-15 17:00:00', 8.0, 1, 'Presencial', '/ImagensEventos/JavaScript_Workshop.png', '2025-01-15 08:00:00', '2025-02-14 23:59:59'),
(2, 'Palestra', 'Palestra sobre IA', 'Auditório Principal', 'Discussão sobre o futuro da Inteligência Artificial.', 'Estudantes', '2025-03-10 14:00:00', '2025-03-10 16:00:00', 2.0, 1, 'Híbrido', '/ImagensEventos/Palestra_sobre_IA.png', '2025-02-10 08:00:00', '2025-03-09 23:59:59'),
(3, 'Curso', 'Curso de Python', 'Laboratório 2', 'Curso completo de programação em Python.', 'Iniciantes', '2025-04-05 08:00:00', '2025-04-12 18:00:00', 40.0, 1, 'Presencial', '/ImagensEventos/Python.png', '2025-03-05 08:00:00', '2025-04-04 23:59:59'),
(4, 'Semana', 'Semana da Tecnologia', 'Centro de Eventos', 'Uma semana repleta de atividades tecnológicas.', 'Todos', '2025-05-20 08:00:00', '2025-05-24 18:00:00', 50.0, 1, 'Híbrido', '/ImagensEventos/Semana_de_CienciaTecnologia.png', '2025-04-01 08:00:00', '2025-05-19 23:59:59'),
(5, 'Minicurso', 'Minicurso de Git', 'Sala 202', 'Aprenda versionamento de código com Git.', 'Programadores', '2025-06-01 13:00:00', '2025-06-01 17:00:00', 4.0, 0, 'Online', '/ImagensEventos/Git_e_Github.png', '2025-05-01 08:00:00', '2025-05-31 23:59:59'),
(6, 'Workshop', 'Workshop de Design UX', 'Studio Criativo', 'Fundamentos de experiência do usuário.', 'Designers', '2025-07-15 09:00:00', '2025-07-15 18:00:00', 9.0, 1, 'Presencial', '/ImagensEventos/DesignUX.png', '2025-06-15 08:00:00', '2025-07-14 23:59:59'),
(7, 'Hackathon', 'Hackathon CEU 2025', 'Pavilhão Principal', 'Competição de desenvolvimento de 48 horas.', 'Desenvolvedores', '2025-08-10 18:00:00', '2025-08-12 18:00:00', 48.0, 1, 'Presencial', '/ImagensEventos/Hackathon_CEU.png', '2025-07-01 08:00:00', '2025-08-09 23:59:59'),
(8, 'Palestra', 'Futuro do Trabalho', 'Anfiteatro', 'Como a tecnologia está mudando o mercado de trabalho.', 'Profissionais', '2025-09-05 19:00:00', '2025-09-05 21:00:00', 2.0, 0, 'Híbrido', '/ImagensEventos/Futuro_do_Trabalho.png', '2025-08-05 08:00:00', '2025-09-04 23:59:59'),
(9, 'Curso', 'Curso de React', 'Lab de Informática', 'Desenvolvimento de interfaces modernas com React.', 'Desenvolvedores', '2025-10-01 08:00:00', '2025-10-15 17:00:00', 60.0, 1, 'Online', '/ImagensEventos/React.png', '2025-09-01 08:00:00', '2025-09-30 23:59:59'),
(10, 'Workshop', 'Workshop de Fotografia', 'Estúdio Fotográfico', 'Técnicas básicas e avançadas de fotografia.', 'Todos', '2025-11-12 10:00:00', '2025-11-12 16:00:00', 6.0, 0, 'Presencial', '/ImagensEventos/Workshop_de_Fotografia.png', '2025-10-12 08:00:00', '2025-11-11 23:59:59'),
(11, 'Minicurso', 'Introdução ao Machine Learning', 'Sala 303', 'Conceitos básicos de aprendizado de máquina.', 'Estudantes', '2025-12-01 14:00:00', '2025-12-01 18:00:00', 4.0, 1, 'Híbrido', '/ImagensEventos/Machine_Learning.png', '2025-11-01 08:00:00', '2025-11-30 23:59:59'),
(12, 'Palestra', 'Sustentabilidade na Tech', 'Sala Verde', 'Como tornar a tecnologia mais sustentável.', 'Todos', '2025-01-25 16:00:00', '2025-01-25 18:00:00', 2.0, 0, 'Online', '/ImagensEventos/Sustentabilidade_na_Tech.png', '2024-12-25 08:00:00', '2025-01-24 23:59:59'),
(13, 'Curso', 'Desenvolvimento Mobile', 'Lab Mobile', 'Criação de aplicativos para Android e iOS.', 'Desenvolvedores', '2025-02-28 09:00:00', '2025-03-14 17:00:00', 45.0, 1, 'Presencial', '/ImagensEventos/Desenvolvimento_mobile.png', '2025-01-28 08:00:00', '2025-02-27 23:59:59'),
(14, 'Workshop', 'Workshop de Blockchain', 'Auditório Tech', 'Entenda a tecnologia por trás das criptomoedas.', 'Todos', '2025-04-18 13:00:00', '2025-04-18 17:00:00', 4.0, 1, 'Híbrido', '/ImagensEventos/Blockchain.png', '2025-03-18 08:00:00', '2025-04-17 23:59:59'),
(15, 'Semana', 'Semana da Inovação', 'Campus Principal', 'Evento de inovação e empreendedorismo.', 'Empreendedores', '2025-06-15 08:00:00', '2025-06-19 18:00:00', 50.0, 1, 'Presencial', '/ImagensEventos/Semana_de_Inovacao.png', '2025-05-01 08:00:00', '2025-06-14 23:59:59'),
(16, 'Minicurso', 'Cybersecurity Basics', 'Sala de Segurança', 'Fundamentos de segurança da informação.', 'Todos', '2025-08-22 14:00:00', '2025-08-22 18:00:00', 4.0, 1, 'Online', '/ImagensEventos/CyberSecurity.png', '2025-07-22 08:00:00', '2025-08-21 23:59:59'),
(17, 'Conferência', 'Conferência de Cloud Computing', 'Auditório 3', 'Conferência sobre tendências em computação em nuvem.', 'Estudantes', '2025-12-03 13:00:00', '2025-12-03 18:00:00', 5.0, 1, 'Híbrido', '/ImagensEventos/Cloud_Computing.png', '2025-11-03 08:00:00', '2025-12-02 23:59:59');

INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES
(1, '/ImagensEventos/JavaScript_Workshop.png', 0, 1),
(2, '/ImagensEventos/Palestra_sobre_IA.png', 0, 1),
(3, '/ImagensEventos/Python.png', 0, 1),
(4, '/ImagensEventos/Semana_de_CienciaTecnologia.png', 0, 1),
(5, '/ImagensEventos/Git_e_Github.png', 0, 1),
(6, '/ImagensEventos/DesignUX.png', 0, 1),
(7, '/ImagensEventos/Hackathon_CEU.png', 0, 1),
(8, '/ImagensEventos/Futuro_do_Trabalho.png', 0, 1),
(9, '/ImagensEventos/React.png', 0, 1),
(10, '/ImagensEventos/Workshop_de_Fotografia.png', 0, 1),
(11, '/ImagensEventos/Machine_Learning.png', 0, 1),
(12, '/ImagensEventos/Sustentabilidade_na_Tech.png', 0, 1),
(13, '/ImagensEventos/Desenvolvimento_mobile.png', 0, 1),
(14, '/ImagensEventos/Blockchain.png', 0, 1),
(15, '/ImagensEventos/Semana_de_Inovacao.png', 0, 1),
(16, '/ImagensEventos/CyberSecurity.png', 0, 1),
(17, '/ImagensEventos/Cloud_Computing.png', 0, 1);

INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES
(1, '/ImagensEventos/CEU-ImagemEvento.png', 1, 0),
(12, '/ImagensEventos/CEU-ImagemEvento.png', 1, 0),
(13, '/ImagensEventos/CEU-ImagemEvento.png', 1, 0);

INSERT INTO organiza (cod_evento, CPF) VALUES
(1, '12345678901'), (2, '12345678901'), (3, '12345678901'), (4, '12345678901'),
(5, '12345678901'), (6, '12345678901'), (7, '12345678901'), (8, '12345678901'),
(9, '12345678901'), (10, '12345678901'), (11, '12345678901'), (12, '12345678901'),
(13, '12345678901'), (14, '12345678901'), (15, '12345678901'), (16, '12345678901'),
(17, '12345678901');

INSERT INTO colaboradores_evento (cod_evento, CPF, papel, criado_em) VALUES
(1, '123', 'colaborador', CURRENT_TIMESTAMP),
(7, '123', 'colaborador', CURRENT_TIMESTAMP);

INSERT INTO inscricao (CPF, cod_evento, data_inscricao, status, presenca_confirmada, certificado_emitido) VALUES
('1234', 1, CURRENT_TIMESTAMP, 'ativa', 0, 0);

COMMIT;

SHOW TABLES;