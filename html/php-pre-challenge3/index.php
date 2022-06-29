<?php
$limit = $_GET['target']; // stringで受け取っている。
if (!ctype_digit($limit) || preg_match("/^[0]/", $limit)) {
  exit('Bad Request');
}

$dsn = 'mysql:dbname=test;host=mysql';
$dbuser = 'test';
$dbpassword = 'test';

// PDOを使ってDBに接続
try {
  $dbh = new PDO($dsn, $dbuser, $dbpassword);
} catch (PDOException $e) {
  exit('Internal Server Error');
}

// DBからデータ取得
$sql = "select value from prechallenge3";
$statement = $dbh->prepare($sql);
$statement->execute();
$res = $statement->fetchAll(PDO::FETCH_ASSOC);
// var_dump($res);
foreach ($res as $value) {
  $arr[] = (int)$value['value'];
}

$combinations = getAllCombinations($arr);
$matched_array = getCombinationsToMatchTargetValue($combinations, $limit);
$result = json_encode($matched_array);
print($result);

/**
 * すべての組み合わせを返す（重複なし）
 *
 * @param array $arr 選ぶ元となる配列
 * @return array
 */
function getAllCombinations(array $arr): array
{
  $arr = array_unique($arr);
  $results = [[]];

  foreach ($arr as $item) {
    foreach ($results as $result) {
      // php7.4 以上の場合
      // $results[] = [$item, ...$result];

      // PHP7.4 未満の場合
      $results[] = array_merge([$item], $result);
    }
  }
  array_shift($results);
  return array_values($results);
}

/**
 * 和が$targetに一致する値の組み合わせを配列として返す
 * 
 * @param arr $arrays 検査する配列
 * @param int $target 目的とする値
 */
function getCombinationsToMatchTargetValue(array $arrays, int $target)
{
  $matched_arrays = [];
  foreach ($arrays as $array) {
    if (array_sum($array) === $target) {
      $matched_arrays[] = $array;
    }
  }
  return $matched_arrays;
}
