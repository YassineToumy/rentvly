FROM node:23-alpine

RUN apk add --no-cache libc6-compat curl

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 3000

CMD ["sh", "-c", "npm run build && npm start"]
