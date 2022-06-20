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
