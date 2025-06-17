<?php

/////////////////////////////////////////////////
/*!
 * NextIP v6 | PHP/HTML/JS/CSS
 * Copyright 2022 ActiveTK. All rights reserved.
 * Modified for Ok-kun318
 */
/////////////////////////////////////////t////////

// 設定
$meurl = "https://daka.stars.ne.jp/pro";
$fqdn = "daka.stars.ne.jp";
$x_name = "hoge";
$image_path = "./index.png";
$starttitle = "NextIP v6 - daka.stars.ne.jp mirror";
$fake_title = 'NextIP v6 - daka.stars.ne.jp mirror';
$title = $fake_title;
$fake_favicon = 'https://daka.stars.ne.jp/pro/favicon.ico';
$activetk_minjs = "https://raw.githubusercontent.com/Ok-kun318/NEXTIPv6-modified/refs/heads/main/ActiveTK.min.js";
$decp = "フィルタリングの回避ができます。ブログやYouTubeの閲覧も可能です！スマホやiPad、ChromeBook、3DSなど機種を問わずご利用頂けます。";
$startua = "Mozilla/5.0 (Linux; AccessBot 6.0; PHP; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4183.121";


// クロスオリジン許可
header('Access-Control-Allow-Origin: *');

// nonce生成
$nonce = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 36);

// 指定されたヘッダー取得
function curl_headers($response)
{
    $headers = array();
    foreach (explode("\r\n", $response) as $i => $line) {
        if ($i === 0)
            $headers['HTTP'] = $line;
        else {
            $tmp = explode(': ', $line, 2);
            if (count($tmp) == 2) {
                list($key, $value) = $tmp;
                $headers[strtolower($key)] = $value;
            }
        }
    }
    return $headers;
}

//Converts relative URLs to absolute ones, given a base URL.
function rel2abs($rel, $base)
{
    if (empty($rel))
        $rel = ".";
    if (parse_url($rel, PHP_URL_SCHEME) != "" || strpos($rel, "//") === 0)
        return $rel; //Return if already an absolute URL
    if ($rel[0] == "#" || $rel[0] == "?")
        return $base . $rel; //Queries and anchors
    extract(parse_url($base)); //Parse base URL and convert to local variables: $scheme, $host, $path
    $path = isset($path) ? preg_replace('#/[^/]*$#', "", $path) : "/"; //Remove non-directory element from path
    if ($rel[0] == '/')
        $path = ""; //Destroy path if relative url points to root
    $port = isset($port) && $port != 80 ? ":" . $port : "";
    $auth = "";
    if (isset($user)) {
        $auth = $user;
        if (isset($pass)) {
            $auth .= ":" . $pass;
        }
        $auth .= "@";
    }
    $abs = "$auth$host$path$port/$rel"; //Dirty absolute URL
    for ($n = 1; $n > 0; $abs = preg_replace(array("#(/\.?/)#", "#/(?!\.\.)[^/]+/\.\./#"), "/", $abs, -1, $n)) {
    }
    return $scheme . "://" . $abs;
}

function proxifyCSS($css, $baseURL)
{
    $sourceLines = explode("\n", $css);
    $normalizedLines = [];
    foreach ($sourceLines as $line) {
        if (preg_match("/@import\s+url/i", $line)) {
            $normalizedLines[] = $line;
        } else {
            $normalizedLines[] = preg_replace_callback(
                "/(@import\s+)([^;\s]+)([\s;])/i",
                function ($matches) use ($baseURL) {
                    return $matches[1] . "url(" . $matches[2] . ")" . $matches[3];
                },
                $line
            );
        }
    }
    $normalizedCSS = implode("\n", $normalizedLines);
    return preg_replace_callback(
        "/url\((.*?)\)/i",
        function ($matches) use ($baseURL) {
            $url = $matches[1];
            global $meurl;
            if (strpos($url, "'") === 0) {
                $url = trim($url, "'");
            }
            if (strpos($url, "\"") === 0) {
                $url = trim($url, "\"");
            }
            if (stripos($url, "data:") === 0)
                return "url(" . $url . ")";
            return "url(" . $meurl . "?q=" . urlencode(base64_encode(rel2abs($url, $baseURL))) . ")";
        },
        $normalizedCSS
    );
}


if (isset($_POST["q"]) && $_POST["q"] != "" || isset($_GET["q"])) {

    if (isset($_POST["q"]))
        $url = $_POST["q"];
    else
        $url = base64_decode(urldecode($_GET["q"]));

    header("X-Robots-Tag: noindex,nofollow");

    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'bot') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Bot') !== false)) {
        header("HTTP/1.1 403 ForBidden");
        exit();
    }

    if (isset($_POST["ua"]))
        $ua = $_POST["ua"];
    if (empty($ua))
        $ua = $startua;

    if (isset($_POST["htt"]) && $_POST["htt"] == "http")
        $url = "http://" . $url;
    else if (isset($_POST["htt"]) && $_POST["htt"] == "https")
        $url = "https://" . $url;

    $url = trim($url);

    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('|^https?://.*$|', $url)) {
        header("HTTP/1.1 404 Not Found");
        die("不正な形式のURLです。<br>URLが正しいか(http[s]://から指定しているかなど)をご確認下さい。");
    }


    if (isset($_POST["js"]) && $_POST["js"] == "false" || strpos(strtoupper($url), 'CHIEBUKURO.YAHOO.CO.JP') !== false) {
        header("Content-Security-Policy: script-src 'nonce-{$nonce}' 'strict-dynamic'");
    }

    if (strpos(strtoupper($url), 'CHIEBUKURO.YAHOO.CO.JP') !== false)
        echo "<script nonce='" . $nonce . "'>window.onload=function(){document.getElementById('msthdtp').style='display:none;';}</script>";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);

    if (isset($_POST["meta"]) && $_POST["meta"] == "post")
        curl_setopt($curl, CURLOPT_POST, TRUE);
    else
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_REFERER, $meurl);

    if (isset($_POST["prk"]) && $_POST["prk"] == "prk-not-use") {
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, TRUE);
        curl_setopt($curl, CURLOPT_PROXYPORT, $prkp);
        curl_setopt($curl, CURLOPT_PROXY, 'https://' . $prks);
    }

    $USERNAME = "";
    $PASSWORD = "";
    curl_setopt($curl, CURLOPT_USERPWD, "$USERNAME:$PASSWORD");

    if (isset($_POST["outr"]) && $_POST["outr"] == "true") {
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    }

    $html = curl_exec($curl);
    $head = curl_getinfo($curl, CURLINFO_HEADER_OUT);
    $info = curl_getinfo($curl);

    if (isset($_POST["baseurl"]) && $_POST["baseurl"] == "last")
        $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

    if ($errno = curl_errno($curl)) {
        $error_message = curl_strerror($errno);
        header("HTTP/1.1 404 Not found");
        echo "cURL error ({$errno}):\n {$error_message}";
        curl_close($curl);
        exit;
    }

    $header = htmlspecialchars(substr($html, 0, $info["header_size"]));
    $html = substr($html, $info["header_size"]);
    curl_close($curl);
    global $fake_favicon;
    // タイトル置換
    $html = preg_replace('/<title>(.*?)<\/title>/is', '<title>' . htmlspecialchars($fake_title, ENT_QUOTES) . '</title>', $html, 1);
    $html = preg_replace(
        '/<link[^>]+rel="(shortcut )?icon"[^>]*>/i', // 既存のiconタグ削除
        '',
        $html
    );
    // head閉じタグの直前に挿入
    $html = preg_replace(
        '/<\/head>/i',
        '<link rel="icon" href="'. $fake_favicon .'">' . "\n" . '</head>',
        $html,
        1
    );
    if (isset($_GET["withcurl"])) {
        header("Content-Type: text/plain;charset=UTF-8");
        echo htmlspecialchars($header);
        echo htmlspecialchars($html);
        die();
    }

    if (isset($_POST["mode"]) && $_POST["mode"] == "text") {
        ?>
                                                              <html>
                                                                <head>
                                                                  <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                                                                  <meta charset='UTF-8'>
                                                                  <meta name='viewport' content='width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no'>
                                                                  <title>SourceCode of <?= htmlspecialchars($url) ?></title>
                                                                  <meta name='author' content='<?php echo $fqdn ?>'>
                                                                  <meta name='ROBOTS' content='noindex'>
                                                                </head>
                                                                <body style='background-color:#e6e6fa;color:#363636;overflow-x:hidden;overflow-y:visible;'>
                                                                  <p align='center' style='color: #00008b;'>SourceCode of <a href='<?= htmlspecialchars($url) ?>' target='_blank' rel='noopener noreferrer'><?= htmlspecialchars($url) ?></a></p>
                                                                  <pre><?= $header ?></pre><br>
                                                                  <pre>
                                                                  <?php
                                                                  echo htmlspecialchars($html);
                                                                  ?>
                                                                  </pre>
                                                                </body>
                                                              </html>
                                                              <?php
                                                              die();
    } else {

        $headerx = curl_headers($header);
        if (isset($headerx["content-type"]))
            header("Content-Type: " . $headerx["content-type"]);

        if (strpos(strtolower($headerx["content-type"]), 'text/css') !== false)
            exit(proxifyCSS($html, $url));
        if (strpos(strtolower($headerx["content-type"]), 'text/html') === false)
            exit($html);

        if (strpos($url, 'https://www.youtube.com/watch?v=') !== false && !isset($_POST["youtube"])) {
            $videocode = substr(strstr($url, 'watch?v='), 8);
            if (strpos($videocode, '&') !== false)
                $videocode = substr($videocode, 0, strcspn($videocode, '&'));
            ?>
                                                                                      <html>
                                                                                        <head>
                                                                                          <meta charset="UTF-8">
                                                                                          <title>プライバシー強化モードYouTube</title>
                                                                                          <!-- <script defer src="https://rinu.cf/pv/index.php?token=kaihi5cfuseyoutube&callback=console.log" nonce="<?= $nonce ?>"></script> -->
                                                                                        </head>
                                                                                        <body style="background-color:#6495ed;color:#080808;">
                                                                                          <div align="center">
                                                                                            <h1>プライバシー強化版YouTube - <?php echo $fqdn ?></h1>
                                                                                            <p>プライバシー強化版のYouTube「YouTube-NoCookie」を利用して動画を表示するページです。</p>
                                                                                            <br>
                                                                                            <iframe width="854" height="480" src="https://www.youtube-nocookie.com/embed/<?= $videocode ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                                                                          </div>
                                                                                          <br><br><br>
                                                                                          <hr size="1" color="#7fffd4">
                                                                                        </body>
                                                                                      </html>
                                                                                    <?php
                                                                                    exit();
        }

        header("X-Robots-Tag: noindex, nofollow");

        preg_match_all('/src="(.*?)"/i', $html, $match);
        echo "<!--\n";
        foreach ($match[0] as $match_url) {
            $matchurl_old = $match_url;
            $match_url = substr($match_url, 5);
            $match_url = substr($match_url, 0, -1);
            if (substr($match_url, 0, 1) != "#" && substr($match_url, 0, 5) != "data:" && substr($match_url, 0, 7) != "mailto:" && strpos($match_url, $fqdn) === false) {
                $html = @str_replace($matchurl_old, "src=\"" . $meurl . "?q=" . urlencode(base64_encode(rel2abs($match_url, $url))) . "\"", $html);
            }
        }
        preg_match_all('/href="(.*?)"/i', $html, $match2);
        foreach ($match2[0] as $match_url) {
            $matchurl_old = $match_url;
            $match_url = substr($match_url, 6);
            $match_url = substr($match_url, 0, -1);
            if (substr($match_url, 0, 1) != "#" && substr($match_url, 0, 5) != "data:" && substr($match_url, 0, 7) != "mailto:" && strpos($match_url, $fqdn) === false) {
                $html = @str_replace($matchurl_old, "href=\"" . $meurl . "?q=" . urlencode(base64_encode(rel2abs($match_url, $url))) . "\"", $html);
            }
        }
        echo "-->\n";
        ?>
                                                        <base href="<?= $url ?>">
                                                        <meta name="robots" content="noindex, nofollow">
                                                        <!-- Main -->
                                                        <?= $html ?>
                                                        <!-- /Main -->
                                                              <?php
                                                              exit();
    }
}

// $meurl = "https://daka.stars.ne.jp/";
// $title = "NextIP v6 - mirrored by daka.stars.ne.jp";

?>
<!DOCTYPE html>
<html lang="ja" itemscope="" itemtype="http://schema.org/WebPage" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo $title; ?></title>
    <base href="<?= $meurl ?>">
    <meta name="author" content="<?php echo $fqdn ?>">
    <meta name="robots" content="All">
    <meta name="description" content="<?php echo $decp; ?>">
    <meta name="copyright" content="Copyright &copy; 2025 <?php echo $fqdn ?>.">
    <meta name="thumbnail" content="<?php echo $image_path ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:creator" content="<?php echo $x_name ?>">
    <meta name="twitter:title" content="<?php echo $title; ?>">
    <meta name="twitter:description" content="<?php echo $decp; ?>">
    <meta name="twitter:image:src" content="<?php echo $image_path ?>">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $decp; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $meurl ?>">
    <meta property="og:site_name" content="<?php echo $title; ?>">
    <meta property="og:locale" content="ja_JP">
    <script src="<?php echo $activetk_minjs ?>"></script>
    <!-- <script src="https://daka.stars.ne.jp/jqery/jquery-3.7.1.min.js"></script> -->
    <!-- <script type="text/javascript" src="https://code.activetk.jp/ActiveTK.min.js"></script> -->
    <!-- <script defer src="https://rinu.cf/pv/index.php?token=kaihi5cfhome&callback=console.log"></script> -->
     <script>
function check() {
  const input = document.getElementById("urlform").value.trim();
  const select = document.getElementById("sor");

  if (input.startsWith("https://")) {
    select.selectedIndex = 0;
  } else {
    select.selectedIndex = 2;
  }
}
</script>
         <script type="text/javascript">onload=function(){$("#m").click(function(){let n=_("more").style;"none"==n.display?(n.display="block",_("si").innerHTML="&lt; 詳細設定を非表示にする"):(n.display="none",_("si").innerHTML="&gt; 詳細設定を表示")})};</script>
<style>a{color: #00ff00;position: relative;display: inline-block;transition: .3s;}a::after {position: absolute;bottom: 0;left: 50%;content: '';width: 0;height: 2px;background-color: #31aae2;transition: .3s;transform: translateX(-50%);}a:hover::after{width: 100%;}</style>
  </head>
  <body style="background-color:#e6e6fa;text:#363636;">
    <br>
    <div align='center'>
      <h1>NextIP v6 - <?php echo $fqdn ?> modified by Ok-kun318 </h1><br>
      <p>NextIPは、Web上で「curl」を実行することによりフィルタリング回避ができるツールです。<br>YouTubeやTwitter、Yahoo知恵袋などの閲覧も可能です。</p>
      <form action='' method='POST'>
        <p>https://またはhttp://で始まらない場合は自動的に選択されます</p>
        <select name="htt" id="sor" style="height:24px;">
          <option value="none">(None)</option>
          <option value="http">http://</option>
          <option value="https">https://</option>
        </select>
        <input  oninput="check()" id="urlform" type='text' name='q' placeholder='ここにURLを入力してください' style="height:20px;width:500px;"><br><br>
        <input type='submit' value='アクセス' style="height:60px;width:140px;">
        <br><br>
          プレビュー形式 : <select name="mode">
            <option value="html">HTML</option>
            <option value="text">テキスト</option>
          </select><br>
          <div id="m">
            <p style="cursor:pointer;color:#4169e1;" id="si">&gt; 詳細設定を表示</p>
          </div>
          <div id="more" style="display:none;clear:both;">
          JavaScript : <select name="js">
            <option value="true">有効</option>
            <option value="false">無効</option>
          </select>
          <br>
          自動リダイレクト : <select name="outr">
            <option value="true">する</option>
            <option value="false">しない</option>
          </select><br>
          解析のベースURL : <select name="baseurl">
            <option value="last">最終URL</option>
            <option value="mine">指定URL</option>
          </select><br>
          メゾット : <select name="meta">
            <option value="get">GET</option>
            <option value="post">POST</option>
          </select><br>
          UserAgent : <input type='text' name='ua' size='20' placeholder='ユーザーエージェント' value=''><br>
          BASIC認証 : <input type='text' name='user' size="8" placeholder='ユーザー名'>
          <input type='password' name='pass' size="8" placeholder='パスワード'><br>
          プロキシ : <select name="prk">
            <option>使用しない</option>
            <option value="prk-not-use">使用する</option>
          </select><br>
          <input type='text' name='prk-server' size="8" placeholder='サーバー'>
          <input type='text' name='prk-port' size="8" placeholder='ポート'>
          <br><br>
          ※この回避サイト自体が検閲/規制されている場合、<br>
          <a href="https://<?php echo $fqdn ?>/contact" target="_blank">お問い合わせ</a>からご連絡下さい。
          </div><br>
      </form>
      <br>
    </div>
  </body>
</html>
