#!/usr/bin/env sh

createAppSettings() {
    # Copy Git Hash from previously built app, we don't want to override it.
    GIT_HASH=$(sed -n "s/^.*GIT_HASH\s*=\s*'\(.*\)'.*$/\1/p" $PROJECT_DIR/config.php)

    # Copy base configuration and populate runtime values
    cp $PROJECT_DIR/config-sample.php $PROJECT_DIR/config.php
    if [ -n "$APP_ENV" ]; then
        sed -i "s/APP_ENV\s*=\s*.*;/APP_ENV       = '$APP_ENV';/g" $PROJECT_DIR/config.php
    fi
    if [ -n "$DEBUG_MODE" ]; then
        sed -i "s/DEBUG_MODE\s*=\s*.*;/DEBUG_MODE    = $DEBUG_MODE;/g" $PROJECT_DIR/config.php
    fi

    sed -i "s/DB_HOST       = ''/DB_HOST = '$DB_HOST'/g" $PROJECT_DIR/config.php
    sed -i "s/DB_USERNAME   = ''/DB_USERNAME = '$DB_USERNAME'/g" $PROJECT_DIR/config.php
    sed -i "s/DB_PASSWORD   = ''/DB_PASSWORD = '$DB_PASSWORD'/g" $PROJECT_DIR/config.php
    sed -i "s/DB_NAME       = ''/DB_NAME = '$DB_NAME'/g" $PROJECT_DIR/config.php

    sed -i "s/PRINT_SFTP_HOST\s*=\s*''/PRINT_SFTP_HOST = '$PRINT_SFTP_HOST'/g" $PROJECT_DIR/config.php
    sed -i "s/PRINT_SFTP_USERNAME\s*=\s*''/PRINT_SFTP_USERNAME = '$PRINT_SFTP_USERNAME'/g" $PROJECT_DIR/config.php
    sed -i "s/PRINT_SFTP_PASSWORD\s*=\s*''/PRINT_SFTP_PASSWORD = '$PRINT_SFTP_PASSWORD'/g" $PROJECT_DIR/config.php

    sed -i "s/SES_EMAIL_ADDRESS\s*=\s*''/SES_EMAIL_ADDRESS = '$SES_EMAIL_ADDRESS'/g" $PROJECT_DIR/config.php
    sed -i "s/SES_EMAIL_NAME\s*=\s*''/SES_EMAIL_NAME = '$SES_EMAIL_NAME'/g" $PROJECT_DIR/config.php

    sed -i "s/HEALTH_NETWORK_USER_NAME\s*=\s*''/HEALTH_NETWORK_USER_NAME = '$HEALTH_NETWORK_USER_NAME'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_USER_PWD\s*=\s*''/HEALTH_NETWORK_USER_PWD = '$HEALTH_NETWORK_USER_PWD'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_MAIL_FROM\s*=\s*''/HEALTH_NETWORK_MAIL_FROM = '$HEALTH_NETWORK_MAIL_FROM'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_MAIL_TO\s*=\s*''/HEALTH_NETWORK_MAIL_TO = '$HEALTH_NETWORK_MAIL_TO'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_SMTP_URL\s*=\s*''/HEALTH_NETWORK_SMTP_URL = '$HEALTH_NETWORK_SMTP_URL'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_SMTP_PORT\s*=\s*''/HEALTH_NETWORK_SMTP_PORT = '$HEALTH_NETWORK_SMTP_PORT'/g" $PROJECT_DIR/config.php

    sed -i "s/GOOGLE_MAPS_API_KEY\s*=\s*''/GOOGLE_MAPS_API_KEY = '$GOOGLE_MAPS_API_KEY'/g" $PROJECT_DIR/config.php
    sed -i "s/NEVERBOUNCE_API_KEY\s*=\s*''/NEVERBOUNCE_API_KEY = '$NEVERBOUNCE_API_KEY'/g" $PROJECT_DIR/config.php
    sed -i "s/METRICS_WEBHOOK_URL\s*=\s*''/METRICS_WEBHOOK_URL = '$METRICS_WEBHOOK_URL'/g" $PROJECT_DIR/config.php

    # Populate Git Hash back in
    sed -i "s/GIT_HASH\s*=\s*''/GIT_HASH = '$GIT_HASH'/g" $PROJECT_DIR/config.php

    # If APP_CONFIG is found use that, otherwise use default app-config.json
    if [ -n "$APP_CONFIG" ]; then
        ESCAPED_REPLACE=$(echo $APP_CONFIG | sed -e 's/[\/&]/\\&/g')
        sed -i "s/APP_CONFIG\s*=\s*''/APP_CONFIG = '$ESCAPED_REPLACE'/g" $PROJECT_DIR/config.php
    else
        FILE_APP_CONFIG=$(cat $PROJECT_DIR/app-config.json)
        # https://stackoverflow.com/questions/407523
        ESCAPED_REPLACE=$(echo $FILE_APP_CONFIG | sed -e 's/[\/&]/\\&/g')
        sed -i "s/APP_CONFIG\s*=\s*''/APP_CONFIG = '$ESCAPED_REPLACE'/g" $PROJECT_DIR/config.php
    fi

    if [ "$EMAIL_PROTOCOL" = "smtp" ]; then
        echo "Setting up email..."
        sed -i "s/\$config\['protocol'\] = 'mail'/\$config['protocol'] = 'smtp'/g" $PROJECT_DIR/application/config/email.php
        sed -i "s#// \$config\['smtp_host'\] = ''#\$config['smtp_host'] = '$SMTP_HOST'#g" $PROJECT_DIR/application/config/email.php
        sed -i "s#// \$config\['smtp_user'\] = ''#\$config['smtp_user'] = '$SMTP_USER'#g" $PROJECT_DIR/application/config/email.php
        sed -i "s#// \$config\['smtp_pass'\] = ''#\$config['smtp_pass'] = '$SMTP_PASS'#g" $PROJECT_DIR/application/config/email.php
        sed -i "s#// \$config\['smtp_crypto'\] = 'ssl'#\$config['smtp_crypto'] = '$SMTP_CRYPTO'#g" $PROJECT_DIR/application/config/email.php
        sed -i "s#// \$config\['smtp_port'\] = 25#\$config['smtp_port'] = $SMTP_PORT#g" $PROJECT_DIR/application/config/email.php
    fi
    sed -i "s/url-to-easyappointments-directory/$APP_URL/g" $PROJECT_DIR/config.php

    chown -R www-data $PROJECT_DIR
}

# Apache setup
a2enmod rewrite
sed -i "s/#ServerName www.example.com/ServerName www.covidtestdetroit.com/g" /etc/apache2/sites-enabled/000-default.conf
echo "ServerName www.covidtestdetroit.com" >> /etc/apache2/apache2.conf

# PHP setup
sed -i "s/memory_limit = 128M/memory_limit = 1024M/g" /usr/local/etc/php/php.ini-production
sed -i "s/memory_limit = 128M/memory_limit = 1024M/g" /usr/local/etc/php/php.ini-development
sed -i "s/session.gc_maxlifetime = 1440/session.gc_maxlifetime = 14400/g" /usr/local/etc/php/php.ini-production
sed -i "s/session.gc_maxlifetime = 1440/session.gc_maxlifetime = 14400/g" /usr/local/etc/php/php.ini-development
sed -i "s#;date.timezone =#date.timezone = America/New_York#g" /usr/local/etc/php/php.ini-production
sed -i "s#;date.timezone =#date.timezone = America/New_York#g" /usr/local/etc/php/php.ini-development


if [ "$1" = "run" ]; then
    # Copy production php ini
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

    echo "Preparing Easy!Appointments production configuration.."

    createAppSettings

    echo "Starting Easy!Appointments production server.."

    exec docker-php-entrypoint apache2-foreground

elif [ "$1" = "standalone" ]; then
    # Copy production php ini
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

    echo "Preparing Easy!Appointments production configuration.."

    createAppSettings

elif [ "$1" = "dev" ]; then
    # Copy development php ini
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

    echo "Preparing Easy!Appointments development configuration.."

    createAppSettings

    echo "Starting Easy!Appointments development server.."

    exec docker-php-entrypoint apache2-foreground
fi

exec $@
