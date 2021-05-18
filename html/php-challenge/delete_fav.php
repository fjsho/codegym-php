<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
    $id = $_REQUEST['id'];

    // いいね！を検査する
    $favorites = $db->prepare('SELECT * FROM favorites WHERE id=?');
    $favorites->execute(array($id));
    $favorite = $favorites->fetch();

    if ($favorite['member_id'] == $_SESSION['id']) {
        // 削除する
        $del = $db->prepare('DELETE FROM favorites WHERE id=?');
        $del->execute(array($id));
    }
}

header('Location: index.php');
exit();
