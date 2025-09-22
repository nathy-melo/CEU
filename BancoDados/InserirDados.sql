-- Dados de exemplo para testar a integração
use CEU_bd;

-- Inserindo alguns eventos de exemplo (usar INSERT IGNORE para não duplicar se já existir)
INSERT IGNORE INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado) VALUES
(1, 'Oficina', 'Workshop de Programação', 'Laboratório de Informática', 'Uma oficina prática sobre desenvolvimento web com HTML, CSS e JavaScript.', 'Estudantes', '2025-03-07 13:00:00', '2025-03-07 17:00:00', 4.0, 0),
(2, 'Palestra', 'Inteligência Artificial no Futuro', 'Auditório Principal', 'Discussão sobre os impactos da IA na sociedade e no mercado de trabalho.', 'Estudantes', '2025-03-10 14:00:00', '2025-03-10 16:00:00', 2.0, 1),
(3, 'Curso', 'Introdução ao Design Gráfico', 'Sala de Design', 'Curso básico sobre princípios de design e uso de ferramentas gráficas.', 'Estudantes', '2025-03-15 09:00:00', '2025-03-15 12:00:00', 3.0, 0),
(4, 'Palestra', 'Inovação no Campus', 'Auditório', 'Palestra sobre iniciativas inovadoras no ambiente universitário.', 'Estudantes', '2025-03-07 09:00:00', '2025-03-07 10:30:00', 1.5, 1),
(5, 'Workshop', 'Programação Python', 'Laboratório 1', 'Workshop introdutório de lógica e programação em Python.', 'Estudantes', '2025-01-17 14:00:00', '2025-01-17 18:00:00', 4.0, 0),
(6, 'Curso', 'Curso Rápido de Oratória', 'Sala Multiuso', 'Curso curto com técnicas básicas de comunicação e oratória.', 'Estudantes', '2025-01-20 08:00:00', '2025-01-20 10:00:00', 2.0, 0),
(7, 'Seminário', 'Seminário de Sustentabilidade', 'Sala Verde', 'Discussão sobre práticas sustentáveis no campus e sociedade.', 'Estudantes', '2025-02-15 09:00:00', '2025-02-15 11:00:00', 2.0, 0),
(8, 'Conferência', 'Conferência de IA', 'Auditório Principal', 'Conferência apresentando aplicações de Inteligência Artificial.', 'Estudantes', '2025-02-20 13:30:00', '2025-02-20 17:30:00', 4.0, 1),
(9, 'Fórum', 'Inclusão Digital', 'Sala TI', 'Fórum para debate sobre inclusão digital e acessibilidade tecnológica.', 'Estudantes', '2025-02-28 10:00:00', '2025-02-28 13:00:00', 3.0, 0),
(10, 'Visita Técnica', 'Visita Técnica ao Laboratório', 'Laboratório Central', 'Visita orientada a laboratórios e infraestrutura técnica.', 'Estudantes', '2025-06-13 08:30:00', '2025-06-13 11:30:00', 3.0, 0),
(11, 'Oficina', 'Oficina de Design Thinking', 'Sala Criativa', 'Oficina prática sobre metodologia de Design Thinking.', 'Estudantes', '2025-06-30 09:00:00', '2025-06-30 12:00:00', 3.0, 0),
(12, 'Congresso', 'Congresso de Educação', 'Centro de Convenções', 'Congresso com palestras e painéis sobre educação contemporânea.', 'Estudantes', '2025-07-07 08:00:00', '2025-07-07 16:00:00', 8.0, 1),
(13, 'Palestra', 'Segurança da Informação', 'Auditório 2', 'Palestra sobre boas práticas e riscos em segurança da informação.', 'Estudantes', '2025-08-10 19:00:00', '2025-08-10 21:00:00', 2.0, 1),
(14, 'Workshop', 'Git e GitHub', 'Laboratório 2', 'Workshop prático de versionamento e colaboração com Git e GitHub.', 'Estudantes', '2025-09-05 14:00:00', '2025-09-05 18:00:00', 4.0, 0),
(15, 'Curso', 'HTML & CSS do Zero', 'Laboratório Web', 'Curso introdutório de desenvolvimento web com HTML e CSS.', 'Estudantes', '2025-10-01 09:00:00', '2025-10-01 14:00:00', 5.0, 0),
(16, 'Seminário', 'Empreendedorismo', 'Sala Negócios', 'Seminário sobre criação de negócios e inovação.', 'Estudantes', '2025-11-12 10:00:00', '2025-11-12 12:00:00', 2.0, 1),
(17, 'Conferência', 'Conferência de Cloud Computing', 'Auditório 3', 'Conferência sobre tendências em computação em nuvem.', 'Estudantes', '2025-12-03 13:00:00', '2025-12-03 18:00:00', 5.0, 1);