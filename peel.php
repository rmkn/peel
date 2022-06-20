<?php
// vim: set et ts=4 sw=4 sts=4:

/**
 * フレームワーク的なもの
 *
 * @version 2022-06-07.1
 */

// デバッグ用
//ini_set('display_errors', '1');
//ini_set('error_log', '');
//ini_set("date.timezone", "Asia/Tokyo");
//error_reporting(E_ALL | E_STRICT);

// デフォルトのエンコーディング
if (!defined('DEFAULT_ENCODING')) {
    define('DEFAULT_ENCODING', 'utf-8');
}

// include_pathに追加
$root = '.' . DIRECTORY_SEPARATOR;
$includePath = array(
    "{$root}lib",
    "{$root}action",
    "{$root}template",
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $includePath));

/**
 * ベースクラス
 */
abstract class Peel
{
    /** 出力フォーマット */
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';

    /** PATH_INFO用 */
    private $pathinf;
    /** BODYデータ用 */
    private $bodyData;
    /** 出力フォーマット */
    protected $format;

    /**
     * コンストラクタ
     *
     * @param string $format 出力フォーマット
     *
     * @return void
     */
    public function __construct($format = self::FORMAT_HTML)
    {
        // デフォルトフォーマットはHTML
        $this->format = $format === self::FORMAT_JSON
            ? self::FORMAT_JSON
            : self::FORMAT_HTML;
        // 初期化
        $this->bodyData  = null;
        // PATH_INFOの解析
        $pi = $this->getHeader('PATH_INFO');
        $this->pathinf = $pi !== null
            ? explode('/', str_replace(array('..', "\r", "\n"), '', $pi))
            : array();
    }

    /**
     * パラメータ取得
     *
     * @param string $paramName    パラメータ名
     * @param string $defaultValue デフォルト値
     * @param string $force        POST時にQUERY_STRINGから取得する場合はtrue
     *
     * @return string パラメータ値。存在しない場合はデフォルト値
     */
    protected function getParam($paramName, $defaultValue = null, $force = false)
    {
        // POSTで$forceがfalseの場合だけ$_POSTから取得
        if ($this->getHeader('REQUEST_METHOD') === 'POST' && $force == false) {
            return isset($_POST[$paramName]) ? $_POST[$paramName] : $defaultValue;
        }
        return isset($_GET[$paramName]) ? $_GET[$paramName] : $defaultValue;
    }

    /**
     * PATH_INFO取得
     *
     * @param int    $index   取得するインデックス
     * @param string $default デフォルト値
     *
     * @return string PATH_INFOの値。存在しないか空の場合はデフォルト値
     */
    protected function getPathinfo($index, $default = null)
    {
        return (isset($this->pathinf[$index]) && !empty($this->pathinf[$index]))
            ? $this->pathinf[$index]
            : $default;
    }

    /**
     * リクエストBODY取得
     *
     * @return string BODYデータ
     */
    protected function getBodyData()
    {
        // BODYデータ未読み込みの場合は読み込んで保持する
        if ($this->bodyData === null) {
            $this->bodyData = @file_get_contents('php://input');
            if ($this->bodyData === false) {
                $this->bodyData = null;
            }
        }
        return $this->bodyData;
    }

    /**
     * リクエストヘッダ取得
     *
     * @param string $headerKey    ヘッダ名
     * @param string $defaultValue デフォルト値
     * @param string $filter       フィルタ
     *
     * @return string ヘッダの値。存在しない場合やフィルタに一致しない場合はデフォルト値
     */
    protected function getHeader($headerName, $defaultValue = null, $filter = '/./')
    {
        return (isset($_SERVER[$headerName]) && preg_match($filter, $_SERVER[$headerName]))
            ? $_SERVER[$headerName]
            : $defaultValue;
    }

    /**
     * レスポンスヘッダー送信
     *
     * @param string  $headerString ヘッダー文字列
     * @param bool    $replace      類似ヘッダーを置き換えるか追加するか
     *
     * @return void
     */
    protected function sendHeader($headerString, $replace = true)
    {
        // 改行コードを除去して送信
        if (preg_match('!^status\s*:\s*(\d{3})!i', $headerString, $m)) {
            // statusヘッダーの場合はレスポンスコードをセットする
            header(str_replace(array("\r", "\n"), '', $headerString), $replace, $m[1]);
        } else {
            header(str_replace(array("\r", "\n"), '', $headerString), $replace);
        }
    }

    /**
     * Content-Type送信
     *
     * @return void
     */
    protected function sendContentType()
    {
        // 出力フォーマットに応じたContent-Typeを送信
        switch ($this->format) {
        case self::FORMAT_JSON:
            $this->sendHeader('Content-Type: application/json; charset=' . DEFAULT_ENCODING);
            break;
        case self::FORMAT_HTML:
            // breakなし
        default:
            $this->sendHeader('Content-Type: text/html; charset=' . DEFAULT_ENCODING);
        }
    }

    /**
     * エラー出力
     *
     * @param int    $errorCode    HTTPレスポンスコード
     * @param string $errorMessage エラーメッセージ
     * @param string $contents     レスポンスボディ部
     *
     * @return void
     */
    protected function dispError($errorCode, $errorMessage = 'ERROR', $contents = null)
    {
        // HTTPステータス
        $message = sprintf('%d %s', is_numeric($errorCode) ? $errorCode : 500, $errorMessage);
        $this->sendHeader("Status: {$message}", true);
        // Content-Type
        $this->sendContentType();
        // レスポンスボディ部。未指定の場合はデフォルトデータを出力
        switch ($this->format) {
        case self::FORMAT_JSON:
            if ($contents === null) {
                echo json_encode(array('status' => $errorCode, 'message' => $errorMessage));
            } else {
                echo (is_string($contents) && json_decode($contents) !== null)
                    ? $contents
                    : json_encode($contents);
            }
            break;
        case self::FORMAT_HTML:
            // breakなし
        default:
            echo $contents === null
                ? "<html><head><title>ERROR</title></head><body>{$message}</body></html>"
                : $contents;
        }
    }

    /**
     * リダイレクト
     *
     * @param string $url リダイレクト先
     *
     * @return void
     */
    protected function redirect($url)
    {
        // 相対パスは変換
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $scheme = $this->getHeader('HTTPS', null, '/^on$/') !== null ? 'https' : 'http';
            $host   = $this->getHeader('SERVER_NAME', '', '/^[a-z0-9.-]+$/');
            $pot    = $this->getHeader('SERVER_PORT', '80', '/^\d+$/') === '80' ? '' : ":{$p}";
            $url    = "{$scheme}://{$host}{$port}{$url}";
        }
        $this->sendHeader('Status: 302', true); // 303 See Other ? 307 Temporary Redirect ?
        $this->sendHeader("Location: " . filter_var($url, FILTER_SANITIZE_URL));
    }
}

/**
 * フロントコントローラー
 */
class PeelController extends Peel
{
    /** デフォルトアクションとメソッド */
    const DEFAULT_ACTION = 'default';
    const DEFAULT_METHOD = 'index';

    /**
     * 実行
     *
     * @param bool $methodOverride メソッドの上書きをするか
     *
     * @return bool 実行結果
     */
    public function execute($methodOverride = false)
    {
        // アクション名、メソッド名の取得
        $actionName = strtolower($this->getPathinfo(1, self::DEFAULT_ACTION));
        $methodName = strtolower($this->getPathinfo(2, self::DEFAULT_METHOD));
        // HTTPメソッドの取得
        $hm = $this->getHeader('REQUEST_METHOD', 'GET', '/^(GET|POST|PUT|DELETE)$/');
        // メソッドの上書き対応
        if ($methodOverride) {
            $om = $this->getHeader('HTTP_X_HTTP_METHOD_OVERRIDE', $this->getParam('_method'), '/^(PUT|DELETE|PATCH)$/i');
            $hm = preg_match('/^(PUT|DELETE|PATCH)$/i', $om) ? $om : $hm;
        }
        $httpMethod = strtolower($hm);

        // アクション名の組み立てと読み込み
        $actionClassName = ucfirst($actionName) . 'Action';
        $rc = @include_once "{$actionName}.php";
        // アクションが見つからない場合は400エラー
        if ($rc === false || !class_exists($actionClassName)) {
            $this->dispError(400, 'Bad Request');
            return false;
        }
        // 出力フォーマットを引き継ぐ
        $action = new $actionClassName($this->format);

        // メソッド名の組み立て
        $methods = array(                       // ex. GET /default/index HTTP/1.0
            $httpMethod . ucfirst($methodName), // getIndex()
            'exec' . ucfirst($methodName),      // execIndex()
            'exec' . ucfirst($httpMethod),      // execGet()
        );
        // メソッドが存在するか確認
        foreach ($methods as $method) {
            if (method_exists($action, $method)) {
                break;
            }
        }
        // メソッドが見つからない場合は404エラー
        if (!method_exists($action, $method)) {
            $this->dispError(404, "NotFound({$actionName}/{$methodName})");
            return false;
        }

        // prepareメソッドが存在する場合は実行
        $res = method_exists($action, 'prepare')
            ? $res = $action->prepare()
            : true;

        // 目的のメソッドを実行
        $res = $res && $action->$method();

        // finishメソッドが存在する場合は実行
        method_exists($action, 'finish') && $action->finish();

        // 結果を返す。finishメソッドの結果は無視
        return $res;
    }
}

/**
 * ビュー用のクラス
 */
class D
{
    /**
     * 特殊文字の変換
     *
     * @param string $s 文字列
     *
     * @return string 変換された文字列
     */
    public static function e($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, DEFAULT_ENCODING);
    }
}

/***
フレームワーク的なもの
======================

使い方
------

### フロントコントローラー

#### 生成
- 出力フォーマットを指定する(Peel::FORMAT_HTML | Peel::FORMAT_JSON)
  - アプリの場合
    > $fcon = new PeelController();

  - APIの場合
    > $fcon = new PeelController(Peel::FORMAT_JSON);

#### 実行
  - メソッド上書きなし
    > $fcon->execute();
  - メソッド上書きあり
    > $fcon->execute(true);
    - メソッドの上書きは _method パラメータか HTTP_X_HTTP_METHOD_OVERRIDE ヘッダ

### アクション

#### 設置場所
- actionディレクトリ

#### 実装
- Peelクラスを継承してSampleActionクラスを作成
- 次のいずれかが必要(GET /sample/index の場合)
  1. getIndex()
  2. execIndex()
  3. execGet()
- prepare()メソッドがあれば最初に呼び出される
- finish()メソッドがあれば最後に呼び出される

### ビュー

#### 設置場所
- templateディレクトリ(にinclude_pathが設定されているだけ)

#### 実装
- 素のPHPで作ってアクションからincludeとか

### ライブラリ

#### 設置場所
- libディレクトリ(にinclude_pathが設定されているだけ)
*/
