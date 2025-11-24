<?php
header('Content-Type: application/json; charset=utf-8');

try {
    // Configurações de conexão
    $servidor = "localhost";
    $usuario = "root";
    $senha = "";
    $banco = "CEU_bd";

    // Conectar ao banco de dados
    $conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

    if (!$conexao) {
        throw new Exception("Erro na conexão: " . mysqli_connect_error());
    }

    mysqli_set_charset($conexao, "utf8mb4");

    $opcoes = [
        'tipos' => [],
        'localizacoes' => [],
        'duracoes' => [],
        'modalidades' => [],
        'certificados' => []
    ];

    // Buscar tipos de eventos (categoria) únicos
    $sqlTipos = "SELECT DISTINCT categoria FROM evento WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
    $resultTipos = mysqli_query($conexao, $sqlTipos);
    if ($resultTipos) {
        while ($row = mysqli_fetch_assoc($resultTipos)) {
            $categoria = trim($row['categoria']);
            if ($categoria) {
                // Normalizar para o formato do filtro - remover acentos e espaços
                $valor = strtolower($categoria);
                $valor = str_replace(
                    ['á', 'à', 'â', 'ã', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'ô', 'õ', 'ú', 'ù', 'û', 'ç', ' '],
                    ['a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'c', '_'],
                    $valor
                );
                $opcoes['tipos'][] = [
                    'valor' => $valor,
                    'label' => ucfirst($categoria)
                ];
            }
        }
    }

    // Buscar localizações únicas
    $sqlLocais = "SELECT DISTINCT lugar FROM evento WHERE lugar IS NOT NULL AND lugar != '' ORDER BY lugar";
    $resultLocais = mysqli_query($conexao, $sqlLocais);
    $locaisMap = []; // Usar array associativo para evitar duplicatas
    if ($resultLocais) {
        while ($row = mysqli_fetch_assoc($resultLocais)) {
            $lugar = trim($row['lugar']);
            if ($lugar) {
                $lugarLower = strtolower($lugar);

                // Normalizar localizações similares
                // Remover acentos do lugar para comparação
                $lugarNormalizado = str_replace(
                    ['á', 'à', 'â', 'ã', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'ô', 'õ', 'ú', 'ù', 'û', 'ç'],
                    ['a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'c'],
                    $lugarLower
                );

                if (strpos($lugarNormalizado, 'sala') !== false) {
                    $chave = 'salas';
                    $label = 'Salas';
                } elseif (strpos($lugarNormalizado, 'laboratorio') !== false || strpos($lugarNormalizado, 'lab') !== false) {
                    $chave = 'laboratorio';
                    $label = 'Laboratórios';
                } elseif (strpos($lugarNormalizado, 'auditorio') !== false) {
                    $chave = 'auditorio';
                    $label = 'Auditórios';
                } elseif (strpos($lugarNormalizado, 'quadra') !== false) {
                    $chave = 'quadra';
                    $label = 'Quadra';
                } elseif (strpos($lugarLower, 'biblioteca') !== false) {
                    $chave = 'biblioteca';
                    $label = 'Biblioteca';
                } elseif (strpos($lugarLower, 'patio') !== false || strpos($lugarLower, 'pátio') !== false) {
                    $chave = 'patio';
                    $label = 'Pátio';
                } elseif (strpos($lugarLower, 'ginasio') !== false || strpos($lugarLower, 'ginásio') !== false) {
                    $chave = 'ginasio';
                    $label = 'Ginásio';
                } elseif (strpos($lugarLower, 'online') !== false || strpos($lugarLower, 'virtual') !== false || strpos($lugarLower, 'remoto') !== false) {
                    $chave = 'online';
                    $label = 'Online';
                } else {
                    // Normalizar nome genérico
                    $chave = strtolower(str_replace([' ', 'ã', 'á', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'ú', 'ç'], ['_', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'u', 'c'], $lugar));
                    $label = $lugar;
                }

                // Adicionar apenas se ainda não existir
                if (!isset($locaisMap[$chave])) {
                    $locaisMap[$chave] = [
                        'valor' => $chave,
                        'label' => $label
                    ];
                }
            }
        }
    }
    // Converter para array indexado e ordenar
    $opcoes['localizacoes'] = array_values($locaisMap);
    usort($opcoes['localizacoes'], function ($a, $b) {
        return strcmp($a['label'], $b['label']);
    });

    // Buscar durações e criar faixas
    $sqlDuracoes = "SELECT DISTINCT duracao FROM evento WHERE duracao IS NOT NULL ORDER BY duracao";
    $resultDuracoes = mysqli_query($conexao, $sqlDuracoes);
    $duracoes = [];
    if ($resultDuracoes) {
        while ($row = mysqli_fetch_assoc($resultDuracoes)) {
            $duracao = floatval($row['duracao']);
            if ($duracao > 0) {
                $duracoes[] = $duracao;
            }
        }
    }

    // Criar faixas de duração baseadas nos dados reais
    $faixas = [
        'menos_1h' => ['min' => 0, 'max' => 1, 'label' => '< 1h', 'existe' => false],
        '1h_2h' => ['min' => 1, 'max' => 2, 'label' => '1h - 2h', 'existe' => false],
        '2h_4h' => ['min' => 2, 'max' => 4, 'label' => '2h - 4h', 'existe' => false],
        '4h_6h' => ['min' => 4, 'max' => 6, 'label' => '4h - 6h', 'existe' => false],
        '6h_8h' => ['min' => 6, 'max' => 8, 'label' => '6h - 8h', 'existe' => false],
        '8h_10h' => ['min' => 8, 'max' => 10, 'label' => '8h - 10h', 'existe' => false],
        '10h_20h' => ['min' => 10, 'max' => 20, 'label' => '10h - 20h', 'existe' => false],
        'mais_20h' => ['min' => 20, 'max' => 9999, 'label' => '> 20h', 'existe' => false]
    ];

    foreach ($duracoes as $duracao) {
        foreach ($faixas as $key => &$faixa) {
            // Para 'mais_20h', não há limite superior
            if ($key === 'mais_20h') {
                if ($duracao >= $faixa['min']) {
                    $faixa['existe'] = true;
                }
            } else {
                if ($duracao >= $faixa['min'] && $duracao < $faixa['max']) {
                    $faixa['existe'] = true;
                }
            }
        }
        unset($faixa); // Liberar a referência para evitar bugs
    }

    // Adicionar apenas faixas que existem
    foreach ($faixas as $key => $faixa) {
        if ($faixa['existe']) {
            $opcoes['duracoes'][] = [
                'valor' => $key,
                'label' => $faixa['label']
            ];
        }
    }

    // Buscar modalidades únicas
    $sqlModalidades = "SELECT DISTINCT modalidade FROM evento WHERE modalidade IS NOT NULL ORDER BY modalidade";
    $resultModalidades = mysqli_query($conexao, $sqlModalidades);
    if ($resultModalidades) {
        while ($row = mysqli_fetch_assoc($resultModalidades)) {
            $modalidade = trim($row['modalidade']);
            if ($modalidade) {
                // Normalizar para o formato do filtro
                $valor = strtolower(str_replace(['í', 'Í'], ['i', 'i'], $modalidade));
                $opcoes['modalidades'][] = [
                    'valor' => $valor,
                    'label' => $modalidade
                ];
            }
        }
    }

    // Verificar se há eventos com e sem certificado
    $sqlCertificados = "SELECT 
        SUM(CASE WHEN certificado = 1 THEN 1 ELSE 0 END) as com_cert,
        SUM(CASE WHEN certificado = 0 THEN 1 ELSE 0 END) as sem_cert
        FROM evento";
    $resultCert = mysqli_query($conexao, $sqlCertificados);
    if ($resultCert) {
        $row = mysqli_fetch_assoc($resultCert);
        $opcoes['certificados'] = [];
        if ($row['com_cert'] > 0) {
            $opcoes['certificados'][] = ['valor' => 'sim', 'label' => 'Com certificado'];
        }
        if ($row['sem_cert'] > 0) {
            $opcoes['certificados'][] = ['valor' => 'nao', 'label' => 'Sem certificado'];
        }
    }

    mysqli_close($conexao);

    echo json_encode($opcoes, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao buscar opções de filtro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
