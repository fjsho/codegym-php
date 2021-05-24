<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {

    // いいね！を追加する
    $cre = $db->prepare('INSERT INTO favorites SET member_id=?, post_id=?, created=NOW()');
    $cre->execute(array(
        $_SESSION['id'],
        $_REQUEST['post_id']
    ));
}

header('Location: index.php');
exit();
