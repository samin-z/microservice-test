# Development Dockerfile for Kotlin Spring Boot application using bootRun

FROM gradle:8.5-jdk17

WORKDIR /app

# Install curl for health checks
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

# Create non-root user for security with proper home directory
RUN groupadd -r appuser && useradd -r -g appuser -m -d /home/appuser appuser

# Set up Gradle home directory with proper permissions
RUN mkdir -p /home/appuser/.gradle && chown -R appuser:appuser /home/appuser/.gradle

# Copy Gradle wrapper and build files
COPY gradle gradle
COPY gradlew .
COPY gradlew.bat .
COPY build.gradle.kts .
COPY settings.gradle.kts .

# Make gradlew executable
RUN chmod +x gradlew

# Change ownership of app directory
RUN chown -R appuser:appuser /app

# Switch to non-root user
USER appuser

# Set Gradle user home
ENV GRADLE_USER_HOME=/home/appuser/.gradle

# Download dependencies (cache layer)
RUN ./gradlew dependencies --no-daemon

# Copy source code
COPY --chown=appuser:appuser src src

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8080/actuator/health || exit 1

# Run the application in development mode with bootRun
CMD ["./gradlew", "bootRun", "--no-daemon", "--console=plain"]
