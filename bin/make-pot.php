<?php
/**
 * Extrait les strings i18n du thème et génère languages/lb3.pot.
 *
 * Usage : php bin/make-pot.php
 *
 * Couvre __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e, _x, _ex,
 * esc_html_x, esc_attr_x, _n (1er + 2e arg), _nx.
 *
 * À relancer quand on ajoute de nouvelles strings traduisibles.
 *
 * @package lb3
 */

declare(strict_types=1);

$themeDir = realpath(__DIR__ . '/..');
if ($themeDir === false) {
    fwrite(STDERR, "Unable to resolve theme dir\n");
    exit(1);
}

$excludeDirs = ['node_modules', 'dist', 'acf-json', 'vendor', 'bin', 'languages'];

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($themeDir, FilesystemIterator::SKIP_DOTS),
        static function ($item) use ($excludeDirs): bool {
            if ($item->isDir() && in_array($item->getFilename(), $excludeDirs, true)) {
                return false;
            }
            return true;
        }
    )
);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}
sort($files);

// Regex qui capture les 4 variantes de simple-arg : __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e
// Et les variantes _x / esc_html_x / esc_attr_x avec 1er arg msgid + 2e arg ctxt.
// Et _n / _nx avec 1er arg singular + 2e arg plural.
$simpleFuncs = '__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e';
$contextFuncs = '_x|_ex|esc_html_x|esc_attr_x';
$pluralFuncs = '_n|_nx';

$entries = []; // key "msgctxt|msgid|msgid_plural" => [msgid, msgctxt, msgid_plural, refs[]]

$addEntry = static function (string $msgid, ?string $msgctxt, ?string $msgid_plural, string $file, int $line) use (&$entries): void {
    $key = ($msgctxt ?? '') . "\x04" . $msgid . "\x04" . ($msgid_plural ?? '');
    if (!isset($entries[$key])) {
        $entries[$key] = [
            'msgid'        => $msgid,
            'msgctxt'      => $msgctxt,
            'msgid_plural' => $msgid_plural,
            'refs'         => [],
        ];
    }
    $entries[$key]['refs'][] = $file . ':' . $line;
};

// Extrait le contenu d'un string littéral PHP ('...' ou "...") avec gestion des échappements simples.
$parseString = static function (string $raw): ?string {
    if ($raw === '') {
        return null;
    }
    $quote = $raw[0];
    if ($quote !== "'" && $quote !== '"') {
        return null;
    }
    // Raw doit commencer/finir par le même quote.
    if (substr($raw, -1) !== $quote) {
        return null;
    }
    $inner = substr($raw, 1, -1);
    if ($quote === "'") {
        // PHP simple quote : seuls \\ et \' sont interprétés.
        return str_replace(["\\'", "\\\\"], ["'", "\\"], $inner);
    }
    // Double quote : \n, \t, \\, \", \$.
    $out = '';
    $len = strlen($inner);
    for ($i = 0; $i < $len; $i++) {
        $c = $inner[$i];
        if ($c === '\\' && $i + 1 < $len) {
            $n = $inner[$i + 1];
            switch ($n) {
                case 'n': $out .= "\n"; $i++; break;
                case 't': $out .= "\t"; $i++; break;
                case 'r': $out .= "\r"; $i++; break;
                case '\\': $out .= "\\"; $i++; break;
                case '"': $out .= '"'; $i++; break;
                case '$': $out .= '$'; $i++; break;
                default: $out .= $c; break;
            }
        } else {
            $out .= $c;
        }
    }
    return $out;
};

// Extrait une liste d'arguments simples (strings littéraux) depuis le contenu entre parenthèses.
// Renvoie les N premiers args (seulement strings ou null si non-string).
$extractArgs = static function (string $content, int $n) use ($parseString): array {
    $args = [];
    $pos = 0;
    $len = strlen($content);
    for ($k = 0; $k < $n; $k++) {
        // Skip whitespace.
        while ($pos < $len && ctype_space($content[$pos])) {
            $pos++;
        }
        if ($pos >= $len) {
            return $args;
        }
        $c = $content[$pos];
        if ($c !== "'" && $c !== '"') {
            return $args; // non-string arg : on s'arrête
        }
        $quote = $c;
        $start = $pos;
        $pos++;
        while ($pos < $len) {
            if ($content[$pos] === '\\' && $pos + 1 < $len) {
                $pos += 2;
                continue;
            }
            if ($content[$pos] === $quote) {
                $pos++;
                break;
            }
            $pos++;
        }
        $raw = substr($content, $start, $pos - $start);
        $parsed = $parseString($raw);
        if ($parsed === null) {
            return $args;
        }
        $args[] = $parsed;
        // Skip whitespace + virgule.
        while ($pos < $len && ctype_space($content[$pos])) {
            $pos++;
        }
        if ($pos < $len && $content[$pos] === ',') {
            $pos++;
        }
    }
    return $args;
};

// Trouve les appels de fonction, extrait le contenu entre parenthèses (gère les parenthèses imbriquées + quotes).
$findCalls = static function (string $code, string $funcPattern) {
    $calls = [];
    if (!preg_match_all('/\b(' . $funcPattern . ')\s*\(/', $code, $matches, PREG_OFFSET_CAPTURE)) {
        return $calls;
    }
    foreach ($matches[0] as $i => $match) {
        $funcName = $matches[1][$i][0];
        $openPos = $match[1] + strlen($match[0]) - 1; // position de la '('
        // Parcourir jusqu'à ')' matching.
        $depth = 1;
        $pos = $openPos + 1;
        $len = strlen($code);
        $inQuote = null;
        while ($pos < $len && $depth > 0) {
            $c = $code[$pos];
            if ($inQuote !== null) {
                if ($c === '\\' && $pos + 1 < $len) {
                    $pos += 2;
                    continue;
                }
                if ($c === $inQuote) {
                    $inQuote = null;
                }
                $pos++;
                continue;
            }
            if ($c === "'" || $c === '"') {
                $inQuote = $c;
                $pos++;
                continue;
            }
            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
            $pos++;
        }
        if ($depth !== 0) {
            continue;
        }
        $innerStart = $openPos + 1;
        $innerLen = $pos - $innerStart;
        $inner = substr($code, $innerStart, $innerLen);
        // Calcul numéro de ligne.
        $line = substr_count($code, "\n", 0, $match[1]) + 1;
        $calls[] = ['func' => $funcName, 'inner' => $inner, 'line' => $line];
    }
    return $calls;
};

foreach ($files as $filePath) {
    $code = file_get_contents($filePath);
    if ($code === false) {
        continue;
    }
    $relative = ltrim(str_replace($themeDir, '', $filePath), '/');

    // Simple : __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e — 1 arg string.
    foreach ($findCalls($code, $simpleFuncs) as $call) {
        $args = $extractArgs($call['inner'], 1);
        if (!empty($args)) {
            $addEntry($args[0], null, null, $relative, $call['line']);
        }
    }
    // Context : _x, _ex, esc_html_x, esc_attr_x — 2 args (msgid, context).
    foreach ($findCalls($code, $contextFuncs) as $call) {
        $args = $extractArgs($call['inner'], 2);
        if (count($args) === 2) {
            $addEntry($args[0], $args[1], null, $relative, $call['line']);
        }
    }
    // Plural : _n, _nx — 2 ou 3 args strings (singular, plural, [context]).
    foreach ($findCalls($code, $pluralFuncs) as $call) {
        $args = $extractArgs($call['inner'], 4); // max 4 : singular, plural, count, [text_domain|context]
        if (count($args) >= 2) {
            $ctxt = ($call['func'] === '_nx' && isset($args[3])) ? $args[3] : null;
            $addEntry($args[0], $ctxt, $args[1], $relative, $call['line']);
        }
    }
}

// Tri par msgid pour un .pot stable.
uasort($entries, static function ($a, $b) {
    return strcmp(
        ($a['msgctxt'] ?? '') . '|' . $a['msgid'],
        ($b['msgctxt'] ?? '') . '|' . $b['msgid']
    );
});

$escape = static function (string $s): string {
    $s = str_replace(["\\", "\""], ["\\\\", "\\\""], $s);
    $s = str_replace(["\n", "\t", "\r"], ['\\n', '\\t', '\\r'], $s);
    return $s;
};

$now = gmdate('Y-m-d H:iO');
$pot = <<<POT
# Copyright (C) Lucie Baudinaud
# This file is distributed under the same license as the lb3 theme.
msgid ""
msgstr ""
"Project-Id-Version: lb3 1.0.0\\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: {$now}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"X-Generator: lb3 i18n (bin/make-pot.php)\\n"
"X-Domain: lb3\\n"


POT;

foreach ($entries as $entry) {
    foreach ($entry['refs'] as $ref) {
        $pot .= "#: {$ref}\n";
    }
    if ($entry['msgctxt'] !== null) {
        $pot .= "msgctxt \"" . $escape($entry['msgctxt']) . "\"\n";
    }
    $pot .= "msgid \"" . $escape($entry['msgid']) . "\"\n";
    if ($entry['msgid_plural'] !== null) {
        $pot .= "msgid_plural \"" . $escape($entry['msgid_plural']) . "\"\n";
        $pot .= "msgstr[0] \"\"\n";
        $pot .= "msgstr[1] \"\"\n";
    } else {
        $pot .= "msgstr \"\"\n";
    }
    $pot .= "\n";
}

$outPath = $themeDir . '/languages/lb3.pot';
if (file_put_contents($outPath, $pot) === false) {
    fwrite(STDERR, "Failed to write {$outPath}\n");
    exit(1);
}

printf("Wrote %s (%d entries)\n", $outPath, count($entries));
