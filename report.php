#!/usr/bin/env php
<?php

const REPORT_FILE = 'report.html';
const PHPCF_PATH = '~/phpcf/phpcf';

if (count($argv) <= 1 || !file_exists($argv[1])) {
    die("Directory?.\n");
}

$fileList = implode(
    ' ',
    array_keys(
        iterator_to_array(
            new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $argv[1]
                    )
                ), '/\.php$/i'
            )
        )
    )
);

if ($fileList) {
    $result = '<h1>Style Check Report @ ' . date('Y-m-d H:i') . '</h1>';
    exec(PHPCF_PATH . ' check ' . $fileList . ' > ' . REPORT_FILE);
    $content = file(REPORT_FILE);

    if ($content) {
        $badResult = array();
        $goodResult = array();
        foreach ($content as $line) {
            $line = str_replace($argv[1], '', $line);
            if (strpos($line, 'not need formatting') === false) {
                if (strpos($line, 'issues')) {
                    $line = '<font color="red"><b>' . $line . '</b></font>';
                }
                $badResult[] = $line;
            } else {
                $line = str_replace('does not need formatting', '', $line);
                $goodResult[] = $line;
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
