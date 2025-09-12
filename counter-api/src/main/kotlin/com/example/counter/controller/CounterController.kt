package com.example.counter.controller

import com.example.counter.dto.CounterResponseDto
import com.example.counter.service.CounterService
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.*

@RestController //this class handle HTTP request and return json response
@RequestMapping("/counter") //define EP
class CounterController(
    private val counterService: CounterService
    private val counterService: CounterService
) {

    @GetMapping
    fun getCounter(): ResponseEntity<CounterResponseDto> {
        val counter = counterService.getCurrentValue()
        return ResponseEntity.ok(CounterResponseDto(counter))
    }

    @PostMapping("/increment")
    fun incrementCounter(): ResponseEntity<CounterResponseDto> {
        val counter = counterService.increment()
        return ResponseEntity.ok(CounterResponseDto(counter))
    }
}
