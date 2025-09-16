# Soccer Manager API

A comprehensive Laravel-based REST API for managing soccer teams, players, and transfers with advanced features like event sourcing, real-time updates, and multi-language support.

## 🚀 Features

- **Team Management**: Create and manage soccer teams with automatic player generation
- **Player Management**: Update player information with authorization controls
- **Transfer System**: List players for transfer and purchase players with event sourcing
- **Authentication**: Secure API access using Laravel Sanctum
- **Internationalization**: Support for English and Georgian languages
- **Event Sourcing**: Advanced transfer system with Spatie Event Sourcing
- **API Documentation**: Complete Swagger/OpenAPI documentation
- **Comprehensive Testing**: Feature and unit tests with Pest PHP

## 🏗️ Technical Overview

### Architecture

The application follows a clean architecture pattern with clear separation of concerns:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Controllers   │    │    Services     │    │  Repositories   │
│                 │    │                 │    │                 │
│ • AuthController│───▶│ • UserService   │───▶│ • UserRepository│
│ • PlayerController│   │ • PlayerService │   │ • PlayerRepository│
│ • TeamController│    │ • TeamService   │    │ • TeamRepository│
│ • TransferController│ │ • TransferService│   │ • TransferRepository│
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Form Requests │    │  Authorization  │    │   Event Sourcing│
│                 │    │    Services     │    │                 │
│ • Validation    │    │ • Permission    │    │ • Aggregates    │
│ • Authorization │    │ • Access Control│    │ • Projectors    │
│ • Error Handling│    │ • Security      │    │ • Events        │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Core Components

#### 1. **Controllers** (`app/Http/Controllers/Api/`)
- Handle HTTP requests and responses
- Implement API endpoints with proper error handling
- Use dependency injection for services
- Include comprehensive Swagger documentation

#### 2. **Services** (`app/Services/`)
- Contain business logic and orchestration
- Handle complex operations and workflows
- Implement service interfaces for testability
- Manage cross-cutting concerns

#### 3. **Repositories** (`app/Repositories/`)
- Abstract data access layer
- Provide clean interface to database operations
- Enable easy testing with mock implementations
- Handle data transformation and queries

#### 4. **Form Requests** (`app/Http/Requests/`)
- Handle request validation and authorization
- Provide consistent error responses
- Support internationalization
- Implement security checks at request level

#### 5. **Event Sourcing** (`app/Aggregates/`, `app/Projectors/`, `app/StorableEvents/`)
- **Aggregates**: Manage business logic and state changes
- **Projectors**: Update read models based on events
- **Events**: Represent domain events in the system
- **Transfer System**: Complete transfer workflow with event sourcing

### Database Schema

#### Core Tables
- **users**: User accounts with team assignments
- **teams**: Soccer teams with balance, value (sum of players), and country information
- **players**: Player information with team assignments and values
- **countries**: Country reference data
- **transfer_listings**: Active transfer market listings
- **storable_events**: Event sourcing event storage

#### Key Relationships
- Users belong to Teams (many-to-one)
- Teams have many Players (one-to-many)
- Players belong to Countries (many-to-one)
- Transfer Listings reference Players and Teams

### Security Features

#### Authentication & Authorization
- **Laravel Sanctum**: Token-based authentication
- **Request-level Authorization**: Authorization logic in form requests
- **Permission Services**: Dedicated authorization services
- **Team Ownership**: Users can only modify their own team's data

#### Validation & Error Handling
- **Comprehensive Validation**: Field-level validation with custom rules
- **Consistent Error Responses**: Standardized JSON error format
- **Internationalization**: Error messages in multiple languages
- **Security Headers**: Proper HTTP status codes and security headers

### API Endpoints

#### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User authentication
- `GET /api/me` - Get current user information with team details

#### Teams
- `PUT /api/teams/{team}` - Update team information

#### Players
- `GET /api/players` - List players with pagination and filtering
- `PUT /api/players/{player}` - Update player information

#### Transfers
- `GET /api/transfer-listings` - List available transfers
- `POST /api/transfer-listings` - List player for transfer
- `POST /api/transfer/purchase/{player}` - Purchase player

#### Countries
- `GET /api/countries` - List available countries

### Event Sourcing Implementation

The transfer system uses event sourcing to ensure data consistency and provide audit trails:

#### Events
- `PlayerTransferInitiated` - Transfer process started
- `FundsTransferred` - Money moved between teams
- `PlayerTransferCompleted` - Transfer finalized

#### Aggregates
- `TransferAggregate` - Manages transfer business logic
- `TeamAggregate` - Handles team creation and fund allocation

#### Projectors
- `TransferProjector` - Updates read models after transfer events
- `TeamProjector` - Manages team state changes

### Configuration

#### Environment Variables
```env
# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306

# Soccer-specific settings
SOCCER_TEAM_INITIAL_BALANCE=5000000
SOCCER_PLAYER_INITIAL_VALUE=1000000
SOCCER_PLAYER_VALUE_INCREASE_MIN=10
SOCCER_PLAYER_VALUE_INCREASE_MAX=100

# Pagination
SOCCER_TRANSFER_LISTINGS_PER_PAGE=15
SOCCER_PLAYERS_PER_PAGE=20
SOCCER_MAX_PER_PAGE=100
```

#### Custom Configuration (`config/soccer.php`)
- Team settings (initial balance, player positions)
- Player settings (initial value, value increase ranges)
- Pagination settings for different endpoints

### Testing Strategy

#### Test Types
- **Feature Tests**: End-to-end API testing with database
- **Unit Tests**: Individual service and repository testing
- **Integration Tests**: Cross-component interaction testing

#### Test Coverage
- Authentication and authorization flows
- CRUD operations for all entities
- Transfer system with event sourcing
- Validation and error handling
- Internationalization support

#### Test Tools
- **Pest PHP**: Modern testing framework
- **Laravel Testing**: Built-in testing utilities
- **Mockery**: Mocking framework for dependencies

## 🛠️ Setup

### Prerequisites
- Docker and Docker Compose
- PHP 8.1+
- Composer

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd soccer-manager-api
```

2. **Install dependencies**
```bash
composer install
```

3. **Start the development environment**
```bash
./vendor/bin/sail up -d
```

4. **Run database migrations**
```bash
./vendor/bin/sail artisan migrate
```

5. **Seed the database**
```bash
./vendor/bin/sail artisan db:seed
```

6. **Generate API documentation**
```bash
./vendor/bin/sail artisan l5-swagger:generate
```

## 🧪 Testing

### Run all tests
```bash
./vendor/bin/sail test
```

### Run specific test suites
```bash
# Feature tests
./vendor/bin/sail test tests/Feature/

# Unit tests
./vendor/bin/sail test tests/Unit/

# Specific test file
./vendor/bin/sail test tests/Feature/PlayerUpdateTest.php
```

### Test coverage
```bash
./vendor/bin/sail test --coverage
```

## 📚 API Documentation

### Generate Swagger documentation
```bash
./vendor/bin/sail artisan l5-swagger:generate
```

### Access documentation
- **Swagger UI**: `http://localhost/api/documentation`
- **JSON Schema**: `http://localhost/api-docs.json`

## 🎨 Code Quality

### Fix code style using Pint
```bash
./vendor/bin/sail pint
```

### Run static analysis
```bash
./vendor/bin/sail pint --test
```

## 🌍 Internationalization

The API supports multiple languages through the `Accept-Language` header:

- **English** (default): `Accept-Language: en`
- **Georgian**: `Accept-Language: ka`

### Supported Languages
- Error messages
- Success messages
- Validation messages
- API responses

## 🔧 Development

### Code Structure
```
app/
├── Http/
│   ├── Controllers/Api/     # API controllers
│   ├── Requests/            # Form request validation
│   ├── Resources/           # API resource transformers
│   └── Middleware/          # Custom middleware
├── Services/                # Business logic services
├── Repositories/            # Data access layer
├── Models/                  # Eloquent models
├── Aggregates/              # Event sourcing aggregates
├── Projectors/              # Event projectors
└── StorableEvents/          # Domain events
```

### Key Design Patterns
- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic encapsulation
- **Event Sourcing**: State change tracking
- **CQRS**: Command Query Responsibility Segregation
- **Dependency Injection**: Loose coupling and testability
