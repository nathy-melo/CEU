<?php
declare(strict_types=1);
namespace CEU\Certificacao;

/**
 * Repositório responsável por garantir o esquema e manipular registros de certificados.
 * Adicionados PHPDocs e ajustes de montagem de SQL para evitar falsos positivos do Intelephense (P1008).
 */
class RepositorioCertificados
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Garante a existência da tabela e colunas necessárias.
     */
    public function garantirEsquema(): void
    {
        $sqlTabela = 'CREATE TABLE IF NOT EXISTS certificado (
            cod_verificacao VARCHAR(8) PRIMARY KEY,
            modelo VARCHAR(255) NULL,
            tipo VARCHAR(100) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->db->query($sqlTabela);

        $this->ajustarTamanhoCodigoSeNecessario('certificado', 'cod_verificacao', 8);

        $this->adicionarColunaSeFalta('certificado', 'arquivo', 'VARCHAR(255) NULL');
        $this->adicionarColunaSeFalta('certificado', 'dados', 'TEXT NULL');
        $this->adicionarColunaSeFalta('certificado', 'criado_em', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $this->adicionarColunaSeFalta('certificado', 'cpf', 'CHAR(11) NULL');
        $this->adicionarColunaSeFalta('certificado', 'cod_evento', 'INT NULL');

        $this->criarIndiceUnicoSeFalta('certificado', 'uniq_cert_cpf_evento', '(cpf, cod_evento)');
    }

    /**
     * Adiciona uma coluna se ela não existir ainda.
     * @param string $tabela Nome da tabela alvo.
     * @param string $coluna Nome da coluna a verificar/adicionar.
     * @param string $definicao Definição SQL da coluna (ex: VARCHAR(255) NULL).
     */
    private function adicionarColunaSeFalta(string $tabela, string $coluna, string $definicao): void
    {
        $colunaExiste = false;
        $colunaEsc = $this->db->real_escape_string($coluna);
        $sql = "SHOW COLUMNS FROM `" . $tabela . "` LIKE '" . $colunaEsc . "'";
        if ($res = $this->db->query($sql)) {
            $colunaExiste = (bool)$res->fetch_assoc();
            $res->free();
        }
        if (!$colunaExiste) {
            $this->db->query("ALTER TABLE `" . $tabela . "` ADD COLUMN `" . $coluna . "` " . $definicao);
        }
    }

    /**
     * Ajusta o tamanho de uma coluna VARCHAR se for diferente do esperado.
     * @param string $tabela
     * @param string $coluna
     * @param int    $tamanhoEsperado
     */
    private function ajustarTamanhoCodigoSeNecessario(string $tabela, string $coluna, int $tamanhoEsperado): void
    {
        $tipoAtual = null;
        $colunaEsc = $this->db->real_escape_string($coluna);
        $sql = "SHOW COLUMNS FROM `" . $tabela . "` LIKE '" . $colunaEsc . "'";
        if ($res = $this->db->query($sql)) {
            if ($row = $res->fetch_assoc()) {
                $tipoAtual = $row['Type'] ?? null;
            }
            $res->free();
        }
        if ($tipoAtual && preg_match('/^varchar\((\d+)\)$/i', (string)$tipoAtual, $m)) {
            $tam = (int)$m[1];
            if ($tam !== $tamanhoEsperado) {
                $this->db->query("ALTER TABLE `" . $tabela . "` MODIFY `" . $coluna . "` VARCHAR(" . $tamanhoEsperado . ") PRIMARY KEY");
            }
        }
    }

    /**
     * Cria um índice único se ele não existir.
     * @param string $tabela
     * @param string $nomeIndice
     * @param string $colunas Lista de colunas entre parênteses já formatada (ex: (a,b)).
     */
    private function criarIndiceUnicoSeFalta(string $tabela, string $nomeIndice, string $colunas): void
    {
        $existe = false;
        $nomeEsc = $this->db->real_escape_string($nomeIndice);
        $sql = "SHOW INDEX FROM `" . $tabela . "` WHERE Key_name = '" . $nomeEsc . "'";
        if ($res = $this->db->query($sql)) {
            $existe = (bool)$res->fetch_assoc();
            $res->free();
        }
        if (!$existe) {
            $this->db->query("ALTER TABLE `" . $tabela . "` ADD UNIQUE KEY `" . $nomeIndice . "` " . $colunas);
        }
    }

    /**
     * Gera um código aleatório único sem caracteres ambíguos.
     * @param int $tamanho
     * @return string
     */
    public function gerarCodigoUnico(int $tamanho = 8): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $codigo = '';
            for ($i = 0; $i < $tamanho; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while ($this->existeCodigo($codigo));
        return $codigo;
    }

    /**
     * Verifica se um código já existe na base.
     */
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

    /**
     * Persiste (insert/update) um certificado.
     * @param string      $codigo Código de verificação (PK)
     * @param string      $arquivoRelativo Caminho relativo do PDF/arquivo gerado
     * @param string|null $modelo Nome do modelo usado
     * @param string|null $tipo Tipo de certificado
     * @param array       $dados Metadados serializados em JSON
     * @param string|null $cpf CPF do participante (11 chars, somente dígitos)
     * @param int|null    $codEvento Código do evento relacionado
     */
    public function salvarCertificado(string $codigo, string $arquivoRelativo, ?string $modelo = null, ?string $tipo = null, array $dados = [], ?string $cpf = null, ?int $codEvento = null): bool
    {
        $json = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sql = 'INSERT INTO certificado (cod_verificacao, modelo, tipo, arquivo, dados, cpf, cod_evento) VALUES (?, ?, ?, ?, ?, ?, ?) '
             . 'ON DUPLICATE KEY UPDATE modelo=VALUES(modelo), tipo=VALUES(tipo), arquivo=VALUES(arquivo), dados=VALUES(dados), cpf=VALUES(cpf), cod_evento=VALUES(cod_evento)';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssssi', $codigo, $modelo, $tipo, $arquivoRelativo, $json, $cpf, $codEvento);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Busca um certificado pelo código de verificação.
     * @return array|null Dados ou null se não encontrado.
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $stmt = $this->db->prepare('SELECT cod_verificacao, modelo, tipo, arquivo, dados, criado_em FROM certificado WHERE cod_verificacao = ? LIMIT 1');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        if (!empty($row['dados'])) {
            $row['dados_array'] = json_decode($row['dados'], true);
        }
        return $row;
    }

    /**
     * Busca certificado por CPF + evento.
     * @return array|null
     */
    public function buscarPorCpfEvento(string $cpf, int $codEvento): ?array
    {
        $stmt = $this->db->prepare('SELECT cod_verificacao, modelo, tipo, arquivo, dados, criado_em FROM certificado WHERE cpf = ? AND cod_evento = ? LIMIT 1');
        $stmt->bind_param('si', $cpf, $codEvento);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        if (!empty($row['dados'])) {
            $row['dados_array'] = json_decode($row['dados'], true);
        }
        return $row;
    }
}
