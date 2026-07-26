FROM node:23-alpine

RUN apk add --no-cache libc6-compat curl

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

# Baked at build time for Nuxt public runtime config (browser → host API URL)
ARG NUXT_PUBLIC_API_BASE=http://localhost:8080/api/v1
ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE

RUN npm run build

ENV HOST=0.0.0.0
ENV PORT=3000
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000

EXPOSE 3000

CMD ["npm", "start"]
