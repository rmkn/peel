#!/bin/sh

mkdir ./lib ./action ./template
curl -o ./lib/peel.php https://raw.githubusercontent.com/rmkn/peel/master/peel.php
curl -o .htaccess https://raw.githubusercontent.com/rmkn/peel/master/.htaccess


cat << 'EOS' >> ./action/default.php
<?php
// vim: set et ts=4 sw=4 sts=4:

class DefaultAction extends Peel
{
    public function prepare()
    {
        return true;
    }

    public function execGet()
    {
        echo __METHOD__;
        return true;
    }
}
EOS

cat << 'EOS' >> ./index.php
<?php
// vim: set et ts=4 sw=4 sts=4 :

require_once 'lib/peel.php';

$fcon = new PeelController();
$fcon->execute();

//$fcon = new PeelController(Peel::FORMAT_JSON);
//$fcon->execute(true);
EOS
