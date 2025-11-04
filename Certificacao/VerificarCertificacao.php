<?php
// Página pública: formulário para digitar o código e visualizar o certificado
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificar Certificação</title>
    <style>
        :root {
            --prim: #334b68;
            --acc: #6598d2;
            --bg: #eef5ff;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: var(--bg);
            color: #1a1a1a;
        }

        header {
            padding: 16px 20px;
            background: var(--prim);
            color: #fff;
        }

        .wrap {
            padding: 16px;
            max-width: 920px;
            margin: 0 auto;
        }

        form {
            background: #fff;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        label {
            font-weight: 600;
        }

        input[type="text"] {
            padding: 10px 12px;
            border: 1px solid #cdd6e0;
            border-radius: 8px;
            font-size: 16px;
            flex: 1 1 260px;
        }

        button {
            background: var(--acc);
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 16px;
            cursor: pointer;
        }

        .hint {
            margin-top: 10px;
            font-size: .92rem;
            opacity: .8;
        }

        .viewer {
            margin-top: 18px;
        }

        iframe {
            width: 100%;
            height: 75vh;
            border: 0;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .1);
            border-radius: 8px;
        }

        .error {
            background: #ffebeb;
            color: #7a1f1f;
            padding: 12px 14px;
            border-radius: 8px;
            margin: 12px 0;
        }
    </style>
</head>

<body>
    <header>
        <strong>CEU · Verificar Certificação</strong>
    </header>
    <div class="wrap">
        <form method="post">
            <label for="codigo">Código do Certificado</label>
            <input type="text" id="codigo" name="codigo" inputmode="latin" autocomplete="off" placeholder="Ex.: J8KLMA73" required pattern="[A-Za-z0-9]{6,16}" title="6 a 16 caracteres, letras e números">
            <button type="submit">Verificar</button>
            <div class="hint">Digite o código exibido no certificado.</div>
        </form>

        <?php
        $codigo = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
            $codigo = strtoupper(trim((string)$_POST['codigo']));
            if (!preg_match('/^[A-Z0-9]{6,16}$/', $codigo)) {
                echo '<div class="error">Código inválido. Verifique e tente novamente.</div>';
                $codigo = '';
            }
        }
        if ($codigo) {
            $src = 'ver_certificado.php?codigo=' . rawurlencode($codigo);
            echo '<div class="viewer">';
            echo '<iframe src="' . htmlspecialchars($src) . '" allowfullscreen></iframe>';
            echo '</div>';
        }
        ?>
    </div>
</body>

</html>