define("CHANNEL_ACCESS_TOKEN", 'UTe5QHwKdoRk8lSlGv7a61Cs1ggivK36/Tym3q1ihF6RvMj1ZF6lwj+zvcGTPJ2fNFMio1ncib40XCEMB+uzbxuzFSIyCgg2QIpCsvcafyjDMTJ5CDw1Bnqaw479Kngqj9i5+Bhpr7dCbAcXAn6OHwdB04t89/1O/w1cDnyilFU=');
define("CHANNEL_SECRET", 'fa344f1cc108f2808b4e4d3dbbb586a1');

$contents = file_get_contents('php://input');
$json = json_decode($contents);
$event = $json->events[0];

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(CHANNEL_ACCESS_TOKEN);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => CHANNEL_SECRET]);

// 個人トークの場合はグループに招待するようにメッセージ表示
if ($event->source->type != 'group') {
  $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('グループに招待してください！');
}

else {
  try {
    // メッセージを書き込んだユーザ情報を取得
    $response = $bot->getProfile($event->source->userId);

    if ($response->isSucceeded()) {
      $profile = $response->getJSONDecodedBody();
      $user_display_name = $profile['displayName'];
      $user_picture_url = $profile['pictureUrl'];
      $user_status_message = $profile['statusMessage'];
      $fileUrl = "";

      // メッセージタイプが画像だった場合は画像を保存
      if ($event->message->type == 'image') {
        function uploadImageThenGetUrl ($rawBody) {
          $im = imagecreatefromstring($rawBody);

          if ($im !== false) {
            $filename = date("Ymd-His") . '-' . mt_rand() . '.jpg';
            imagejpeg($im, "images/uploads/" . $filename);
          }

          else {
            error_log("fail to create image.");
          }
        
          return $filename;
        }

        $res = $bot->getMessageContent($event->message->id);
        $fileUrl = uploadImageThenGetUrl($res->getRawBody());
      }
    }

    // ユーザ情報やメッセージをデータベースに保存
    $pdo = new PDO('mysql:host=ec2-184-73-199-189.compute-1.amazonaws.com:5432;dbname=dfcg6mcup740f2;charset=utf8','hegovrdrjdmwzc','4154d0ac42b75b14bb7ea145615e6f272fbf2cdedd80752d2c40f2ee1e04adf7',
    array(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC));
    $stmt = $pdo -> prepare("INSERT INTO talks (id, type, group_id, user_id, user_display_name, user_picture_url, user_status_message, talk_type, upload_image_name, time, reply_token, message_id, message_type, message_text, created_at) VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");
    $stmt->execute(array($event->type, $event->source->groupId, $event->source->userId, $user_display_name, $user_picture_url, $user_status_message, $event->source->type, @$fileUrl, $event->timestamp, $event->replyToken, $event->message->id, $event->message->type, $event->message->text));
  }
  catch (PDOException $e) {
    exit('データベース接続失敗。'.$e->getMessage());
  }
}
