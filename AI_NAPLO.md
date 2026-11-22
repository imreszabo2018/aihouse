# AI Development Log – Invoice Processor

## 1. Project Overview

The Invoice Processor project was developed as a full-stack application using Laravel, React, TypeScript, and MySQL.

AI assistance was used throughout the development process for:
- Understanding and breaking down the assignment requirements.
- Planning the application architecture.
- Implementing backend and frontend functionality.
- Debugging and troubleshooting.
- Writing and improving automated tests.
- Reviewing Docker configuration and project documentation.

All generated suggestions were reviewed, tested, and adapted before being included in the project.

## 2. Development Process

### 2.1. Project Analysis and Planning

**Objective:** Understand the assignment and identify the required application features.

AI assistance:
- Analyzed the assignment requirements.
- Helped divide the project into backend, frontend, database, infrastructure, and testing tasks.
- Suggested a Laravel API with a React frontend and MySQL database.
- Helped identify important edge cases, including duplicate invoices, invalid PDF files, missing exchange rates, and partial upload failures.

**Decision:** The Laravel + React + MySQL architecture was selected because it provides a clear separation between the API and the user interface.

### 2.2. Docker Environment and Backend Setup

**Objective:** Create a reproducible development environment.

AI assistance:
- Helped configure Docker Compose services.
- Assisted with PHP extensions and Composer dependencies.
- Helped troubleshoot container startup and database connectivity.
- Assisted with Laravel environment configuration and migrations.

**Issues encountered:**
- PHP extension compatibility problems.
- Docker container configuration issues.
- Environment variable and database connection problems.

**Resolution:** The Docker configuration was adjusted and the backend was successfully started in a container.

### 2.3. Backend Implementation

**Objective:** Implement invoice processing and business partner management.

AI assistance:
- Helped design Laravel models, migrations, controllers, and services.
- Assisted with PDF parsing.
- Helped implement invoice data validation.
- Assisted with invoice processing and duplicate handling.
- Helped structure API endpoints for invoices and partners.

**Implementation decisions:**
- Business logic was separated into service classes.
- Invoice and partner data are stored in MySQL.
- Uploaded files are processed independently.
- Validation is performed before invoice data is persisted.

### 2.4. MNB Exchange Rate Integration

**Objective:** Convert invoice amounts into Hungarian Forints using official exchange rates.

AI assistance:
- Helped design the exchange rate service.
- Assisted with integrating the Hungarian National Bank exchange rate source.
- Helped implement exchange rate caching.
- Assisted with handling dates when an exchange rate is not available.

**Implementation decisions:**
- Exchange rates are associated with the invoice fulfillment date.
- Previously published rates can be used when a rate is unavailable for the requested date.
- Retrieved rates are cached to avoid unnecessary repeated requests.
- HUF amounts are rounded per invoice before aggregation.

### 2.5. AI-Assisted Invoice Extraction

**Objective:** Support automated invoice data extraction.

AI assistance:
- Helped design the OpenRouter integration.
- Assisted with structuring and validating AI-generated invoice data.
- Helped separate AI extraction from the rest of the invoice processing logic.

**Important limitation:** An OpenRouter API key was not available during development. The application therefore supports optional AI extraction, but successful live extraction through OpenRouter was not verified.

### 2.6. React Frontend

**Objective:** Build an interface for uploading and reviewing invoices.

AI assistance:
- Helped set up the React and TypeScript frontend.
- Assisted with component structure and routing.
- Helped implement invoice uploads.
- Assisted with displaying invoice details and processing results.
- Helped implement dashboard statistics and visualizations.

**Implemented features:**
- Multiple PDF file selection and upload.
- Individual handling of upload failures.
- Invoice list with relevant invoice and exchange rate information.
- Monthly incoming and outgoing cash flow.
- Partner-level incoming and outgoing summaries.

### 2.7. Automated Testing

**Objective:** Verify important application behavior.

AI assistance:
- Helped identify test cases.
- Assisted with writing backend feature and unit tests.
- Helped write frontend tests for dashboard aggregation.
- Assisted with troubleshooting failing tests.

**Verified test results during development:**
- Backend upload tests passed.
- Backend application tests passed in earlier test runs.
- Frontend statistics tests passed (9 tests).

Testing was performed incrementally as features were implemented.

### 2.8. Docker Startup Improvements

**Objective:** Make the application easier to start in a fresh development environment.

AI assistance:
- Helped configure the frontend as a Docker Compose service.
- Assisted with backend container startup automation.
- Helped add automatic Composer dependency installation when required.
- Assisted with Laravel application key generation and database migrations during container startup.
- Helped configure Git line endings for shell scripts.

**Verification:** The Docker Compose configuration was validated, and the backend, frontend, and database containers started successfully. The application was accessible through the configured local URLs.

## 3. AI Suggestions and Human Decisions

AI-generated suggestions were not accepted automatically.

The following principles were followed:
- Suggestions were reviewed before implementation.
- Commands and configuration changes were executed and checked.
- Test results were used to verify behavior.
- Incorrect or environment-incompatible suggestions were adjusted.
- Optional features were kept separate from core application functionality where appropriate.

The final implementation decisions and code changes were reviewed during development.

## 4. Problems and Corrections

| Problem | Resolution |
|---|---|
| PHP extension compatibility issues | Adjusted the PHP Docker image and rebuilt the container |
| Database connection and environment configuration | Corrected Docker and Laravel environment settings |
| Frontend and backend integration issues | Tested API endpoints and adjusted frontend requests |
| OpenRouter API key unavailable | Kept AI extraction optional and documented the requirement |
| Docker startup dependencies | Added startup automation to the backend container |
| Git shell script line endings | Added `.gitattributes` configuration |

## 5. Development Tools

- ChatGPT – planning, implementation guidance, debugging, testing assistance, and documentation.
- Visual Studio Code – code editing.
- Docker Desktop – containerized development environment.
- Git and GitHub – version control.
- Laravel – backend framework.
- React and TypeScript – frontend development.

## 6. Final Notes

AI was used as a development assistant, not as an automatic code generator whose output was accepted without review.

The implementation was developed incrementally. Features were tested during development, and issues were investigated and corrected as they appeared.

The project documentation and final verification are being completed before submission.

## 7. AI Interaction and Decision Log

The following entries summarize the main AI-assisted development activities in the order they took place. Prompt descriptions are summaries rather than verbatim transcripts.

| Development stage | Main prompt / request | Decision and result |
|---|---|---|
| Requirements analysis | Analyze the assignment and break it down into implementation tasks | Accepted. Used to organize backend, frontend, database, testing, and Docker work |
| Backend development | Help implement invoice processing, PDF parsing, validation, and partner management | Suggestions were reviewed and adapted to the existing Laravel application |
| Exchange rate integration | Help implement MNB exchange rate retrieval, date selection, and caching | Accepted after implementation and testing |
| Frontend development | Help build the React interface, invoice list, uploads, and dashboard | Accepted and incrementally tested |
| Testing | Help identify test cases and investigate test failures | Test suggestions were reviewed and relevant tests were added or corrected |
| Docker configuration | Help make the application start through Docker Compose | Accepted after container startup and application access were verified |
| Documentation | Help prepare the README and AI development log | Accepted, with manual review and corrections |

### 8. AI Suggestions, Corrections, and Limitations

**OpenRouter integration**

The AI-assisted invoice extraction feature was implemented as an optional integration. A live API key was not available, so suggestions requiring a real OpenRouter request could not be fully verified.

**Docker and environment configuration**

Some initial Docker and PHP configuration attempts required corrections. The PHP image, extensions, environment variables, and startup process were adjusted until the containers started successfully.

**Git line endings**

A Git warning indicated possible line-ending conversion for shell scripts. A `.gitattributes` file was added to enforce LF line endings for `.sh` files.

**Rejected or adjusted approaches**

- AI-generated code and configuration suggestions were not accepted without review.
- Environment-dependent suggestions were adjusted when they did not work correctly in the Windows and Docker setup.
- Live OpenRouter functionality was not treated as verified without an API key.

### 9. Context and Rule Files

The following project materials were used as development context:

- The original assignment description.
- Existing Laravel and React source files.
- Docker Compose and Docker configuration files.
- Database migrations and automated tests.
- Project environment example files.

No separate custom AI rule file was created for this project.

### 10. AI Conversation Records

The development discussions took place in ChatGPT. The main implementation decisions, troubleshooting steps, and corrections are summarized in this document.

A separate full conversation export can be attached if required and available.