#!/bin/bash

function initEnvironment(){
    containerVersion="0.91"
    awsAccountId=$(curl -s http://169.254.169.254/latest/dynamic/instance-identity/document | jq -r ".accountId")
    dbhost=$(aws ssm get-parameter --name /haloapp/database/dbHost --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    dbpassword=$(aws ssm get-parameter --name /haloapp/database/dbpassword --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    dbName=$(aws ssm get-parameter --name /haloapp/database/dbName --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    dbUserName=$(aws ssm get-parameter --name /haloapp/database/dbuserName --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    sesEmailAddress=$(aws ssm get-parameter --name /haloapp/outbound/sesEmailAddress --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    sesEmailName=$(aws ssm get-parameter --name /haloapp/outbound/sesEmailName --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    cityEmailAddress=$(aws ssm get-parameter --name /haloapp/outbound/cityEmailAddress --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    heathNetworkEmailAddress=$(aws ssm get-parameter --name /haloapp/outbound/mihinEmailAddress --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    printSftpHost=$(aws ssm get-parameter --name /haloapp/outbound/printSftpHost --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    printSftpUser=$(aws ssm get-parameter --name /haloapp/outbound/printSftpUser --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    printSftpPass=$(aws ssm get-parameter --name /haloapp/outbound/printSftpPassword --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    aws ssm get-parameter --name /haloapp/eodscripts/boxCredentials --region us-east-2 --with-decryption | jq -r ".Parameter.Value" > /var/www/scripts/box_config.json
    heathNetworkUserName=$(aws ssm get-parameter --name /haloapp/outbound/mihinUserName --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    heathNetworkUserPassword=$(aws ssm get-parameter --name /haloapp/outbound/mihinUserPassword --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    heathNetworkEmailFrom=$(aws ssm get-parameter --name /haloapp/outbound/mihinEmailFrom --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    heathNetworkEmailTo=$(aws ssm get-parameter --name /haloapp/outbound/mihinEmailTo --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    heathNetworkSmtpPort=$(aws ssm get-parameter --name /haloapp/outbound/mihinSmtpPort --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    heathNetworkSmtpUrl=$(aws ssm get-parameter --name /haloapp/outbound/mihinSmtpUrl --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    neverbouceApiKey=$(aws ssm get-parameter --name /haloapp/external/neverbouceApiKey --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    googleMapsApiKey=$(aws ssm get-parameter --name /haloapp/external/googleMapsApiKey --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    businessServiceId=$(aws ssm get-parameter --name /haloapp/businessServiceId --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
    export PATH="/home/ec2-user/.nvm/versions/node/v12.1.0/bin:/home/ec2-user/.local/bin:/home/ec2-user/bin:$PATH"
    export NVM_BIN="/home/ec2-user/.nvm/versions/node/v12.1.0/bin"
    npx box configure:environments:add /var/www/scripts/box_config.json
}

function generateBusinessFile(){
    currentDate=$(TZ=America/New_York date +%Y-%m-%d)
    currentHourStart=$(TZ=America/New_York date -d "1 hour ago" +%H:00:00)
    currentHourEnd=$(TZ=America/New_York date -d "1 hour ago" +%H:59:59)
    fileName="business_requistion_${currentDate}_$(TZ=America/New_York date +%H00).csv"
    startDateTime="${currentDate}T$currentHourStart"
    endDateTime="${currentDate}T$currentHourEnd"
    echo $fileName
    echo $startDateTime
    echo $endDateTime

    # Generate business csv files
    docker run \
    -e DB_HOST=$dbhost -e DB_USERNAME=$dbUserName \
    -e DB_PASSWORD=$dbpassword -e DB_NAME=$dbName \
    -e "APP_URL=localhost"  -e "APP_URL=localhost" \
    -e "APP_HOST=0.0.0.0" -e "APP_PORT=80" \
    -e "SES_EMAIL_ADDRESS=$sesEmailAddress" \
    -e "SES_EMAIL_NAME=$sesEmailName" \
    -e "CITY_EMAIL_ADDRESS=$cityEmailAddress" \
    -e "HEALTH_NETWORK_EMAIL_ADDRESS=$heathNetworkEmailAddress" \
    -e "PRINT_SFTP_HOST=$printSftpHost" \
    -e "PRINT_SFTP_USERNAME=$printSftpUser" \
    -e "PRINT_SFTP_PASS=$printSftpPass" \
    -e "HEALTH_NETWORK_USER_NAME=$heathNetworkUserName" \
    -e "HEALTH_NETWORK_USER_PWD=$heathNetworkUserPassword" \
    -e "HEALTH_NETWORK_MAIL_FROM=$heathNetworkEmailFrom" \
    -e "HEALTH_NETWORK_MAIL_TO=$heathNetworkEmailTo" \
    -e "HEALTH_NETWORK_SMTP_URL=$heathNetworkSmtpUrl" \
    -e "HEALTH_NETWORK_SMTP_PORT=$heathNetworkSmtpPort" \
    -e "NEVERBOUNCE_API_KEY=$neverbouceApiKey" \
    -e "GOOGLE_MAPS_API_KEY=$googleMapsApiKey" \
    -e "BUSINESS_SERVICE_ID=$businessServiceId" \
    -v /var/www/run:/var/script \
    -v /var/www/data:/var/www/data --entrypoint "/bin/bash" \
    -p 8080:80 $awsAccountId.dkr.ecr.us-east-2.amazonaws.com/haloapp:$containerVersion \
    -c "/bin/bash /var/script/docker-run-cmd.sh && php /var/www/html/index.php businessform generate $fileName $startDateTime $endDateTime"
}

function sendBusinessFile(){
    businessDirectory="/var/www/data/business"
    heathNetworkfolder=$(npx box folders:get 0 --json| jq '.item_collection.entries[] | select (.type == "folder" and .name == "Health - Rock - MiHIN") | .id' | sed "s/\"//g")
    businessfolder=$(npx box folders:get $heathNetworkfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "Business-Testing") | .id' | sed "s/\"//g")
    businessuploadfolder=$(npx box folders:get $businessfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "ToCity") | .id' | sed "s/\"//g")
    sudo mkdir -p $businessDirectory/sent

    csvCount=$(find $businessDirectory -maxdepth 1 -name "*.csv" -type f | wc -l)
    if [ "$csvCount" -ne 0 ]
    then
        for csvfile in $(ls $businessDirectory/*.csv)
        do
            npx box files:upload $csvfile -p $businessuploadfolder
            sudo mv $csvfile $businessDirectory/sent/$(basename $csvfile)
        done
        echo "Start sending success notifications to City"
        aws ses send-email \
        --from no-reply@covidtestdetroit.com \
        --destination file:///var/www/scripts/email/citySendEmailDestination.json \
        --message file:///var/www/scripts/email/citySendEmailContent.json --region us-east-1
        echo "End sending success notifications to City"
    else
        echo "No Business Files exist for this hour $currentHourStart"
    fi

}

initEnvironment
generateBusinessFile
sendBusinessFile
