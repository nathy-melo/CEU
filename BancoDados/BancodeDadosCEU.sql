drop database if exists CEU_bd;

create database CEU_bd default character
set
    utf8mb4 collate utf8mb4_unicode_ci;

use CEU_bd;

set
    sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

set
    autocommit = 0;

start transaction;

set
    time_zone = "-03:00";

create table
    evento (
        cod_evento int primary key not null,
        categoria varchar(40) not null,
        nome varchar(100) not null,
        lugar varchar(40) not null,
        descricao varchar(500),
        publico_alvo varchar(100),
        inicio datatime not null,
        conclusao datatime not null,
        duracao float,
        certificado tinyint (1) not null default 0,
        modalidade ENUM ('Presencial', 'Online', 'Híbrido') not null default 'Presencial',
        imagem varchar(255) null,
        inicio_inscricao datatime null,
        fim_inscricao datatime null
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    certificado (
        cod_verificacao varchar(8) primary key,
        modelo varchar(255),
        tipo varchar(100)
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    usuario (
        CPF char(11) primary key,
        Nome varchar(100),
        Email varchar(100),
        Senha varchar(255),
        RA char(7) null,
        Codigo varchar(8),
        Organizador tinyint (1) not null default 0,
        TemaSite tinyint (1) not null default 0,
        FotoPerfil varchar(255) null,
        constraint chk_codigo_organizador check (
            (Organizador = 0)
            or (
                Organizador = 1
                and Codigo is not null
            )
        ),
        constraint chk_tema_site check (
            (TemaSite = 0)
            or (TemaSite = 1)
        )
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    lista_de_participantes (
        CPF char(11) primary key,
        Nome varchar(100),
        RA char(7)
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    organiza (
        cod_evento int,
        CPF char(11),
        primary key (cod_evento, CPF),
        foreign key (cod_evento) references evento (cod_evento) on delete cascade,
        foreign key (CPF) references usuario (CPF) on delete cascade
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    inscricao (
        CPF char(11) not null,
        cod_evento int not null,
        data_inscricao timestamp default current_timestamp,
        status ENUM ('ativa', 'cancelada') not null default 'ativa',
        presenca_confirmada tinyint (1) not null default 0,
        certificado_emitido tinyint (1) not null default 0,
        primary key (CPF, cod_evento),
        foreign key (CPF) references usuario (CPF) on delete cascade,
        foreign key (cod_evento) references evento (cod_evento) on delete cascade
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    codigos_organizador (
        id int auto_increment primary key,
        codigo varchar(8) unique not null,
        ativo tinyint (1) not null default 1,
        usado tinyint (1) not null default 0,
        data_criacao timestamp default current_timestamp,
        data_uso timestamp null,
        usado_por varchar(11) null comment 'CPF do usuário que usou o código',
        criado_por varchar(100) default 'SISTEMA' comment 'Quem criou o código',
        observacoes text null comment 'Observações sobre o código'
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    imagens_evento (
        id int auto_increment primary key,
        cod_evento int not null,
        caminho_imagem varchar(255) not null,
        ordem int not null default 0,
        principal tinyint (1) not null default 0,
        data_upload timestamp default current_timestamp,
        foreign key (cod_evento) references evento (cod_evento) on delete cascade
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    notificacoes (
        id int auto_increment primary key,
        CPF char(11) not null,
        tipo varchar(50) not null comment 'Tipo: inscricao, desinscricao, evento_cancelado, evento_prestes_iniciar, etc',
        mensagem varchar(255) not null,
        cod_evento int null comment 'Referência ao evento se aplicável',
        lida tinyint (1) not null default 0,
        data_criacao timestamp default current_timestamp,
        foreign key (CPF) references usuario (CPF) on delete cascade,
        foreign key (cod_evento) references evento (cod_evento) on delete set null
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    colaboradores_evento (
        id int auto_increment primary key,
        cod_evento int not null,
        CPF char(11) not null,
        papel ENUM ('colaborador', 'coorganizador') not null default 'colaborador',
        presenca_confirmada tinyint (1) not null default 0,
        certificado_emitido tinyint (1) not null default 0,
        criado_em timestamp default current_timestamp,
        unique key uk_evento_cpf (cod_evento, CPF),
        foreign key (cod_evento) references evento (cod_evento) on delete cascade,
        foreign key (CPF) references usuario (CPF) on delete cascade
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    solicitacoes_colaboracao (
        id int auto_increment primary key,
        cod_evento int not null,
        cpf_solicitante char(11) not null,
        status ENUM ('pendente', 'aprovada', 'recusada') not null default 'pendente',
        mensagem text null,
        data_criacao timestamp default current_timestamp,
        data_resolucao timestamp null,
        foreign key (cod_evento) references evento (cod_evento) on delete cascade,
        foreign key (cpf_solicitante) references usuario (CPF) on delete cascade,
        unique key uk_pedido (cod_evento, cpf_solicitante)
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

create table
    solicitacoes_redefinicao_senha (
        id int auto_increment primary key,
        email varchar(100) not null,
        CPF char(11) null comment 'CPF do usuário se encontrado',
        nome_usuario varchar(100) null comment 'Nome do usuário',
        data_solicitacao timestamp default current_timestamp,
        status ENUM ('pendente', 'resolvida', 'cancelada') not null default 'pendente',
        data_resolucao timestamp null,
        resolvido_por varchar(100) null comment 'Admin que resolveu',
        observacoes text null
    ) engine = InnoDB default CHARset = utf8mb4 collate = utf8mb4_unicode_ci;

insert into
    usuario (
        CPF,
        Nome,
        Email,
        Senha,
        Codigo,
        Organizador,
        TemaSite
    )
values
    (
        '12345678901',
        'Aurora Sobrinho',
        'aurora@ceu.edu.br',
        '$2y$10$RCjaM7e2Hq/a/p56ggSTEeFvYlQC4GEUgayQ476pn0SY1y1fN70R.',
        'CAIKE123',
        1,
        0
    ),
    (
        '123',
        'Caike',
        'ck@ceu.com',
        '$2y$10$w1m1cvEFWj4exWSbvll6FugnXw2RoksAEFrMg0FNZH9BAyV2CMFiC',
        'CAIKE001',
        1,
        0
    ),
    (
        '1234',
        'Caike',
        'ck@pceu.com',
        '$2y$10$w1m1cvEFWj4exWSbvll6FugnXw2RoksAEFrMg0FNZH9BAyV2CMFiC',
        null,
        0,
        0
    );

insert into
    codigos_organizador (
        codigo,
        ativo,
        usado,
        data_criacao,
        data_uso,
        usado_por,
        criado_por,
        observacoes
    )
values
    (
        'CAIKE123',
        1,
        1,
        current_timestamp,
        current_timestamp,
        '12345678901',
        'SISTEMA',
        'Código utilizado pela Aurora'
    ),
    (
        'CAIKE001',
        1,
        1,
        current_timestamp,
        current_timestamp,
        '123',
        'SISTEMA',
        'Código de teste - Caike - ck@ceu.com'
    );

insert into
    evento (
        cod_evento,
        categoria,
        nome,
        lugar,
        descricao,
        publico_alvo,
        inicio,
        conclusao,
        duracao,
        certificado,
        modalidade,
        imagem,
        inicio_inscricao,
        fim_inscricao
    )
values
    (
        1,
        'Workshop',
        'Workshop de JavaScript',
        'Sala 101',
        'Aprenda conceitos básicos e avançados de JavaScript.',
        'Todos',
        '2025-02-15 09:00:00',
        '2025-02-15 17:00:00',
        8.0,
        1,
        'Presencial',
        '/ImagensEventos/JavaScript_Workshop.png',
        '2025-01-15 08:00:00',
        '2025-02-14 23:59:59'
    ),
    (
        2,
        'Palestra',
        'Palestra sobre IA',
        'Auditório Principal',
        'Discussão sobre o futuro da Inteligência Artificial.',
        'Estudantes',
        '2025-03-10 14:00:00',
        '2025-03-10 16:00:00',
        2.0,
        1,
        'Híbrido',
        '/ImagensEventos/Palestra_sobre_IA.png',
        '2025-02-10 08:00:00',
        '2025-03-09 23:59:59'
    ),
    (
        3,
        'Curso',
        'Curso de Python',
        'Laboratório 2',
        'Curso completo de programação em Python.',
        'Iniciantes',
        '2025-04-05 08:00:00',
        '2025-04-12 18:00:00',
        40.0,
        1,
        'Presencial',
        '/ImagensEventos/Python.png',
        '2025-03-05 08:00:00',
        '2025-04-04 23:59:59'
    ),
    (
        4,
        'Semana',
        'Semana da Tecnologia',
        'Centro de Eventos',
        'Uma semana repleta de atividades tecnológicas.',
        'Todos',
        '2025-05-20 08:00:00',
        '2025-05-24 18:00:00',
        50.0,
        1,
        'Híbrido',
        '/ImagensEventos/Semana_de_CienciaTecnologia.png',
        '2025-04-01 08:00:00',
        '2025-05-19 23:59:59'
    ),
    (
        5,
        'Minicurso',
        'Minicurso de Git',
        'Sala 202',
        'Aprenda versionamento de código com Git.',
        'Programadores',
        '2025-06-01 13:00:00',
        '2025-06-01 17:00:00',
        4.0,
        0,
        'Online',
        '/ImagensEventos/Git_e_Github.png',
        '2025-05-01 08:00:00',
        '2025-05-31 23:59:59'
    ),
    (
        6,
        'Workshop',
        'Workshop de Design UX',
        'Studio Criativo',
        'Fundamentos de experiência do usuário.',
        'Designers',
        '2025-07-15 09:00:00',
        '2025-07-15 18:00:00',
        9.0,
        1,
        'Presencial',
        '/ImagensEventos/DesignUX.png',
        '2025-06-15 08:00:00',
        '2025-07-14 23:59:59'
    ),
    (
        7,
        'Hackathon',
        'Hackathon CEU 2025',
        'Pavilhão Principal',
        'Competição de desenvolvimento de 48 horas.',
        'Desenvolvedores',
        '2025-08-10 18:00:00',
        '2025-08-12 18:00:00',
        48.0,
        1,
        'Presencial',
        '/ImagensEventos/Hackathon_CEU.png',
        '2025-07-01 08:00:00',
        '2025-08-09 23:59:59'
    ),
    (
        8,
        'Palestra',
        'Futuro do Trabalho',
        'Anfiteatro',
        'Como a tecnologia está mudando o mercado de trabalho.',
        'Profissionais',
        '2025-09-05 19:00:00',
        '2025-09-05 21:00:00',
        2.0,
        0,
        'Híbrido',
        '/ImagensEventos/Futuro_do_Trabalho.png',
        '2025-08-05 08:00:00',
        '2025-09-04 23:59:59'
    ),
    (
        9,
        'Curso',
        'Curso de React',
        'Lab de Informática',
        'Desenvolvimento de interfaces modernas com React.',
        'Desenvolvedores',
        '2025-10-01 08:00:00',
        '2025-10-15 17:00:00',
        60.0,
        1,
        'Online',
        '/ImagensEventos/React.png',
        '2025-09-01 08:00:00',
        '2025-09-30 23:59:59'
    ),
    (
        10,
        'Workshop',
        'Workshop de Fotografia',
        'Estúdio Fotográfico',
        'Técnicas básicas e avançadas de fotografia.',
        'Todos',
        '2025-11-12 10:00:00',
        '2025-11-12 16:00:00',
        6.0,
        0,
        'Presencial',
        '/ImagensEventos/Workshop_de_Fotografia.png',
        '2025-10-12 08:00:00',
        '2025-11-11 23:59:59'
    ),
    (
        11,
        'Minicurso',
        'Introdução ao Machine Learning',
        'Sala 303',
        'Conceitos básicos de aprendizado de máquina.',
        'Estudantes',
        '2025-12-01 14:00:00',
        '2025-12-01 18:00:00',
        4.0,
        1,
        'Híbrido',
        '/ImagensEventos/Machine_Learning.png',
        '2025-11-01 08:00:00',
        '2025-11-30 23:59:59'
    ),
    (
        12,
        'Palestra',
        'Sustentabilidade na Tech',
        'Sala Verde',
        'Como tornar a tecnologia mais sustentável.',
        'Todos',
        '2025-01-25 16:00:00',
        '2025-01-25 18:00:00',
        2.0,
        0,
        'Online',
        '/ImagensEventos/Sustentabilidade_na_Tech.png',
        '2024-12-25 08:00:00',
        '2025-01-24 23:59:59'
    ),
    (
        13,
        'Curso',
        'Desenvolvimento Mobile',
        'Lab Mobile',
        'Criação de aplicativos para Android e iOS.',
        'Desenvolvedores',
        '2025-02-28 09:00:00',
        '2025-03-14 17:00:00',
        45.0,
        1,
        'Presencial',
        '/ImagensEventos/Desenvolvimento_mobile.png',
        '2025-01-28 08:00:00',
        '2025-02-27 23:59:59'
    ),
    (
        14,
        'Workshop',
        'Workshop de Blockchain',
        'Auditório Tech',
        'Entenda a tecnologia por trás das criptomoedas.',
        'Todos',
        '2025-04-18 13:00:00',
        '2025-04-18 17:00:00',
        4.0,
        1,
        'Híbrido',
        '/ImagensEventos/Blockchain.png',
        '2025-03-18 08:00:00',
        '2025-04-17 23:59:59'
    ),
    (
        15,
        'Semana',
        'Semana da Inovação',
        'Campus Principal',
        'Evento de inovação e empreendedorismo.',
        'Empreendedores',
        '2025-06-15 08:00:00',
        '2025-06-19 18:00:00',
        50.0,
        1,
        'Presencial',
        '/ImagensEventos/Semana_de_Inovacao.png',
        '2025-05-01 08:00:00',
        '2025-06-14 23:59:59'
    ),
    (
        16,
        'Minicurso',
        'Cybersecurity Basics',
        'Sala de Segurança',
        'Fundamentos de segurança da informação.',
        'Todos',
        '2025-08-22 14:00:00',
        '2025-08-22 18:00:00',
        4.0,
        1,
        'Online',
        '/ImagensEventos/CyberSecurity.png',
        '2025-07-22 08:00:00',
        '2025-08-21 23:59:59'
    ),
    (
        17,
        'Conferência',
        'Conferência de Cloud Computing',
        'Auditório 3',
        'Conferência sobre tendências em computação em nuvem.',
        'Estudantes',
        '2025-12-03 13:00:00',
        '2025-12-03 18:00:00',
        5.0,
        1,
        'Híbrido',
        '/ImagensEventos/Cloud_Computing.png',
        '2025-11-03 08:00:00',
        '2025-12-02 23:59:59'
    );

insert into
    imagens_evento (cod_evento, caminho_imagem, ordem, principal)
values
    (
        1,
        '/ImagensEventos/JavaScript_Workshop.png',
        0,
        1
    ),
    (2, '/ImagensEventos/Palestra_sobre_IA.png', 0, 1),
    (3, '/ImagensEventos/Python.png', 0, 1),
    (
        4,
        '/ImagensEventos/Semana_de_CienciaTecnologia.png',
        0,
        1
    ),
    (5, '/ImagensEventos/Git_e_Github.png', 0, 1),
    (6, '/ImagensEventos/DesignUX.png', 0, 1),
    (7, '/ImagensEventos/Hackathon_CEU.png', 0, 1),
    (8, '/ImagensEventos/Futuro_do_Trabalho.png', 0, 1),
    (9, '/ImagensEventos/React.png', 0, 1),
    (
        10,
        '/ImagensEventos/Workshop_de_Fotografia.png',
        0,
        1
    ),
    (11, '/ImagensEventos/Machine_Learning.png', 0, 1),
    (
        12,
        '/ImagensEventos/Sustentabilidade_na_Tech.png',
        0,
        1
    ),
    (
        13,
        '/ImagensEventos/Desenvolvimento_mobile.png',
        0,
        1
    ),
    (14, '/ImagensEventos/Blockchain.png', 0, 1),
    (
        15,
        '/ImagensEventos/Semana_de_Inovacao.png',
        0,
        1
    ),
    (16, '/ImagensEventos/CyberSecurity.png', 0, 1),
    (17, '/ImagensEventos/Cloud_Computing.png', 0, 1);

insert into
    imagens_evento (cod_evento, caminho_imagem, ordem, principal)
values
    (1, '/ImagensEventos/CEU-ImagemEvento.png', 1, 0),
    (12, '/ImagensEventos/CEU-ImagemEvento.png', 1, 0),
    (13, '/ImagensEventos/CEU-ImagemEvento.png', 1, 0);

insert into
    organiza (cod_evento, CPF)
values
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

insert into
    colaboradores_evento (cod_evento, CPF, papel, criado_em)
values
    (1, '123', 'colaborador', current_timestamp),
    (7, '123', 'colaborador', current_timestamp);

insert into
    inscricao (
        CPF,
        cod_evento,
        data_inscricao,
        status,
        presenca_confirmada,
        certificado_emitido
    )
values
    ('1234', 1, current_timestamp, 'ativa', 0, 0);

commit;

show tables;