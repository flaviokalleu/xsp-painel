FROM golang:1.22-alpine AS build
WORKDIR /src
RUN apk add --no-cache git ca-certificates
COPY go.mod go.sum* ./
RUN go mod download
COPY . .
ENV CGO_ENABLED=0 GOOS=linux GOARCH=amd64
RUN go build -trimpath -ldflags="-s -w" -o /out/api-license ./cmd/server

FROM gcr.io/distroless/static-debian12:nonroot
COPY --from=build /out/api-license /api-license
COPY --from=build /src/migrations /migrations
USER nonroot:nonroot
EXPOSE 8443
ENTRYPOINT ["/api-license"]
