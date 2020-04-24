<?php

use App\GxpResolver;

define('DOCROOT', __DIR__);

require DOCROOT . '/vendor/autoload.php';

$s = [];

//
//$__t = microtime(1);
//$ss = json_decode(file_get_contents('http://localhost:8080/data'));
//$_times = (microtime(1) - $__t) * 1000;
//$s[] = [
//  'time' => $_times,
//  'response' => $ss,
//];

$json['global'] = [
  'scope' => 'GLOBAL',
  'type' => '_default',
  'name' => 'zumba',
  'picture' => 'https://i.ytimg.com/vi/Bv245eKS15o/maxresdefault.jpg',
];
$json['wichita'] = [
  'scope' => 'wichita',
  'type' => '_class',
  'name' => 'zumba',
  'picture' => 'https://i.ytimg.com/vi/Bv245eKS15o/maxresdefault.jpg',
];

$json['default'] = [
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_DEFAULT,  'name' => '',        'picture' => 'global default'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'zumba',   'picture' => 'global category zumba'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'boxing',  'picture' => 'global category boxing'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'soccer',  'picture' => 'global category soccer'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'barre',   'picture' => 'global category barre'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CLASS,    'name' => 'zumba',   'picture' => 'global class zumba'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CLASS,    'name' => 'boxing',  'picture' => 'global class boxing'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CLASS,    'name' => 'soccer',  'picture' => 'global class soccer'],
  ['scope' => GxpResolver::SCOPE_GLOBAL, 'type' => GxpResolver::TYPE_CLASS,    'name' => 'barre',   'picture' => 'global class barre'],
];

$json['wichita_full'] = [
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_DEFAULT,  'name' => '',        'picture' => 'wichita default'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'zumba',   'picture' => 'wichita category zumba'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'boxing',  'picture' => 'wichita category boxing'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'soccer',  'picture' => 'wichita category soccer'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'barre',   'picture' => 'wichita category barre'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'zumba',   'picture' => 'wichita class zumba'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'boxing',  'picture' => 'wichita class boxing'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'soccer',  'picture' => 'wichita class soccer'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'barre',   'picture' => 'wichita class barre'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'zumba1',  'picture' => 'wichita class zumba1'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'boxing1', 'picture' => 'wichita class boxing1'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'soccer1', 'picture' => 'wichita class soccer1'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'barre1',  'picture' => 'wichita class barre1'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'zumba2',  'picture' => 'wichita class zumba2'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'boxing2', 'picture' => 'wichita class boxing2'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'soccer2', 'picture' => 'wichita class soccer2'],
  ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS,    'name' => 'barre2',  'picture' => 'wichita class barre2'],
];

$json['random_categories'] = [];
for ($i = 1; $i < 100000; $i++) {
  $json['random_categories'][] = ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CATEGORY, 'name' => 'category' . $i,  'picture' => 'wichita category category'  . $i];
}
$json['random_classes'] = [];
for ($i = 1; $i < 100000; $i++) {
  $json['random_classes'][] = ['scope' => 'wichita', 'type' => GxpResolver::TYPE_CLASS, 'name' => 'class' . $i,  'picture' => 'wichita class class'  . $i];
}

$json['random_multi'] = [];
for ($i = 1; $i < 100; $i++) {
  $json['random_multi'][] = ['category' => 'category' . mt_rand(0, 200000), 'class' => 'class' . mt_rand(0, 200000)];
}

function post($uri, $data) {
  $client = new \GuzzleHttp\Client([
    'base_uri' => 'http://localhost:8080/',
    'timeout' => 2.0,
  ]);

  $timer = -microtime(1);
  try {
    $response = $client->post($uri, [
      'json' => $data
    ]);
  } catch (\Exception $e) {
    print_r($e->getMessage());
    return $e->getMessage();
  }
  $timer += microtime(1);
  return [
    'data' => json_encode($data),
    'time' => $timer * 1000,
    'response' => $response->getBody()->getContents()
  ];
}

function get($url) {
  $timer = -microtime(1);
  $response = file_get_contents($url);
  $timer += microtime(1);

  return [
    'url' => $url,
    'time' => $timer * 1000,
    'response' => $response
  ];
}

foreach ($json['default'] as $data) {
  $s[] = post('/api/v1/add', $data);
}
foreach ($json['wichita_full'] as $data) {
  $s[] = post('/api/v1/add', $data);
}
//foreach ($json['random_categories'] as $data) {
//  $s[] = post('/api/v1/add', $data);
//}
//foreach ($json['random_classes'] as $data) {
//  $s[] = post('/api/v1/add', $data);
//}

//post($json['wichita']);

// Direct GET
//$s[] = get('http://localhost:8080/get/scope/wichita/cat/zumba/class/zumba');

$timer = -microtime(1);

// Search
//for ($i = 0; $i < 20; $i++) {
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita/cat/zumba/class/zumba');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita/cat/zumba/class/zumba2');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita/cat/barre/class/barre');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita/cat/barre/class/barre2');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita/cat/barre/class/barre3');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita/cat/barre2/class/barre3');
//
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita2/cat/zumba/class/zumba');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita2/cat/zumba/class/zumba2');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita2/cat/barre/class/barre');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita2/cat/barre/class/barre2');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita2/cat/barre/class/barre3');
//  $s[] = get('http://localhost:8080/api/v1/search/scope/wichita2/cat/barre2/class/barre3');
//}

$s[] = post('/api/v1/search/scope/wichita', [
  ['category' => 'barre', 'class' => 'barre'],
  ['category' => 'barre', 'class' => 'barre'],
]);

$s[] = post('/api/v1/search/scope/wichita', $json['random_multi']);
$s[] = get('http://localhost:8080/stats');
$s[] = get('http://localhost:8080/get/scope/wichita/cat/zumba4/class/zumba4');
//$s[] = get('http://localhost:8080/api/v1/flush');


$timer += microtime(1);
echo $timer . PHP_EOL;

$times = array_column($s, 'time');
sort($times);
echo 'MAX: ' . max($times) . PHP_EOL;
echo 'MIN: ' . min($times) . PHP_EOL;
echo 'AVG: ' . (array_sum($times) / count($times)) . PHP_EOL;
echo 'MEAN: ' . ($times[intval(count($times)/2)]) . PHP_EOL;
// Full data dump
//$s[] = get('http://localhost:8080/data');

print_r($s);
