define("CHANNEL_ACCESS_TOKEN", 'C5MWbvnJoAP+ONCkCtHUfvyacShTV/dL4CNOKabljA9S/y94RX3eAH3wmh31BNJYVfmzZEUPY668FTfK902Av+eLKYWFH/UNRuE+CNt7u6TLR73L2VjvQcw+TT5SVOVw7Fipd/mBQt/OpPYCtoRm7wdB04t89/1O/w1cDnyilFU=');
define("CHANNEL_SECRET", 'ad3738517d21146f3127ab0e881a3518');

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
    $pdo = new PDO('mysql:host=ec2-23-21-162-90.compute-1.amazonaws.com;dbname=d7ome7hkib5ijv;charset=utf8','hedpilyonjeflm','fef830234663ab616bac89a7a8e61a14db8adf1afa235427051067c0e049765f',
    array(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC));
    $stmt = $pdo -> prepare("INSERT INTO talks (id, type, group_id, user_id, user_display_name, user_picture_url, user_status_message, talk_type, upload_image_name, time, reply_token, message_id, message_type, message_text, created_at) VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");
    $stmt->execute(array($event->type, $event->source->groupId, $event->source->userId, $user_display_name, $user_picture_url, $user_status_message, $event->source->type, @$fileUrl, $event->timestamp, $event->replyToken, $event->message->id, $event->message->type, $event->message->text));
  }
  catch (PDOException $e) {
    exit('データベース接続失敗。'.$e->getMessage());
  }
}