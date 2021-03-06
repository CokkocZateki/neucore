#!/usr/bin/env php
<?php declare(strict_types=1);

$swagger = file_get_contents('https://esi.evetech.net/latest/swagger.json');
$def = json_decode($swagger);

$httpGet = [];
foreach ($def->paths as $path => $data) {
    if (! isset($data->get)) {
        continue;
    }

    $httpGet[] = '/latest' . $path;
}

file_put_contents(__DIR__ . '/../../web/static/esi-paths-http-get.json', json_encode($httpGet, JSON_UNESCAPED_SLASHES));

echo "wrote web/static/esi-paths-http-get.json", PHP_EOL;
