create database if not exists CEU_bd;
use CEU_bd;

create table if not exists evento(
cod_evento int primary key not null,
categoria varchar(40) not null,
nome varchar(100) not null,
lugar varchar(40) not null,
descricao varchar(500),
publico_alvo varchar(100),
inicio datetime not null,
conclusao datetime not null,
duracao float, 
certificado tinyint(1) not null default 0,
modalidade enum('Presencial','Online','Híbrido') not null default 'Presencial',
imagem varchar(255) null
);

create table if not exists certificado(
cod_verificacao varchar(8),
modelo varchar(255),
tipo varchar(100),
primary key (cod_verificacao)
);

create table if not exists usuario(
CPF char(11) primary key,
Nome varchar(100),
Email varchar(100),
Senha varchar(255),
RA char(7) null,
Codigo varchar(8),
Organizador tinyint(1) not null default 0,
TemaSite tinyint(1) not null default 0,
FotoPerfil varchar(255) null,
constraint chk_codigo_organizador check (
    (Organizador = 0) OR 
    (Organizador = 1 AND Codigo is not null)
),
constraint chk_tema_site check (TemaSite in (0,1))
);

ALTER TABLE usuario ADD COLUMN IF NOT EXISTS TemaSite tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS FotoPerfil varchar(255) NULL;

ALTER TABLE evento ADD COLUMN IF NOT EXISTS modalidade enum('Presencial','Online','Híbrido') NOT NULL DEFAULT 'Presencial';
ALTER TABLE evento ADD COLUMN IF NOT EXISTS imagem varchar(255) NULL;

create table if not exists lista_de_participantes(
CPF char(11) primary key,
Nome varchar(100),
RA char(7)
);

create table if not exists organiza(
cod_evento int ,
CPF char(11),
primary key (cod_evento,CPF),
foreign key (cod_evento) references evento(cod_evento),
foreign key (CPF) references usuario(CPF)
);

CREATE TABLE IF NOT EXISTS inscricao (
    CPF char(11) NOT NULL,
    cod_evento int NOT NULL,
    data_inscricao timestamp DEFAULT CURRENT_TIMESTAMP,
    status enum('ativa', 'cancelada') NOT NULL DEFAULT 'ativa',
    PRIMARY KEY (CPF, cod_evento),
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE
);

create table if not exists codigos_organizador (
    id int auto_increment primary key,
    codigo varchar(8) unique not null,
    ativo tinyint(1) not null default 1,
    usado tinyint(1) not null default 0,
    data_criacao timestamp default current_timestamp,
    data_uso timestamp null,
    usado_por varchar(11) null comment 'CPF do usuário que usou o código',
    criado_por varchar(100) default 'SISTEMA' comment 'Quem criou o código',
    observacoes text null comment 'Observações sobre o código'
);

CREATE TABLE IF NOT EXISTS imagens_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_evento INT NOT NULL,
    caminho_imagem VARCHAR(255) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CPF char(11) NOT NULL,
    tipo VARCHAR(50) NOT NULL COMMENT 'Tipo: inscricao, desinscricao, evento_cancelado, evento_prestes_iniciar, etc',
    mensagem VARCHAR(255) NOT NULL,
    cod_evento INT NULL COMMENT 'Referência ao evento se aplicável',
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE,
    FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS solicitacoes_redefinicao_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    CPF char(11) NULL COMMENT 'CPF do usuário se encontrado',
    nome_usuario VARCHAR(100) NULL COMMENT 'Nome do usuário',
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'resolvida', 'cancelada') NOT NULL DEFAULT 'pendente',
    data_resolucao TIMESTAMP NULL,
    resolvido_por VARCHAR(100) NULL COMMENT 'Admin que resolveu',
    observacoes TEXT NULL
);

INSERT INTO usuario (CPF, Nome, Email, Senha, Codigo, Organizador, TemaSite) VALUES
('12345678901', 'Aurora Sobrinho', 'aurora@ceu.edu.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CAIKE123', 1, 0);

INSERT INTO codigos_organizador (codigo, ativo, usado, data_criacao, data_uso, usado_por, criado_por, observacoes)
VALUES ('CAIKE123', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '12345678901', 'SISTEMA', 'Código utilizado pela Aurora');

INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado, modalidade, imagem) VALUES
(1, 'Workshop', 'Workshop de JavaScript', 'Sala 101', 'Aprenda conceitos básicos e avançados de JavaScript.', 'Todos', '2025-02-15 09:00:00', '2025-02-15 17:00:00', 8.0, 1, 'Presencial', 'ImagensEventos/JavaScript_Workshop.png'),
(2, 'Palestra', 'Palestra sobre IA', 'Auditório Principal', 'Discussão sobre o futuro da Inteligência Artificial.', 'Estudantes', '2025-03-10 14:00:00', '2025-03-10 16:00:00', 2.0, 1, 'Híbrido', 'ImagensEventos/Palestra_sobre_IA.png'),
(3, 'Curso', 'Curso de Python', 'Laboratório 2', 'Curso completo de programação em Python.', 'Iniciantes', '2025-04-05 08:00:00', '2025-04-12 18:00:00', 40.0, 1, 'Presencial', 'ImagensEventos/Python.png'),
(4, 'Semana', 'Semana da Tecnologia', 'Centro de Eventos', 'Uma semana repleta de atividades tecnológicas.', 'Todos', '2025-05-20 08:00:00', '2025-05-24 18:00:00', 50.0, 1, 'Híbrido', 'ImagensEventos/Semana_de_CienciaTecnologia.png'),
(5, 'Minicurso', 'Minicurso de Git', 'Sala 202', 'Aprenda versionamento de código com Git.', 'Programadores', '2025-06-01 13:00:00', '2025-06-01 17:00:00', 4.0, 0, 'Online', 'ImagensEventos/Git_e_Github.png'),
(6, 'Workshop', 'Workshop de Design UX', 'Studio Criativo', 'Fundamentos de experiência do usuário.', 'Designers', '2025-07-15 09:00:00', '2025-07-15 18:00:00', 9.0, 1, 'Presencial', 'ImagensEventos/DesignUX.png'),
(7, 'Hackathon', 'Hackathon CEU 2025', 'Pavilhão Principal', 'Competição de desenvolvimento de 48 horas.', 'Desenvolvedores', '2025-08-10 18:00:00', '2025-08-12 18:00:00', 48.0, 1, 'Presencial', 'ImagensEventos/Hackathon_CEU.png'),
(8, 'Palestra', 'Futuro do Trabalho', 'Anfiteatro', 'Como a tecnologia está mudando o mercado de trabalho.', 'Profissionais', '2025-09-05 19:00:00', '2025-09-05 21:00:00', 2.0, 0, 'Híbrido', 'ImagensEventos/Futuro_do_Trabalho.png'),
(9, 'Curso', 'Curso de React', 'Lab de Informática', 'Desenvolvimento de interfaces modernas com React.', 'Desenvolvedores', '2025-10-01 08:00:00', '2025-10-15 17:00:00', 60.0, 1, 'Online', 'ImagensEventos/React.png'),
(10, 'Workshop', 'Workshop de Fotografia', 'Estúdio Fotográfico', 'Técnicas básicas e avançadas de fotografia.', 'Todos', '2025-11-12 10:00:00', '2025-11-12 16:00:00', 6.0, 0, 'Presencial', 'ImagensEventos/Workshop_de_Fotografia.png'),
(11, 'Minicurso', 'Introdução ao Machine Learning', 'Sala 303', 'Conceitos básicos de aprendizado de máquina.', 'Estudantes', '2025-12-01 14:00:00', '2025-12-01 18:00:00', 4.0, 1, 'Híbrido', 'ImagensEventos/Machine_Learning.png'),
(12, 'Palestra', 'Sustentabilidade na Tech', 'Sala Verde', 'Como tornar a tecnologia mais sustentável.', 'Todos', '2025-01-25 16:00:00', '2025-01-25 18:00:00', 2.0, 0, 'Online', 'ImagensEventos/Sustentabilidade_na_Tech.png'),
(13, 'Curso', 'Desenvolvimento Mobile', 'Lab Mobile', 'Criação de aplicativos para Android e iOS.', 'Desenvolvedores', '2025-02-28 09:00:00', '2025-03-14 17:00:00', 45.0, 1, 'Presencial', 'ImagensEventos/Desenvolvimento_mobile.png'),
(14, 'Workshop', 'Workshop de Blockchain', 'Auditório Tech', 'Entenda a tecnologia por trás das criptomoedas.', 'Todos', '2025-04-18 13:00:00', '2025-04-18 17:00:00', 4.0, 1, 'Híbrido', 'ImagensEventos/Blockchain.png'),
(15, 'Semana', 'Semana da Inovação', 'Campus Principal', 'Evento de inovação e empreendedorismo.', 'Empreendedores', '2025-06-15 08:00:00', '2025-06-19 18:00:00', 50.0, 1, 'Presencial', 'ImagensEventos/Semana_de_Inovacao.png'),
(16, 'Minicurso', 'Cybersecurity Basics', 'Sala de Segurança', 'Fundamentos de segurança da informação.', 'Todos', '2025-08-22 14:00:00', '2025-08-22 18:00:00', 4.0, 1, 'Online', 'ImagensEventos/CyberSecurity.png'),
(17, 'Conferência', 'Conferência de Cloud Computing', 'Auditório 3', 'Conferência sobre tendências em computação em nuvem.', 'Estudantes', '2025-12-03 13:00:00', '2025-12-03 18:00:00', 5.0, 1, 'Híbrido', 'ImagensEventos/Cloud_Computing.png');

-- Popula tabela imagens_evento com as imagens existentes na pasta ImagensEventos
INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES
(1, 'ImagensEventos/JavaScript_Workshop.png', 0, 1),
(2, 'ImagensEventos/Palestra_sobre_IA.png', 0, 1),
(3, 'ImagensEventos/Python.png', 0, 1),
(4, 'ImagensEventos/Semana_de_CienciaTecnologia.png', 0, 1),
(5, 'ImagensEventos/Git_e_Github.png', 0, 1),
(6, 'ImagensEventos/DesignUX.png', 0, 1),
(7, 'ImagensEventos/Hackathon_CEU.png', 0, 1),
(8, 'ImagensEventos/Futuro_do_Trabalho.png', 0, 1),
(9, 'ImagensEventos/React.png', 0, 1),
(10, 'ImagensEventos/Workshop_de_Fotografia.png', 0, 1),
(11, 'ImagensEventos/Machine_Learning.png', 0, 1),
(12, 'ImagensEventos/Sustentabilidade_na_Tech.png', 0, 1),
(13, 'ImagensEventos/Desenvolvimento_mobile.png', 0, 1),
(14, 'ImagensEventos/Blockchain.png', 0, 1),
(15, 'ImagensEventos/Semana_de_Inovacao.png', 0, 1),
(16, 'ImagensEventos/CyberSecurity.png', 0, 1),
(17, 'ImagensEventos/Cloud_Computing.png', 0, 1);

INSERT INTO organiza (cod_evento, CPF) VALUES
(1, '12345678901'),
(2, '12345678901'),
(3, '12345678901'),
(4, '12345678901'),
(5, '12345678901'),
(6, '12345678901'),
(7, '12345678901'),
(8, '12345678901'),
(9, '12345678901'),
(10, '12345678901'),
(11, '12345678901'),
(12, '12345678901'),
(13, '12345678901'),
(14, '12345678901'),
(15, '12345678901'),
(16, '12345678901'),
(17, '12345678901');

show tables;

