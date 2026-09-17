<?php
// Feed de agenda (.ics) para assinar no Google Agenda, Outlook ou Apple Calendar.
// Não usa login de sessão (o Google busca esse link periodicamente sozinho, sem
// estar logado): a proteção é o token secreto na URL. Não compartilhe esse link.

require_once __DIR__ . '/includes/db.php';

$token = $_GET['token'] ?? '';
if (!hash_equals(ICS_TOKEN, $token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Acesso negado.';
    exit;
}

function icsEscapar(string $texto): string {
    $texto = str_replace(['\\', "\r\n", "\n", ',', ';'], ['\\\\', '\\n', '\\n', '\\,', '\\;'], $texto);
    return $texto;
}

/** Quebra linhas longas em 75 octetos, como pede o RFC 5545. */
function icsDobrarLinha(string $linha): string {
    $bytes = strlen($linha);
    if ($bytes <= 75) return $linha . "\r\n";
    $partes = [];
    $offset = 0;
    $primeiro = true;
    while ($offset < $bytes) {
        $tamanho = $primeiro ? 75 : 74;
        $parte = substr($linha, $offset, $tamanho);
        $partes[] = ($primeiro ? '' : ' ') . $parte;
        $offset += $tamanho;
        $primeiro = false;
    }
    return implode("\r\n", $partes) . "\r\n";
}

$pdo = conectarBanco();
$eventos = $pdo->query("
    SELECT * FROM eventos
    WHERE data_evento >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    ORDER BY data_evento
")->fetchAll();

$escalas = $pdo->query("
    SELECT e.*, m.nome AS ministerio_nome FROM escalas e
    JOIN ministerios m ON m.id = e.ministerio_id
    WHERE e.data_escala >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    ORDER BY e.data_escala
")->fetchAll();

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="agenda-cbp-lideres.ics"');

$saida = '';
$saida .= icsDobrarLinha('BEGIN:VCALENDAR');
$saida .= icsDobrarLinha('VERSION:2.0');
$saida .= icsDobrarLinha('PRODID:-//CBP Sede//Painel de Lideres//PT-BR');
$saida .= icsDobrarLinha('CALSCALE:GREGORIAN');
$saida .= icsDobrarLinha('X-WR-CALNAME:Agenda CBP Lideres');
$saida .= icsDobrarLinha('X-WR-TIMEZONE:America/Sao_Paulo');

foreach ($eventos as $ev) {
    $uid = 'evento-' . $ev['id'] . '@cbpsede.com.br';
    $data = str_replace('-', '', $ev['data_evento']);
    $saida .= icsDobrarLinha('BEGIN:VEVENT');
    $saida .= icsDobrarLinha('UID:' . $uid);
    $saida .= icsDobrarLinha('DTSTAMP:' . gmdate('Ymd\THis\Z'));

    if ($ev['hora_evento']) {
        $hora = str_replace(':', '', $ev['hora_evento']);
        $inicio = $data . 'T' . $hora;
        $saida .= icsDobrarLinha('DTSTART;TZID=America/Sao_Paulo:' . $inicio);
        $saida .= icsDobrarLinha('DURATION:PT1H');
    } else {
        $saida .= icsDobrarLinha('DTSTART;VALUE=DATE:' . $data);
        $fim = date('Ymd', strtotime($ev['data_evento'] . ' +1 day'));
        $saida .= icsDobrarLinha('DTEND;VALUE=DATE:' . $fim);
    }

    $titulo = $ev['titulo'] . ($ev['categoria'] ? ' (' . $ev['categoria'] . ')' : '');
    $saida .= icsDobrarLinha('SUMMARY:' . icsEscapar($titulo));
    if ($ev['descricao']) $saida .= icsDobrarLinha('DESCRIPTION:' . icsEscapar($ev['descricao']));
    if ($ev['local']) $saida .= icsDobrarLinha('LOCATION:' . icsEscapar($ev['local']));
    $saida .= icsDobrarLinha('END:VEVENT');
}

foreach ($escalas as $es) {
    $uid = 'escala-' . $es['id'] . '@cbpsede.com.br';
    $data = str_replace('-', '', $es['data_escala']);
    $saida .= icsDobrarLinha('BEGIN:VEVENT');
    $saida .= icsDobrarLinha('UID:' . $uid);
    $saida .= icsDobrarLinha('DTSTAMP:' . gmdate('Ymd\THis\Z'));

    if ($es['hora_escala']) {
        $hora = str_replace(':', '', $es['hora_escala']);
        $inicio = $data . 'T' . $hora;
        $saida .= icsDobrarLinha('DTSTART;TZID=America/Sao_Paulo:' . $inicio);
        $saida .= icsDobrarLinha('DURATION:PT1H');
    } else {
        $saida .= icsDobrarLinha('DTSTART;VALUE=DATE:' . $data);
        $fim = date('Ymd', strtotime($es['data_escala'] . ' +1 day'));
        $saida .= icsDobrarLinha('DTEND;VALUE=DATE:' . $fim);
    }

    $titulo = 'Escala · ' . $es['ministerio_nome'] . ' · ' . $es['titulo'];
    $saida .= icsDobrarLinha('SUMMARY:' . icsEscapar($titulo));
    if ($es['observacoes']) $saida .= icsDobrarLinha('DESCRIPTION:' . icsEscapar($es['observacoes']));
    $saida .= icsDobrarLinha('END:VEVENT');
}

$saida .= icsDobrarLinha('END:VCALENDAR');

echo $saida;
