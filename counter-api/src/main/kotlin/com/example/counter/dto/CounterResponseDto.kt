package com.example.counter.dto

import io.swagger.v3.oas.annotations.media.Schema

@Schema(description = "Counter response containing the current counter value")
data class CounterResponseDto(
    @Schema(description = "Current counter value", example = "0", minimum = "0")
    val value: Int
)
