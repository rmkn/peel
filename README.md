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

####実行
  > $fcon->execute();

### コントローラー

#### 設置場所
- controllerディレクトリ

#### 実装
- Peelクラスを継承
- 次のいずれかが必要(GET /controller/method/index の場合)
  1. getIndex()
  2. execIndex()
  3. execGet()
- prepare()メソッドがあれば最初に呼び出される
- finish()メソッドがあれば最後に呼び出される

### ビュー

#### 設置場所
- templateディレクトリ(にinclude_pathが設定されているだけ)

#### 実装
- 素のPHPで作ってコントローラーからincludeとか

### ライブラリ

#### 設置場所
- libディレクトリ(にinclude_pathが設定されているだけ)

