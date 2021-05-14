<?php
$array = explode(',', $_GET['array']);
// 修正はここから
$my_count = count($array);

// function my_count($array) {
//  print_r("call my_count\n");
//   return count($array);
// }

for ($i = $my_count; $i > 0; $i--) {
  for ($j = 0; $j < $my_count - 1; $j++) {
    if ($array[$j] > $array[$j + 1]) {
      $big = $array[$j];
      $array[$j] = $array[$j + 1];
      $array[$j + 1] = $big;
    }
  }
}
// 修正はここまで

//whileを使う場合
// $i = 0;
// while ($i < count($array)) {
//   for ($j = 0; $j < count($array) - 1; $j++) {
//     if ($array[$j] > $array[$j + 1]) {
//       $big = $array[$j];
//       $array[$j] = $array[$j + 1];
//       $array[$j + 1] = $big;
//     }
//   }
//   $i++;
// }

echo "<pre>";
print_r($array);
echo "</pre>";
