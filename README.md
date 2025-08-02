# Amusement Park Coaster Management System

This application manages roller coasters and their assigned wagons in an amusement park. It analyzes if the park has sufficient resources (wagons and personnel) to handle the projected number of daily clients for each roller coaster.

## Architecture

The application follows Domain-Driven Design (DDD) principles and is structured into three bounded contexts:

1. **Fleet Management Context**: Responsible for CRUD operations of Coasters and Wagons via a REST API.
2. **Operational Analysis Context**: Contains the core business logic for calculating resource needs, analyzing throughput, and detecting operational problems.
3. **Monitoring Context**: An asynchronous CLI dashboard that displays the real-time status of the system.

## Environment Modes

The application supports two modes:
- **Development**: Used for development and testing
- **Production**: Used for production deployment

## Requirements

- PHP 8.0 or newer
- Docker and Docker Compose
- Redis

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd <repository-directory>
   ```

2. Start the Docker containers:
   ```
   docker-compose up -d
   ```

3. Install dependencies:
   ```
   docker-compose exec php composer install
   ```

## Running the Application

### Development Mode

By default, the application runs in development mode. To start the application in development mode:

```
docker-compose up -d
```

In development mode:
- Redis database 1 is used for data storage
- Logging is set to DEBUG level (all messages are logged)

### Production Mode

To run the application in production mode:

```
APP_ENVIRONMENT=production docker-compose up -d
```

In production mode:
- Redis database 0 is used for data storage
- Logging is set to WARNING level (only warnings and errors are logged)

## Testing Redis Configuration

A command is provided to test the Redis configuration in different environments:

```
# Test in development mode
docker-compose exec php php spark test:redis

# Test in production mode
docker-compose exec php APP_ENVIRONMENT=production php spark test:redis
```

## API Endpoints

### Coasters

- **Register a new Coaster**
  - `POST /api/coasters`
  - Example Request Body:
    ```json
    {
      "personnel_count": 16,
      "daily_clients": 60000,
      "track_length_meters": 1800,
      "operating_hours_start": "08:00",
      "operating_hours_end": "16:00"
    }
    ```

- **Update Coaster Details**
  - `PUT /api/coasters/{coasterId}`
  - Example Request Body:
    ```json
    {
      "personnel_count": 18,
      "daily_clients": 65000,
      "operating_hours_start": "08:00",
      "operating_hours_end": "17:00"
    }
    ```

### Wagons

- **Register a new Wagon for a Coaster**
  - `POST /api/coasters/{coasterId}/wagons`
  - Example Request Body:
    ```json
    {
      "seat_count": 32,
      "speed_mps": 1.2
    }
    ```

- **Remove a Wagon**
  - `DELETE /api/coasters/{coasterId}/wagons/{wagonId}`

## Monitoring Dashboard

To start the real-time monitoring dashboard:

```
docker-compose exec php php spark monitor
```

This will display a CLI dashboard showing the status of all coasters in real-time.

## Logging

Logs are stored in the `writable/logs` directory:
- Application logs: `writable/logs/log-*.php`
- Notification logs: `writable/logs/notifications.log`

## Code Quality

This project uses PHP_CodeSniffer (PHPCS) to ensure code quality and consistent coding style.

### Coding Standards

The project follows the PSR-12 coding standard with some custom configurations:
- Maximum line length is set to 120 characters
- PHP 8.1 compatibility is enforced

### Running Code Style Checks

To check your code for style violations:

```
docker-compose exec php composer cs
```

### Fixing Code Style Issues

To automatically fix code style issues where possible:

```
docker-compose exec php composer cs-fix
```

### Configuration

PHPCS configuration is stored in the `phpcs.xml` file in the project root. This defines:
- Which directories to check (app, tests)
- Which files to exclude
- Custom rules and settings
