package com.example.counter.service

import com.example.counter.entity.Counter
import com.example.counter.repository.CounterRepository
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import software.amazon.awssdk.services.sqs.SqsAsyncClient
import software.amazon.awssdk.services.sqs.model.SendMessageRequest
import java.time.Instant

@Service
class CounterService(
    private val counterRepository: CounterRepository,
    private val sqsAsyncClient: SqsAsyncClient
) {

    private val queueName: String = System.getenv("SQS_QUEUE_NAME") ?: "counter-increment-queue"
    private val queueUrl: String = System.getenv("SQS_QUEUE_URL") ?: "http://localstack:4566/000000000000/$queueName"

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
            .queueUrl(queueUrl)
            .messageBody(payload)
            .build()
        sqsAsyncClient.sendMessage(request)

        return counter.value
    }
}