package com.example.counter.controller

import com.example.counter.dto.CounterResponseDto
import com.example.counter.service.CounterService
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.*
import io.swagger.v3.oas.annotations.Operation
import io.swagger.v3.oas.annotations.media.Content
import io.swagger.v3.oas.annotations.media.Schema
import io.swagger.v3.oas.annotations.responses.ApiResponse
import io.swagger.v3.oas.annotations.responses.ApiResponses
import io.swagger.v3.oas.annotations.tags.Tag

@RestController // this class handle HTTP request and return json response
@RequestMapping("/counter") // define EP
@Tag(name = "Counter", description = "Operations for retrieving and incrementing the counter")
class CounterController(
    private val counterService: CounterService
) {

    @GetMapping
    @Operation(summary = "Get current counter value", description = "Returns the current value of the counter")
    @ApiResponses(
        value = [
            ApiResponse(
                responseCode = "200",
                description = "Current counter value returned",
                content = [Content(
                    mediaType = "application/json",
                    schema = Schema(implementation = CounterResponseDto::class),
                    examples = []
                )]
            ),
            ApiResponse(responseCode = "500", description = "Server error")
        ]
    )
    fun getCurrentValue(): ResponseEntity<CounterResponseDto>
    {
        val counter = counterService.getCurrentValue()
        return ResponseEntity.ok(CounterResponseDto(counter))
    }

    @PostMapping("/increment")
    @Operation(summary = "Increment counter", description = "Increments the counter by 1 and returns the new value")
    @ApiResponses(
        value = [
            ApiResponse(
                responseCode = "200",
                description = "Counter incremented successfully",
                content = [Content(
                    mediaType = "application/json",
                    schema = Schema(implementation = CounterResponseDto::class)
                )]
            ),
            ApiResponse(responseCode = "500", description = "Server error")
        ]
    )
    fun increment(): ResponseEntity<CounterResponseDto>
    {
        val counter = counterService.increment()
        return ResponseEntity.ok(CounterResponseDto(counter))
    }
}
