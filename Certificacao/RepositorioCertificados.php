<?php
namespace CEU\Certificacao;

class RepositorioCertificados
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function garantirEsquema(): void
    {
        // Cria tabela certificado se não existir (compatível com script existente)
        $sqlTabela = "CREATE TABLE IF NOT EXISTS certificado (
            cod_verificacao VARCHAR(16) PRIMARY KEY,
            modelo VARCHAR(255) NULL,
            tipo VARCHAR(100) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sqlTabela);

        // Adiciona colunas para armazenamento do arquivo e metadados, se faltarem
        $this->adicionarColunaSeFalta('certificado', 'arquivo', "VARCHAR(255) NULL");
        $this->adicionarColunaSeFalta('certificado', 'dados', "TEXT NULL");
        $this->adicionarColunaSeFalta('certificado', 'criado_em', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        $this->adicionarColunaSeFalta('certificado', 'cpf', "CHAR(11) NULL");
        $this->adicionarColunaSeFalta('certificado', 'cod_evento', "INT NULL");

        // Índice único por CPF+evento (um certificado por pessoa por evento)
        $this->criarIndiceUnicoSeFalta('certificado', 'uniq_cert_cpf_evento', '(cpf, cod_evento)');
    }

    private function adicionarColunaSeFalta(string $tabela, string $coluna, string $definicao): void
    {
        $colunaExiste = false;
        if ($res = $this->db->query("SHOW COLUMNS FROM `{$tabela}` LIKE '" . $this->db->real_escape_string($coluna) . "'")) {
            $colunaExiste = (bool)$res->fetch_assoc();
            $res->free();
        }
        if (!$colunaExiste) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN `{$coluna}` {$definicao}");
        }
    }

    private function criarIndiceUnicoSeFalta(string $tabela, string $nomeIndice, string $colunas): void
    {
        $existe = false;
        if ($res = $this->db->query("SHOW INDEX FROM `{$tabela}` WHERE Key_name = '" . $this->db->real_escape_string($nomeIndice) . "'")) {
            $existe = (bool)$res->fetch_assoc();
            $res->free();
        }
        if (!$existe) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD UNIQUE KEY `{$nomeIndice}` {$colunas}");
        }
    }

    public function gerarCodigoUnico(int $tamanho = 8): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem 0/O/I/1 para evitar confusão
        do {
            $codigo = '';
            for ($i = 0; $i < $tamanho; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while ($this->existeCodigo($codigo));
        return $codigo;
    }

    public function existeCodigo(string $codigo): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM certificado WHERE cod_verificacao = ? LIMIT 1');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function salvarCertificado(string $codigo, string $arquivoRelativo, ?string $modelo = null, ?string $tipo = null, array $dados = [], ?string $cpf = null, ?int $codEvento = null): bool
    {
        $json = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Tenta INSERT; se já existir, faz UPDATE dos campos variáveis
        $stmt = $this->db->prepare('INSERT INTO certificado (cod_verificacao, modelo, tipo, arquivo, dados, cpf, cod_evento) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE modelo=VALUES(modelo), tipo=VALUES(tipo), arquivo=VALUES(arquivo), dados=VALUES(dados), cpf=VALUES(cpf), cod_evento=VALUES(cod_evento)');
        $stmt->bind_param('ssssssi', $codigo, $modelo, $tipo, $arquivoRelativo, $json, $cpf, $codEvento);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        $stmt = $this->db->prepare('SELECT cod_verificacao, modelo, tipo, arquivo, dados, criado_em FROM certificado WHERE cod_verificacao = ? LIMIT 1');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) return null;
        if (!empty($row['dados'])) {
            $row['dados_array'] = json_decode($row['dados'], true);
        }
        return $row;
    }

    public function buscarPorCpfEvento(string $cpf, int $codEvento): ?array
    {
        $stmt = $this->db->prepare('SELECT cod_verificacao, modelo, tipo, arquivo, dados, criado_em FROM certificado WHERE cpf = ? AND cod_evento = ? LIMIT 1');
        $stmt->bind_param('si', $cpf, $codEvento);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) return null;
        if (!empty($row['dados'])) { $row['dados_array'] = json_decode($row['dados'], true); }
        return $row;
    }
}
