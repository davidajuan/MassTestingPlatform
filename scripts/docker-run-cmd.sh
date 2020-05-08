#!/usr/bin/env sh

createAppSettings() {
    cp $PROJECT_DIR/config-sample.php $PROJECT_DIR/config.php
    sed -i "s/DB_HOST       = ''/DB_HOST = '$DB_HOST'/g" $PROJECT_DIR/config.php
    sed -i "s/DB_USERNAME   = ''/DB_USERNAME = '$DB_USERNAME'/g" $PROJECT_DIR/config.php
    sed -i "s/DB_PASSWORD   = ''/DB_PASSWORD = '$DB_PASSWORD'/g" $PROJECT_DIR/config.php
    sed -i "s/DB_NAME       = ''/DB_NAME = '$DB_NAME'/g" $PROJECT_DIR/config.php

    sed -i "s/PRINT_SFTP_HOST\s*=\s*''/PRINT_SFTP_HOST = '$PRINT_SFTP_HOST'/g" $PROJECT_DIR/config.php
    sed -i "s/PRINT_SFTP_USERNAME\s*=\s*''/PRINT_SFTP_USERNAME = '$PRINT_SFTP_USERNAME'/g" $PROJECT_DIR/config.php
    sed -i "s/PRINT_SFTP_PASSWORD\s*=\s*''/PRINT_SFTP_PASSWORD = '$PRINT_SFTP_PASSWORD'/g" $PROJECT_DIR/config.php

    sed -i "s/SES_EMAIL_ADDRESS\s*=\s*''/SES_EMAIL_ADDRESS = '$SES_EMAIL_ADDRESS'/g" $PROJECT_DIR/config.php
    sed -i "s/SES_EMAIL_NAME\s*=\s*''/SES_EMAIL_NAME = '$SES_EMAIL_NAME'/g" $PROJECT_DIR/config.php
    sed -i "s/CITY_EMAIL_ADDRESS\s*=\s*''/CITY_EMAIL_ADDRESS = '$CITY_EMAIL_ADDRESS'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_EMAIL_ADDRESS\s*=\s*''/HEALTH_NETWORK_EMAIL_ADDRESS = '$HEALTH_NETWORK_EMAIL_ADDRESS'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_USER_NAME\s*=\s*''/HEALTH_NETWORK_USER_NAME = '$HEALTH_NETWORK_USER_NAME'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_USER_PWD\s*=\s*''/HEALTH_NETWORK_USER_PWD = '$HEALTH_NETWORK_USER_PWD'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_MAIL_FROM\s*=\s*''/HEALTH_NETWORK_MAIL_FROM = '$HEALTH_NETWORK_MAIL_FROM'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_MAIL_TO\s*=\s*''/HEALTH_NETWORK_MAIL_TO = '$HEALTH_NETWORK_MAIL_TO'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_SMTP_URL\s*=\s*''/HEALTH_NETWORK_SMTP_URL = '$HEALTH_NETWORK_SMTP_URL'/g" $PROJECT_DIR/config.php
    sed -i "s/HEALTH_NETWORK_SMTP_PORT\s*=\s*''/HEALTH_NETWORK_SMTP_PORT = '$HEALTH_NETWORK_SMTP_PORT'/g" $PROJECT_DIR/config.php

    sed -i "s/GOOGLE_MAPS_API_KEY\s*=\s*''/GOOGLE_MAPS_API_KEY = '$GOOGLE_MAPS_API_KEY'/g" $PROJECT_DIR/config.php
    sed -i "s/NEVERBOUNCE_API_KEY\s*=\s*''/NEVERBOUNCE_API_KEY = '$NEVERBOUNCE_API_KEY'/g" $PROJECT_DIR/config.php

    sed -i "s/APP_CONFIG\s*=\s*''/APP_CONFIG = '$APP_CONFIG'/g" $PROJECT_DIR/config.php

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
mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/

#Create Open SSL settings for secure email
sed -re 's/@SECLEVEL=[2-9]//' /etc/ssl/openssl.cnf >/var/script/openssl_nmap.cnf
export OPENSSL_CONF=/var/script/openssl_nmap.cnf

# PHP setup
sed -i "s/memory_limit = 128M/memory_limit = 1024M/g" /usr/local/etc/php/php.ini-production
sed -i "s/memory_limit = 128M/memory_limit = 1024M/g" /usr/local/etc/php/php.ini-development
sed -i "s#;date.timezone =#date.timezone = America/New_York#g" /usr/local/etc/php/php.ini-production
sed -i "s#;date.timezone =#date.timezone = America/New_York#g" /usr/local/etc/php/php.ini-development


# Copy production php ini
cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
echo "Preparing Easy!Appointments production configuration.."

createAppSettings
#exec $@
