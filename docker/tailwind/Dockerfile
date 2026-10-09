FROM alpine:3.20

ARG TARGETARCH
RUN apk add --no-cache curl libstdc++ libgcc \
 && case "$TARGETARCH" in \
      amd64) A=x64 ;; \
      arm64) A=arm64 ;; \
      *) echo "arch non supportata: $TARGETARCH" && exit 1 ;; \
    esac \
 && curl -fsSL -o /usr/local/bin/tailwindcss \
      "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-${A}-musl" \
 && chmod +x /usr/local/bin/tailwindcss

WORKDIR /app
ENTRYPOINT ["tailwindcss"]
