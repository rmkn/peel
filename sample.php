<?php
// vim: set et ts=4 sw=4 sts=4:

/**
 * APIのサンプル
 *
 * ドキュメント出力のサンプル用のAPI
 *
 * @author rmkn
 */


class SampleAction extends Peel
{
    private $method;

    /**
     * 前処理
     *
     * @return boolean
     */
    public function prepare()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        return true;
    }

    /**
     * GETのデフォルト
     *
     * 定義されていないコマンドがGETで呼ばれた際に実行される
     *
     * @return boolean 実行結果
     */
    public function execGet()
    {
        echo __FUNCTION__;
        $param = $this->getParam('p1', null); // パラメータ
        var_dump($this->method, $param);
        return true;
    }

    /**
     * POSTのデフォルト
     *
     * 定義されていないコマンドがPOSTで呼ばれた際に実行される
     *
     * @return boolean 実行結果
     */
    public function execPost()
    {
        echo __FUNCTION__;
        $param = $this->getParam('p1', 'abc'); // パラメータ
        var_dump($this->method, $param);
        return true;
    }

    /**
     * PUTのデフォルト
     *
     * 定義されていないコマンドがPUTで呼ばれた際に実行される
     *
     * @return boolean 実行結果
     */
    public function execPut()
    {
        echo __FUNCTION__;
        $data = $this->getBodyData(); // リクエストBODYのデータ
        var_dump($this->method, $data);
        return true;
    }

    /**
     * DELETEのデフォルト
     *
     * 定義されていないコマンドがDELETEで呼ばれた際に実行される
     *
     * @return boolean 実行結果
     */
    public function execDelete()
    {
        echo __FUNCTION__;
        $param = $this->getParam('p1'); // パラメータ
        var_dump($this->method, $param);
        return true;
    }

    /**
     * commandコマンド
     *
     * コマンドがcommandの場合にメソッドに関係なく実行される
     */
    public function execCommand()
    {
        echo __FUNCTION__;
        $param  = $this->getParam('p1'); // パラメータ
        var_dump($param);
        return true;
        /***
         * #### リクエストサンプル
         * 
         * ```
         * http://server/sample/command?p1=123
         * ```
         ***/
    }

    /**
     * command1コマンド
     * 
     * コマンドがcommmand1かつGETで呼ばれた際に実行される
     *
     * @return void
     */
    public function getCommand1()
    {
        echo __FUNCTION__;
        $param  = $this->getPathinfo(3); // パラメータ
        var_dump($param);
        return true;
/***
#### リクエストサンプル

```
http://server/sample/command/param
```

#### 補足説明

コマンド説明の最後に追加される
***/
    }
}

/***
## 更新履歴
2019-04-XX 初版

## その他
TODOとか
メモなど
***/
