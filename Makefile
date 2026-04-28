.PHONY: help certs certs-renew hosts hosts-cleanup up down node-shell php-shell npm-install npm-build npm-dev wayfinder composer-install composer-update test dev setup-local

help:
	@echo "📖 $(PROJECT_NAME) - Available commands"
	@echo ""
	@echo "🚀 Quick start:"
	@echo "  make dev           - Setup and start complete dev environment"
	@echo ""
	@echo "🔐 Certificates & hosts:"
	@echo "  make certs         - Generate TLS certificates (if needed)"
	@echo "  make certs-renew   - Force regenerate TLS certificates"
	@echo "  make hosts         - Update /etc/hosts entries"
	@echo "  make hosts-cleanup - Remove hosts entry from /etc/hosts"
	@echo ""
	@echo "🐳 Docker:"
	@echo "  make up            - Start all Docker services"
	@echo "  make down          - Stop all Docker services"
	@echo "  make node-shell    - Enter Node container shell"
	@echo "  make php-shell     - Enter PHP container shell"
	@echo ""
	@echo "📦 Dependencies:"
	@echo "  make npm-install   - Install npm dependencies"
	@echo "  make npm-build     - Build frontend assets"
	@echo "  make npm-dev       - Start Vite dev server"
	@echo "  make composer-install  - Install Composer dependencies"
	@echo "  make composer-update   - Update Composer dependencies"
	@echo ""
	@echo "🧪 Testing:"
	@echo "  make test          - Run Pest tests"
	@echo "  make test-coverage - Run tests with coverage report"
	@echo ""
	@echo "🔍 Development Tools:"
	@echo "  make wayfinder     - Generate Wayfinder types"
	@echo ""
	@echo "⚙️  Setup:"
	@echo "  make setup-local   - Setup certificates and hosts"
	@echo ""
	@echo "📚 Documentation:"
	@echo "  make help          - Show this help message"

# Project configuration
PROJECT_NAME := peniti
DOMAIN := $(PROJECT_NAME).dev
ALT_DOMAIN ?= pawdesk.$(DOMAIN)
# User ID for running commands inside containers (auto-detected from current user)
UID := $(shell id -u)
GID := $(shell id -g)
# Directory where certificates are saved
CERTS_DIR := .docker/traefik/certs
# Domains to generate certificates for
DOMAINS := $(DOMAIN) "$(ALT_DOMAIN)" "*.$(DOMAIN)"
# Domains to add to /etc/hosts
HOSTS_ENTRIES := $(DOMAIN) $(ALT_DOMAIN) assets.$(DOMAIN) mailpit.$(DOMAIN) traefik.$(DOMAIN)

certs:
	@echo "🔐 Checking TLS certificates..."
	@if [ -f "$(CERTS_DIR)/local.pem" ] && [ -f "$(CERTS_DIR)/local-key.pem" ]; then \
		echo "✓ Certificates already exist in $(CERTS_DIR)/"; \
		echo "  Run 'make certs-renew' to regenerate them"; \
	else \
		$(MAKE) certs-renew; \
	fi

certs-renew:
	@echo "🔐 Generating TLS certificates with mkcert..."
	@mkdir -p $(CERTS_DIR)
	@if ! command -v mkcert >/dev/null 2>&1; then \
		echo "❌ Error: mkcert is not installed. Please install it first:"; \
		echo "  brew install mkcert  # on macOS"; \
		echo "  apt install mkcert   # on Ubuntu/Debian"; \
		echo ""; \
		echo "For other systems, visit: https://github.com/FiloSottile/mkcert"; \
		exit 1; \
	fi
	@mkcert -install
	@mkcert -cert-file $(CERTS_DIR)/local.pem -key-file $(CERTS_DIR)/local-key.pem $(DOMAINS)
	@echo "✅ Certificates generated successfully in $(CERTS_DIR)/"

hosts:
	@echo "🌐 Checking /etc/hosts for required entries..."
	@HOSTS_LINE="127.0.0.1 $(HOSTS_ENTRIES) # $(PROJECT_NAME)-hosts"; \
	if grep -q "# $(PROJECT_NAME)-hosts$$" /etc/hosts; then \
		if grep -q "$$HOSTS_LINE" /etc/hosts; then \
			echo "✅ Hosts entry already up to date"; \
		else \
			echo "🔄 Updating hosts entry (requires sudo)"; \
			sudo sed -i.bak "s|.*# $(PROJECT_NAME)-hosts$$|$$HOSTS_LINE|" /etc/hosts; \
			echo "✅ Hosts entry updated"; \
		fi; \
	else \
		echo "➕ Adding new hosts entry (requires sudo)"; \
		echo "$$HOSTS_LINE" | sudo tee -a /etc/hosts > /dev/null; \
		echo "✅ Hosts entry added"; \
	fi
	@echo "🌐 Hosts configuration complete"

hosts-cleanup:
	@echo "🧹 Removing hosts entry from /etc/hosts..."
	@if grep -q "# $(PROJECT_NAME)-hosts$$" /etc/hosts; then \
		sudo sed -i.bak "/.*# $(PROJECT_NAME)-hosts$$/d" /etc/hosts; \
		echo "✅ Hosts entry removed"; \
	else \
		echo "✅ No hosts entry found"; \
	fi

setup-local: certs hosts
	@echo "✨ Local development environment setup complete!"

up:
	@echo "🐳 Starting Docker services..."
	docker compose up -d
	@echo "✅ Docker services started"

down:
	@echo "🛑 Stopping Docker services..."
	docker compose down
	@echo "✅ Docker services stopped"

node-shell: up
	@echo "📦 Entering Node container shell..."
	docker compose exec --user $(UID):$(GID) node sh

php-shell: up
	@echo "🐘 Entering PHP container shell..."
	docker compose exec --user $(UID):$(GID) app sh

php-shell-root: up
	@echo "🐘 Entering PHP container shell..."
	docker compose exec app sh

npm-install: up
	@echo "📦 Installing npm dependencies..."
	docker compose exec --user $(UID):$(GID) node npm install
	@echo "✅ npm dependencies installed"

npm-build: up
	@echo "🔨 Building frontend assets..."
	docker compose exec --user $(UID):$(GID) node npm run build
	@echo "✅ Frontend assets built"

npm-dev: up
	@echo "🔄 Running development server..."
	docker compose exec --user $(UID):$(GID) node npm run dev

composer-install: up
	@echo "📚 Installing Composer dependencies..."
	docker compose exec --user $(UID):$(GID) app composer install
	@echo "✅ Composer dependencies installed"

composer-update: up
	@echo "⬆️  Updating Composer dependencies..."
	docker compose exec --user $(UID):$(GID) app composer update
	@echo "✅ Composer dependencies updated"

test: up
	@echo "🧪 Running tests..."
	docker compose exec --user $(UID):$(GID) app php artisan test --compact

test-coverage: up
	@echo "🧪 Running tests with coverage..."
	docker compose exec --user $(UID):$(GID) app php artisan test --coverage

wayfinder: up
	@echo "🔍 Generating Wayfinder types..."
	docker compose exec --user $(UID):$(GID) app php artisan wayfinder:types
	@echo "✅ Wayfinder types generated"

dev: setup-local composer-install npm-install npm-dev
	@echo ""
	@echo "🚀 Development environment is ready!"
	@echo "   App: https://$(DOMAIN)"
	@echo "   Mailpit: https://mailpit.$(DOMAIN)"
	@echo "   Traefik: https://traefik.$(DOMAIN)"
