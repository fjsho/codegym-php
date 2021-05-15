<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();

    $members = $db->prepare('SELECT * FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
} else {
    // ログインしていない
    header('Location: login.php');
    exit();
}

// 投稿を記録する
if (!empty($_POST)) {
    if ($_POST['message'] != '') {
        $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, retweet_post_id=?, created=NOW()');
            $message->execute(array(
                $member['id'],
                $_POST['message'],
                $_POST['reply_post_id'],
                $_POST['retweet_post_id']
            ));

        header('Location: index.php');
        exit();
    }
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
    $page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

// 返信の場合
if (isset($_REQUEST['res'])) {
    $response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
    $response->execute(array($_REQUEST['res']));

    $table = $response->fetch();
    $message = '@' . $table['name'] . ' ' . $table['message'];
}

// htmlspecialcharsのショートカット
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value)
{
    return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>', $value);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ひとこと掲示板</title>

    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <div id="wrap">
        <div id="head">
            <h1>ひとこと掲示板</h1>
        </div>
        <div id="content">
            <div style="text-align: right"><a href="logout.php">ログアウト</a></div>
            <form action="" method="post">
                <dl>
                    <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
                    <dd>
                        <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
                        <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
                        <input type="hidden" name="retweet_post_id" value="" />
                    </dd>
                </dl>
                <div>
                    <p>
                        <input type="submit" value="投稿する" />
                    </p>
                </div>
            </form>

            <?php
            foreach ($posts as $post) :
            ?>
                <div class="msg">
                    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
                    <!-- RTした人の名前の表示 -->
                    <?php if($post['retweet_post_id']): ?>
                    <p style="font-size:0.8em;"><?php echo h($post['name']); ?>さんがリツイートしました</p>
                    <?php endif; ?>
                    <!-- RT投稿（retweet_post_id != 0）の場合は、投稿内容の後に元の投稿者の名前を表示する -->
                    <?php
                    if($post['retweet_post_id']){
                        $postNames = $db->prepare('SELECT m.name FROM members m, posts p WHERE m.id=p.member_id and p.id=?');
                        $postNames->bindParam(1,$post['retweet_post_id'],PDO::PARAM_INT);
                        $postNames->execute();
                        $postName = $postNames->fetch();                        
                    }else{
                        $postName['name'] = $post['name'];
                    }
                    ?>
                    <p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($postName['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>

                    <p class="day">
                        <!-- 課題：リツイートといいね機能の実装 -->
                        <!-- RT機能 -->
                        <!-- RT数を取得する -->
                        <?php
                        if($post['retweet_post_id']){
                            $retweetCounts = $db->prepare('SELECT COUNT(retweet_post_id) AS cnt FROM posts WHERE retweet_post_id=?');
                            $retweetCounts->bindParam(1,$post['retweet_post_id'],PDO::PARAM_INT);
                            $retweetCounts->execute();
                            $retweetCount = $retweetCounts->fetch();                        
                        }else{
                            $retweetCount = 0;
                        }
                        ?>
                        <!-- RTボタン -->
                        <form action="" method="post">
                            <input type="hidden" name="message" value="<?php echo h($post['message']); ?>" />                        
                            <input type="hidden" name="reply_post_id" value="" />
                            <!-- RTの有無による画像と文字色の変更 -->
                            <?php $imgSrc = $retweetCount['cnt'] > 0 ? 'images/retweet-solid-blue.svg' : 'images/retweet-solid-gray.svg' ; ?>
                            <?php $imgColor = $retweetCount['cnt'] > 0 ?'color:blue;' : 'color:gray;' ; ?>
                            <!-- RT投稿と取り消しの分岐 -->
                            <?php if($post['retweet_post_id'] != 0 && $post['name'] === $member['name']): ?>
                                <!-- RT取り消し -->
                                <a class="retweet" href="delete.php?id=<?php echo h($post['id']); ?>"><img  class="retweet-image" src="<?php echo $imgSrc; ?>" alt=""></a><span style="<?php echo $imgColor; ?>"><?php echo $retweetCount['cnt']; ?></span>
                            <?php else: ?>
                                <!-- RT投稿 -->
                                <!-- retweet_post_id：初回リツイート時は投稿id、２回目以降はRTのidで登録する -->
                                <?php $retweetId = $post['retweet_post_id'] == 0 ? $post['id'] : $post['retweet_post_id']; ?>
                                <input type="hidden" name="retweet_post_id" value="<?php echo h($retweetId); ?>" />
                                
                                <span class="retweet">
                                    <input class="retweet-image" type="image" src="<?php echo $imgSrc; ?>" /><span style="<?php echo $imgColor; ?>"><?php echo $retweetCount['cnt']; ?></span>
                                </span>
                            <?php endif; ?>
                        </form>
                        <!-- RT機能ここまで -->

                        <span class="favorite">
                            <img class="favorite-image" src="images/heart-solid-gray.svg"><span style="color:gray;">34</span>
                        </span>

                        <a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
                        <?php
                        if ($post['reply_post_id'] > 0) :
                        ?><a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">
                                返信元のメッセージ</a>
                        <?php
                        endif;
                        ?>
                        <?php
                        if ($_SESSION['id'] == $post['member_id']) :
                        ?>
                            [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
                        <?php
                        endif;
                        ?>
                    </p>
                </div>
            <?php
            endforeach;
            ?>

            <ul class="paging">
                <?php
                if ($page > 1) {
                ?>
                    <li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
                <?php
                } else {
                ?>
                    <li>前のページへ</li>
                <?php
                }
                ?>
                <?php
                if ($page < $maxPage) {
                ?>
                    <li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
                <?php
                } else {
                ?>
                    <li>次のページへ</li>
                <?php
                }
                ?>
            </ul>
        </div>
    </div>
</body>

</html>