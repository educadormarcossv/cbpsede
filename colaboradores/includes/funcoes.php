<?php

function escaparHtml(?string $valor): string {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData(?string $data): string {
    if (!$data) return '-';
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : '-';
}

function calcularIdade(?string $dataNascimento): ?int {
    if (!$dataNascimento) return null;
    try {
        $nascimento = new DateTime($dataNascimento);
        $hoje = new DateTime('today');
        return $hoje->diff($nascimento)->y;
    } catch (Exception $e) {
        return null;
    }
}

function linkWhatsApp(?string $telefone, string $mensagem = ''): ?string {
    if (!$telefone) return null;
    $numero = preg_replace('/\D/', '', $telefone);
    if (strlen($numero) < 10) return null;
    if (substr($numero, 0, 2) !== '55') {
        $numero = '55' . $numero;
    }
    $url = 'https://wa.me/' . $numero;
    if ($mensagem !== '') {
        $url .= '?text=' . rawurlencode($mensagem);
    }
    return $url;
}

function fotoExisteNoServidor(?string $caminhoRelativo): bool {
    if (!$caminhoRelativo) return false;
    return is_file(__DIR__ . '/../' . $caminhoRelativo);
}

/**
 * Salva uma foto enviada (upload) redimensionando pro tamanho máximo indicado.
 * Retorna o caminho relativo salvo (a partir de /colaboradores/) ou null se não houver arquivo.
 * Lança uma string de erro em $erro por referência quando algo dá errado.
 */
function salvarFotoUpload(array $arquivo, string $pastaDestinoAbsoluta, string $prefixo, ?string &$erro, int $maxLado = 640): ?string {
    $erro = null;
    if (empty($arquivo['name']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Falha no envio do arquivo.';
        return null;
    }
    if ($arquivo['size'] > 8 * 1024 * 1024) {
        $erro = 'Arquivo maior que 8MB.';
        return null;
    }
    $info = @getimagesize($arquivo['tmp_name']);
    if (!$info) {
        $erro = 'Envie uma imagem válida (JPG, PNG ou WEBP).';
        return null;
    }
    [$largura, $altura, $tipo] = $info;
    $origem = match ($tipo) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($arquivo['tmp_name']),
        IMAGETYPE_PNG => imagecreatefrompng($arquivo['tmp_name']),
        IMAGETYPE_WEBP => imagecreatefromwebp($arquivo['tmp_name']),
        default => null,
    };
    if (!$origem) {
        $erro = 'Formato de imagem não suportado.';
        return null;
    }

    $escala = min(1, $maxLado / max($largura, $altura));
    $novaLargura = (int) round($largura * $escala);
    $novaAltura = (int) round($altura * $escala);
    $destino = imagecreatetruecolor($novaLargura, $novaAltura);
    imagecopyresampled($destino, $origem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

    if (!is_dir($pastaDestinoAbsoluta)) {
        mkdir($pastaDestinoAbsoluta, 0755, true);
    }
    $nomeArquivo = $prefixo . '_' . bin2hex(random_bytes(6)) . '.jpg';
    imagejpeg($destino, $pastaDestinoAbsoluta . '/' . $nomeArquivo, 82);
    imagedestroy($origem);
    imagedestroy($destino);

    return $nomeArquivo;
}

/** Lista aniversariantes (membros e crianças) dos próximos $dias dias, ordenado pela próxima ocorrência. */
function buscarAniversariantes(PDO $pdo, int $dias = 30): array {
    $sql = "
        SELECT nome, data_nascimento, 'membro' AS tipo, id
        FROM membros
        WHERE data_nascimento IS NOT NULL AND ativo = 1
        UNION ALL
        SELECT nome, data_nascimento, 'crianca' AS tipo, id
        FROM criancas
        WHERE data_nascimento IS NOT NULL
    ";
    $todos = $pdo->query($sql)->fetchAll();

    $hoje = new DateTime('today');
    $resultado = [];
    foreach ($todos as $pessoa) {
        $nasc = new DateTime($pessoa['data_nascimento']);
        $proximo = new DateTime($hoje->format('Y') . '-' . $nasc->format('m-d'));
        if ($proximo < $hoje) {
            $proximo->modify('+1 year');
        }
        $diff = (int) $hoje->diff($proximo)->days;
        if ($diff <= $dias) {
            $pessoa['proxima_data'] = $proximo;
            $pessoa['dias_faltando'] = $diff;
            $pessoa['idade_completar'] = (int) $nasc->diff($proximo)->y;
            $resultado[] = $pessoa;
        }
    }
    usort($resultado, fn($a, $b) => $a['dias_faltando'] <=> $b['dias_faltando']);
    return $resultado;
}
