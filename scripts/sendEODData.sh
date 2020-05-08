#!/bin/bash
containerVersion="0.96"
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
s3bucketName=$(aws ssm get-parameter --name /haloapp/backup/s3BucketName --region us-east-2 --with-decryption | jq -r ".Parameter.Value")

echo "Starting daily process for $(TZ=EST date)"
echo "------ Start Generating daily files for Printer and City----------"

#rm -rf /var/www/data

# if [ $(TZ=EST date +%w) -ne 6 ]
# then
#    nextDay=$(TZ=EST date +%Y-%m-%d -d "$(date) + 1 day")
# else
#    nextDay=$(TZ=EST date +%Y-%m-%d -d "$(date) + 2 day")
# fi
if [ -z "$1" ]
then
   nextDay=$(TZ=EST date +%Y-%m-%d -d "$(date) + 1 day")
else
   date +%Y-%m-%d -d "$1" > /dev/null 2>&1
   if [ $? -eq 0 ]
   then
      nextDay=$1
   else
      echo "Date format is invalid. Please enter date in YYYY-MM-DD format"
      exit 1
   fi
fi

# Generate csv files
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
-v /var/www/run:/var/script \
-v /var/www/data:/var/www/data --entrypoint "/bin/bash" \
-p 8080:80 $awsAccountId.dkr.ecr.us-east-2.amazonaws.com/haloapp:$containerVersion \
-c "/bin/bash /var/script/docker-run-cmd.sh && php /var/www/html/index.php patientform generate $nextDay"

echo "------ Finished Generating daily files for Printer and City----------"
echo "------ Start sending daily files to Printer----------"
#send sftp files to wolverine
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
-e "PRINT_SFTP_PASSWORD=$printSftpPass" \
-e "HEALTH_NETWORK_USER_NAME=$heathNetworkUserName" \
-e "HEALTH_NETWORK_USER_PWD=$heathNetworkUserPassword" \
-e "HEALTH_NETWORK_MAIL_FROM=$heathNetworkEmailFrom" \
-e "HEALTH_NETWORK_MAIL_TO=$heathNetworkEmailTo" \
-e "HEALTH_NETWORK_SMTP_URL=$heathNetworkSmtpUrl" \
-e "HEALTH_NETWORK_SMTP_PORT=$heathNetworkSmtpPort" \
-e "NEVERBOUNCE_API_KEY=$neverbouceApiKey" \
-e "GOOGLE_MAPS_API_KEY=$googleMapsApiKey" \
-v /var/www/run:/var/script \
-v /var/www/data:/var/www/data --entrypoint "/bin/bash" \
-v /var/www/html/storage/logs:/var/www/html/storage/logs \
-p 8080:80 $awsAccountId.dkr.ecr.us-east-2.amazonaws.com/haloapp:$containerVersion \
-c "/bin/bash /var/script/docker-run-cmd.sh && php /var/www/html/index.php sendcsv sendprint $nextDay"
echo "------ Finished sending daily files to Printer----------"
echo "------ Start emailing daily files to Health Network----------"
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
-e "PRINT_SFTP_PASSWORD=$printSftpPass" \
-e "HEALTH_NETWORK_USER_NAME=$heathNetworkUserName" \
-e "HEALTH_NETWORK_USER_PWD=$heathNetworkUserPassword" \
-e "HEALTH_NETWORK_MAIL_FROM=$heathNetworkEmailFrom" \
-e "HEALTH_NETWORK_MAIL_TO=$heathNetworkEmailTo" \
-e "HEALTH_NETWORK_SMTP_URL=$heathNetworkSmtpUrl" \
-e "HEALTH_NETWORK_SMTP_PORT=$heathNetworkSmtpPort" \
-e "NEVERBOUNCE_API_KEY=$neverbouceApiKey" \
-e "GOOGLE_MAPS_API_KEY=$googleMapsApiKey" \
-v /var/www/run:/var/script \
-v /var/www/data:/var/www/data --entrypoint "/bin/bash" \
-v /var/www/html/storage/logs:/var/www/html/storage/logs \
-p 8080:80 $awsAccountId.dkr.ecr.us-east-2.amazonaws.com/haloapp:$containerVersion \
-c "/bin/bash /var/script/docker-run-cmd.sh && export OPENSSL_CONF=/var/script/openssl_nmap.cnf && php /var/www/html/index.php documentpush index $nextDay"
echo "------ End sending daily files to Health NEtwork----------"
echo "Start backing up files to s3"
aws s3 cp /var/www/data/$nextDay s3://$s3bucketName/$nextDay --recursive
echo "End backing up files to s3"

echo "Start sending daily files to box"
export PATH="/home/ec2-user/.nvm/versions/node/v12.1.0/bin:/home/ec2-user/.local/bin:/home/ec2-user/bin:$PATH"
export NVM_BIN="/home/ec2-user/.nvm/versions/node/v12.1.0/bin"
npx box configure:environments:add /var/www/scripts/box_config.json
heathNetworkfolder=$(npx box folders:get 0 --json| jq '.item_collection.entries[] | select (.type == "folder" and .name == "Health - Rock - MiHIN") | .id' | sed "s/\"//g")
dailyContainerfolder=$(npx box folders:get $heathNetworkfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "Daily Drive Thru Documents") | .id' | sed "s/\"//g")
transportationfolder=$(npx box folders:get $heathNetworkfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "transportation") | .id' | sed "s/\"//g")
#dailyfolder=$(npx box folders:get $dailyContainerfolder --json | jq '.item_collection.entries[] | select (.name | contains("03-30-2020")) | .id' | sed "s/\"//g")
nextDayBox=$(TZ=EST date +%m-%d-%Y -d "$(date) + 1 day")
dailyfolder=$(npx box folders:get $dailyContainerfolder --json | jq '.item_collection.entries[] | select (.type == "folder") | .' | jq '. | select (.name | contains('\"$nextDayBox\"')) | .id' | sed "s/\"//g")
for csvfile in $(ls /var/www/data/$nextDay/*.csv)
do
    npx box files:upload $csvfile -p $dailyfolder
done
cp /var/www/data/$nextDay/patients_form_print.csv /var/www/data/$nextDay/patients_form_print_$nextDay.csv
npx box files:upload /var/www/data/$nextDay/patients_form_print_$nextDay.csv -p $transportationfolder
rm /var/www/data/$nextDay/patients_form_print_$nextDay.csv
echo "End sending daily files to box"
echo "Ending daily process for $(TZ=EST date)"

echo "Start sending success notifications to halo"
aws sns publish --region us-east-2 --topic-arn arn:aws:sns:us-east-2:$awsAccountId:events_prod_halo_eodJobs --message "Successfully sent Files to Print Company"
echo "End sending success notifications to halo"
