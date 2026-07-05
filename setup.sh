#!/bin/bash
set -e

echo "🚀 SurveyFlow setup"

echo "📦 Building images..."
docker compose build

echo "🐳 Starting containers..."
docker compose up -d

echo "⏳ Waiting for MySQL to be ready..."
until docker compose exec -T database mysqladmin ping -h localhost --silent 2>/dev/null; do
    sleep 2
done
sleep 3  # extra margin to make sure users have already been created

echo "🎼 Installing PHP dependencies (composer install)..."
docker compose exec -T php composer install

echo "📦 Installing Node dependencies (npm install)..."
docker compose exec -T frontend npm install

if [ ! -f backend/.env.local ]; then
    echo "⚙️  Creating .env.local..."
    echo 'DATABASE_URL="mysql://symfony:symfony_pass@database:3306/app?serverVersion=8.0&charset=utf8mb4"' > backend/.env.local
fi

echo "🗄️  Creating database and running migrations..."
docker compose exec -T php php bin/console doctrine:database:create --if-not-exists
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

echo "✅ Setup complete!"
echo "👉 Symfony: http://localhost:8088"
echo "👉 Vue:     http://localhost:5173"