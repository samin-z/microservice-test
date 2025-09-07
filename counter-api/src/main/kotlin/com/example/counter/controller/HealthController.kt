package com.example.counter.controller

import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

@RestController
@RequestMapping("/health")
class HealthController {

    @GetMapping
    fun health(): ResponseEntity<Map<String, String>> = ResponseEntity.ok(
        mapOf(
            "status" to "UP",
            "service" to "counter-api",
            "timestamp" to System.currentTimeMillis().toString()
        )
    )
}
