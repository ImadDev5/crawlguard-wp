#!/bin/bash

# 🚀 Arbiter Platform - WSL Setup & Enhanced Prototype Builder
# This script sets up the complete development environment in WSL

set -e

echo "🚀 Starting Arbiter Platform WSL Setup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Update system
print_status "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install essential packages
print_status "Installing essential packages..."
sudo apt install -y curl wget git build-essential software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Install Node.js 20 LTS
print_status "Installing Node.js 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Docker
print_status "Installing Docker..."
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Add user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
print_status "Installing Docker Compose..."
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install PostgreSQL client
print_status "Installing PostgreSQL client..."
sudo apt install -y postgresql-client

# Install Redis client
print_status "Installing Redis client..."
sudo apt install -y redis-tools

# Install Python and pip (for some development tools)
print_status "Installing Python..."
sudo apt install -y python3 python3-pip

# Create project directory
print_status "Setting up project directory..."
PROJECT_DIR="$HOME/arbiter-platform"
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR"

# Verify installations
print_status "Verifying installations..."
node --version
npm --version
docker --version
docker-compose --version

print_success "WSL environment setup complete!"
print_status "Project directory: $PROJECT_DIR"
print_warning "Please restart your terminal or run 'newgrp docker' to use Docker without sudo"

# Create initial project structure
print_status "Creating enhanced project structure..."

# Create directory structure
mkdir -p {
    frontend/{src/{components/{common,creator,ai-company},pages,hooks,services,types,assets},public},
    backend/{api-gateway,services/{auth,content,payment,analytics,notification,bot-detection,pricing,licensing},shared/{types,utils,middleware}},
    database/{migrations,seeds,schemas},
    docker,
    docs,
    scripts,
    tests/{unit,integration,e2e}
}

print_success "Enhanced project structure created!"

# Copy project files from Windows to WSL
print_status "Ready to copy project files from Windows..."
print_warning "Run this from Windows PowerShell to copy files:"
echo "wsl cp -r /mnt/c/Users/ADMIN/OneDrive/Desktop/plugin/* ~/arbiter-platform/"

print_success "Setup script completed! 🎉"
print_status "Next steps:"
echo "1. Copy project files from Windows"
echo "2. Run 'npm run setup' to install dependencies"
echo "3. Run 'npm run dev' to start the enhanced prototype"
