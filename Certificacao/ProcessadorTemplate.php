<?php
/**
 * Processador de Templates de Certificado (Opção A)
 * - Entrada: DOCX com placeholders do tipo {NomeParticipante}
 * - Pipeline: DOCX -> (preenche via Zip) -> DOCX -> HTML (PHPWord) -> PDF (mPDF)
 * - Requer: phpoffice/phpword e mpdf/mpdf via autoload de bibliotecas/vendor
 */

namespace CEU\Certificacao;

use Exception;

class ProcessadorTemplate
{
    private string $autoload;
    private ?string $sofficePath = null;

    public function __construct(string $autoloadPath)
    {
        $this->autoload = $autoloadPath;
        if (!file_exists($this->autoload)) {
            throw new Exception('Autoload não encontrado em: ' . $this->autoload);
        }
        require_once $this->autoload;

        if (!class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            throw new Exception('Biblioteca PHPWord não disponível. Execute a instalação das dependências.');
        }
        if (!class_exists('Mpdf\\Mpdf')) {
            throw new Exception('Biblioteca mPDF não disponível. Execute a instalação das dependências.');
        }

        // Tenta detectar LibreOffice para conversão PPTX -> PDF (Caminho B)
        $this->sofficePath = $this->descobrirSoffice();
    }

    

    /**
     * Converte DOCX para HTML usando PHPWord e retorna o caminho do HTML gerado.
     */
    public function converterDocxParaHtml(string $docxPath, string $saidaHtml): void
    {
        $ioFactory = '\\PhpOffice\\PhpWord\\IOFactory';
        $phpWord = $ioFactory::load($docxPath, 'Word2007');
        $writer = $ioFactory::createWriter($phpWord, 'HTML');
        $writer->save($saidaHtml);
    }

    /**
     * Converte HTML para PDF usando mPDF.
     */
    public function converterHtmlParaPdf(string $htmlPath, string $saidaPdf): void
    {
        $html = @file_get_contents($htmlPath);
        if ($html === false) {
            throw new Exception('Não foi possível ler HTML: ' . $htmlPath);
        }
        $mpdf = new \Mpdf\Mpdf([ 'mode' => 'utf-8', 'format' => 'A4-L' ]); // Paisagem
        $mpdf->WriteHTML($html);
        // Usa destino em string para evitar dependência do tipo em análise estática
        $mpdf->Output($saidaPdf, 'F');
    }

    /**
     * Pipeline completo: DOCX + dados -> PDF (usa LibreOffice por padrão para manter layout)
     */
    public function gerarPdfDeDocx(string $templateDocx, array $dados, string $saidaPdf, ?string $workdir = null): array
    {
        $work = $workdir ?: sys_get_temp_dir();
        $tmpDocx = rtrim($work, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cert-tmp-' . uniqid() . '.docx';

        $this->preencherDocx($templateDocx, $dados, $tmpDocx);
        $conv = $this->converterDocxParaPdf($tmpDocx, $saidaPdf);
        if (!$conv['success']) {
            // Fallback: tenta via HTML->PDF
            $tmpHtml = rtrim($work, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cert-tmp-' . uniqid() . '.html';
            $this->converterDocxParaHtml($tmpDocx, $tmpHtml);
            $this->converterHtmlParaPdf($tmpHtml, $saidaPdf);
        }

        return [
            'success' => true,
            'pdf' => $saidaPdf,
            'temp_docx' => $tmpDocx,
        ];
    }

    /**
     * Preenche um PPTX substituindo placeholders em todos os slides (sem converter para PDF).
     * Útil como etapa anterior ao Caminho B (LibreOffice). Salva em $saidaPptx.
     */
    public function preencherPptx(string $templatePptx, array $dados, string $saidaPptx): void
    {
        if (!file_exists($templatePptx)) {
            throw new Exception('Template PPTX não encontrado: ' . $templatePptx);
        }
        if (!@copy($templatePptx, $saidaPptx)) {
            throw new Exception('Falha ao copiar PPTX para destino: ' . $saidaPptx);
        }

        $zip = new \ZipArchive();
        if ($zip->open($saidaPptx) !== true) {
            throw new Exception('Não foi possível abrir o PPTX como zip.');
        }
        // Alvos comuns dentro do PPTX
        $alvos = [];
        // Slides
        for ($i = 1; $i <= 60; $i++) { // limite razoável
            $alvos[] = "ppt/slides/slide{$i}.xml";
        }
        // Mestres e layouts
        for ($i = 1; $i <= 10; $i++) {
            $alvos[] = "ppt/slideMasters/slideMaster{$i}.xml";
            $alvos[] = "ppt/slideLayouts/slideLayout{$i}.xml";
        }

        foreach ($alvos as $alvo) {
            $xml = $zip->getFromName($alvo);
            if ($xml === false) { continue; }
            $xml = $this->normalizeAndReplace($xml, $dados, ['a:t']);
            $zip->addFromString($alvo, $xml);
        }
        $zip->close();
    }

    /** Preenche DOCX normalizando runs w:t e aplicando regex tolerante a tags. */
    public function preencherDocx(string $templateDocx, array $dados, string $saidaDocx): void
    {
        if (!file_exists($templateDocx)) {
            throw new Exception('Template DOCX não encontrado: ' . $templateDocx);
        }
        if (!@copy($templateDocx, $saidaDocx)) {
            throw new Exception('Falha ao copiar template para destino: ' . $saidaDocx);
        }
        $zip = new \ZipArchive();
        if ($zip->open($saidaDocx) !== true) {
            throw new Exception('Não foi possível abrir o DOCX como zip.');
        }
        $alvos = ['word/document.xml'];
        for ($i=1;$i<=5;$i++) { $alvos[] = "word/header{$i}.xml"; $alvos[] = "word/footer{$i}.xml"; }
        foreach ($alvos as $alvo) {
            $xml = $zip->getFromName($alvo);
            if ($xml === false) { continue; }
            $xml = $this->normalizeAndReplace($xml, $dados, ['w:t']);
            $zip->addFromString($alvo, $xml);
        }
        $zip->close();
    }

    /** Preenche ODT/ODP/FODT/FODP usando content.xml (+styles.xml opcional). */
    public function preencherOdt(string $templateOdt, array $dados, string $saidaOdt): void
    {
        $this->preencherOdfGenerico($templateOdt, $dados, $saidaOdt);
    }
    public function preencherOdp(string $templateOdp, array $dados, string $saidaOdp): void
    {
        $this->preencherOdfGenerico($templateOdp, $dados, $saidaOdp);
    }
    public function preencherFodt(string $templateFodt, array $dados, string $saidaFodt): void
    {
        // Arquivo XML plano — substituição direta
        $xml = @file_get_contents($templateFodt);
        if ($xml === false) { throw new Exception('FODT não encontrado: ' . $templateFodt); }
        $xml = $this->normalizeAndReplace($xml, $dados, []);
        if (@file_put_contents($saidaFodt, $xml) === false) {
            throw new Exception('Falha ao salvar FODT destino: ' . $saidaFodt);
        }
    }
    public function preencherFodp(string $templateFodp, array $dados, string $saidaFodp): void
    {
        $xml = @file_get_contents($templateFodp);
        if ($xml === false) { throw new Exception('FODP não encontrado: ' . $templateFodp); }
        $xml = $this->normalizeAndReplace($xml, $dados, []);
        if (@file_put_contents($saidaFodp, $xml) === false) {
            throw new Exception('Falha ao salvar FODP destino: ' . $saidaFodp);
        }
    }

    private function preencherOdfGenerico(string $template, array $dados, string $saida): void
    {
        if (!file_exists($template)) { throw new Exception('Template ODF não encontrado: ' . $template); }
        if (!@copy($template, $saida)) { throw new Exception('Falha ao copiar template ODF: ' . $saida); }
        $zip = new \ZipArchive();
        if ($zip->open($saida) !== true) { throw new Exception('Não foi possível abrir ODF como zip.'); }
        $targets = ['content.xml', 'styles.xml'];
        foreach ($targets as $t) {
            $xml = $zip->getFromName($t);
            if ($xml === false) { continue; }
            $xml = $this->normalizeAndReplace($xml, $dados, []);
            $zip->addFromString($t, $xml);
        }
        $zip->close();
    }

    /** Normaliza runs e substitui placeholders com regex que ignora tags entre caracteres. */
    private function normalizeAndReplace(string $xml, array $dados, array $textTagNames): string
    {
        // Normalizações de runs para DOCX (w:t) e PPTX (a:t)
        // Junta fechamentos/aberturas de runs consecutivos
        $xml = preg_replace('#</w:t>\s*</w:r>\s*<w:r[^>]*>\s*<w:t[^>]*>#i', '', $xml);
        $xml = preg_replace('#</w:t>\s*<w:t[^>]*>#i', '', $xml);
        $xml = preg_replace('#</a:t>\s*</a:r>\s*<a:r[^>]*>\s*<a:t[^>]*>#i', '', $xml);
        $xml = preg_replace('#</a:t>\s*<a:t[^>]*>#i', '', $xml);

        // Função de padrão tag-agnóstico
        $tagAgnostic = function(string $texto): string {
            $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);
            $escaped = array_map(fn($c) => preg_quote($c, '#'), $chars);
            return '#'. implode('(?:\s*<[^>]+>\s*)?', $escaped) .'#u';
        };

        foreach ($dados as $chave => $valor) {
            $subs = [];
            if (strpos($chave, '{') === false) { $subs[] = '{' . $chave . '}'; }
            $subs[] = (string)$chave;
            $subs = array_unique($subs);
            foreach ($subs as $s) {
                $safe = htmlspecialchars($valor, ENT_QUOTES | ENT_XML1);
                $xml = str_replace($s, $safe, $xml);
                // Aplica regex tolerante caso fragmentado por tags
                $xml = preg_replace($tagAgnostic($s), $safe, $xml);
            }
        }
        return $xml;
    }

    /** Localiza possíveis caminhos do LibreOffice (soffice/soffice.exe). */
    private function candidatosSoffice(): array
    {
        $cands = [];
        $env = getenv('SOFFICE_PATH') ?: getenv('LIBREOFFICE_PATH');
        if ($env) { $cands[] = $env; }
        if (stripos(PHP_OS, 'WIN') === 0) {
            $cands[] = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
            $cands[] = 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe';
        } else {
            $cands[] = '/usr/bin/soffice';
            $cands[] = '/usr/lib/libreoffice/program/soffice';
        }
        return $cands;
    }

    private function descobrirSoffice(): ?string
    {
        foreach ($this->candidatosSoffice() as $c) {
            if ($c && file_exists($c)) { return $c; }
        }
        return null;
    }

    /** Converte PPTX para PDF usando LibreOffice headless. Requer soffice instalado. */
    public function converterPptxParaPdf(string $pptxPath, string $saidaPdf, ?string $sofficePath = null): array
    {
        $soffice = $sofficePath ?: $this->sofficePath;
        if (!$soffice || !file_exists($soffice)) {
            return ['success' => false, 'message' => 'LibreOffice (soffice) não encontrado. Defina SOFFICE_PATH ou instale o LibreOffice.'];
        }
        if (!file_exists($pptxPath)) {
            return ['success' => false, 'message' => 'PPTX não encontrado: ' . $pptxPath];
        }

        $outDir = dirname($saidaPdf);
        if (!is_dir($outDir)) { @mkdir($outDir, 0775, true); }

        $cmd = '"' . $soffice . '" --headless --nologo --nodefault --nofirststartwizard --convert-to pdf --outdir ' . '"' . $outDir . '" ' . '"' . $pptxPath . '"';
        $desc = [ 1 => ['pipe', 'w'], 2 => ['pipe', 'w'] ];
        $proc = @proc_open($cmd, $desc, $pipes, dirname($pptxPath));
        if (!is_resource($proc)) {
            return ['success' => false, 'message' => 'Falha ao iniciar processo LibreOffice.'];
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $p) { if (is_resource($p)) fclose($p); }
        $code = proc_close($proc);

        // PDF gerado tem o mesmo basename
        $esperado = $outDir . DIRECTORY_SEPARATOR . pathinfo($pptxPath, PATHINFO_FILENAME) . '.pdf';
        if ($code === 0 && file_exists($esperado)) {
            // move/renomeia para $saidaPdf
            if (realpath($esperado) !== realpath($saidaPdf)) {
                @rename($esperado, $saidaPdf);
            }
            return ['success' => true, 'pdf' => $saidaPdf, 'stdout' => $stdout, 'stderr' => $stderr, 'code' => $code, 'soffice' => $soffice];
        }
        return ['success' => false, 'message' => 'Conversão PPTX->PDF falhou', 'stdout' => $stdout, 'stderr' => $stderr, 'code' => $code, 'soffice' => $soffice];
    }

    /** Convertores para outros formatos via LibreOffice */
    public function converterDocxParaPdf(string $docxPath, string $saidaPdf, ?string $sofficePath = null): array
    { return $this->converterViaSoffice($docxPath, $saidaPdf, $sofficePath); }
    public function converterOdtParaPdf(string $odtPath, string $saidaPdf, ?string $sofficePath = null): array
    { return $this->converterViaSoffice($odtPath, $saidaPdf, $sofficePath); }
    public function converterOdpParaPdf(string $odpPath, string $saidaPdf, ?string $sofficePath = null): array
    { return $this->converterViaSoffice($odpPath, $saidaPdf, $sofficePath); }

    private function converterViaSoffice(string $entrada, string $saidaPdf, ?string $sofficePath = null): array
    {
        $soffice = $sofficePath ?: $this->sofficePath;
        if (!$soffice || !file_exists($soffice)) {
            return ['success' => false, 'message' => 'LibreOffice (soffice) não encontrado.'];
        }
        if (!file_exists($entrada)) {
            return ['success' => false, 'message' => 'Arquivo de entrada não encontrado: ' . $entrada];
        }
        $outDir = dirname($saidaPdf);
        if (!is_dir($outDir)) { @mkdir($outDir, 0775, true); }
        $cmd = '"' . $soffice . '" --headless --nologo --nodefault --nofirststartwizard --convert-to pdf --outdir ' . '"' . $outDir . '" ' . '"' . $entrada . '"';
        $desc = [1=>['pipe','w'],2=>['pipe','w']];
        $proc = @proc_open($cmd, $desc, $pipes, dirname($entrada));
        if (!is_resource($proc)) { return ['success'=>false,'message'=>'Falha ao iniciar LibreOffice']; }
        $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); foreach ($pipes as $p) { if (is_resource($p)) fclose($p);} $code = proc_close($proc);
        $esperado = $outDir . DIRECTORY_SEPARATOR . pathinfo($entrada, PATHINFO_FILENAME) . '.pdf';
        if ($code === 0 && file_exists($esperado)) {
            if (realpath($esperado) !== realpath($saidaPdf)) { @rename($esperado, $saidaPdf); }
            return ['success'=>true,'pdf'=>$saidaPdf,'stdout'=>$stdout,'stderr'=>$stderr,'code'=>$code,'soffice'=>$soffice];
        }
        return ['success'=>false,'message'=>'Conversão via LibreOffice falhou','stdout'=>$stdout,'stderr'=>$stderr,'code'=>$code,'soffice'=>$soffice];
    }

    /** Pipeline universal por extensão: docx/pptx/odt/odp/fodt/fodp -> PDF (via LibreOffice). */
    public function gerarPdfDeModelo(string $templatePath, array $dados, string $saidaPdf, ?string $workdir = null): array
    {
        $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));
        $work = $workdir ?: sys_get_temp_dir();
        $tmp = rtrim($work, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cert-model-' . uniqid() . '.' . $ext;
        switch ($ext) {
            case 'docx':
                $this->preencherDocx($templatePath, $dados, $tmp);
                return $this->converterDocxParaPdf($tmp, $saidaPdf);
            case 'pptx':
                $this->preencherPptx($templatePath, $dados, $tmp);
                return $this->converterPptxParaPdf($tmp, $saidaPdf);
            case 'odt':
                $this->preencherOdt($templatePath, $dados, $tmp);
                return $this->converterOdtParaPdf($tmp, $saidaPdf);
            case 'odp':
                $this->preencherOdp($templatePath, $dados, $tmp);
                return $this->converterOdpParaPdf($tmp, $saidaPdf);
            case 'fodt':
                $this->preencherFodt($templatePath, $dados, $tmp);
                return $this->converterOdtParaPdf($tmp, $saidaPdf);
            case 'fodp':
                $this->preencherFodp($templatePath, $dados, $tmp);
                return $this->converterOdpParaPdf($tmp, $saidaPdf);
            default:
                return ['success' => false, 'message' => 'Extensão não suportada: ' . $ext];
        }
    }
}
