<?php
// vim: set et ts=4 sw=4 sts=4:

/**
 * APIのサンプル
 * 説明
 */


class SampleAction extends Peel
{
    public function prepare()
    {
        return true;
    }

    /**
     * GETのデフォルト
     */
    public function execGet()
    {
    }

    /**
     * POSTのデフォルト
     */
    public function execPost()
    {
    }

    /**
     * PUTのデフォルト
     */
    public function execPut()
    {
        $data = $this->getBodyData(); // リクエストBODYのデータ
    }

    /**
     * DELETEのデフォルト
     */
    public function execDelete()
    {
    }

    /**
     * commandのデフォルト
     */
    public function execCommand()
    {
        /***
         * ### 補足
         * 
         * |aaa|bbb|
         * |---|---|
         * |aaa|bbb|
         ***/
    }

    /**
     * command1
     * せつめい
     * 説明
     *
     * @return void
     */
    public function getCommand()
    {
        $data1 = $this->getParam('p1', null); // p1パラメータ
        $data2 = $this->getPathinfo(3, null); // PATHINFOから取得
/***
### 補足

|aaa|bbb|
|---|---|
|aaa|bbb|
***/
    }
}
