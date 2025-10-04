package com.example.counter.config

import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import software.amazon.awssdk.auth.credentials.AwsBasicCredentials
import software.amazon.awssdk.auth.credentials.StaticCredentialsProvider
import software.amazon.awssdk.regions.Region
import software.amazon.awssdk.services.sqs.SqsAsyncClient
import java.net.URI

@Configuration
class AwsConfig {
    
    init {
        println("aws config class is initialized!")
    }

    @Value("\${AWS_ACCESS_KEY_ID:test}")
    private lateinit var accessKey: String

    @Value("\${AWS_SECRET_ACCESS_KEY:test}")
    private lateinit var secretKey: String

    @Value("\${AWS_REGION:us-east-1}")
    private lateinit var region: String

    @Value("\${LOCALSTACK_ENDPOINT:http://localhost:4566}")
    private lateinit var endpoint: String

    @Bean
    fun sqsAsyncClient(): SqsAsyncClient {
        println("create SQS async client...")
        println("Access Key: $accessKey")
        println("Secret Key: $secretKey")
        println("Region: $region")
        println("Endpoint: $endpoint")
        
        val credentials = AwsBasicCredentials.create(accessKey, secretKey)
        val credentialsProvider = StaticCredentialsProvider.create(credentials)

        val client = SqsAsyncClient
            .builder()
            .region(Region.of(region))
            .credentialsProvider(credentialsProvider)
            .endpointOverride(URI.create(endpoint))
            .build()
            
        println("SQS async client successfully created")
        return client
    }
}
