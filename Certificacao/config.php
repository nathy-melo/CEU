<?php
// Configurações do módulo de Certificação

return [
    // Caminho para o autoload do Composer (vendor)
    'caminho_autoload'      => __DIR__ . '/bibliotecas/vendor/autoload.php',

    // Diretórios principais
    'diretorio_modelos'     => __DIR__ . '/templates',
    'diretorio_certificados'=> __DIR__ . '/certificados',
    'diretorio_temporario'  => sys_get_temp_dir(),

    // Opcional: caminho específico do LibreOffice (soffice). Se null, será detectado automaticamente
    'caminho_soffice'       => null,
];
