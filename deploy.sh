#!/bin/bash
# CubeTimer Home Deployment Script
# This script deploys the CubeTimer application on a home server with Docker

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting CubeTimer Home Deployment...${NC}"

# Check if we're in the project directory
if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: This script must be run from the CubeTimer project root directory.${NC}"
    echo -e "${RED}Please navigate to the directory containing artisan and composer.json.${NC}"
    exit 1
fi

# Check for Docker and Docker Compose (supporting both docker compose and docker-compose)
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

# Check for docker-compose (standalone) first, then docker compose (v2 plugin)
if command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
elif command -v docker compose &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    echo -e "${RED}Error: Docker Compose is not installed. Please install Docker Compose (either as v2 plugin or standalone).${NC}"
    exit 1
fi

# Create necessary directories
mkdir -p secrets
mkdir -p ssl

# Function to generate a random string
generate_random_string() {
    openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | fold -w ${1:-32} | head -n 1
}

# Load existing .env if it exists, otherwise create from example
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}Created .env from .env.example${NC}"
    else
        echo -e "${RED}Error: .env.example not found.${NC}"
        exit 1
    fi
fi

# Function to set or generate a value in .env
set_env_value() {
    local key="$1"
    local value="$2"
    # Escape any sed special characters in value
    local escaped_value=$(printf '%s\n' "$value" | sed -e 's/[\/&]/\\&/g')
    if grep -q "^${key}=" .env; then
        # Key exists, replace value
        sed -i '' "s/^${key}=.*/${key}=${escaped_value}/" .env 2>/dev/null || sed -i "s/^${key}=.*/${key}=${escaped_value}/" .env
    else
        # Key doesn't exist, append
        echo "${key}=${value}" >> .env
    fi
}

# Generate secure secrets if they are using defaults or not set
echo -e "${YELLOW}Checking and generating secure secrets...${NC}"

# Check and set APP_KEY
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env || grep -q "^APP_KEY=base64:" .env | grep -v "base64:[0-9a-zA-Z]\{40,\}"; then
    APP_KEY="base64:$(generate_random_string 32)"
    set_env_value "APP_KEY" "$APP_KEY"
    echo -e "${GREEN}Generated new APP_KEY${NC}"
fi

# Check and set DB_PASSWORD
if ! grep -q "^DB_PASSWORD=" .env || [ "$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)" = "secret" ]; then
    DB_PASSWORD=$(generate_random_string 16)
    set_env_value "DB_PASSWORD" "$DB_PASSWORD"
    echo -e "${GREEN}Generated new DB_PASSWORD${NC}"
fi

# Check and set DB_ROOT_PASSWORD (if not set)
if ! grep -q "^DB_ROOT_PASSWORD=" .env || [ "$(grep '^DB_ROOT_PASSWORD=' .env | cut -d'=' -f2)" = "" ]; then
    DB_ROOT_PASSWORD=$(generate_random_string 16)
    set_env_value "DB_ROOT_PASSWORD" "$DB_ROOT_PASSWORD"
    echo -e "${GREEN}Generated new DB_ROOT_PASSWORD${NC}"
fi

# Check and set REDIS_PASSWORD
if ! grep -q "^REDIS_PASSWORD=" .env || [ "$(grep '^REDIS_PASSWORD=' .env | cut -d'=' -f2)" = "null" ] || [ "$(grep '^REDIS_PASSWORD=' .env | cut -d'=' -f2)" = "" ]; then
    REDIS_PASSWORD=$(generate_random_string 16)
    set_env_value "REDIS_PASSWORD" "$REDIS_PASSWORD"
    echo -e "${GREEN}Generated new REDIS_PASSWORD${NC}"
fi

# Check and set REVERB_APP_KEY
if ! grep -q "^REVERB_APP_KEY=" .env || [ "$(grep '^REVERB_APP_KEY=' .env | cut -d'=' -f2)" = "" ]; then
    REVERB_APP_KEY=$(generate_random_string 32)
    set_env_value "REVERB_APP_KEY" "$REVERB_APP_KEY"
    echo -e "${GREEN}Generated new REVERB_APP_KEY${NC}"
fi

# Check and set REVERB_APP_SECRET
if ! grep -q "^REVERB_APP_SECRET=" .env || [ "$(grep '^REVERB_APP_SECRET=' .env | cut -d'=' -f2)" = "" ]; then
    REVERB_APP_SECRET=$(generate_random_string 32)
    set_env_value "REVERB_APP_SECRET" "$REVERB_APP_SECRET"
    echo -e "${GREEN}Generated new REVERB_APP_SECRET${NC}"
fi

# Auto-detect server IP for APP_URL (if not already set to a custom domain)
if ! grep -q "^APP_URL=" .env || [ "$(grep '^APP_URL=' .env | cut -d'=' -f2)" = "http://192.168.1.35:8080" ]; then
    # Try to get the primary IP address (cross-platform)
    SERVER_IP=""
    if command -v hostname >/dev/null 2>&1; then
        if hostname -I >/dev/null 2>&1; then
            # Linux
            SERVER_IP=$(hostname -I | awk '{print $1}')
        elif hostname -i >/dev/null 2>&1; then
            # macOS/BSD alternative
            SERVER_IP=$(hostname -i)
        fi
    fi
    
    # If hostname didn't work, try other methods
    if [ -z "$SERVER_IP" ] || [ "$SERVER_IP" = "127.0.0.1" ]; then
        if command -v ifconfig >/dev/null 2>&1; then
            # Try to get first non-localhost IPv4 address
            SERVER_IP=$(ifconfig | grep -E "inet.[0-9]" | grep -v 127.0.0.1 | awk '{ print $2 }' | head -1 | cut -d: -f2)
        fi
    fi
    
    # Last resort fallback
    if [ -z "$SERVER_IP" ] || [ "$SERVER_IP" = "127.0.0.1" ]; then
        SERVER_IP="192.168.1.100"
        echo -e "${YELLOW}Warning: Could not auto-detect server IP, using $SERVER_IP as fallback${NC}"
    fi
    
    # Use a delimiter that won't conflict with slashes in the URL
    set_env_value "APP_URL" "http://${SERVER_IP}:8080"
    echo -e "${GREEN}Set APP_URL to http://${SERVER_IP}:8080${NC}"
fi

# Create docker-compose.home.yml if it doesn't exist
if [ ! -f "docker-compose.home.yml" ]; then
    cat > docker-compose.home.yml << 'EOF'
version: '3.8'

services:
  # Laravel Application
  app:
    build:
      context: .
      dockerfile: Dockerfile.app
    ports:
      - "8080:8080"  # Expose via Nginx on port 8080
    volumes:
      - ./:/var/www/html
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    environment:
      - APP_URL=${APP_URL}
      - APP_ENV=${APP_ENV:-local}
      - APP_DEBUG=${APP_DEBUG:-true}
      - APP_KEY=${APP_KEY}
      - DB_CONNECTION=${DB_CONNECTION:-mysql}
      - DB_HOST=${DB_HOST:-db}
      - DB_PORT=${DB_PORT:-3306}
      - DB_DATABASE=${DB_DATABASE:-cubetimer}
      - DB_USERNAME=${DB_USERNAME:-cubetimer}
      - DB_PASSWORD=${DB_PASSWORD}
      - CACHE_STORE=redis
      - SESSION_DRIVER=redis
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=${REDIS_HOST:-redis}
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - REDIS_PORT=${REDIS_PORT:-6379}
      - REVERB_HOST=${REVERB_HOST:-reverb}
      - REVERB_PORT=${REVERB_PORT:-8081}
      - REVERB_SCHEME=${REVERB_SCHEME:-http}
      - REVERB_APP_ID=${REVERB_APP_ID}
      - REVERB_APP_KEY=${REVERB_APP_KEY}
      - REVERB_APP_SECRET=${REVERB_APP_SECRET}
    depends_on:
      - db
      - redis
      - reverb
    restart: unless-stopped

  # Web Server (Nginx)
  web:
    build:
      context: .
      dockerfile: Dockerfile.web
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
      - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
    restart: unless-stopped

  # Database
  db:
    build:
      context: .
      dockerfile: Dockerfile.db
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
    environment:
      - MYSQL_DATABASE=${DB_DATABASE:-cubetimer}
      - MYSQL_USER=${DB_USERNAME:-cubetimer}
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
    restart: unless-stopped

  # Redis
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: ["redis-server", "--appendonly", "yes"]
    restart: unless-stopped

   # Reverb WebSocket Server
   reverb:
     build:
       context: .
       dockerfile: Dockerfile.reverb
     ports:
       - "8081:8081"
     volumes:
       - ./:/var/www/html
       - ./storage:/var/www/html/storage
       - ./bootstrap/cache:/var/www/html/bootstrap/cache
     environment:
       - APP_NAME=${APP_NAME:-Laravel}
       - APP_ENV=${APP_ENV:-local}
       - BROADCAST_DRIVER=pusher
       - PUSHER_APP_ID=${REVERB_APP_ID}
       - PUSHER_KEY=${REVERB_APP_KEY}
       - PUSHER_SECRET=${REVERB_APP_SECRET}
       - PUSHER_HOST=${REVERB_HOST:-reverb}
       - PUSHER_PORT=${REVERB_PORT:-8081}
       - PUSHER_SCHEME=${REVERB_SCHEME:-http}
     depends_on:
       - redis
     restart: unless-stopped

volumes:
  db_data:
  redis_data:
EOF
    echo -e "${GREEN}Created docker-compose.home.yml${NC}"
fi

# Create Dockerfile.app if it doesn't exist
if [ ! -f "Dockerfile.app" ]; then
    cat > Dockerfile.app << 'EOF'
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Clear cache
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
EOF
    echo -e "${GREEN}Created Dockerfile.app${NC}"
fi

# Create Dockerfile.web if it doesn't exist
if [ ! -f "Dockerfile.web" ]; then
    cat > Dockerfile.web << 'EOF'
FROM nginx:stable-alpine

# Remove default configuration
RUN rm /etc/nginx/conf.d/default.conf

# Copy custom configuration
COPY nginx.conf /etc/nginx/conf.d/

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
EOF
    echo -e "${GREEN}Created Dockerfile.web${NC}"
fi

# Create nginx.conf if it doesn't exist
if [ ! -f "nginx.conf" ]; then
    cat > nginx.conf << 'EOF'
server {
    listen 80;
    server_name localhost;

    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
    echo -e "${GREEN}Created nginx.conf${NC}"
fi

# Create Dockerfile.reverb if it doesn't exist
if [ ! -f "Dockerfile.reverb" ]; then
    cat > Dockerfile.reverb << 'EOF'
FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pcntl bcmath

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expose port 8081 for Reverb
EXPOSE 8081

# Start Reverb server
CMD ["php", "artisan", "reverb:start", "--host", "0.0.0.0", "--port", "8081"]
EOF
    echo -e "${GREEN}Created Dockerfile.reverb${NC}"
fi

# Build and start the containers
echo -e "${YELLOW}Building Docker images...${NC}"
$DOCKER_COMPOSE -f docker-compose.home.yml build --no-cache

echo -e "${YELLOW}Starting services...${NC}"
$DOCKER_COMPOSE -f docker-compose.home.yml up -d

# Wait for services to be healthy (simple sleep, in production you'd want health checks)
echo -e "${YELLOW}Waiting for services to start...${NC}"
sleep 15

# Run Laravel initialization
echo -e "${YELLOW}Initializing Laravel application...${NC}"
$DOCKER_COMPOSE -f docker-compose.home.yml exec app php artisan key:generate --show
$DOCKER_COMPOSE -f docker-compose.home.yml exec app php artisan migrate --force
$DOCKER_COMPOSE -f docker-compose.home.yml exec app php artisan config:cache
$DOCKER_COMPOSE -f docker-compose.home.yml exec app php artisan route:cache
$DOCKER_COMPOSE -f docker-compose.home.yml exec app php artisan view:cache

# Build frontend assets (if Node.js is available in the app container)
echo -e "${YELLOW}Building frontend assets...${NC}"
$DOCKER_COMPOSE -f docker-compose.home.yml exec app npm install --no-fund --no-audit
$DOCKER_COMPOSE -f docker-compose.home.yml exec app npm run build

# Display success message
echo -e "${GREEN}===================================================${NC}"
echo -e "${GREEN}CubeTimer has been successfully deployed!${NC}"
echo -e "${GREEN}===================================================${NC}"
echo -e "${YELLOW}Access your application at:${NC}"
echo -e "${GREEN}  http://$(hostname -I | awk '{print $1}'):80${NC}"
echo -e "${YELLOW}Alternative access points:${NC}"
echo -e "${GREEN}  http://localhost:80${NC}"
echo -e "${GREEN}  http://<your-server-ip>:80${NC}"
echo -e "${YELLOW}Management commands:${NC}"
echo -e "${GREEN}  View logs: $DOCKER_COMPOSE -f docker-compose.home.yml logs -f${NC}"
echo -e "${GREEN}  Restart: $DOCKER_COMPOSE -f docker-compose.home.yml restart${NC}"
echo -e "${GREEN}  Update code: git pull && $DOCKER_COMPOSE -f docker-compose.home.yml build app && $DOCKER_COMPOSE -f docker-compose.home.yml up -d app${NC}"
echo -e "${GREEN}  Rebuild all: $DOCKER_COMPOSE -f docker-compose.home.yml build --no-cache && $DOCKER_COMPOSE -f docker-compose.home.yml up -d${NC}"
echo -e "${YELLOW}Note: For HTTPS, consider adding a reverse proxy like Nginx Proxy Manager or Traefik.${NC}"
echo -e "${GREEN}===================================================${NC}"