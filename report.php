#!/usr/bin/env php
<?php

define('REPORT_FILE', 'report.html');
define('REPORT_TEMPLATE_FILE', __DIR__ . '/report.template.html');
define('PHPCF_PATH', '~/phpcf/phpcf');

define('REGEX_ISSUE', '/(.*)\sissues\:/i');
define('REGEX_ERROR', '/(.*)\son\s(line\s[0-9]+.*)/i');

$fileList = '';
$argList = array();
$badResult = array();

if (count($argv) <= 1) {
    die("Uh?.\n");
}

try {
    for ($argIdent = 1; $argIdent < count($argv); $argIdent++) {
        $resultList = '';
        if (!file_exists($argv[$argIdent])) {
            continue;
        }
        $argList[] = $argv[$argIdent];
        if (is_file($argv[$argIdent])
            && pathinfo($argv[$argIdent], PATHINFO_EXTENSION) == 'php') {
            $resultList = $argv[$argIdent];
        } else {
            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $argv[$argIdent]
                    )
                ),
                '/\.php$/i'
            );
            $resultList = implode(
                ' ',
                array_keys(
                    iterator_to_array(
                        $iterator
                    )
                )
            );
        }
        if ($resultList) {
            $fileList .= $resultList . ' ';
        }
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage() . "\n");
}

if (!$fileList) {
    die("Empty file list.\n");
}

exec(PHPCF_PATH . ' check ' . $fileList, $content);

if (!$content) {
    die("Error running PHPCF.\n");
}

$isOpenTag = false;
foreach ($content as $line) {
    if (strpos($line, 'does not need formatting') !== false || !$line) {
        continue;
    }

    if (preg_match(REGEX_ISSUE, $line)) {
        $line = preg_replace(
            REGEX_ISSUE,
            ($isOpenTag ? '</table></p>' : '') . '<p style="font-size: 16px"><code>$1</code></p><p><table class="table">',
            $line
        );
        $isOpenTag = true;
    } else if (preg_match(REGEX_ERROR, $line)) {
        $line = preg_replace(
            REGEX_ERROR,
            '<tr><td style="width: 20%;"><kbd>$2</kbd></td><td>$1</td></tr>',
            $line
        );
    } else {
        $line = '<tr><td></td><td>' . $line . '</td></tr>';
    }

    $badResult[] = $line;
}

$result = file_get_contents(REPORT_TEMPLATE_FILE);

if (!$result) {
    die("Template not found.\n");
}

$result = str_replace('{{date}}', date('Y-m-d @ H:i'), $result);

if ($badResult) {
    $result = str_replace('{{list}}', implode('', $badResult), $result);
} else {
    $result = str_replace('{{list}}', '<h3>Does not need formatting</h3>' . implode('<br>', $argList), $result);
}

if (file_put_contents(REPORT_FILE, $result)) {
    die("Done.\n");
} else {
    die("Cant save report file.\n");
}
