<?php
// vim: set et ts=4 sw=4 sts=4:

ini_set('display_errors', '1');

/**
 * APIのドキュメントを表示する
 */


class DocAction extends Peel
{
    private $theme;
    const DEFAULT_THEME = 'Simplex';

    public function prepare()
    {
        // theme: Amelia Cerulean Cyborg Journal Readable Simplex Slate Spacelab Spruce Superhero United
        $this->theme = $this->getParam('theme', self::DEFAULT_THEME);
        return true;
    }

    /**
     * APIリスト表示
     *
     * @return boolean 処理結果
     */
    public function getList()
    {
        $md   = array('# APIリスト');
        $apis = $this->getActionList();
        $md[] = "|API名|説明|\n|:---|:---|";
        foreach ($apis as $api) {
            $buf     = $this->parse($api);
            $comment = isset($buf['comment'])
                     ? array_shift(explode("\n", $buf['comment'])) // APIのコメントの先頭行のみ
                     : '';
            $md[]    = sprintf('|[%s](desc?api=%s)|%s|', $api, $api, $comment);
        }
        $this->output(implode("\n", $md));
        return true;
    }

    /**
     * API詳細表示
     *
     * @return boolean 処理結果
     */
    public function getDesc()
    {
        $api = $this->getParam('api', null); // API名
        $md  = array("# {$api}");
        if ($api !== null) {
            $buf  = $this->parse($api);
            // APIのコメント
            $md[] = preg_replace("!\n!", "  \n", isset($buf['comment']) ? $buf['comment'] : '');
            // コマンド表示
            $commands = isset($buf['commands']) ? $buf['commands'] : null;
            foreach ($commands as $cmd) {
                // コメントの先頭行をタイトルにする
                $buf   = explode("\n", $cmd['comment']);
                $title = array_shift($buf);
                if (!empty($title)) {
                    $md[] = "\n- - -\n## {$title}";
                    $md[] = implode("  \n", $buf) . "\n";
                }
                // HTTPメソッドとパス
                $md[] = sprintf("```\n%s %s%s\n```",
                            $cmd['method'],
                            preg_replace('!/[^/]+$!', '/', $_SERVER['SCRIPT_URL']),
                            $cmd['name']
                        );
                // パラメータ
                if (!empty($cmd['param'])) {
                    $md[] = "\n|パラメータ|説明|\n|:---|:---|";
                    foreach ($cmd['param'] as $param) {
                        $md[] = isset($param['name'])
                              ? sprintf('|%s:%s|%s|', $param['source'], $param['name'], $param['comment']) // パラメータ
                              : sprintf('|%s|%s|', $param['source'], $param['comment']);  // PATHINFO
                    }
                }
                // 追加情報
                if (!empty($cmd['info'])) {
                    $md[] = "\n{$cmd['info']}";
                }
            }

            $this->output(implode("\n", $md));
        }
        return true;
    }

    /**
     * action(API)リストの取得
     *
     * @return array actionリスト
     */
    private function getActionList()
    {
        $res = array();
        foreach (glob(__DIR__ . '/*.php') as $f) {
            $res[] = basename($f, '.php');
        }
        return $res;
    }

    /**
     * ファイル名の組み立て
     *
     * @param string $api API名
     *
     * @return string ファイル名
     */
    private function getFilename($api)
    {
        return __DIR__ . "/{$api}.php";
    }

    /**
     * ソースの解析
     *
     * @param string $api API名
     *
     * @return array 解析結果
     */
    private function parse($api)
    {
        $res = array();
        $tmp = array();
        $fp = fopen($this->getFilename($api) , 'r');
        if ($fp !== false) {
            $blockType = null;
            $block     = null;
            $buf       = null;
            $braceCnt  = 0;
            while (($line = fgets($fp, 1024)) !== false) {
                $l = rtrim($line, "\r\n");
                switch ($blockType) {
                case 'comment_data':
                    if (preg_match('!\s+\*/|\s+\*\s*$!', $l)) {
                        // コメント終端か空行で終了
                        $tmp[]     = $block;
                        $blockType = null;
                    } else {
                        // 先頭の*を除去
                        $block['data'][] = preg_replace('!^\s+\*\s?!', '', $l);
                    }
                    break;
                case 'function_data':
                    // {}の数をカウント
                    $braceCnt += preg_match_all('/\{/', $l);
                    $braceCnt -= preg_match_all('/\}/', $l);
                    if ($braceCnt == 0) {
                        // {}が全て閉じたので終了
                        $tmp[]     = $block;
                        $blockType = null;
                    } elseif (preg_match('!get(?<source>Param|Pathinfo)\([\'"]?(?<name>[^\'",]+)(?:.*//(?<comment>.*))?!', $l, $m)) {
                        // パラメータの取得部分
                        $block['param'][] = array('source' => $m['source'], 'name' => $m['name'], 'comment' => $m['comment']);
                    } elseif (preg_match('!get(?<source>BodyData)(?:.*//(?<comment>.*))?!', $l, $m)) {
                        // データの取得部分
                        $block['param'][] = array('source' => $m['source'], 'comment' => $m['comment']);
                    } elseif ($buf === null && preg_match('!/\*\*\*!', $l)) {
                        // /*** は補足情報開始行
                        $buf = array();
                        continue;
                    } elseif ($buf !== null && preg_match('!\*\*\*/!', $l)) {
                        // ***/ は補足情報終了行
                        $block['info'] = preg_replace('!^\s*\*\s!', '', $buf);
                        $buf           = null;
                    } elseif ($buf !== null) {
                        $buf[] = $l;
                    }
                    break;
                default:
                    if (
                        preg_match('!public\s+function\s+exec(?<http_method>Get|Post|Put|Delete)!', $l, $m)
                        || preg_match('!public\s+function\s+exec(?<command>[A-Z][^(]+)!', $l, $m)
                        || preg_match('!public\s+function\s+(?<http_method>get|post|put|delete)(?<command>[A-Z][^(]+)!', $l, $m)
                    ) {
                        // publicのメソッドはコマンド
                        $tmp[] = array(
                            'type'        => 'function',
                            'http_method' => strtoupper(isset($m['http_method']) ? $m['http_method'] : '[any]'),
                            'command'     => strtolower(isset($m['command']) ? $m['command'] : '[any]'),
                        );
                        $blockType = 'function_data';
                        $block     = array('type' => $blockType);
                        $buf       = null;
                        $braceCnt  = preg_match_all('/\{/', $l);
                        continue;
                    } elseif (preg_match('!(?<indent>^|\s+)/\*\*!', $l, $m)) {
                        // インデントがないコメントはAPIの説明、インデントがあるコメントはコマンドの説明
                        $blockType = 'comment_data';
                        $block     = array('type' => strlen($m['indent']) == 0 ? 'global_comment' : 'function_comment');
                        continue;
                    }
                }
            }
            fclose($fp);
        }
        // コマンドごとにまとめる
        for ($i = 0; $i < count($tmp); $i++) {
            if ($tmp[$i]['type'] === 'global_comment') {
                // APIの説明
                $res['comment'] = implode("\n", $tmp[$i]['data']);
            } elseif ($tmp[$i]['type'] === 'function') {
                // コマンド定義の前後にコメントとパラメータがあればまとめる
                $res['commands'][] = array(
                    'method'  => $tmp[$i]['http_method'],
                    'name'    => $tmp[$i]['command'],
                    'comment' => (isset($tmp[$i - 1]['type']) && $tmp[$i - 1]['type'] === 'function_comment')
                               ? implode("\n", $tmp[$i - 1]['data'])
                               : null,
                    'param'   => (isset($tmp[$i + 1]['param']))
                               ? $tmp[$i + 1]['param']
                               : null,
                    'info'    => (isset($tmp[$i + 1]['info']))
                               ? implode("\n", $tmp[$i + 1]['info'])
                               : null,
                );
            }
        }
        return $res;
    }

    /**
     * 画面出力
     *
     * @param string $md マークダウン
     *
     * @return void
     */
    private function output($md)
    {
        $title = 'no title';
        foreach (explode("\n", $md) as $l) {
            if (preg_match('!^# (?<title>.+)!', $l, $m)) {
                $title = $m['title'];
                break;
            }
        }
        echo <<< EOS
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <title>{$title}</title>
</head>
<body>
  <xmp theme="{$this->theme}" style="display:none;">
{$md}
  </xmp>
  <script src="//strapdownjs.com/v/0.2/strapdown.js"></script>
</body>
</html>
EOS;
    }
}
