create database if not exists CEU_bd;
use CEU_bd;

create table if not exists evento(
cod_evento int primary key not null,
categoria varchar(40) not null,
nome varchar(100) not null,
lugar varchar(40) not null,
descricao varchar(500),
inicio datetime not null,
conclusao datetime not null,
duracao float -- pesquisar se isso está certo mesmo  not null
);

create table if not exists certificado(
cod_verificacao varchar(8),
modelo varchar(255),
tipo varchar(100),
primary key (cod_verificacao)
);

create table if not exists participante(
CPF char(11) primary key,
Nome varchar(100),
Email varchar(100),
Senha varchar(20),
RA char(7));

create table if not exists organizador(
CPF char(11) primary key,
Nome varchar(100),
Email varchar(100),
Senha varchar(20),
Codigo varchar(8)
);

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
foreign key (CPF) references organizador(CPF)
);

show tables;

