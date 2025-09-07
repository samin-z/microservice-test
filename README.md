# Microservices Learning Project

A polyglot microservices architecture demonstrating synchronous and asynchronous communication patterns using different technology stacks and AWS services via LocalStack.

## 🏗️ Architecture Overview

This project consists of two microservices that demonstrate modern microservice patterns:

- **Service 1**: Counter API (Kotlin/Spring Boot) - Handles HTTP requests and manages a simple counter
- **Service 2**: Message Processor (PHP/Symfony) - Processes events asynchronously and sends email reports

## 🚀 System Flow

### Synchronous Communication
```
Client → GET /counter → Service 1 → PostgreSQL (returns current total)
Client → POST /counter/increment → Service 1 → PostgreSQL (+1) → SQS Message
```

### Asynchronous Communication
```
SQS Message → Symfony Messenger → MongoDB Document → Hourly Job → SES Email → Clear MongoDB
```

## 🛠️ Technology Stack

### Service 1: Counter API
- **Language**: Kotlin
- **Framework**: Spring Boot 3.x
- **Database**: PostgreSQL
- **Features**: Spring Data JPA, Spring Web, OpenAPI 3, JUnit 5
- **Purpose**: REST API for counter operations with comprehensive testing

### Service 2: Message Processor
- **Language**: PHP 8.4
- **Framework**: Symfony 7.x
- **Message Queue**: Symfony Messenger + SQS transport
- **Database**: MongoDB
- **Email**: AWS SES integration
- **Purpose**: Event collection and hourly email reports

### Infrastructure
- **Message Queue**: AWS SQS via LocalStack
- **Email Service**: AWS SES via LocalStack
- **Containerization**: Docker & Docker Compose
- **Local Development**: LocalStack for AWS services simulation

## 📁 Project Structure

```
microservice-test/
├── counter-api/                    # Service 1 (Kotlin/Spring Boot)
│   ├── src/main/kotlin/com/example/counter/
│   │   ├── CounterApplication.kt
│   │   ├── controller/CounterController.kt
│   │   ├── service/CounterService.kt
│   │   ├── repository/CounterRepository.kt
│   │   ├── entity/Counter.kt
│   │   ├── dto/CounterResponseDto.kt
│   │   └── config/
│   │       ├── DatabaseConfig.kt
│   │       └── SqsConfig.kt
│   ├── src/test/kotlin/com/example/counter/
│   │   ├── CounterApplicationTests.kt
│   │   ├── controller/CounterControllerTest.kt
│   │   ├── service/CounterServiceTest.kt
│   │   └── repository/CounterRepositoryTest.kt
│   ├── src/main/resources/
│   │   ├── application.yml
│   │   ├── application-test.yml
│   │   └── db/migration/V1__create_counter_table.sql
│   ├── build.gradle.kts
│   └── Dockerfile
├── message-processor/              # Service 2 (PHP/Symfony)
│   ├── src/
│   │   ├── MessageHandler/CounterIncrementHandler.php
│   │   ├── Document/CounterEvent.php
│   │   ├── Service/EmailService.php
│   │   ├── Command/ProcessHourlyEmailCommand.php
│   │   └── Repository/CounterEventRepository.php
│   ├── config/
│   │   ├── packages/messenger.yaml
│   │   ├── packages/doctrine_mongodb.yaml
│   │   └── services.yaml
│   ├── composer.json
│   ├── symfony.lock
│   ├── .env
│   └── Dockerfile
├── docker-compose.yml
└── README.md
```

## 🗄️ Database Schemas

### PostgreSQL (Service 1) - Simple Counter
```sql
CREATE TABLE counter (
    id INTEGER PRIMARY KEY DEFAULT 1,
    value INTEGER NOT NULL DEFAULT 0,
    CONSTRAINT single_row CHECK (id = 1)
);

INSERT INTO counter (id, value) VALUES (1, 0);
```

### MongoDB (Service 2) - Event Collection
```javascript
// Collection: counter_events
{
  "_id": ObjectId,
  "eventType": "COUNTER_INCREMENT",
  "timestamp": ISODate("2024-01-15T10:30:00Z"),
  "createdAt": ISODate("2024-01-15T10:30:05Z"),
  "metadata": {
    "source": "counter-api",
    "version": "1.0"
  }
}
```

## 🔌 API Endpoints

### Service 1: Counter API
- `GET /counter` - Returns current counter value: `{"value": 42}`
- `POST /counter/increment` - Increments counter, sends SQS message, returns new value
- `GET /health` - Health check endpoint
- `GET /swagger-ui.html` - OpenAPI documentation

## 📨 Message Format

### SQS Message Structure
```json
{
  "eventType": "COUNTER_INCREMENT",
  "timestamp": "2024-01-15T10:30:00Z",
  "metadata": {
    "source": "counter-api",
    "version": "1.0"
  }
}
```

## 📧 Email Reports

Service 2 generates hourly email reports with the following content:

```
Subject: Hourly Counter Report - [Date Hour]

Counter Activity Summary:
- Total increments this hour: 15
- Events processed: 15
- Time period: 2024-01-15 10:00-11:00 UTC

First event: 10:05:23 UTC
Last event: 10:58:47 UTC

All events have been processed and cleared from the system.
```

## 🎯 Learning Outcomes

### Microservice Patterns
- **Database per Service**: PostgreSQL vs MongoDB for different use cases
- **Event-driven Architecture**: Asynchronous communication via SQS
- **Polyglot Persistence**: Different storage solutions for different needs
- **Batch Processing**: Hourly aggregation and cleanup operations

### Technology Integration
- **Kotlin/Spring Boot**: Modern JVM microservice development with comprehensive testing
- **PHP/Symfony**: Modern PHP with Symfony Messenger for message handling
- **PostgreSQL**: Relational data management with atomic operations
- **MongoDB**: Document-based event storage with temporal data
- **LocalStack**: AWS services simulation for local development

### Communication Patterns
- **Synchronous**: REST API calls with proper error handling
- **Asynchronous**: SQS message queues with reliable processing
- **Scheduled Tasks**: Cron-based email notifications with cleanup
- **Event Sourcing**: Store events then process and clean pattern

## 🚀 Getting Started

### Prerequisites
- Docker & Docker Compose
- JDK 17+ (for Kotlin service)
- PHP 8.4+ & Composer (for PHP service)
- LocalStack CLI (optional, for debugging)

### Quick Start
```bash
# Clone the repository
git clone <repository-url>
cd microservice-test

# Start all services with Docker Compose
docker-compose up -d

# Check service health
curl http://localhost:8080/health  # Counter API
curl http://localhost:8080/counter # Get current counter value

# Increment counter (triggers async processing)
curl -X POST http://localhost:8080/counter/increment
```

### Development Setup
```bash
# Service 1 (Kotlin) - Local development
cd counter-api
./gradlew bootRun

# Service 2 (PHP) - Local development
cd message-processor
composer install
symfony server:start

# Run message consumer
php bin/console messenger:consume sqs

# Run hourly email job (manually)
php bin/console app:process-hourly-email
```

## 🧪 Testing

### Service 1 (Kotlin)
```bash
cd counter-api

# Run all tests
./gradlew test

# Run integration tests with TestContainers
./gradlew integrationTest

# Generate test coverage report
./gradlew jacocoTestReport
```

### Service 2 (PHP)
```bash
cd message-processor

# Run PHPUnit tests
php bin/phpunit

# Run specific test suites
php bin/phpunit tests/MessageHandler/
```

## 📊 Monitoring & Debugging

### LocalStack Services
- SQS Console: `http://localhost:4566/_localstack/sqs`
- SES Console: `http://localhost:4566/_localstack/ses`
- Health: `http://localhost:4566/_localstack/health`

### Application Logs
```bash
# View service logs
docker-compose logs -f counter-api
docker-compose logs -f message-processor

# MongoDB operations
docker-compose exec mongodb mongosh
```

## 🔧 Configuration

### Environment Variables
- `LOCALSTACK_ENDPOINT`: LocalStack endpoint URL
- `SQS_QUEUE_URL`: SQS queue URL for message processing
- `DATABASE_URL`: PostgreSQL connection string
- `MONGODB_URL`: MongoDB connection string
- `SES_FROM_EMAIL`: Email sender address for notifications

## 🎓 Educational Value

This project demonstrates:
- **Polyglot Microservices**: Different languages solving different problems
- **Event-driven Architecture**: Loose coupling through messaging
- **Different Data Models**: SQL for state, NoSQL for events
- **Comprehensive Testing**: Unit, integration, and contract testing
- **Modern Frameworks**: Latest versions of Spring Boot and Symfony
- **Cloud-native Patterns**: AWS services integration via LocalStack
- **Production Readiness**: Proper logging, health checks, and monitoring

## ⏱️ Implementation Timeline
- **Service 1 + Tests**: 4-5 hours
- **Service 2 + Symfony**: 3-4 hours
- **Integration & Docker**: 1-2 hours
- **Total**: 8-11 hours

Perfect for weekend learning or spread across multiple evening sessions!

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Implement changes with tests
4. Submit a pull request

## 📝 License

This project is for educational purposes. Feel free to use and modify as needed for learning microservices architecture.
