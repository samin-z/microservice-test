package com.example.counter.service

import com.example.counter.entity.Counter
import com.example.counter.repository.CounterRepository
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import software.amazon.awssdk.services.sqs.SqsAsyncClient
import software.amazon.awssdk.services.sqs.model.SendMessageRequest
import software.amazon.awssdk.services.sqs.model.MessageAttributeValue
import software.amazon.awssdk.services.sqs.model.GetQueueUrlRequest
import software.amazon.awssdk.services.sqs.model.CreateQueueRequest
import java.time.Instant

@Service
class CounterService(
    private val counterRepository: CounterRepository,
    private val sqsAsyncClient: SqsAsyncClient
) {

    private val queueName: String = System.getenv("SQS_QUEUE_NAME") ?: "counter-increment-queue"
    @Volatile private var cachedQueueUrl: String? = null

    /*
    this function has three benefits, 
    fisrt of all avoid multiple AWS api calls, 
    second create queue in case it does not exist and 
    this this stop multiple request to have conflicts
    */
    private fun resolveQueueUrl(): String {
        cachedQueueUrl?.let { return it }
        synchronized(this) { // this line ensure that were executing one thread at a time
            cachedQueueUrl?.let { return it }
            val url = try {
                sqsAsyncClient.getQueueUrl(
                    GetQueueUrlRequest.builder().queueName(queueName).build()
                ).get().queueUrl()
            } catch (ex: Exception) {
                sqsAsyncClient.createQueue(
                    CreateQueueRequest.builder().queueName(queueName).build()
                ).get()
                sqsAsyncClient.getQueueUrl(
                    GetQueueUrlRequest.builder().queueName(queueName).build()
                ).get().queueUrl()
            }
            cachedQueueUrl = url
            return url
        }
    }

    fun getCurrentValue(): Int {
        val counter = counterRepository.findById(1).orElseGet { Counter(id = 1, value = 0) }
        if (!counterRepository.existsById(1)) {
            counterRepository.save(counter)
        }
        return counter.value
    }

    @Transactional
    fun increment(): Int {
        val counter = counterRepository.findById(1).orElseGet { Counter(id = 1, value = 0) }
        counter.value = counter.value + 1
        counterRepository.save(counter)

        // this block creates event payload according to the readme 
        // in kotlin triple " start a raw multiline string
        val payload = """
            {"eventType":"COUNTER_INCREMENT","timestamp":"${Instant.now()}","metadata":{"source":"counter-api","version":"1.0"}}
        """.trimIndent()

        // sending SQS message 
        val request = SendMessageRequest.builder()
            .queueUrl(resolveQueueUrl())
            .messageBody(payload)
            .messageAttributes(
                mapOf(
                    "type" to MessageAttributeValue.builder()
                        .dataType("String")
                        .stringValue("App\\Message\\CounterIncrementMessage")
                        .build()
                )
            )
            .build()
        sqsAsyncClient.sendMessage(request)

        return counter.value
    }
}