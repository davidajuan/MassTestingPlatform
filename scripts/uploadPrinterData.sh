#!/bin/bash

echo "Starting daily print download process for $(TZ=EST date)"
printSftpHost=$(aws ssm get-parameter --name /haloapp/outbound/printSftpHost --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
printSftpUser=$(aws ssm get-parameter --name /haloapp/outbound/printSftpUser --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
printSftpPass=$(aws ssm get-parameter --name /haloapp/outbound/printSftpPassword --region us-east-2 --with-decryption | jq -r ".Parameter.Value")
aws ssm get-parameter --name /haloapp/eodscripts/boxCredentials --region us-east-2 --with-decryption | jq -r ".Parameter.Value" > /var/www/scripts/box_config.json
PORT=22
PASSWORD=$printSftpPass
GET_FILE_PATTERN=patient*.pdf
currDay=$(TZ=EST date +%Y-%m-%d)
GET_DIR=pickbox/$currDay
TARGET_DIR=/var/www/data/$currDay
echo "Start downloading daily print sftp files for $(TZ=EST date)"
/usr/bin/expect<<EOD
spawn /usr/bin/sftp -o Port=$PORT $printSftpUser@$printSftpHost
expect "password:"
send "$printSftpPass\r"
expect "sftp>"
send "cd $GET_DIR\r"
expect "sftp>"
send "lcd $TARGET_DIR\r"
expect "sftp>"
send "get $GET_FILE_PATTERN\r"
expect "sftp>"
send "bye\r"
EOD
echo "End downloading daily print sftp files for $(TZ=EST date)"
echo "Start uploading daily print files to box for $(TZ=EST date)"
export PATH="/home/ec2-user/.nvm/versions/node/v12.1.0/bin:/home/ec2-user/.local/bin:/home/ec2-user/bin:$PATH"
export NVM_BIN="/home/ec2-user/.nvm/versions/node/v12.1.0/bin"
npx box configure:environments:add /var/www/scripts/box_config.json
heathNetworkfolder=$(npx box folders:get 0 --json| jq '.item_collection.entries[] | select (.type == "folder" and .name == "Health - Rock - MiHIN") | .id' | sed "s/\"//g")
dailyContainerfolder=$(npx box folders:get $heathNetworkfolder --json | jq '.item_collection.entries[] | select (.type == "folder" and .name == "Daily Drive Thru Documents") | .id' | sed "s/\"//g")
currDayBox=$(TZ=EST date +%m-%d-%Y)
dailyfolder=$(npx box folders:get $dailyContainerfolder --json | jq '.item_collection.entries[] | select (.type == "folder") | .' | jq '. | select (.name | contains('\"$currDayBox\"')) | .id' | sed "s/\"//g")
for pdffile in $(ls /var/www/data/$currDay/*.pdf)
do
    npx box files:upload $pdffile -p $dailyfolder
done
echo "End uploading daily print files to box for $(TZ=EST date)"
echo "End daily print download process for $(TZ=EST date)"


