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
duracao float, -- pesquisar se isso está certo mesmo
certificado tinyint(1) not null default 0,
-- Novos atributos do evento
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
constraint chk_codigo_organizador check (
    (Organizador = 0) OR 
    (Organizador = 1 AND Codigo is not null)
),
constraint chk_tema_site check (TemaSite in (0,1))
);

-- Upgrade seguro: adiciona a coluna se já existir a tabela e a coluna ainda não existir
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS TemaSite tinyint(1) NOT NULL DEFAULT 0;

-- Upgrade seguro para novos atributos do evento
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

-- Tabela para códigos de organizador
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

-- Inserção de dados de exemplo para eventos
INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado, modalidade, imagem) VALUES
(1, 'Workshop', 'Workshop de JavaScript', 'Sala 101', 'Aprenda conceitos básicos e avançados de JavaScript.', 'Todos', '2025-02-15 09:00:00', '2025-02-15 17:00:00', 8.0, 1, 'Presencial', 'Imagens/20250916_084902.jpg'),
(2, 'Palestra', 'Palestra sobre IA', 'Auditório Principal', 'Discussão sobre o futuro da Inteligência Artificial.', 'Estudantes', '2025-03-10 14:00:00', '2025-03-10 16:00:00', 2.0, 1, 'Híbrido', 'Imagens/20250923_131004.jpg'),
(3, 'Curso', 'Curso de Python', 'Laboratório 2', 'Curso completo de programação em Python.', 'Iniciantes', '2025-04-05 08:00:00', '2025-04-12 18:00:00', 40.0, 1, 'Presencial', 'Imagens/20250916_084902.jpg'),
(4, 'Semana', 'Semana da Tecnologia', 'Centro de Eventos', 'Uma semana repleta de atividades tecnológicas.', 'Todos', '2025-05-20 08:00:00', '2025-05-24 18:00:00', 50.0, 1, 'Híbrido', 'Imagens/20250923_131004.jpg'),
(5, 'Minicurso', 'Minicurso de Git', 'Sala 202', 'Aprenda versionamento de código com Git.', 'Programadores', '2025-06-01 13:00:00', '2025-06-01 17:00:00', 4.0, 0, 'Online', 'Imagens/20250916_084902.jpg'),
(6, 'Workshop', 'Workshop de Design UX', 'Studio Criativo', 'Fundamentos de experiência do usuário.', 'Designers', '2025-07-15 09:00:00', '2025-07-15 18:00:00', 9.0, 1, 'Presencial', 'Imagens/20250923_131004.jpg'),
(7, 'Hackathon', 'Hackathon CEU 2025', 'Pavilhão Principal', 'Competição de desenvolvimento de 48 horas.', 'Desenvolvedores', '2025-08-10 18:00:00', '2025-08-12 18:00:00', 48.0, 1, 'Presencial', 'Imagens/20250916_084902.jpg'),
(8, 'Palestra', 'Futuro do Trabalho', 'Anfiteatro', 'Como a tecnologia está mudando o mercado de trabalho.', 'Profissionais', '2025-09-05 19:00:00', '2025-09-05 21:00:00', 2.0, 0, 'Híbrido', 'Imagens/20250923_131004.jpg'),
(9, 'Curso', 'Curso de React', 'Lab de Informática', 'Desenvolvimento de interfaces modernas com React.', 'Desenvolvedores', '2025-10-01 08:00:00', '2025-10-15 17:00:00', 60.0, 1, 'Online', 'Imagens/20250916_084902.jpg'),
(10, 'Workshop', 'Workshop de Fotografia', 'Estúdio Fotográfico', 'Técnicas básicas e avançadas de fotografia.', 'Todos', '2025-11-12 10:00:00', '2025-11-12 16:00:00', 6.0, 0, 'Presencial', 'Imagens/20250923_131004.jpg'),
(11, 'Minicurso', 'Introdução ao Machine Learning', 'Sala 303', 'Conceitos básicos de aprendizado de máquina.', 'Estudantes', '2025-12-01 14:00:00', '2025-12-01 18:00:00', 4.0, 1, 'Híbrido', 'Imagens/20250916_084902.jpg'),
(12, 'Palestra', 'Sustentabilidade na Tech', 'Sala Verde', 'Como tornar a tecnologia mais sustentável.', 'Todos', '2025-01-25 16:00:00', '2025-01-25 18:00:00', 2.0, 0, 'Online', 'Imagens/20250923_131004.jpg'),
(13, 'Curso', 'Desenvolvimento Mobile', 'Lab Mobile', 'Criação de aplicativos para Android e iOS.', 'Desenvolvedores', '2025-02-28 09:00:00', '2025-03-14 17:00:00', 45.0, 1, 'Presencial', 'Imagens/20250916_084902.jpg'),
(14, 'Workshop', 'Workshop de Blockchain', 'Auditório Tech', 'Entenda a tecnologia por trás das criptomoedas.', 'Todos', '2025-04-18 13:00:00', '2025-04-18 17:00:00', 4.0, 1, 'Híbrido', 'Imagens/20250923_131004.jpg'),
(15, 'Semana', 'Semana da Inovação', 'Campus Principal', 'Evento de inovação e empreendedorismo.', 'Empreendedores', '2025-06-15 08:00:00', '2025-06-19 18:00:00', 50.0, 1, 'Presencial', 'Imagens/20250916_084902.jpg'),
(16, 'Minicurso', 'Cybersecurity Basics', 'Sala de Segurança', 'Fundamentos de segurança da informação.', 'Todos', '2025-08-22 14:00:00', '2025-08-22 18:00:00', 4.0, 1, 'Online', 'Imagens/20250923_131004.jpg'),
(17, 'Conferência', 'Conferência de Cloud Computing', 'Auditório 3', 'Conferência sobre tendências em computação em nuvem.', 'Estudantes', '2025-12-03 13:00:00', '2025-12-03 18:00:00', 5.0, 1, 'Híbrido', 'Imagens/20250923_131004.jpg');

show tables;

