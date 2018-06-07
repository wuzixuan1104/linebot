<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OC Wu <cherry51120@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

use LINE\LINEBot;
use LINE\LINEBot\Constant;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\DatetimePickerTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;

use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\MessageEvent\VideoMessage;
use LINE\LINEBot\Event\MessageEvent\StickerMessage;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use LINE\LINEBot\Event\MessageEvent\ImageMessage;
use LINE\LINEBot\Event\MessageEvent\AudioMessage;
use LINE\LINEBot\Event\MessageEvent\FileMessage;

use LINE\LINEBot\Event\JoinEvent;
use LINE\LINEBot\Event\LeaveEvent;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\Event\PostbackEvent;

class MyLineBot extends LINEBot{
  static $bot;

  public function __construct ($client, $option) {
    parent::__construct ($client, $option);
  }
  public static function create() {
    return new LINEBot( new CurlHTTPClient(config('line', 'channelToken')), ['channelSecret' => config('line', 'channelSecret')]);
  }
  public static function bot() {
    if (self::$bot)
      return self::$bot;

    return self::$bot = self::create ();
  }
  public static function events() {
    if( !isset ($_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE]) )
      return false;

    try {
      Log::info( file_get_contents ("php://input") );
      return MyLineBot::bot()->parseEventRequest (file_get_contents ("php://input"), $_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE]);
    } catch (Exception $e) {
      return $e;
    }
  }
}

class MyLineBotLog {
  private $param = null, $source = null, $speaker = null, $event = null;

  public function __construct($source, $speaker, $event) {
    $this->source = $source;
    $this->speaker = $speaker;
    $this->event = $event;
  }
  public static function init($source, $speaker, $event) {
    return new MyLineBotLog($source, $speaker, $event);
  }

  public function create() {
    if( $this->event->getType() == 'message' )
      $this->getParam() == null && $this->setParam();

    $split = explode("\\", get_class($this->event));
    $type = lcfirst( $split[count($split)-1] );

    Log::info($type);
    if( method_exists( __CLASS__, $type ) )
      return $this->{$type}($this->event);
    Log::info('true==========');
    return true;
  }

  private function setParam() {
    $this->param = array(
      'source_id' => $this->source->id,
      'speaker_id' => $this->speaker->id,
      'reply_token' => $this->event->getReplyToken() ? $this->event->getReplyToken() : '',
      'message_id' => $this->event->getMessageId() ? $this->event->getMessageId() : '',
      'timestamp' => $this->event->getTimestamp() ? $this->event->getTimestamp() : '',
    );
  }
  private function getParam() {
    return $this->param;
  }

  private function textMessage() {
    $param = array_merge( $this->getParam(), array('text' => $this->event->getText()) );
    if( !Text::transaction( function() use ($param, &$obj) { return $obj = Text::create($param); }) )
      return false;
    return $obj;

  }

  private function imageMessage() {
    if ( !$obj = MyLineBot::bot()->getMessageContent( $this->event->getMessageId() ) )
      return false;
    if ( !$obj->isSucceeded() )
      return false;

    $param = array_merge( $this->getParam(), array('file' => '') );
    $filename = FCPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid( rand() . '_' ) . get_extension_by_mime( $obj->getHeader('Content-Type') );

    if ( !(write_file( $filename, $obj->getRawBody()) && $image = Image::create($param) ) )
      return false;
    if( !$image->file->put($filename) )
      return false;

    return $image;
  }

  private function videoMessage() {
    if ( !$obj = MyLineBot::bot()->getMessageContent( $this->event->getMessageId() ) )
      return false;
    if ( !$obj->isSucceeded() )
      return false;

    $param = array_merge( $this->getParam(), array('file' => '') );
    $filename = FCPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid( rand() . '_' ) . get_extension_by_mime( $obj->getHeader('Content-Type') );

    if ( !(write_file( $filename, $obj->getRawBody()) && $video = Video::create($param) ) )
      return false;
    if( !$video->file->put($filename) )
      return false;
    return $video;
  }

  private function audioMessage() {
    if ( !$obj = MyLineBot::bot()->getMessageContent( $this->event->getMessageId() ) )
      return false;
    if ( !$obj->isSucceeded() )
      return false;

    $param = array_merge( $this->getParam(), array('file' => '') );
    $filename = FCPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid( rand() . '_' ) . get_extension_by_mime( $obj->getHeader('Content-Type') );

    if ( !(write_file( $filename, $obj->getRawBody()) && $audio = Audio::create($param) ) )
      return false;

    if( !$audio->file->put($filename) )
      return false;
    return $audio;
  }

  private function fileMessage() {
    $param = array_merge( $this->getParam(), array('text' => $this->event->getText()) );
    return Text::transaction( function() use ($param) {
      return Text::create($param);
    });
  }

  private function locationMessage() {
    $param = array_merge( $this->getParam(), array( 'title' =>  $this->event->getTitle(), 'address' =>  $this->event->getAddress(), 'latitude' =>  $this->event->getLatitude(), 'longitude' =>  $this->event->getLongitude(), ));
    if( !Location::transaction(function ($param, &$obj) { return $obj = Location::create($param);}, $param, $obj) ) {
      return false;
    }
    return $obj;
  }

  private function stickerMessage() {

  }

  private function followEvent() {
    $param = array( 'source_id' => $this->source->id, 'reply_token' => $this->event->getReplyToken() ? $this->event->getReplyToken() : '', 'timestamp' => $this->event->getTimestamp() ? $this->event->getTimestamp() : '');
    if( !Follow::transaction( function($param, &$obj) { return $obj = Follow::create($param); }, $param, $obj ) )
      return false;
    return $obj;
  }

  private function unfollowEvent() {
    $param = array( 'source_id' => $this->source->id, 'timestamp' => $this->event->getTimestamp() );
    if( !Unfollow::transaction( function($param, &$obj) { return $obj = Unfollow::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }

  private function joinEvent() {
    $param = array( 'source_id' => $this->source->id, 'reply_token' => $this->event->getReplyToken(), 'timestamp' => $this->event->getTimestamp() );
    if( !Join::transaction( function($param, &$obj) { return $obj = Join::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }

  private function leaveEvent() {
    $param = array( 'source_id' => $this->source->id, 'timestamp' => $this->event->getTimestamp() );
    if( !Leave::transaction( function($param, &$obj) { return $obj = Leave::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }

  private function postbackEvent() {
    $param = array( 'source_id' => $this->source->id, 'speaker_id' => $this->speaker->id, 'reply_token' => $this->event->getReplyToken(), 'data' => $this->event->getPostbackData(), 'params' => $this->event->getPostbackParams() ? json_encode($this->event->getPostbackParams()):'', 'timestamp' => $this->event->getTimestamp());
    if( !Postback::transaction( function($param, &$obj) { return $obj = Postback::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }
}

class MyLineBotMsg {
  private $builder;

  public function __construct() {
  }
  public static function create() {
    return new MyLineBotMsg();
  }
  public function reply ($token) {
    if ($this->builder)
      MyLineBot::bot()->replyMessage($token, $this->builder);
  }
  public function getBuilder() {
    return $this->builder;
  }
  public function text($text) {
    $this->builder = !is_null($text) ? new TextMessageBuilder($text) : null;
    return $this;
  }
  public function image($url1, $url2) {
    $this->builder = is_string ($url1) && is_string ($url2) ? new ImageMessageBuilder($url1, $url2) : null;
    return $this;
  }
  public function sticker($packId, $id) {
    $this->builder = is_numeric($packId) && is_numeric($id) ? new StickerMessageBuilder($packId, $id) : null;
    return $this;
  }
  public function video($ori, $prev) {
    $this->builder = isHttps($ori) && isHttps($prev) ? new VideoMessageBuilder($ori, $prev) : null;
    return $this;
  }
  public function audio($ori, $d) {
    $this->builder = isHttps($ori) && is_numeric($d) ? new AudioMessageBuilder($ori, $d) : null;
    return $this;
  }
  public function location($title, $add, $lat, $lon) {
    $this->builder = is_string($title) && is_string($add) && is_numeric($lat) && is_numeric($lon) ? new LocationMessageBuilder($title, $add, $lat, $lon) : null;
    return $this;
  }
  public function imagemap($url, $altText, $weight, $height, array $actionBuilders) {
    $this->builder = isHttps($url) && is_string($altText) && is_numeric($weight) && is_numeric($height) && is_array($actionBuilders) ? new ImagemapMessageBuilder($url, $altText, new BaseSizeBuilder($height, $weight), $actionBuilders) : null;
    return $this;
  }
  public function multi($builds) {
    if (!is_array ($builds))
      $this->builder = null;

    $this->builder = new MultiMessageBuilder();
    foreach ($builds as $build) {
      $this->builder->add ($build->getBuilder ());
    }
    return $this;
  }
  public function templateConfirm($text, array $actionBuilders) {
    $this->builder = is_string($text) && is_array($actionBuilders) ? new ConfirmTemplateBuilder($text, $actionBuilders) : null;
    return $this;
  }
  public function templateImageCarousel(array $columnBuilders) {
    $this->builder = is_array($columnBuilders) ? new ImageCarouselTemplateBuilder($columnBuilders) : null;
    return $this;
  }
  public function templateImageCarouselColumn($imageUrl, $actionBuilder) {
    return is_string($imageUrl) && is_object($actionBuilder) ? new ImageCarouselColumnTemplateBuilder($imageUrl, $actionBuilder) : null;
  }
  public function templateButton($title, $text, $imageUrl, array $actionBuilders) {
    $this->builder = is_string($title) && is_string($text) && is_string($imageUrl) && is_array($actionBuilders) ? new ButtonTemplateBuilder($title, $text, $imageUrl, $actionBuilders) : null;
    return $this;
  }
  public function templateCarouselColumn($title, $text, $imageUrl, array $actionBuilders) {
    return is_string($title) && is_string($text) && is_string($imageUrl) && is_array($actionBuilders) ? new CarouselColumnTemplateBuilder($title, $text, $imageUrl, $actionBuilders) : null;
  }
  public function templateCarousel(array $columnBuilders) {
    $this->builder = is_array($columnBuilders) ? new CarouselTemplateBuilder($columnBuilders) : null;
    return $this;
  }
  public function template($text, $builder) {
    if( !is_string($text) || empty($builder) )
      return $this;

    $this->builder = new TemplateMessageBuilder($text, $builder->getBuilder());
    return $this;
  }
}

class MyLineBotActionMsg {
  private $action;
  public function __construct() {
  }
  public static function create() {
    return new MyLineBotActionMsg();
  }
  public function datetimePicker($label, $data, $mode, $initial = null, $max = null, $min = null) {
    return is_string($label) && is_string($data) && in_array($mode, ['date', 'time', 'datetime']) ? new DatetimePickerTemplateActionBuilder($label, $data, $mode, $initial, $max, $min) : null;
  }
  public function message($label, $text) {
    return is_string($label) && is_string($text) ? new MessageTemplateActionBuilder($label, $text) : null;
  }
  public function uri($label, $url) {
    return is_string($label) && (isHttp($url) || isHttps($url)) ? new UriTemplateActionBuilder($label, $url) : null;
  }
  public function postback($label, $data, $text = '') {
    Log::info($label . '/' . $data . '/' . $text);
    return new PostbackTemplateActionBuilder($label, $data);
    return is_string($label) && ($data = is_array($data) ? json_encode($data) : $data ) && is_string($text) ? new PostbackTemplateActionBuilder($label, $data, $text) : null;
  }

  public function imagemapMsg($text, $x, $y, $width, $height) {
    return is_string($text) && is_numeric($x) && is_numeric($y) && is_numeric($width) && is_numeric($height) ? new ImagemapMessageActionBuilder($text, new AreaBuilder($x, $y, $width, $height) ) : null;
  }
  public function imagemapUri($url, $x, $y, $width, $height) {
    return is_string($url) && is_numeric($x) && is_numeric($y) && is_numeric($width) && is_numeric($height) ? new ImagemapUriActionBuilder($url, new AreaBuilder($x, $y, $width, $height) ) : null;
  }
}

class MyLineBotRichMenu {

  public function getRichMenu() {

  }
  public function createRichMenu() {

  }
  public function deleteRichMenu() {

  }
  public function getRichMenuIdOfUser() {

  }
  public function linkRichMenuToUser() {

  }
  public function linkRichMenuFromUser() {

  }
  public function downloadRichMenuImage() {

  }
  public function uploadRichMenuImage() {

  }
  public function getRichMenuList() {

  }
}
