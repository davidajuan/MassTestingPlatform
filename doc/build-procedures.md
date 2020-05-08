# Build Procedures

## AWS credentials

1) Login to [awsconsole/](http://awsconsole/) and select the prod account.
2) Ensure that you have [AWS STS Chrome Plugin](https://chrome.google.com/webstore/detail/saml-to-aws-sts-keys-conv/ekniobabpcnfjgfbphhcolcinmnbehde?hl=en) installed. This will download a credentials file as you login.
3) Copy the credentials file to `~/.aws/`.

## Application Build

1) Navigate to the Mass Testing Platform project directory.
    - `git fetch`
    - `git pull`
    - `docker-compose build --build-arg CERT_URL=`
    - `docker-compose up`
    - `docker exec -it mtp_app bash`
    - run `./bin/build.sh` in the container
    - `exit`

## Docker build and ECR upload

You're going to need to replace:

- `<aws-account-number>` - AWS account number that Mass Testing Platform is deployed to
- `<version>` - next iteration of Git tag
- `<app-name>` - app name in aws
- `<ecr-repo-name>` - ecr app name in aws

1) ***Login to ECR***
    `aws ecr --no-verify-ssl get-login-password --region us-east-2 | docker login --username AWS --password-stdin <aws-account-number>.dkr.ecr.us-east-2.amazonaws.com/<app-name>`
2) ***Build docker and push to ECR***
    Update version of container below to the next version

    ```bash
    docker build -f Dockerfile-prod -t <ecr-repo-name> .
    docker tag <ecr-repo-name>:latest <aws-account-number>.dkr.ecr.us-east-2.amazonaws.com/<ecr-repo-name>:<version>
    docker push <aws-account-number>.dkr.ecr.us-east-2.amazonaws.com/<ecr-repo-name>:<version>
    git tag <version>
    git push origin --tags
    ```

## ECS Container Deployment

1) Create a new revision for task definition
   [Task Definition File](https://us-east-2.console.aws.amazon.com/ecs/home?region=us-east-2#/taskDefinitions/
2) Select latest revision (checkbox) and `Create new revision` button
3) Change JSON, click `Configure via JSON` button
   1) Change image reference to use new docker image
   2) `"image": "<aws-account-number>.dkr.ecr.us-east-2.amazonaws.com/<ecr-repo-name>:<version>",`
4) `Create` button to save task;
5) Navigate to ECS clusters
   [ECS Cluster](https://us-east-2.console.aws.amazon.com/ecs/home?region=us-east-2#/clusters/<your-cluster>/services)
6) Select services (checkbox) and `Update` button
7) Set the following in these screens:
   1) Select new Revision Number/latest (created previously)
   2) CodeDeploy application: `<app-name>`
   3) CodeDeploy deployment group: `<app-name>`
8) Proceed and click `Update Service` button
9) If blue/green is enabled, navigate to [code deploy](https://us-east-2.console.aws.amazon.com/codesuite/codedeploy/applications/<app-name>/deployment-groups/<app-name>?region=us-east-2&deployments-state=%7B%22f%22%3A%7B%22text%22%3A%22%22%7D%2C%22s%22%3A%7B%7D%2C%22n%22%3A20%2C%22i%22%3A0%7D) and wait till it is ready
   1) Follow the steps in code deploy and reroute traffic and terminate original task set
10) If no blue/green, then wait for the new task version to be active
11) Visit the site to confirm that `X-version` HTTP header represents deployed git hash

## Migration in ECS

TODO:

## Resources

<https://us-east-2.console.aws.amazon.com/ecr/repositories/<ecr-repo-name>/?region=us-east-2>
<https://us-east-2.console.aws.amazon.com/ecs/home?region=us-east-2#/taskDefinitions/<app-name>>
