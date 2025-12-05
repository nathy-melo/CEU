<?php

class GerenciadorBackup
{
    private $conexao;
    private $pastaBackup;

    public function __construct()
    {
        require_once '../BancoDados/conexao.php';
        $this->conexao = $conexao ?? null;
        $this->pastaBackup = __DIR__ . '/Backups/';

        if (!is_dir($this->pastaBackup)) {
            mkdir($this->pastaBackup, 0755, true);
        }
    }

    public function fazerBackup()
    {
        try {
            $nomeArquivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $caminhoBackup = $this->pastaBackup . $nomeArquivo;

            $sql = $this->exportarBD();

            if (!file_put_contents($caminhoBackup, $sql)) {
                throw new Exception('Erro ao salvar backup');
            }

            return [
                'sucesso' => true,
                'arquivo' => $nomeArquivo,
                'tamanho' => filesize($caminhoBackup),
                'mensagem' => 'Backup realizado com sucesso'
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    private function exportarBD()
    {
        $sql = "-- Backup CEU_bd - " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "DROP DATABASE IF EXISTS CEU_bd;\n";
        $sql .= "CREATE DATABASE CEU_bd;\n";
        $sql .= "USE CEU_bd;\n\n";

        $resultado = $this->conexao->query("SHOW TABLES FROM CEU_bd");

        if (!$resultado) {
            throw new Exception("Erro ao listar tabelas");
        }

        while ($linha = $resultado->fetch_row()) {
            $tabela = $linha[0];

            $resultCreate = $this->conexao->query("SHOW CREATE TABLE CEU_bd.`$tabela`");
            $linhaCreate = $resultCreate->fetch_row();
            $sql .= $linhaCreate[1] . ";\n\n";

            $resultDados = $this->conexao->query("SELECT * FROM CEU_bd.`$tabela`");

            if ($resultDados && $resultDados->num_rows > 0) {
                $sql .= "INSERT INTO `$tabela` VALUES\n";

                $primeira = true;
                while ($dados = $resultDados->fetch_assoc()) {
                    if (!$primeira) $sql .= ",\n";

                    $valores = [];
                    foreach ($dados as $valor) {
                        $valores[] = $valor === null ? "NULL" : "'" . $this->conexao->real_escape_string($valor) . "'";
                    }

                    $sql .= "(" . implode(", ", $valores) . ")";
                    $primeira = false;
                }

                $sql .= ";\n\n";
            }
        }

        return $sql;
    }

    public function listarBackups()
    {
        try {
            $arquivos = glob($this->pastaBackup . 'backup_*.sql');
            $backups = [];

            foreach ($arquivos as $arquivo) {
                $backups[] = [
                    'nome' => basename($arquivo),
                    'tamanho' => filesize($arquivo),
                    'tamanhoFormatado' => $this->formatarTamanho(filesize($arquivo)),
                    'data' => date('d/m/Y H:i:s', filemtime($arquivo)),
                    'timestamp' => filemtime($arquivo)
                ];
            }

            usort($backups, function ($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            return [
                'sucesso' => true,
                'total' => count($backups),
                'backups' => $backups
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function restaurarBackup($nomeArquivo)
    {
        try {
            $caminhoBackup = $this->pastaBackup . $nomeArquivo;

            if (!file_exists($caminhoBackup)) {
                throw new Exception('Arquivo não encontrado');
            }

            $sql = file_get_contents($caminhoBackup);
            $querys = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($querys as $query) {
                if (!empty($query)) {
                    if (!$this->conexao->query($query)) {
                        throw new Exception("Erro na query: " . $this->conexao->error);
                    }
                }
            }

            return [
                'sucesso' => true,
                'mensagem' => 'Backup restaurado com sucesso'
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function deletarBackup($nomeArquivo)
    {
        try {
            $caminhoBackup = $this->pastaBackup . $nomeArquivo;

            if (!file_exists($caminhoBackup)) {
                throw new Exception('Arquivo não encontrado');
            }

            if (!unlink($caminhoBackup)) {
                throw new Exception('Erro ao deletar arquivo');
            }

            return [
                'sucesso' => true,
                'mensagem' => 'Backup deletado com sucesso'
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function obterInfo()
    {
        try {
            $resultado = $this->conexao->query("SELECT 
                SUM(data_length + index_length) as tamanho,
                COUNT(*) as num_tabelas
            FROM information_schema.tables 
            WHERE table_schema = 'CEU_bd'");

            $info = $resultado->fetch_assoc();

            return [
                'sucesso' => true,
                'tamanho' => $this->formatarTamanho($info['tamanho']),
                'numTabelas' => $info['num_tabelas']
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    private function formatarTamanho($bytes)
    {
        $unidades = ['B', 'KB', 'MB'];
        $tamanho = $bytes;
        $indice = 0;

        while ($tamanho >= 1024 && $indice < 2) {
            $tamanho /= 1024;
            $indice++;
        }

        return round($tamanho, 2) . ' ' . $unidades[$indice];
    }
}

if (php_sapi_name() !== 'cli' && isset($_GET['acao'])) {
    header('Content-Type: application/json; charset=utf-8');

    $gerenciador = new GerenciadorBackup();
    $acao = $_GET['acao'];

    switch ($acao) {
        case 'fazer-backup':
            echo json_encode($gerenciador->fazerBackup());
            break;

        case 'listar':
            echo json_encode($gerenciador->listarBackups());
            break;

        case 'info':
            echo json_encode($gerenciador->obterInfo());
            break;

        case 'restaurar':
            $arquivo = $_POST['arquivo'] ?? null;
            echo json_encode($arquivo ? $gerenciador->restaurarBackup($arquivo) : ['sucesso' => false, 'mensagem' => 'Arquivo não especificado']);
            break;

        case 'deletar':
            $arquivo = $_POST['arquivo'] ?? null;
            echo json_encode($arquivo ? $gerenciador->deletarBackup($arquivo) : ['sucesso' => false, 'mensagem' => 'Arquivo não especificado']);
            break;

        case 'baixar':
            $arquivo = $_GET['arquivo'] ?? null;
            if ($arquivo && file_exists(__DIR__ . '/Backups/' . $arquivo)) {
                $caminho = __DIR__ . '/Backups/' . $arquivo;
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $arquivo . '"');
                readfile($caminho);
                exit;
            }
            break;

        default:
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não reconhecida']);
    }
    exit;
}
