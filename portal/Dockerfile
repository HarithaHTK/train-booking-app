FROM node:22-alpine

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm install --no-fund --no-audit

COPY docker/start.sh /usr/local/bin/start.sh

ENTRYPOINT ["sh", "/usr/local/bin/start.sh"]