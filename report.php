#!/usr/bin/env php
<?php

const REPORT_FILE = 'report.html';
const PHPCF_PATH = '~/phpcf/phpcf';

if (count($argv) <= 1 || !file_exists($argv[1])) {
    die("Directory?.\n");
}

try {
    $fileList = implode(
        ' ',
        array_keys(
            iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $argv[1]
                        )
                    ),
                    '/\.php$/i'
                )
            )
        )
    );
} catch (Exception $e) {
    die('Error: ' .  $e->getMessage() . "\n");
}

if ($fileList) {
    $result = '<h1>Style Check Report @ ' . date('Y-m-d H:i') . '</h1>';
    exec(PHPCF_PATH . ' check ' . $fileList . ' > ' . REPORT_FILE);
    $content = file(REPORT_FILE);

    if ($content) {
        $badResult = array();
        $goodResult = array();
        foreach ($content as $line) {
            $line = str_replace($argv[1], '', $line);
            if (strpos($line, 'does not need formatting') !== false) {
                $line = str_replace('does not need formatting', '', $line);
                $goodResult[] = $line;
            } else {
                if (strpos($line, 'issues')) {
                    $line = preg_replace(
                        '/(.*)\sissues\:/i',
                        '<font color="red"><b>$1</b></font>',
                        $line
                    );
                } else {
                    $line = preg_replace(
                        '/(.*)\son\s(line\s[0-9]+.*)/i',
                        '<b>$2</b>: $1',
                        $line
                    );
                }
                $badResult[] = $line;
            }
        }

        if ($badResult) {
            $result .= '<h2>Needs to be formatted</h2>';
            $result .= implode('<br>', $badResult);
        }

        if ($goodResult) {
            $result .= '<h2>Not need formatting</h2>';
            $result .= implode('<br>', $goodResult);
        }

        file_put_contents(
            REPORT_FILE,
            $result
        );
    }
}
