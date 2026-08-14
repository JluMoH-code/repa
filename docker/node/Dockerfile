FROM node:20-alpine

WORKDIR /var/www/html

EXPOSE 5173

# npm install и запуск dev-сервера делаются командой в docker-compose.yml (там же,
# где монтируется исходный код), а не здесь — так изменения package.json не требуют
# пересборки образа.
CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0"]
