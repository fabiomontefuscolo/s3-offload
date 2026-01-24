#!/bin/bash

# Wait for LocalStack to be ready
echo "Waiting for LocalStack to be ready..."
sleep 5

# Create S3 bucket
awslocal s3 mb s3://wordpress-media --region us-east-1

# Set bucket policy to allow public read
awslocal s3api put-bucket-acl --bucket wordpress-media --acl public-read

echo "LocalStack S3 bucket 'wordpress-media' created successfully"
