package com.example.counter.repository

import com.example.counter.entity.Counter
import org.springframework.data.jpa.repository.JpaRepository

interface CounterRepository : JpaRepository<Counter, Int>

