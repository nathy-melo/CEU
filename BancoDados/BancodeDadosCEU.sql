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
Senha varchar(20),
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

show tables;

