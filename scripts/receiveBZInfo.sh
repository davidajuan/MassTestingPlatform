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

function getBusinessFiles(){
    echo "Getting business files from the city"
    businessDirectory="/var/www/data/business"
    heathNetworkfolder=$(npx box folders:get 0 --json| jq '.item_collection.entries[] | select (.type == "folder" and .name == "Health - Rock - MiHIN") | .id' | sed "s/\"//g")
    businessfolder=$(npx box folders:get $heathNetworkfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "Business-Testing") | .id' | sed "s/\"//g")
    businessdownloadfolder=$(npx box folders:get $businessfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "FromCity") | .id' | sed "s/\"//g")
    businessfiles=$(npx box folders:get $businessdownloadfolder --json | jq '.item_collection.entries[] | select (.type == "file") |.' | jq '. | select (.name | endswith(".csv")) | .id' | sed "s/\"//g")
    sudo mkdir -p $businessDirectory/received
    sudo chown ec2-user $businessDirectory/received
    for fileId in $businessfiles
    do
        npx box files:download $fileId --destination $businessDirectory/received
    done
}

function processBusinessFiles(){
    echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - Processing business files"
    processDirectory="/var/www/data/business/received"
    find $processDirectory -type f -name "*.csv" | grep " " | while read line
    do
        newfile=$(echo $line | sed 's/ /_/g')
        mv "$line" $newfile
    done

    #csvCount=$(ls -l /var/www/data/business/received/*.csv | wc -l)
    csvCount=$(find $processDirectory -maxdepth 1 -name "*.csv" -type f | wc -l)
    scriptlogDirectory="/var/www/scripts/logs"
    echo $csvCount
    if [ "$csvCount" -ne 0 ]
    then
        for csvfile in $(ls $processDirectory/*.csv)
        do
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
            -c "/bin/bash /var/script/docker-run-cmd.sh && php /var/www/html/index.php importbusiness insert $csvfile" > $scriptlogDirectory/businessimporttmp.log
            sendAlerts $csvfile
            rm $scriptlogDirectory/businessimporttmp.log
            rm $csvfile
        done
    else
        echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - No Business Files to upload"
    fi
}
function sendAlerts()
{
    echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - Sending alerts"
    cityFilePath=$1
    cityFileName=$(basename $1)
    emailTemplateFolder="/var/www/scripts/email"
    scriptlogDirectory="/var/www/scripts/logs"
    errorCount=$(cat $scriptlogDirectory/businessimporttmp.log | grep "error" | wc -l)
    echo "$(cat $scriptlogDirectory/businessimporttmp.log)"

    if [ "$errorCount" -eq 0 ]
    then
        sed "s/\${fileName}/$cityFileName/g" \
          $emailTemplateFolder/cityReceiveEmailSuccessContent.json > \
          $emailTemplateFolder/citySuccessContent.json
        echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - Business Files upload success"
        aws ses send-email \
        --from no-reply@covidtestdetroit.com \
        --destination file://$emailTemplateFolder/cityReceiveEmailDestination.json \
        --message file://$emailTemplateFolder/citySuccessContent.json --region us-east-1
        backupBusinessFiles $csvfile "success"
    else
        echo "File has errored out"
        errorMessage=$(cat $scriptlogDirectory/businessimporttmp.log | grep "error" | sed '1q;d' | jq '.message' | sed 's/\\//g' | sed '$ s/.$//' | sed '0,/./s/^.//' | jq '.errorList[] | .' | sed -e 's/$/\\/' | sed '$ s/.$//' | sed 's/\"//g')
        echo $errorMessage

        sed -e "s/\${fileName}/$cityFileName/g" \
            -e "s/\${errorMessage}/$errorMessage/g" \
          $emailTemplateFolder/cityReceiveEmailErrorContent.json > \
          $emailTemplateFolder/cityErrorContent.json
        echo $(cat $emailTemplateFolder/cityErrorContent.json)
        echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - Business Files upload failure"
        aws ses send-email \
        --from no-reply@covidtestdetroit.com \
        --destination file://$emailTemplateFolder/cityReceiveEmailDestination.json \
        --message file://$emailTemplateFolder/cityErrorContent.json --region us-east-1
        backupBusinessFiles $csvfile "error"
    fi
}

function backupBusinessFiles()
{
    echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - Backup business files"
    filePath=$1
    businessFileName=$(basename $1)
    fileStatus=$2
    heathNetworkfolder=$(npx box folders:get 0 --json| jq '.item_collection.entries[] | select (.type == "folder" and .name == "Health - Rock - MiHIN") | .id' | sed "s/\"//g")
    businessfolder=$(npx box folders:get $heathNetworkfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "Business-Testing") | .id' | sed "s/\"//g")
    businessdownloadfolder=$(npx box folders:get $businessfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "FromCity") | .id' | sed "s/\"//g")
    processedfolder=$(npx box folders:get $businessdownloadfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "processed") | .id' | sed "s/\"//g")
    successfolder=$(npx box folders:get $processedfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "success") | .id' | sed "s/\"//g")
    errorfolder=$(npx box folders:get $processedfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "error") | .id' | sed "s/\"//g")
    if [ "$fileStatus" = "success" ]
    then
       npx box files:upload $filePath -p $successfolder
    else
       npx box files:upload $filePath -p $errorfolder
    fi
    echo "$(TZ=America/New_York date +'%Y-%m-%d %H:%M:%S') - Delete processed Business Files"
    businessFileId=$(npx box folders:get $businessdownloadfolder --json | jq '.item_collection.entries[] | select (.type == "file" and .name == '\"$businessFileName\"') | .id' | sed "s/\"//g")
    npx box files:delete $businessFileId
}

initEnvironment
getBusinessFiles
processBusinessFiles
