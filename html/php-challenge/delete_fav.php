<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
    $id = $_REQUEST['id'];

    // いいね！を削除する
    $del = $db->prepare('DELETE FROM favorites WHERE id=? AND member_id=?');
    $del->execute(array($id,$_SESSION['id']));
}

header('Location: index.php');
exit();
