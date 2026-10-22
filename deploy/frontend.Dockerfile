# syntax=docker/dockerfile:1.7
# Nuxt 4 SSR. Build context: ../frontend
FROM node:22-alpine AS build
RUN npm install -g pnpm@10
WORKDIR /app
COPY package.json pnpm-lock.yaml ./
RUN --mount=type=cache,target=/root/.local/share/pnpm/store \
    pnpm install --frozen-lockfile
COPY . .
# NUXT_PUBLIC_* qiymatlar ishga tushirishda (runtime) env'dan olinadi, build'da kerak emas
RUN pnpm build

FROM node:22-alpine AS runtime
ENV NODE_ENV=production \
    NITRO_HOST=0.0.0.0 \
    NITRO_PORT=3000
WORKDIR /app
COPY --from=build --chown=node:node /app/.output ./.output
USER node
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --start-interval=2s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:3000/').then(r=>process.exit(r.status<500?0:1)).catch(()=>process.exit(1))"
CMD ["node", ".output/server/index.mjs"]
