package com.example.counter

import org.springframework.boot.autoconfigure.SpringBootApplication
import org.springframework.boot.runApplication

@SpringBootApplication(scanBasePackages = ["com.example.counter"])
class CounterApplication

// starts the spring boot application
fun main(args: Array<String>) {
    runApplication<CounterApplication>(*args)
}
