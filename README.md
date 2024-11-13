# News Aggregation API

This project is a RESTful API built using Laravel 11, which aggregates news from multiple sources

## Project Setup

### Prerequisites

Ensure you have the following installed on your machine:

    - Docker Desktop
    - WSL2 
    - git

1. **Clone the repository:**
    git clone https://github.com/Serrao06/news-aggregator.git
     
    cd news-aggregation-api

2. **Build the Docker containers:**
    docker-compose up --build -d

3. **Access the Swagger documentation:**
    http://localhost:8000/api/documentation/

4. **Make sure you copy the .env.example file to .env file**
5. **Also make sure you add the apikeyes**
    NEWSAPI_KEY=
    NYTIMES_KEY=
    GUARDIAN_KEY=