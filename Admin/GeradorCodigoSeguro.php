<?php

/**
 * Gerador de Códigos Seguros para Organizadores
 * Sistema melhorado com códigos verdadeiramente aleatórios
 */

class GeradorCodigoSeguro
{

    /**
     * Caracteres permitidos para códigos (sem confusões visuais)
     * Removido: 0, O, I, 1, l para evitar confusão
     */
    private static $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Gera um código verdadeiramente aleatório e único
     */
    public static function gerarCodigo($conexao, $tentativas = 10)
    {
        for ($i = 0; $i < $tentativas; $i++) {
            $codigo = self::gerarCodigoAleatorio();

            // Verifica se já existe no banco
            if (!self::codigoJaExiste($conexao, $codigo)) {
                return $codigo;
            }
        }

        // Se após 10 tentativas não conseguiu gerar único, força com timestamp
        return self::gerarCodigoComTimestamp();
    }

    /**
     * Gera código totalmente aleatório de 8 caracteres
     */
    private static function gerarCodigoAleatorio()
    {
        $codigo = '';
        $maxIndex = strlen(self::$caracteres) - 1;

        // Gera 8 caracteres aleatórios
        for ($i = 0; $i < 8; $i++) {
            $codigo .= self::$caracteres[random_int(0, $maxIndex)];
        }

        return $codigo;
    }

    /**
     * Gera código com timestamp para garantir unicidade
     */
    private static function gerarCodigoComTimestamp()
    {
        $timestamp = substr(str_replace('.', '', microtime(true)), -6);
        $random = '';
        $maxIndex = strlen(self::$caracteres) - 1;

        // 2 caracteres aleatórios + 6 dígitos do timestamp convertidos
        for ($i = 0; $i < 2; $i++) {
            $random .= self::$caracteres[random_int(0, $maxIndex)];
        }

        // Converte timestamp para caracteres do nosso conjunto
        $timestampConverted = '';
        $timestampNum = intval($timestamp);
        $base = strlen(self::$caracteres);

        while ($timestampNum > 0) {
            $timestampConverted = self::$caracteres[$timestampNum % $base] . $timestampConverted;
            $timestampNum = intval($timestampNum / $base);
        }

        return $random . str_pad($timestampConverted, 6, self::$caracteres[0], STR_PAD_LEFT);
    }

    /**
     * Verifica se código já existe no banco
     */
    private static function codigoJaExiste($conexao, $codigo)
    {
        $sql = "SELECT id FROM codigos_organizador WHERE codigo = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_num_rows($result) > 0;
    }

    /**
     * Gera múltiplos códigos únicos
     */
    public static function gerarMultiplosCodigos($conexao, $quantidade = 5)
    {
        $codigos = [];

        for ($i = 0; $i < $quantidade; $i++) {
            $codigo = self::gerarCodigo($conexao);
            $codigos[] = $codigo;
        }

        return $codigos;
    }

    /**
     * Valida formato do código
     */
    public static function validarFormato($codigo)
    {
        // Debug
        error_log("GeradorCodigoSeguro::validarFormato - Validando código: '$codigo' (length: " . strlen($codigo) . ")");

        // Deve ter exatamente 8 caracteres
        if (strlen($codigo) !== 8) {
            error_log("GeradorCodigoSeguro::validarFormato - FALHOU: Tamanho incorreto");
            return false;
        }

        // Todos os caracteres devem estar no conjunto permitido
        for ($i = 0; $i < strlen($codigo); $i++) {
            if (strpos(self::$caracteres, $codigo[$i]) === false) {
                error_log("GeradorCodigoSeguro::validarFormato - FALHOU: Caractere inválido '" . $codigo[$i] . "' na posição $i");
                return false;
            }
        }

        error_log("GeradorCodigoSeguro::validarFormato - SUCESSO: Código válido");
        return true;
    }

    /**
     * Gera código para migração dos códigos antigos
     */
    public static function migrarCodigosExistentes($conexao)
    {
        echo "🔄 Iniciando migração de códigos antigos...\n";

        // Busca códigos com padrão antigo (ORG + números)
        $sql = "SELECT id, codigo FROM codigos_organizador WHERE codigo LIKE 'ORG%'";
        $result = mysqli_query($conexao, $sql);

        $migrados = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $novodCodigo = self::gerarCodigo($conexao);

            $sqlUpdate = "UPDATE codigos_organizador SET codigo = ? WHERE id = ?";
            $stmtUpdate = mysqli_prepare($conexao, $sqlUpdate);
            mysqli_stmt_bind_param($stmtUpdate, "si", $novodCodigo, $row['id']);

            if (mysqli_stmt_execute($stmtUpdate)) {
                echo "✅ Migrado: {$row['codigo']} → $novodCodigo\n";
                $migrados++;
            } else {
                echo "❌ Erro ao migrar: {$row['codigo']}\n";
            }
        }

        echo "🎉 Migração concluída! $migrados códigos atualizados.\n";
        return $migrados;
    }
}
