package com.example.counter.repository

import com.example.counter.entity.Counter
import org.springframework.data.jpa.repository.JpaRepository

// layer for data access
interface CounterRepository : JpaRepository<Counter, Int>

