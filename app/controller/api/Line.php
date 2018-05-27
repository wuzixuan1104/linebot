<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Line extends ApiController {
  public function __construct() {
    parent::__construct();
  }

  public function index() {
    Load::lib ('MyLineBot.php');
    Load::sysFunc('file.php');

    $events = MyLineBot::events();
    foreach( $events as $event ) {

      if( !$source = Source::checkSourceExist($event) )
        continue;

      $speaker = Source::checkSpeakerExist($event);

      if (!$log = MyLineBotLog::init($source, $speaker, $event)->create())
        return false;

      switch( $event->getMessageType() ) {
        case 'text':
          MyLineBotMsg::create()
            ->text($event->getText())
            ->reply($event->getReplyToken());
          break;
        case 'image':
          Log::info('===========123242');
          $url = $log->file->url();
          Log::info($url);
          MyLineBotMsg::create()
            ->image($url, $url)
            ->reply ($event->getReplyToken());
          break;
      }
    }
  }


}
