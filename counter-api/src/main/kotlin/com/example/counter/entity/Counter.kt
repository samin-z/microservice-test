package com.example.counter.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.Id
import jakarta.persistence.Table

@Entity
@Table(name = "counter")
class Counter(
    @Id
    @Column(name = "id")
    var id: Int = 1,

    @Column(name = "value", nullable = false)
    var value: Int = 0
)