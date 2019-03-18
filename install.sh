#!/bin/sh

mkdir ./lib ./action ./template
curl -o ./lib/peel.php https://raw.githubusercontent.com/rmkn/peel/master/peel.php
curl -o .htaccess https://raw.githubusercontent.com/rmkn/peel/master/.htaccess
