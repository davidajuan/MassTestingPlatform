#!/usr/bin/env bash
set -e

DIR=$( cd "$( dirname "$0" )" && pwd )
printTitle()
{
    echo "========================================"
    echo "  $@"
    echo "========================================"
}


printTitle "Building App"


printTitle "PHP Install Dependencies"
composer install

printTitle "PHP Listing Outdated Packages"
composer outdated -m -D


printTitle "Node Dependencies"
# Include nvm script
. ~/.nvm/nvm.sh
nvm install 8.6.0
nvm use 8.6.0
npm install

printTitle "Node Listing Outdated Packages"
npm outdated || true


printTitle "Gulp Sass"
# npm rebuild node-sass
gulp sass


printTitle "Database Updates"
php ${DIR}/../src/index.php migrate update


printTitle "Setting Git Hash"
GIT_HASH=$(git rev-parse --short HEAD)
# Set only if we have git installed
if [ -z "$GIT_HASH" ]; then
      echo "Git is not available"
else
      echo "Setting git hash '$GIT_HASH' in config"
      sed -i "s/GIT_HASH\s*=\s*'[a-fA-F0-9]*'/GIT_HASH = '$GIT_HASH'/g" ${DIR}/../src/config.php
fi


printTitle "Finished"
